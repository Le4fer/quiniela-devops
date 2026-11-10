<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$admin = QWC_Admin::get_instance();
$tables = $admin->tables;

$tournaments = $admin->get_tournaments();
$selected_tournament = isset($_GET['tournament']) ? intval($_GET['tournament']) : (get_option('qwc_default_tournament', 0));

$groups = $selected_tournament ? $admin->get_groups_by_tournament($selected_tournament) : array();
$teams = $selected_tournament ? $admin->get_teams_by_tournament($selected_tournament) : array();
$matches = $selected_tournament ? $admin->get_matches_by_tournament($selected_tournament) : array();

$edit_match = null;
if (isset($_GET['edit'])) {
    $edit_match = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables['matches']} WHERE id = %d",
        intval($_GET['edit'])
    ));
}
?>

<div class="wrap qwc-admin-wrap">
    <h1>
        <?php echo $edit_match ? 'Editar Partido' : 'Gestión de Partidos'; ?>
        <?php if ($edit_match): ?>
            <a href="<?php echo admin_url('admin.php?page=qwc-matches&tournament=' . $selected_tournament); ?>" class="page-title-action">← Volver</a>
        <?php endif; ?>
    </h1>

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

    <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
        <label for="tournament_select"><strong>Torneo:</strong></label>
        <select id="tournament_select" onchange="window.location.href=this.value" style="min-width: 300px;">
            <option value="<?php echo admin_url('admin.php?page=qwc-matches'); ?>">-- Seleccionar --</option>
            <?php foreach ($tournaments as $t): ?>
                <option value="<?php echo admin_url('admin.php?page=qwc-matches&tournament=' . $t->id); ?>" <?php selected($selected_tournament, $t->id); ?>>
                    <?php echo esc_html($t->name . ' (' . $t->year . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selected_tournament): ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('¿Generar automáticamente todos los partidos de fase de grupos?');">
                <input type="hidden" name="action" value="qwc_generate_matches">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <?php wp_nonce_field('qwc_generate_matches'); ?>
                <button type="submit" class="button button-primary">⚡ Auto-generar Fase de Grupos</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$selected_tournament): ?>
        <div class="qwc-notice notice notice-warning">
            <p>⚠️ Selecciona un torneo para gestionar sus partidos.</p>
        </div>
    <?php else: ?>

        <?php if ($edit_match): ?>
            <!-- FORMULARIO DE EDICIÓN -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="qwc_save_match">
                <input type="hidden" name="match_id" value="<?php echo intval($edit_match->id); ?>">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <?php wp_nonce_field('qwc_save_match'); ?>

                <table class="form-table qwc-form-table">
                    <tr>
                        <th><label>Fase / Grupo</label></th>
                        <td>
                            <select name="group_id">
                                <option value="">-- Fase Eliminatoria --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo intval($g->id); ?>" <?php selected($edit_match->group_id, $g->id); ?>>Grupo <?php echo esc_html($g->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="round" value="<?php echo esc_attr($edit_match->round); ?>" placeholder="Ej: Octavos, Final" style="margin-top: 5px; width: 200px;">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Equipos</label></th>
                        <td>
                            <select name="home_team_id" required style="width: 45%;">
                                <option value="">Local</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo intval($t->id); ?>" <?php selected($edit_match->home_team_id, $t->id); ?>><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span style="margin: 0 10px; font-weight: bold;">VS</span>
                            <select name="away_team_id" required style="width: 45%;">
                                <option value="">Visitante</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo intval($t->id); ?>" <?php selected($edit_match->away_team_id, $t->id); ?>><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Fecha y Hora</label></th>
                        <td>
                            <input type="datetime-local" name="match_date" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($edit_match->match_date))); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Estadio y Ciudad</label></th>
                        <td>
                            <input type="text" name="stadium" value="<?php echo esc_attr($edit_match->stadium); ?>" placeholder="Estadio" style="width: 45%;">
                            <input type="text" name="city" value="<?php echo esc_attr($edit_match->city); ?>" placeholder="Ciudad" style="width: 45%;">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Estado del Partido</label></th>
                        <td>
                            <select name="status" required>
                                <option value="scheduled" <?php selected($edit_match->status, 'scheduled'); ?>>Programado</option>
                                <option value="live" <?php selected($edit_match->status, 'live'); ?>>🔴 En Vivo</option>
                                <option value="finished" <?php selected($edit_match->status, 'finished'); ?>>Finalizado</option>
                                <option value="postponed" <?php selected($edit_match->status, 'postponed'); ?>>Pospuesto</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Marcador (Solo si está En Vivo o Finalizado)</label></th>
                        <td>
                            <input type="number" name="home_score" value="<?php echo esc_attr($edit_match->home_score); ?>" min="0" placeholder="Local" style="width: 80px;">
                            <span style="margin: 0 10px;">-</span>
                            <input type="number" name="away_score" value="<?php echo esc_attr($edit_match->away_score); ?>" min="0" placeholder="Visitante" style="width: 80px;">
                            <input type="number" name="current_minute" value="<?php echo esc_attr($edit_match->current_minute); ?>" min="0" max="120" placeholder="Minuto" style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Actualizar Partido</button>
                    <a href="<?php echo admin_url('admin.php?page=qwc-matches&tournament=' . $selected_tournament); ?>" class="button">Cancelar</a>
                </p>
            </form>

        <?php else: ?>
            <!-- FORMULARIO DE CREACIÓN MANUAL -->
            <h2>Agregar Partido Manual</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="qwc_save_match">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <?php wp_nonce_field('qwc_save_match'); ?>

                <table class="form-table qwc-form-table">
                    <tr>
                        <th><label>Fase / Grupo</label></th>
                        <td>
                            <select name="group_id">
                                <option value="">-- Fase Eliminatoria --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo intval($g->id); ?>">Grupo <?php echo esc_html($g->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="round" placeholder="Ej: Octavos, Final" style="margin-top: 5px; width: 200px;">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Equipos</label></th>
                        <td>
                            <select name="home_team_id" required style="width: 45%;">
                                <option value="">Local</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo intval($t->id); ?>"><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span style="margin: 0 10px; font-weight: bold;">VS</span>
                            <select name="away_team_id" required style="width: 45%;">
                                <option value="">Visitante</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo intval($t->id); ?>"><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Fecha y Hora</label></th>
                        <td>
                            <input type="datetime-local" name="match_date" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Estadio y Ciudad</label></th>
                        <td>
                            <input type="text" name="stadium" placeholder="Estadio" style="width: 45%;">
                            <input type="text" name="city" placeholder="Ciudad" style="width: 45%;">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Agregar Partido</button>
                </p>
            </form>

            <hr>

            <!-- LISTADO DE PARTIDOS -->
            <h2>Partidos del Torneo (<?php echo count($matches); ?>)</h2>
            
            <?php if (empty($matches)): ?>
                <div class="qwc-notice notice notice-info">
                    <p>📋 No hay partidos programados. Usa el botón "⚡ Auto-generar Fase de Grupos" o agrega manualmente.</p>
                </div>
            <?php else: ?>
                <table class="qwc-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Fase</th>
                            <th>Partido</th>
                            <th>Estadio</th>
                            <th>Estado</th>
                            <th>Marcador</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $m): ?>
                            <tr>
                                <td><?php echo esc_html(date('d/m/Y H:i', strtotime($m->match_date))); ?></td>
                                <td>
                                    <?php if ($m->group_name): ?>
                                        <span class="qwc-badge">Grupo <?php echo esc_html($m->group_name); ?></span>
                                    <?php else: ?>
                                        <span class="qwc-badge"><?php echo esc_html($m->round ?: 'Eliminatoria'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="qwc-team-cell">
                                        <?php if ($m->home_flag): ?><img src="<?php echo esc_url($m->home_flag); ?>" class="qwc-flag"><?php endif; ?>
                                        <strong><?php echo esc_html($m->home_team_name); ?></strong>
                                    </div>
                                    <div style="margin: 4px 0; color: #646970; font-size: 12px;">VS</div>
                                    <div class="qwc-team-cell">
                                        <?php if ($m->away_flag): ?><img src="<?php echo esc_url($m->away_flag); ?>" class="qwc-flag"><?php endif; ?>
                                        <strong><?php echo esc_html($m->away_team_name); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo esc_html($m->stadium . ($m->city ? ', ' . $m->city : '')); ?></td>
                                <td><span class="qwc-badge qwc-badge-<?php echo esc_attr($m->status); ?>"><?php echo esc_html($m->status); ?></span></td>
                                <td>
                                    <?php if ($m->status === 'live'): ?>
                                        <strong style="color: #d63638;"><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong>
                                        <br><small>Min <?php echo intval($m->current_minute); ?>'</small>
                                    <?php elseif ($m->status === 'finished'): ?>
                                        <strong><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong>
                                    <?php else: ?>
                                        <span style="color: #c3c4c7;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=qwc-matches&edit=' . intval($m->id) . '&tournament=' . $selected_tournament); ?>" class="qwc-btn qwc-btn-secondary">Editar</a>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline; margin-top: 5px;" onsubmit="return confirm('¿Eliminar este partido y sus pronósticos asociados?');">
                                        <input type="hidden" name="action" value="qwc_delete_match">
                                        <input type="hidden" name="match_id" value="<?php echo intval($m->id); ?>">
                                        <?php wp_nonce_field('qwc_delete_match'); ?>
                                        <button type="submit" class="qwc-btn qwc-btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>