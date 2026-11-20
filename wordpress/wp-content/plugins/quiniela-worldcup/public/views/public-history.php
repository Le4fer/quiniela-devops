<?php
if (!defined('ABSPATH')) exit;

require_once QWC_PLUGIN_DIR . 'includes/class-qwc-history.php';

$archived = QWC_History::get_archived_tournaments();
$global_stats = QWC_History::get_global_stats();

// Si se solicita ver un torneo específico
$view_tournament_id = isset($_GET['tournament']) ? intval($_GET['tournament']) : 0;
$detail = $view_tournament_id ? QWC_History::get_archived_tournament($view_tournament_id) : null;
?>

<div class="qwc-public-history">
    <?php if ($detail): ?>
        <!-- VISTA DE DETALLE DE UN TORNEO -->
        <h2>📖 <?php echo esc_html($detail->name); ?> <?php echo esc_html($detail->year); ?></h2>
        <p><a href="<?php echo get_permalink(); ?>">← Volver al Historial</a></p>

        <div class="qwc-podium">
            <div class="qwc-podium-item second">
                <span class="medal">🥈</span>
                <?php if ($detail->runner_up_flag): ?>
                    <img src="<?php echo esc_url($detail->runner_up_flag); ?>" class="qwc-flag-large">
                <?php endif; ?>
                <strong><?php echo esc_html($detail->runner_up_name ?: 'N/A'); ?></strong>
                <span>Subcampeón</span>
            </div>
            <div class="qwc-podium-item first">
                <span class="medal">🏆</span>
                <?php if ($detail->champion_flag): ?>
                    <img src="<?php echo esc_url($detail->champion_flag); ?>" class="qwc-flag-large">
                <?php endif; ?>
                <strong><?php echo esc_html($detail->champion_name); ?></strong>
                <span>CAMPEÓN</span>
            </div>
            <div class="qwc-podium-item third">
                <span class="medal">🥉</span>
                <?php if ($detail->third_place_flag): ?>
                    <img src="<?php echo esc_url($detail->third_place_flag); ?>" class="qwc-flag-large">
                <?php endif; ?>
                <strong><?php echo esc_html($detail->third_place_name ?: 'N/A'); ?></strong>
                <span>Tercer Lugar</span>
            </div>
        </div>

        <?php if ($detail->notes): ?>
            <div class="qwc-tournament-notes">
                <h3>📝 Resumen del Torneo</h3>
                <p><?php echo nl2br(esc_html($detail->notes)); ?></p>
            </div>
        <?php endif; ?>

        <h3>📊 Fase de Grupos Final</h3>
        <?php foreach ($detail->group_standings as $group_name => $data): ?>
            <div class="qwc-group-block">
                <h4>Grupo <?php echo esc_html($group_name); ?></h4>
                <table class="qwc-group-table">
                    <thead>
                        <tr><th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>PTS</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['teams'] as $team): ?>
                            <tr class="<?php echo $team['position'] <= 2 ? 'qwc-qualified-row' : ''; ?>">
                                <td><?php echo $team['position']; ?></td>
                                <td class="qwc-team-cell">
                                    <?php if ($team['team_flag']): ?>
                                        <img src="<?php echo esc_url($team['team_flag']); ?>" class="qwc-flag">
                                    <?php endif; ?>
                                    <strong><?php echo esc_html($team['team_name']); ?></strong>
                                </td>
                                <td><?php echo $team['played']; ?></td>
                                <td><?php echo $team['wins']; ?></td>
                                <td><?php echo $team['draws']; ?></td>
                                <td><?php echo $team['losses']; ?></td>
                                <td><?php echo $team['goals_for']; ?></td>
                                <td><?php echo $team['goals_against']; ?></td>
                                <td><?php echo $team['goal_diff']; ?></td>
                                <td class="qwc-pts"><strong><?php echo $team['points']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($detail->knockout_matches)): ?>
            <h3>🎯 Fase Eliminatoria</h3>
            <?php $current_round = ''; foreach ($detail->knockout_matches as $m): 
                if ($m->round !== $current_round):
                    $current_round = $m->round;
                    echo '<h4>' . esc_html($m->round) . '</h4>';
                endif;
            ?>
                <div class="qwc-knockout-match">
                    <div class="qwc-km-teams">
                        <div class="qwc-km-team <?php echo ($m->status === 'finished' && $m->home_score > $m->away_score) ? 'winner' : ''; ?>">
                            <?php if ($m->home_flag): ?><img src="<?php echo esc_url($m->home_flag); ?>" class="qwc-flag"><?php endif; ?>
                            <span><?php echo esc_html($m->home_name ?: 'TBD'); ?></span>
                        </div>
                        <div class="qwc-km-score">
                            <?php if ($m->status === 'finished'): ?>
                                <strong><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong>
                            <?php else: ?>
                                <span class="qwc-pending">VS</span>
                            <?php endif; ?>
                        </div>
                        <div class="qwc-km-team <?php echo ($m->status === 'finished' && $m->away_score > $m->home_score) ? 'winner' : ''; ?>">
                            <?php if ($m->away_flag): ?><img src="<?php echo esc_url($m->away_flag); ?>" class="qwc-flag"><?php endif; ?>
                            <span><?php echo esc_html($m->away_name ?: 'TBD'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>

        <h3>👥 Top Quinielistas de esta Edición</h3>
        <?php if (empty($detail->user_standings)): ?>
            <p>No hubo participantes en la quiniela.</p>
        <?php else: ?>
            <table class="qwc-standings-table">
                <thead><tr><th>#</th><th>Usuario</th><th>Pronósticos</th><th>Puntos</th></tr></thead>
                <tbody>
                    <?php foreach ($detail->user_standings as $index => $u): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                            <td><?php echo intval($u->num_predictions); ?></td>
                            <td class="qwc-points"><strong><?php echo number_format(floatval($u->total_points), 1); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php else: ?>
        <!-- LISTADO GENERAL DEL HISTORIAL -->
        <h2>📚 Historial de Copas</h2>

        <div class="qwc-history-stats">
            <div class="qwc-dash-stat">
                <span class="stat-num"><?php echo intval($global_stats['total_tournaments']); ?></span>
                <span class="stat-lbl">Torneos Archivados</span>
            </div>
            <div class="qwc-dash-stat">
                <span class="stat-num"><?php echo intval($global_stats['total_matches']); ?></span>
                <span class="stat-lbl">Partidos Jugados</span>
            </div>
            <div class="qwc-dash-stat">
                <span class="stat-num"><?php echo intval($global_stats['total_predictions']); ?></span>
                <span class="stat-lbl">Pronósticos Totales</span>
            </div>
        </div>

        <?php if (!empty($global_stats['top_champions'])): ?>
            <h3>🏆 Equipos Más Campeones</h3>
            <div class="qwc-top-champions">
                <?php foreach ($global_stats['top_champions'] as $team): ?>
                    <div class="qwc-champion-card">
                        <?php if ($team->flag_url): ?>
                            <img src="<?php echo esc_url($team->flag_url); ?>" class="qwc-flag-large">
                        <?php endif; ?>
                        <strong><?php echo esc_html($team->name); ?></strong>
                        <span class="qwc-titles"><?php echo intval($team->titles); ?> título(s)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($archived)): ?>
            <p>Aún no hay torneos archivados en el historial.</p>
        <?php else: ?>
            <h3>📅 Torneos Archivados</h3>
            <div class="qwc-history-list">
                <?php foreach ($archived as $t): ?>
                    <a href="<?php echo get_permalink(); ?>?tournament=<?php echo intval($t->id); ?>" class="qwc-history-card">
                        <div class="qwc-history-year"><?php echo esc_html($t->year); ?></div>
                        <div class="qwc-history-info">
                            <strong><?php echo esc_html($t->name); ?></strong>
                            <?php if ($t->champion_flag): ?>
                                <img src="<?php echo esc_url($t->champion_flag); ?>" class="qwc-flag">
                            <?php endif; ?>
                            <span class="qwc-champion-label">🏆 <?php echo esc_html($t->champion_name ?: 'N/A'); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
