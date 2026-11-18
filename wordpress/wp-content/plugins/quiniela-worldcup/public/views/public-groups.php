<?php
if (!defined('ABSPATH')) exit;

$tournament_id = get_option('qwc_default_tournament', 0);
if (!$tournament_id) {
    echo '<p>No hay un torneo activo configurado.</p>';
    return;
}

$standings = QWC_Standings::get_group_standings($tournament_id);

if (empty($standings)) {
    echo '<p>Aún no hay grupos creados para este torneo.</p>';
    return;
}
?>

<div class="qwc-public-groups">
    <h2>📊 Fase de Grupos</h2>

    <?php foreach ($standings as $group_name => $data): ?>
        <div class="qwc-group-block">
            <h3>Grupo <?php echo esc_html($group_name); ?></h3>
            <table class="qwc-group-table">
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
                        <tr class="<?php echo $team['position'] <= 2 ? 'qwc-qualified-row' : ''; ?>">
                            <td class="qwc-pos"><?php echo intval($team['position']); ?></td>
                            <td class="qwc-team-cell">
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
                            <td class="qwc-pts"><strong><?php echo intval($team['points']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="qwc-qualifier-note">🟢 Los 2 primeros clasifican a la fase eliminatoria</p>
        </div>
    <?php endforeach; ?>
</div>
