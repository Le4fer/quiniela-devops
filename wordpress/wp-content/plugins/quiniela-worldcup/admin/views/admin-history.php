<?php
if (!defined('ABSPATH')) exit;

require_once QWC_PLUGIN_DIR . 'includes/class-qwc-history.php';

$admin = QWC_Admin::get_instance();
$tables = $admin->get_tables();
$tournaments = $admin->get_tournaments();

$archived = QWC_History::get_archived_tournaments();
$global_stats = QWC_History::get_global_stats();
?>

<div class="wrap qwc-admin-wrap">
    <h1>📚 Historial de Copas</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- ESTADÍSTICAS GLOBALES -->
    <div class="qwc-stats-grid">
        <div class="qwc-stat-card">
            <div class="stat-label">Torneos Archivados</div>
            <div class="stat-value"><?php echo intval($global_stats['total_tournaments']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Partidos Jugados</div>
            <div class="stat-value"><?php echo intval($global_stats['total_matches']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Pronósticos Totales</div>
            <div class="stat-value"><?php echo intval($global_stats['total_predictions']); ?></div>
        </div>
    </div>

    <hr>

    <!-- EQUIPOS MÁS CAMPEONES -->
    <h2>🏆 Equipos Más Campeones</h2>
    <?php if (empty($global_stats['top_champions'])): ?>
        <p>Aún no hay campeonatos registrados.</p>
    <?php else: ?>
        <table class="qwc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Equipo</th>
                    <th>Títulos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($global_stats['top_champions'] as $index => $team): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php if ($team->flag_url): ?>
                                <img src="<?php echo esc_url($team->flag_url); ?>" class="qwc-flag">
                            <?php endif; ?>
                            <strong><?php echo esc_html($team->name); ?></strong>
                            <code><?php echo esc_html($team->code); ?></code>
                        </td>
                        <td><strong><?php echo intval($team->titles); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <!-- ARCHIVAR TORNEO ACTIVO -->
    <h2>📦 Archivar Torneo Activo</h2>
    <p>Selecciona un torneo finalizado para archivarlo y registrar su campeón. <strong>Esto bloqueará el torneo para edición.</strong></p>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="qwc-form-table">
        <input type="hidden" name="action" value="qwc_archive_tournament">
        <?php wp_nonce_field('qwc_archive_tournament'); ?>

        <table class="form-table qwc-form-table">
            <tr>
                <th><label for="tournament_id">Torneo a Archivar</label></th>
                <td>
                    <select id="tournament_id" name="tournament_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($tournaments as $t): 
                            if ($t->is_archived) continue; // Solo mostrar activos
                        ?>
                            <option value="<?php echo intval($t->id); ?>">
                                <?php echo esc_html($t->name . ' (' . $t->year . ') - ' . ucfirst($t->status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="champion_team_id">🏆 Equipo Campeón</label></th>
                <td>
                    <select id="champion_team_id" name="champion_team_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($tournaments as $t): 
                            $teams = $admin->get_teams_by_tournament($t->id);
                            foreach ($teams as $team): ?>
                                <option value="<?php echo intval($team->id); ?>">
                                    <?php echo esc_html($team->name . ' (' . $team->code . ')'); ?>
                                </option>
                            <?php endforeach;
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="runner_up_team_id">🥈 Subcampeón</label></th>
                <td>
                    <select id="runner_up_team_id" name="runner_up_team_id">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($tournaments as $t): 
                            $teams = $admin->get_teams_by_tournament($t->id);
                            foreach ($teams as $team): ?>
                                <option value="<?php echo intval($team->id); ?>">
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach;
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="third_place_team_id">🥉 Tercer Lugar</label></th>
                <td>
                    <select id="third_place_team_id" name="third_place_team_id">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($tournaments as $t): 
                            $teams = $admin->get_teams_by_tournament($t->id);
                            foreach ($teams as $team): ?>
                                <option value="<?php echo intval($team->id); ?>">
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach;
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="top_scorer_team_id">⚽ Goleador del Torneo</label></th>
                <td>
                    <select id="top_scorer_team_id" name="top_scorer_team_id">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($tournaments as $t): 
                            $teams = $admin->get_teams_by_tournament($t->id);
                            foreach ($teams as $team): ?>
                                <option value="<?php echo intval($team->id); ?>">
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach;
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="top_scorer_goals">Goles del Goleador</label></th>
                <td>
                    <input type="number" name="top_scorer_goals" min="0" value="0">
                </td>
            </tr>
            <tr>
                <th><label for="mvp_team_id">🌟 MVP del Torneo</label></th>
                <td>
                    <select id="mvp_team_id" name="mvp_team_id">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($tournaments as $t): 
                            $teams = $admin->get_teams_by_tournament($t->id);
                            foreach ($teams as $team): ?>
                                <option value="<?php echo intval($team->id); ?>">
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach;
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="notes">Notas / Resumen</label></th>
                <td>
                    <textarea name="notes" rows="4" class="large-text" placeholder="Ej: Final épica en penales. Argentina ganó su tercer título..."></textarea>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary" onclick="return confirm('¿Archivar este torneo? Se bloqueará para edición.');">📦 Archivar Torneo</button>
        </p>
    </form>

    <hr>

    <!-- LISTADO DE TORNEOS ARCHIVADOS -->
    <h2>📚 Torneos en el Historial (<?php echo count($archived); ?>)</h2>
    <?php if (empty($archived)): ?>
        <p>Aún no hay torneos archivados. Usa el formulario de arriba para archivar el primero.</p>
    <?php else: ?>
        <table class="qwc-table">
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Torneo</th>
                    <th>🏆 Campeón</th>
                    <th>🥈 Subcampeón</th>
                    <th>Archivado el</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived as $t): ?>
                    <tr>
                        <td><strong><?php echo esc_html($t->year); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($t->name); ?></strong>
                            <br><small><?php echo esc_html(ucfirst($t->type)); ?></small>
                        </td>
                        <td>
                            <?php if ($t->champion_flag): ?>
                                <img src="<?php echo esc_url($t->champion_flag); ?>" class="qwc-flag">
                            <?php endif; ?>
                            <strong><?php echo esc_html($t->champion_name ?: 'N/A'); ?></strong>
                        </td>
                        <td>
                            <?php if ($t->runner_up_flag): ?>
                                <img src="<?php echo esc_url($t->runner_up_flag); ?>" class="qwc-flag">
                            <?php endif; ?>
                            <?php echo esc_html($t->runner_up_name ?: '-'); ?>
                        </td>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($t->updated_at))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=qwc-history&view=' . intval($t->id)); ?>" class="qwc-btn qwc-btn-secondary">Ver Detalles</a>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('¿Desarchivar este torneo?');">
                                <input type="hidden" name="action" value="qwc_unarchive_tournament">
                                <input type="hidden" name="tournament_id" value="<?php echo intval($t->id); ?>">
                                <?php wp_nonce_field('qwc_unarchive_tournament'); ?>
                                <button type="submit" class="qwc-btn qwc-btn-danger">Desarchivar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php
    // Vista de detalle de un torneo archivado
    if (isset($_GET['view'])):
        $detail = QWC_History::get_archived_tournament(intval($_GET['view']));
        if ($detail):
    ?>
        <hr style="margin: 40px 0;">
        <h2>📖 Detalle: <?php echo esc_html($detail->name); ?> <?php echo esc_html($detail->year); ?></h2>

        <div class="qwc-tournament-detail">
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
                    <h3>📝 Resumen</h3>
                    <p><?php echo nl2br(esc_html($detail->notes)); ?></p>
                </div>
            <?php endif; ?>

            <h3>📊 Tabla Final de Grupos</h3>
            <?php foreach ($detail->group_standings as $group_name => $data): ?>
                <h4>Grupo <?php echo esc_html($group_name); ?></h4>
                <table class="qwc-table">
                    <thead>
                        <tr><th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>PTS</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['teams'] as $team): ?>
                            <tr class="<?php echo $team['position'] <= 2 ? 'qwc-qualified' : ''; ?>">
                                <td><?php echo $team['position']; ?></td>
                                <td><?php echo esc_html($team['team_name']); ?></td>
                                <td><?php echo $team['played']; ?></td>
                                <td><?php echo $team['wins']; ?></td>
                                <td><?php echo $team['draws']; ?></td>
                                <td><?php echo $team['losses']; ?></td>
                                <td><?php echo $team['goals_for']; ?></td>
                                <td><?php echo $team['goals_against']; ?></td>
                                <td><?php echo $team['goal_diff']; ?></td>
                                <td><strong><?php echo $team['points']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

            <h3>🎯 Fase Eliminatoria</h3>
            <?php if (empty($detail->knockout_matches)): ?>
                <p>No hubo fase eliminatoria en este torneo.</p>
            <?php else: ?>
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

            <h3>👥 Top 10 Quinielistas</h3>
            <?php if (empty($detail->user_standings)): ?>
                <p>No hubo participantes en la quiniela.</p>
            <?php else: ?>
                <table class="qwc-table">
                    <thead><tr><th>#</th><th>Usuario</th><th>Pronósticos</th><th>Puntos</th></tr></thead>
                    <tbody>
                        <?php foreach ($detail->user_standings as $index => $u): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                                <td><?php echo intval($u->num_predictions); ?></td>
                                <td><strong><?php echo number_format(floatval($u->total_points), 1); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; endif; ?>
</div>
