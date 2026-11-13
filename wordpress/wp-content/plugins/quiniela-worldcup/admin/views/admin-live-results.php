<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$admin = QWC_Admin::get_instance();
$tables = $admin->get_tables();

$tournaments = $admin->get_tournaments();
$selected_tournament = isset($_GET['tournament']) ? intval($_GET['tournament']) : (get_option('qwc_default_tournament', 0));

// Partidos que NO han finalizado (programados o en vivo)
$active_matches = $selected_tournament ? $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, 
            ht.name as home_team_name, at.name as away_team_name,
            g.name as group_name
     FROM {$tables['matches']} m
     LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
     LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
     LEFT JOIN {$tables['groups']} g ON m.group_id = g.id
     WHERE m.tournament_id = %d AND m.status IN ('scheduled','live')
     ORDER BY m.match_date ASC",
    $selected_tournament
)) : array();

// Partidos finalizados (para ver resultados)
$finished_matches = $selected_tournament ? $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, 
            ht.name as home_team_name, at.name as away_team_name,
            g.name as group_name
     FROM {$tables['matches']} m
     LEFT JOIN {$tables['teams']} ht ON m.home_team_id = ht.id
     LEFT JOIN {$tables['teams']} at ON m.away_team_id = at.id
     LEFT JOIN {$tables['groups']} g ON m.group_id = g.id
     WHERE m.tournament_id = %d AND m.status = 'finished'
     ORDER BY m.match_date DESC
     LIMIT 20",
    $selected_tournament
)) : array();
?>

<div class="wrap qwc-admin-wrap">
    <h1>🔴 Resultados en Vivo</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <label for="tournament_select"><strong>Torneo:</strong></label>
        <select id="tournament_select" onchange="window.location.href=this.value" style="min-width: 300px;">
            <option value="<?php echo admin_url('admin.php?page=qwc-live-results'); ?>">-- Seleccionar --</option>
            <?php foreach ($tournaments as $t): ?>
                <option value="<?php echo admin_url('admin.php?page=qwc-live-results&tournament=' . $t->id); ?>" <?php selected($selected_tournament, $t->id); ?>>
                    <?php echo esc_html($t->name . ' (' . $t->year . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$selected_tournament): ?>
        <div class="qwc-notice notice notice-warning"><p>⚠️ Selecciona un torneo.</p></div>
    <?php else: ?>

        <h2>Partidos Activos (<?php echo count($active_matches); ?>)</h2>
        <?php if (empty($active_matches)): ?>
            <p>No hay partidos programados o en vivo.</p>
        <?php else: ?>
            <?php foreach ($active_matches as $m): ?>
                <div class="qwc-live-card">
                    <div class="qwc-live-header">
                        <span><?php echo esc_html(date('d/m/Y H:i', strtotime($m->match_date))); ?></span>
                        <?php if ($m->group_name): ?><span class="qwc-badge">Grupo <?php echo esc_html($m->group_name); ?></span><?php endif; ?>
                        <span class="qwc-badge qwc-badge-<?php echo esc_attr($m->status); ?>"><?php echo esc_html($m->status); ?></span>
                    </div>
                    <div class="qwc-live-teams">
                        <strong><?php echo esc_html($m->home_team_name); ?></strong>
                        <span>VS</span>
                        <strong><?php echo esc_html($m->away_team_name); ?></strong>
                    </div>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="qwc-live-form">
                        <input type="hidden" name="action" value="qwc_update_live_result">
                        <input type="hidden" name="match_id" value="<?php echo intval($m->id); ?>">
                        <?php wp_nonce_field('qwc_update_live_result'); ?>

                        <label>Estado:
                            <select name="status" required>
                                <option value="scheduled" <?php selected($m->status, 'scheduled'); ?>>Programado</option>
                                <option value="live" <?php selected($m->status, 'live'); ?>>🔴 En Vivo</option>
                                <option value="finished" <?php selected($m->status, 'finished'); ?>>✅ Finalizado</option>
                                <option value="postponed" <?php selected($m->status, 'postponed'); ?>>Pospuesto</option>
                            </select>
                        </label>

                        <label>Local: <input type="number" name="home_score" min="0" value="<?php echo esc_attr($m->home_score); ?>" placeholder="0"></label>
                        <label>Visitante: <input type="number" name="away_score" min="0" value="<?php echo esc_attr($m->away_score); ?>" placeholder="0"></label>
                        <label>Minuto: <input type="number" name="current_minute" min="0" max="120" value="<?php echo esc_attr($m->current_minute); ?>" placeholder="0"></label>

                        <button type="submit" class="button button-primary">Actualizar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr style="margin: 40px 0;">

        <h2>Últimos Resultados Finalizados (<?php echo count($finished_matches); ?>)</h2>
        <?php if (empty($finished_matches)): ?>
            <p>Aún no hay partidos finalizados. Cuando marques uno como "Finalizado", los puntos se calcularán automáticamente.</p>
        <?php else: ?>
            <table class="qwc-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Partido</th>
                        <th>Resultado</th>
                        <th>Pronósticos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finished_matches as $m): 
                        $pred_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$tables['predictions']} WHERE match_id = %d",
                            $m->id
                        ));
                    ?>
                        <tr>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($m->match_date))); ?></td>
                            <td><?php echo esc_html($m->home_team_name); ?> vs <?php echo esc_html($m->away_team_name); ?></td>
                            <td><strong><?php echo intval($m->home_score); ?> - <?php echo intval($m->away_score); ?></strong></td>
                            <td><?php echo intval($pred_count); ?> pronósticos</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>
</div>