<?php
/**
 * Clase para el panel de administración
 * TODO: Implementar funcionalidad completa
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // TODO: Registrar hooks de admin
    }
}
