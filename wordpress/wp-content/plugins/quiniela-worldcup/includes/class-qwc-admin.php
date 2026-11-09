<?php
/**
 * Clase principal del Panel de Administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Admin {

    private static $instance = null;
    private $tables;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->tables = QWC_Database::get_tables();

        // Registrar menús del admin
        add_action('admin_menu', array($this, 'register_menu'));

        // Cargar assets del admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Registrar columnas personalizadas
        add_action('init', array($this, 'register_post_types'));

        // Procesar formularios
        add_action('admin_post_qwc_save_tournament', array($this, 'handle_save_tournament'));
        add_action('admin_post_qwc_delete_tournament', array($this, 'handle_delete_tournament'));
        add_action('admin_post_qwc_save_team', array($this, 'handle_save_team'));
        add_action('admin_post_qwc_delete_team', array($this, 'handle_delete_team'));
        add_action('admin_post_qwc_save_group', array($this, 'handle_save_group'));      // ← AÑADIR
        add_action('admin_post_qwc_delete_group', array($this, 'handle_delete_group'));  // ← AÑADIR
        add_action('admin_post_qwc_save_match', array($this, 'handle_save_match'));
        add_action('admin_post_qwc_delete_match', array($this, 'handle_delete_match'));
        add_action('admin_post_qwc_save_settings', array($this, 'handle_save_settings'));
    }

    /**
     * Registrar menú principal y submenús
     */
    public function register_menu() {
        // Menú principal
        add_menu_page(
            'Quiniela',
            '🏆 Quiniela',
            'manage_options',
            'quiniela-worldcup',
            array($this, 'render_dashboard'),
            'dashicons-tickets-alt',
            30
        );

        // Submenús
        add_submenu_page(
            'quiniela-worldcup',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'quiniela-worldcup',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Torneos',
            'Torneos',
            'manage_options',
            'qwc-tournaments',
            array($this, 'render_tournaments')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Equipos',
            'Equipos',
            'manage_options',
            'qwc-teams',
            array($this, 'render_teams')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Partidos',
            'Partidos',
            'manage_options',
            'qwc-matches',
            array($this, 'render_matches')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Pronósticos',
            'Pronósticos',
            'manage_options',
            'qwc-predictions',
            array($this, 'render_predictions')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Ligas',
            'Ligas',
            'manage_options',
            'qwc-leagues',
            array($this, 'render_leagues')
        );

        add_submenu_page(
            'quiniela-worldcup',
            'Configuración',
            'Configuración',
            'manage_options',
            'qwc-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Cargar CSS y JS del admin
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'quiniela-worldcup') === false && strpos($hook, 'qwc-') === false) {
            return;
        }

        wp_enqueue_style(
            'qwc-admin',
            QWC_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            QWC_VERSION
        );

        wp_enqueue_script(
            'qwc-admin',
            QWC_PLUGIN_URL . 'public/js/admin.js',
            array('jquery'),
            QWC_VERSION,
            true
        );

        wp_localize_script('qwc-admin', 'qwcAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('qwc_admin_nonce'),
        ));
    }

    /**
     * Renderizar Dashboard
     */
    public function render_dashboard() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    /**
     * Renderizar Torneos
     */
    public function render_tournaments() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-tournaments.php';
    }

    /**
     * Renderizar Equipos
     */
    public function render_teams() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-teams.php';
    }

    /**
     * Renderizar Partidos
     */
    public function render_matches() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-matches.php';
    }

    /**
     * Renderizar Pronósticos
     */
    public function render_predictions() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-predictions.php';
    }

    /**
     * Renderizar Ligas
     */
    public function render_leagues() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-leagues.php';
    }

    /**
     * Renderizar Configuración
     */
    public function render_settings() {
        include QWC_PLUGIN_DIR . 'admin/views/admin-settings.php';
    }

    /**
     * Obtener todos los torneos
     */
    public function get_tournaments() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->tables['tournaments']} ORDER BY year DESC");
    }

    /**
     * Obtener un torneo por ID
     */
    public function get_tournament($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['tournaments']} WHERE id = %d",
            $id
        ));
    }

    /**
     * Obtener equipos por torneo
     */
    public function get_teams_by_tournament($tournament_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, g.name as group_name 
             FROM {$this->tables['teams']} t
             LEFT JOIN {$this->tables['groups']} g ON t.group_id = g.id
             WHERE t.tournament_id = %d
             ORDER BY g.sort_order, t.name",
            $tournament_id
        ));
    }

    /**
     * Obtener grupos por torneo
     */
    public function get_groups_by_tournament($tournament_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['groups']} WHERE tournament_id = %d ORDER BY sort_order",
            $tournament_id
        ));
    }

    /**
     * Obtener partidos por torneo
     */
    public function get_matches_by_tournament($tournament_id, $status = null) {
        global $wpdb;
        $sql = "SELECT m.*, 
                       ht.name as home_team_name, ht.code as home_team_code, ht.flag_url as home_flag,
                       at.name as away_team_name, at.code as away_team_code, at.flag_url as away_flag,
                       g.name as group_name
                FROM {$this->tables['matches']} m
                LEFT JOIN {$this->tables['teams']} ht ON m.home_team_id = ht.id
                LEFT JOIN {$this->tables['teams']} at ON m.away_team_id = at.id
                LEFT JOIN {$this->tables['groups']} g ON m.group_id = g.id
                WHERE m.tournament_id = %d";
        
        $params = array($tournament_id);
        
        if ($status) {
            $sql .= " AND m.status = %s";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY m.match_date ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Obtener estadísticas generales
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        $stats['tournaments'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['tournaments']}");
        $stats['teams'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['teams']}");
        $stats['matches'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['matches']}");
        $stats['predictions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['predictions']}");
        $stats['users'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['users']}");
        $stats['live_matches'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['matches']} WHERE status = 'live'");
        
        return $stats;
    }

    /**
     * Procesar guardado de torneo
     */
    public function handle_save_tournament() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_save_tournament');

        global $wpdb;

        $data = array(
            'name'       => sanitize_text_field($_POST['name']),
            'type'       => sanitize_text_field($_POST['type']),
            'year'       => intval($_POST['year']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date'   => sanitize_text_field($_POST['end_date']),
            'status'     => sanitize_text_field($_POST['status']),
            'theme'      => sanitize_text_field($_POST['theme']),
        );

        if (!empty($_POST['tournament_id'])) {
            // Actualizar
            $wpdb->update(
                $this->tables['tournaments'],
                $data,
                array('id' => intval($_POST['tournament_id']))
            );
            $message = 'Torneo actualizado correctamente';
        } else {
            // Crear nuevo
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->tables['tournaments'], $data);
            $message = 'Torneo creado correctamente';
        }

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-tournaments',
            'message' => urlencode($message),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar eliminación de torneo
     */
    public function handle_delete_tournament() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_delete_tournament');

        global $wpdb;
        $id = intval($_POST['tournament_id']);

        $wpdb->delete($this->tables['tournaments'], array('id' => $id));

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-tournaments',
            'message' => urlencode('Torneo eliminado'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar guardado de equipo
     */
    public function handle_save_team() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_save_team');

        global $wpdb;

        $data = array(
            'tournament_id' => intval($_POST['tournament_id']),
            'group_id'      => !empty($_POST['group_id']) ? intval($_POST['group_id']) : null,
            'name'          => sanitize_text_field($_POST['name']),
            'code'          => strtoupper(sanitize_text_field($_POST['code'])),
            'flag_url'      => esc_url_raw($_POST['flag_url']),
            'fifa_ranking'  => !empty($_POST['fifa_ranking']) ? intval($_POST['fifa_ranking']) : null,
        );

        if (!empty($_POST['team_id'])) {
            $wpdb->update(
                $this->tables['teams'],
                $data,
                array('id' => intval($_POST['team_id']))
            );
            $message = 'Equipo actualizado';
        } else {
            $wpdb->insert($this->tables['teams'], $data);
            $message = 'Equipo creado';
        }

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-teams',
            'message' => urlencode($message),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar eliminación de equipo
     */
    public function handle_delete_team() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_delete_team');

        global $wpdb;
        $id = intval($_POST['team_id']);

        $wpdb->delete($this->tables['teams'], array('id' => $id));

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-teams',
            'message' => urlencode('Equipo eliminado'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar guardado de partido
     */
    public function handle_save_match() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_save_match');

        global $wpdb;

        $data = array(
            'tournament_id'  => intval($_POST['tournament_id']),
            'group_id'       => !empty($_POST['group_id']) ? intval($_POST['group_id']) : null,
            'round'          => sanitize_text_field($_POST['round']),
            'home_team_id'   => intval($_POST['home_team_id']),
            'away_team_id'   => intval($_POST['away_team_id']),
            'match_date'     => sanitize_text_field($_POST['match_date']),
            'stadium'        => sanitize_text_field($_POST['stadium']),
            'city'           => sanitize_text_field($_POST['city']),
            'status'         => sanitize_text_field($_POST['status']),
            'home_score'     => isset($_POST['home_score']) && $_POST['home_score'] !== '' ? intval($_POST['home_score']) : null,
            'away_score'     => isset($_POST['away_score']) && $_POST['away_score'] !== '' ? intval($_POST['away_score']) : null,
            'current_minute' => !empty($_POST['current_minute']) ? intval($_POST['current_minute']) : null,
        );

        if (!empty($_POST['match_id'])) {
            $wpdb->update(
                $this->tables['matches'],
                $data,
                array('id' => intval($_POST['match_id']))
            );
            $message = 'Partido actualizado';
        } else {
            $wpdb->insert($this->tables['matches'], $data);
            $message = 'Partido creado';
        }

        // Si el partido terminó, calcular puntos automáticamente
        if ($data['status'] === 'finished' && $data['home_score'] !== null) {
            $this->calculate_predictions_points(intval($_POST['match_id'] ?? $_POST['match_id']));
        }

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-matches',
            'message' => urlencode($message),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar eliminación de partido
     */
    public function handle_delete_match() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_delete_match');

        global $wpdb;
        $id = intval($_POST['match_id']);

        $wpdb->delete($this->tables['matches'], array('id' => $id));

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-matches',
            'message' => urlencode('Partido eliminado'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar guardado de configuración
     */
    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_save_settings');

        $settings = array(
            'qwc_default_tournament'    => intval($_POST['default_tournament']),
            'qwc_points_exact_score'    => floatval($_POST['points_exact_score']),
            'qwc_points_correct_result' => floatval($_POST['points_correct_result']),
            'qwc_points_correct_winner' => floatval($_POST['points_correct_winner']),
            'qwc_points_correct_score'  => floatval($_POST['points_correct_score']),
            'qwc_enable_live_updates'   => isset($_POST['enable_live_updates']) ? 1 : 0,
            'qwc_live_update_interval'  => intval($_POST['live_update_interval']),
            'qwc_theme'                 => sanitize_text_field($_POST['theme']),
        );

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        wp_redirect(add_query_arg(array(
            'page'    => 'qwc-settings',
            'message' => urlencode('Configuración guardada'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Calcular puntos de pronósticos cuando un partido termina
     */
    private function calculate_predictions_points($match_id) {
        global $wpdb;

        $match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['matches']} WHERE id = %d",
            $match_id
        ));

        if (!$match) return;

        $predictions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['predictions']} WHERE match_id = %d",
            $match_id
        ));

        $points_exact = floatval(get_option('qwc_points_exact_score', 10));
        $points_result = floatval(get_option('qwc_points_correct_result', 5));
        $points_winner = floatval(get_option('qwc_points_correct_winner', 3));

        foreach ($predictions as $prediction) {
            $points = 0;

            // Resultado exacto
            if ($prediction->home_score == $match->home_score && $prediction->away_score == $match->away_score) {
                $points = $points_exact;
            }
            // Resultado correcto (mismo ganador o empate)
            elseif (
                ($prediction->home_score > $prediction->away_score && $match->home_score > $match->away_score) ||
                ($prediction->home_score < $prediction->away_score && $match->home_score < $match->away_score) ||
                ($prediction->home_score == $prediction->away_score && $match->home_score == $match->away_score)
            ) {
                $points = $points_result;
            }
            // Ganador correcto
            elseif (
                ($prediction->home_score > $prediction->away_score && $match->home_score > $match->away_score) ||
                ($prediction->home_score < $prediction->away_score && $match->home_score < $match->away_score)
            ) {
                $points = $points_winner;
            }

            $wpdb->update(
                $this->tables['predictions'],
                array('points_earned' => $points),
                array('id' => $prediction->id)
            );

            // Actualizar puntos totales del usuario
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(points_earned), 0) FROM {$this->tables['predictions']} WHERE user_id = %d",
                $prediction->user_id
            ));

            $wpdb->update(
                $this->tables['users'],
                array('total_points' => $total),
                array('id' => $prediction->user_id)
            );
        }
    }

    /**
     * Placeholder para post types (no usado aún pero preparado)
     */
    public function register_post_types() {
        // Reservado para futuras extensiones
    }

        /**
     * Procesar guardado de grupo
     */
    public function handle_save_group() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_save_group');

        global $wpdb;

        $tournament_id = intval($_POST['tournament_id']);
        $group_name = strtoupper(sanitize_text_field($_POST['group_name']));
        $sort_order = intval($_POST['sort_order']);

        // Verificar si ya existe un grupo con ese nombre en este torneo
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['groups']} WHERE tournament_id = %d AND name = %s",
            $tournament_id,
            $group_name
        ));

        if ($exists) {
            wp_redirect(add_query_arg(array(
                'page'    => 'qwc-teams',
                'tournament' => $tournament_id,
                'error'   => urlencode('El grupo ' . $group_name . ' ya existe'),
            ), admin_url('admin.php')));
            exit;
        }

        $wpdb->insert(
            $this->tables['groups'],
            array(
                'tournament_id' => $tournament_id,
                'name'          => $group_name,
                'sort_order'    => $sort_order,
                'created_at'    => current_time('mysql'),
            )
        );

        wp_redirect(add_query_arg(array(
            'page'      => 'qwc-teams',
            'tournament' => $tournament_id,
            'message'   => urlencode('Grupo ' . $group_name . ' creado correctamente'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Procesar eliminación de grupo
     */
    public function handle_delete_group() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('qwc_delete_group');

        global $wpdb;
        $group_id = intval($_POST['group_id']);
        $tournament_id = intval($_POST['tournament_id']);

        // Eliminar el grupo (los equipos quedan sin grupo)
        $wpdb->delete($this->tables['groups'], array('id' => $group_id));

        wp_redirect(add_query_arg(array(
            'page'      => 'qwc-teams',
            'tournament' => $tournament_id,
            'message'   => urlencode('Grupo eliminado'),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Generar automáticamente grupos para un torneo
     */
    public function generate_groups($tournament_id, $num_groups = 8) {
        global $wpdb;

        $groups = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
        
        for ($i = 0; $i < $num_groups && $i < count($groups); $i++) {
            $wpdb->insert(
                $this->tables['groups'],
                array(
                    'tournament_id' => $tournament_id,
                    'name'          => $groups[$i],
                    'sort_order'    => $i + 1,
                    'created_at'    => current_time('mysql'),
                )
            );
        }

        return $num_groups;
    }
}
