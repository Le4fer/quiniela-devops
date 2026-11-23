<?php
/**
 * Seeder: Inserta datos históricos de copas pasadas
 * Se ejecuta manualmente desde el admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class QWC_Seeder {

    /**
     * Insertar torneos históricos
     */
    public static function seed_historical_tournaments() {
        global $wpdb;
        $tables = QWC_Database::get_tables();

        // Datos de torneos históricos
        $tournaments = array(
            array(
                'name'       => 'Copa Mundial FIFA 2022',
                'type'       => 'worldcup',
                'year'       => 2022,
                'start_date' => '2022-11-20',
                'end_date'   => '2022-12-18',
                'status'     => 'finished',
                'theme'      => 'worldcup',
                'is_archived'=> 1,
            ),
            array(
                'name'       => 'Copa América 2024',
                'type'       => 'copaamerica',
                'year'       => 2024,
                'start_date' => '2024-06-20',
                'end_date'   => '2024-07-14',
                'status'     => 'finished',
                'theme'      => 'copaamerica',
                'is_archived'=> 1,
            ),
            array(
                'name'       => 'Copa Mundial FIFA 2018',
                'type'       => 'worldcup',
                'year'       => 2018,
                'start_date' => '2018-06-14',
                'end_date'   => '2018-07-15',
                'status'     => 'finished',
                'theme'      => 'worldcup',
                'is_archived'=> 1,
            ),
        );

        foreach ($tournaments as $t) {
            // Verificar si ya existe
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tables['tournaments']} WHERE name = %s AND year = %d",
                $t['name'], $t['year']
            ));

            if ($exists) continue;

            $wpdb->insert($tables['tournaments'], $t);
        }

        // Datos de campeones históricos
        $champions = array(
            array(
                'tournament_name' => 'Copa Mundial FIFA 2022',
                'team_name'       => 'Argentina',
                'team_code'       => 'ARG',
                'runner_up'       => 'Francia',
                'third_place'     => 'Croacia',
            ),
            array(
                'tournament_name' => 'Copa América 2024',
                'team_name'       => 'Argentina',
                'team_code'       => 'ARG',
                'runner_up'       => 'Colombia',
                'third_place'     => 'Uruguay',
            ),
            array(
                'tournament_name' => 'Copa Mundial FIFA 2018',
                'team_name'       => 'Francia',
                'team_code'       => 'FRA',
                'runner_up'       => 'Croacia',
                'third_place'     => 'Bélgica',
            ),
        );

        foreach ($champions as $c) {
            // Buscar el torneo
            $tournament = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tables['tournaments']} WHERE name = %s",
                $c['tournament_name']
            ));

            if (!$tournament) continue;

            // Buscar o crear el equipo campeón
            $team = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tables['teams']} WHERE name = %s AND code = %s",
                $c['team_name'], $c['team_code']
            ));

            if (!$team) {
                $wpdb->insert($tables['teams'], array(
                    'tournament_id' => $tournament->id,
                    'name'          => $c['team_name'],
                    'code'          => $c['team_code'],
                    'flag_url'      => 'https://flagcdn.com/w80/' . strtolower($c['team_code']) . '.png',
                ));
                $team_id = $wpdb->insert_id;
            } else {
                $team_id = $team->id;
            }

            // Registrar campeón
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tables['champions']} WHERE tournament_id = %d",
                $tournament->id
            ));

            if (!$exists) {
                $wpdb->insert($tables['champions'], array(
                    'tournament_id'    => $tournament->id,
                    'champion_team_id' => $team_id,
                    'archived_at'      => current_time('mysql'),
                ));
            }
        }

        return true;
    }
}
