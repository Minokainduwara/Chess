<?php
/**
 * Captain Portal Front Controller
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/src/Services/AuthService.php';
require_once __DIR__ . '/src/Services/TeamService.php';
require_once __DIR__ . '/src/Services/DropBoardService.php';
require_once __DIR__ . '/src/Helpers/Flash.php';
require_once __DIR__ . '/src/Helpers/View.php';

AuthService::startSession();

$error_msg = Flash::getError();
$success_msg = Flash::getSuccess();

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthService::logoutCaptain();
    header('Location: captain.php');
    exit;
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $team_name = trim($_POST['team_name'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (AuthService::loginCaptain($team_name, $password)) {
        header('Location: captain.php');
        exit;
    } else {
        $error_msg = 'Invalid team name or password. Please try again.';
    }
}

$is_authenticated = AuthService::isCaptainLoggedIn();
$team_id = AuthService::getCaptainTeamId();
$team_name = AuthService::getCaptainTeamName();

// Handle Drop Boards POST
if ($is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_action'])) {
    $round_number = (int)$_POST['round_number'];
    $dropped_members = $_POST['dropped_members'] ?? [];

    $res = DropBoardService::saveCaptainDrops($team_id, $round_number, $dropped_members);
    if ($res['success']) {
        Flash::setSuccess($res['message']);
    } else {
        Flash::setError($res['message']);
    }
    header('Location: captain.php?page=dropboards');
    exit;
}

// Fetch team details and active round
$team_info = null;
$members = [];
$active_round = null;
$drops = [];

if ($is_authenticated && $team_id) {
    $team_data = TeamService::getTeamWithMembers($team_id);
    if ($team_data) {
        $team_info = $team_data;
        $members = $team_data['members'] ?? [];
    }

    $active_round = DropBoardService::getActiveOpenRound();
    if ($active_round) {
        $drops = DropBoardService::getDropsForTeamAndRound($team_id, (int)$active_round['round_number']);
    }
}

$page = $_GET['page'] ?? 'dashboard';

// Extra CSS styles for Captain Portal
$extra_head = '
<style>
    .portal-nav { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--checker); padding-bottom: 1rem; }
    .portal-nav a { font-weight: 600; font-size: 1.1rem; color: var(--ink-light); text-decoration: none; }
    .portal-nav a.active { color: var(--red); border-bottom: 2px solid var(--red); padding-bottom: 0.2rem; }
    .card { background: var(--card); border: 1px solid var(--ink); box-shadow: 4px 4px 0 var(--ink); padding: 2rem; margin-bottom: 2rem; overflow-wrap: break-word; }
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 1rem; }
    .table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .table th, .table td { padding: 0.75rem; border: 1px solid var(--checker); text-align: left; }
    .table th { background: var(--bg-alt); font-weight: bold; font-family: var(--font-display); }
    .btn-primary { background: var(--red); color: #fff; padding: 0.75rem 1.5rem; border: none; font-weight: bold; cursor: pointer; display: inline-block; white-space: nowrap; }
    .btn-primary:hover { background: var(--red-hover); }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--ink); box-sizing: border-box; margin-top: 0.25rem; font-size: 1rem; font-family: var(--font-ui); }
    @media (max-width: 768px) {
        .card { padding: 1rem; box-shadow: 2px 2px 0 var(--ink); }
        main { padding-top: 2rem !important; }
        .team-header-row { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
    }
</style>';

View::render('layouts/header', [
    'page_title'        => 'Captain Portal — FOT Knights Arena',
    'is_captain'        => $is_authenticated,
    'captain_team_name' => $team_name,
    'current_tab'       => ($page === 'dropboards') ? 'drops' : 'dashboard',
    'extra_head'        => $extra_head
]);
?>

<main style="padding-top: 6rem; min-height: 80vh;">
    <div class="page-rail">
        <?php if (!empty($error_msg)): ?>
            <div style="background: #fee; color: #c00; padding: 1rem; border: 1px solid #fcc; margin-bottom: 1.5rem; font-weight: bold;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div style="background: #efe; color: #090; padding: 1rem; border: 1px solid #cfc; margin-bottom: 1.5rem; font-weight: bold;">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_authenticated): ?>
            <?php View::render('captain/login'); ?>
        <?php else: ?>
            <div class="team-header-row" style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.5rem;">
                <h1 class="section-title">Team: <?= htmlspecialchars($team_info['team_name'] ?? '') ?></h1>
                <a href="?action=logout" style="color: var(--red); font-weight: bold;">Log Out →</a>
            </div>

            <div class="portal-nav">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Roster Dashboard</a>
                <a href="?page=dropboards" class="<?= $page === 'dropboards' ? 'active' : '' ?>">Drop Boards Management</a>
            </div>

            <?php if ($page === 'dashboard'): ?>
                <?php View::render('captain/dashboard', ['members' => $members]); ?>
            <?php elseif ($page === 'dropboards'): ?>
                <?php View::render('captain/drops', [
                    'active_round' => $active_round,
                    'members'      => $members,
                    'drops'        => $drops
                ]); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php
View::render('layouts/footer');
