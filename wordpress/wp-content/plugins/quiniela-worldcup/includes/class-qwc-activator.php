<?php
/**
 * Clase para manejar la activación del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Activator {

    /**
     * Método llamado al activar el plugin
     */
    public static function activate() {
        // Crear tablas de la base de datos
        QWC_Database::create_tables();

        // Crear páginas necesarias
        self::create_pages();

        // Establecer opciones por defecto
        self::set_default_options();

        // Limpiar caché de rewrites
        flush_rewrite_rules();
    }

    /**
     * Crear páginas necesarias para el plugin
     */
    private static function create_pages() {
        $pages = array(
            'quiniela-dashboard' => array(
                'title'   => 'Mi Quiniela',
                'content' => '[qwc_dashboard]',
            ),
            'quiniela-matches' => array(
                'title'   => 'Partidos',
                'content' => '[qwc_matches]',
            ),
            'quiniela-standings' => array(
                'title'   => 'Tabla de Posiciones',
                'content' => '[qwc_standings]',
            ),
            'quiniela-groups' => array(
                'title'   => 'Fase de Grupos',
                'content' => '[qwc_groups]',
            ),
        );

        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);

            if (!$existing_page) {
                wp_insert_post(array(
                    'post_title'   => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_slug'    => $slug,
                ));
            }
        }
    }

    /**
     * Establecer opciones por defecto
     */
    private static function set_default_options() {
        $defaults = array(
            'qwc_default_tournament'    => 0,
            'qwc_points_exact_score'    => 10,
            'qwc_points_correct_result' => 5,
            'qwc_points_correct_winner' => 3,
            'qwc_points_correct_score'  => 1,
            'qwc_enable_live_updates'   => 1,
            'qwc_live_update_interval'  => 30,
            'qwc_theme'                 => 'worldcup',
        );

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
}
