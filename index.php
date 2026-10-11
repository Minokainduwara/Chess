<?php
/**
 * Public Landing Page Front Controller
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/src/Services/TournamentService.php';
require_once __DIR__ . '/src/Services/MatchService.php';
require_once __DIR__ . '/src/Services/DropBoardService.php';
require_once __DIR__ . '/src/Services/ArbiterService.php';
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

// ── 4. Fetch Standings, Matches, Arbiters & Schedules ──
$standings_teams = $scoreboard_visible ? TournamentService::getStandings() : [];
$individual_standings = TournamentService::getIndividualStandings();
$public_matches = MatchService::getPublicMatches();
$schedule_rounds = DropBoardService::getAllRounds('ASC');
$all_arbiters = ArbiterService::getAllArbiters();

$registration_open = ($settings['registration_open'] ?? '1') === '1';
$registration_closed_message = $settings['registration_closed_message'] ?? 'Official team registration for FOT Knights Arena is now closed.';

// ── 5. Render Page View Partials ──
View::render('layouts/header', [
    'page_title'         => 'FOT Knights Arena — University of Ruhuna',
    'scoreboard_visible' => $scoreboard_visible,
    'registration_open'  => $registration_open
]);

View::render('home/hero', [
    'countdown_iso'   => $countdown_iso,
    'countdown_label' => $countdown_label
]);

View::render('home/details');

View::render('home/arbiters', [
    'all_arbiters' => $all_arbiters
]);

View::render('home/schedule', [
    'schedule_rounds' => $schedule_rounds
]);

$mp_win        = (int)($settings['mp_win'] ?? 2);
$mp_draw       = (int)($settings['mp_draw'] ?? 1);
$mp_loss       = (int)($settings['mp_loss'] ?? 0);
$mp_loss_zero_gp = (int)($settings['mp_loss_zero_gp'] ?? -1);

View::render('home/standings', [
    'scoreboard_visible' => $scoreboard_visible,
    'scoreboard_status'  => $scoreboard_status,
    'standings_teams'    => $standings_teams,
    'mp_win'             => $mp_win,
    'mp_draw'            => $mp_draw,
    'mp_loss'            => $mp_loss,
    'mp_loss_zero_gp'    => $mp_loss_zero_gp
]);

View::render('home/indiv_standings', [
    'individual_standings' => $individual_standings
]);

View::render('home/pairings', [
    'public_matches' => $public_matches
]);

View::render('home/rules', [
    'mp_win'          => $mp_win,
    'mp_draw'         => $mp_draw,
    'mp_loss'         => $mp_loss,
    'mp_loss_zero_gp' => $mp_loss_zero_gp
]);

View::render('home/register_form', [
    'registration_open'           => $registration_open,
    'registration_closed_message' => $registration_closed_message
]);

View::render('layouts/footer');
