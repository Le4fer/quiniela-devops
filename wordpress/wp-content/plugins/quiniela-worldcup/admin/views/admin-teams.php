<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$admin = QWC_Admin::get_instance();
$tables = $admin->get_tables();

// Obtener torneos
$tournaments = $admin->get_tournaments();
$selected_tournament = isset($_GET['tournament']) ? intval($_GET['tournament']) : (get_option('qwc_default_tournament', 0));

// Obtener datos según el torneo seleccionado
$groups = $selected_tournament ? $admin->get_groups_by_tournament($selected_tournament) : array();
$teams = $selected_tournament ? $admin->get_teams_by_tournament($selected_tournament) : array();

// Ver si estamos editando
$edit_team = null;
if (isset($_GET['edit'])) {
    $edit_team = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables['teams']} WHERE id = %d",
        intval($_GET['edit'])
    ));
    if ($edit_team && !$selected_tournament) {
        $selected_tournament = $edit_team->tournament_id;
        $groups = $admin->get_groups_by_tournament($selected_tournament);
    }
}
?>

<div class="wrap qwc-admin-wrap">
    <h1>
        <?php echo $edit_team ? 'Editar Equipo' : 'Gestión de Equipos'; ?>
        <?php if ($edit_team): ?>
            <a href="<?php echo admin_url('admin.php?page=qwc-teams'); ?>" class="page-title-action">← Volver al listado</a>
        <?php endif; ?>
    </h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Selector de Torneo -->
    <div style="margin-bottom: 20px;">
        <label for="tournament_select"><strong>Seleccionar Torneo:</strong></label>
        <select id="tournament_select" onchange="window.location.href=this.value" style="margin-left: 10px; min-width: 300px;">
            <option value="<?php echo admin_url('admin.php?page=qwc-teams'); ?>">-- Seleccionar --</option>
            <?php foreach ($tournaments as $t): ?>
                <option value="<?php echo admin_url('admin.php?page=qwc-teams&tournament=' . $t->id); ?>" 
                        <?php selected($selected_tournament, $t->id); ?>>
                    <?php echo esc_html($t->name . ' (' . $t->year . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$selected_tournament): ?>
        <div class="qwc-notice notice notice-warning">
            <p>️ Primero debes seleccionar un torneo o <a href="<?php echo admin_url('admin.php?page=qwc-tournaments'); ?>">crear uno nuevo</a>.</p>
        </div>
    <?php else: ?>

        <?php if ($edit_team): ?>
            <!-- FORMULARIO DE EDICIÓN -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="qwc_save_team">
                <input type="hidden" name="team_id" value="<?php echo intval($edit_team->id); ?>">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <?php wp_nonce_field('qwc_save_team'); ?>

                <table class="form-table qwc-form-table">
                    <tr>
                        <th><label for="name">Nombre del Equipo</label></th>
                        <td>
                            <input type="text" id="name" name="name" value="<?php echo esc_attr($edit_team->name); ?>" required class="regular-text" placeholder="Ej: Argentina">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="code">Código (3 letras)</label></th>
                        <td>
                            <input type="text" id="code" name="code" value="<?php echo esc_attr($edit_team->code); ?>" required maxlength="10" placeholder="Ej: ARG" style="text-transform: uppercase;">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="group_id">Grupo</label></th>
                        <td>
                            <select id="group_id" name="group_id">
                                <option value="">-- Sin grupo --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo intval($g->id); ?>" <?php selected($edit_team->group_id, $g->id); ?>>
                                        Grupo <?php echo esc_html($g->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Si no hay grupos, <a href="<?php echo admin_url('admin.php?page=qwc-teams&tab=groups&tournament=' . $selected_tournament); ?>">crea grupos primero</a>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="flag_url">URL de la Bandera</label></th>
                        <td>
                            <input type="url" id="flag_url" name="flag_url" value="<?php echo esc_attr($edit_team->flag_url); ?>" class="regular-text" placeholder="https://ejemplo.com/bandera.png">
                            <p class="description">URL de la imagen de la bandera del país.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fifa_ranking">Ranking FIFA</label></th>
                        <td>
                            <input type="number" id="fifa_ranking" name="fifa_ranking" value="<?php echo esc_attr($edit_team->fifa_ranking); ?>" min="1" max="211" placeholder="Ej: 1">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Actualizar Equipo</button>
                    <a href="<?php echo admin_url('admin.php?page=qwc-teams&tournament=' . $selected_tournament); ?>" class="button">Cancelar</a>
                </p>
            </form>

        <?php else: ?>
            <!-- FORMULARIO DE CREACIÓN -->
            <h2>Agregar Nuevo Equipo</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="qwc_save_team">
                <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                <?php wp_nonce_field('qwc_save_team'); ?>

                <table class="form-table qwc-form-table">
                    <tr>
                        <th><label for="name">Nombre del Equipo</label></th>
                        <td>
                            <input type="text" id="name" name="name" required class="regular-text" placeholder="Ej: Argentina">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="code">Código (3 letras)</label></th>
                        <td>
                            <input type="text" id="code" name="code" required maxlength="10" placeholder="Ej: ARG" style="text-transform: uppercase;">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="group_id">Grupo</label></th>
                        <td>
                            <select id="group_id" name="group_id">
                                <option value="">-- Sin grupo --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo intval($g->id); ?>">
                                        Grupo <?php echo esc_html($g->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="flag_url">URL de la Bandera</label></th>
                        <td>
                            <input type="url" id="flag_url" name="flag_url" class="regular-text" placeholder="https://ejemplo.com/bandera.png">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fifa_ranking">Ranking FIFA</label></th>
                        <td>
                            <input type="number" id="fifa_ranking" name="fifa_ranking" min="1" max="211" placeholder="Ej: 1">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Agregar Equipo</button>
                </p>
            </form>

            <hr>

            <!-- LISTADO DE EQUIPOS -->
            <h2>
                Equipos del Torneo
                <span style="font-size: 14px; color: #646970;">(<?php echo count($teams); ?> equipos)</span>
            </h2>

            <?php if (empty($teams)): ?>
                <div class="qwc-notice notice notice-info">
                    <p>📋 No hay equipos en este torneo aún. Agrega el primer equipo usando el formulario de arriba.</p>
                </div>
            <?php else: ?>
                <table class="qwc-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bandera</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Grupo</th>
                            <th>Ranking FIFA</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><?php echo intval($team->id); ?></td>
                                <td>
                                    <?php if ($team->flag_url): ?>
                                        <img src="<?php echo esc_url($team->flag_url); ?>" alt="" class="qwc-flag">
                                    <?php else: ?>
                                        <span style="color: #c3c4c7;">🏳️</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($team->name); ?></strong></td>
                                <td><code><?php echo esc_html($team->code); ?></code></td>
                                <td>
                                    <?php if ($team->group_name): ?>
                                        <span class="qwc-badge">Grupo <?php echo esc_html($team->group_name); ?></span>
                                    <?php else: ?>
                                        <span style="color: #646970;">Sin grupo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $team->fifa_ranking ? intval($team->fifa_ranking) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=qwc-teams&edit=' . intval($team->id) . '&tournament=' . $selected_tournament); ?>" class="qwc-btn qwc-btn-secondary">Editar</a>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar este equipo?');">
                                        <input type="hidden" name="action" value="qwc_delete_team">
                                        <input type="hidden" name="team_id" value="<?php echo intval($team->id); ?>">
                                        <?php wp_nonce_field('qwc_delete_team'); ?>
                                        <button type="submit" class="qwc-btn qwc-btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Pestaña de Grupos -->
            <hr style="margin: 40px 0;">
            <h2>
                Gestión de Grupos
                <a href="<?php echo admin_url('admin.php?page=qwc-teams&tab=groups&action=add&tournament=' . $selected_tournament); ?>" class="button button-small" id="btn_add_group">+ Agregar Grupo</a>
            </h2>

            <?php
            // Mostrar formulario de agregar grupo si está en modo add
            if (isset($_GET['tab']) && $_GET['tab'] === 'groups' && isset($_GET['action']) && $_GET['action'] === 'add'):
            ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 20px; padding: 15px; background: #f6f7f7; border-left: 4px solid #2271b1;">
                    <input type="hidden" name="action" value="qwc_save_group">
                    <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                    <?php wp_nonce_field('qwc_save_group'); ?>
                    
                    <label><strong>Nombre del Grupo:</strong></label>
                    <input type="text" name="group_name" placeholder="Ej: A, B, C..." required style="width: 100px; margin: 0 10px;">
                    
                    <label><strong>Orden:</strong></label>
                    <input type="number" name="sort_order" value="<?php echo count($groups) + 1; ?>" min="1" required style="width: 60px;">
                    
                    <button type="submit" class="button button-primary">Crear Grupo</button>
                    <a href="<?php echo admin_url('admin.php?page=qwc-teams&tournament=' . $selected_tournament); ?>" class="button">Cancelar</a>
                </form>
            <?php endif; ?>

            <?php if (empty($groups)): ?>
                <p>No hay grupos creados. Haz clic en "Agregar Grupo" para crear la fase de grupos.</p>
            <?php else: ?>
                <table class="qwc-table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Orden</th>
                            <th>Equipos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): 
                            $teams_in_group = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tables['teams']} WHERE group_id = %d",
                                $g->id
                            ));
                        ?>
                            <tr>
                                <td><strong>Grupo <?php echo esc_html($g->name); ?></strong></td>
                                <td><?php echo intval($g->sort_order); ?></td>
                                <td><?php echo intval($teams_in_group); ?> equipos</td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar este grupo? Los equipos permanecerán sin grupo.');">
                                        <input type="hidden" name="action" value="qwc_delete_group">
                                        <input type="hidden" name="group_id" value="<?php echo intval($g->id); ?>">
                                        <input type="hidden" name="tournament_id" value="<?php echo intval($selected_tournament); ?>">
                                        <?php wp_nonce_field('qwc_delete_group'); ?>
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