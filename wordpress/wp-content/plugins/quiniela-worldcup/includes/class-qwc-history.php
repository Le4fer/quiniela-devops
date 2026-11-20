<?php
/**
 * Clase para manejar el historial de torneos y estadísticas
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_History {

    /**
     * Archivar un torneo (marcar como finalizado + registrar campeón)
     */
    public static function archive_tournament($tournament_id, $champion_team_id, $runner_up_id = null, $third_place_id = null, $top_scorer_id = null, $top_scorer_goals = 0, $mvp_id = null, $notes = '') {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        // 1. Marcar torneo como archivado
        $wpdb->update(
            $tables['tournaments'],
            array(
                'status'             => 'finished',
                'is_archived'        => 1,
                'champion_team_id'   => $champion_team_id,
                'runner_up_team_id'  => $runner_up_id,
                'top_scorer_team_id' => $top_scorer_id,
                'notes'              => $notes,
            ),
            array('id' => $tournament_id)
        );

        // 2. Registrar en tabla de campeones
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables['champions']} WHERE tournament_id = %d",
            $tournament_id
        ));

        $data = array(
            'tournament_id'      => $tournament_id,
            'champion_team_id'   => $champion_team_id,
            'runner_up_team_id'  => $runner_up_id,
            'third_place_team_id'=> $third_place_id,
            'top_scorer_team_id' => $top_scorer_id,
            'top_scorer_goals'   => intval($top_scorer_goals),
            'mvp_team_id'        => $mvp_id,
            'notes'              => $notes,
            'archived_at'        => current_time('mysql'),
        );

        if ($existing) {
            $wpdb->update($tables['champions'], $data, array('id' => $existing));
        } else {
            $wpdb->insert($tables['champions'], $data);
        }

        // 3. Desactivar todos los partidos pendientes del torneo
        $wpdb->update(
            $tables['matches'],
            array('status' => 'cancelled'),
            array('tournament_id' => $tournament_id, 'status' => 'scheduled')
        );

        return true;
    }

    /**
     * Obtener todos los torneos archivados (historial)
     */
    public static function get_archived_tournaments() {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        return $wpdb->get_results(
            "SELECT t.*, 
                    tc.name as champion_name, tc.code as champion_code, tc.flag_url as champion_flag,
                    tr.name as runner_up_name, tr.code as runner_up_code, tr.flag_url as runner_up_flag
             FROM {$tables['tournaments']} t
             LEFT JOIN {$tables['teams']} tc ON t.champion_team_id = tc.id
             LEFT JOIN {$tables['teams']} tr ON t.runner_up_team_id = tr.id
             WHERE t.is_archived = 1
             ORDER BY t.year DESC"
        );
    }

    /**
     * Obtener un torneo archivado con todos sus detalles
     */
    public static function get_archived_tournament($tournament_id) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $tournament = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, 
                    tc.name as champion_name, tc.code as champion_code, tc.flag_url as champion_flag,
                    tr.name as runner_up_name, tr.code as runner_up_code, tr.flag_url as runner_up_flag,
                    tt.name as third_place_name, tt.flag_url as third_place_flag,
                    ts.name as top_scorer_name, ts.flag_url as top_scorer_flag,
                    tm.name as mvp_name, tm.flag_url as mvp_flag
             FROM {$tables['tournaments']} t
             LEFT JOIN {$tables['teams']} tc ON t.champion_team_id = tc.id
             LEFT JOIN {$tables['teams']} tr ON t.runner_up_team_id = tr.id
             LEFT JOIN {$tables['teams']} tt ON t.third_place_team_id = tt.id
             LEFT JOIN {$tables['teams']} ts ON t.top_scorer_team_id = ts.id
             LEFT JOIN {$tables['teams']} tm ON t.mvp_team_id = tm.id
             WHERE t.id = %d AND t.is_archived = 1",
            $tournament_id
        ));

        if (!$tournament) return null;

        // Obtener tabla final de grupos
        $tournament->group_standings = QWC_Standings::get_group_standings($tournament_id);

        // Obtener partidos de la fase eliminatoria
        $tournament->knockout_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, 
                    ht.name as home_name, ht.flag_url as home_flag,
                    at.name as away_name, at.flag_url as away_flag
             FROM {$tables['matches']} m
             LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
             LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
             WHERE m.tournament_id = %d AND m.group_id IS NULL
             ORDER BY m.round, m.match_date ASC",
            $tournament_id
        ));

        // Obtener tabla de posiciones de usuarios (quiniela)
        $tournament->user_standings = $wpdb->get_results(
            "SELECT u.*, 
                    (SELECT COUNT(*) FROM {$tables['predictions']} p WHERE p.user_id = u.id) as num_predictions
             FROM {$tables['users']} u 
             ORDER BY u.total_points DESC
             LIMIT 10"
        );

        return $tournament;
    }

    /**
     * Obtener estadísticas históricas globales
     */
    public static function get_global_stats() {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $stats = array();

        // Total de torneos archivados
        $stats['total_tournaments'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM {$tables['tournaments']} WHERE is_archived = 1"
        ));

        // Total de partidos jugados
        $stats['total_matches'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM {$tables['matches']} WHERE status = 'finished'"
        ));

        // Total de pronósticos
        $stats['total_predictions'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM {$tables['predictions']}"
        ));

        // Equipos más campeones
        $stats['top_champions'] = $wpdb->get_results(
            "SELECT tc.name, tc.code, tc.flag_url, COUNT(*) as titles
             FROM {$tables['champions']} c
             JOIN {$tables['teams']} tc ON c.champion_team_id = tc.id
             GROUP BY c.champion_team_id
             ORDER BY titles DESC
             LIMIT 5"
        );

        return $stats;
    }

    /**
     * Desarchivar un torneo (para correcciones)
     */
    public static function unarchive_tournament($tournament_id) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $wpdb->update(
            $tables['tournaments'],
            array('is_archived' => 0, 'status' => 'finished'),
            array('id' => $tournament_id)
        );

        $wpdb->delete($tables['champions'], array('tournament_id' => $tournament_id));

        return true;
    }
}
