<?php
/**
 * Manual Pairings & Matches Manager View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- Pairings & Matches Manager -->
<div class="team-card" id="pairings-manager" style="margin-top: 2rem; border-top: 3px solid #8e44ad;">
    <div class="team-card-header" style="background: #f4ecf8;">
        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: #8e44ad;">
            ⚔️ Pairings & Matches Manager
        </span>
    </div>
    <div style="padding: 1.5rem;">
        
        <!-- Automatic Swiss Pairing Engine Panel -->
        <div style="margin-bottom: 2rem; background: linear-gradient(135deg, #fbf7ff 0%, #f4ecf8 100%); padding: 1.5rem; border: 1.5px solid #8e44ad; border-radius: 8px; box-shadow: 0 2px 6px rgba(142,68,173,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <div>
                    <h4 style="margin: 0; font-family: var(--font-display); color: #5b2c6f; font-size: 1.25rem; display: flex; align-items: center; gap: 8px;">
                        ⚡ Automatic FIDE Swiss System Pairing Engine
                    </h4>
                    <p style="margin: 6px 0 0 0; font-size: 0.88rem; color: #555; line-height: 1.45;">
                        Automatically calculates official Swiss pairings based on current <strong>Match Points (MP)</strong> &amp; <strong>Game Points (GP)</strong> standings, strictly prevents duplicate match-ups, alternates Board 1 White/Black colors according to FIDE rules, and manages pairing byes for odd teams.
                    </p>
                </div>
                <div style="background: rgba(142, 68, 173, 0.1); border-left: 3px solid #8e44ad; padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; color: #4a235a;">
                    <strong>FIDE Dutch System</strong> &bull; Score Brackets &bull; Color Balancing
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Generate Swiss pairings for the selected round? Existing unplayed/draft pairings in this round will be updated.');">
                <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #e1d5eb;">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: bold; color: #333; display: block; margin-bottom: 4px;">Target Round</label>
                        <select name="swiss_round_number" required style="padding: 7px 12px; border-radius: 4px; border: 1px solid #ccc; font-weight: bold; min-width: 140px;" class="form-control">
                            <?php foreach ($all_rounds as $r): ?>
                                <option value="<?= (int)$r['round_number'] ?>">Round <?= (int)$r['round_number'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="padding-bottom: 6px;">
                        <label style="font-size: 0.85rem; color: #444; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="overwrite_scheduled" value="1" checked style="accent-color: #8e44ad;">
                            Overwrite existing draft/scheduled pairings for this round
                        </label>
                        <small style="color: #888; display: block; font-size: 0.75rem;">Note: Finalized/completed match scores are strictly protected and never overwritten.</small>
                    </div>

                    <div style="margin-left: auto;">
                        <button type="submit" name="auto_swiss_pairing" class="btn-action" style="padding: 8px 18px; background: linear-gradient(135deg, #8e44ad 0%, #6c3483 100%); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                            ⚡ Generate Swiss Pairings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Manual Match Form -->
        <form method="POST" style="margin-bottom: 2rem; background: #fafafa; padding: 1rem; border: 1px solid #ddd; border-radius: 4px;">
            <h4 style="margin-top: 0; font-family: var(--font-display);">Manual Schedule / Custom Pairing</h4>
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Round</label><br>
                    <select name="round_number" required style="padding: 5px;" class="form-control">
                        <?php foreach ($all_rounds as $r): ?>
                            <option value="<?= (int)$r['round_number'] ?>">Round <?= (int)$r['round_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Team A (White on Bd 1)</label><br>
                    <select name="team_a_id" required style="padding: 5px;" class="form-control">
                        <option value="">Select Team...</option>
                        <?php foreach ($all_teams as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Team B (Black on Bd 1)</label><br>
                    <select name="team_b_id" required style="padding: 5px;" class="form-control">
                        <option value="">Select Team...</option>
                        <?php foreach ($all_teams as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="create_match" class="btn-action" style="padding: 6px 15px; background: #8e44ad; color: white; border: none; cursor: pointer;">
                    Create Pairing
                </button>
            </div>
        </form>

        <!-- List Matches by Round -->
        <?php foreach ($all_rounds as $r): $rn = (int)$r['round_number']; ?>
            <div style="margin-bottom: 2rem;">
                <h3 style="border-bottom: 2px solid #ddd; padding-bottom: 5px; font-family: var(--font-display);">Round <?= $rn ?> Pairings</h3>
                
                <?php if (empty($all_matches[$rn])): ?>
                    <p style="color: #666;">No matches scheduled for this round yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-bottom: 10px;">
                            <tr style="background: #f9f9f9;">
                                <th style="padding:8px; border:1px solid #ddd;">Match</th>
                                <th style="padding:8px; border:1px solid #ddd;">Status</th>
                                <th style="padding:8px; border:1px solid #ddd; text-align:center;">Team A (GP | MP)</th>
                                <th style="padding:8px; border:1px solid #ddd; text-align:center;">Team B (GP | MP)</th>
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
                                        <?= htmlspecialchars($m['team_a_gp']) ?> | <?= htmlspecialchars($m['team_a_mp']) ?>
                                    </td>
                                    <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                        <?= htmlspecialchars($m['team_b_gp']) ?> | <?= htmlspecialchars($m['team_b_mp']) ?>
                                    </td>
                                    <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                        <form method="GET" style="display:inline;">
                                            <input type="hidden" name="edit_match" value="<?= (int)$m['id'] ?>">
                                            <button type="submit" class="btn-action" style="padding: 4px 8px; background:#0056b3; color:#fff; border:none; cursor:pointer;">Enter Results</button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this match?');">
                                            <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                                            <button type="submit" name="delete_match" class="btn-action" style="padding: 4px 8px; background:#dc3545; color:#fff; border:none; cursor:pointer;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Board-by-Board Result Editor UI -->
        <?php if (!empty($edit_match_data)): ?>
            <div style="margin-top: 2rem; border: 2px solid #8e44ad; padding: 1.5rem; background: #fff;">
                <h3 style="margin-top:0; font-family: var(--font-display);">Enter Results: <?= htmlspecialchars($edit_match_data['match']['ta_name']) ?> vs <?= htmlspecialchars($edit_match_data['match']['tb_name']) ?></h3>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                    Players are automatically mapped according to board orders and submitted drop boards.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="match_id" value="<?= (int)$edit_match_data['match']['id'] ?>">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                            <tr style="background: #f0f0f0;">
                                <th style="padding:8px; border:1px solid #ddd; width: 60px;">Board</th>
                                <th style="padding:8px; border:1px solid #ddd;"><?= htmlspecialchars($edit_match_data['match']['ta_name']) ?> (White)</th>
                                <th style="padding:8px; border:1px solid #ddd; text-align: center; width: 140px;">Result</th>
                                <th style="padding:8px; border:1px solid #ddd;"><?= htmlspecialchars($edit_match_data['match']['tb_name']) ?> (Black)</th>
                            </tr>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <?php
                                $pA = $edit_match_data['ta_playing'][$i-1] ?? null;
                                $pB = $edit_match_data['tb_playing'][$i-1] ?? null;
                                $bRes = $edit_match_data['match']['boards'][$i]['result'] ?? '';
                                ?>
                                <tr>
                                    <td style="padding:8px; border:1px solid #ddd; text-align:center; font-weight:bold;">Board <?= $i ?></td>
                                    <td style="padding:8px; border:1px solid #ddd;">
                                        <?php if ($pA): ?>
                                            <?= htmlspecialchars($pA['name']) ?>
                                            <input type="hidden" name="team_a_member_id[<?= $i ?>]" value="<?= (int)$pA['id'] ?>">
                                        <?php else: ?>
                                            <span style="color:red;">No Player</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                                        <select name="boards[<?= $i ?>]" style="padding: 5px; font-weight:bold;" class="form-control">
                                            <option value="">- Select -</option>
                                            <option value="1-0" <?= $bRes === '1-0' ? 'selected' : '' ?>>1 - 0</option>
                                            <option value="0.5-0.5" <?= $bRes === '0.5-0.5' ? 'selected' : '' ?>>½ - ½</option>
                                            <option value="0-1" <?= $bRes === '0-1' ? 'selected' : '' ?>>0 - 1</option>
                                        </select>
                                    </td>
                                    <td style="padding:8px; border:1px solid #ddd;">
                                        <?php if ($pB): ?>
                                            <?= htmlspecialchars($pB['name']) ?>
                                            <input type="hidden" name="team_b_member_id[<?= $i ?>]" value="<?= (int)$pB['id'] ?>">
                                        <?php else: ?>
                                            <span style="color:red;">No Player</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </table>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <label style="font-weight: bold;">Match Status:</label>
                        <select name="status" style="padding: 5px;" class="form-control">
                            <option value="scheduled" <?= $edit_match_data['match']['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled / In Progress</option>
                            <option value="completed" <?= $edit_match_data['match']['status'] === 'completed' ? 'selected' : '' ?>>Completed (Finalize &amp; Update Standings)</option>
                            <option value="draft" <?= $edit_match_data['match']['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hide)</option>
                        </select>
                        <button type="submit" name="save_match_results" class="btn-action" style="padding: 8px 20px; background: #28a745; color: #fff; border: none; font-weight: bold; cursor: pointer;">
                            Save Board Results
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- 6x6 Pairing Matrix -->
        <h3 style="margin-top: 3rem; font-family: var(--font-display);">Pairing Matrix</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; text-align: center;">
                <tr style="background: #f9f9f9;">
                    <th style="padding:8px; border:1px solid #ddd;">Teams</th>
                    <?php foreach ($all_teams as $t): ?>
                        <th style="padding:8px; border:1px solid #ddd;" title="<?= htmlspecialchars($t['team_name']) ?>">
                            <?= mb_substr($t['team_name'], 0, 3) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($all_teams as $tRow): ?>
                    <tr>
                        <th style="padding:8px; border:1px solid #ddd; background: #f9f9f9; text-align:left;">
                            <?= htmlspecialchars($tRow['team_name']) ?>
                        </th>
                        <?php foreach ($all_teams as $tCol): ?>
                            <td style="padding:8px; border:1px solid #ddd;">
                                <?php 
                                if ($tRow['id'] == $tCol['id']) {
                                    echo "<span style='color:#ccc;'>—</span>";
                                } else {
                                    $cell = $pairing_matrix[$tRow['id']][$tCol['id']] ?? null;
                                    if ($cell) {
                                        echo "<span style='color:green; font-weight:bold;'>" . htmlspecialchars($cell) . "</span>";
                                    } else {
                                        echo "<span style='color:#999;'>-</span>";
                                    }
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
    </div>
</div>
