<?php
if (!defined('ABSPATH')) exit;

class QWC_Shortcodes {

    public static function register() {
        add_shortcode('qwc_dashboard', array(__CLASS__, 'render_dashboard'));
        add_shortcode('qwc_matches', array(__CLASS__, 'render_matches'));
        add_shortcode('qwc_standings', array(__CLASS__, 'render_standings'));
        add_shortcode('qwc_groups', array(__CLASS__, 'render_groups'));
        add_shortcode('quiniela_board', array(__CLASS__, 'render_legacy_board'));
    }

    public static function render_matches($atts) {
        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        // Mostrar mensaje de éxito/error si existe
        $html = '';
        if (isset($_GET['qwc_message'])) {
            $message = sanitize_text_field($_GET['qwc_message']);
            $status = isset($_GET['qwc_status']) && $_GET['qwc_status'] === 'success' ? 'success' : 'error';
            
            $html .= '<div class="qwc-notice notice notice-' . esc_attr($status) . ' is-dismissible" style="margin-bottom: 20px; padding: 12px; border-left: 4px solid ' . ($status === 'success' ? '#00a32a' : '#d63638') . '; background: #fff;">';
            $html .= '<p style="margin: 0;">' . esc_html($message) . '</p>';
            $html .= '</div>';
            
            // Limpiar los parámetros de la URL para que no se queden mostrando
            echo '<script>
                if (window.history.replaceState) {
                    var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: newUrl}, "", newUrl);
                }
            </script>';
        }
        
        // Obtener torneo activo por defecto
        $tournament_id = get_option('qwc_default_tournament', 0);
        if (!$tournament_id) return $html . '<p>No hay un torneo activo configurado.</p>';
        
        // Si no hay torneo por defecto, obtener el primero disponible
        if (!$tournament_id) {
            $tournament_id = $wpdb->get_var("SELECT id FROM {$tables['tournaments']} LIMIT 1");
            if ($tournament_id) {
                update_option('qwc_default_tournament', $tournament_id);
            }
        }
        
        if (!$tournament_id) {
            return '<div class="qwc-notice qwc-error"><strong>⚠️ Error:</strong> No hay torneos disponibles. Contacta al administrador.</div>';
        }

        // Obtener partidos
        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, 
                    ht.name as home_name, ht.code as home_code, ht.flag_url as home_flag,
                    at.name as away_name, at.code as away_code, at.flag_url as away_flag,
                    g.name as group_name
             FROM {$tables['matches']} m
             LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
             LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
             LEFT JOIN {$tables['groups']} g ON m.group_id = g.id
             WHERE m.tournament_id = %d 
             AND m.status IN ('scheduled', 'live')
             ORDER BY m.match_date ASC",
            $tournament_id
        ));

        // Debug: mostrar información si es admin
        $html = '';
        if (current_user_can('administrator') && isset($_GET['qwc_debug'])) {
            $html .= '<div style="background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;">';
            $html .= '<strong>🔍 Debug Info:</strong><br>';
            $html .= 'Tournament ID: ' . intval($tournament_id) . '<br>';
            $html .= 'Total Matches: ' . count($matches) . '<br>';
            $html .= 'User ID: ' . get_current_user_id() . '<br>';
            $html .= 'Is Logged In: ' . (is_user_logged_in() ? 'Yes' : 'No');
            $html .= '</div>';
        }

        if (empty($matches)) {
            $html .= '<div class="qwc-notice qwc-info">';
            $html .= '<strong>📋 No hay partidos programados</strong><br>';
            $html .= '<small>No hay partidos con estado "scheduled" o "live" para este torneo.</small>';
            if (current_user_can('administrator')) {
                $html .= '<br><a href="' . admin_url('admin.php?page=qwc-matches&tournament=' . $tournament_id) . '" class="button">Ir a gestionar partidos</a>';
            }
            $html .= '</div>';
            return $html;
        }

        $user_id = get_current_user_id();
        $html .= '<div class="qwc-public-matches">';
        $html .= '<h2> Próximos Partidos</h2>';

        foreach ($matches as $m) {
            // Verificar si el usuario ya hizo pronóstico
            $prediction = null;
            if ($user_id) {
                $qwc_user = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$tables['users']} WHERE wp_user_id = %d",
                    $user_id
                ));
                if ($qwc_user) {
                    $prediction = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$tables['predictions']} WHERE user_id = %d AND match_id = %d",
                        $qwc_user->id, $m->id
                    ));
                }
            }

            $html .= '<div class="qwc-match-card">';
            
            // Header con fecha y grupo
            $html .= '<div class="qwc-match-header">';
            $html .= '<span class="qwc-match-date">📅 ' . date('d/m/Y H:i', strtotime($m->match_date)) . '</span>';
            if ($m->group_name) {
                $html .= '<span class="qwc-badge">Grupo ' . esc_html($m->group_name) . '</span>';
            }
            if ($m->status === 'live') {
                $html .= '<span class="qwc-badge qwc-badge-live">🔴 EN VIVO - Min ' . intval($m->current_minute) . "'</span>";
            }
            $html .= '</div>';

            // Equipos
            $html .= '<div class="qwc-match-teams">';
            $html .= '<div class="qwc-team">';
            if ($m->home_flag) {
                $html .= '<img src="' . esc_url($m->home_flag) . '" class="qwc-flag" alt="' . esc_attr($m->home_code) . '">';
            }
            $html .= '<span class="qwc-team-name">' . esc_html($m->home_name) . '</span>';
            $html .= '</div>';

            $html .= '<div class="qwc-match-score">VS</div>';

            $html .= '<div class="qwc-team">';
            if ($m->away_flag) {
                $html .= '<img src="' . esc_url($m->away_flag) . '" class="qwc-flag" alt="' . esc_attr($m->away_code) . '">';
            }
            $html .= '<span class="qwc-team-name">' . esc_html($m->away_name) . '</span>';
            $html .= '</div>';
            $html .= '</div>';

            // Formulario de pronóstico
            if ($user_id && $m->status === 'scheduled') {
                $html .= '<form method="post" action="' . admin_url('admin-post.php') . '" class="qwc-prediction-form">';
                $html .= '<input type="hidden" name="action" value="qwc_save_prediction">';
                $html .= '<input type="hidden" name="match_id" value="' . intval($m->id) . '">';
                $html .= '<input type="hidden" name="tournament_id" value="' . intval($tournament_id) . '">';
                $html .= '<input type="hidden" name="redirect_url" value="' . esc_url(get_permalink()) . '">';
                $html .= wp_nonce_field('qwc_save_prediction', '_wpnonce', true, false);
                
                $home_val = $prediction ? intval($prediction->home_score) : '';
                $away_val = $prediction ? intval($prediction->away_score) : '';

                if ($prediction) {
                    $html .= '<p style="margin-bottom: 10px; color: #646970; font-size: 13px;">️ Tu pronóstico actual:</p>';
                }

                $html .= '<div class="qwc-score-inputs">';
                $html .= '<input type="number" name="home_score" min="0" max="20" value="' . esc_attr($home_val) . '" placeholder="0" required>';
                $html .= '<span>-</span>';
                $html .= '<input type="number" name="away_score" min="0" max="20" value="' . esc_attr($away_val) . '" placeholder="0" required>';
                $html .= '<button type="submit" class="button button-primary">' . ($prediction ? 'Actualizar' : 'Guardar') . ' Pronóstico</button>';
                $html .= '</div>';
                $html .= '</form>';
            } elseif (!$user_id) {
                $html .= '<p class="qwc-login-prompt">🔐 <a href="' . wp_login_url(get_permalink()) . '">Inicia sesión</a> para hacer tu pronóstico.</p>';
            } elseif ($m->status === 'live') {
                $html .= '<p class="qwc-notice qwc-live">🔴 El partido está en vivo. Los pronósticos están cerrados.</p>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>🔐 Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para ver tu dashboard.</p>';
        }
        
        global $wpdb;
        $tables = QWC_Database::get_tables();
        $user_id = get_current_user_id();
        
        // Obtener estadísticas del usuario
        $qwc_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['users']} WHERE wp_user_id = %d",
            $user_id
        ));
        
        $total_predictions = 0;
        $total_points = 0;
        
        if ($qwc_user) {
            $total_predictions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['predictions']} WHERE user_id = %d",
                $qwc_user->id
            ));
            $total_points = floatval($qwc_user->total_points);
        }
        
        $html = '<div class="qwc-public-dashboard">';
        $html .= '<h2> Bienvenido, ' . esc_html(wp_get_current_user()->display_name) . '</h2>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">';
        $html .= '<div style="background: #f6f7f7; padding: 20px; border-radius: 8px; text-align: center;">';
        $html .= '<div style="font-size: 32px; font-weight: bold; color: #2271b1;">' . intval($total_predictions) . '</div>';
        $html .= '<div style="color: #646970;">Pronósticos Realizados</div>';
        $html .= '</div>';
        $html .= '<div style="background: #f6f7f7; padding: 20px; border-radius: 8px; text-align: center;">';
        $html .= '<div style="font-size: 32px; font-weight: bold; color: #00a32a;">' . number_format($total_points, 1) . '</div>';
        $html .= '<div style="color: #646970;">Puntos Totales</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p><a href="' . get_permalink(get_page_by_path('partidos')) . '" class="button button-primary">📅 Ver Partidos y Hacer Pronósticos</a></p>';
        $html .= '</div>';
        
        return $html;
    }

    public static function render_standings($atts) {
        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        $tournament_id = get_option('qwc_default_tournament', 0);
        if (!$tournament_id) {
            return '<p>No hay torneo activo.</p>';
        }
        
        // Obtener usuarios con sus puntos
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, wu.display_name, wu.user_email 
             FROM {$tables['users']} u
             LEFT JOIN {$wpdb->users} wu ON u.wp_user_id = wu.ID
             WHERE EXISTS (
                 SELECT 1 FROM {$tables['predictions']} p 
                 WHERE p.user_id = u.id AND p.tournament_id = %d
             )
             ORDER BY u.total_points DESC, u.created_at ASC",
            $tournament_id
        ));
        
        if (empty($users)) {
            return '<p>Aún no hay participantes con pronósticos.</p>';
        }
        
        $html = '<div class="qwc-standings">';
        $html .= '<h2>🏆 Tabla de Posiciones</h2>';
        $html .= '<table class="qwc-table">';
        $html .= '<thead><tr><th>#</th><th>Usuario</th><th>Pronósticos</th><th>Puntos</th></tr></thead>';
        $html .= '<tbody>';
        
        $rank = 1;
        foreach ($users as $user) {
            $predictions_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['predictions']} WHERE user_id = %d AND tournament_id = %d",
                $user->id, $tournament_id
            ));
            
            $html .= '<tr>';
            $html .= '<td><strong>' . $rank++ . '</strong></td>';
            $html .= '<td>' . esc_html($user->display_name) . '</td>';
            $html .= '<td>' . intval($predictions_count) . '</td>';
            $html .= '<td><strong style="color: #00a32a;">' . number_format($user->total_points, 1) . '</strong></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table></div>';
        return $html;
    }

    public static function render_groups($atts) {
        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        $tournament_id = get_option('qwc_default_tournament', 0);
        if (!$tournament_id) {
            return '<p>No hay torneo activo.</p>';
        }
        
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['groups']} WHERE tournament_id = %d ORDER BY sort_order",
            $tournament_id
        ));
        
        if (empty($groups)) {
            return '<p>No hay grupos configurados para este torneo.</p>';
        }
        
        $html = '<div class="qwc-groups">';
        $html .= '<h2>📊 Fase de Grupos</h2>';
        
        foreach ($groups as $group) {
            $html .= '<div class="qwc-group" style="margin-bottom: 30px;">';
            $html .= '<h3>Grupo ' . esc_html($group->name) . '</h3>';
            
            // Obtener equipos de este grupo
            $teams = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tables['teams']} WHERE group_id = %d ORDER BY name",
                $group->id
            ));
            
            if (empty($teams)) {
                $html .= '<p>No hay equipos en este grupo.</p>';
            } else {
                $html .= '<table class="qwc-table">';
                $html .= '<thead><tr><th>Equipo</th><th>Código</th></tr></thead>';
                $html .= '<tbody>';
                foreach ($teams as $team) {
                    $html .= '<tr>';
                    $html .= '<td>';
                    if ($team->flag_url) {
                        $html .= '<img src="' . esc_url($team->flag_url) . '" class="qwc-flag" style="margin-right: 8px;">';
                    }
                    $html .= esc_html($team->name) . '</td>';
                    $html .= '<td><code>' . esc_html($team->code) . '</code></td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    public static function render_legacy_board($atts) {
        return '<p>🔄 Este shortcode ha sido actualizado. Usa <code>[qwc_matches]</code> en su lugar.</p>';
    }
}