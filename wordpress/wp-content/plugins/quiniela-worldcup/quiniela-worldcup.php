<?php
/**
 * Plugin Name: Quiniela World Cup / Copa América
 * Description: Sistema completo de quiniela deportiva con fase de grupos, resultados en vivo y múltiples torneos.
 * Version: 2.0.0
 * Author: Deimer
 * License: GPL-2.0+
 * Text Domain: quiniela-worldcup
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('QWC_VERSION', '2.0.0');
define('QWC_PLUGIN_FILE', __FILE__);
define('QWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QWC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Incluir archivos necesarios
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-database.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-activator.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-deactivator.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-admin.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-public.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-shortcodes.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-api.php';
require_once QWC_PLUGIN_DIR . 'includes/class-qwc-cron.php';

/**
 * Clase principal del plugin
 */
final class Quiniela_World_Cup {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (patrón singleton)
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Activación y desactivación
        register_activation_hook(QWC_PLUGIN_FILE, array('QWC_Activator', 'activate'));
        register_deactivation_hook(QWC_PLUGIN_FILE, array('QWC_Deactivator', 'deactivate'));

        // Inicializar admin y público
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Inicializar componentes
     */
    public function init() {
        // Cargar textos para traducción
        load_plugin_textdomain('quiniela-worldcup', false, dirname(QWC_PLUGIN_BASENAME) . '/languages');

        // Inicializar admin si estamos en el panel
        if (is_admin()) {
            QWC_Admin::get_instance();
        }

        // Inicializar frontend siempre
        QWC_Public::get_instance();

        // Registrar shortcodes
        QWC_Shortcodes::register();

        // Registrar REST API
        QWC_API::register_routes();

        // Inicializar cron jobs
        QWC_Cron::init();
    }
}

// Iniciar el plugin
Quiniela_World_Cup::get_instance();