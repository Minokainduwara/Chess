<?php
/**
 * Global Footer Layout
 * FOT Knights Arena — Chess Tournament
 */
?>
    <!-- ═══ FOOTER ═══ -->
    <footer class="site-footer">
        <div class="page-rail footer-inner">
            <div class="footer-info">
                <strong>Contact the Organizers</strong>
                Chess Society, Faculty of Technology<br>
                University of Ruhuna, Kamburupitiya
            </div>
            <div style="text-align: right;">
                <a href="index.php#register" class="footer-cta gtouch-ripple">
                    Register Now <span aria-hidden="true">→</span>
                </a>
                <div class="footer-colophon" style="margin-top: 1.25rem;">
                    © 2026 Faculty of Technology, University of Ruhuna · <a href="captain.php" style="color: var(--ink-light); text-decoration: underline;" class="gtouch-hover">Captain Portal</a> · <a href="admin.php" style="color: var(--ink-light); text-decoration: underline;" class="gtouch-hover">Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Particles.js & Interactive Scripts -->
    <script>
    (function() {
        // 1. Particles.js Configuration
        if (typeof particlesJS !== 'undefined') {
            particlesJS('particles-js', {
                "particles": {
                    "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                    "color": { "value": ["#0A0A0A", "#FFFFFF", "#A1A1AA", "#A93226"] },
                    "shape": { "type": "char", "character": { "value": ["♙", "♟", "♖", "♜", "♘", "♞", "♗", "♝", "♕", "♛", "♔"] } },
                    "opacity": { "value": 0.1, "random": true, "anim": { "enable": true, "speed": 1, "opacity_min": 0.05, "sync": false } },
                    "size": { "value": 15, "random": true, "anim": { "enable": true, "speed": 2, "size_min": 8, "sync": false } },
                    "line_linked": { "enable": false },
                    "move": { "enable": true, "speed": 1, "direction": "none", "random": true, "straight": false, "out_mode": "out", "bounce": false }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": { "onhover": { "enable": true, "mode": "repulse" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
                    "modes": { "repulse": { "distance": 200, "duration": 0.4 }, "push": { "particles_nb": 4 } }
                },
                "retina_detect": true
            });
        }

        // 2. Header Scroll Effect
        var siteHeader = document.getElementById('siteHeader');
        if (siteHeader) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    siteHeader.classList.add('scrolled');
                } else {
                    siteHeader.classList.remove('scrolled');
                }
            });
        }

        // 3. Parallax Effect for Floating Pieces
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

        // 4. Touch/Mobile Parallax
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

        // 5. Haptic Feedback for Mobile Devices
        if ('vibrate' in navigator) {
            document.querySelectorAll('.gtouch-tap, .gtouch-long-press').forEach(function(element) {
                element.addEventListener('touchstart', function() {
                    navigator.vibrate([20]);
                });
            });
        }
    })();
    </script>
</body>
</html>
