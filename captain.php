<?php
/**
 * Captain Portal — FOT Knights Arena
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$pdo = get_db_connection();
$error_msg = '';
$success_msg = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['captain_team_id']);
    unset($_SESSION['captain_team_name']);
    session_destroy();
    header('Location: captain.php');
    exit;
}

// Handle Login POST
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $team_name = trim($_POST['team_name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM `teams` WHERE `team_name` = ?");
        $stmt->execute([$team_name]);
        $team = $stmt->fetch();

        if ($team && $team['password_hash'] && password_verify($password, $team['password_hash'])) {
            $_SESSION['captain_team_id'] = $team['id'];
            $_SESSION['captain_team_name'] = $team['team_name'];
            header('Location: captain.php');
            exit;
        } else {
            $error_msg = 'Invalid team name or password. Please try again.';
        }
    } else {
        $error_msg = 'Database connection error.';
    }
}

$is_authenticated = !empty($_SESSION['captain_team_id']);

if ($is_authenticated && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_action'])) {
    $team_id = $_SESSION['captain_team_id'];
    $round_number = (int)$_POST['round_number'];
    $dropped_members = $_POST['dropped_members'] ?? [];

    $stmt_round = $pdo->prepare("SELECT * FROM `rounds` WHERE `round_number` = ? AND `status` = 'open'");
    $stmt_round->execute([$round_number]);
    $round = $stmt_round->fetch();

    if (!$round) {
        $error_msg = 'This round is not currently open.';
    } else {
        date_default_timezone_set('Asia/Colombo');
        if ($round['deadline'] && strtotime($round['deadline']) < time()) {
            $error_msg = 'The deadline for this round has passed.';
        } else {
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `members` WHERE `team_id` = ?");
            $stmt_count->execute([$team_id]);
            $total_members = (int)$stmt_count->fetchColumn();

            $required_drops = $total_members - 4;

            if (count($dropped_members) !== $required_drops) {
                $error_msg = "You must select exactly $required_drops member(s) to drop.";
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?")->execute([$team_id, $round_number]);

                    $stmt_drop = $pdo->prepare("INSERT INTO `drop_boards` (`team_id`, `round_number`, `member_id`) VALUES (?, ?, ?)");
                    foreach ($dropped_members as $m_id) {
                        $stmt_drop->execute([$team_id, $round_number, $m_id]);
                    }
                    $pdo->commit();
                    $success_msg = 'Drop boards successfully saved for Round ' . $round_number . '.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_msg = 'Failed to save drop boards: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch team info
$team_info = null;
$members = [];
$active_round = null;
$drops = [];

if ($is_authenticated && $pdo) {
    $stmt_team = $pdo->prepare("SELECT * FROM `teams` WHERE `id` = ?");
    $stmt_team->execute([$_SESSION['captain_team_id']]);
    $team_info = $stmt_team->fetch();

    $stmt_members = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
    $stmt_members->execute([$_SESSION['captain_team_id']]);
    $members = $stmt_members->fetchAll();

    $stmt_active_round = $pdo->query("SELECT * FROM `rounds` WHERE `status` = 'open' ORDER BY `round_number` ASC LIMIT 1");
    $active_round = $stmt_active_round->fetch();

    if ($active_round) {
        $stmt_drops = $pdo->prepare("SELECT member_id FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
        $stmt_drops->execute([$_SESSION['captain_team_id'], $active_round['round_number']]);
        $drops = $stmt_drops->fetchAll(PDO::FETCH_COLUMN);
    }
}

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain Portal - FOT Knights Arena</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
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
        
        /* Mobile Specific Overrides */
        @media (max-width: 768px) {
            .card { padding: 1rem; box-shadow: 2px 2px 0 var(--ink); }
            main { padding-top: 2rem !important; }
            .team-header-row { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="page-rail header-inner">
            <a href="index.php" class="header-brand">
                <div class="header-brand-text">
                    University of Ruhuna
                    <span>Faculty of Technology</span>
                </div>
            </a>
            <nav class="header-nav">
                <a href="index.php">Public Site</a>
                <span class="header-nav-sep">·</span>
                <a href="captain.php" style="color: var(--blue); font-weight: 600;">Captain Portal</a>
                <span class="header-nav-sep">·</span>
                <a href="admin.php" style="color: var(--red); font-weight: 600;">Admin</a>
            </nav>
        </div>
    </header>

    <main style="padding-top: 6rem; min-height: 80vh;">
        <div class="page-rail">
            <?php if ($error_msg): ?>
                <div style="background: #fee; color: #c00; padding: 1rem; border: 1px solid #fcc; margin-bottom: 1.5rem; font-weight: bold;">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($success_msg): ?>
                <div style="background: #efe; color: #090; padding: 1rem; border: 1px solid #cfc; margin-bottom: 1.5rem; font-weight: bold;">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!$is_authenticated): ?>
                <div class="card" style="max-width: 500px; margin: 2rem auto;">
                    <h1 class="section-title">Captain Portal</h1>
                    <p style="margin-bottom: 1.5rem; color: var(--ink-light);">Log in to manage your team's roster and drop boards.</p>
                    <form method="POST">
                        <input type="hidden" name="login_action" value="1">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight: bold; font-family: var(--font-display);">Team Name</label>
                            <input type="text" name="team_name" class="form-control" required>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="font-weight: bold; font-family: var(--font-display);">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%;">Log In</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="team-header-row" style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.5rem;">
                    <h1 class="section-title">Team: <?= htmlspecialchars($team_info['team_name']) ?></h1>
                    <a href="?action=logout" style="color: var(--red); font-weight: bold;">Log Out →</a>
                </div>

                <div class="portal-nav">
                    <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Roster Dashboard</a>
                    <a href="?page=dropboards" class="<?= $page === 'dropboards' ? 'active' : '' ?>">Drop Boards Management</a>
                </div>

                <?php if ($page === 'dashboard'): ?>
                    <div class="card">
                        <h2 style="font-size: 1.5rem; margin-bottom: 1rem; font-family: var(--font-display);">Squad Roster</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Name</th>
                                        <th>Reg Number</th>
                                        <th>Gender</th>
                                        <th>Batch</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $m): ?>
                                        <tr>
                                            <td><?= $m['member_order'] ?></td>
                                            <td><?= htmlspecialchars($m['name']) ?> <?= $m['is_captain'] ? '<strong>(C)</strong>' : '' ?></td>
                                            <td><?= htmlspecialchars($m['reg_number']) ?></td>
                                            <td><?= $m['gender'] ?></td>
                                            <td><?= htmlspecialchars($m['batch_year']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($page === 'dropboards'): ?>
                    <div class="card">
                        <?php if ($active_round): ?>
                            <?php
                            date_default_timezone_set('Asia/Colombo');
                            $is_open = true;
                            if ($active_round['deadline'] && strtotime($active_round['deadline']) < time()) {
                                $is_open = false;
                            }
                            $required_drops = count($members) - 4;
                            ?>
                            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; font-family: var(--font-display);">Round <?= (int)$active_round['round_number'] ?> Drop Boards</h2>
                            <p style="color: var(--ink-light); margin-bottom: 1.5rem;">Deadline: <?= $active_round['deadline'] ? date('Y-m-d h:i A', strtotime($active_round['deadline'])) : 'None' ?></p>
                            
                            <?php if ($is_open && $required_drops > 0): ?>
                                <p style="margin-bottom: 1rem; font-weight: bold;">Action Required: Select exactly <?= $required_drops ?> member(s) to drop for this round.</p>
                                <form method="POST">
                                    <input type="hidden" name="drop_action" value="1">
                                    <input type="hidden" name="round_number" value="<?= $active_round['round_number'] ?>">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th style="text-align: center; width: 60px;">Drop</th>
                                                    <th>Order</th>
                                                    <th>Name</th>
                                                    <th>Reg Number</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($members as $m): ?>
                                                    <tr>
                                                        <td style="text-align: center;">
                                                            <input type="checkbox" name="dropped_members[]" value="<?= $m['id'] ?>" <?= in_array($m['id'], $drops) ? 'checked' : '' ?> style="transform: scale(1.2);">
                                                        </td>
                                                        <td><?= $m['member_order'] ?></td>
                                                        <td><?= htmlspecialchars($m['name']) ?></td>
                                                        <td><?= htmlspecialchars($m['reg_number']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn-primary">Save Drop Boards</button>
                                </form>
                            <?php elseif ($is_open && $required_drops === 0): ?>
                                <p>Your team has exactly 4 members. No drops are necessary.</p>
                            <?php else: ?>
                                <p style="color: var(--red); font-weight:bold; margin-bottom: 1rem;">The deadline for this round has passed. Drop boards are locked.</p>
                                <?php
                                    $actual_drops = $drops;
                                    $playing_count = 0;
                                    foreach ($members as $m) {
                                        if (!in_array($m['id'], $actual_drops) && $playing_count < 4) {
                                            $playing_count++;
                                        } elseif (!in_array($m['id'], $actual_drops)) {
                                            $actual_drops[] = $m['id'];
                                        }
                                    }
                                ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Order</th>
                                                <th>Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members as $m): ?>
                                                <tr>
                                                    <td style="font-weight: bold; color: <?= in_array($m['id'], $actual_drops) ? 'var(--red)' : 'var(--blue)' ?>;">
                                                        <?= in_array($m['id'], $actual_drops) ? 'Dropped' : 'Playing' ?>
                                                    </td>
                                                    <td><?= $m['member_order'] ?></td>
                                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>There are no active rounds at the moment.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="page-rail footer-inner">
            <div class="footer-info">
                <strong>Contact the Organizers</strong>
                Chess Society, Faculty of Technology<br>
                University of Ruhuna, Kamburupitiya
            </div>
            <div style="text-align: right;">
                <div class="footer-colophon" style="margin-top: 1.25rem;">
                    © 2026 Faculty of Technology, University of Ruhuna · <a href="captain.php" style="color: var(--ink-light); text-decoration: underline;">Captain Portal</a> · <a href="admin.php" style="color: var(--ink-light); text-decoration: underline;">Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
