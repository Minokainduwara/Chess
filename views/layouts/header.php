<?php
/**
 * Global Header Layout
 * FOT Knights Arena — Chess Tournament
 */
$page_title = $page_title ?? 'FOT Knights Arena — University of Ruhuna';
$scoreboard_visible = $scoreboard_visible ?? true;
$is_captain = $is_captain ?? false;
$captain_team_name = $captain_team_name ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="The official chess tournament of the Faculty of Technology, University of Ruhuna. FOT Knights Arena — 10 October 2026, 7001 Hall.">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Particles.js Library for floating chess pieces -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    
    <link rel="stylesheet" href="style.css">
    <?php if (!empty($extra_head)): ?>
        <?= $extra_head ?>
    <?php endif; ?>
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
            <a href="index.php" class="header-brand gtouch-ripple" aria-label="University of Ruhuna, Faculty of Technology">
                <div class="header-brand-text">
                    University of Ruhuna
                    <span>Faculty of Technology</span>
                </div>
            </a>
            <nav class="header-nav" aria-label="Main navigation">
                <?php if ($is_captain): ?>
                    <a href="index.php" class="gtouch-hover">Homepage</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="captain.php?page=dashboard" class="gtouch-hover <?= ($current_tab ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="captain.php?page=drops" class="gtouch-hover <?= ($current_tab ?? '') === 'drops' ? 'active' : '' ?>">Drop Boards</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="captain.php?action=logout" style="color: var(--red); font-weight: 600;" class="gtouch-hover">Logout (<?= htmlspecialchars($captain_team_name ?? '') ?>)</a>
                <?php else: ?>
                    <a href="index.php#details" class="gtouch-hover">About</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="index.php#schedule" class="gtouch-hover">Schedule</a>
                    <?php if ($scoreboard_visible): ?>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="index.php#standings" class="gtouch-hover">Standings</a>
                    <?php endif; ?>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="index.php#pairings" class="gtouch-hover">Pairings</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="index.php#rules" class="gtouch-hover">Rules</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="index.php#register" class="gtouch-hover">Register</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="captain.php" style="color: var(--blue); font-weight: 600;" class="gtouch-hover">Captain Portal</a>
                    <span class="header-nav-sep" aria-hidden="true">·</span>
                    <a href="admin.php" style="color: var(--red); font-weight: 600;" class="gtouch-hover">Admin</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
