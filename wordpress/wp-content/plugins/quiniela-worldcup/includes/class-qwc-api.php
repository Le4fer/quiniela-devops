<?php
/**
 * REST API para la Quiniela
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_API {

    public static function register_routes() {
        add_action('rest_api_init', array(__CLASS__, 'init_routes'));
    }

    public static function init_routes() {
        register_rest_route('qwc/v1', '/live-scores', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'get_live_scores'),
            'permission_callback' => '__return_true', // Público: solo muestra marcadores
        ));
    }

    /**
     * Devolver marcadores de partidos (para actualización en vivo)
     */
    public static function get_live_scores($request) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $tournament_id = intval($request->get_param('tournament'));
        if (!$tournament_id) {
            $tournament_id = intval(get_option('qwc_default_tournament', 0));
        }

        if (!$tournament_id) {
            return rest_ensure_response(array());
        }

        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id, m.status, m.home_score, m.away_score, m.current_minute,
                    ht.name as home_name, ht.code as home_code, ht.flag_url as home_flag,
                    at.name as away_name, at.code as away_code, at.flag_url as away_flag
             FROM {$tables['matches']} m
             LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
             LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
             WHERE m.tournament_id = %d
             ORDER BY m.match_date ASC",
            $tournament_id
        ));

        return rest_ensure_response($matches ? $matches : array());
    }
}