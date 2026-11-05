<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$admin = QWC_Admin::get_instance();
$tournaments = $admin->get_tournaments();
$edit_tournament = null;

if (isset($_GET['edit'])) {
    $edit_tournament = $admin->get_tournament(intval($_GET['edit']));
}
?>

<div class="wrap qwc-admin-wrap">
    <h1>
        <?php echo $edit_tournament ? 'Editar Torneo' : 'Gestión de Torneos'; ?>
        <a href="<?php echo admin_url('admin.php?page=qwc-tournaments'); ?>" class="page-title-action">← Volver al listado</a>
    </h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="qwc-notice notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($edit_tournament): ?>
        <!-- FORMULARIO DE EDICIÓN -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="qwc_save_tournament">
            <input type="hidden" name="tournament_id" value="<?php echo intval($edit_tournament->id); ?>">
            <?php wp_nonce_field('qwc_save_tournament'); ?>

            <table class="form-table qwc-form-table">
                <tr>
                    <th><label for="name">Nombre del Torneo</label></th>
                    <td>
                        <input type="text" id="name" name="name" value="<?php echo esc_attr($edit_tournament->name); ?>" required class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="type">Tipo</label></th>
                    <td>
                        <select id="type" name="type" required>
                            <option value="worldcup" <?php selected($edit_tournament->type, 'worldcup'); ?>>Copa Mundial</option>
                            <option value="copaamerica" <?php selected($edit_tournament->type, 'copaamerica'); ?>>Copa América</option>
                            <option value="custom" <?php selected($edit_tournament->type, 'custom'); ?>>Personalizado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="year">Año</label></th>
                    <td>
                        <input type="number" id="year" name="year" value="<?php echo intval($edit_tournament->year); ?>" min="2000" max="2100" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="start_date">Fecha Inicio</label></th>
                    <td>
                        <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($edit_tournament->start_date); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="end_date">Fecha Fin</label></th>
                    <td>
                        <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($edit_tournament->end_date); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Estado</label></th>
                    <td>
                        <select id="status" name="status" required>
                            <option value="upcoming" <?php selected($edit_tournament->status, 'upcoming'); ?>>Próximo</option>
                            <option value="active" <?php selected($edit_tournament->status, 'active'); ?>>Activo</option>
                            <option value="finished" <?php selected($edit_tournament->status, 'finished'); ?>>Finalizado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="theme">Tema Visual</label></th>
                    <td>
                        <select id="theme" name="theme" required>
                            <option value="worldcup" <?php selected($edit_tournament->theme, 'worldcup'); ?>>Mundial (Azul/Dorado)</option>
                            <option value="copaamerica" <?php selected($edit_tournament->theme, 'copaamerica'); ?>>Copa América (Rojo/Amarillo)</option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Actualizar Torneo</button>
                <a href="<?php echo admin_url('admin.php?page=qwc-tournaments'); ?>" class="button">Cancelar</a>
            </p>
        </form>

    <?php else: ?>
        <!-- FORMULARIO DE CREACIÓN -->
        <h2>Crear Nuevo Torneo</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="qwc_save_tournament">
            <?php wp_nonce_field('qwc_save_tournament'); ?>

            <table class="form-table qwc-form-table">
                <tr>
                    <th><label for="name">Nombre del Torneo</label></th>
                    <td>
                        <input type="text" id="name" name="name" placeholder="Ej: Copa Mundial FIFA 2026" required class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="type">Tipo</label></th>
                    <td>
                        <select id="type" name="type" required>
                            <option value="worldcup">Copa Mundial</option>
                            <option value="copaamerica">Copa América</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="year">Año</label></th>
                    <td>
                        <input type="number" id="year" name="year" value="<?php echo date('Y'); ?>" min="2000" max="2100" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="start_date">Fecha Inicio</label></th>
                    <td>
                        <input type="date" id="start_date" name="start_date" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="end_date">Fecha Fin</label></th>
                    <td>
                        <input type="date" id="end_date" name="end_date" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Estado</label></th>
                    <td>
                        <select id="status" name="status" required>
                            <option value="upcoming">Próximo</option>
                            <option value="active">Activo</option>
                            <option value="finished">Finalizado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="theme">Tema Visual</label></th>
                    <td>
                        <select id="theme" name="theme" required>
                            <option value="worldcup">Mundial (Azul/Dorado)</option>
                            <option value="copaamerica">Copa América (Rojo/Amarillo)</option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Crear Torneo</button>
            </p>
        </form>

        <hr>

        <!-- LISTADO DE TORNEOS -->
        <h2>Torneos Existentes</h2>
        <?php if (empty($tournaments)): ?>
            <p>No hay torneos creados aún.</p>
        <?php else: ?>
            <table class="qwc-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Año</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th>Tema</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $t): ?>
                        <tr>
                            <td><?php echo intval($t->id); ?></td>
                            <td><strong><?php echo esc_html($t->name); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($t->type)); ?></td>
                            <td><?php echo esc_html($t->year); ?></td>
                            <td><?php echo esc_html($t->start_date); ?></td>
                            <td><?php echo esc_html($t->end_date); ?></td>
                            <td><span class="qwc-badge qwc-badge-<?php echo esc_attr($t->status); ?>"><?php echo esc_html($t->status); ?></span></td>
                            <td><?php echo esc_html($t->theme); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=qwc-tournaments&edit=' . intval($t->id)); ?>" class="qwc-btn qwc-btn-secondary">Editar</a>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar este torneo y todos sus datos?');">
                                    <input type="hidden" name="action" value="qwc_delete_tournament">
                                    <input type="hidden" name="tournament_id" value="<?php echo intval($t->id); ?>">
                                    <?php wp_nonce_field('qwc_delete_tournament'); ?>
                                    <button type="submit" class="qwc-btn qwc-btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
