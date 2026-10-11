<?php
/**
 * Scoreboard & Countdown Manager View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<div class="team-card" id="scoreboard-manager" style="margin-bottom: 2rem; border-top: 3px solid var(--red);">
    <div class="team-card-header" style="background: var(--cream);">
        <div>
            <span style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--ink);">
                🏆 Live Scoreboard & Standings Manager
            </span>
            <div style="font-size: 0.85rem; color: var(--ink-muted); margin-top: 0.25rem;">
                Control public visibility on the homepage and update match scores &amp; ranks
            </div>
        </div>
    </div>

    <form method="POST" action="admin.php" style="padding: 1.5rem;">
        <input type="hidden" name="update_standings_action" value="1">
        <input type="hidden" id="scoreboard_visible_input" name="scoreboard_visible" value="<?= $scoreboard_visible ? '1' : '0' ?>">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
            <!-- Instant Visibility Toggle Card -->
            <div class="publish-control-box">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                    <div>
                        <span style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--ink);">
                            Homepage Scoreboard Visibility
                        </span>
                        <span id="scoreboardLiveBadge" class="badge <?= $scoreboard_visible ? 'badge-green' : 'badge-gold' ?>" style="margin-left: 0.4rem; vertical-align: middle;">
                            <?= $scoreboard_visible ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE' ?>
                        </span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem;">
                    <button type="button" id="instantToggleBtn" class="btn-toggle-scoreboard <?= $scoreboard_visible ? 'btn-toggle-hide' : 'btn-toggle-publish' ?>">
                        <span id="instantToggleBtnIcon" aria-hidden="true"><?= $scoreboard_visible ? '✕' : '✓' ?></span>
                        <span id="instantToggleBtnText"><?= $scoreboard_visible ? 'Hide Scoreboard from Homepage' : 'Publish Scoreboard on Homepage' ?></span>
                    </button>

                    <label class="switch-toggle-wrap" title="Toggle scoreboard visibility instantly">
                        <span class="switch-control">
                            <input type="checkbox" id="scoreboard_toggle_switch" <?= $scoreboard_visible ? 'checked' : '' ?>>
                            <span class="switch-slider"></span>
                        </span>
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--ink);">Instant Switch</span>
                    </label>
                </div>

                <div id="instantFeedback" style="display: none;"></div>

                <div style="font-size: 0.85rem; color: var(--ink-muted); line-height: 1.4;">
                    ⚡ <strong>Instant action:</strong> Clicking the button or flipping the switch immediately shows or hides the scoreboard on the homepage without needing to click "Save Standings".
                </div>
            </div>

            <!-- Scoreboard Notice / Banner -->
            <div style="background: var(--cream); border: 1px solid var(--rule-light); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <label class="form-label" for="scoreboard_status" style="font-size: 0.85rem;">Scoreboard Status / Notice</label>
                    <input type="text" id="scoreboard_status" name="scoreboard_status" class="form-control" value="<?= htmlspecialchars($scoreboard_status) ?>" placeholder="e.g. Standings after Round 2">
                    <span style="font-size: 0.85rem; color: var(--ink-muted); display: block; margin-top: 0.35rem;">
                        Public badge displayed directly above the standings on the homepage. Updated when saving standings below.
                    </span>
                </div>
                <div style="margin-top: 0.75rem; text-align: right;">
                    <a href="index.php#standings" target="_blank" class="btn-link" style="font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                        Open Homepage Standings ↗
                    </a>
                </div>
            </div>
        </div>

        <!-- ═══ COUNTDOWN CONTROLS ═══ -->
        <div class="publish-control-box" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                <div>
                    <span style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--ink);">
                        ⏱ Homepage Countdown Timer
                    </span>
                    <span id="countdownLiveBadge" class="badge <?= $countdown_enabled ? 'badge-green' : 'badge-gold' ?>" style="margin-left: 0.4rem; vertical-align: middle;">
                        <?= $countdown_enabled ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE' ?>
                    </span>
                </div>
                <a href="index.php#countdown" target="_blank" class="btn-link" style="font-size: 0.85rem;">View on Homepage ↗</a>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem;">
                <button type="button" id="instantCountdownToggleBtn" class="btn-toggle-scoreboard <?= $countdown_enabled ? 'btn-toggle-hide' : 'btn-toggle-publish' ?>">
                    <span id="instantCountdownToggleBtnIcon" aria-hidden="true"><?= $countdown_enabled ? '✕' : '✓' ?></span>
                    <span id="instantCountdownToggleBtnText"><?= $countdown_enabled ? 'Hide Countdown from Homepage' : 'Publish Countdown on Homepage' ?></span>
                </button>

                <label class="switch-toggle-wrap" title="Toggle countdown visibility instantly">
                    <span class="switch-control">
                        <input type="checkbox" id="countdown_toggle_switch" <?= $countdown_enabled ? 'checked' : '' ?>>
                        <span class="switch-slider"></span>
                    </span>
                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--ink);">Instant Switch</span>
                </label>
            </div>

            <div id="countdownInstantFeedback" style="display: none;"></div>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; margin-top: 0.75rem;">
                <div>
                    <label class="form-label" for="countdown_target" style="font-size: 0.85rem;">Tournament Start (Date &amp; Time)</label>
                    <input type="datetime-local" id="countdown_target" name="countdown_target" class="form-control"
                           value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($countdown_target))) ?>"
                           style="max-width: 280px;">
                    <span style="font-size: 0.85rem; color: var(--ink-muted); display: block; margin-top: 0.35rem;">
                        Countdown target in <strong>venue local time (Asia/Colombo, UTC+05:30)</strong>.<br>
                        Current: <strong><?= htmlspecialchars(date('j M Y, g:i a', strtotime($countdown_target))) ?></strong>
                        · <?= $countdown_enabled ? 'visible' : 'hidden' ?> on homepage.
                        Takes effect when you save below.
                    </span>
                </div>
                <input type="hidden" id="countdown_enabled_input" name="countdown_enabled" value="<?= $countdown_enabled ? '1' : '0' ?>">
            </div>
        </div>

        <!-- ═══ REGISTRATION CONTROLS ═══ -->
        <?php 
            $registration_open = $registration_open ?? true;
            $registration_closed_message = $registration_closed_message ?? 'Official team registration for FOT Knights Arena is now closed.';
        ?>
        <div class="publish-control-box" style="margin-bottom: 1.5rem; border-left: 4px solid <?= $registration_open ? '#2E7D32' : 'var(--red)' ?>;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                <div>
                    <span style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--ink);">
                        📝 Team Registration Status
                    </span>
                    <span id="regLiveBadge" class="badge <?= $registration_open ? 'badge-green' : 'badge-gold' ?>" style="margin-left: 0.4rem; vertical-align: middle; <?= !$registration_open ? 'background:#FFEBEE; color:var(--red); border-color:#EF9A9A;' : '' ?>">
                        <?= $registration_open ? '● REGISTRATION OPEN' : '🔒 REGISTRATION CLOSED' ?>
                    </span>
                </div>
                <a href="index.php#register" target="_blank" class="btn-link" style="font-size: 0.85rem;">View on Homepage ↗</a>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem;">
                <button type="button" id="instantRegToggleBtn" class="btn-toggle-scoreboard <?= $registration_open ? 'btn-toggle-hide' : 'btn-toggle-publish' ?>">
                    <span id="instantRegToggleBtnIcon" aria-hidden="true"><?= $registration_open ? '🔒' : '🔓' ?></span>
                    <span id="instantRegToggleBtnText"><?= $registration_open ? 'Close Registration (Stop Submissions)' : 'Re-open Registration (Accept Submissions)' ?></span>
                </button>

                <label class="switch-toggle-wrap" title="Toggle registration status instantly">
                    <span class="switch-control">
                        <input type="checkbox" id="registration_toggle_switch" <?= $registration_open ? 'checked' : '' ?>>
                        <span class="switch-slider"></span>
                    </span>
                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--ink);">Instant Switch</span>
                </label>
            </div>

            <div id="regInstantFeedback" style="display: none;"></div>

            <div style="margin-top: 0.75rem;">
                <label class="form-label" for="registration_closed_message" style="font-size: 0.85rem;">Registration Closed Notice / Reason</label>
                <input type="text" id="registration_closed_message" name="registration_closed_message" class="form-control"
                       value="<?= htmlspecialchars($registration_closed_message) ?>"
                       placeholder="e.g. Official team registration for FOT Knights Arena is now closed.">
                <span style="font-size: 0.85rem; color: var(--ink-muted); display: block; margin-top: 0.35rem;">
                    Displayed prominently on the homepage when registration is closed. Updated when saving standings below.
                </span>
            </div>
            <input type="hidden" id="registration_open_input" name="registration_open" value="<?= $registration_open ? '1' : '0' ?>">
        </div>

        <!-- ═══ MATCH POINT (MP) SCORING SYSTEM CONFIGURATION ═══ -->
        <?php
            $mp_win = $mp_win ?? 2;
            $mp_draw = $mp_draw ?? 1;
            $mp_loss = $mp_loss ?? 0;
            $mp_loss_zero_gp = $mp_loss_zero_gp ?? -1;
        ?>
        <div class="publish-control-box" id="matchpoints-manager" style="margin-bottom: 2rem; border-top: 3px solid var(--ink); background: #FCFBF7;">
            <div style="display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.5rem;">
                <div>
                    <span style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--ink);">
                        ⚖️ Match Point (MP) Scoring System Rules
                    </span>
                    <div style="font-size: 0.85rem; color: var(--ink-muted); margin-top: 0.2rem;">
                        Configure how match outcomes are awarded. Updating this setting automatically recalculates all completed matches and standings.
                    </div>
                </div>
                <div>
                    <span class="badge" style="background: var(--cream-dark); color: var(--ink); font-size: 0.85rem; padding: 0.3rem 0.7rem;">
                        Active: Win: <?= $mp_win ?> MP · Draw: <?= $mp_draw ?> MP · Loss: <?= $mp_loss ?> MP · 0-GP: <?= $mp_loss_zero_gp ?> MP
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 0.75rem; align-items: flex-end;">
                <div>
                    <label class="form-label" for="mp_preset_select" style="font-size: 0.85rem;">Preset Scheme</label>
                    <select id="mp_preset_select" class="form-control" style="background: #FFF;" onchange="applyMpPreset(this.value)">
                        <option value="">— Select a Standard Scheme —</option>
                        <option value="fot" <?= ($mp_win == 2 && $mp_draw == 1 && $mp_loss == 0 && $mp_loss_zero_gp == -1) ? 'selected' : '' ?>>FOT Arena Penalty (2-1-0 / -1 for 0-GP)</option>
                        <option value="fide" <?= ($mp_win == 2 && $mp_draw == 1 && $mp_loss == 0 && $mp_loss_zero_gp == 0) ? 'selected' : '' ?>>Standard FIDE / Olympiad (2-1-0-0)</option>
                        <option value="three_point" <?= ($mp_win == 3 && $mp_draw == 1 && $mp_loss == 0 && $mp_loss_zero_gp == 0) ? 'selected' : '' ?>>3-Point Win System (3-1-0-0)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="input_mp_win" style="font-size: 0.85rem;">Match Win (MP) *</label>
                    <input type="number" id="input_mp_win" name="mp_win" class="form-control" value="<?= (int)$mp_win ?>" required>
                </div>
                <div>
                    <label class="form-label" for="input_mp_draw" style="font-size: 0.85rem;">Match Draw (MP) *</label>
                    <input type="number" id="input_mp_draw" name="mp_draw" class="form-control" value="<?= (int)$mp_draw ?>" required>
                </div>
                <div>
                    <label class="form-label" for="input_mp_loss" style="font-size: 0.85rem;">Normal Loss (MP) *</label>
                    <input type="number" id="input_mp_loss" name="mp_loss" class="form-control" value="<?= (int)$mp_loss ?>" required>
                </div>
                <div>
                    <label class="form-label" for="input_mp_loss_zero_gp" style="font-size: 0.85rem;" title="Penalty when team loses all 4 boards (0 GP)">0-GP Loss Penalty *</label>
                    <input type="number" id="input_mp_loss_zero_gp" name="mp_loss_zero_gp" class="form-control" value="<?= (int)$mp_loss_zero_gp ?>" required>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <span style="font-size: 0.85rem; color: var(--ink-muted);">
                    ⚡ <strong>Auto-Recalculate:</strong> Clicking update will recalculate all existing match results and rebuild the scoreboard immediately.
                </span>
                <button type="submit" name="update_matchpoint_system" value="1" class="btn-submit" style="width: auto; padding: 0.55rem 1.75rem; font-size: 0.95rem;">
                    💾 Apply Scoring System &amp; Recalculate
                </button>
            </div>
        </div>

        <script>
        function applyMpPreset(preset) {
            var w = document.getElementById('input_mp_win');
            var d = document.getElementById('input_mp_draw');
            var l = document.getElementById('input_mp_loss');
            var z = document.getElementById('input_mp_loss_zero_gp');
            if (!w || !d || !l || !z) return;

            if (preset === 'fot') {
                w.value = 2; d.value = 1; l.value = 0; z.value = -1;
            } else if (preset === 'fide') {
                w.value = 2; d.value = 1; l.value = 0; z.value = 0;
            } else if (preset === 'three_point') {
                w.value = 3; d.value = 1; l.value = 0; z.value = 0;
            }
        }
        </script>

        <?php if (empty($standings_teams)): ?>
            <div class="alert" style="margin: 1rem 0; text-align: center;">
                No teams have registered yet. As soon as squads register, they will appear here to enter match scores.
            </div>
        <?php else: ?>
            <!-- 1. Live Standings Summary View -->
            <div style="background: #FFF; border: 1px solid var(--rule); padding: 1.25rem; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--ink);">
                        📊 Current Standings Breakdown (Live Values)
                    </h3>
                    <span style="font-size: 0.85rem; color: var(--ink-muted);">
                        Ranked by Match Points (MP) → Game Points (GP) → Wins (W)
                    </span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="roster-table" style="width: 100%; border: 1px solid var(--rule-light);">
                        <thead>
                            <tr style="background: #EDE6D2;">
                                <th style="width: 50px;">Rank</th>
                                <th>Team Name</th>
                                <th style="width: 75px; text-align: center;">Played (P)</th>
                                <th style="width: 75px; text-align: center;">Won (W)</th>
                                <th style="width: 75px; text-align: center;">Drawn (D)</th>
                                <th style="width: 75px; text-align: center;">Lost (L)</th>
                                <th style="width: 85px; text-align: center;">Game Pts (GP)</th>
                                <th style="width: 85px; text-align: center;">Match Pts (MP)</th>
                                <th>Status / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings_teams as $idx => $st): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--red); font-size: 1.15rem; text-align: center;">
                                        #<?= $idx + 1 ?>
                                    </td>
                                    <td style="font-weight: 700; font-size: 1.1rem;">
                                        <?= htmlspecialchars($st['team_name']) ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                        <?= (int)$st['played'] ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; font-size: 1.15rem; color: #1E6B23;">
                                        <?= (int)$st['won'] ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                        <?= (int)$st['drawn'] ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; font-size: 1.15rem; color: #922B21;">
                                        <?= (int)$st['lost'] ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                        <?= number_format((float)$st['game_points'], 1) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background: var(--ink); color: #FFF; font-size: 1rem; font-weight: 700; padding: 0.2rem 0.6rem;">
                                            <?= (int)$st['match_points'] ?> MP
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($st['standing_notes'])): ?>
                                            <span class="badge" style="background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; font-weight: 700;">
                                                <?= htmlspecialchars($st['standing_notes']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--rule);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Edit Scores Form -->
            <div style="background: var(--cream); border: 1px solid var(--rule); padding: 1.25rem;">
                <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--ink);">
                    ✏️ Edit Match Scores & Results
                </h3>
                <div style="overflow-x: auto; margin-bottom: 1rem;">
                    <table class="roster-table" style="background: #FFF; border: 1px solid var(--rule);">
                        <thead>
                            <tr style="background: #EDE6D2;">
                                <th style="width: 45px;">#</th>
                                <th>Team Name</th>
                                <th style="width: 75px; text-align: center;">P</th>
                                <th style="width: 75px; text-align: center;">W</th>
                                <th style="width: 75px; text-align: center;">D</th>
                                <th style="width: 75px; text-align: center;">L</th>
                                <th style="width: 85px; text-align: center;">GP (Pts)</th>
                                <th style="width: 85px; text-align: center;">MP (Score)</th>
                                <th>Status / Arbiter Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings_teams as $idx => $st): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--red); font-size: 1.1rem; text-align: center;">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td style="font-weight: 700; font-size: 1.05rem;">
                                        <?= htmlspecialchars($st['team_name']) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][played]" value="<?= (int)$st['played'] ?>" class="score-input score-p" title="Matches Played">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][won]" value="<?= (int)$st['won'] ?>" class="score-input score-w" style="color: #1E6B23;" onchange="autoCalcPoints(this)" title="Matches Won">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][drawn]" value="<?= (int)$st['drawn'] ?>" class="score-input score-d" onchange="autoCalcPoints(this)" title="Matches Drawn">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][lost]" value="<?= (int)$st['lost'] ?>" class="score-input score-l" style="color: #922B21;" title="Matches Lost">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" step="0.5" min="0" max="300" name="scores[<?= $st['id'] ?>][game_points]" value="<?= (float)$st['game_points'] ?>" class="score-input score-gp" style="width: 75px;" title="Game Points">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" min="0" max="100" name="scores[<?= $st['id'] ?>][match_points]" value="<?= (int)$st['match_points'] ?>" class="score-input score-mp" style="width: 75px; background: #FFF8E1;" title="Match Points">
                                    </td>
                                    <td>
                                        <input type="text" name="scores[<?= $st['id'] ?>][standing_notes]" value="<?= htmlspecialchars($st['standing_notes'] ?? '') ?>" placeholder="e.g. Leader, Round 1 Bye" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.95rem;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; flex-wrap: wrap; gap: 1rem;">
                    <span style="font-size: 0.85rem; color: var(--ink-muted);">
                        💡 Round-robin scoring: P = Played, W = Won, D = Drawn, L = Lost, GP = Game Points, MP = Match Points (Win = 2, Draw = 1).
                    </span>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" onclick="calcAllMatchPoints()" class="btn-action" style="font-size: 0.95rem;">
                            ⚡ Auto-Calc MP (W×2 + D×1)
                        </button>
                        <button type="submit" class="btn-submit" style="width: auto; padding: 0.6rem 2rem;">
                            💾 Save & Publish Standings
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>
