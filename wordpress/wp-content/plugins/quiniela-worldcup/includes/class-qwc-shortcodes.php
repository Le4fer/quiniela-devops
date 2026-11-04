<?php
/**
 * Clase para registrar shortcodes
 * TODO: Implementar shortcodes completos
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Shortcodes {

    /**
     * Registrar todos los shortcodes
     */
    public static function register() {
        add_shortcode('qwc_dashboard', array(__CLASS__, 'render_dashboard'));
        add_shortcode('qwc_matches', array(__CLASS__, 'render_matches'));
        add_shortcode('qwc_standings', array(__CLASS__, 'render_standings'));
        add_shortcode('qwc_groups', array(__CLASS__, 'render_groups'));
        
        // Mantener el shortcode antiguo para compatibilidad
        add_shortcode('quiniela_board', array(__CLASS__, 'render_legacy_board'));
    }

    public static function render_dashboard($atts) {
        return '<p>Dashboard de la Quiniela - Próximamente</p>';
    }

    public static function render_matches($atts) {
        return '<p>Lista de Partidos - Próximamente</p>';
    }

    public static function render_standings($atts) {
        return '<p>Tabla de Posiciones - Próximamente</p>';
    }

    public static function render_groups($atts) {
        return '<p>Fase de Grupos - Próximamente</p>';
    }

    /**
     * Shortcode legacy (el que tenías antes)
     */
    public static function render_legacy_board($atts) {
        return '
        <div style="border: 2px solid #0d6efd; padding: 15px; border-radius: 8px; font-family: Arial, sans-serif;">
            <h3 style="color: #0d6efd;">🏆 PRONÓSTICOS DE LA COPA</h3>
            <p>¡Registra tus marcadores antes del inicio de los partidos!</p>
            <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th>Partidos</th>
                        <th>Pronóstico</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🇦🇷 Argentina vs 🇧🇷 Brasil</td>
                        <td><input type="number" min="0" placeholder="0"> - <input type="number" min="0" placeholder="0"></td>
                    </tr>
                    <tr>
                        <td>🇲🇽 México vs 🇺🇸 USA</td>
                        <td><input type="number" min="0" placeholder="0"> - <input type="number" min="0" placeholder="0"></td>
                    </tr>
                    <tr>
                        <td>🇪🇸 España vs 🇫🇷 Francia</td>
                        <td><input type="number" min="0" placeholder="0"> - <input type="number" min="0" placeholder="0"></td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }
}
