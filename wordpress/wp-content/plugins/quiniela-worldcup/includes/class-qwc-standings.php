<?php
/**
 * Clase para calcular la tabla de posiciones de cada grupo
 * Estilo FIFA: PJ, PG, PE, PP, GF, GC, DG, PTS
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Standings {

    /**
     * Calcular standings de todos los grupos de un torneo
     */
    public static function get_group_standings($tournament_id) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['groups']} WHERE tournament_id = %d ORDER BY sort_order",
            $tournament_id
        ));

        $standings = array();

        foreach ($groups as $group) {
            $teams = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, code, flag_url FROM {$tables['teams']} WHERE group_id = %d",
                $group->id
            ));

            $group_standings = array();

            foreach ($teams as $team) {
                $stats = self::calculate_team_stats($team->id, $tournament_id);
                $stats['team_id'] = $team->id;
                $stats['team_name'] = $team->name;
                $stats['team_code'] = $team->code;
                $stats['team_flag'] = $team->flag_url;
                $group_standings[] = $stats;
            }

            // Ordenar por: PTS > DG > GF > PG > nombre
            usort($group_standings, function($a, $b) {
                if ($a['points'] !== $b['points']) return $b['points'] - $a['points'];
                if ($a['goal_diff'] !== $b['goal_diff']) return $b['goal_diff'] - $a['goal_diff'];
                if ($a['goals_for'] !== $b['goals_for']) return $b['goals_for'] - $a['goals_for'];
                if ($a['wins'] !== $b['wins']) return $b['wins'] - $a['wins'];
                return strcmp($a['team_name'], $b['team_name']);
            });

            // Asignar posición
            foreach ($group_standings as $pos => &$team) {
                $team['position'] = $pos + 1;
            }
            unset($team);

            $standings[$group->name] = array(
                'group' => $group,
                'teams' => $group_standings,
            );
        }

        return $standings;
    }

    /**
     * Calcular estadísticas de un equipo en un torneo
     */
    private static function calculate_team_stats($team_id, $tournament_id) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        $stats = array(
            'played'   => 0,
            'wins'     => 0,
            'draws'    => 0,
            'losses'   => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_diff' => 0,
            'points'   => 0,
        );

        // Partidos donde el equipo fue local
        $home_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT home_score, away_score, status FROM {$tables['matches']} 
             WHERE tournament_id = %d AND home_team_id = %d AND status = 'finished'",
            $tournament_id, $team_id
        ));

        foreach ($home_matches as $m) {
            $stats['played']++;
            $stats['goals_for'] += intval($m->home_score);
            $stats['goals_against'] += intval($m->away_score);

            if ($m->home_score > $m->away_score) {
                $stats['wins']++;
                $stats['points'] += 3;
            } elseif ($m->home_score == $m->away_score) {
                $stats['draws']++;
                $stats['points'] += 1;
            } else {
                $stats['losses']++;
            }
        }

        // Partidos donde el equipo fue visitante
        $away_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT home_score, away_score, status FROM {$tables['matches']} 
             WHERE tournament_id = %d AND away_team_id = %d AND status = 'finished'",
            $tournament_id, $team_id
        ));

        foreach ($away_matches as $m) {
            $stats['played']++;
            $stats['goals_for'] += intval($m->away_score);
            $stats['goals_against'] += intval($m->home_score);

            if ($m->away_score > $m->home_score) {
                $stats['wins']++;
                $stats['points'] += 3;
            } elseif ($m->home_score == $m->away_score) {
                $stats['draws']++;
                $stats['points'] += 1;
            } else {
                $stats['losses']++;
            }
        }

        $stats['goal_diff'] = $stats['goals_for'] - $stats['goals_against'];

        return $stats;
    }

    /**
     * Obtener los clasificados de cada grupo (top N)
     */
    public static function get_qualifiers($tournament_id, $per_group = 2) {
        $standings = self::get_group_standings($tournament_id);
        $qualifiers = array();

        foreach ($standings as $group_name => $data) {
            $qualified = array_slice($data['teams'], 0, $per_group);
            $qualifiers[$group_name] = $qualified;
        }

        return $qualifiers;
    }
}
