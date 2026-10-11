<?php
/**
 * Final Boards Printable View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- View Final Boards -->
<div class="team-card" id="final-boards-manager" style="margin-top: 2rem; border-top: 3px solid #28a745;">
    <div class="team-card-header" style="background: #eef9f0;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #28a745;">
            ♟ View Final Boards (Printable)
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="view_round" style="padding:5px;" class="form-control">
                <option value="">Select Round...</option>
                <?php foreach ($all_rounds as $r): ?>
                    <option value="<?= (int)$r['round_number'] ?>" <?= (isset($_GET['view_round']) && $_GET['view_round'] == $r['round_number']) ? 'selected' : '' ?>>
                        Round <?= (int)$r['round_number'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-action" style="padding: 6px 15px;">View Boards</button>
            <?php if (!empty($_GET['view_round'])): ?>
                <button type="button" onclick="window.print()" class="btn-action" style="padding: 6px 15px; background: #333; color: white; border: none; cursor: pointer;">
                    🖨 Print View
                </button>
            <?php endif; ?>
        </form>

        <?php if (!empty($printable_boards)): ?>
            <h3>Active Boards for Round <?= (int)$_GET['view_round'] ?></h3>
            <?php foreach ($printable_boards as $teamLineup): ?>
                <div style="margin-bottom: 20px; page-break-inside: avoid; border: 1px solid #ddd; padding: 15px; background: #fff;">
                    <h4 style="margin-bottom: 10px; font-family: var(--font-display); font-size: 1.2rem;"><?= htmlspecialchars($teamLineup['team_name']) ?></h4>
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                        <tr style="background: #f5f5f5;">
                            <th style="padding:6px; border:1px solid #ddd; width: 80px;">Board</th>
                            <th style="padding:6px; border:1px solid #ddd;">Player Name</th>
                            <th style="padding:6px; border:1px solid #ddd;">Reg Number</th>
                        </tr>
                        <?php foreach ($teamLineup['playing'] as $bIdx => $p): ?>
                            <tr>
                                <td style="padding:6px; border:1px solid #ddd; text-align: center; font-weight: bold;">Board <?= $bIdx + 1 ?></td>
                                <td style="padding:6px; border:1px solid #ddd;"><?= htmlspecialchars($p['name']) ?></td>
                                <td style="padding:6px; border:1px solid #ddd;"><?= htmlspecialchars($p['reg_number']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
