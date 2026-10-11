<?php
/**
 * Tournament Rounds & Drop Boards Management View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- Rounds & Drop Boards Manager -->
<div class="team-card" id="rounds-manager" style="margin-top: 2rem; border-top: 3px solid #0056b3;">
    <div class="team-card-header" style="background: #eef2f5;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #0056b3;">
            ⌚ Tournament Rounds & Drop Boards
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <h3>Create New Round</h3>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <label style="font-size: 0.85rem; font-weight: bold;">Round Number</label><br>
                <input type="number" name="round_number" required style="padding:5px; width:80px;" class="form-control">
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: bold;">Label</label><br>
                <input type="text" name="label" placeholder="e.g. Round 1" style="padding:5px; width:120px;" class="form-control">
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: bold;">Drop Board Deadline</label><br>
                <input type="datetime-local" name="deadline" required style="padding:5px;" class="form-control">
            </div>
            <button type="submit" name="create_round" class="btn-action" style="padding: 7px 15px; background: #0056b3; color: white; border: none; cursor: pointer;">
                Create Round
            </button>
        </form>

        <h3>Manage Rounds</h3>
        <div style="overflow-x: auto; margin-bottom: 2rem;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <tr style="background: #f9f9f9;">
                    <th style="padding:8px; border:1px solid #ddd;">Round</th>
                    <th style="padding:8px; border:1px solid #ddd;">Label</th>
                    <th style="padding:8px; border:1px solid #ddd;">Date</th>
                    <th style="padding:8px; border:1px solid #ddd;">Time</th>
                    <th style="padding:8px; border:1px solid #ddd;">Status Text</th>
                    <th style="padding:8px; border:1px solid #ddd;">Open/Closed</th>
                    <th style="padding:8px; border:1px solid #ddd;">Deadline</th>
                    <th style="padding:8px; border:1px solid #ddd;">Action</th>
                </tr>
                <?php foreach ($all_rounds as $r): ?>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd; text-align: center; font-weight: bold;"><?= (int)$r['round_number'] ?></td>
                    <form method="POST">
                        <input type="hidden" name="round_number" value="<?= (int)$r['round_number'] ?>">
                        <td style="padding:8px; border:1px solid #ddd;">
                            <input type="text" name="label" value="<?= htmlspecialchars($r['label'] ?? "Round {$r['round_number']}") ?>" style="padding:4px; width: 100px;" class="form-control">
                        </td>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <input type="text" name="schedule_date" value="<?= htmlspecialchars($r['schedule_date'] ?? '10 Oct 2026') ?>" placeholder="10 Oct 2026" style="padding:4px; width: 90px;" class="form-control">
                        </td>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <input type="text" name="schedule_time" value="<?= htmlspecialchars($r['schedule_time'] ?? '09:00 – 10:30') ?>" placeholder="09:00 – 10:30" style="padding:4px; width: 100px;" class="form-control">
                        </td>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <input type="text" name="schedule_status" value="<?= htmlspecialchars($r['schedule_status'] ?? 'Scheduled') ?>" placeholder="Ongoing" style="padding:4px; width: 100px;" class="form-control">
                        </td>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <select name="status" style="padding:4px;" class="form-control">
                                <option value="open" <?= $r['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="closed" <?= $r['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </td>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <input type="datetime-local" name="deadline" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($r['deadline'] ?? 'now'))) ?>" style="padding:4px;" class="form-control">
                        </td>
                        <td style="padding:8px; border:1px solid #ddd; text-align: center;">
                            <button type="submit" name="update_round_details" class="btn-action" style="padding: 4px 10px; background: #28a745; color: white; border: none; cursor:pointer;">
                                Save
                            </button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Admin Override Drops Form -->
        <h3>Admin Drop Boards Override</h3>
        <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Admins can set or override drop boards for any team, even after round deadlines.</p>
        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="override_round" required style="padding: 5px;" class="form-control">
                <option value="">Select Round...</option>
                <?php foreach ($all_rounds as $r): ?>
                    <option value="<?= (int)$r['round_number'] ?>" <?= (isset($_GET['override_round']) && $_GET['override_round'] == $r['round_number']) ? 'selected' : '' ?>>Round <?= (int)$r['round_number'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="override_team" required style="padding: 5px;" class="form-control">
                <option value="">Select Team...</option>
                <?php foreach ($all_teams as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= (isset($_GET['override_team']) && $_GET['override_team'] == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['team_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-action" style="padding: 6px 15px;">Load Drop Status</button>
        </form>

        <?php if (!empty($override_data)): ?>
            <div style="background: #fdfdfd; padding: 15px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h4>Editing Drops for: <?= htmlspecialchars($override_data['team']['team_name']) ?> (Round <?= (int)$override_data['round'] ?>)</h4>
                <p>Team Size: <?= count($override_data['members']) ?> members (Select <?= count($override_data['members']) - 4 ?> to drop)</p>
                <form method="POST">
                    <input type="hidden" name="override_team_id" value="<?= (int)$override_data['team']['id'] ?>">
                    <input type="hidden" name="override_round_number" value="<?= (int)$override_data['round'] ?>">
                    <div style="margin: 10px 0;">
                        <?php foreach ($override_data['members'] as $m): ?>
                            <div style="margin-bottom: 5px;">
                                <label>
                                    <input type="checkbox" name="admin_dropped_members[]" value="<?= (int)$m['id'] ?>" <?= in_array($m['id'], $override_data['drops']) ? 'checked' : '' ?>>
                                    [Order <?= (int)$m['member_order'] ?>] <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['reg_number']) ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="admin_update_drops" class="btn-action" style="padding: 6px 15px; background: #d9534f; color: white; border: none; cursor: pointer;">
                        Save Drop Override
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
