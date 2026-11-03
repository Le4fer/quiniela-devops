<?php
/**
 * Clase para manejar el schema de la base de datos
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Database {

    /**
     * Versión del schema (para actualizaciones futuras)
     */
    const SCHEMA_VERSION = '1.0.0';

    /**
     * Obtener el nombre de las tablas con prefijo de WordPress
     */
    public static function get_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'qwc_';

        return array(
            'tournaments'  => $prefix . 'tournaments',
            'groups'       => $prefix . 'groups',
            'teams'        => $prefix . 'teams',
            'matches'      => $prefix . 'matches',
            'users'        => $prefix . 'users',
            'predictions'  => $prefix . 'predictions',
            'results'      => $prefix . 'results',
            'leagues'      => $prefix . 'leagues',
            'league_members' => $prefix . 'league_members',
        );
    }

    /**
     * Crear todas las tablas
     */
    public static function create_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();
        $tables = self::get_tables();

        // ============================================
        // TABLA: Torneos (Mundial 2026, Copa América 2024, etc.)
        // ============================================
        $sql = "CREATE TABLE {$tables['tournaments']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type enum('worldcup','copaamerica','custom') NOT NULL DEFAULT 'worldcup',
            year int(4) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            status enum('upcoming','active','finished') NOT NULL DEFAULT 'upcoming',
            theme varchar(50) NOT NULL DEFAULT 'worldcup',
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY year (year),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Grupos (A, B, C, D...)
        // ============================================
        $sql = "CREATE TABLE {$tables['groups']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            name varchar(10) NOT NULL,
            sort_order int(3) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id),
            KEY name (name)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Equipos
        // ============================================
        $sql = "CREATE TABLE {$tables['teams']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            group_id bigint(20) DEFAULT NULL,
            name varchar(100) NOT NULL,
            code varchar(10) NOT NULL,
            flag_url varchar(500) DEFAULT NULL,
            fifa_ranking int(5) DEFAULT NULL,
            stats longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id),
            KEY group_id (group_id),
            KEY code (code)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Partidos
        // ============================================
        $sql = "CREATE TABLE {$tables['matches']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            group_id bigint(20) DEFAULT NULL,
            round varchar(50) DEFAULT NULL,
            home_team_id bigint(20) NOT NULL,
            away_team_id bigint(20) NOT NULL,
            match_date datetime NOT NULL,
            stadium varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            status enum('scheduled','live','finished','postponed','cancelled') NOT NULL DEFAULT 'scheduled',
            home_score int(3) DEFAULT NULL,
            away_score int(3) DEFAULT NULL,
            current_minute int(3) DEFAULT NULL,
            extra_info longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id),
            KEY group_id (group_id),
            KEY home_team_id (home_team_id),
            KEY away_team_id (away_team_id),
            KEY match_date (match_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Usuarios de la quiniela
        // ============================================
        $sql = "CREATE TABLE {$tables['users']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            display_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            avatar_url varchar(500) DEFAULT NULL,
            total_points decimal(10,2) NOT NULL DEFAULT 0.00,
            rank_position int(10) DEFAULT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id),
            KEY email (email),
            KEY total_points (total_points)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Pronósticos
        // ============================================
        $sql = "CREATE TABLE {$tables['predictions']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            match_id bigint(20) NOT NULL,
            tournament_id bigint(20) NOT NULL,
            home_score int(3) NOT NULL,
            away_score int(3) NOT NULL,
            points_earned decimal(10,2) NOT NULL DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_match (user_id, match_id),
            KEY user_id (user_id),
            KEY match_id (match_id),
            KEY tournament_id (tournament_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Resultados (con detalles de goles)
        // ============================================
        $sql = "CREATE TABLE {$tables['results']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            home_score int(3) NOT NULL,
            away_score int(3) NOT NULL,
            scorer_details longtext,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY match_id (match_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Ligas (grupos de amigos/empresa)
        // ============================================
        $sql = "CREATE TABLE {$tables['leagues']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            description text,
            owner_id bigint(20) NOT NULL,
            invite_code varchar(20) NOT NULL,
            is_public tinyint(1) NOT NULL DEFAULT 0,
            max_members int(5) NOT NULL DEFAULT 50,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY invite_code (invite_code),
            KEY tournament_id (tournament_id),
            KEY owner_id (owner_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ============================================
        // TABLA: Miembros de ligas
        // ============================================
        $sql = "CREATE TABLE {$tables['league_members']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            league_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY league_user (league_id, user_id),
            KEY league_id (league_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Guardar versión del schema
        update_option('qwc_schema_version', self::SCHEMA_VERSION);
    }

    /**
     * Eliminar todas las tablas (solo en desinstalación)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = self::get_tables();

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('qwc_schema_version');
    }

    /**
     * Verificar si las tablas existen
     */
    public static function tables_exist() {
        global $wpdb;

        $tables = self::get_tables();
        $existing_tables = $wpdb->get_col("SHOW TABLES");

        foreach ($tables as $table) {
            if (!in_array($table, $existing_tables)) {
                return false;
            }
        }

        return true;
    }
}
