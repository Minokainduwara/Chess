<?php
if (!$is_authenticated) return;

// Fetch Rounds
$stmt_rounds = $pdo->query("SELECT * FROM `rounds` ORDER BY `round_number` DESC");
$all_rounds = $stmt_rounds->fetchAll();

// Fetch Teams
$stmt_teams = $pdo->query("SELECT id, team_name FROM `teams` ORDER BY team_name ASC");
$all_teams = $stmt_teams->fetchAll();
?>

<!-- Rounds & Timers -->
<div class="team-card" id="rounds-manager" style="margin-top: 2rem; border-top: 3px solid #0056b3;">
    <div class="team-card-header" style="background: #eef2f5;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #0056b3;">
            ⌚ Tournament Rounds & Drop Boards
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <h3>Create New Round</h3>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 20px;">
            <div>
                <label>Round Number</label><br>
                <input type="number" name="round_number" required style="padding:5px; width:60px;">
            </div>
            <div>
                <label>Label</label><br>
                <input type="text" name="label" placeholder="e.g. Round 1" style="padding:5px;">
            </div>
            <div>
                <label>Drop Board Deadline</label><br>
                <input type="datetime-local" name="deadline" required style="padding:5px;">
            </div>
            <button type="submit" name="create_round" style="padding: 6px 15px; background: #0056b3; color: white; border: none; cursor: pointer;">Create Round</button>
        </form>

        <h3>Manage Rounds</h3>
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
                <td style="padding:8px; border:1px solid #ddd;"><?= $r['round_number'] ?></td>
                <td style="padding:8px; border:1px solid #ddd;">
                    <form method="POST" style="display:inline-flex; gap: 5px;">
                        <input type="hidden" name="round_number" value="<?= $r['round_number'] ?>">
                        <input type="text" name="label" value="<?= htmlspecialchars($r['label'] ?? "Round {$r['round_number']}") ?>" style="padding:4px; width: 100px;">
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                        <input type="text" name="schedule_date" value="<?= htmlspecialchars($r['schedule_date'] ?? '10 Oct 2026') ?>" placeholder="10 Oct 2026" style="padding:4px; width: 90px;">
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                        <input type="text" name="schedule_time" value="<?= htmlspecialchars($r['schedule_time'] ?? '09:00 – 10:30') ?>" placeholder="09:00 – 10:30" style="padding:4px; width: 100px;">
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                        <input type="text" name="schedule_status" value="<?= htmlspecialchars($r['schedule_status'] ?? 'Scheduled') ?>" placeholder="Ongoing" style="padding:4px; width: 100px;">
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                    <select name="status" style="padding:4px;">
                        <option value="open" <?= $r['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= $r['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                    <input type="datetime-local" name="deadline" value="<?= $r['deadline'] ?>" style="padding:4px;">
                </td>
                <td style="padding:8px; border:1px solid #ddd;">
                        <button type="submit" name="update_round_details" style="padding: 4px 8px; cursor:pointer;">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- View Final Boards -->
<div class="team-card" id="final-boards-manager" style="margin-top: 2rem; border-top: 3px solid #28a745;">
    <div class="team-card-header" style="background: #eef9f0;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #28a745;">
            ♟ View Final Boards (Printable)
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <form method="GET" style="margin-bottom: 20px;">
            <select name="view_round" style="padding:5px;">
                <option value="">Select Round...</option>
                <?php foreach ($all_rounds as $r): ?>
                    <option value="<?= $r['round_number'] ?>" <?= (isset($_GET['view_round']) && $_GET['view_round'] == $r['round_number']) ? 'selected' : '' ?>>Round <?= $r['round_number'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="padding: 6px 15px;">View Boards</button>
            <?php if (isset($_GET['view_round']) && $_GET['view_round']): ?>
                <button type="button" onclick="window.print()" style="padding: 6px 15px; background: #333; color: white; border: none; cursor: pointer; margin-left: 10px;">🖨 Print View</button>
            <?php endif; ?>
        </form>

        <?php
        if (isset($_GET['view_round']) && $_GET['view_round']) {
            $vr = (int)$_GET['view_round'];
            echo "<h3>Active Boards for Round $vr</h3>";
            
            foreach ($all_teams as $t) {
                // Get all members for team
                $stmt_m = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
                $stmt_m->execute([$t['id']]);
                $t_members = $stmt_m->fetchAll();
                
                // Get drops
                $stmt_d = $pdo->prepare("SELECT member_id FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
                $stmt_d->execute([$t['id'], $vr]);
                $drops = $stmt_d->fetchAll(PDO::FETCH_COLUMN);
                
                $playing = [];
                $actual_drops = $drops; // Start with submitted drops
                
                // Enforce max 4 players
                $playing_count = 0;
                foreach ($t_members as $m) {
                    if (!in_array($m['id'], $actual_drops) && $playing_count < 4) {
                        $playing[] = $m;
                        $playing_count++;
                    } elseif (!in_array($m['id'], $actual_drops)) {
                        // If they weren't explicitly dropped but we already have 4, force drop them
                        $actual_drops[] = $m['id'];
                    }
                }
                
                if (count($playing) > 0) {
                    echo "<div style='margin-bottom: 20px; page-break-inside: avoid;'>";
                    echo "<h4>{$t['team_name']}</h4>";
                    echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #ddd;'>";
                    echo "<tr style='background: #f9f9f9;'><th style='padding:5px; border:1px solid #ddd;'>Board</th><th style='padding:5px; border:1px solid #ddd;'>Name</th><th style='padding:5px; border:1px solid #ddd;'>Reg No</th><th style='padding:5px; border:1px solid #ddd;'>Original Order</th></tr>";
                    $board_num = 1;
                    foreach ($playing as $p) {
                        echo "<tr>";
                        echo "<td style='padding:5px; border:1px solid #ddd;'>Board {$board_num}</td>";
                        echo "<td style='padding:5px; border:1px solid #ddd;'>{$p['name']}</td>";
                        echo "<td style='padding:5px; border:1px solid #ddd;'>{$p['reg_number']}</td>";
                        echo "<td style='padding:5px; border:1px solid #ddd;'>{$p['member_order']}</td>";
                        echo "</tr>";
                        $board_num++;
                    }
                    echo "</table></div>";
                }
            }
        }
        ?>
    </div>
</div>

<!-- Member CRUD & Board Order -->
<div class="team-card" id="member-crud" style="margin-top: 2rem; border-top: 3px solid #ff9800;">
    <div class="team-card-header" style="background: #fff8e1;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #ff9800;">
            🛠 Edit Team Members & Board Order
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <form method="GET" style="margin-bottom: 20px;">
            <select name="edit_team" style="padding:5px;">
                <option value="">Select Team to Edit...</option>
                <?php foreach ($all_teams as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (isset($_GET['edit_team']) && $_GET['edit_team'] == $t['id']) ? 'selected' : '' ?>><?= $t['team_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="padding: 6px 15px;">Load Members</button>
        </form>

        <?php
        if (isset($_GET['edit_team']) && $_GET['edit_team']) {
            $et_id = (int)$_GET['edit_team'];
            $stmt_m = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
            $stmt_m->execute([$et_id]);
            $et_members = $stmt_m->fetchAll();
            
            if ($et_members) {
                echo "<form method='POST'>";
                echo "<input type='hidden' name='update_members' value='1'>";
                echo "<input type='hidden' name='team_id' value='$et_id'>";
                echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-bottom: 15px;'>";
                echo "<tr style='background: #f9f9f9;'><th style='padding:8px; border:1px solid #ddd;'>Order</th><th style='padding:8px; border:1px solid #ddd;'>Name</th><th style='padding:8px; border:1px solid #ddd;'>Reg No</th><th style='padding:8px; border:1px solid #ddd;'>Gender</th><th style='padding:8px; border:1px solid #ddd;'>Batch</th></tr>";
                foreach ($et_members as $m) {
                    echo "<tr>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'><input type='number' name='members[{$m['id']}][member_order]' value='{$m['member_order']}' style='width:50px;' required></td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'><input type='text' name='members[{$m['id']}][name]' value='".htmlspecialchars($m['name'])."' required></td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'><input type='text' name='members[{$m['id']}][reg_number]' value='".htmlspecialchars($m['reg_number'])."' required></td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'><select name='members[{$m['id']}][gender]'><option value='Male' ".($m['gender']=='Male'?'selected':'').">Male</option><option value='Female' ".($m['gender']=='Female'?'selected':'').">Female</option></select></td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'><input type='text' name='members[{$m['id']}][batch_year]' value='".htmlspecialchars($m['batch_year'])."' style='width:80px;' required></td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<button type='submit' style='padding: 8px 15px; background: #ff9800; color: white; border: none; cursor: pointer;'>Save Member Changes</button>";
                echo "</form>";
            } else {
                echo "<p>No members found for this team.</p>";
            }
        }
        ?>
    </div>
</div>

<!-- Manage Drop Boards (Override) -->
<div class="team-card" id="override-drops" style="margin-top: 2rem; border-top: 3px solid #dc3545;">
    <div class="team-card-header" style="background: #fdf5f6;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #dc3545;">
            🛑 Manage Drop Boards (Admin Override)
        </span>
    </div>
    <div style="padding: 1.5rem;">
        <form method="GET" style="margin-bottom: 20px;">
            <select name="override_round" style="padding:5px;" required>
                <option value="">Select Round...</option>
                <?php foreach ($all_rounds as $r): ?>
                    <option value="<?= $r['round_number'] ?>" <?= (isset($_GET['override_round']) && $_GET['override_round'] == $r['round_number']) ? 'selected' : '' ?>>Round <?= $r['round_number'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="override_team" style="padding:5px;" required>
                <option value="">Select Team...</option>
                <?php foreach ($all_teams as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (isset($_GET['override_team']) && $_GET['override_team'] == $t['id']) ? 'selected' : '' ?>><?= $t['team_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="padding: 6px 15px;">Load Drops</button>
        </form>

        <?php
        if (isset($_GET['override_round']) && isset($_GET['override_team']) && $_GET['override_round'] && $_GET['override_team']) {
            $or_round = (int)$_GET['override_round'];
            $or_team = (int)$_GET['override_team'];
            
            $stmt_m = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
            $stmt_m->execute([$or_team]);
            $or_members = $stmt_m->fetchAll();
            
            $stmt_d = $pdo->prepare("SELECT member_id FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
            $stmt_d->execute([$or_team, $or_round]);
            $or_drops = $stmt_d->fetchAll(PDO::FETCH_COLUMN);
            
            if ($or_members) {
                echo "<p>Select the members to drop for Round $or_round (Total team members: ".count($or_members).")</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='admin_update_drops' value='1'>";
                echo "<input type='hidden' name='override_team_id' value='$or_team'>";
                echo "<input type='hidden' name='override_round_number' value='$or_round'>";
                echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-bottom: 15px;'>";
                echo "<tr style='background: #f9f9f9;'><th style='padding:8px; border:1px solid #ddd;'>Drop</th><th style='padding:8px; border:1px solid #ddd;'>Order</th><th style='padding:8px; border:1px solid #ddd;'>Name</th><th style='padding:8px; border:1px solid #ddd;'>Reg No</th></tr>";
                foreach ($or_members as $m) {
                    $checked = in_array($m['id'], $or_drops) ? 'checked' : '';
                    echo "<tr>";
                    echo "<td style='padding:8px; border:1px solid #ddd; text-align:center;'><input type='checkbox' name='admin_dropped_members[]' value='{$m['id']}' $checked></td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'>{$m['member_order']}</td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'>".htmlspecialchars($m['name'])."</td>";
                    echo "<td style='padding:8px; border:1px solid #ddd;'>".htmlspecialchars($m['reg_number'])."</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<button type='submit' style='padding: 8px 15px; background: #dc3545; color: white; border: none; cursor: pointer;'>Override Drop Boards</button>";
                echo "</form>";
            } else {
                echo "<p>No members found for this team.</p>";
            }
        }
        ?>
    </div>
</div>
