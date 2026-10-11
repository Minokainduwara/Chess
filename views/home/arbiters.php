<?php
/**
 * Tournament Arbiters Public Section
 * FOT Knights Arena — Chess Tournament
 */
$all_arbiters = $all_arbiters ?? [];
?>
    <div class="page-rail"><hr class="section-rule"></div>

    <!-- ═══ TOURNAMENT ARBITERS & OFFICIALS ═══ -->
    <section class="details page-rail" id="arbiters" aria-labelledby="arbiters-heading" style="padding-top: 1.5rem; padding-bottom: 2rem;">
        <div style="display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; margin-bottom: 1.5rem; gap: 0.5rem;">
            <div>
                <h2 class="details-heading" id="arbiters-heading" style="margin-bottom: 0.35rem;">
                    <span aria-hidden="true">⚖️</span> Tournament Arbiters & Officials
                </h2>
                <p style="font-size: 0.95rem; color: var(--ink-muted); margin: 0;">
                    Adjudication, tournament regulations, and fair-play oversight panel for FOT Knights Arena.
                </p>
            </div>
            <span style="font-family: var(--font-display); font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--red); font-weight: 700;">
                Official Panel
            </span>
        </div>

        <?php if (empty($all_arbiters)): ?>
            <div style="background: var(--cream-card); border: 1px dashed var(--rule); padding: 2rem; text-align: center; border-radius: 4px;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.7;">⚖️</div>
                <div style="font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: var(--ink);">
                    Panel Announcement Pending
                </div>
                <p style="font-size: 0.9rem; color: var(--ink-muted); max-width: 500px; margin: 0.5rem auto 0;">
                    The Chief Arbiter and officiating panel appointed for the tournament will be published here ahead of Round 1.
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <?php foreach ($all_arbiters as $arb): 
                    $arb_role = $arb['role'] ?? 'Arbiter';
                    $is_chief = (stripos($arb_role, 'chief') !== false);
                ?>
                    <div class="gtouch-swipe" style="background: #FFFFFF; border: 1px solid var(--rule); border-top: 4px solid <?= $is_chief ? 'var(--red)' : 'var(--ink)' ?>; padding: 1.5rem; border-radius: 2px; box-shadow: 2px 2px 0 rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <span style="font-family: var(--font-display); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.65rem; border-radius: 2px; background: <?= $is_chief ? '#FFEBEE' : 'var(--cream-card)' ?>; color: <?= $is_chief ? 'var(--red)' : 'var(--ink)' ?>; border: 1px solid <?= $is_chief ? '#FFCDD2' : 'var(--rule)' ?>;">
                                    <?= htmlspecialchars($arb_role) ?>
                                </span>
                                <?php if (!empty($arb['fide_id'])): ?>
                                    <span style="font-family: monospace; font-size: 0.75rem; background: var(--cream); border: 1px solid var(--rule); padding: 0.2rem 0.5rem; border-radius: 2px; color: var(--ink-muted);" title="FIDE ID / License / Title">
                                        <?= htmlspecialchars($arb['fide_id']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--ink); line-height: 1.3; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($arb['name']) ?>
                            </div>
                        </div>

                        <?php if (!empty($arb['contact'])): ?>
                            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--rule-light); font-size: 0.85rem; color: var(--ink-muted); display: flex; align-items: center; gap: 0.5rem;">
                                <span aria-hidden="true" style="font-size: 0.95rem;">📞</span>
                                <span><?= htmlspecialchars($arb['contact']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
