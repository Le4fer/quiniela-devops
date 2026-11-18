<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$admin = QWC_Admin::get_instance();
$tables = $admin->tables;

$tournaments = $admin->get_tournaments();
$selected_tournament = isset($_GET['tournament']) ? intval($_GET['tournament']) : (get_option('qwc_default_tournament', 0));

$standings = $selected_tournament ? QWC_Standings::get_group_standings($selected_tournament) : array();

// Partidos de fase eliminatoria
$knockout_matches = array();
if ($selected_tournament) {
    $knockout_matches = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, 
                ht.name as home_name, ht.flag_url as home_flag,
                at.name as away_name, at.flag_url as away_flag
         FROM {$tables['matches']} m
         LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
         LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
         WHERE m.tournament_id = %d AND m.group_id IS NULL
         ORDER BY m.round, m.match_date ASC",
        $selected_tournament
    ));
}
?>

<div class="wrap qwc-admin-wrap">
    <h1>🏆 Fase Eliminatoria</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="qwc-notice notice notice-error is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <label for="tournament_select"><strong>Torneo:</strong></label>
        <select id="tournament_select" onchange="window.location.href=this.value" style="min-width: 300px;">
            <option value="<?php echo admin_url('admin.php?page=qwc-knockout'); ?>">-- Seleccionar --</option>
            <?php foreach ($tournaments as $t): ?>
                <option value="<?php echo admin_url('admin.php?page=qwc-knockout&tournament=' . $t->id); ?>" <?php selected($selected_tournament, $t->id); ?>>
                    <?php echo esc_html($t->name . ' (' . $t->year . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$selected_tournament): ?>
        <div class="qwc-notice notice notice-warning"><p>⚠️ Selecciona un torneo.</p></div>
    <?php else: ?>

        <!-- TABLA DE POSICIONES POR GRUPO -->
        <h2>📊 Tabla de Posiciones por Grupo</h2>
        <?php if (empty($standings)): ?>
            <p>No hay grupos creados para este torneo.</p>
        <?php else: ?>
            <div class="qwc-groups-grid">
                <?php foreach ($standings as $group_name => $data): ?>
                    <div class="qwc-group-card">
                        <h3>Grupo <?php echo esc_html($group_name); ?></h3>
                        <table class="qwc-standings-mini">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Equipo</th>
                                    <th>PJ</th>
                                    <th>G</th>
                                    <th>E</th>
                                    <th>P</th>
                                    <th>GF</th>
                                    <th>GC</th>
                                    <th>DG</th>
                                    <th>PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['teams'] as $team): ?>
                                    <tr class="<?php echo $team['position'] <= 2 ? 'qwc-qualified' : ''; ?>">
                                        <td><?php echo intval($team['position']); ?></td>
                                        <td>
                                            <?php if ($team['team_flag']): ?>
                                                <img src="<?php echo esc_url($team['team_flag']); ?>" class="qwc-flag">
                                            <?php endif; ?>
                                            <strong><?php echo esc_html($team['team_name']); ?></strong>
                                        </td>
                                        <td><?php echo intval($team['played']); ?></td>
                                        <td><?php echo intval($team['wins']); ?></td>
                                        <td><?php echo intval($team['draws']); ?></td>
                                        <td><?php echo intval($team['losses']); ?></td>
                                        <td><?php echo intval($team['goals_for']); ?></td>
                                        <td><?php echo intval($team['goals_against']); ?></td>
                                        <td><?php echo intval($team['goal_diff']); ?></td>
                                        <td><strong><?php echo intval($team['points']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr style="margin: 40px 0;">

        <!-- GENERADOR DE ELIMINATORIA -->
        <h2>⚡ Generar Fase Eliminatoria</h2>
        <div class="qwc-knockout-controls">
            <p>Esta acción generará automáticamente los cruces de la fase eliminatoria basándose en los 2 mejores de cada grupo.</p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block; margin-right: 10px;" 
                  onsubmit="return confirm('¿Generar fase eliminatoria? Esto creará partidos para Octavos/Cuartos/Semis/Final.');">
                <input type="hidden" name="action" value="qwc_generate_knockout">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <input type="hidden" name="num_groups" value="<?php echo count($standings); ?>">
                <?php wp_nonce_field('qwc_generate_knockout'); ?>
                <button type="submit" class="button button-primary">🏆 Generar Eliminatoria</button>
            </form>

            <?php
            // Detectar la ronda actual con partidos pendientes
            $current_round = $wpdb->get_var($wpdb->prepare(
                "SELECT DISTINCT round FROM {$tables['matches']} 
                 WHERE tournament_id = %d AND group_id IS NULL AND status IN ('scheduled','live')
                 ORDER BY match_date ASC LIMIT 1",
                $selected_tournament
            ));

            if ($current_round): ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block;"
                      onsubmit="return confirm('¿Avanzar ganadores de <?php echo esc_attr($current_round); ?> a la siguiente ronda?');">
                    <input type="hidden" name="action" value="qwc_advance_knockout">
                    <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                    <input type="hidden" name="current_round" value="<?php echo esc_attr($current_round); ?>">
                    <?php wp_nonce_field('qwc_advance_knockout'); ?>
                    <button type="submit" class="button button-secondary">➡️ Avanzar Ganadores de "<?php echo esc_html($current_round); ?>"</button>
                </form>
            <?php endif; ?>
        </div>

        <hr style="margin: 40px 0;">

        <!-- PARTIDOS DE ELIMINATORIA -->
        <h2>🎯 Partidos de Fase Eliminatoria (<?php echo count($knockout_matches); ?>)</h2>
        <?php if (empty($knockout_matches)): ?>
            <p>Aún no se ha generado la fase eliminatoria. Usa el botón de arriba.</p>
        <?php else: ?>
            <?php
            $current_round_display = '';
            foreach ($knockout_matches as $m):
                if ($m->round !== $current_round_display):
                    $current_round_display = $m->round;
                    echo '<h3 style="margin-top: 30px; padding: 10px; background: #1d2327; color: #fff; border-radius: 4px;">' . esc_html($m->round) . '</h3>';
                endif;
            ?>
                <div class="qwc-knockout-match">
                    <div class="qwc-km-date"><?php echo esc_html(date('d/m/Y H:i', strtotime($m->match_date))); ?></div>
                    <div class="qwc-km-teams">
                        <div class="qwc-km-team <?php echo ($m->status === 'finished' && $m->home_score > $m->away_score) ? 'winner' : ''; ?>">
                            <?php if ($m->home_flag): ?><img src="<?php echo esc_url($m->home_flag); ?>" class="qwc-flag"><?php endif; ?>
                            <span><?php echo $m->home_team_id ? esc_html($m->home_name) : '<em>Por definir</em>'; ?></span>
                        </div>
                        <div class="qwc-km-score">
                            <?php if ($m->status === 'finished'): ?>
                                <strong><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong>
                            <?php elseif ($m->status === 'live'): ?>
                                <strong class="qwc-live"><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong>
                                <small>(<?php echo intval($m->current_minute); ?>')</small>
                            <?php else: ?>
                                <span class="qwc-pending">VS</span>
                            <?php endif; ?>
                        </div>
                        <div class="qwc-km-team <?php echo ($m->status === 'finished' && $m->away_score > $m->home_score) ? 'winner' : ''; ?>">
                            <?php if ($m->away_flag): ?><img src="<?php echo esc_url($m->away_flag); ?>" class="qwc-flag"><?php endif; ?>
                            <span><?php echo $m->away_team_id ? esc_html($m->away_name) : '<em>Por definir</em>'; ?></span>
                        </div>
                    </div>
                    <div class="qwc-km-status">
                        <span class="qwc-badge qwc-badge-<?php echo esc_attr($m->status); ?>"><?php echo esc_html($m->status); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>
