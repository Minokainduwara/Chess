<?php
if (!$is_authenticated) return;

// Handle Pairings Post Requests
if (isset($_POST['create_match'])) {
    $r_num = (int)$_POST['round_number'];
    $ta = (int)$_POST['team_a_id'];
    $tb = (int)$_POST['team_b_id'];

    if ($ta === $tb) {
        $error_msg = "A team cannot play itself.";
    } else {
        // Check if either team is already playing in this round
        $stmt_check_round = $pdo->prepare("SELECT COUNT(*) FROM `matches` WHERE `round_number` = ? AND (`team_a_id` IN (?, ?) OR `team_b_id` IN (?, ?))");
        $stmt_check_round->execute([$r_num, $ta, $tb, $ta, $tb]);
        if ($stmt_check_round->fetchColumn() > 0) {
            $error_msg = "One or both teams are already scheduled to play in this round.";
        } else {
            // Check if they played each other historically
            $stmt_check_hist = $pdo->prepare("SELECT COUNT(*) FROM `matches` WHERE (`team_a_id` = ? AND `team_b_id` = ?) OR (`team_a_id` = ? AND `team_b_id` = ?)");
            $stmt_check_hist->execute([$ta, $tb, $tb, $ta]);
            if ($stmt_check_hist->fetchColumn() > 0) {
                $error_msg = "These teams have already played each other in a previous round.";
            } else {
                // Create match
                $stmt = $pdo->prepare("INSERT INTO `matches` (`round_number`, `team_a_id`, `team_b_id`, `status`) VALUES (?, ?, ?, 'scheduled')");
                $stmt->execute([$r_num, $ta, $tb]);
                $success_msg = "Match successfully scheduled for Round $r_num.";
            }
        }
    }
}

if (isset($_POST['delete_match'])) {
    $match_id = (int)$_POST['match_id'];
    // only allow delete if not completed, or require strict override
    $stmt = $pdo->prepare("SELECT status FROM `matches` WHERE id = ?");
    $stmt->execute([$match_id]);
    $st = $stmt->fetchColumn();
    
    if ($st === 'completed') {
        $error_msg = "Cannot delete a completed match. Change its status first.";
    } else {
        $pdo->prepare("DELETE FROM `matches` WHERE id = ?")->execute([$match_id]);
        $success_msg = "Match deleted.";
    }
}

if (isset($_POST['save_match_results'])) {
    $match_id = (int)$_POST['match_id'];
    $status = $_POST['status'];
    $board_results = $_POST['boards'] ?? [];
    $a_ids = $_POST['team_a_member_id'] ?? [];
    $b_ids = $_POST['team_b_member_id'] ?? [];

    try {
        $pdo->beginTransaction();
        
        $team_a_gp = 0.0;
        $team_b_gp = 0.0;

        // Clear existing board results
        $pdo->prepare("DELETE FROM `match_boards` WHERE match_id = ?")->execute([$match_id]);

        $stmt_ins = $pdo->prepare("INSERT INTO `match_boards` (`match_id`, `board_number`, `team_a_member_id`, `team_b_member_id`, `result`) VALUES (?, ?, ?, ?, ?)");

        for ($i=1; $i<=4; $i++) {
            $res = $board_results[$i] ?? null;
            $a_m = $a_ids[$i] ?? null;
            $b_m = $b_ids[$i] ?? null;
            
            if ($res === '1-0') {
                $team_a_gp += 1.0;
            } elseif ($res === '0.5-0.5') {
                $team_a_gp += 0.5;
                $team_b_gp += 0.5;
            } elseif ($res === '0-1') {
                $team_b_gp += 1.0;
            }

            $stmt_ins->execute([$match_id, $i, $a_m ?: null, $b_m ?: null, $res]);
        }

        // Calculate MP
        $team_a_mp = 0;
        $team_b_mp = 0;

        if ($team_a_gp > $team_b_gp) {
            $team_a_mp = 2;
            $team_b_mp = ($team_b_gp == 0) ? -1 : 0;
        } elseif ($team_a_gp < $team_b_gp) {
            $team_b_mp = 2;
            $team_a_mp = ($team_a_gp == 0) ? -1 : 0;
        } else {
            $team_a_mp = 1;
            $team_b_mp = 1;
        }

        $stmt_upd = $pdo->prepare("UPDATE `matches` SET `status` = ?, `team_a_gp` = ?, `team_b_gp` = ?, `team_a_mp` = ?, `team_b_mp` = ? WHERE id = ?");
        $stmt_upd->execute([$status, $team_a_gp, $team_b_gp, $team_a_mp, $team_b_mp, $match_id]);

        // Auto-update Standings
        if ($status === 'completed') {
            recalculate_standings($pdo);
            $success_msg = "Match completed and standings updated!";
        } else {
            $success_msg = "Match results saved as Draft/Scheduled.";
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error saving match: " . $e->getMessage();
    }
}

function recalculate_standings($pdo) {
    // Reset all team stats
    $pdo->query("UPDATE `teams` SET `played` = 0, `won` = 0, `drawn` = 0, `lost` = 0, `game_points` = 0, `match_points` = 0 WHERE `status` = 'approved'");

    $stmt_m = $pdo->query("SELECT * FROM `matches` WHERE `status` = 'completed'");
    while ($m = $stmt_m->fetch()) {
        $a = $m['team_a_id'];
        $b = $m['team_b_id'];

        $a_w = $m['team_a_gp'] > $m['team_b_gp'] ? 1 : 0;
        $a_d = $m['team_a_gp'] == $m['team_b_gp'] ? 1 : 0;
        $a_l = $m['team_a_gp'] < $m['team_b_gp'] ? 1 : 0;

        $b_w = $m['team_b_gp'] > $m['team_a_gp'] ? 1 : 0;
        $b_d = $m['team_b_gp'] == $m['team_a_gp'] ? 1 : 0;
        $b_l = $m['team_b_gp'] < $m['team_a_gp'] ? 1 : 0;

        $pdo->prepare("UPDATE `teams` SET `played` = `played` + 1, `won` = `won` + ?, `drawn` = `drawn` + ?, `lost` = `lost` + ?, `game_points` = `game_points` + ?, `match_points` = `match_points` + ? WHERE id = ?")->execute([$a_w, $a_d, $a_l, $m['team_a_gp'], $m['team_a_mp'], $a]);
        $pdo->prepare("UPDATE `teams` SET `played` = `played` + 1, `won` = `won` + ?, `drawn` = `drawn` + ?, `lost` = `lost` + ?, `game_points` = `game_points` + ?, `match_points` = `match_points` + ? WHERE id = ?")->execute([$b_w, $b_d, $b_l, $m['team_b_gp'], $m['team_b_mp'], $b]);
    }
}

// Helper to get playing members based on drops
function get_playing_members($pdo, $team_id, $round_num) {
    $stmt_m = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
    $stmt_m->execute([$team_id]);
    $t_members = $stmt_m->fetchAll();
    
    $stmt_d = $pdo->prepare("SELECT member_id FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
    $stmt_d->execute([$team_id, $round_num]);
    $drops = $stmt_d->fetchAll(PDO::FETCH_COLUMN);
    
    $playing = [];
    $actual_drops = $drops;
    $playing_count = 0;
    foreach ($t_members as $m) {
        if (!in_array($m['id'], $actual_drops) && $playing_count < 4) {
            $playing[] = $m;
            $playing_count++;
        } elseif (!in_array($m['id'], $actual_drops)) {
            $actual_drops[] = $m['id'];
        }
    }
    return $playing;
}

// Fetch all matches grouped by round
$all_matches = [];
$stmt_all_m = $pdo->query("SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name FROM matches m JOIN teams t1 ON m.team_a_id = t1.id JOIN teams t2 ON m.team_b_id = t2.id ORDER BY m.round_number ASC, m.id ASC");
while ($row = $stmt_all_m->fetch()) {
    $all_matches[$row['round_number']][] = $row;
}
?>

<div class="team-card" style="margin-top: 2rem; border-top: 3px solid #8e44ad;">
    <div class="team-card-header" style="background: #f4ecf8;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #8e44ad;">
            ⚔️ Pairings & Matches Manager
        </span>
    </div>
    <div style="padding: 1.5rem;">
        
        <!-- Create Match Form -->
        <form method="POST" style="margin-bottom: 2rem; background: #fafafa; padding: 1rem; border: 1px solid #ddd; border-radius: 4px;">
            <h4 style="margin-top: 0;">Schedule a Match</h4>
            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div>
                    <label>Round</label><br>
                    <select name="round_number" required style="padding: 5px;">
                        <?php foreach ($all_rounds as $r): ?>
                            <option value="<?= $r['round_number'] ?>">Round <?= $r['round_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Team A (White on Bd 1)</label><br>
                    <select name="team_a_id" required style="padding: 5px;">
                        <option value="">Select Team...</option>
                        <?php foreach ($all_teams as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Team B (Black on Bd 1)</label><br>
                    <select name="team_b_id" required style="padding: 5px;">
                        <option value="">Select Team...</option>
                        <?php foreach ($all_teams as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="create_match" style="padding: 6px 15px; background: #8e44ad; color: white; border: none; cursor: pointer;">Create Pairing</button>
            </div>
        </form>

        <!-- List Matches by Round -->
        <?php foreach ($all_rounds as $r): $rn = $r['round_number']; ?>
            <div style="margin-bottom: 2rem;">
                <h3 style="border-bottom: 2px solid #ddd; padding-bottom: 5px;">Round <?= $rn ?> Pairings</h3>
                
                <?php if (empty($all_matches[$rn])): ?>
                    <p style="color: #666;">No matches scheduled for this round yet.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-bottom: 10px;">
                        <tr style="background: #f9f9f9;">
                            <th style="padding:8px; border:1px solid #ddd;">Match</th>
                            <th style="padding:8px; border:1px solid #ddd;">Status</th>
                            <th style="padding:8px; border:1px solid #ddd;">Team A (GP | MP)</th>
                            <th style="padding:8px; border:1px solid #ddd;">Team B (GP | MP)</th>
                            <th style="padding:8px; border:1px solid #ddd; text-align:center;">Actions</th>
                        </tr>
                        <?php foreach ($all_matches[$rn] as $m): ?>
                            <tr>
                                <td style="padding:8px; border:1px solid #ddd; font-weight: bold;"><?= htmlspecialchars($m['ta_name']) ?> vs <?= htmlspecialchars($m['tb_name']) ?></td>
                                <td style="padding:8px; border:1px solid #ddd;">
                                    <?php if ($m['status'] == 'completed'): ?>
                                        <span style="color: green; font-weight:bold;">Completed</span>
                                    <?php elseif ($m['status'] == 'scheduled'): ?>
                                        <span style="color: orange; font-weight:bold;">Scheduled</span>
                                    <?php else: ?>
                                        <span style="color: gray; font-weight:bold;">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                    <?= $m['team_a_gp'] ?> | <?= $m['team_a_mp'] ?>
                                </td>
                                <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                    <?= $m['team_b_gp'] ?> | <?= $m['team_b_mp'] ?>
                                </td>
                                <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                    <form method="GET" style="display:inline;">
                                        <input type="hidden" name="edit_match" value="<?= $m['id'] ?>">
                                        <button type="submit" style="padding: 4px 8px; background:#0056b3; color:#fff; border:none; cursor:pointer;">Enter Results</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this match?');">
                                        <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                                        <button type="submit" name="delete_match" style="padding: 4px 8px; background:#dc3545; color:#fff; border:none; cursor:pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php
        // Editor UI for a specific match
        if (isset($_GET['edit_match'])) {
            $mid = (int)$_GET['edit_match'];
            $stmt_edit = $pdo->prepare("SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name FROM matches m JOIN teams t1 ON m.team_a_id = t1.id JOIN teams t2 ON m.team_b_id = t2.id WHERE m.id = ?");
            $stmt_edit->execute([$mid]);
            $edit_m = $stmt_edit->fetch();

            if ($edit_m) {
                // Fetch existing board results if any
                $stmt_br = $pdo->prepare("SELECT * FROM match_boards WHERE match_id = ? ORDER BY board_number ASC");
                $stmt_br->execute([$mid]);
                $boards = [];
                while ($br = $stmt_br->fetch()) {
                    $boards[$br['board_number']] = $br;
                }

                $ta_playing = get_playing_members($pdo, $edit_m['team_a_id'], $edit_m['round_number']);
                $tb_playing = get_playing_members($pdo, $edit_m['team_b_id'], $edit_m['round_number']);
                
                echo "<div style='margin-top: 2rem; border: 2px solid #8e44ad; padding: 1.5rem; background: #fff;'>";
                echo "<h3 style='margin-top:0;'>Enter Results: {$edit_m['ta_name']} vs {$edit_m['tb_name']}</h3>";
                echo "<p>Ensure all drop boards for both teams have been submitted or overridden before entering board results!</p>";
                
                echo "<form method='POST'>";
                echo "<input type='hidden' name='match_id' value='$mid'>";
                echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 1rem;'>";
                echo "<tr style='background: #f0f0f0;'><th style='padding:8px; border:1px solid #ddd;'>Board</th><th style='padding:8px; border:1px solid #ddd;'>{$edit_m['ta_name']} (White)</th><th style='padding:8px; border:1px solid #ddd;'>Result</th><th style='padding:8px; border:1px solid #ddd;'>{$edit_m['tb_name']} (Black)</th></tr>";
                
                for ($i=1; $i<=4; $i++) {
                    $pA = $ta_playing[$i-1] ?? null;
                    $pB = $tb_playing[$i-1] ?? null;
                    $b_res = $boards[$i]['result'] ?? '';
                    
                    echo "<tr>";
                    echo "<td style='padding:8px; border:1px solid #ddd; text-align:center; font-weight:bold;'>$i</td>";
                    
                    // Team A
                    echo "<td style='padding:8px; border:1px solid #ddd;'>";
                    if ($pA) {
                        echo htmlspecialchars($pA['name']);
                        echo "<input type='hidden' name='team_a_member_id[$i]' value='{$pA['id']}'>";
                    } else {
                        echo "<span style='color:red;'>No Player</span>";
                    }
                    echo "</td>";
                    
                    // Result Dropdown
                    echo "<td style='padding:8px; border:1px solid #ddd; text-align:center;'>";
                    echo "<select name='boards[$i]' style='padding: 5px; font-weight:bold;'>";
                    echo "<option value=''>- Select -</option>";
                    echo "<option value='1-0' ".($b_res=='1-0'?'selected':'').">1 - 0</option>";
                    echo "<option value='0.5-0.5' ".($b_res=='0.5-0.5'?'selected':'').">½ - ½</option>";
                    echo "<option value='0-1' ".($b_res=='0-1'?'selected':'').">0 - 1</option>";
                    echo "</select>";
                    echo "</td>";
                    
                    // Team B
                    echo "<td style='padding:8px; border:1px solid #ddd;'>";
                    if ($pB) {
                        echo htmlspecialchars($pB['name']);
                        echo "<input type='hidden' name='team_b_member_id[$i]' value='{$pB['id']}'>";
                    } else {
                        echo "<span style='color:red;'>No Player</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<div style='display: flex; gap: 10px; align-items: center;'>";
                echo "<label>Match Status:</label>";
                echo "<select name='status' style='padding: 5px;'>";
                echo "<option value='scheduled' ".($edit_m['status']=='scheduled'?'selected':'').">Scheduled / In Progress</option>";
                echo "<option value='completed' ".($edit_m['status']=='completed'?'selected':'').">Completed (Finalize & Update Standings)</option>";
                echo "<option value='draft' ".($edit_m['status']=='draft'?'selected':'').">Draft (Hide)</option>";
                echo "</select>";
                
                echo "<button type='submit' name='save_match_results' style='padding: 8px 20px; background: #28a745; color: #fff; border: none; font-weight: bold; cursor: pointer;'>Save Board Results</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";
            }
        }
        ?>

        <!-- Matrix -->
        <h3 style="margin-top: 3rem;">Pairing Matrix</h3>
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; text-align: center;">
            <tr style="background: #f9f9f9;">
                <th style="padding:8px; border:1px solid #ddd;">Teams</th>
                <?php foreach ($all_teams as $t): ?>
                    <th style="padding:8px; border:1px solid #ddd;" title="<?= htmlspecialchars($t['team_name']) ?>"><?= mb_substr($t['team_name'], 0, 3) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($all_teams as $tRow): ?>
                <tr>
                    <th style="padding:8px; border:1px solid #ddd; background: #f9f9f9; text-align:left;"><?= htmlspecialchars($tRow['team_name']) ?></th>
                    <?php foreach ($all_teams as $tCol): ?>
                        <td style="padding:8px; border:1px solid #ddd;">
                            <?php 
                            if ($tRow['id'] == $tCol['id']) {
                                echo "<span style='color:#ccc;'>—</span>";
                            } else {
                                // check match
                                $found = false;
                                foreach ($all_matches as $rMatches) {
                                    foreach ($rMatches as $m) {
                                        if (($m['team_a_id'] == $tRow['id'] && $m['team_b_id'] == $tCol['id']) || ($m['team_b_id'] == $tRow['id'] && $m['team_a_id'] == $tCol['id'])) {
                                            $found = true;
                                            if ($m['status'] == 'completed') {
                                                echo "<span style='color:green; font-weight:bold;' title='Round {$m['round_number']}'>✔</span>";
                                            } else {
                                                echo "<span style='color:orange;' title='Round {$m['round_number']}'>R{$m['round_number']}</span>";
                                            }
                                        }
                                    }
                                }
                                if (!$found) echo "<span style='color:#999;'>-</span>";
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        
    </div>
</div>
