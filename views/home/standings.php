<?php
/**
 * Team Standings Scoreboard Section
 * FOT Knights Arena — Chess Tournament
 */
if (!$scoreboard_visible) return;
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ STANDINGS SCOREBOARD ═══ -->
    <section class="standings page-rail" id="standings" aria-labelledby="standings-heading">
        <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
            <h2 class="standings-heading" id="standings-heading" style="margin-bottom: 0;">
                <span aria-hidden="true">♛</span> Arena Standings
            </h2>
            <?php if (!empty($scoreboard_status)): ?>
                <span class="standings-status-badge gtouch-hover">
                    ● <?= htmlspecialchars($scoreboard_status) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="standings-table-wrap gtouch-swipe">
            <table class="standings-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Rank</th>
                        <th>Team</th>
                        <th style="text-align: center; width: 60px;">P</th>
                        <th style="text-align: center; width: 60px;">W</th>
                        <th style="text-align: center; width: 60px;">D</th>
                        <th style="text-align: center; width: 60px;">L</th>
                        <th style="text-align: center; width: 80px;" title="Game Points / Board Points">GP</th>
                        <th style="text-align: center; width: 80px;" title="Match Points">MP</th>
                        <th>Status / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($standings_teams)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2.5rem 1rem; color: var(--ink-light);">
                                <span style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">♟️</span>
                                Official team rankings will update here as rounds commence.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($standings_teams as $idx => $st): ?>
                            <?php $rank = $idx + 1; ?>
                            <tr class="<?= $rank <= 3 ? 'rank-podium rank-' . $rank : '' ?> gtouch-tap">
                                <td>
                                    <?php if ($rank === 1): ?>
                                        <span class="rank-badge rank-gold">♔ 1</span>
                                    <?php elseif ($rank === 2): ?>
                                        <span class="rank-badge rank-silver">♕ 2</span>
                                    <?php elseif ($rank === 3): ?>
                                        <span class="rank-badge rank-bronze">♗ 3</span>
                                    <?php else: ?>
                                        <span class="rank-num"><?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600; font-size: 1.05rem;">
                                    <?= htmlspecialchars($st['team_name']) ?>
                                </td>
                                <td style="text-align: center;"><?= (int)$st['played'] ?></td>
                                <td style="text-align: center; font-weight: 600; color: var(--ink);"><?= (int)$st['won'] ?></td>
                                <td style="text-align: center;"><?= (int)$st['drawn'] ?></td>
                                <td style="text-align: center; color: var(--red);"><?= (int)$st['lost'] ?></td>
                                <td style="text-align: center; font-weight: 600;"><?= number_format((float)$st['game_points'], 1) ?></td>
                                <td style="text-align: center;">
                                    <span class="mp-badge"><?= (int)$st['match_points'] ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($st['standing_notes'])): ?>
                                        <span class="note-pill"><?= htmlspecialchars($st['standing_notes']) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--rule); font-size: 0.85rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size: 0.85rem; color: var(--ink-light); margin-top: 0.75rem;">
            * P: Played, W: Won, D: Drawn, L: Lost, GP: Game Points (total individual board points), MP: Match Points (Win = 2 pts, Draw = 1 pt, Loss = 0 pts, Loss with 0 GP = -1 pt).
        </p>
    </section>
