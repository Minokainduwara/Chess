<?php
/**
 * Teams Roster & Approval Management View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- Toolbar (Search & Filter) -->
<div class="toolbar">
    <form method="GET" action="admin.php" class="search-form">
        <input type="text" name="search" class="form-control" placeholder="Search team, player, or reg number..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <select name="filter_batch" class="form-control" style="max-width: 140px;">
            <option value="">All Batches</option>
            <?php foreach (array_keys($batch_counts ?? []) as $byear): ?>
                <option value="<?= htmlspecialchars($byear) ?>" <?= (isset($_GET['filter_batch']) && $_GET['filter_batch'] === $byear) ? 'selected' : '' ?>>
                    Batch <?= htmlspecialchars($byear) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-action">Filter</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['filter_batch'])): ?>
            <a href="admin.php" class="btn-link">Reset</a>
        <?php endif; ?>
    </form>

    <div style="display: flex; gap: 0.75rem;">
        <button onclick="window.print()" class="btn-action">🖨️ Print Rosters</button>
        <a href="admin.php?action=export_csv" class="btn-action" style="background: var(--red); color: var(--cream); border-color: var(--red);">📥 Export to CSV</a>
    </div>
</div>

<?php if (($pending_teams ?? 0) > 0): ?>
    <div class="alert" style="border-left: 4px solid #E65100; background: #FFF3E0; color: #BF360C;">
        <strong>&#9203; <?= $pending_teams ?> team<?= $pending_teams == 1 ? '' : 's' ?> awaiting approval.</strong>
        New registrations stay hidden from the public standings until you approve them below.
    </div>
<?php endif; ?>

<!-- Teams Listing -->
<?php if (empty($teams_data)): ?>
    <div class="alert" style="text-align: center; padding: 3rem 1rem;">
        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">♟️</div>
        <h3 style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: 0.5rem;">No registered teams found</h3>
        <p style="color: var(--ink-muted);">
            <?= (!empty($_GET['search']) || !empty($_GET['filter_batch'])) ? 'No teams match your filter criteria.' : 'When teams register through the website form, they will appear here.' ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($teams_data as $idx => $team): ?>
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-title-group">
                    <span style="font-family: var(--font-display); font-weight: 700; color: var(--red); font-size: 1.25rem;">#<?= htmlspecialchars($team['id']) ?></span>
                    <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                    <?php $team_status = $team['status'] ?? 'pending'; ?>
                    <?php if ($team_status === 'approved'): ?>
                        <span class="badge badge-green">&#10003; Approved</span>
                    <?php elseif ($team_status === 'rejected'): ?>
                        <span class="badge" style="background: #FFEBEE; color: #C0392B; border: 1px solid #FFCDD2; font-weight: 700;">&#10007; Rejected</span>
                    <?php else: ?>
                        <span class="badge" style="background: #FFF3E0; color: #BF360C; border: 1px solid #FFCC80; font-weight: 700;">&#9203; Pending Approval</span>
                    <?php endif; ?>
                    <span class="badge" style="background: #FFF9C4; color: #5D4037; border: 1px solid #FFE082; font-weight: 700; font-size: 0.85rem;">
                        ♟ P: <?= (int)$team['played'] ?> · W: <?= (int)$team['won'] ?> · D: <?= (int)$team['drawn'] ?> · L: <?= (int)$team['lost'] ?> · GP: <?= number_format((float)$team['game_points'], 1) ?> · MP: <?= (int)$team['match_points'] ?>
                    </span>
                    <span class="badge badge-green">✓ <?= $team['member_count'] ?> Players</span>
                    <span class="badge badge-female">✓ <?= $team['female_count'] ?> Girl<?= $team['female_count'] == 1 ? '' : 's' ?></span>
                    <span class="badge badge-blue">✓ Batches: <?= htmlspecialchars($team['batches_list']) ?></span>
                    <?php if (!empty($team['standing_notes'])): ?>
                        <span class="badge" style="background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; font-weight: 700;">
                            ★ <?= htmlspecialchars($team['standing_notes']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="team-meta">
                    <?php if (!empty($team['contact_phone'])): ?>
                        <span>📞 <?= htmlspecialchars($team['contact_phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($team['contact_email'])): ?>
                        <span>✉️ <?= htmlspecialchars($team['contact_email']) ?></span>
                    <?php endif; ?>
                    <span>📅 <?= date('M d, Y H:i', strtotime($team['created_at'])) ?></span>
                </div>
            </div>

            <!-- Member Roster & Board Order Form -->
            <form method="POST" action="admin.php">
                <input type="hidden" name="team_id" value="<?= (int)$team['id'] ?>">
                <table class="roster-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Board Order</th>
                            <th>Player Name</th>
                            <th>Registration No.</th>
                            <th>Gender</th>
                            <th>Batch</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team['members'] as $m): ?>
                            <tr>
                                <td>
                                    <input type="number" min="1" max="6" name="members[<?= (int)$m['id'] ?>][member_order]" value="<?= (int)$m['member_order'] ?>" class="form-control" style="width: 60px; padding: 3px 5px; text-align: center;">
                                </td>
                                <td>
                                    <input type="text" name="members[<?= (int)$m['id'] ?>][name]" value="<?= htmlspecialchars($m['name']) ?>" class="form-control" style="padding: 3px 6px;">
                                </td>
                                <td>
                                    <input type="text" name="members[<?= (int)$m['id'] ?>][reg_number]" value="<?= htmlspecialchars($m['reg_number']) ?>" class="form-control" style="padding: 3px 6px; width: 120px;">
                                </td>
                                <td>
                                    <select name="members[<?= (int)$m['id'] ?>][gender]" class="form-control" style="padding: 3px;">
                                        <option value="Male" <?= $m['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $m['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="members[<?= (int)$m['id'] ?>][batch_year]" value="<?= htmlspecialchars($m['batch_year']) ?>" class="form-control" style="padding: 3px 6px; width: 70px;">
                                </td>
                                <td>
                                    <?= $m['is_captain'] ? '<span class="badge badge-captain">★ Captain</span>' : '<span style="color: var(--ink-muted); font-size: 0.85rem;">Player</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="team-card-footer">
                    <div>
                        <button type="submit" name="update_members" class="btn-action" style="background: #2E7D32; color: #FFF; border-color: #2E7D32; font-size: 0.85rem;">
                            💾 Save Member Order &amp; Info
                        </button>
                    </div>
                    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                        <?php if (($team['status'] ?? 'pending') !== 'approved'): ?>
                            <button type="button" class="btn-action" style="background: #2E7D32; color: #F5F0E0; border-color: #2E7D32;" onclick="moderateTeam(<?= (int)$team['id'] ?>, 'approve_team', this)">&#10003; Approve for Standings</button>
                        <?php endif; ?>
                        <?php if (($team['status'] ?? 'pending') === 'pending'): ?>
                            <button type="button" class="btn-action" style="background: #C0392B; color: #F5F0E0; border-color: #C0392B;" onclick="moderateTeam(<?= (int)$team['id'] ?>, 'reject_team', this)">&#10007; Reject</button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="submit" name="delete_team_id" value="<?= (int)$team['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete team \'<?= htmlspecialchars(addslashes($team['team_name'])) ?>\'? This action cannot be undone.');">
                            Delete Team
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
