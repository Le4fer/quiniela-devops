<?php
/**
 * Plugin Name: Quiniela World Cup / Copa América
 * Description: Plugin personalizado para gestión de pronósticos deportivos.
 * Version: 1.0.0
 * Author: Deimer
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

// Registrar un shortcode simple para renderizar la tabla de partidos
function quiniela_render_board() {
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
                    <td>🇷 Argentina vs 🇧 Brasil</td>
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

add_shortcode('quiniela_board', 'quiniela_render_board');