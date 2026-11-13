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
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'qwc-public',
            QWC_PLUGIN_URL . 'public/css/public.css',
            array(),
            QWC_VERSION
        );

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
        ));
    }
}