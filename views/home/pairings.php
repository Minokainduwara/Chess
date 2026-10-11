<?php
/**
 * Public Pairings & Results Section
 * FOT Knights Arena — Chess Tournament
 */
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ PAIRINGS & RESULTS ═══ -->
    <section class="standings page-rail" id="pairings" aria-labelledby="pairings-heading">
        <h2 class="standings-heading" id="pairings-heading">
            <span aria-hidden="true">⚔️</span> Pairings & Results
        </h2>
        
        <?php if (empty($public_matches)): ?>
            <div class="card" style="text-align: center; padding: 2.5rem 1rem; color: var(--ink-light); margin-bottom: 2rem;">
                <span style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">♟️</span>
                Pairings will be published here once the tournament begins.
            </div>
        <?php else: ?>
            <?php foreach ($public_matches as $r_num => $matches): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="border-bottom: 2px solid var(--checker); padding-bottom: 5px; margin-bottom: 1rem; font-family: var(--font-display);">Round <?= (int)$r_num ?></h3>
                    <div class="table-responsive">
                        <table class="standings-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%; text-align: right;">White (Team A)</th>
                                    <th style="width: 20%; text-align: center;">Result</th>
                                    <th style="width: 40%; text-align: left;">Black (Team B)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matches as $m): ?>
                                    <tr>
                                        <td style="text-align: right; font-weight: 600;"><?= htmlspecialchars($m['ta_name']) ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($m['status'] == 'completed'): ?>
                                                <span class="mp-badge" style="background: var(--ink); color: #fff; display: inline-block; padding: 4px 10px; border-radius: 4px; font-weight: bold; letter-spacing: 1px;">
                                                    <?= htmlspecialchars($m['team_a_gp']) ?> - <?= htmlspecialchars($m['team_b_gp']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: var(--ink-light); font-weight: bold; font-family: var(--font-display); padding: 4px 10px;">- vs -</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: left; font-weight: 600;"><?= htmlspecialchars($m['tb_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
