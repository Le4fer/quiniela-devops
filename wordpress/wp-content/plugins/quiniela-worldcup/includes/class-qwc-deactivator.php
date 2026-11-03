<?php
/**
 * Clase para manejar la desactivación del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Deactivator {

    /**
     * Método llamado al desactivar el plugin
     */
    public static function deactivate() {
        // Limpiar cron jobs
        QWC_Cron::clear_scheduled_events();

        // Limpiar caché de rewrites
        flush_rewrite_rules();
    }
}
