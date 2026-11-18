<?php
/**
 * Generador automático de fase eliminatoria
 * Crea cruces de Octavos, Cuartos, Semis y Final
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Knockout {

    /**
     * Generar fase eliminatoria completa
     * @param int $tournament_id
     * @param int $num_groups Número de grupos (4, 8, 16...)
     * @param int $per_group Cuántos clasifican por grupo (2 normalmente)
     * @return array Información de los partidos creados
     */
    public static function generate($tournament_id, $num_groups = 8, $per_group = 2) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        // 1. Obtener clasificados
        $qualifiers = QWC_Standings::get_qualifiers($tournament_id, $per_group);

        if (count($qualifiers) < 2) {
            return array('error' => 'Se necesitan al menos 2 grupos con clasificados.');
        }

        $total_teams = count($qualifiers) * $per_group;

        // 2. Determinar ronda inicial según cantidad de equipos
        // 16 equipos → Octavos de final
        // 8 equipos → Cuartos de final
        // 4 equipos → Semifinales
        $rounds = array();
        if ($total_teams >= 16) {
            $rounds[] = 'Octavos de Final';
            $rounds[] = 'Cuartos de Final';
            $rounds[] = 'Semifinal';
            $rounds[] = 'Final';
        } elseif ($total_teams >= 8) {
            $rounds[] = 'Cuartos de Final';
            $rounds[] = 'Semifinal';
            $rounds[] = 'Final';
        } elseif ($total_teams >= 4) {
            $rounds[] = 'Semifinal';
            $rounds[] = 'Final';
        } else {
            return array('error' => 'No hay suficientes equipos para generar eliminatoria.');
        }

        // 3. Generar cruces de la primera ronda
        $first_round_matches = self::generate_first_round($qualifiers, $num_groups, $per_group);

        // 4. Crear partidos en la BD
        $created = array();
        $base_date = strtotime('+1 day'); // Un día después del último partido de grupos

        foreach ($first_round_matches as $index => $match) {
            $match_date = date('Y-m-d H:i:s', $base_date + ($index * 86400)); // 1 partido por día

            $wpdb->insert(
                $tables['matches'],
                array(
                    'tournament_id' => $tournament_id,
                    'group_id'      => null,
                    'round'         => $rounds[0],
                    'home_team_id'  => $match['home'],
                    'away_team_id'  => $match['away'],
                    'match_date'    => $match_date,
                    'status'        => 'scheduled',
                    'created_at'    => current_time('mysql'),
                )
            );

            $created[] = array(
                'round' => $rounds[0],
                'home'  => $match['home_name'],
                'away'  => $match['away_name'],
                'date'  => $match_date,
            );
        }

        // 5. Crear partidos "placeholder" para rondas siguientes (sin equipos definidos aún)
        $matches_per_round = count($first_round_matches) / 2;
        $day_offset = count($first_round_matches) + 1;

        for ($r = 1; $r < count($rounds); $r++) {
            $matches_in_round = pow(2, count($rounds) - 1 - $r);
            
            for ($i = 0; $i < $matches_in_round; $i++) {
                $match_date = date('Y-m-d H:i:s', $base_date + (($day_offset + $i) * 86400));

                $wpdb->insert(
                    $tables['matches'],
                    array(
                        'tournament_id' => $tournament_id,
                        'group_id'      => null,
                        'round'         => $rounds[$r],
                        'home_team_id'  => 0, // Se define cuando termine la ronda anterior
                        'away_team_id'  => 0,
                        'match_date'    => $match_date,
                        'status'        => 'scheduled',
                        'extra_info'    => json_encode(array('pending' => true)),
                        'created_at'    => current_time('mysql'),
                    )
                );
            }
            $day_offset += $matches_in_round;
        }

        return array(
            'success' => true,
            'rounds'  => $rounds,
            'matches' => $created,
            'total'   => count($created) + (count($rounds) - 1),
        );
    }

    /**
     * Generar cruces de la primera ronda (1°A vs 2°B, 1°C vs 2°D, etc.)
     */
    private static function generate_first_round($qualifiers, $num_groups, $per_group) {
        $matches = array();
        $group_names = array_keys($qualifiers);
        $half = count($group_names) / 2;

        // Cruces estándar: 1°A vs 2°B, 1°C vs 2°D, 1°E vs 2°F, 1°G vs 2°H
        for ($i = 0; $i < $half; $i++) {
            $group_a = $group_names[$i * 2];
            $group_b = $group_names[$i * 2 + 1];

            $first_a = $qualifiers[$group_a][0]; // 1° del grupo A
            $second_b = $qualifiers[$group_b][1]; // 2° del grupo B

            $matches[] = array(
                'home'      => $first_a['team_id'],
                'home_name' => $first_a['team_name'],
                'away'      => $second_b['team_id'],
                'away_name' => $second_b['team_name'],
            );

            $first_b = $qualifiers[$group_b][0];
            $second_a = $qualifiers[$group_a][1];

            $matches[] = array(
                'home'      => $first_b['team_id'],
                'home_name' => $first_b['team_name'],
                'away'      => $second_a['team_id'],
                'away_name' => $second_a['team_name'],
            );
        }

        return $matches;
    }

    /**
     * Avanzar ganadores a la siguiente ronda
     * Se ejecuta cuando todos los partidos de una ronda están finalizados
     */
    public static function advance_winners($tournament_id, $current_round) {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        // Obtener partidos finalizados de la ronda actual
        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['matches']} 
             WHERE tournament_id = %d AND round = %s AND status = 'finished'
             ORDER BY match_date ASC",
            $tournament_id, $current_round
        ));

        if (empty($matches)) return array('error' => 'No hay partidos finalizados en esta ronda.');

        // Verificar que TODOS los partidos de la ronda estén finalizados
        $total_in_round = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['matches']} WHERE tournament_id = %d AND round = %s",
            $tournament_id, $current_round
        ));

        if (count($matches) < $total_in_round) {
            return array('error' => "Aún faltan partidos por finalizar en {$current_round}.");
        }

        // Obtener partidos de la siguiente ronda (con equipos pendientes)
        $next_round = self::get_next_round($current_round);
        if (!$next_round) return array('error' => 'No hay siguiente ronda (torneo finalizado).');

        $next_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['matches']} 
             WHERE tournament_id = %d AND round = %s AND home_team_id = 0
             ORDER BY match_date ASC",
            $tournament_id, $next_round
        ));

        // Emparejar ganadores: partido 1 y 2 → siguiente partido 1, etc.
        $winners = array();
        foreach ($matches as $m) {
            if ($m->home_score > $m->away_score) {
                $winners[] = $m->home_team_id;
            } elseif ($m->away_score > $m->home_score) {
                $winners[] = $m->away_team_id;
            } else {
                // Empate en eliminatoria → penales (se define manualmente)
                return array('error' => "Hay un empate en {$current_round}. Define el ganador manualmente.");
            }
        }

        // Asignar ganadores a los partidos de la siguiente ronda
        for ($i = 0; $i < count($next_matches); $i++) {
            if (isset($winners[$i * 2]) && isset($winners[$i * 2 + 1])) {
                $wpdb->update(
                    $tables['matches'],
                    array(
                        'home_team_id' => $winners[$i * 2],
                        'away_team_id' => $winners[$i * 2 + 1],
                        'extra_info'   => null,
                    ),
                    array('id' => $next_matches[$i]->id)
                );
            }
        }

        return array('success' => true, 'next_round' => $next_round, 'winners' => count($winners));
    }

    /**
     * Obtener el nombre de la siguiente ronda
     */
    private static function get_next_round($current) {
        $order = array(
            'Octavos de Final' => 'Cuartos de Final',
            'Cuartos de Final' => 'Semifinal',
            'Semifinal'        => 'Final',
            'Final'            => null,
        );
        return isset($order[$current]) ? $order[$current] : null;
    }
}
