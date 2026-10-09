<?php
/**
 * Captain Portal — FOT Knights Arena
 * University of Ruhuna, Faculty of Technology
 * Select drop boards for upcoming rounds
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
            // Count total members
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Captain Portal - FOT Knights Arena</title>
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; background: #f4f6f8; color: #333; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1, h2 { color: #1a1a1a; margin-top: 0; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-error { background: #fee; color: #c00; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #090; border: 1px solid #cfc; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: #fff; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #004494; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .table th { background: #f9f9f9; }
        .logout-btn { float: right; color: #c00; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!$is_authenticated): ?>
            <h1>Captain Portal Login</h1>
            <form method="POST">
                <input type="hidden" name="login_action" value="1">
                <div class="form-group">
                    <label>Team Name</label>
                    <input type="text" name="team_name" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Log In</button>
            </form>
        <?php else: ?>
            <a href="?action=logout" class="logout-btn">Log Out</a>
            <h1>Welcome, <?= htmlspecialchars($team_info['team_name']) ?> Captain</h1>
            <hr>
            
            <?php if ($active_round): ?>
                <?php
                date_default_timezone_set('Asia/Colombo');
                $is_open = true;
                if ($active_round['deadline'] && strtotime($active_round['deadline']) < time()) {
                    $is_open = false;
                }
                $required_drops = count($members) - 4;
                ?>
                <h2>Round <?= (int)$active_round['round_number'] ?> Drop Boards</h2>
                <p>Deadline: <?= $active_round['deadline'] ? date('Y-m-d h:i A', strtotime($active_round['deadline'])) : 'None' ?></p>
                
                <?php if ($is_open && $required_drops > 0): ?>
                    <p>Select exactly <strong><?= $required_drops ?></strong> member(s) to drop for this round.</p>
                    <form method="POST">
                        <input type="hidden" name="drop_action" value="1">
                        <input type="hidden" name="round_number" value="<?= $active_round['round_number'] ?>">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Drop</th>
                                    <th>Order</th>
                                    <th>Name</th>
                                    <th>Reg Number</th>
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $m): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="dropped_members[]" value="<?= $m['id'] ?>" <?= in_array($m['id'], $drops) ? 'checked' : '' ?>>
                                        </td>
                                        <td><?= $m['member_order'] ?></td>
                                        <td><?= htmlspecialchars($m['name']) ?> <?= $m['is_captain'] ? '(C)' : '' ?></td>
                                        <td><?= htmlspecialchars($m['reg_number']) ?></td>
                                        <td><?= $m['gender'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <br>
                        <button type="submit">Save Drop Boards</button>
                    </form>
                <?php elseif ($is_open && $required_drops === 0): ?>
                    <p>Your team has exactly 4 members. No drops are necessary.</p>
                <?php else: ?>
                    <p style="color:red; font-weight:bold;">The deadline for this round has passed. Drop boards are locked.</p>
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
                                    <td><?= in_array($m['id'], $actual_drops) ? '<span style="color:red">Dropped</span>' : '<span style="color:green">Playing</span>' ?></td>
                                    <td><?= $m['member_order'] ?></td>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <p>There are no active rounds at the moment.</p>
            <?php endif; ?>

            <h2 style="margin-top:40px;">Team Roster</h2>
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
                            <td><?= htmlspecialchars($m['name']) ?> <?= $m['is_captain'] ? '(Captain)' : '' ?></td>
                            <td><?= htmlspecialchars($m['reg_number']) ?></td>
                            <td><?= $m['gender'] ?></td>
                            <td><?= htmlspecialchars($m['batch_year']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
