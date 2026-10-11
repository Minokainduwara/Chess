<?php
/**
 * Hero & Countdown Section
 * FOT Knights Arena — Chess Tournament
 */
?>
    <!-- ═══ HERO ═══ -->
    <section class="hero" aria-labelledby="hero-title">
        <div class="page-rail hero-inner">
            <div class="hero-copy">
                <h1 id="hero-title" class="hero-title">
                    FOT Knights<br>
                    Arena<br>
                    2026
                </h1>
                <?php if (!empty($countdown_iso)): ?>
                <div class="countdown" id="countdown" data-target="<?= htmlspecialchars($countdown_iso, ENT_QUOTES) ?>">
                    <h2 class="countdown-heading">
                        <span aria-hidden="true">♜</span> <?= htmlspecialchars($countdown_label ?? 'Countdown to First Move') ?>
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
                        $pieces = [
                            ['♜', 0, 0], ['♞', 1, 0], ['♝', 2, 0], ['♛', 3, 0],
                            ['♚', 4, 0], ['♝', 5, 0], ['♞', 6, 0], ['♜', 7, 0],
                            ['♟', 0, 1], ['♟', 1, 1], ['♟', 3, 1], ['♟', 4, 1],
                            ['♟', 5, 1], ['♟', 6, 1], ['♟', 7, 1],
                            ['♟', 2, 3],
                            ['♙', 0, 6], ['♙', 1, 6], ['♙', 2, 6], ['♙', 3, 6],
                            ['♙', 5, 6], ['♙', 6, 6], ['♙', 7, 6],
                            ['♙', 4, 4],
                            ['♖', 0, 7], ['♘', 1, 7], ['♗', 2, 7], ['♕', 3, 7],
                            ['♔', 4, 7], ['♗', 5, 7], ['♘', 6, 7], ['♖', 7, 7],
                        ];

                        foreach ($pieces as [$piece, $col, $row]) {
                            $x = $col * 50 + 25;
                            $y = $row * 50 + 35;
                            $isBlack = in_array($piece, ['♜','♞','♝','♛','♚','♟']);
                            $color = $isBlack ? '#0A0A0A' : '#FFFFFF';
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

    <!-- Countdown Ticker Script -->
    <script>
    (function () {
        var box = document.getElementById('countdown');
        if (!box) return;

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
                if (grid) grid.innerHTML = '<div class="countdown-done">♔ Play in Progress</div>';
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

            box.classList.toggle('is-urgent', remaining < 3600000);

            if (elSr && s === 0) {
                elSr.textContent = d + ' day' + (d === 1 ? '' : 's') + ', ' + h +
                    ' hour' + (h === 1 ? '' : 's') + ' until FOT Knights Arena begins.';
            }
        }

        render();
        window.__cdTimer = setInterval(render, 1000);
    })();
    </script>
