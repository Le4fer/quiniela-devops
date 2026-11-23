<?php
/**
 * Sistema de temas dinámicos para la Quiniela
 * Cambia la paleta de colores según el tipo de torneo
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Themes {

    /**
     * Paletas de colores por tipo de torneo
     */
    const PALETTES = array(
        'worldcup' => array(
            'primary'       => '#00529B',  // Azul FIFA
            'primary-dark'  => '#003366',
            'secondary'     => '#FFD700',  // Dorado
            'secondary-dark'=> '#B8860B',
            'accent'        => '#FFFFFF',  // Blanco
            'background'    => '#F0F4F8',
            'text'          => '#1A1A1A',
            'gradient'      => 'linear-gradient(135deg, #00529B 0%, #003366 100%)',
            'gradient-gold' => 'linear-gradient(135deg, #FFD700 0%, #B8860B 100%)',
        ),
        'copaamerica' => array(
            'primary'       => '#E30613',  // Rojo CONMEBOL
            'primary-dark'  => '#A00410',
            'secondary'     => '#FFD100',  // Amarillo
            'secondary-dark'=> '#CC9900',
            'accent'        => '#009739',  // Verde
            'background'    => '#FFF5F5',
            'text'          => '#1A1A1A',
            'gradient'      => 'linear-gradient(135deg, #E30613 0%, #A00410 100%)',
            'gradient-gold' => 'linear-gradient(135deg, #FFD100 0%, #CC9900 100%)',
        ),
        'custom' => array(
            'primary'       => '#2271B1',  // WordPress blue (fallback)
            'primary-dark'  => '#135E96',
            'secondary'     => '#00A32A',
            'secondary-dark'=> '#008A23',
            'accent'        => '#FFFFFF',
            'background'    => '#F6F7F7',
            'text'          => '#1D2327',
            'gradient'      => 'linear-gradient(135deg, #2271B1 0%, #135E96 100%)',
            'gradient-gold' => 'linear-gradient(135deg, #00A32A 0%, #008A23 100%)',
        ),
    );

    /**
     * Obtener la paleta del torneo activo
     */
    public static function get_active_palette() {
        $tournament_id = get_option('qwc_default_tournament', 0);
        
        if (!$tournament_id) {
            return self::PALETTES['custom'];
        }

        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        $tournament = $wpdb->get_row($wpdb->prepare(
            "SELECT type, theme FROM {$tables['tournaments']} WHERE id = %d",
            $tournament_id
        ));

        if (!$tournament) {
            return self::PALETTES['custom'];
        }

        $type = $tournament->type;
        $theme = $tournament->theme;

        // Si tiene un tema específico, usarlo; si no, usar el tipo
        if ($theme && isset(self::PALETTES[$theme])) {
            return self::PALETTES[$theme];
        }

        if (isset(self::PALETTES[$type])) {
            return self::PALETTES[$type];
        }

        return self::PALETTES['custom'];
    }

    /**
     * Obtener paleta por ID de torneo específico
     */
    public static function get_palette_by_tournament($tournament_id) {
        if (!$tournament_id) {
            return self::PALETTES['custom'];
        }

        global $wpdb;
        $tables = QWC_Database::get_tables();
        
        $tournament = $wpdb->get_row($wpdb->prepare(
            "SELECT type, theme FROM {$tables['tournaments']} WHERE id = %d",
            $tournament_id
        ));

        if (!$tournament) {
            return self::PALETTES['custom'];
        }

        $type = $tournament->type;
        $theme = $tournament->theme;

        if ($theme && isset(self::PALETTES[$theme])) {
            return self::PALETTES[$theme];
        }

        if (isset(self::PALETTES[$type])) {
            return self::PALETTES[$type];
        }

        return self::PALETTES['custom'];
    }

    /**
     * Generar CSS variables dinámicas
     */
    public static function get_css_variables($palette = null) {
        if (!$palette) {
            $palette = self::get_active_palette();
        }

        $css = ':root {' . "\n";
        foreach ($palette as $key => $value) {
            $css_var = '--qwc-' . str_replace('-', '-', $key);
            $css .= "  {$css_var}: {$value};\n";
        }
        $css .= "}\n";

        return $css;
    }

    /**
     * Obtener el nombre legible del tema
     */
    public static function get_theme_name($type) {
        $names = array(
            'worldcup'    => 'Copa Mundial',
            'copaamerica' => 'Copa América',
            'custom'      => 'Personalizado',
        );
        return isset($names[$type]) ? $names[$type] : 'Personalizado';
    }

    /**
     * Obtener el emoji/icono del tema
     */
    public static function get_theme_icon($type) {
        $icons = array(
            'worldcup'    => '🏆',
            'copaamerica' => '🌎',
            'custom'      => '⚽',
        );
        return isset($icons[$type]) ? $icons[$type] : '⚽';
    }
}
