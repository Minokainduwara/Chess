<?php
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();
$scoreboard_visible = true;
$scoreboard_status = 'Standings updated live after each round';
$standings_teams = [];

// ── Countdown defaults ──
$countdown_enabled = true;
$countdown_target = '2026-10-10 07:00:00';
$countdown_iso = '';
$countdown_label = "Countdown to First Move";

if ($pdo) {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM `tournament_settings`");
    $settings = [];
    while ($s = $stmt_set->fetch()) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
    $scoreboard_visible = ($settings['scoreboard_visible'] ?? '1') === '1';
    $scoreboard_status = $settings['scoreboard_status'] ?? 'Standings updated live after each round';

    $countdown_enabled = ($settings['countdown_enabled'] ?? '1') === '1';
    $countdown_target  = $settings['countdown_target'] ?? '2026-10-10 07:00:00';

    if ($scoreboard_visible) {
        $stmt_st = $pdo->query("
            SELECT id, team_name, played, won, drawn, lost, game_points, match_points, standing_notes
            FROM `teams`
            WHERE `status` = 'approved'
            ORDER BY match_points DESC, game_points DESC, tiebreak_sb DESC, id ASC
        ");
        $standings_teams = $stmt_st->fetchAll();
    }
    
    // Check for active round to override countdown
    $countdown_label = "Countdown to First Move";
    $stmt_round = $pdo->query("SELECT * FROM `rounds` WHERE `status` = 'open' ORDER BY `round_number` ASC LIMIT 1");
    if ($stmt_round) {
        $active_round = $stmt_round->fetch();
        if ($active_round) {
            $countdown_target = $active_round['deadline'] ?? $countdown_target;
            if (!empty($active_round['label'])) {
                $countdown_label = "Countdown to " . $active_round['label'];
            }
        }
    }

    // Fetch all public matches
    $stmt_pub_m = $pdo->query("SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name FROM `matches` m JOIN `teams` t1 ON m.team_a_id = t1.id JOIN `teams` t2 ON m.team_b_id = t2.id WHERE m.status IN ('scheduled', 'completed') ORDER BY m.round_number ASC, m.id ASC");
    $public_matches = [];
    if ($stmt_pub_m) {
        while ($row = $stmt_pub_m->fetch()) {
            $public_matches[$row['round_number']][] = $row;
        }
    }

    // Fetch individual player standings
    $stmt_indiv = $pdo->query("
        SELECT m.id, m.name, t.team_name,
               SUM(CASE
                   WHEN mb.result = '1-0' AND mb.team_a_member_id = m.id THEN 1
                   WHEN mb.result = '0-1' AND mb.team_b_member_id = m.id THEN 1
                   WHEN mb.result = '0.5-0.5' AND (mb.team_a_member_id = m.id OR mb.team_b_member_id = m.id) THEN 0.5
                   ELSE 0
               END) as individual_score
        FROM `members` m
        JOIN `teams` t ON m.team_id = t.id
        LEFT JOIN `match_boards` mb ON (mb.team_a_member_id = m.id OR mb.team_b_member_id = m.id)
        GROUP BY m.id
        ORDER BY individual_score DESC, m.name ASC
    ");
    $individual_standings = [];
    if ($stmt_indiv) {
        $individual_standings = $stmt_indiv->fetchAll();
    }

    // Fetch round schedules
    $stmt_sched = $pdo->query("SELECT * FROM `rounds` ORDER BY `round_number` ASC");
    $schedule_rounds = [];
    if ($stmt_sched) {
        $schedule_rounds = $stmt_sched->fetchAll();
    }
}

/**
 * Render the countdown target as an absolute ISO-8601 instant.
 *
 * The target is stored as Sri Lankan venue-local time (Asia/Colombo, UTC+05:30).
 * Emitting it WITH the offset means every visitor's browser resolves to the same
 * absolute instant, regardless of the visitor's own timezone or the server's
 * (PHP here runs in UTC). Without the offset the same wall-clock string would be
 * interpreted in each visitor's local zone and the countdown would be wrong.
 */
$countdown_iso = '';
if ($countdown_enabled && $countdown_target !== '') {
    try {
        $tz_venue  = new DateTimeZone('Asia/Colombo');
        $dt_target = new DateTime($countdown_target, $tz_venue);
        $countdown_iso = $dt_target->format(DATE_ATOM);   // 2026-10-10T07:00:00+05:30
    } catch (Exception $e) {
        $countdown_iso = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FOT Knights Arena — University of Ruhuna</title>
    <meta name="description" content="The official chess tournament of the Faculty of Technology, University of Ruhuna. FOT Knights Arena — 10 October 2026, 7001 Hall. Register now.">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Particles.js Library for floating chess pieces -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Particles Container -->
    <div id="particles-js"></div>

    <!-- Floating Chess Pieces -->
    <div class="floating-pieces">
        <span class="floating-piece" style="top: 10%; left: 10%; animation-delay: 0s;">♙</span>
        <span class="floating-piece" style="top: 20%; left: 80%; animation-delay: 0.5s;">♟</span>
        <span class="floating-piece" style="top: 60%; left: 15%; animation-delay: 1s;">♖</span>
        <span class="floating-piece" style="top: 70%; left: 75%; animation-delay: 1.5s;">♕</span>
        <span class="floating-piece" style="top: 40%; left: 50%; animation-delay: 2s;">♘</span>
        <span class="floating-piece" style="top: 30%; left: 30%; animation-delay: 2.5s;">♗</span>
    </div>

    <!-- ═══ HEADER ═══ -->
    <header class="site-header" id="siteHeader">
        <div class="page-rail header-inner">
            <a href="#" class="header-brand gtouch-ripple" aria-label="University of Ruhuna, Faculty of Technology">
                <div class="header-brand-text">
                    University of Ruhuna
                    <span>Faculty of Technology</span>
                </div>
            </a>
            <nav class="header-nav" aria-label="Main navigation">
                <a href="#details" class="gtouch-hover">About</a>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="#schedule" class="gtouch-hover">Schedule</a>
                <?php if ($scoreboard_visible): ?>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="#standings" class="gtouch-hover">Standings</a>
                <?php endif; ?>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="#rules" class="gtouch-hover">Rules</a>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="#register" class="gtouch-hover">Register</a>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="captain.php" style="color: var(--blue); font-weight: 600;" class="gtouch-hover">Captain Portal</a>
                <span class="header-nav-sep" aria-hidden="true">·</span>
                <a href="admin.php" style="color: var(--red); font-weight: 600;" class="gtouch-hover">Admin</a>
            </nav>
        </div>
    </header>

    <!-- ═══ HERO ═══ -->
    <section class="hero" aria-labelledby="hero-title">
        <div class="page-rail hero-inner">
            <div class="hero-copy">
                <h1 id="hero-title" class="hero-title">
                    FOT Knights<br>
                    Arena<br>
                    2026
                </h1>
                <?php if ($countdown_iso !== ''): ?>
                <div class="countdown" id="countdown" data-target="<?= htmlspecialchars($countdown_iso, ENT_QUOTES) ?>">
                    <h2 class="countdown-heading">
                        <span aria-hidden="true">♜</span> <?= htmlspecialchars($countdown_label) ?>
                    </h2>
                    <div class="countdown-grid" role="timer" aria-live="off">
                        <div class="countdown-cell"><span class="countdown-num" id="cd-days">--</span><span class="countdown-unit">Days</span></div>
                        <div class="countdown-cell"><span class="countdown-num" id="cd-hours">--</span><span class="countdown-unit">Hours</span></div>
                        <div class="countdown-cell"><span class="countdown-num" id="cd-mins">--</span><span class="countdown-unit">Minutes</span></div>
                        <div class="countdown-cell"><span class="countdown-num" id="cd-secs">--</span><span class="countdown-unit">Seconds</span></div>
                    </div>
                    <p class="countdown-sr" id="cd-sr" aria-live="polite"></p>
                </div>
                <?php endif; ?>
                <p class="hero-subtitle">University of Ruhuna</p>
                <a href="#register" class="hero-cta gtouch-ripple gtouch-long-press">
                    Register Now
                    <span class="hero-cta-arrow" aria-hidden="true">→</span>
                    <span class="hero-cta-notation" aria-hidden="true">1.e4</span>
                </a>
            </div>
            <div class="hero-board">
                <div class="chess-diagram gtouch-hover" role="img" aria-label="Chess diagram showing a Sicilian Defense position">
                    <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                        <!-- Board squares -->
                        <?php
                        $lightSq = '#FFFFFF';
                        $darkSq  = '#D4D4D8';
                        for ($row = 0; $row < 8; $row++) {
                            for ($col = 0; $col < 8; $col++) {
                                $x = $col * 50;
                                $y = $row * 50;
                                $fill = (($row + $col) % 2 === 0) ? $lightSq : $darkSq;
                                echo "<rect x=\"$x\" y=\"$y\" width=\"50\" height=\"50\" fill=\"$fill\"/>\n";
                            }
                        }

                        // Piece positions: Sicilian Defense after 1.e4 c5
                        // Black pieces (top, rows 0-1 from top)
                        $pieces = [
                            // Row 0 (rank 8): Black back rank
                            ['♜', 0, 0], ['♞', 1, 0], ['♝', 2, 0], ['♛', 3, 0],
                            ['♚', 4, 0], ['♝', 5, 0], ['♞', 6, 0], ['♜', 7, 0],
                            // Row 1 (rank 7): Black pawns (c-pawn moved to c5)
                            ['♟', 0, 1], ['♟', 1, 1], ['♟', 3, 1], ['♟', 4, 1],
                            ['♟', 5, 1], ['♟', 6, 1], ['♟', 7, 1],
                            // c5 pawn
                            ['♟', 2, 3],
                            // Row 6 (rank 2): White pawns (e-pawn moved to e4)
                            ['♙', 0, 6], ['♙', 1, 6], ['♙', 2, 6], ['♙', 3, 6],
                            ['♙', 5, 6], ['♙', 6, 6], ['♙', 7, 6],
                            // e4 pawn
                            ['♙', 4, 4],
                            // Row 7 (rank 1): White back rank
                            ['♖', 0, 7], ['♘', 1, 7], ['♗', 2, 7], ['♕', 3, 7],
                            ['♔', 4, 7], ['♗', 5, 7], ['♘', 6, 7], ['♖', 7, 7],
                        ];

                        foreach ($pieces as [$piece, $col, $row]) {
                            $x = $col * 50 + 25;
                            $y = $row * 50 + 35;
                            $isBlack = in_array($piece, ['♜','♞','♝','♛','♚','♟']);
                            $color = $isBlack ? '#0A0A0A' : '#FFFFFF';
                            // White pieces get a black stroke so they read on light squares
                            $stroke = $isBlack ? 'stroke="none"' : 'stroke="#0A0A0A" stroke-width="1.2"';
                            echo "<text x=\"$x\" y=\"$y\" text-anchor=\"middle\" font-size=\"36\" fill=\"$color\" $stroke font-family=\"serif\" class=\"chess-piece-drag\">$piece</text>\n";
                        }
                        ?>
                        <!-- File labels -->
                        <?php
                        $files = ['a','b','c','d','e','f','g','h'];
                        foreach ($files as $i => $f) {
                            $x = $i * 50 + 25;
                            echo "<text x=\"$x\" y=\"396\" text-anchor=\"middle\" font-size=\"8\" fill=\"#52525B\" font-family=\"sans-serif\" letter-spacing=\"0.05em\">$f</text>\n";
                        }
                        for ($r = 8; $r >= 1; $r--) {
                            $y = (8 - $r) * 50 + 30;
                            echo "<text x=\"4\" y=\"$y\" font-size=\"8\" fill=\"#52525B\" font-family=\"sans-serif\">$r</text>\n";
                        }
                        ?>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <div class="page-rail"><hr class="section-rule"></div>

    <!-- ═══ EVENT DETAILS (Annotation columns) ═══ -->
    <section class="details page-rail" id="details" aria-labelledby="details-heading">
        <h2 class="details-heading" id="details-heading">
            <span aria-hidden="true">□</span> Event Details
        </h2>
        <div class="details-grid">
            <div class="detail-item gtouch-swipe">
                <span class="detail-symbol" aria-hidden="true">♔</span>
                <div>
                    <div class="detail-label">Date</div>
                    <div class="detail-value">10th October 2026, 7:00 AM</div>
                </div>
            </div>
            <div class="detail-item gtouch-swipe">
                <span class="detail-symbol" aria-hidden="true">♕</span>
                <div>
                    <div class="detail-label">Venue</div>
                    <div class="detail-value">7001 Hall,<br>Faculty of Technology, University of Ruhuna</div>
                </div>
            </div>
            <div class="detail-item gtouch-swipe">
                <span class="detail-symbol" aria-hidden="true">±</span>
                <div>
                    <div class="detail-label">Format</div>
                    <div class="detail-value">Round-Robin (Every team plays against all other teams)<br>Time control: 25+5 (rapid)</div>
                </div>
            </div>
            <div class="detail-item gtouch-swipe">
                <span class="detail-symbol" aria-hidden="true">⩲</span>
                <div>
                    <div class="detail-label">Eligibility</div>
                    <div class="detail-value">All registered students of the<br>Faculty of Technology, University of Ruhuna</div>
                </div>
            </div>
        </div>
    </section>

    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ SCHEDULE (Variation notation) ═══ -->
    <section class="schedule page-rail" id="schedule" aria-labelledby="schedule-heading">
        <h2 class="schedule-heading" id="schedule-heading">
            <span aria-hidden="true">⌖</span> Round Schedule
        </h2>
        <ol class="round-list">
            <?php if (!empty($schedule_rounds)): ?>
                <?php foreach ($schedule_rounds as $r): ?>
                    <li class="round-item gtouch-tap">
                        <span class="round-number"><?= $r['round_number'] ?>.</span>
                        <span class="round-date"><?= htmlspecialchars($r['schedule_date'] ?? '10 Oct 2026') ?></span>
                        <span class="round-time"><?= htmlspecialchars($r['schedule_time'] ?? '09:00 – 10:30') ?></span>
                        <?php
                        $status_class = "round-status";
                        if ($r['status'] === 'open') $status_class .= " round-status--active";
                        ?>
                        <span class="<?= $status_class ?>"><?= htmlspecialchars($r['schedule_status'] ?? 'Scheduled') ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="round-item gtouch-tap">
                    <span class="round-number">-</span>
                    <span class="round-date">TBD</span>
                    <span class="round-time">TBD</span>
                    <span class="round-status">Schedule Pending</span>
                </li>
            <?php endif; ?>
        </ol>
    </section>

    <?php if ($scoreboard_visible): ?>
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
    <?php endif; ?>

    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ INDIVIDUAL STANDINGS ═══ -->
    <section class="standings page-rail" id="individual-standings" aria-labelledby="indiv-heading">
        <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
            <h2 class="standings-heading" id="indiv-heading" style="margin-bottom: 0;">
                <span aria-hidden="true">🎖️</span> Individual Player Standings
            </h2>
        </div>
        <div class="standings-table-wrap gtouch-swipe">
            <table class="standings-table" id="indivTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">Rank</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th style="text-align: center; width: 80px;">Score</th>
                    </tr>
                </thead>
                <tbody id="indivBody">
                    <!-- populated by js -->
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <button id="prevIndivBtn" class="btn-primary" style="padding: 5px 15px; border-radius: 4px; font-weight: bold; background: var(--ink); color: #fff; cursor: pointer; border: none;" disabled>&larr; Prev</button>
            <span id="indivPageInfo" style="font-weight: 600; color: var(--ink-light);">Page 1</span>
            <button id="nextIndivBtn" class="btn-primary" style="padding: 5px 15px; border-radius: 4px; font-weight: bold; background: var(--ink); color: #fff; cursor: pointer; border: none;">Next &rarr;</button>
        </div>
        <script>
            const indivData = <?php echo json_encode($individual_standings); ?>;
            let indivPage = 0;
            const indivPerPage = 10;
            
            function renderIndivTable() {
                const tbody = document.getElementById('indivBody');
                tbody.innerHTML = '';
                
                if (!indivData || indivData.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2.5rem 1rem; color: var(--ink-light);"><span style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">♟️</span>Official player rankings will update here.</td></tr>';
                    document.getElementById('prevIndivBtn').style.display = 'none';
                    document.getElementById('nextIndivBtn').style.display = 'none';
                    document.getElementById('indivPageInfo').style.display = 'none';
                    return;
                }
                
                const start = indivPage * indivPerPage;
                const end = Math.min(start + indivPerPage, indivData.length);
                
                for (let i = start; i < end; i++) {
                    const row = indivData[i];
                    const rank = i + 1;
                    let rankHtml = `<span class="rank-num">${rank}</span>`;
                    if (rank === 1) rankHtml = `<span class="rank-badge rank-gold">♔ 1</span>`;
                    else if (rank === 2) rankHtml = `<span class="rank-badge rank-silver">♕ 2</span>`;
                    else if (rank === 3) rankHtml = `<span class="rank-badge rank-bronze">♗ 3</span>`;
                    
                    const tr = document.createElement('tr');
                    if (rank <= 3) tr.className = `rank-podium rank-${rank} gtouch-tap`;
                    else tr.className = 'gtouch-tap';
                    
                    tr.innerHTML = `
                        <td>${rankHtml}</td>
                        <td style="font-weight: 600; font-size: 1.05rem;">${row.name}</td>
                        <td style="color: var(--ink-light); font-weight: 500;">${row.team_name}</td>
                        <td style="text-align: center; font-weight: 600; color: var(--ink);">${parseFloat(row.individual_score || 0).toFixed(1)}</td>
                    `;
                    tbody.appendChild(tr);
                }
                
                const prevBtn = document.getElementById('prevIndivBtn');
                const nextBtn = document.getElementById('nextIndivBtn');
                
                prevBtn.disabled = indivPage === 0;
                prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '1';
                prevBtn.style.cursor = prevBtn.disabled ? 'not-allowed' : 'pointer';
                
                nextBtn.disabled = end >= indivData.length;
                nextBtn.style.opacity = nextBtn.disabled ? '0.5' : '1';
                nextBtn.style.cursor = nextBtn.disabled ? 'not-allowed' : 'pointer';
                
                document.getElementById('indivPageInfo').textContent = `Showing ${start + 1} - ${end} of ${indivData.length}`;
            }
            
            document.getElementById('prevIndivBtn').addEventListener('click', () => {
                if (indivPage > 0) { indivPage--; renderIndivTable(); }
            });
            document.getElementById('nextIndivBtn').addEventListener('click', () => {
                if ((indivPage + 1) * indivPerPage < indivData.length) { indivPage++; renderIndivTable(); }
            });
            
            renderIndivTable();
        </script>
    </section>

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
                    <h3 style="border-bottom: 2px solid var(--checker); padding-bottom: 5px; margin-bottom: 1rem; font-family: var(--font-display);">Round <?= $r_num ?></h3>
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
                                                    <?= $m['team_a_gp'] ?> - <?= $m['team_b_gp'] ?>
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
                <span class="rule-text">Scoring System: A match win earns 2 Match Points (MP), a draw earns 1 MP. A match loss earns 0 MP, however, a team losing all 4 boards (0 Game Points) will receive -1 MP.</span>
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

    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ REGISTRATION SECTION ═══ -->
    <section class="registration page-rail" id="register" aria-labelledby="reg-heading">
        <h2 class="reg-heading" id="reg-heading">
            <span aria-hidden="true">♔</span> Official Team Registration
        </h2>
        <p class="reg-lead">
            Register your squad for FOT Knights Arena 2026. Each team requires a minimum of 4 and a maximum of 6 registered students. Per tournament rules, every team <strong>must include at least 1 female player</strong> and <strong>represent at least two batches</strong>. All registrations are reviewed by the tournament organizer — your team will appear in the public Arena Standings only after <strong>admin approval</strong>.
        </p>

        <!-- Live Tournament Rules Compliance Bar -->
        <div class="compliance-bar gtouch-swipe" id="complianceBar" aria-live="polite">
            <div class="compliance-title">📋 Live Eligibility Status</div>
            <div class="compliance-pill gtouch-hover" id="pill-members">
                <span class="pill-dot"></span>
                <span class="pill-text">Roster: 4 / 6 Members</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-females">
                <span class="pill-dot"></span>
                <span class="pill-text">Female Quota: 0 / 1 Girl (Required)</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-batches">
                <span class="pill-dot"></span>
                <span class="pill-text">Batches: 0 / 2 (Required)</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-format">
                <span class="pill-dot"></span>
                <span class="pill-text">Reg No Format: TG/YYYY/XXXX</span>
            </div>
        </div>

        <!-- Registration Form -->
        <form id="teamRegForm" novalidate>
            <!-- 1. Team & Contact Info -->
            <div class="form-block">
                <h3 class="form-block-title">
                    <span aria-hidden="true">♜</span> Team Identity & Contact
                </h3>
                <div class="form-grid-3">
                    <div class="input-group">
                        <label class="input-label" for="team_name">Team Name <span class="req">*</span></label>
                        <input type="text" id="team_name" name="team_name" class="input-field" placeholder="e.g. FOT Gambit Kings" required>
                        <span class="input-hint">Unique name representing your squad</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="contact_phone">Captain's Phone <span class="req">*</span></label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="input-field" placeholder="e.g. 0771234567" required>
                        <span class="input-hint">For match pairings & captain briefings</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="captain_password">Captain Password <span class="req">*</span></label>
                        <input type="password" id="captain_password" name="captain_password" class="input-field" placeholder="Enter password" required>
                        <span class="input-hint">For logging into the Captain Portal</span>
                    </div>
                </div>
            </div>

            <!-- 2. Team Members Roster -->
            <div class="form-block">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h3 class="form-block-title" style="margin-bottom: 0;">
                        <span aria-hidden="true">♟</span> Squad Roster (Board 1 to 6)
                    </h3>
                    <span style="font-family: var(--font-display); font-size: 0.95rem; color: var(--ink-light);">
                        * Required: ≥ 1 Girl & ≥ 2 Batches
                    </span>
                </div>

                <div class="members-grid" id="membersGrid">
                    <!-- Cards will be initialized dynamically by JavaScript -->
                </div>

                <div class="form-actions-bar">
                    <button type="button" id="btnAddMember" class="btn-add-member gtouch-tap">
                        <span style="font-size: 1.25rem; line-height: 1;">+</span> Add Reserve Player (Max 6)
                    </button>
                    <button type="submit" id="btnSubmitReg" class="btn-submit-reg gtouch-ripple gtouch-long-press">
                        Submit Team Registration
                        <span aria-hidden="true">→</span>
                        <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>
                    </button>
                </div>
            </div>

            <!-- Feedback & Receipt Box -->
            <div id="formFeedback" class="form-feedback" aria-live="polite"></div>
        </form>
    </section>

    <div class="page-rail"><hr class="section-rule"></div>

    <!-- ═══ FOOTER ═══ -->
    <footer class="site-footer">
        <div class="page-rail footer-inner">
            <div class="footer-info">
                <strong>Contact the Organizers</strong>
                Chess Society, Faculty of Technology<br>
                University of Ruhuna, Kamburupitiya
            </div>
            <div style="text-align: right;">
                <a href="#register" class="footer-cta gtouch-ripple">
                    Register Now <span aria-hidden="true">→</span>
                </a>
                <div class="footer-colophon" style="margin-top: 1.25rem;">
                    © 2026 Faculty of Technology, University of Ruhuna · <a href="captain.php" style="color: var(--ink-light); text-decoration: underline;" class="gtouch-hover">Captain Portal</a> · <a href="admin.php" style="color: var(--ink-light); text-decoration: underline;" class="gtouch-hover">Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ═══ Scripts: Animation & Dynamic Registration ═══ -->
    <script>
    (function() {
        // ── 1. Scroll Reveal Animations ──
        document.querySelectorAll('.details, .schedule, .rules, .registration').forEach(function(el) {
            el.classList.add('reveal');
        });

        var heroBoard = document.querySelector('.hero-board');
        if (heroBoard) {
            requestAnimationFrame(function() {
                heroBoard.classList.add('is-visible');
            });
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -40px 0px'
            });

            document.querySelectorAll('.reveal').forEach(function(el) {
                observer.observe(el);
            });
        } else {
            document.querySelectorAll('.reveal').forEach(function(el) {
                el.classList.add('is-visible');
            });
        }

        // ── 2. Dynamic Team Registration System ──
        var membersGrid = document.getElementById('membersGrid');
        var btnAddMember = document.getElementById('btnAddMember');
        var btnSubmitReg = document.getElementById('btnSubmitReg');
        var regForm = document.getElementById('teamRegForm');
        var formFeedback = document.getElementById('formFeedback');

        var pillMembers = document.getElementById('pill-members');
        var pillFemales = document.getElementById('pill-females');
        var pillBatches = document.getElementById('pill-batches');
        var pillFormat = document.getElementById('pill-format');

        // Initial member count (minimum 4)
        var currentMemberCount = 4;
        var regPattern = /^TG\/(\d{4})\/(\d{4})$/i;

        // Render member card markup
        function createMemberCardHtml(index) {
            var isCaptain = (index === 1);
            var title = isCaptain ? 'Player #1 — Board 1' : 'Player #' + index + ' — Board ' + index;
            var captainBadge = isCaptain ? '<span class="member-badge-captain">★ Captain</span>' : '';
            var removeBtn = (!isCaptain && index > 4) ? '<button type="button" class="btn-remove-member" onclick="window.removeMember(' + index + ')">✕ Remove</button>' : '';

            return `
            <div class="member-card ${isCaptain ? 'is-captain' : ''}" id="memberCard_${index}">
                <div class="member-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="member-slot-title">${title}</span>
                        ${captainBadge}
                    </div>
                    ${removeBtn}
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="input-group">
                        <label class="input-label" for="member_name_${index}">Full Name <span class="req">*</span></label>
                        <input type="text" id="member_name_${index}" name="members[${index - 1}][name]" class="input-field member-input-name" placeholder="e.g. Kasun Perera" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="member_reg_${index}">
                            Registration Number <span class="req">*</span>
                            <span class="batch-preview" id="batchPreview_${index}" style="display: none;"></span>
                        </label>
                        <input type="text" id="member_reg_${index}" name="members[${index - 1}][reg_number]" class="input-field member-input-reg" placeholder="TG/2023/0001" required maxlength="12" style="text-transform: uppercase;">
                        <span class="input-hint">Format: TG/YYYY/XXXX (e.g. TG/2022/1111 or TG/2023/0001)</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Gender <span class="req">*</span></label>
                        <div class="gender-options">
                            <label class="gender-label">
                                <input type="radio" name="members[${index - 1}][gender]" value="Male" class="member-gender" required> ♂ Male
                            </label>
                            <label class="gender-label">
                                <input type="radio" name="members[${index - 1}][gender]" value="Female" class="member-gender" required> ♀ Female (Girl)
                            </label>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // Render all initial cards
        function renderInitialMembers() {
            var html = '';
            for (var i = 1; i <= currentMemberCount; i++) {
                html += createMemberCardHtml(i);
            }
            membersGrid.innerHTML = html;
            bindInputEvents();
            updateLiveChecklist();
        }

        // Add Member handler (up to 6)
        btnAddMember.addEventListener('click', function() {
            if (currentMemberCount < 6) {
                currentMemberCount++;
                var temp = document.createElement('div');
                temp.innerHTML = createMemberCardHtml(currentMemberCount);
                membersGrid.appendChild(temp.firstElementChild);
                bindInputEvents();
                updateLiveChecklist();

                if (currentMemberCount >= 6) {
                    btnAddMember.disabled = true;
                    btnAddMember.innerText = 'Maximum 6 Members Reached';
                }
            }
        });

        // Global remove member handler
        window.removeMember = function(index) {
            var card = document.getElementById('memberCard_' + index);
            if (card) {
                card.remove();
                currentMemberCount--;
                // Re-index remaining cards if needed
                reIndexMemberCards();
                btnAddMember.disabled = false;
                btnAddMember.innerHTML = '<span style="font-size: 1.25rem; line-height: 1;">+</span> Add Reserve Player (Max 6)';
                updateLiveChecklist();
            }
        };

        function reIndexMemberCards() {
            var cards = membersGrid.querySelectorAll('.member-card');
            cards.forEach(function(card, idx) {
                var num = idx + 1;
                card.id = 'memberCard_' + num;
                var isCap = (num === 1);
                var titleEl = card.querySelector('.member-slot-title');
                if (titleEl) {
                    titleEl.innerText = isCap ? 'Player #1 — Board 1' : 'Player #' + num + ' — Board ' + num;
                }
                var removeBtn = card.querySelector('.btn-remove-member');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', 'window.removeMember(' + num + ')');
                }
                var nameInput = card.querySelector('.member-input-name');
                if (nameInput) {
                    nameInput.name = `members[${idx}][name]`;
                    nameInput.id = `member_name_${num}`;
                }
                var regInput = card.querySelector('.member-input-reg');
                if (regInput) {
                    regInput.name = `members[${idx}][reg_number]`;
                    regInput.id = `member_reg_${num}`;
                }
                var genderRadios = card.querySelectorAll('.member-gender');
                genderRadios.forEach(function(radio) {
                    radio.name = `members[${idx}][gender]`;
                });
            });
        }

        // Bind input events to update checklist live
        function bindInputEvents() {
            membersGrid.querySelectorAll('.member-input-reg').forEach(function(input) {
                input.removeEventListener('input', onRegInput);
                input.addEventListener('input', onRegInput);
            });
            membersGrid.querySelectorAll('.member-gender').forEach(function(input) {
                input.removeEventListener('change', updateLiveChecklist);
                input.addEventListener('change', updateLiveChecklist);
            });
        }

        function onRegInput(e) {
            e.target.value = e.target.value.toUpperCase();
            var val = e.target.value.trim();
            var card = e.target.closest('.member-card');
            var preview = card.querySelector('.batch-preview');

            var match = val.match(regPattern);
            if (match && preview) {
                preview.style.display = 'inline-block';
                preview.innerText = 'Batch ' + match[1];
            } else if (preview) {
                preview.style.display = 'none';
            }
            updateLiveChecklist();
        }

        // Live Eligibility Checklist Evaluator
        function updateLiveChecklist() {
            var cards = membersGrid.querySelectorAll('.member-card');
            var totalCount = cards.length;

            // 1. Members count pill
            if (totalCount >= 4 && totalCount <= 6) {
                setPillStatus(pillMembers, true, `✓ Roster: ${totalCount} / 6 Members (Valid)`);
            } else {
                setPillStatus(pillMembers, false, `Roster: ${totalCount} / 6 (Min 4, Max 6)`);
            }

            // 2. Female count pill
            var femaleRadios = membersGrid.querySelectorAll('.member-gender[value="Female"]:checked');
            var femaleCount = femaleRadios.length;
            if (femaleCount >= 1) {
                setPillStatus(pillFemales, true, `✓ Female Quota: ${femaleCount} Girl${femaleCount > 1 ? 's' : ''} (Met)`);
            } else {
                setPillStatus(pillFemales, false, `Female Quota: 0 / 1 Girl (Need 1 more)`);
            }

            // 3. Batches count pill & format
            var batches = [];
            var invalidFormatCount = 0;
            var regInputs = membersGrid.querySelectorAll('.member-input-reg');

            regInputs.forEach(function(input) {
                var val = input.value.trim();
                if (val !== '') {
                    var match = val.match(regPattern);
                    if (match) {
                        batches.push(match[1]);
                    } else {
                        invalidFormatCount++;
                    }
                }
            });

            var uniqueBatches = Array.from(new Set(batches));
            if (uniqueBatches.length >= 2) {
                setPillStatus(pillBatches, true, `✓ Batches: ${uniqueBatches.join(', ')} (${uniqueBatches.length} Batches)`);
            } else if (uniqueBatches.length === 1) {
                setPillStatus(pillBatches, false, `Batches: ${uniqueBatches[0]} only (Need ≥ 2 Batches)`);
            } else {
                setPillStatus(pillBatches, false, `Batches: None detected (Need ≥ 2 Batches)`);
            }

            // 4. Format pill
            if (invalidFormatCount === 0 && regInputs.length > 0) {
                setPillStatus(pillFormat, true, `✓ Format: TG/YYYY/XXXX`);
            } else {
                setPillStatus(pillFormat, false, `Format: TG/YYYY/XXXX (${invalidFormatCount} invalid)`);
            }
        }

        function setPillStatus(pill, isValid, text) {
            if (!pill) return;
            pill.className = 'compliance-pill ' + (isValid ? 'valid' : 'invalid');
            var textEl = pill.querySelector('.pill-text');
            if (textEl) textEl.innerText = text;
        }

        // Initialize cards
        renderInitialMembers();

        // ── 3. AJAX Form Submission ──
        regForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side pre-flight check
            var cards = membersGrid.querySelectorAll('.member-card');
            var totalCount = cards.length;
            var females = membersGrid.querySelectorAll('.member-gender[value="Female"]:checked').length;
            var batches = [];
            var regNumbers = [];
            var hasDuplicateReg = false;

            cards.forEach(function(card) {
                var regVal = card.querySelector('.member-input-reg').value.trim().toUpperCase();
                var match = regVal.match(regPattern);
                if (match) {
                    batches.push(match[1]);
                }
                if (regVal !== '') {
                    if (regNumbers.includes(regVal)) {
                        hasDuplicateReg = true;
                    }
                    regNumbers.push(regVal);
                }
            });

            var uniqueBatches = Array.from(new Set(batches));
            var clientErrors = [];

            var teamName = document.getElementById('team_name').value.trim();
            if (!teamName) clientErrors.push('Please enter a team name.');

            var contactPhone = document.getElementById('contact_phone').value.trim();
            if (!contactPhone) clientErrors.push("Please enter the team captain's contact phone number.");

            var captainPassword = document.getElementById('captain_password').value.trim();
            if (!captainPassword) clientErrors.push("Please enter a captain password.");

            if (totalCount < 4 || totalCount > 6) {
                clientErrors.push('Every team must have between 4 and 6 members.');
            }
            if (females < 1) {
                clientErrors.push('Tournament rule requirement: Team must have at least 1 female player (girl).');
            }
            if (uniqueBatches.length < 2) {
                clientErrors.push('Tournament rule requirement: Team must represent at least two distinct batches (e.g., 2022 and 2023).');
            }
            if (hasDuplicateReg) {
                clientErrors.push('Duplicate registration numbers detected in your team roster.');
            }

            if (clientErrors.length > 0) {
                formFeedback.className = 'form-feedback is-visible';
                formFeedback.innerHTML = `
                    <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                        <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block; margin-bottom: 0.5rem;">Registration Requirements Not Met:</strong>
                        <ul style="padding-left: 1.5rem; line-height: 1.6;">
                            ${clientErrors.map(e => `<li>${e}</li>`).join('')}
                        </ul>
                    </div>`;
                formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Submit via Fetch API
            btnSubmitReg.disabled = true;
            btnSubmitReg.innerHTML = 'Verifying & Registering... ⏳';
            formFeedback.className = 'form-feedback is-visible';
            formFeedback.innerHTML = `
                <div style="background: var(--cream-dark); border: 1px solid var(--rule); padding: 1rem 1.5rem; color: var(--ink);">
                    Transmitting team registration dossier to tournament database...
                </div>`;

            var formData = new FormData(regForm);

            fetch('api_register.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btnSubmitReg.disabled = false;
                btnSubmitReg.innerHTML = 'Submit Team Registration <span aria-hidden="true">→</span> <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>';

                if (data.success) {
                    // Success receipt
                    formFeedback.innerHTML = `
                        <div class="receipt-card">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--ink); padding-bottom: 0.75rem;">
                                <div>
                                    <h3 class="receipt-title">♔ Team Registration Submitted!</h3>
                                    <p style="color: var(--ink); font-size: 1.05rem;">
                                        <strong>Team: ${data.data.team_name}</strong> (Squad ID: #${data.data.team_id})
                                    </p>
                                </div>
                                <span style="background: var(--ink); color: var(--cream); padding: 0.25rem 0.75rem; font-family: var(--font-display); font-weight: 700; text-transform: uppercase; font-size: 0.85rem;">
                                    Pending Admin Approval
                                </span>
                            </div>
                            <div style="margin: 1.25rem 0; font-size: 1rem; line-height: 1.7; color: var(--ink);">
                                <p><strong>Tournament Verification:</strong></p>
                                <ul class="receipt-list">
                                    <li>✓ <strong>Total Players:</strong> ${data.data.member_count} registered</li>
                                    <li>✓ <strong>Female Quota:</strong> ${data.data.female_count} girl${data.data.female_count == 1 ? '' : 's'} included</li>
                                    <li>✓ <strong>Batches Represented:</strong> ${data.data.batches.join(', ')}</li>
                                    <li>⏳ <strong>Status:</strong> Pending admin approval - the team appears in the Arena Standings once approved</li>
                                </ul>
                                <p style="font-size: 0.95rem; color: var(--ink-light); margin-top: 0.5rem;">
                                    Your entry is awaiting organizer approval. Your captain will receive round pairing alerts before Round 1. You may present your student ID at the registration desk on tournament day.
                                </p>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.25rem;">
                                <button type="button" onclick="window.print()" class="btn-submit-reg" style="background: var(--ink); padding: 0.6rem 1.5rem; font-size: 1rem;">
                                    🖨️ Print Confirmation Slip
                                </button>
                                <button type="button" onclick="location.reload()" class="btn-add-member" style="padding: 0.6rem 1.5rem; font-size: 1rem;">
                                    Register Another Team
                                </button>
                            </div>
                        </div>`;
                    regForm.reset();
                    renderInitialMembers();
                    formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Error message
                    var errList = Array.isArray(data.errors) ? data.errors : (data.errors ? Object.values(data.errors) : []);
                    var errorsMarkup = errList.length > 0 ? `
                        <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                            ${errList.map(err => `<li>${err}</li>`).join('')}
                        </ul>` : '';

                    formFeedback.innerHTML = `
                        <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                            <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block;">Registration Error:</strong>
                            <p style="margin-top: 0.25rem;">${data.message || 'Validation failed.'}</p>
                            ${errorsMarkup}
                        </div>`;
                    formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            })
            .catch(function(err) {
                btnSubmitReg.disabled = false;
                btnSubmitReg.innerHTML = 'Submit Team Registration <span aria-hidden="true">→</span> <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>';
                formFeedback.innerHTML = `
                    <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                        <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block;">Connection Error:</strong>
                        <p style="margin-top: 0.25rem;">Unable to contact the registration server. Please check MySQL database service.</p>
                    </div>`;
            });
        });

        // ── 4. Particles.js Configuration ──
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 60,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": ["#0A0A0A", "#FFFFFF", "#A1A1AA", "#A93226"]
                },
                "shape": {
                    "type": "char",
                    "character": {
                        "value": ["♙", "♟", "♖", "♜", "♘", "♞", "♗", "♝", "♕", "♛", "♔"]
                    }
                },
                "opacity": {
                    "value": 0.1,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 1,
                        "opacity_min": 0.05,
                        "sync": false
                    }
                },
                "size": {
                    "value": 15,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 2,
                        "size_min": 8,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": false
                },
                "move": {
                    "enable": true,
                    "speed": 1,
                    "direction": "none",
                    "random": true,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "repulse"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                },
                "modes": {
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    }
                }
            },
            "retina_detect": true
        });

        // ── 5. Header Scroll Effect ──
        var siteHeader = document.getElementById('siteHeader');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                siteHeader.classList.add('scrolled');
            } else {
                siteHeader.classList.remove('scrolled');
            }
        });

        // ── 6. Parallax Effect for Floating Pieces ──
        window.addEventListener('mousemove', function(e) {
            var pieces = document.querySelectorAll('.floating-piece');
            var mouseX = e.clientX / window.innerWidth;
            var mouseY = e.clientY / window.innerHeight;
            
            pieces.forEach(function(piece, index) {
                var offsetX = (mouseX - 0.5) * 20 * (index + 1);
                var offsetY = (mouseY - 0.5) * 20 * (index + 1);
                piece.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
            });
        });

        // ── 7. Touch/Mobile Parallax ──
        window.addEventListener('touchmove', function(e) {
            var pieces = document.querySelectorAll('.floating-piece');
            if (e.touches && e.touches.length > 0) {
                var touchX = e.touches[0].clientX / window.innerWidth;
                var touchY = e.touches[0].clientY / window.innerHeight;
                
                pieces.forEach(function(piece, index) {
                    var offsetX = (touchX - 0.5) * 10 * (index + 1);
                    var offsetY = (touchY - 0.5) * 10 * (index + 1);
                    piece.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
                });
            }
        });

        // ── 8. Haptic Feedback for Mobile Devices ──
        if ('vibrate' in navigator) {
            document.querySelectorAll('.gtouch-tap, .gtouch-long-press').forEach(function(element) {
                element.addEventListener('touchstart', function() {
                    navigator.vibrate([20]);
                });
            });
        }

    })();
    </script>

    <!-- ═══ COUNTDOWN TICKER ═══ -->
    <script>
    (function () {
        var box = document.getElementById('countdown');
        if (!box) return;

        // Absolute instant, already offset-normalised server-side (e.g. +05:30).
        var target = new Date(box.getAttribute('data-target'));
        if (isNaN(target.getTime())) return;

        var elDays  = document.getElementById('cd-days');
        var elHours = document.getElementById('cd-hours');
        var elMins  = document.getElementById('cd-mins');
        var elSecs  = document.getElementById('cd-secs');
        var elSr    = document.getElementById('cd-sr');
        var grid    = box.querySelector('.countdown-grid');

        function pad(n) { return String(n).padStart(2, '0'); }

        function render() {
            var remaining = target.getTime() - Date.now();

            if (remaining <= 0) {
                if (window.__cdDone) return;
                window.__cdDone = true;
                clearInterval(window.__cdTimer);
                if (grid) grid.innerHTML =
                    '<div class="countdown-done">♔ Play in Progress</div>';
                if (elSr) elSr.textContent = 'FOT Knights Arena has started. Play is in progress.';
                return;
            }

            var secs = Math.floor(remaining / 1000);
            var d = Math.floor(secs / 86400);
            var h = Math.floor((secs % 86400) / 3600);
            var m = Math.floor((secs % 3600) / 60);
            var s = secs % 60;

            if (elDays)  elDays.textContent  = pad(d);
            if (elHours) elHours.textContent = pad(h);
            if (elMins)  elMins.textContent  = pad(m);
            if (elSecs)  elSecs.textContent  = pad(s);

            // Clock-red urgency once under an hour
            box.classList.toggle('is-urgent', remaining < 3600000);

            // Only announce on whole-minute boundaries — avoids a chatty
            // screen reader reading out every second.
            if (elSr && s === 0) {
                elSr.textContent = d + ' day' + (d === 1 ? '' : 's') + ', ' + h +
                    ' hour' + (h === 1 ? '' : 's') + ' until FOT Knights Arena begins.';
            }
        }

        render();
        window.__cdTimer = setInterval(render, 1000);
    })();
    </script>
</body>
</html>
