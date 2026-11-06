<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$admin = QWC_Admin::get_instance();
$stats = $admin->get_stats();
$tournaments = $admin->get_tournaments();
?>

<div class="wrap qwc-admin-wrap">
    <h1>🏆 Dashboard de la Quiniela</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <div class="qwc-stats-grid">
        <div class="qwc-stat-card">
            <div class="stat-label">Torneos</div>
            <div class="stat-value"><?php echo intval($stats['tournaments']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Equipos</div>
            <div class="stat-value"><?php echo intval($stats['teams']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Partidos</div>
            <div class="stat-value"><?php echo intval($stats['matches']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Pronósticos</div>
            <div class="stat-value"><?php echo intval($stats['predictions']); ?></div>
        </div>
        <div class="qwc-stat-card">
            <div class="stat-label">Usuarios</div>
            <div class="stat-value"><?php echo intval($stats['users']); ?></div>
        </div>
        <div class="qwc-stat-card live">
            <div class="stat-label">🔴 En Vivo</div>
            <div class="stat-value"><?php echo intval($stats['live_matches']); ?></div>
        </div>
    </div>

    <h2>Torneos Activos</h2>
    <?php if (empty($tournaments)): ?>
        <p>No hay torneos creados aún. <a href="<?php echo admin_url('admin.php?page=qwc-tournaments'); ?>">Crear primer torneo</a></p>
    <?php else: ?>
        <table class="qwc-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Año</th>
                    <th>Estado</th>
                    <th>Equipos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tournaments as $t): 
                    // Usar el método del admin en vez de $wpdb directo
                    $teams_count = count($admin->get_teams_by_tournament($t->id));
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($t->name); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($t->type)); ?></td>
                        <td><?php echo esc_html($t->year); ?></td>
                        <td><span class="qwc-badge qwc-badge-<?php echo esc_attr($t->status); ?>"><?php echo esc_html($t->status); ?></span></td>
                        <td><?php echo intval($teams_count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>