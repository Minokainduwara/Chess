<?php
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();
$scoreboard_visible = true;
$scoreboard_status = 'Standings updated live after each round';
$standings_teams = [];

if ($pdo) {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM `tournament_settings`");
    $settings = [];
    while ($s = $stmt_set->fetch()) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
    $scoreboard_visible = ($settings['scoreboard_visible'] ?? '1') === '1';
    $scoreboard_status = $settings['scoreboard_status'] ?? 'Standings updated live after each round';

    if ($scoreboard_visible) {
        $stmt_st = $pdo->query("
            SELECT id, team_name, played, won, drawn, lost, game_points, match_points, standing_notes
            FROM `teams`
            ORDER BY match_points DESC, game_points DESC, won DESC, id ASC
        ");
        $standings_teams = $stmt_st->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty of Technology Chess Championship 2026 — University of Ruhuna</title>
    <meta name="description" content="The official chess championship of the Faculty of Technology, University of Ruhuna. Register now for the 2026 tournament.">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Particles.js Library for floating chess pieces -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    
    <style>
        /* ── Font Loading ── */
        /* Archivo Narrow — variable weight axis (400–700). Condensed sans used
           as the tournament/scoreboard display face. */
        @font-face {
            font-family: 'Archivo Narrow';
            src: url('fonts/ArchivoNarrow-Regular.woff2') format('woff2');
            font-weight: 400 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Cormorant Garamond';
            src: url('fonts/CormorantGaramond.woff2') format('woff2');
            font-weight: 400 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'EB Garamond';
            src: url('fonts/EBGaramond.woff2') format('woff2');
            font-weight: 400 600;
            font-style: normal;
            font-display: swap;
        }

        /* ── Reset & Base ── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        ::selection {
            background: var(--ink);
            color: var(--cream);
        }

        :root {
            /* ══ Black & White Mono — chess tournament ═══════════════════════
               The legacy token NAMES (--cream, --red, --ink …) are deliberately
               kept so the existing var() references keep resolving; only their
               VALUES have been re-mapped to the monochrome scheme. */

            /* Surfaces: white → light grey */
            --cream: #FFFFFF;            /* base surface (page)      */
            --cream-dark: #F4F4F5;       /* subtle fill              */
            --card: #FAFAFA;             /* raised card surface      */

            /* Ink: primary text + solid fills (the "black" pieces) */
            --ink: #0A0A0A;
            --ink-light: #52525B;        /* secondary text           */
            --ink-muted: #71717A;        /* tertiary text            */

            /* The single restrained accent — the tournament clock */
            --red: #A93226;
            --red-hover: #8C271E;
            --accent: #A93226;
            --accent-tint: #FCF3F2;

            /* Hairlines */
            --rule: #E4E4E7;
            --rule-light: #EFEFF1;

            /* The board — mono chessboard squares */
            --board-light: #FFFFFF;
            --board-dark: #D4D4D8;
            --checker: repeating-conic-gradient(var(--ink) 0% 25%, #FFFFFF 0% 50%) 0 0 / 14px 14px;

            --blue-ink: #52525B;         /* legacy token — now neutral */

            --font-display: 'Archivo Narrow', 'Helvetica Neue', Arial, sans-serif;
            --font-body: 'EB Garamond', 'Georgia', serif;

            --page-max: 1180px;
            --page-pad: clamp(1.25rem, 4vw, 3rem);
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: var(--font-body);
            font-weight: 400;
            line-height: 1.6;
            color: var(--ink);
            background: var(--cream);
            overflow-x: hidden;
            position: relative;
        }
        
        /* Faint chessboard — the page itself reads as a board */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: repeating-conic-gradient(
                rgba(10, 10, 10, 0.035) 0% 25%,
                rgba(255, 255, 255, 0) 0% 50%
            );
            background-size: 72px 72px;
            pointer-events: none;
            z-index: -2;
        }
        
        /* Particles Container */
        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Floating Chess Pieces */
        .floating-pieces {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }
        
        .floating-piece {
            position: absolute;
            font-size: 2rem;
            opacity: 0.12;
            animation: pieceFloat 6s ease-in-out infinite;
            color: var(--ink);
        }
        
        @keyframes pieceFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        /* ── Scrollbar theming ── */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--cream-dark);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--ink);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--ink-light);
        }

        /* ── Focus ring ── */
        :focus-visible {
            outline: 2px solid var(--red);
            outline-offset: 3px;
        }

        /* ── Layout ── */
        .page-rail {
            max-width: var(--page-max);
            margin: 0 auto;
            padding-left: var(--page-pad);
            padding-right: var(--page-pad);
        }

        /* ── Header ── */
        .site-header {
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--ink);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--ink);
        }
        .header-seal {
            width: 42px;
            height: 42px;
            border-radius: 0; /* square — a board square, not a coin */
            background: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .header-seal svg {
            width: 28px;
            height: 28px;
            fill: var(--cream);
        }
        .header-brand-text {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.9375rem;
            line-height: 1.2;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .header-brand-text span {
            display: block;
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            color: var(--ink-light);
        }
        .header-nav {
            display: flex;
            gap: 0.25rem;
        }
        .header-nav a {
            font-family: var(--font-display);
            font-weight: 500;
            font-size: 0.9375rem;
            letter-spacing: 0.02em;
            text-decoration: none;
            color: var(--ink);
            padding: 0.375rem 0.75rem;
            border-radius: 2px;
            transition: color 0.2s ease;
        }
        .header-nav a:hover {
            color: var(--red);
        }
        .header-nav-sep {
            color: var(--rule);
            font-family: var(--font-display);
            font-size: 0.8125rem;
            line-height: 2;
            user-select: none;
        }

        /* ── Hero ── */
        .hero {
            padding: 4.5rem 0 0;
        }
        .hero-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .hero-copy {
            padding-top: 1rem;
        }
        .hero-eval {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            color: var(--red);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .hero-eval-symbol {
            font-size: 1.5rem;
            line-height: 1;
        }
        .hero-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: clamp(2.25rem, 4.5vw, 3.75rem);
            line-height: 1.1;
            letter-spacing: -0.01em;
            color: var(--ink);
            margin-bottom: 0.75rem;
        }
        .hero-subtitle {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: clamp(1.25rem, 2.5vw, 1.75rem);
            color: var(--ink-light);
            margin-bottom: 2.25rem;
            letter-spacing: -0.01em;
        }
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.0625rem;
            letter-spacing: 0.02em;
            color: var(--cream);
            background: var(--red);
            border: none;
            padding: 0.875rem 2rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.25s ease, transform 0.15s ease;
        }
        .hero-cta:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
        }
        .hero-cta:active {
            transform: translateY(0);
        }
        .hero-cta-arrow {
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }
        .hero-cta:hover .hero-cta-arrow {
            transform: translateX(3px);
        }
        .hero-cta-notation {
            font-weight: 400;
            opacity: 0.7;
            font-size: 0.875rem;
            margin-left: 0.25rem;
        }

        /* ── Chess Board (SVG diagram) ── */
        .hero-board {
            position: relative;
        }
        .chess-diagram {
            width: 100%;
            max-width: 480px;
            margin-left: auto;
            border: 2px solid var(--ink);
        }
        .chess-diagram svg {
            display: block;
            width: 100%;
            height: auto;
        }

        /* ── Section divider — a checkerboard rank ── */
        .section-rule {
            border: none;
            height: 10px;
            margin: 0;
            background: var(--checker);
            outline: 1px solid var(--ink);
            outline-offset: -1px;
        }
        .section-rule--light {
            opacity: 0.45;
        }

        /* ── Event Details (Annotation columns) ── */
        .details {
            padding: 4rem 0;
        }
        .details-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .details-heading::after {
            content: '';
            flex: 1;
            height: 10px;
            background: var(--checker);
            opacity: 0.9;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem 4rem;
        }
        .detail-item {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }
        .detail-symbol {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--red);
            flex-shrink: 0;
            width: 1.5rem;
            text-align: center;
            line-height: 1.6;
        }
        .detail-label {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.9375rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-family: var(--font-body);
            font-size: 1rem;
            color: var(--ink-light);
            line-height: 1.5;
        }

        /* ── Schedule (Variation notation) ── */
        .schedule {
            padding: 4rem 0;
        }
        .schedule-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .schedule-heading::after {
            content: '';
            flex: 1;
            height: 10px;
            background: var(--checker);
            opacity: 0.9;
        }
        .round-list {
            list-style: none;
        }
        .round-item {
            display: grid;
            grid-template-columns: 3.5rem 1fr 1fr auto;
            gap: 0.5rem 1.5rem;
            align-items: baseline;
            padding: 1rem 0;
            border-bottom: 1px solid var(--rule-light);
        }
        .round-item:last-child {
            border-bottom: none;
        }
        .round-number {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.375rem;
            color: var(--ink);
            letter-spacing: -0.02em;
        }
        .round-date {
            font-family: var(--font-body);
            font-size: 1rem;
            color: var(--ink);
        }
        .round-time {
            font-family: var(--font-display);
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--ink-light);
            letter-spacing: 0.02em;
        }
        .round-status {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.6875rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.25rem 0.625rem;
            border-radius: 2px;
        }
        .round-status--upcoming {
            color: var(--ink-light);
            background: var(--cream-dark);
        }
        .round-status--active {
            color: var(--cream);
            background: var(--red);
        }

        /* ── Standings Scoreboard ── */
        .standings {
            padding: 4rem 0;
        }
        .standings-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .standings-heading::after {
            content: '';
            flex: 1;
            height: 10px;
            background: var(--checker);
            opacity: 0.9;
        }
        .standings-status-badge {
            font-family: var(--font-display);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--cream);
            background: var(--ink);
            padding: 0.25rem 0.65rem;
            border: 1px solid var(--ink);
            border-radius: 2px;
        }
        .standings-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--ink);
            background: var(--card);
            margin-top: 1rem;
        }
        .standings-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--font-body);
            font-size: 1rem;
        }
        .standings-table th {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink);
            background: var(--cream-dark);
            padding: 0.85rem 1rem;
            border-bottom: 2px solid var(--ink);
            text-align: left;
        }
        .standings-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--rule-light);
            color: var(--ink);
            vertical-align: middle;
        }
        .standings-table tbody tr:hover td {
            background: var(--card);
        }
        .rank-podium td {
            background: var(--cream-dark);
        }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.2rem 0.5rem;
            border-radius: 2px;
            letter-spacing: 0.04em;
        }
        /* Mono podium — 1st solid black, 2nd outlined, 3rd grey */
        .rank-gold {
            background: var(--ink);
            color: var(--cream);
            border: 1px solid var(--ink);
        }
        .rank-silver {
            background: var(--cream);
            color: var(--ink);
            border: 1px solid var(--ink);
        }
        .rank-bronze {
            background: var(--cream-dark);
            color: var(--ink);
            border: 1px solid var(--rule);
        }
        .rank-num {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.05rem;
            padding-left: 0.35rem;
            color: var(--ink-light);
        }
        .mp-badge {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            background: var(--ink);
            color: var(--cream);
            padding: 0.15rem 0.6rem;
            border-radius: 2px;
            display: inline-block;
        }
        .note-pill {
            font-family: var(--font-display);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: var(--cream-dark);
            color: var(--ink-light);
            padding: 0.2rem 0.5rem;
            border-radius: 2px;
        }

        /* ── Rules section ── */
        .rules {
            padding: 4rem 0;
        }
        .rules-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .rules-heading::after {
            content: '';
            flex: 1;
            height: 10px;
            background: var(--checker);
            opacity: 0.9;
        }
        .rules-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 4rem;
        }
        .rule-entry {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }
        .rule-move {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--red);
            flex-shrink: 0;
            min-width: 2.5rem;
            line-height: 1.6;
        }
        .rule-text {
            font-family: var(--font-body);
            font-size: 1rem;
            color: var(--ink-light);
            line-height: 1.5;
        }

        /* ── Registration Form Section ── */
        .registration {
            padding: 4.5rem 0;
        }
        .reg-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .reg-heading::after {
            content: '';
            flex: 1;
            height: 10px;
            background: var(--checker);
            opacity: 0.9;
        }
        .reg-lead {
            font-family: var(--font-body);
            font-size: 1.15rem;
            color: var(--ink-light);
            margin-bottom: 2rem;
            max-width: 820px;
        }

        /* ── Live Compliance Checklist ── */
        .compliance-bar {
            background: var(--cream-dark);
            border: 1px solid var(--rule);
            padding: 1.25rem 1.5rem;
            margin-bottom: 2.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            align-items: center;
        }
        .compliance-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink);
            width: 100%;
            margin-bottom: -0.25rem;
        }
        .compliance-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.95rem;
            font-family: var(--font-body);
            color: var(--ink-light);
            transition: all 0.2s ease;
        }
        .compliance-pill .pill-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #A1A1AA;
            display: inline-block;
            transition: background 0.2s ease;
        }
        .compliance-pill.valid {
            color: var(--ink);
            font-weight: 600;
        }
        .compliance-pill.valid .pill-dot {
            background: var(--ink);
        }
        .compliance-pill.invalid {
            color: var(--red);
        }
        .compliance-pill.invalid .pill-dot {
            background: var(--red);
        }

        /* ── Form Layout ── */
        .form-block {
            margin-bottom: 2.5rem;
        }
        .form-block-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.35rem;
            color: var(--ink);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .input-label {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--ink);
        }
        .input-label .req {
            color: var(--red);
        }
        .input-field {
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--rule);
            background: var(--cream);
            font-family: var(--font-body);
            font-size: 1.05rem;
            color: var(--ink);
            border-radius: 2px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 2px rgba(169, 50, 38, 0.12);
        }
        .input-hint {
            font-size: 0.82rem;
            color: var(--ink-light);
            margin-top: 0.15rem;
        }

        /* ── Member Cards Grid ── */
        .members-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .member-card {
            background: var(--card);
            border: 1px solid var(--rule);
            padding: 1.35rem 1.5rem;
            position: relative;
            border-left: 3px solid var(--ink);
            transition: border-color 0.2s;
        }
        .member-card.is-captain {
            border-left-color: var(--red);
            background: var(--card);
        }
        .member-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--rule-light);
        }
        .member-slot-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--ink);
        }
        .member-badge-captain {
            font-family: var(--font-display);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: var(--red);
            color: var(--cream);
            padding: 0.2rem 0.5rem;
        }
        .btn-remove-member {
            background: none;
            border: none;
            color: var(--red);
            cursor: pointer;
            font-size: 0.85rem;
            font-family: var(--font-display);
            font-weight: 700;
            padding: 0.2rem 0.4rem;
        }
        .btn-remove-member:hover {
            text-decoration: underline;
        }

        .gender-options {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.35rem;
        }
        .gender-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            font-size: 1rem;
            font-family: var(--font-body);
        }
        .gender-label input[type="radio"] {
            accent-color: var(--red);
            width: 16px;
            height: 16px;
        }

        .batch-preview {
            display: inline-block;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.15rem 0.45rem;
            background: var(--cream-dark);
            color: var(--ink-light);
            margin-left: 0.5rem;
            border-radius: 2px;
        }

        /* ── Action Buttons ── */
        .form-actions-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--rule);
        }
        .btn-add-member {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 1px dashed var(--ink);
            color: var(--ink);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            padding: 0.65rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-add-member:hover {
            background: var(--cream-dark);
            border-style: solid;
        }
        .btn-add-member:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: transparent;
        }
        .btn-submit-reg {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--red);
            color: var(--cream);
            border: none;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            padding: 0.85rem 2.25rem;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .btn-submit-reg:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
        }
        .btn-submit-reg:disabled {
            background: #A1A1AA;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Form Feedback Message Box ── */
        .form-feedback {
            margin-top: 1.5rem;
            display: none;
        }
        .form-feedback.is-visible {
            display: block;
        }
        .receipt-card {
            background: var(--card);
            border: 2px solid var(--ink);
            padding: 2rem;
            margin-top: 1.5rem;
        }
        .receipt-title {
            font-family: var(--font-display);
            font-size: 1.75rem;
            color: var(--ink);
            margin-bottom: 0.5rem;
        }
        .receipt-list {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }
        .receipt-list li {
            margin-bottom: 0.35rem;
        }

        /* ── Mobile Form Refinement ── */
        @media (max-width: 768px) {
            .members-grid {
                grid-template-columns: 1fr;
            }
            .form-grid-3 {
                grid-template-columns: 1fr;
            }
            .form-actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-submit-reg {
                justify-content: center;
            }
        }

        /* ── Contact / Footer ── */
        .site-footer {
            border-top: 1px solid var(--ink);
            padding: 2.5rem 0;
        }
        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
        }
        .footer-info {
            font-family: var(--font-body);
            font-size: 0.875rem;
            color: var(--ink-light);
            line-height: 1.7;
        }
        .footer-info strong {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink);
            display: block;
            margin-bottom: 0.375rem;
        }
        .footer-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.02em;
            color: var(--cream);
            background: var(--red);
            border: none;
            padding: 0.625rem 1.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.25s ease;
        }
        .footer-cta:hover {
            background: var(--red-hover);
        }
        .footer-colophon {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: 0.6875rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--rule);
            text-align: right;
        }

        /* GTOUCH ANIMATIONS - Custom Touch/Gesture Effects */
        .gtouch-hover { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .gtouch-hover:hover { transform: scale(1.02); }
        .gtouch-press:active { transform: scale(0.98); }
        .gtouch-tap { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .gtouch-tap:active { transform: scale(0.95); opacity: 0.9; }
        .gtouch-ripple { position: relative; overflow: hidden; }
        .gtouch-ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .gtouch-ripple:active::after { width: 300%; height: 300%; }
        .chess-piece-drag { cursor: grab; transition: all 0.2s ease; }
        .chess-piece-drag:active { cursor: grabbing; transform: scale(1.05); z-index: 1000; }
        
        /* Enhanced animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-5px); } 60% { transform: translateY(-3px); } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        
        /* Enhanced header with scroll effect */
        .site-header.scrolled {
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.98);
        }
        
        /* Enhanced hero section */
        .hero {
            padding: 6rem 0 4rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, transparent 0%, rgba(10, 10, 10, 0.05) 100%);
            pointer-events: none;
        }
        .hero-copy { animation: fadeInUp 0.8s ease-out; }
        .hero-eval { animation: slideIn 0.6s ease-out 0.2s both; }
        .hero-eval-symbol { animation: bounce 2s infinite; }
        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--red);
            border-radius: 2px;
        }
        .hero-subtitle { animation: fadeIn 1s ease-out 0.4s both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        /* Enhanced CTA button */
        .hero-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        .hero-cta:hover::before { left: 100%; }
        
        /* Enhanced chess diagram */
        .chess-diagram {
            border: 3px solid var(--ink);
            border-radius: 0; /* square frame, like a real board */
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .chess-diagram:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
        }
        
        /* Enhanced table and interactive elements */
        .round-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .round-item:hover {
            background: rgba(10, 10, 10, 0.04);
            transform: translateX(5px);
        }
        .round-item:hover .round-number {
            color: var(--red);
            transform: scale(1.1);
        }
        .round-status--active { animation: pulse 2s infinite; }
        
        .standings-table-wrap:hover { box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15); }
        .standings-table tbody tr:hover td { transform: scale(1.01); }
        
        .rule-entry {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .rule-entry:hover { transform: translateX(5px); }
        .rule-entry:hover .rule-move { transform: scale(1.1); }
        .rule-entry:hover .rule-text { color: var(--ink); }
        
        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        .input-field:focus { transform: translateY(-2px); }
        .btn-remove-member:hover { background: rgba(169, 50, 38, 0.1); transform: scale(1.05); }
        
        .compliance-pill {
            padding: 0.5rem 1rem;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .compliance-pill:hover { transform: scale(1.05); }
        
        .footer-cta:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(169, 50, 38, 0.3); }
        
        /* Loading spinner for forms */
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        /* Responsive - hide floating pieces on mobile */
        @media (max-width: 768px) {
            .floating-pieces { display: none; }
        }
        
        /* ── Entrance animation ── */
        @media (prefers-reduced-motion: no-preference) {
            .reveal {
                opacity: 0;
                transform: translateY(24px);
                transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1),
                            transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .reveal.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
            .hero-board {
                opacity: 0;
                transform: translateY(16px);
                transition: opacity 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s,
                            transform 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
            }
            .hero-board.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .hero {
                padding: 2.5rem 0 0;
            }
            .hero-inner {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .hero-copy {
                padding-top: 0;
                order: 1;
            }
            .hero-board {
                order: 2;
            }
            .chess-diagram {
                max-width: 320px;
                margin: 0 auto;
            }
            .details-grid,
            .rules-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .round-item {
                grid-template-columns: 2.5rem 1fr;
                gap: 0.25rem 1rem;
            }
            .round-time {
                grid-column: 2;
            }
            .round-status {
                grid-column: 2;
                justify-self: start;
                margin-top: 0.25rem;
            }
            .header-nav {
                display: none;
            }
            .footer-inner {
                flex-direction: column;
            }
            .footer-colophon {
                text-align: left;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .hero-inner {
                gap: 2rem;
            }
            .chess-diagram {
                max-width: 380px;
            }
        }
    </style>
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
                <a href="admin.php" style="color: var(--red); font-weight: 600;" class="gtouch-hover">Admin</a>
            </nav>
        </div>
    </header>

    <!-- ═══ HERO ═══ -->
    <section class="hero" aria-labelledby="hero-title">
        <div class="page-rail hero-inner">
            <div class="hero-copy">
                <h1 id="hero-title" class="hero-title">
                    Faculty of Technology<br>
                    Chess Championship<br>
                    2026
                </h1>
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
                    <div class="detail-value">10th October 2026</div>
                </div>
            </div>
            <div class="detail-item gtouch-swipe">
                <span class="detail-symbol" aria-hidden="true">♕</span>
                <div>
                    <div class="detail-label">Venue</div>
                    <div class="detail-value">Faculty of Technology Canteen,<br>University of Ruhuna, Kamburupitiya</div>
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
            <li class="round-item gtouch-tap">
                <span class="round-number">1.</span>
                <span class="round-date">10 Oct 2026</span>
                <span class="round-time">09:00 – 10:30</span>
                <span class="round-status round-status--active">Registration Open</span>
            </li>
            <li class="round-item gtouch-tap">
                <span class="round-number">2.</span>
                <span class="round-date">10 Oct 2026</span>
                <span class="round-time">10:45 – 12:15</span>
                <span class="round-status round-status--active">Registration Open</span>
            </li>
            <li class="round-item gtouch-tap">
                <span class="round-number">3.</span>
                <span class="round-date">10 Oct 2026</span>
                <span class="round-time">13:00 – 14:30</span>
                <span class="round-status round-status--active">Registration Open</span>
            </li>
            <li class="round-item gtouch-tap">
                <span class="round-number">4.</span>
                <span class="round-date">10 Oct 2026</span>
                <span class="round-time">14:45 – 16:15</span>
                <span class="round-status round-status--active">Registration Open</span>
            </li>
            <li class="round-item gtouch-tap">
                <span class="round-number">5.</span>
                <span class="round-date">10 Oct 2026</span>
                <span class="round-time">16:30 – 18:00</span>
                <span class="round-status round-status--active">Registration Open</span>
            </li>
        </ol>
    </section>

    <?php if ($scoreboard_visible): ?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ STANDINGS SCOREBOARD ═══ -->
    <section class="standings page-rail" id="standings" aria-labelledby="standings-heading">
        <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
            <h2 class="standings-heading" id="standings-heading" style="margin-bottom: 0;">
                <span aria-hidden="true">♛</span> Championship Standings
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
            * P: Played, W: Won, D: Drawn, L: Lost, GP: Game Points (total individual board points), MP: Match Points (Win = 2 pts, Draw = 1 pt).
        </p>
    </section>
    <?php endif; ?>

    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ RULES ═══ -->
    <section class="rules page-rail" id="rules" aria-labelledby="rules-heading">
        <h2 class="rules-heading" id="rules-heading">
            <span aria-hidden="true">±</span> Tournament Rules
        </h2>
        <div class="rules-grid">
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">1.</span>
                <span class="rule-text">FIDE Laws of Chess apply to all games. Standard FIDE rules govern all play and disputes.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">2.</span>
                <span class="rule-text">Round-robin format. Every team plays against all other participating teams in the championship.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">3.</span>
                <span class="rule-text">Time control: 25 minutes + 5 seconds increment per move. Rapid format throughout.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">4.</span>
                <span class="rule-text">Touch-move rule is strictly enforced. Once a piece is touched, it must be moved if legal.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">5.</span>
                <span class="rule-text">Electronic devices must be switched off and kept away from the playing area during games.</span>
            </div>
            <div class="rule-entry gtouch-hover">
                <span class="rule-move">6.</span>
                <span class="rule-text">Tiebreaks: Buchholz, then Sonneborn-Berger, then direct encounter. Final standings use these criteria in order.</span>
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
            Register your squad for the Faculty of Technology Chess Championship 2026. Each team requires a minimum of 4 and a maximum of 6 registered students. Per championship rules, every team <strong>must include at least 1 female player</strong> and <strong>represent at least two batches</strong>.
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
                    © 2026 Faculty of Technology, University of Ruhuna · <a href="admin.php" style="color: var(--ink-light); text-decoration: underline;" class="gtouch-hover">Admin Portal</a>
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
                                    <h3 class="receipt-title">♔ Team Registration Confirmed!</h3>
                                    <p style="color: var(--ink); font-size: 1.05rem;">
                                        <strong>Team: ${data.data.team_name}</strong> (Squad ID: #${data.data.team_id})
                                    </p>
                                </div>
                                <span style="background: var(--ink); color: var(--cream); padding: 0.25rem 0.75rem; font-family: var(--font-display); font-weight: 700; text-transform: uppercase; font-size: 0.85rem;">
                                    Official Entry Recorded
                                </span>
                            </div>
                            <div style="margin: 1.25rem 0; font-size: 1rem; line-height: 1.7; color: var(--ink);">
                                <p><strong>Tournament Verification:</strong></p>
                                <ul class="receipt-list">
                                    <li>✓ <strong>Total Players:</strong> ${data.data.member_count} registered</li>
                                    <li>✓ <strong>Female Quota:</strong> ${data.data.female_count} girl${data.data.female_count == 1 ? '' : 's'} included</li>
                                    <li>✓ <strong>Batches Represented:</strong> ${data.data.batches.join(', ')}</li>
                                </ul>
                                <p style="font-size: 0.95rem; color: var(--ink-light); margin-top: 0.5rem;">
                                    Your captain will receive round pairing alerts before Round 1. You may present your student ID at the registration desk on tournament day.
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
</body>
</html>
