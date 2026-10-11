<?php
/**
 * Admin Panel Front Controller
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/src/Services/AuthService.php';
require_once __DIR__ . '/src/Services/TeamService.php';
require_once __DIR__ . '/src/Services/TournamentService.php';
require_once __DIR__ . '/src/Services/MatchService.php';
require_once __DIR__ . '/src/Services/DropBoardService.php';
require_once __DIR__ . '/src/Services/ArbiterService.php';
require_once __DIR__ . '/src/Helpers/Flash.php';
require_once __DIR__ . '/src/Helpers/View.php';

AuthService::startSession();

$error_msg = Flash::getError();
$success_msg = Flash::getSuccess();

// ── 1. Handle Logout ──
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthService::logoutAdmin();
    header('Location: admin.php');
    exit;
}

// ── 2. Handle Login POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (AuthService::loginAdmin($username, $password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error_msg = 'Invalid administrator credentials. Please try again.';
    }
}

$is_authenticated = AuthService::isAdminLoggedIn();

// ── 3. Authenticated POST Handlers ──
if ($is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // AJAX: Instant Toggle Scoreboard Visibility
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_scoreboard_visibility') {
        header('Content-Type: application/json; charset=utf-8');
        $new_visible = ($_POST['visible'] === '1' || $_POST['visible'] === 'true') ? '1' : '0';
        TournamentService::updateSetting('scoreboard_visible', $new_visible);
        echo json_encode(['success' => true, 'visible' => $new_visible === '1']);
        exit;
    }

    // AJAX: Instant Toggle Countdown Visibility
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_countdown_visibility') {
        header('Content-Type: application/json; charset=utf-8');
        $new_visible = ($_POST['visible'] === '1' || $_POST['visible'] === 'true') ? '1' : '0';
        TournamentService::updateSetting('countdown_enabled', $new_visible);
        echo json_encode(['success' => true, 'visible' => $new_visible === '1']);
        exit;
    }

    // AJAX: Instant Toggle Registration Status (Open / Close)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_registration_status') {
        header('Content-Type: application/json; charset=utf-8');
        $new_open = ($_POST['open'] === '1' || $_POST['open'] === 'true') ? '1' : '0';
        TournamentService::updateSetting('registration_open', $new_open);
        echo json_encode(['success' => true, 'open' => $new_open === '1']);
        exit;
    }

    // AJAX: Approve / Reject Team
    if (isset($_POST['action']) && in_array($_POST['action'], ['approve_team', 'reject_team'], true)) {
        header('Content-Type: application/json; charset=utf-8');
        $team_id = (int)($_POST['team_id'] ?? 0);
        $new_status = ($_POST['action'] === 'approve_team') ? 'approved' : 'rejected';
        if ($team_id > 0 && TeamService::updateStatus($team_id, $new_status)) {
            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update team status']);
        }
        exit;
    }

    // Team Deletion
    if (isset($_POST['delete_team_id'])) {
        $del_id = (int)$_POST['delete_team_id'];
        if ($del_id > 0 && TeamService::deleteTeam($del_id)) {
            Flash::setSuccess("Team #{$del_id} and its members were removed.");
        }
        header('Location: admin.php');
        exit;
    }

    // Update Member Orders
    if (isset($_POST['update_members'])) {
        $team_id = (int)$_POST['team_id'];
        $res = TeamService::updateMembers($team_id, $_POST['members'] ?? []);
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php');
        exit;
    }

    // Update Scoreboard Standings & Countdown Targets
    if (isset($_POST['update_standings_action'])) {
        if (isset($_POST['scoreboard_visible'])) {
            TournamentService::updateSetting('scoreboard_visible', $_POST['scoreboard_visible'] === '1' ? '1' : '0');
        }
        if (isset($_POST['scoreboard_status'])) {
            TournamentService::updateSetting('scoreboard_status', trim($_POST['scoreboard_status']));
        }
        if (isset($_POST['countdown_enabled'])) {
            TournamentService::updateSetting('countdown_enabled', $_POST['countdown_enabled'] === '1' ? '1' : '0');
        }
        if (!empty($_POST['countdown_target'])) {
            TournamentService::updateSetting('countdown_target', str_replace('T', ' ', $_POST['countdown_target']) . ':00');
        }
        if (isset($_POST['registration_open'])) {
            TournamentService::updateSetting('registration_open', $_POST['registration_open'] === '1' ? '1' : '0');
        }
        if (isset($_POST['registration_closed_message'])) {
            TournamentService::updateSetting('registration_closed_message', trim($_POST['registration_closed_message']));
        }

        if (isset($_POST['scores']) && is_array($_POST['scores'])) {
            foreach ($_POST['scores'] as $team_id => $sc) {
                $p = max(0, (int)($sc['played'] ?? 0));
                $w = max(0, (int)($sc['won'] ?? 0));
                $d = max(0, (int)($sc['drawn'] ?? 0));
                $l = max(0, (int)($sc['lost'] ?? 0));
                $gp = max(0.0, (float)($sc['game_points'] ?? 0.0));
                $mp = (int)($sc['match_points'] ?? 0);
                $sb = (float)($sc['tiebreak_sb'] ?? 0.0);
                $notes = trim($sc['standing_notes'] ?? '');
                TeamService::updateStandingsRow((int)$team_id, $p, $w, $d, $l, $gp, $mp, $sb, $notes);
            }
        }
        Flash::setSuccess("Scoreboard standings and tournament status updated successfully!");
        header('Location: admin.php');
        exit;
    }

    // Create Round
    if (isset($_POST['create_round'])) {
        $res = DropBoardService::createRound((int)$_POST['round_number'], $_POST['label'] ?? '', $_POST['deadline'] ?? null);
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#rounds-manager');
        exit;
    }

    // Update Round Details & Schedule
    if (isset($_POST['update_round_details'])) {
        $res = DropBoardService::updateRoundDetails(
            (int)$_POST['round_number'],
            $_POST['label'] ?? '',
            $_POST['status'] ?? 'open',
            $_POST['deadline'] ?? null,
            $_POST['schedule_date'] ?? null,
            $_POST['schedule_time'] ?? null,
            $_POST['schedule_status'] ?? null
        );
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#rounds-manager');
        exit;
    }

    // Admin Drop Override
    if (isset($_POST['admin_update_drops'])) {
        $res = DropBoardService::saveAdminDropOverride((int)$_POST['override_team_id'], (int)$_POST['override_round_number'], $_POST['admin_dropped_members'] ?? []);
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#rounds-manager');
        exit;
    }

    // Create Match Pairing
    if (isset($_POST['create_match'])) {
        $res = MatchService::createMatch((int)$_POST['round_number'], (int)$_POST['team_a_id'], (int)$_POST['team_b_id']);
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#pairings-manager');
        exit;
    }

    // Delete Match Pairing
    if (isset($_POST['delete_match'])) {
        $res = MatchService::deleteMatch((int)$_POST['match_id']);
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#pairings-manager');
        exit;
    }

    // Save Board Results
    if (isset($_POST['save_match_results'])) {
        $res = MatchService::saveMatchResults(
            (int)$_POST['match_id'],
            $_POST['status'] ?? 'scheduled',
            $_POST['boards'] ?? [],
            $_POST['team_a_member_id'] ?? [],
            $_POST['team_b_member_id'] ?? []
        );
        if ($res['success']) Flash::setSuccess($res['message']);
        else Flash::setError($res['message']);
        header('Location: admin.php#pairings-manager');
        exit;
    }

    // Change Admin Password
    if (isset($_POST['change_admin_password'])) {
        $curr = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        $admin_user = AuthService::getAdminUser() ?? 'admin';

        $res = AuthService::changeAdminPassword($admin_user, $curr, $new, $conf);
        if ($res['success']) {
            Flash::setSuccess($res['message']);
        } else {
            Flash::setError($res['message']);
        }
        header('Location: admin.php#password-manager');
        exit;
    }

    // Add Arbiter
    if (isset($_POST['add_arbiter'])) {
        try {
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? 'Arbiter');
            $fideId = trim($_POST['fide_id'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $order = (int)($_POST['display_order'] ?? 1);

            if ($name === '') {
                Flash::setError('Arbiter full name is required.');
            } else {
                ArbiterService::createArbiter($name, $role, $fideId ?: null, $contact ?: null, $order);
                Flash::setSuccess("Arbiter '{$name}' was registered successfully.");
            }
        } catch (Throwable $e) {
            Flash::setError("Unable to save arbiter: " . $e->getMessage());
        }
        header('Location: admin.php#arbiters-manager');
        exit;
    }

    // Update Arbiter
    if (isset($_POST['update_arbiter'])) {
        try {
            $arb_id = (int)($_POST['arbiter_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? 'Arbiter');
            $fideId = trim($_POST['fide_id'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $order = (int)($_POST['display_order'] ?? 1);

            if ($arb_id > 0 && $name !== '') {
                ArbiterService::updateArbiter($arb_id, $name, $role, $fideId ?: null, $contact ?: null, $order);
                Flash::setSuccess("Arbiter #{$arb_id} '{$name}' was updated.");
            } else {
                Flash::setError('Invalid arbiter details for update.');
            }
        } catch (Throwable $e) {
            Flash::setError("Unable to update arbiter: " . $e->getMessage());
        }
        header('Location: admin.php#arbiters-manager');
        exit;
    }

    // Delete Arbiter
    if (isset($_POST['delete_arbiter'])) {
        try {
            $arb_id = (int)($_POST['arbiter_id'] ?? 0);
            if ($arb_id > 0 && ArbiterService::deleteArbiter($arb_id)) {
                Flash::setSuccess("Arbiter #{$arb_id} was removed.");
            }
        } catch (Throwable $e) {
            Flash::setError("Unable to delete arbiter: " . $e->getMessage());
        }
        header('Location: admin.php#arbiters-manager');
        exit;
    }
}

// ── 4. CSV Export ──
if ($is_authenticated && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=chess_tournament_teams_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

    fputcsv($output, ['Team ID', 'Team Name', 'Phone', 'Email', 'Status', 'Registered At', 'Board', 'Player Name', 'Reg Number', 'Gender', 'Batch', 'Role']);
    
    $pdo = Database::getConnection();
    $stmt_export = $pdo->query("
        SELECT t.id AS team_id, t.team_name, t.contact_phone, t.contact_email, t.status, t.created_at,
               m.member_order, m.name AS member_name, m.reg_number, m.gender, m.batch_year, m.is_captain
        FROM `teams` t
        JOIN `members` m ON t.id = m.team_id
        ORDER BY t.id ASC, m.member_order ASC
    ");
    while ($r = $stmt_export->fetch()) {
        fputcsv($output, [
            $r['team_id'], $r['team_name'], $r['contact_phone'] ?? 'N/A', $r['contact_email'] ?? 'N/A',
            $r['status'], $r['created_at'], $r['member_order'], $r['member_name'], $r['reg_number'],
            $r['gender'], $r['batch_year'], $r['is_captain'] ? 'Captain' : 'Player'
        ]);
    }
    fclose($output);
    exit;
}

// ── 5. Prepare View Data ──
$teams_data = [];
$total_teams = 0;
$pending_teams = 0;
$total_players = 0;
$total_females = 0;
$batch_counts = [];
$standings_teams = [];
$all_rounds = [];
$all_teams = [];
$all_matches = [];
$pairing_matrix = [];
$override_data = null;
$printable_boards = [];
$edit_match_data = null;
$all_arbiters = [];

if ($is_authenticated) {
    $pdo = Database::getConnection();
    $all_arbiters = ArbiterService::getAllArbiters();

    // Stats
    $total_teams   = (int)$pdo->query("SELECT COUNT(*) FROM `teams`")->fetchColumn();
    $pending_teams = (int)$pdo->query("SELECT COUNT(*) FROM `teams` WHERE `status` = 'pending'")->fetchColumn();
    $total_players = (int)$pdo->query("SELECT COUNT(*) FROM `members`")->fetchColumn();
    $total_females = (int)$pdo->query("SELECT COUNT(*) FROM `members` WHERE `gender` = 'Female'")->fetchColumn();

    $stmt_batches = $pdo->query("SELECT `batch_year`, COUNT(*) as cnt FROM `members` GROUP BY `batch_year` ORDER BY `batch_year` ASC");
    while ($b = $stmt_batches->fetch()) {
        $batch_counts[$b['batch_year']] = (int)$b['cnt'];
    }

    // Settings & Standings
    $settings = TournamentService::getSettings();
    $scoreboard_visible = ($settings['scoreboard_visible'] ?? '1') === '1';
    $scoreboard_status  = $settings['scoreboard_status'] ?? 'Standings updated live after each round';
    $countdown_enabled  = ($settings['countdown_enabled'] ?? '1') === '1';
    $countdown_target   = $settings['countdown_target'] ?? '2026-10-10 07:00:00';
    $registration_open  = ($settings['registration_open'] ?? '1') === '1';
    $registration_closed_message = $settings['registration_closed_message'] ?? 'Official team registration for FOT Knights Arena is now closed.';

    $standings_teams = TournamentService::getStandings();

    // Search and filter teams
    $search = trim($_GET['search'] ?? '');
    $filter_batch = trim($_GET['filter_batch'] ?? '');

    $sql = "
        SELECT t.*, COUNT(m.id) AS member_count,
               SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) AS female_count,
               GROUP_CONCAT(DISTINCT m.batch_year ORDER BY m.batch_year SEPARATOR ', ') AS batches_list
        FROM `teams` t
        LEFT JOIN `members` m ON t.id = m.team_id
        WHERE 1=1
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (t.team_name LIKE ? OR m.name LIKE ? OR m.reg_number LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($filter_batch !== '') {
        $sql .= " AND m.batch_year = ?";
        $params[] = $filter_batch;
    }
    $sql .= " GROUP BY t.id ORDER BY (t.status = 'pending') DESC, t.id ASC";

    $stmt_teams = $pdo->prepare($sql);
    $stmt_teams->execute($params);
    $teams_raw = $stmt_teams->fetchAll();

    foreach ($teams_raw as $t) {
        $stmt_m = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
        $stmt_m->execute([$t['id']]);
        $t['members'] = $stmt_m->fetchAll();
        $teams_data[] = $t;
    }

    // Rounds & Drop Overrides
    $all_rounds = DropBoardService::getAllRounds('ASC');
    $all_teams  = TeamService::getAllTeams();

    if (!empty($_GET['override_round']) && !empty($_GET['override_team'])) {
        $ov_r = (int)$_GET['override_round'];
        $ov_t = (int)$_GET['override_team'];
        $t_info = TeamService::getTeamWithMembers($ov_t);
        if ($t_info) {
            $drops = DropBoardService::getDropsForTeamAndRound($ov_t, $ov_r);
            $override_data = [
                'round'   => $ov_r,
                'team'    => $t_info,
                'members' => $t_info['members'],
                'drops'   => $drops
            ];
        }
    }

    // Printable Boards
    if (!empty($_GET['view_round'])) {
        $vr = (int)$_GET['view_round'];
        foreach ($all_teams as $t) {
            $playing = DropBoardService::getPlayingMembers($t['id'], $vr);
            if (!empty($playing)) {
                $printable_boards[] = [
                    'team_name' => $t['team_name'],
                    'playing'   => $playing
                ];
            }
        }
    }

    // Matches & Pairing Matrix
    $all_matches = MatchService::getAllMatchesGrouped();
    $pairing_matrix = MatchService::getPairingMatrix($all_teams);

    if (isset($_GET['edit_match'])) {
        $mid = (int)$_GET['edit_match'];
        $match = MatchService::getMatchWithBoards($mid);
        if ($match) {
            $ta_playing = DropBoardService::getPlayingMembers($match['team_a_id'], $match['round_number']);
            $tb_playing = DropBoardService::getPlayingMembers($match['team_b_id'], $match['round_number']);
            $edit_match_data = [
                'match'      => $match,
                'ta_playing' => $ta_playing,
                'tb_playing' => $tb_playing
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Administration — FOT Knights Arena</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --cream: #FAF7EE;
            --cream-card: #F2EDE0;
            --cream-dark: #E8E1CE;
            --ink: #0A0A0A;
            --ink-muted: #71717A;
            --red: #C0392B;
            --red-hover: #962D22;
            --rule: #D3CABA;
            --rule-light: #E8E1CE;
            --font-display: 'Cinzel', 'Georgia', serif;
            --font-body: 'EB Garamond', 'Georgia', serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-body); background: var(--cream); color: var(--ink); line-height: 1.6; min-height: 100vh; padding-bottom: 4rem; }
        .container { max-width: 1240px; margin: 0 auto; padding: 0 1.5rem; }
        .admin-header { border-bottom: 2px solid var(--ink); padding: 1.25rem 0; background: var(--cream); position: sticky; top: 0; z-index: 100; }
        .admin-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .admin-brand { display: flex; align-items: center; gap: 0.85rem; text-decoration: none; color: var(--ink); }
        .admin-brand-icon { width: 38px; height: 38px; background: var(--ink); color: var(--cream); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .admin-brand-title { font-family: var(--font-display); font-weight: 700; font-size: 1.25rem; line-height: 1.1; }
        .admin-brand-subtitle { font-size: 0.85rem; color: var(--ink-muted); text-transform: uppercase; }
        .admin-nav { display: flex; align-items: center; gap: 1rem; }
        .btn-link { font-family: var(--font-display); font-size: 1rem; text-decoration: none; color: var(--ink); padding: 0.4rem 0.8rem; }
        .btn-red { background: var(--red); color: var(--cream) !important; border: 1px solid var(--red); }
        .alert { padding: 1rem 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid var(--ink); background: var(--cream-card); }
        .alert-error { border-color: var(--red); background: #FFEBEE; color: #C62828; }
        .alert-success { border-color: #2E7D32; background: #E8F5E9; color: #1B5E20; }
        .login-wrap { max-width: 420px; margin: 5rem auto; background: var(--cream-card); border: 2px solid var(--ink); padding: 2.5rem; box-shadow: 6px 6px 0 var(--ink); }
        .login-title { font-family: var(--font-display); font-size: 1.75rem; text-align: center; margin-bottom: 0.25rem; }
        .login-subtitle { font-size: 0.95rem; color: var(--ink-muted); text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; margin-bottom: 0.35rem; }
        .form-control { width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--rule); background: #FFF; font-family: var(--font-body); font-size: 1rem; }
        .btn-submit { width: 100%; padding: 0.75rem; background: var(--red); color: #FFF; border: none; font-family: var(--font-display); font-size: 1.05rem; font-weight: 700; cursor: pointer; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: #FFF; border: 1px solid var(--rule); padding: 1.25rem; text-align: center; }
        .stat-num { font-family: var(--font-display); font-size: 2.25rem; font-weight: 700; line-height: 1; margin-bottom: 0.35rem; }
        .stat-label { font-size: 0.85rem; text-transform: uppercase; color: var(--ink-muted); }
        .stat-red { border-top: 3px solid var(--red); }
        .stat-green { border-top: 3px solid #2E7D32; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin: 2rem 0 1.5rem; flex-wrap: wrap; }
        .search-form { display: flex; gap: 0.5rem; align-items: center; flex: 1; max-width: 550px; }
        .btn-action { padding: 0.5rem 1rem; background: var(--cream-card); border: 1px solid var(--rule); font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; text-decoration: none; color: var(--ink); cursor: pointer; }
        .team-card { background: #FFF; border: 1px solid var(--rule); margin-bottom: 1.5rem; box-shadow: 2px 2px 0 rgba(0,0,0,0.05); }
        .team-card-header { padding: 1.25rem 1.5rem; background: var(--cream-card); border-bottom: 1px solid var(--rule); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .team-title-group { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .team-name { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; }
        .badge { padding: 0.2rem 0.55rem; font-size: 0.75rem; font-family: var(--font-display); font-weight: 700; text-transform: uppercase; border-radius: 2px; }
        .badge-green { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .badge-gold { background: #FFF8E1; color: #F57F17; border: 1px solid #FFE082; }
        .badge-blue { background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; }
        .badge-female { background: #FCE4EC; color: #C2185B; border: 1px solid #F48FB1; }
        .badge-male { background: #E8EAF6; color: #283593; border: 1px solid #9FA8DA; }
        .badge-captain { background: var(--ink); color: #FFF; }
        .roster-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .roster-table th { text-align: left; padding: 0.75rem 1.5rem; background: var(--cream-card); border-bottom: 1px solid var(--rule); font-family: var(--font-display); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--ink-muted); }
        .roster-table td { padding: 0.75rem 1.5rem; border-bottom: 1px solid var(--rule-light); }
        .team-card-footer { padding: 0.75rem 1.5rem; background: var(--cream); border-top: 1px solid var(--rule-light); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--ink-muted); flex-wrap: wrap; gap: 0.75rem; }
        .btn-delete { background: none; border: 1px solid #E57373; color: #C62828; padding: 0.25rem 0.65rem; font-size: 0.8rem; cursor: pointer; font-family: var(--font-display); }
        .publish-control-box { background: #FFFFFF; border: 1px solid var(--rule); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; }
        .btn-toggle-scoreboard { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-family: var(--font-display); font-size: 1rem; font-weight: 700; cursor: pointer; border-radius: 2px; border: 1px solid transparent; }
        .btn-toggle-publish { background: #1E6B23; color: #FFFFFF; }
        .btn-toggle-hide { background: #FAF7EE; color: var(--red); border-color: var(--red); }
        .switch-toggle-wrap { display: inline-flex; align-items: center; gap: 0.75rem; cursor: pointer; }
        .switch-control { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
        .switch-control input { opacity: 0; width: 0; height: 0; }
        .switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #D3CABA; border: 1px solid var(--rule); border-radius: 26px; }
        .switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.3); transition: 0.25s; }
        .switch-control input:checked + .switch-slider { background-color: #1E6B23; border-color: #1E6B23; }
        .switch-control input:checked + .switch-slider:before { transform: translateX(22px); }
        .instant-feedback-toast { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; font-size: 0.9rem; font-weight: 600; border-radius: 2px; }
        .instant-feedback-toast.success-live { background: #E8F5E9; color: #1E6B23; border: 1px solid #A5D6A7; }
        .instant-feedback-toast.success-hidden { background: #FFF8E1; color: #8A5D00; border: 1px solid #FFE082; }
        input.score-input { width: 65px; text-align: center; font-size: 1.15rem; font-weight: 700; padding: 0.45rem 0.2rem; border: 1px solid var(--rule); background: #FFFFFF; color: var(--ink); }
    </style>
</head>
<body>
    <!-- ═══ HEADER ═══ -->
    <header class="admin-header">
        <div class="container admin-header-inner">
            <a href="admin.php" class="admin-brand">
                <div class="admin-brand-icon">♔</div>
                <div>
                    <div class="admin-brand-title">FOT Knights Arena</div>
                    <div class="admin-brand-subtitle">Tournament Administration Portal</div>
                </div>
            </a>
            <nav class="admin-nav">
                <a href="index.php" class="btn-link">← Public Site</a>
                <?php if ($is_authenticated): ?>
                    <a href="#arbiters-manager" class="btn-link" title="Manage Tournament Arbiters">⚖️ Arbiters</a>
                    <a href="#password-manager" class="btn-link" title="Change Admin Password">🔑 Password</a>
                    <a href="admin.php?action=export_csv" class="btn-link" title="Export CSV for Excel">📥 Export CSV</a>
                    <a href="admin.php?action=logout" class="btn-link btn-red">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><strong>Attention:</strong> <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><strong>Success:</strong> <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!$is_authenticated): ?>
            <?php View::render('admin/login'); ?>
        <?php else: ?>
            <!-- Quick Metrics -->
            <div class="stats-grid">
                <div class="stat-card stat-red">
                    <div class="stat-num"><?= $total_teams ?></div>
                    <div class="stat-label">Registered Teams</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?= $total_players ?></div>
                    <div class="stat-label">Total Players</div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-num"><?= $total_females ?> <span style="font-size: 1.15rem; font-weight: normal; color: var(--ink-muted);">(<?= $total_players > 0 ? round(($total_females / $total_players) * 100) : 0 ?>%)</span></div>
                    <div class="stat-label">Female Players (Girls)</div>
                </div>
                <div class="stat-card" style="border-color: #E65100; background: #FFF3E0;">
                    <div class="stat-num" style="color: #BF360C;"><?= $pending_teams ?></div>
                    <div class="stat-label" style="color: #BF360C;">&#9203; Pending Approval</div>
                </div>
            </div>

            <!-- 1. Scoreboard & Standings Manager -->
            <?php View::render('admin/scoreboard', [
                'scoreboard_visible'          => $scoreboard_visible,
                'scoreboard_status'           => $scoreboard_status,
                'countdown_enabled'           => $countdown_enabled,
                'countdown_target'            => $countdown_target,
                'registration_open'           => $registration_open,
                'registration_closed_message' => $registration_closed_message,
                'standings_teams'             => $standings_teams
            ]); ?>

            <!-- 2. Registered Teams & Rosters -->
            <?php View::render('admin/teams', [
                'teams_data'    => $teams_data,
                'pending_teams' => $pending_teams,
                'batch_counts'  => $batch_counts
            ]); ?>

            <!-- 3. Rounds & Drop Overrides -->
            <?php View::render('admin/rounds', [
                'all_rounds'    => $all_rounds,
                'all_teams'     => $all_teams,
                'override_data' => $override_data
            ]); ?>

            <!-- 4. Printable Final Boards -->
            <?php View::render('admin/print_boards', [
                'all_rounds'       => $all_rounds,
                'printable_boards' => $printable_boards
            ]); ?>

            <!-- 5. Pairings & Results Manager -->
            <?php View::render('admin/pairings', [
                'all_rounds'       => $all_rounds,
                'all_teams'        => $all_teams,
                'all_matches'      => $all_matches,
                'pairing_matrix'   => $pairing_matrix,
                'edit_match_data'  => $edit_match_data
            ]); ?>

            <!-- 6. Arbiters & Officials Manager -->
            <?php View::render('admin/arbiters', [
                'all_arbiters' => $all_arbiters
            ]); ?>

            <!-- 7. Change Password Manager -->
            <?php View::render('admin/password', [
                'admin_user' => AuthService::getAdminUser()
            ]); ?>
        <?php endif; ?>
    </main>

    <!-- Admin Interactive Scripts -->
    <script>
    function autoCalcPoints(el) {
        var row = el.closest('tr');
        var w = parseInt(row.querySelector('.score-w').value) || 0;
        var d = parseInt(row.querySelector('.score-d').value) || 0;
        var mp = row.querySelector('.score-mp');
        if (mp) mp.value = (w * 2) + d;
    }

    function calcAllMatchPoints() {
        document.querySelectorAll('tbody tr').forEach(function(row) {
            var wInput = row.querySelector('.score-w');
            var dInput = row.querySelector('.score-d');
            var mp = row.querySelector('.score-mp');
            if (wInput && dInput && mp) {
                var w = parseInt(wInput.value) || 0;
                var d = parseInt(dInput.value) || 0;
                mp.value = (w * 2) + d;
            }
        });
    }

    function moderateTeam(teamId, action, btn) {
        var isApprove = action === 'approve_team';
        var confirmMsg = isApprove
            ? 'Approve this team? It will immediately appear in the public Arena Standings.'
            : 'Reject this team? It will stay hidden from the public standings.';
        if (!confirm(confirmMsg)) return;
        if (btn) btn.disabled = true;
        var fd = new FormData();
        fd.append('action', action);
        fd.append('team_id', teamId);
        fetch('admin.php', { method: 'POST', body: fd })
            .then(function(res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
            .then(function(data) {
                if (data.success) { location.reload(); }
                else { alert(data.error || 'Failed to update team status.'); if (btn) btn.disabled = false; }
            })
            .catch(function() { alert('Network error while updating team status. Please try again.'); if (btn) btn.disabled = false; });
    }

    function toggleScoreboardVisibility(forcedVal) {
        var hiddenInput = document.getElementById('scoreboard_visible_input');
        if (!hiddenInput) return;
        var currentVal = hiddenInput.value === '1';
        var targetVal = (typeof forcedVal === 'boolean') ? forcedVal : !currentVal;

        var btn = document.getElementById('instantToggleBtn');
        var btnText = document.getElementById('instantToggleBtnText');
        var btnIcon = document.getElementById('instantToggleBtnIcon');
        var toggleBox = document.getElementById('scoreboard_toggle_switch');
        var badge = document.getElementById('scoreboardLiveBadge');
        var feedback = document.getElementById('instantFeedback');

        if (btn) btn.disabled = true;
        if (toggleBox) toggleBox.disabled = true;

        if (feedback) {
            feedback.style.display = 'inline-flex';
            feedback.className = 'instant-feedback-toast';
            feedback.style.background = '#EDE6D2';
            feedback.style.color = 'var(--ink)';
            feedback.style.border = '1px solid var(--rule)';
            feedback.style.opacity = '1';
            feedback.innerHTML = '⏳ Updating homepage visibility...';
        }

        var formData = new FormData();
        formData.append('action', 'toggle_scoreboard_visibility');
        formData.append('visible', targetVal ? '1' : '0');

        fetch('admin.php', { method: 'POST', body: formData })
        .then(function(res) { if (!res.ok) throw new Error('HTTP error ' + res.status); return res.json(); })
        .then(function(data) {
            if (data.success) {
                var isVis = !!data.visible;
                hiddenInput.value = isVis ? '1' : '0';
                if (toggleBox) toggleBox.checked = isVis;

                if (badge) {
                    badge.className = isVis ? 'badge badge-green' : 'badge badge-gold';
                    badge.textContent = isVis ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE';
                }
                if (btn) {
                    btn.className = 'btn-toggle-scoreboard ' + (isVis ? 'btn-toggle-hide' : 'btn-toggle-publish');
                    if (btnIcon) btnIcon.textContent = isVis ? '✕' : '✓';
                    if (btnText) btnText.textContent = isVis ? 'Hide Scoreboard from Homepage' : 'Publish Scoreboard on Homepage';
                }
                if (feedback) {
                    feedback.className = 'instant-feedback-toast ' + (isVis ? 'success-live' : 'success-hidden');
                    feedback.style.background = '';
                    feedback.style.border = '';
                    feedback.innerHTML = isVis
                        ? '✓ <strong>Published!</strong> Scoreboard is now live on the homepage.'
                        : '✓ <strong>Hidden!</strong> Scoreboard is now hidden from the homepage.';
                    setTimeout(function() {
                        feedback.style.opacity = '0';
                        setTimeout(function() { feedback.style.display = 'none'; feedback.style.opacity = '1'; }, 500);
                    }, 3500);
                }
            } else {
                alert(data.error || 'Failed to update visibility.');
                if (feedback) feedback.style.display = 'none';
                if (toggleBox) toggleBox.checked = currentVal;
            }
        })
        .catch(function(err) {
            console.error('Error toggling scoreboard:', err);
            alert('Could not update scoreboard visibility. Please try again.');
            if (feedback) feedback.style.display = 'none';
            if (toggleBox) toggleBox.checked = currentVal;
        })
        .finally(function() {
            if (btn) btn.disabled = false;
            if (toggleBox) toggleBox.disabled = false;
        });
    }

    function toggleCountdownVisibility(forcedVal) {
        var hiddenInput = document.getElementById('countdown_enabled_input');
        if (!hiddenInput) return;
        var currentVal = hiddenInput.value === '1';
        var targetVal = (typeof forcedVal === 'boolean') ? forcedVal : !currentVal;

        var btn = document.getElementById('instantCountdownToggleBtn');
        var btnText = document.getElementById('instantCountdownToggleBtnText');
        var btnIcon = document.getElementById('instantCountdownToggleBtnIcon');
        var toggleBox = document.getElementById('countdown_toggle_switch');
        var badge = document.getElementById('countdownLiveBadge');
        var feedback = document.getElementById('countdownInstantFeedback');

        if (btn) btn.disabled = true;
        if (toggleBox) toggleBox.disabled = true;

        if (feedback) {
            feedback.style.display = 'inline-flex';
            feedback.className = 'instant-feedback-toast';
            feedback.style.background = '#EDE6D2';
            feedback.style.color = 'var(--ink)';
            feedback.style.border = '1px solid var(--rule)';
            feedback.style.opacity = '1';
            feedback.innerHTML = '⏳ Updating countdown visibility...';
        }

        var formData = new FormData();
        formData.append('action', 'toggle_countdown_visibility');
        formData.append('visible', targetVal ? '1' : '0');

        fetch('admin.php', { method: 'POST', body: formData })
        .then(function(res) { if (!res.ok) throw new Error('HTTP error ' + res.status); return res.json(); })
        .then(function(data) {
            if (data.success) {
                var isVis = !!data.visible;
                hiddenInput.value = isVis ? '1' : '0';
                if (toggleBox) toggleBox.checked = isVis;

                if (badge) {
                    badge.className = isVis ? 'badge badge-green' : 'badge badge-gold';
                    badge.textContent = isVis ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE';
                }
                if (btn) {
                    btn.className = 'btn-toggle-scoreboard ' + (isVis ? 'btn-toggle-hide' : 'btn-toggle-publish');
                    if (btnIcon) btnIcon.textContent = isVis ? '✕' : '✓';
                    if (btnText) btnText.textContent = isVis ? 'Hide Countdown from Homepage' : 'Publish Countdown on Homepage';
                }
                if (feedback) {
                    feedback.className = 'instant-feedback-toast ' + (isVis ? 'success-live' : 'success-hidden');
                    feedback.style.background = '';
                    feedback.style.border = '';
                    feedback.innerHTML = isVis
                        ? '✓ <strong>Published!</strong> Countdown is now live on the homepage.'
                        : '✓ <strong>Hidden!</strong> Countdown is now hidden from the homepage.';
                    setTimeout(function() {
                        feedback.style.opacity = '0';
                        setTimeout(function() { feedback.style.display = 'none'; feedback.style.opacity = '1'; }, 500);
                    }, 3500);
                }
            } else {
                alert(data.error || 'Failed to update countdown visibility.');
                if (feedback) feedback.style.display = 'none';
                if (toggleBox) toggleBox.checked = currentVal;
            }
        })
        .catch(function(err) {
            console.error('Error toggling countdown:', err);
            alert('Could not update countdown visibility. Please try again.');
            if (feedback) feedback.style.display = 'none';
            if (toggleBox) toggleBox.checked = currentVal;
        })
        .finally(function() {
            if (btn) btn.disabled = false;
            if (toggleBox) toggleBox.disabled = false;
        });
    }

    function toggleRegistrationStatus(forcedVal) {
        var hiddenInput = document.getElementById('registration_open_input');
        if (!hiddenInput) return;
        var currentVal = hiddenInput.value === '1';
        var targetVal = (typeof forcedVal === 'boolean') ? forcedVal : !currentVal;

        var btn = document.getElementById('instantRegToggleBtn');
        var btnText = document.getElementById('instantRegToggleBtnText');
        var btnIcon = document.getElementById('instantRegToggleBtnIcon');
        var toggleBox = document.getElementById('registration_toggle_switch');
        var badge = document.getElementById('regLiveBadge');
        var feedback = document.getElementById('regInstantFeedback');

        if (btn) btn.disabled = true;
        if (toggleBox) toggleBox.disabled = true;

        if (feedback) {
            feedback.style.display = 'inline-flex';
            feedback.className = 'instant-feedback-toast';
            feedback.style.background = '#EDE6D2';
            feedback.style.color = 'var(--ink)';
            feedback.style.border = '1px solid var(--rule)';
            feedback.style.opacity = '1';
            feedback.innerHTML = '⏳ Updating registration status...';
        }

        var formData = new FormData();
        formData.append('action', 'toggle_registration_status');
        formData.append('open', targetVal ? '1' : '0');

        fetch('admin.php', { method: 'POST', body: formData })
        .then(function(res) { if (!res.ok) throw new Error('HTTP error ' + res.status); return res.json(); })
        .then(function(data) {
            if (data.success) {
                var isOpen = !!data.open;
                hiddenInput.value = isOpen ? '1' : '0';
                if (toggleBox) toggleBox.checked = isOpen;

                if (badge) {
                    badge.className = isOpen ? 'badge badge-green' : 'badge';
                    badge.style.background = isOpen ? '' : '#FFEBEE';
                    badge.style.color = isOpen ? '' : 'var(--red)';
                    badge.style.borderColor = isOpen ? '' : '#EF9A9A';
                    badge.textContent = isOpen ? '● REGISTRATION OPEN' : '🔒 REGISTRATION CLOSED';
                }
                if (btn) {
                    btn.className = 'btn-toggle-scoreboard ' + (isOpen ? 'btn-toggle-hide' : 'btn-toggle-publish');
                    if (btnIcon) btnIcon.textContent = isOpen ? '🔒' : '🔓';
                    if (btnText) btnText.textContent = isOpen ? 'Close Registration (Stop Submissions)' : 'Re-open Registration (Accept Submissions)';
                }
                if (feedback) {
                    feedback.className = 'instant-feedback-toast ' + (isOpen ? 'success-live' : 'success-hidden');
                    feedback.style.background = '';
                    feedback.style.border = '';
                    feedback.innerHTML = isOpen
                        ? '✓ <strong>Opened!</strong> Team registration is now open on the homepage.'
                        : '🔒 <strong>Closed!</strong> Registration is now closed on the homepage.';
                    setTimeout(function() {
                        feedback.style.opacity = '0';
                        setTimeout(function() { feedback.style.display = 'none'; feedback.style.opacity = '1'; }, 500);
                    }, 3500);
                }
            } else {
                alert(data.error || 'Failed to update registration status.');
                if (feedback) feedback.style.display = 'none';
                if (toggleBox) toggleBox.checked = currentVal;
            }
        })
        .catch(function(err) {
            console.error('Error toggling registration:', err);
            alert('Could not update registration status. Please try again.');
            if (feedback) feedback.style.display = 'none';
            if (toggleBox) toggleBox.checked = currentVal;
        })
        .finally(function() {
            if (btn) btn.disabled = false;
            if (toggleBox) toggleBox.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('instantToggleBtn');
        var toggleBox = document.getElementById('scoreboard_toggle_switch');
        if (btn) {
            btn.addEventListener('click', function(e) { e.preventDefault(); toggleScoreboardVisibility(); });
        }
        if (toggleBox) {
            toggleBox.addEventListener('change', function() { toggleScoreboardVisibility(this.checked); });
        }

        var cdBtn = document.getElementById('instantCountdownToggleBtn');
        var cdBox = document.getElementById('countdown_toggle_switch');
        if (cdBtn) {
            cdBtn.addEventListener('click', function(e) { e.preventDefault(); toggleCountdownVisibility(); });
        }
        if (cdBox) {
            cdBox.addEventListener('change', function() { toggleCountdownVisibility(this.checked); });
        }

        var regBtn = document.getElementById('instantRegToggleBtn');
        var regBox = document.getElementById('registration_toggle_switch');
        if (regBtn) {
            regBtn.addEventListener('click', function(e) { e.preventDefault(); toggleRegistrationStatus(); });
        }
        if (regBox) {
            regBox.addEventListener('change', function() { toggleRegistrationStatus(this.checked); });
        }
        
        // Maintain scroll position after form submissions/page reloads
        var scrollpos = sessionStorage.getItem('scrollpos');
        if (scrollpos) {
            window.scrollTo(0, scrollpos);
            sessionStorage.removeItem('scrollpos');
        }
    });

    window.addEventListener("beforeunload", function(e) {
        sessionStorage.setItem('scrollpos', window.scrollY);
    });
    </script>
</body>
</html>
