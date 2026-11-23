<?php
if (!defined('ABSPATH')) exit;

class QWC_Public {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('body_class', array($this, 'add_theme_body_class'));
    }

    /**
     * Cargar assets según el tema activo
     */
    public function enqueue_assets() {
        // CSS base (siempre se carga)
        wp_enqueue_style(
            'qwc-public',
            QWC_PLUGIN_URL . 'public/css/public.css',
            array(),
            QWC_VERSION
        );

        // Detectar tema activo y cargar CSS temático
        $palette = QWC_Themes::get_active_palette();
        $theme_type = $this->get_active_theme_type();

        if ($theme_type === 'worldcup') {
            wp_enqueue_style(
                'qwc-theme-worldcup',
                QWC_PLUGIN_URL . 'public/css/theme-worldcup.css',
                array('qwc-public'),
                QWC_VERSION
            );
        } elseif ($theme_type === 'copaamerica') {
            wp_enqueue_style(
                'qwc-theme-copaamerica',
                QWC_PLUGIN_URL . 'public/css/theme-copaamerica.css',
                array('qwc-public'),
                QWC_VERSION
            );
        }

        // CSS variables dinámicas inline
        $css_vars = QWC_Themes::get_css_variables($palette);
        wp_add_inline_style('qwc-public', $css_vars);

        // JS público
        wp_enqueue_script(
            'qwc-public',
            QWC_PLUGIN_URL . 'public/js/public.js',
            array(),
            QWC_VERSION,
            true
        );

        wp_localize_script('qwc-public', 'qwcPublic', array(
            'restUrl'       => esc_url_raw(rest_url()),
            'tournamentId'  => intval(get_option('qwc_default_tournament', 0)),
            'updateInterval'=> intval(get_option('qwc_live_update_interval', 30)),
            'themeType'     => $theme_type,
            'themeIcon'     => QWC_Themes::get_theme_icon($theme_type),
        ));
    }

    /**
     * Añadir clase de tema al body
     */
    public function add_theme_body_class($classes) {
        $theme_type = $this->get_active_theme_type();
        $classes[] = 'qwc-theme-' . $theme_type;
        return $classes;
    }

    /**
     * Obtener el tipo de tema activo
     */
    private function get_active_theme_type() {
        $tournament_id = get_option('qwc_default_tournament', 0);
        
        if (!$tournament_id) {
            return 'custom';
        }

        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        $tournament = $wpdb->get_row($wpdb->prepare(
            "SELECT type, theme FROM {$tables['tournaments']} WHERE id = %d",
            $tournament_id
        ));

        if (!$tournament) {
            return 'custom';
        }

        return $tournament->theme ?: $tournament->type;
    }
}