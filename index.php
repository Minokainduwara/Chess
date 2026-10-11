<?php
/**
 * Public Landing Page Front Controller
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/src/Services/TournamentService.php';
require_once __DIR__ . '/src/Services/MatchService.php';
require_once __DIR__ . '/src/Services/DropBoardService.php';
require_once __DIR__ . '/src/Helpers/View.php';

// ── 1. Fetch Tournament Settings ──
$settings = TournamentService::getSettings();
$scoreboard_visible = ($settings['scoreboard_visible'] ?? '1') === '1';
$scoreboard_status  = $settings['scoreboard_status'] ?? 'Standings updated live after each round';

$countdown_enabled = ($settings['countdown_enabled'] ?? '1') === '1';
$countdown_target  = $settings['countdown_target'] ?? '2026-10-10 07:00:00';
$countdown_label   = 'Countdown to First Move';

// ── 2. Check for Active Round Override ──
$active_round = DropBoardService::getActiveOpenRound();
if ($active_round) {
    $countdown_target = $active_round['deadline'] ?? $countdown_target;
    if (!empty($active_round['label'])) {
        $countdown_label = "Countdown to " . $active_round['label'];
    }
}

// ── 3. Normalise Countdown Instant to ISO-8601 (Asia/Colombo UTC+05:30) ──
$countdown_iso = '';
if ($countdown_enabled && $countdown_target !== '') {
    try {
        $tz_venue  = new DateTimeZone('Asia/Colombo');
        $dt_target = new DateTime($countdown_target, $tz_venue);
        $countdown_iso = $dt_target->format(DATE_ATOM);
    } catch (Exception $e) {
        $countdown_iso = '';
    }
}

// ── 4. Fetch Standings, Matches & Schedules ──
$standings_teams = $scoreboard_visible ? TournamentService::getStandings() : [];
$individual_standings = TournamentService::getIndividualStandings();
$public_matches = MatchService::getPublicMatches();
$schedule_rounds = DropBoardService::getAllRounds('ASC');

// ── 5. Render Page View Partials ──
View::render('layouts/header', [
    'page_title'         => 'FOT Knights Arena — University of Ruhuna',
    'scoreboard_visible' => $scoreboard_visible
]);

View::render('home/hero', [
    'countdown_iso'   => $countdown_iso,
    'countdown_label' => $countdown_label
]);

View::render('home/details');

View::render('home/schedule', [
    'schedule_rounds' => $schedule_rounds
]);

View::render('home/standings', [
    'scoreboard_visible' => $scoreboard_visible,
    'scoreboard_status'  => $scoreboard_status,
    'standings_teams'    => $standings_teams
]);

View::render('home/indiv_standings', [
    'individual_standings' => $individual_standings
]);

View::render('home/pairings', [
    'public_matches' => $public_matches
]);

View::render('home/rules');

View::render('home/register_form');

View::render('layouts/footer');
