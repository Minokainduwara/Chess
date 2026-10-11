<?php
/**
 * Tournament Rules Section
 * FOT Knights Arena — Chess Tournament
 */
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ RULES ═══ -->
    <section class="rules page-rail" id="rules" aria-labelledby="rules-heading">
        <h2 class="rules-heading" id="rules-heading">
            <span aria-hidden="true">±</span> Tournament Rules
        </h2>
        <div class="rules-grid">
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">1.</span>
                <span class="rule-text">Tournament Format: 6-team Round-Robin. Every team plays exactly one match against every other team over 5 rounds.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">2.</span>
                <span class="rule-text">Match Composition: Each match consists of exactly 4 boards. Teams with 5 or 6 members must submit drop boards before the round deadline, or reserve players will be dropped automatically.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">3.</span>
                <?php
                    $mp_win = $mp_win ?? 2;
                    $mp_draw = $mp_draw ?? 1;
                    $mp_loss = $mp_loss ?? 0;
                    $mp_loss_zero_gp = $mp_loss_zero_gp ?? -1;
                    $penalty_text = ($mp_loss_zero_gp < $mp_loss) ? ", however, a team losing all 4 boards (0 Game Points) will receive {$mp_loss_zero_gp} MP" : "";
                ?>
                <span class="rule-text">Scoring System: A match win earns <?= $mp_win ?> Match Points (MP), a draw earns <?= $mp_draw ?> MP. A match loss earns <?= $mp_loss ?> MP<?= $penalty_text ?>.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">4.</span>
                <span class="rule-text">Tiebreaks: In the event of a tie in Match Points, standings are determined by Game Points, then Direct Encounter, and finally Sonneborn-Berger.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">5.</span>
                <span class="rule-text">Time control: 25 minutes + 5 seconds increment per move. Rapid format throughout.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">6.</span>
                <span class="rule-text">FIDE Laws of Chess apply to all games. Touch-move rule is strictly enforced, and electronic devices are prohibited in the playing area.</span>
            </div>
        </div>
    </section>
