<?php
/**
 * Clase para la REST API
 * TODO: Implementar endpoints completos
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_API {

    /**
     * Registrar las rutas de la API
     */
    public static function register_routes() {
        add_action('rest_api_init', array(__CLASS__, 'init_routes'));
    }

    /**
     * Inicializar rutas
     */
    public static function init_routes() {
        // TODO: Registrar endpoints
        // register_rest_route('qwc/v1', '/matches', array(...));
    }
}
