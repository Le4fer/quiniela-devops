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
        
        // Obtener torneo activo por defecto
        $tournament_id = get_option('qwc_default_tournament', 0);
        if (!$tournament_id) return '<p>No hay un torneo activo configurado.</p>';

        // Obtener partidos programados o en vivo
        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, 
                    ht.name as home_name, ht.code as home_code, ht.flag_url as home_flag,
                    at.name as away_name, at.code as away_code, at.flag_url as away_flag
             FROM {$tables['matches']} m
             LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
             LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
             WHERE m.tournament_id = %d AND m.status IN ('scheduled', 'live')
             ORDER BY m.match_date ASC",
            $tournament_id
        ));

        if (empty($matches)) {
            return '<p>No hay partidos programados próximamente.</p>';
        }

        $user_id = get_current_user_id();
        $html = '<div class="qwc-public-matches">';
        $html .= '<h2>📅 Próximos Partidos</h2>';

        foreach ($matches as $m) {
            // Verificar si el usuario ya hizo pronóstico
            $prediction = null;
            if ($user_id) {
                $qwc_user = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$tables['users']} WHERE wp_user_id = %d", $user_id));
                if ($qwc_user) {
                    $prediction = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$tables['predictions']} WHERE user_id = %d AND match_id = %d",
                        $qwc_user->id, $m->id
                    ));
                }
            }

            $html .= '<div class="qwc-match-card">';
            $html .= '<div class="qwc-match-header">';
            $html .= '<span class="qwc-match-date">' . date('d/m/Y H:i', strtotime($m->match_date)) . '</span>';
            if ($m->status === 'live') {
                $html .= '<span class="qwc-badge qwc-badge-live">🔴 EN VIVO - Min ' . intval($m->current_minute) . '\'</span>';
            }
            $html .= '</div>';

            $html .= '<div class="qwc-match-teams">';
            $html .= '<div class="qwc-team">';
            if ($m->home_flag) $html .= '<img src="' . esc_url($m->home_flag) . '" class="qwc-flag">';
            $html .= '<span class="qwc-team-name">' . esc_html($m->home_name) . '</span>';
            $html .= '</div>';

            $html .= '<div class="qwc-match-score">VS</div>';

            $html .= '<div class="qwc-team">';
            if ($m->away_flag) $html .= '<img src="' . esc_url($m->away_flag) . '" class="qwc-flag">';
            $html .= '<span class="qwc-team-name">' . esc_html($m->away_name) . '</span>';
            $html .= '</div>';
            $html .= '</div>';

            // Formulario de pronóstico
            if ($user_id && $m->status === 'scheduled') {
                $html .= '<form method="post" action="' . admin_url('admin-post.php') . '" class="qwc-prediction-form">';
                $html .= '<input type="hidden" name="action" value="qwc_save_prediction">';
                $html .= '<input type="hidden" name="match_id" value="' . intval($m->id) . '">';
                $html .= '<input type="hidden" name="tournament_id" value="' . intval($tournament_id) . '">';
                $html .= wp_nonce_field('qwc_save_prediction', '_wpnonce', true, false);
                
                $home_val = $prediction ? intval($prediction->home_score) : '';
                $away_val = $prediction ? intval($prediction->away_score) : '';

                $html .= '<div class="qwc-score-inputs">';
                $html .= '<input type="number" name="home_score" min="0" max="20" value="' . esc_attr($home_val) . '" placeholder="0" required>';
                $html .= '<span>-</span>';
                $html .= '<input type="number" name="away_score" min="0" max="20" value="' . esc_attr($away_val) . '" placeholder="0" required>';
                $html .= '<button type="submit" class="button button-primary">Guardar Pronóstico</button>';
                $html .= '</div>';
                $html .= '</form>';
            } elseif (!$user_id) {
                $html .= '<p class="qwc-login-prompt"><a href="' . wp_login_url(get_permalink()) . '">Inicia sesión</a> para hacer tu pronóstico.</p>';
            } elseif ($m->status === 'live') {
                $html .= '<p class="qwc-notice">🔴 El partido está en vivo. Los pronósticos están cerrados.</p>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para ver tu dashboard.</p>';
        }
        return '<div class="qwc-public-dashboard"><h2>👋 Bienvenido a tu Quiniela</h2><p>Ve a la pestaña <strong>Partidos</strong> para hacer tus pronósticos.</p></div>';
    }

    public static function render_standings($atts) {
        return '<p>🏆 Tabla de Posiciones - Próximamente</p>';
    }

    public static function render_groups($atts) {
        return '<p>📊 Fase de Grupos - Próximamente</p>';
    }

    public static function render_legacy_board($atts) {
        return '<p>Este shortcode ha sido actualizado. Usa <code>[qwc_matches]</code> en su lugar.</p>';
    }
}