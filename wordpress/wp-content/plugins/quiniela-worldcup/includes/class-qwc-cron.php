<?php
/**
 * Clase para tareas programadas (cron jobs)
 * TODO: Implementar tareas automáticas
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Cron {

    /**
     * Inicializar cron jobs
     */
    public static function init() {
        // TODO: Registrar hooks de cron
    }

    /**
     * Limpiar eventos programados
     */
    public static function clear_scheduled_events() {
        // TODO: Limpiar cron jobs al desactivar
    }
}
