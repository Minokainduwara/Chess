<?php
/**
 * Admin Panel — FOT Knights Arena
 * University of Ruhuna, Faculty of Technology
 * View, filter, export, and manage registered teams and rosters
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
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Login POST
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: admin.php');
            exit;
        } else {
            $error_msg = 'Invalid administrator credentials. Please try again.';
        }
    } else {
        $error_msg = 'Database connection error. Please verify MySQL service.';
    }
}

// Check if authenticated
$is_authenticated = !empty($_SESSION['admin_logged_in']);

// Handle Team Deletion
if ($is_authenticated && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team_id'])) {
    $del_id = (int)$_POST['delete_team_id'];
    if ($pdo && $del_id > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM `teams` WHERE `id` = ?");
        $stmt_del->execute([$del_id]);
        $success_msg = "Team #{$del_id} and its registered members were successfully removed.";
    }
}

// Handle AJAX Instant Toggle Scoreboard Visibility (without needing to save form)
if ($is_authenticated && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_scoreboard_visibility') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection unavailable']);
        exit;
    }

    if (isset($_POST['visible'])) {
        $new_visible = ($_POST['visible'] === '1' || $_POST['visible'] === 'true' || $_POST['visible'] === 1) ? '1' : '0';
    } else {
        $stmt_cur = $pdo->prepare("SELECT `setting_value` FROM `tournament_settings` WHERE `setting_key` = 'scoreboard_visible' LIMIT 1");
        $stmt_cur->execute();
        $cur_val = $stmt_cur->fetchColumn();
        $new_visible = ($cur_val === '1') ? '0' : '1';
    }

    $stmt_set = $pdo->prepare("INSERT INTO `tournament_settings` (`setting_key`, `setting_value`) VALUES ('scoreboard_visible', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    $stmt_set->execute([$new_visible]);

    echo json_encode([
        'success' => true,
        'visible' => ($new_visible === '1'),
        'message' => ($new_visible === '1') 
            ? 'Scoreboard published! It is now live on the homepage.' 
            : 'Scoreboard hidden! It is now removed from the homepage.'
    ]);
    exit;
}

// Handle AJAX Instant Toggle Countdown Visibility (mirrors scoreboard toggle)
if ($is_authenticated && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_countdown_visibility') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection unavailable']);
        exit;
    }

    if (isset($_POST['visible'])) {
        $new_visible = ($_POST['visible'] === '1' || $_POST['visible'] === 'true' || $_POST['visible'] === 1) ? '1' : '0';
    } else {
        $stmt_cur = $pdo->prepare("SELECT `setting_value` FROM `tournament_settings` WHERE `setting_key` = 'countdown_enabled' LIMIT 1");
        $stmt_cur->execute();
        $cur_val = $stmt_cur->fetchColumn();
        $new_visible = ($cur_val === '1') ? '0' : '1';
    }

    $stmt_set = $pdo->prepare("INSERT INTO `tournament_settings` (`setting_key`, `setting_value`) VALUES ('countdown_enabled', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    $stmt_set->execute([$new_visible]);

    echo json_encode([
        'success' => true,
        'visible' => ($new_visible === '1'),
        'message' => ($new_visible === '1')
            ? 'Countdown published! It is now live on the homepage.'
            : 'Countdown hidden! It is now removed from the homepage.'
    ]);
    exit;
}

// Handle Standings & Scoreboard Update POST
if ($is_authenticated && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_standings_action'])) {
    if ($pdo) {
        $sb_visible = (isset($_POST['scoreboard_visible']) && ($_POST['scoreboard_visible'] === '1' || $_POST['scoreboard_visible'] === 'on')) ? '1' : '0';
        $sb_status  = trim($_POST['scoreboard_status'] ?? '');

        $stmt_set = $pdo->prepare("INSERT INTO `tournament_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
        $stmt_set->execute(['scoreboard_visible', $sb_visible]);
        $stmt_set->execute(['scoreboard_status', $sb_status]);

        // ── Countdown settings ──
        $cd_visible = (isset($_POST['countdown_enabled']) && ($_POST['countdown_enabled'] === '1' || $_POST['countdown_enabled'] === 'on')) ? '1' : '0';
        $cd_raw     = trim($_POST['countdown_target'] ?? '');

        // datetime-local posts "Y-m-d H:i" with no timezone; normalise to the
        // stored venue-local format. Reject anything unparseable so a typo can
        // never wipe a working countdown.
        $cd_target = null;
        if ($cd_raw !== '') {
            try {
                $cd_dt = new DateTime($cd_raw, new DateTimeZone('Asia/Colombo'));
                $cd_target = $cd_dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $cd_target = null;
            }
        }
        if ($cd_target !== null) {
            $stmt_set->execute(['countdown_target', $cd_target]);
        }
        $stmt_set->execute(['countdown_enabled', $cd_visible]);

        if (isset($_POST['scores']) && is_array($_POST['scores'])) {
            $stmt_score = $pdo->prepare("
                UPDATE `teams` 
                SET `played` = ?, `won` = ?, `drawn` = ?, `lost` = ?, `game_points` = ?, `match_points` = ?, `standing_notes` = ?
                WHERE `id` = ?
            ");
            foreach ($_POST['scores'] as $team_id => $sc) {
                $p     = (int)($sc['played'] ?? 0);
                $w     = (int)($sc['won'] ?? 0);
                $d     = (int)($sc['drawn'] ?? 0);
                $l     = (int)($sc['lost'] ?? 0);
                $gp    = (float)($sc['game_points'] ?? 0.0);
                $mp    = (int)($sc['match_points'] ?? 0);
                $notes = trim($sc['standing_notes'] ?? '');

                $stmt_score->execute([$p, $w, $d, $l, $gp, $mp, $notes, (int)$team_id]);
            }
        }
        $success_msg = "Scoreboard standings and tournament status updated successfully!";
    }
}

// Handle CSV Export
if ($is_authenticated && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if ($pdo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=chess_tournament_teams_' . date('Y-m-d_His') . '.csv');

        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8 support
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        fputcsv($output, [
            'Team ID',
            'Team Name',
            'Contact Phone',
            'Contact Email',
            'Registered At',
            'Member Order',
            'Member Name',
            'Registration Number',
            'Gender',
            'Batch Year',
            'Is Captain'
        ]);

        $query = "
            SELECT 
                t.id AS team_id,
                t.team_name,
                t.contact_phone,
                t.contact_email,
                t.created_at,
                m.member_order,
                m.name AS member_name,
                m.reg_number,
                m.gender,
                m.batch_year,
                m.is_captain
            FROM `teams` t
            JOIN `members` m ON t.id = m.team_id
            ORDER BY t.id ASC, m.member_order ASC
        ";
        $stmt_export = $pdo->query($query);
        while ($row = $stmt_export->fetch()) {
            fputcsv($output, [
                $row['team_id'],
                $row['team_name'],
                $row['contact_phone'] ?? 'N/A',
                $row['contact_email'] ?? 'N/A',
                $row['created_at'],
                $row['member_order'],
                $row['member_name'],
                $row['reg_number'],
                $row['gender'],
                $row['batch_year'],
                $row['is_captain'] ? 'Captain' : 'Player'
            ]);
        }
        fclose($output);
        exit;
    }
}

// Fetch dashboard data if authenticated
$teams_data = [];
$total_teams = 0;
$total_players = 0;
$total_females = 0;
$total_males = 0;
$batch_counts = [];

// Countdown defaults (overwritten from DB when authenticated)
$countdown_enabled = true;
$countdown_target  = '2026-10-10 07:00:00';

if ($is_authenticated && $pdo) {
    // Stats
    $total_teams = (int)$pdo->query("SELECT COUNT(*) FROM `teams`")->fetchColumn();
    $total_players = (int)$pdo->query("SELECT COUNT(*) FROM `members`")->fetchColumn();
    $total_females = (int)$pdo->query("SELECT COUNT(*) FROM `members` WHERE `gender` = 'Female'")->fetchColumn();
    $total_males = (int)$pdo->query("SELECT COUNT(*) FROM `members` WHERE `gender` = 'Male'")->fetchColumn();

    $stmt_batches = $pdo->query("SELECT `batch_year`, COUNT(*) as cnt FROM `members` GROUP BY `batch_year` ORDER BY `batch_year` ASC");
    while ($b = $stmt_batches->fetch()) {
        $batch_counts[$b['batch_year']] = (int)$b['cnt'];
    }

    // Fetch tournament settings & standings
    $settings = [];
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM `tournament_settings`");
    while ($s = $stmt_set->fetch()) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
    $scoreboard_visible = ($settings['scoreboard_visible'] ?? '1') === '1';
    $scoreboard_status = $settings['scoreboard_status'] ?? 'Standings updated live after each round';

    // Countdown settings
    $countdown_enabled = ($settings['countdown_enabled'] ?? '1') === '1';
    $countdown_target  = $settings['countdown_target'] ?? '2026-10-10 07:00:00';

    $stmt_st = $pdo->query("
        SELECT id, team_name, played, won, drawn, lost, game_points, match_points, standing_notes 
        FROM `teams` 
        ORDER BY match_points DESC, game_points DESC, won DESC, id ASC
    ");
    $standings_teams = $stmt_st->fetchAll();

    // Search query if provided
    $search = trim($_GET['search'] ?? '');
    $filter_batch = trim($_GET['filter_batch'] ?? '');

    $sql = "
        SELECT 
            t.id, 
            t.team_name, 
            t.contact_phone, 
            t.contact_email, 
            t.created_at,
            t.played,
            t.won,
            t.drawn,
            t.lost,
            t.game_points,
            t.match_points,
            t.standing_notes,
            COUNT(m.id) AS member_count,
            SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) AS female_count,
            SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
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
        $sql .= " AND t.id IN (SELECT DISTINCT team_id FROM members WHERE batch_year = ?)";
        $params[] = $filter_batch;
    }

    $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";

    $stmt_teams = $pdo->prepare($sql);
    $stmt_teams->execute($params);
    $teams_list = $stmt_teams->fetchAll();

    // Fetch members for each team
    $stmt_team_members = $pdo->prepare("
        SELECT * FROM `members` 
        WHERE `team_id` = ? 
        ORDER BY `member_order` ASC
    ");

    foreach ($teams_list as $t) {
        $stmt_team_members->execute([$t['id']]);
        $t['members'] = $stmt_team_members->fetchAll();
        $teams_data[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FOT Knights Arena — Tournament Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    <style>
        /* ── Typography & Base (Matching Informator Theme) ── */
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

        :root {
            --cream: #F5F0E0;
            --cream-card: #FAF7EE;
            --cream-dark: #EDE5D0;
            --ink: #1A1A1A;
            --ink-muted: #5A544C;
            --red: #C0392B;
            --red-hover: #A03020;
            --green: #2E7D32;
            --blue: #1565C0;
            --rule: #C8C0AD;
            --rule-light: #E0D9C8;
            --font-display: 'Cormorant Garamond', 'Garamond', serif;
            --font-body: 'EB Garamond', 'Georgia', serif;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
            line-height: 1.6;
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* ── Header ── */
        .admin-header {
            border-bottom: 2px solid var(--ink);
            padding: 1.25rem 0;
            background: var(--cream);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .admin-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: var(--ink);
        }
        .admin-brand-icon {
            width: 38px;
            height: 38px;
            background: var(--ink);
            color: var(--cream);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .admin-brand-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.25rem;
            line-height: 1.1;
            letter-spacing: 0.02em;
        }
        .admin-brand-subtitle {
            font-size: 0.85rem;
            color: var(--ink-muted);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .admin-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .btn-link {
            font-family: var(--font-display);
            font-size: 1rem;
            text-decoration: none;
            color: var(--ink);
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--rule);
            transition: all 0.2s ease;
        }
        .btn-link:hover {
            border-color: var(--ink);
            background: var(--cream-dark);
        }
        .btn-red {
            background: var(--red);
            color: var(--cream);
            border: 1px solid var(--red);
        }
        .btn-red:hover {
            background: var(--red-hover);
            color: var(--cream);
        }

        /* ── Alerts ── */
        .alert {
            padding: 1rem 1.25rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--ink);
            background: var(--cream-card);
            font-size: 1rem;
        }
        .alert-error {
            border-left-color: var(--red);
            background: #FDF2F0;
            color: #922B21;
        }
        .alert-success {
            border-left-color: var(--green);
            background: #F1F8F1;
            color: #1E6B23;
        }

        /* ── Login View ── */
        .login-wrap {
            max-width: 440px;
            margin: 4.5rem auto;
            background: var(--cream-card);
            border: 2px solid var(--ink);
            padding: 2.5rem 2rem;
            box-shadow: 6px 6px 0px rgba(26,26,26,0.1);
        }
        .login-title {
            font-family: var(--font-display);
            font-size: 2rem;
            line-height: 1.1;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .login-subtitle {
            text-align: center;
            color: var(--ink-muted);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.35rem;
        }
        .form-label {
            display: block;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--rule);
            background: #FFFFFF;
            font-family: var(--font-body);
            font-size: 1.05rem;
            color: var(--ink);
            transition: border-color 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 2px rgba(192,57,43,0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: var(--red);
            color: var(--cream);
            border: none;
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-submit:hover {
            background: var(--red-hover);
        }

        /* ── Metrics Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: var(--cream-card);
            border: 1px solid var(--rule);
            padding: 1.25rem 1.5rem;
            border-top: 3px solid var(--ink);
        }
        .stat-card.stat-red { border-top-color: var(--red); }
        .stat-card.stat-green { border-top-color: var(--green); }
        .stat-card.stat-blue { border-top-color: var(--blue); }
        .stat-num {
            font-family: var(--font-display);
            font-size: 2.75rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.35rem;
        }
        .stat-label {
            font-family: var(--font-display);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.85rem;
            color: var(--ink-muted);
        }

        /* ── Action Toolbar ── */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin: 2rem 0 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--rule);
        }
        .search-form {
            display: flex;
            gap: 0.5rem;
            flex-grow: 1;
            max-width: 500px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--ink);
            background: var(--cream-card);
            border: 1px solid var(--ink);
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: var(--ink);
            color: var(--cream);
        }

        /* ── Team Cards ── */
        .team-card {
            background: var(--cream-card);
            border: 1px solid var(--rule);
            margin-bottom: 2rem;
            transition: border-color 0.2s ease;
        }
        .team-card:hover {
            border-color: var(--ink);
        }
        .team-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--rule-light);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--cream-dark);
        }
        .team-title-group {
            display: flex;
            align-items: baseline;
            gap: 0.85rem;
            flex-wrap: wrap;
        }
        .team-name {
            font-family: var(--font-display);
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--ink);
        }
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-radius: 2px;
        }
        .badge-green { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .badge-blue { background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; }
        .badge-female { background: #FCE4EC; color: #C2185B; border: 1px solid #F48FB1; font-weight: 700; }
        .badge-male { background: #ECEFF1; color: #455A64; border: 1px solid #CFD8DC; }
        .badge-captain { background: #FFF8E1; color: #F57F17; border: 1px solid #FFE082; font-weight: 700; }
        .badge-score { background: #FFF9C4; color: #5D4037; border: 1px solid #FFE082; font-weight: 700; font-size: 0.85rem; }
        input.score-input {
            width: 65px;
            text-align: center;
            font-size: 1.15rem;
            font-weight: 700;
            padding: 0.45rem 0.2rem;
            border: 1px solid var(--rule);
            background: #FFFFFF;
            border-radius: 2px;
            color: var(--ink);
            -moz-appearance: textfield;
            display: inline-block;
        }
        input.score-input:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.15);
        }
        input.score-input::-webkit-outer-spin-button,
        input.score-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .team-meta {
            font-size: 0.9rem;
            color: var(--ink-muted);
            display: flex;
            gap: 1.25rem;
            align-items: center;
        }

        /* ── Rosters Table ── */
        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .roster-table th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            background: var(--cream-card);
            border-bottom: 1px solid var(--rule);
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-muted);
        }
        .roster-table td {
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--rule-light);
        }
        .roster-table tr:last-child td {
            border-bottom: none;
        }
        .roster-table tr:hover td {
            background: #FFFDF8;
        }

        .team-card-footer {
            padding: 0.75rem 1.5rem;
            background: var(--cream);
            border-top: 1px solid var(--rule-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--ink-muted);
        }

        /* ── Delete Button ── */
        .btn-delete {
            background: none;
            border: 1px solid #E57373;
            color: #C62828;
            padding: 0.25rem 0.65rem;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: var(--font-display);
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: #FFEBEE;
            border-color: #C62828;
        }

        /* ── Instant Scoreboard Toggle Styles ── */
        .publish-control-box {
            background: #FFFFFF;
            border: 1px solid var(--rule);
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .btn-toggle-scoreboard {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            text-decoration: none;
        }
        .btn-toggle-publish {
            background: #1E6B23;
            color: #FFFFFF;
            border-color: #1E6B23;
        }
        .btn-toggle-publish:hover {
            background: #16531A;
            border-color: #16531A;
        }
        .btn-toggle-hide {
            background: #FAF7EE;
            color: var(--red);
            border-color: var(--red);
        }
        .btn-toggle-hide:hover {
            background: var(--red);
            color: #FFFFFF;
        }
        .btn-toggle-scoreboard:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* iOS / Material style toggle switch */
        .switch-toggle-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }
        .switch-control {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .switch-control input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #D3CABA;
            border: 1px solid var(--rule);
            transition: 0.25s;
            border-radius: 26px;
        }
        .switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.25s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .switch-control input:checked + .switch-slider {
            background-color: #1E6B23;
            border-color: #1E6B23;
        }
        .switch-control input:checked + .switch-slider:before {
            transform: translateX(22px);
        }
        .instant-feedback-toast {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 2px;
            transition: opacity 0.3s ease;
        }
        .instant-feedback-toast.success-live {
            background: #E8F5E9;
            color: #1E6B23;
            border: 1px solid #A5D6A7;
        }
        .instant-feedback-toast.success-hidden {
            background: #FFF8E1;
            color: #8A5D00;
            border: 1px solid #FFE082;
        }

        /* ── Print Styles ── */
        @media print {
            .admin-header, .toolbar, .btn-link, .btn-delete, .search-form {
                display: none !important;
            }
            body {
                background: #FFF !important;
                color: #000 !important;
                padding: 0 !important;
            }
            .team-card {
                border: 1px solid #000 !important;
                break-inside: avoid;
                margin-bottom: 1.5rem !important;
            }
            .team-card-header {
                background: #EEE !important;
            }
        }
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
                    <a href="admin.php?action=export_csv" class="btn-link" title="Export CSV for Excel">📥 Export CSV</a>
                    <a href="admin.php?action=logout" class="btn-link btn-red">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <strong>Attention:</strong> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_authenticated): ?>
            <!-- ═══ LOGIN SCREEN ═══ -->
            <div class="login-wrap">
                <div style="text-align: center; font-size: 2.5rem; margin-bottom: 0.5rem;">♕</div>
                <h1 class="login-title">Organizer Sign In</h1>
                <p class="login-subtitle">Enter administrator credentials to manage tournament rosters.</p>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="login_action" value="1">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-submit">Authenticate →</button>
                </form>
            </div>
        <?php else: ?>
            <!-- ═══ ADMIN DASHBOARD ═══ -->

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
            </div>

            <!-- ═══ SCOREBOARD & STANDINGS MANAGER ═══ -->
            <div class="team-card" id="scoreboard-manager" style="margin-top: 2rem; border-top: 3px solid var(--red);">
                <div class="team-card-header" style="background: #FAF7EE;">
                    <div style="display: flex; align-items: baseline; gap: 0.75rem; flex-wrap: wrap;">
                        <span style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--ink);">
                            ♛ Scoreboard & Standings Manager
                        </span>
                        <span class="badge badge-blue">Live Public Control</span>
                        <span class="badge badge-green">Round-Robin System</span>
                    </div>
                    <div>
                        <a href="index.php#standings" target="_blank" class="btn-link" style="font-size: 0.85rem;">
                            View on Homepage ↗
                        </a>
                    </div>
                </div>

                <form method="POST" action="admin.php" style="padding: 1.5rem;">
                    <input type="hidden" name="update_standings_action" value="1">

                    <!-- Hidden input to keep form submissions in sync with the instant toggle -->
                    <input type="hidden" id="scoreboard_visible_input" name="scoreboard_visible" value="<?= $scoreboard_visible ? '1' : '0' ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <!-- Instant Visibility Toggle Card -->
                        <div class="publish-control-box">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                                <div>
                                    <span style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--ink);">
                                        Homepage Scoreboard Visibility
                                    </span>
                                    <span id="scoreboardLiveBadge" class="badge <?= $scoreboard_visible ? 'badge-green' : 'badge-gold' ?>" style="margin-left: 0.4rem; vertical-align: middle;">
                                        <?= $scoreboard_visible ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE' ?>
                                    </span>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem;">
                                <button type="button" id="instantToggleBtn" class="btn-toggle-scoreboard <?= $scoreboard_visible ? 'btn-toggle-hide' : 'btn-toggle-publish' ?>">
                                    <span id="instantToggleBtnIcon" aria-hidden="true"><?= $scoreboard_visible ? '✕' : '✓' ?></span>
                                    <span id="instantToggleBtnText"><?= $scoreboard_visible ? 'Hide Scoreboard from Homepage' : 'Publish Scoreboard on Homepage' ?></span>
                                </button>

                                <label class="switch-toggle-wrap" title="Toggle scoreboard visibility instantly">
                                    <span class="switch-control">
                                        <input type="checkbox" id="scoreboard_toggle_switch" <?= $scoreboard_visible ? 'checked' : '' ?>>
                                        <span class="switch-slider"></span>
                                    </span>
                                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--ink);">Instant Switch</span>
                                </label>
                            </div>

                            <div id="instantFeedback" style="display: none;"></div>

                            <div style="font-size: 0.85rem; color: var(--ink-muted); line-height: 1.4;">
                                ⚡ <strong>Instant action:</strong> Clicking the button or flipping the switch immediately shows or hides the scoreboard on the homepage without needing to click "Save Standings".
                            </div>
                        </div>

                        <!-- Scoreboard Notice / Banner -->
                        <div style="background: var(--cream); border: 1px solid var(--rule-light); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label class="form-label" for="scoreboard_status" style="font-size: 0.85rem;">Scoreboard Status / Notice</label>
                                <input type="text" id="scoreboard_status" name="scoreboard_status" class="form-control" value="<?= htmlspecialchars($scoreboard_status) ?>" placeholder="e.g. Standings after Round 2">
                                <span style="font-size: 0.85rem; color: var(--ink-muted); display: block; margin-top: 0.35rem;">
                                    Public badge displayed directly above the standings on the homepage. Updated when saving standings below.
                                </span>
                            </div>
                            <div style="margin-top: 0.75rem; text-align: right;">
                                <a href="index.php#standings" target="_blank" class="btn-link" style="font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    Open Homepage Standings ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ COUNTDOWN CONTROLS ═══ -->
                    <div class="publish-control-box" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                            <div>
                                <span style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--ink);">
                                    ⏱ Homepage Countdown Timer
                                </span>
                                <span id="countdownLiveBadge" class="badge <?= $countdown_enabled ? 'badge-green' : 'badge-gold' ?>" style="margin-left: 0.4rem; vertical-align: middle;">
                                    <?= $countdown_enabled ? '● LIVE ON HOMEPAGE' : '○ HIDDEN FROM HOMEPAGE' ?>
                                </span>
                            </div>
                            <a href="index.php#countdown" target="_blank" class="btn-link" style="font-size: 0.85rem;">View on Homepage ↗</a>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem;">
                            <button type="button" id="instantCountdownToggleBtn" class="btn-toggle-scoreboard <?= $countdown_enabled ? 'btn-toggle-hide' : 'btn-toggle-publish' ?>">
                                <span id="instantCountdownToggleBtnIcon" aria-hidden="true"><?= $countdown_enabled ? '✕' : '✓' ?></span>
                                <span id="instantCountdownToggleBtnText"><?= $countdown_enabled ? 'Hide Countdown from Homepage' : 'Publish Countdown on Homepage' ?></span>
                            </button>

                            <label class="switch-toggle-wrap" title="Toggle countdown visibility instantly">
                                <span class="switch-control">
                                    <input type="checkbox" id="countdown_toggle_switch" <?= $countdown_enabled ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </span>
                                <span style="font-size: 0.9rem; font-weight: 600; color: var(--ink);">Instant Switch</span>
                            </label>
                        </div>

                        <div id="countdownInstantFeedback" style="display: none;"></div>

                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; margin-top: 0.75rem;">
                            <div>
                                <label class="form-label" for="countdown_target" style="font-size: 0.85rem;">Tournament Start (Date &amp; Time)</label>
                                <input type="datetime-local" id="countdown_target" name="countdown_target" class="form-control"
                                       value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($countdown_target))) ?>"
                                       style="max-width: 280px;">
                                <span style="font-size: 0.85rem; color: var(--ink-muted); display: block; margin-top: 0.35rem;">
                                    Countdown target in <strong>venue local time (Asia/Colombo, UTC+05:30)</strong>.<br>
                                    Current: <strong><?= htmlspecialchars(date('j M Y, g:i a', strtotime($countdown_target))) ?></strong>
                                    · <?= $countdown_enabled ? 'visible' : 'hidden' ?> on homepage.
                                    Takes effect when you save below.
                                </span>
                            </div>
                            <input type="hidden" id="countdown_enabled_input" name="countdown_enabled" value="<?= $countdown_enabled ? '1' : '0' ?>">
                        </div>
                    </div>

                    <?php if (empty($standings_teams)): ?>
                        <div class="alert" style="margin: 1rem 0; text-align: center;">
                            No teams have registered yet. As soon as squads register, they will appear here to enter match scores.
                        </div>
                    <?php else: ?>
                        <!-- 1. Live Standings Summary View -->
                        <div style="background: #FFF; border: 1px solid var(--rule); padding: 1.25rem; margin-bottom: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--ink);">
                                    📊 Current Standings Breakdown (Live Values)
                                </h3>
                                <span style="font-size: 0.85rem; color: var(--ink-muted);">
                                    Ranked by Match Points (MP) → Game Points (GP) → Wins (W)
                                </span>
                            </div>
                            <div style="overflow-x: auto;">
                                <table class="roster-table" style="width: 100%; border: 1px solid var(--rule-light);">
                                    <thead>
                                        <tr style="background: #EDE6D2;">
                                            <th style="width: 50px;">Rank</th>
                                            <th>Team Name</th>
                                            <th style="width: 75px; text-align: center;">Played (P)</th>
                                            <th style="width: 75px; text-align: center;">Won (W)</th>
                                            <th style="width: 75px; text-align: center;">Drawn (D)</th>
                                            <th style="width: 75px; text-align: center;">Lost (L)</th>
                                            <th style="width: 85px; text-align: center;">Game Pts (GP)</th>
                                            <th style="width: 85px; text-align: center;">Match Pts (MP)</th>
                                            <th>Status / Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($standings_teams as $idx => $st): ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--red); font-size: 1.15rem; text-align: center;">
                                                    #<?= $idx + 1 ?>
                                                </td>
                                                <td style="font-weight: 700; font-size: 1.1rem;">
                                                    <?= htmlspecialchars($st['team_name']) ?>
                                                </td>
                                                <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                                    <?= (int)$st['played'] ?>
                                                </td>
                                                <td style="text-align: center; font-weight: 700; font-size: 1.15rem; color: #1E6B23;">
                                                    <?= (int)$st['won'] ?>
                                                </td>
                                                <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                                    <?= (int)$st['drawn'] ?>
                                                </td>
                                                <td style="text-align: center; font-weight: 700; font-size: 1.15rem; color: #922B21;">
                                                    <?= (int)$st['lost'] ?>
                                                </td>
                                                <td style="text-align: center; font-weight: 700; font-size: 1.15rem;">
                                                    <?= number_format((float)$st['game_points'], 1) ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <span class="badge" style="background: var(--ink); color: #FFF; font-size: 1rem; font-weight: 700; padding: 0.2rem 0.6rem;">
                                                        <?= (int)$st['match_points'] ?> MP
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($st['standing_notes'])): ?>
                                                        <span class="badge" style="background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; font-weight: 700;">
                                                            <?= htmlspecialchars($st['standing_notes']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: var(--rule);">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 2. Edit Scores Form -->
                        <div style="background: var(--cream); border: 1px solid var(--rule); padding: 1.25rem;">
                            <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--ink);">
                                ✏️ Edit Match Scores & Results
                            </h3>
                            <div style="overflow-x: auto; margin-bottom: 1rem;">
                                <table class="roster-table" style="background: #FFF; border: 1px solid var(--rule);">
                                    <thead>
                                        <tr style="background: #EDE6D2;">
                                            <th style="width: 45px;">#</th>
                                            <th>Team Name</th>
                                            <th style="width: 75px; text-align: center;">P</th>
                                            <th style="width: 75px; text-align: center;">W</th>
                                            <th style="width: 75px; text-align: center;">D</th>
                                            <th style="width: 75px; text-align: center;">L</th>
                                            <th style="width: 85px; text-align: center;">GP (Pts)</th>
                                            <th style="width: 85px; text-align: center;">MP (Score)</th>
                                            <th>Status / Arbiter Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($standings_teams as $idx => $st): ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--red); font-size: 1.1rem; text-align: center;">
                                                    <?= $idx + 1 ?>
                                                </td>
                                                <td style="font-weight: 700; font-size: 1.05rem;">
                                                    <?= htmlspecialchars($st['team_name']) ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][played]" value="<?= (int)$st['played'] ?>" class="score-input score-p" title="Matches Played">
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][won]" value="<?= (int)$st['won'] ?>" class="score-input score-w" style="color: #1E6B23;" onchange="autoCalcPoints(this)" title="Matches Won">
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][drawn]" value="<?= (int)$st['drawn'] ?>" class="score-input score-d" onchange="autoCalcPoints(this)" title="Matches Drawn">
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" min="0" max="50" name="scores[<?= $st['id'] ?>][lost]" value="<?= (int)$st['lost'] ?>" class="score-input score-l" style="color: #922B21;" title="Matches Lost">
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" step="0.5" min="0" max="300" name="scores[<?= $st['id'] ?>][game_points]" value="<?= (float)$st['game_points'] ?>" class="score-input score-gp" style="width: 75px;" title="Game Points">
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="number" min="0" max="100" name="scores[<?= $st['id'] ?>][match_points]" value="<?= (int)$st['match_points'] ?>" class="score-input score-mp" style="width: 75px; background: #FFF8E1;" title="Match Points">
                                                </td>
                                                <td>
                                                    <input type="text" name="scores[<?= $st['id'] ?>][standing_notes]" value="<?= htmlspecialchars($st['standing_notes'] ?? '') ?>" placeholder="e.g. Leader, Round 1 Bye" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.95rem;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; flex-wrap: wrap; gap: 1rem;">
                                <span style="font-size: 0.85rem; color: var(--ink-muted);">
                                    💡 Round-robin scoring: P = Played, W = Won, D = Drawn, L = Lost, GP = Game Points, MP = Match Points (Win = 2, Draw = 1).
                                </span>
                                <div style="display: flex; gap: 0.75rem;">
                                    <button type="button" onclick="calcAllMatchPoints()" class="btn-action" style="font-size: 0.95rem;">
                                        ⚡ Auto-Calc MP (W×2 + D×1)
                                    </button>
                                    <button type="submit" class="btn-submit" style="width: auto; padding: 0.6rem 2rem;">
                                        💾 Save & Publish Standings
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Toolbar (Search & Export) -->
            <div class="toolbar">
                <form method="GET" action="admin.php" class="search-form">
                    <input type="text" name="search" class="form-control" placeholder="Search team, player, or reg number..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <select name="filter_batch" class="form-control" style="max-width: 140px;">
                        <option value="">All Batches</option>
                        <?php foreach (array_keys($batch_counts) as $byear): ?>
                            <option value="<?= htmlspecialchars($byear) ?>" <?= (isset($_GET['filter_batch']) && $_GET['filter_batch'] === $byear) ? 'selected' : '' ?>>
                                Batch <?= htmlspecialchars($byear) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-action">Filter</button>
                    <?php if (!empty($_GET['search']) || !empty($_GET['filter_batch'])): ?>
                        <a href="admin.php" class="btn-link">Reset</a>
                    <?php endif; ?>
                </form>

                <div style="display: flex; gap: 0.75rem;">
                    <button onclick="window.print()" class="btn-action">🖨️ Print Rosters</button>
                    <a href="admin.php?action=export_csv" class="btn-action" style="background: var(--red); color: var(--cream); border-color: var(--red);">📥 Export to CSV</a>
                </div>
            </div>

            <!-- Teams Listing -->
            <?php if (empty($teams_data)): ?>
                <div class="alert" style="text-align: center; padding: 3rem 1rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">♟️</div>
                    <h3 style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: 0.5rem;">No registered teams found</h3>
                    <p style="color: var(--ink-muted);">
                        <?= (!empty($_GET['search']) || !empty($_GET['filter_batch'])) ? 'No teams match your filter criteria.' : 'When teams register through the website form, they will appear here.' ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($teams_data as $idx => $team): ?>
                    <div class="team-card">
                        <div class="team-card-header">
                            <div class="team-title-group">
                                <span style="font-family: var(--font-display); font-weight: 700; color: var(--red); font-size: 1.25rem;">#<?= htmlspecialchars($team['id']) ?></span>
                                <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                <span class="badge" style="background: #FFF9C4; color: #5D4037; border: 1px solid #FFE082; font-weight: 700; font-size: 0.85rem;">
                                    ♟ P: <?= (int)$team['played'] ?> · W: <?= (int)$team['won'] ?> · D: <?= (int)$team['drawn'] ?> · L: <?= (int)$team['lost'] ?> · GP: <?= number_format((float)$team['game_points'], 1) ?> · MP: <?= (int)$team['match_points'] ?>
                                </span>
                                <span class="badge badge-green">✓ <?= $team['member_count'] ?> Players</span>
                                <span class="badge badge-female">✓ <?= $team['female_count'] ?> Girl<?= $team['female_count'] == 1 ? '' : 's' ?></span>
                                <span class="badge badge-blue">✓ Batches: <?= htmlspecialchars($team['batches_list']) ?></span>
                                <?php if (!empty($team['standing_notes'])): ?>
                                    <span class="badge" style="background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; font-weight: 700;">
                                        ★ <?= htmlspecialchars($team['standing_notes']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="team-meta">
                                <?php if (!empty($team['contact_phone'])): ?>
                                    <span>📞 <?= htmlspecialchars($team['contact_phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($team['contact_email'])): ?>
                                    <span>✉️ <?= htmlspecialchars($team['contact_email']) ?></span>
                                <?php endif; ?>
                                <span>📅 <?= date('M d, Y H:i', strtotime($team['created_at'])) ?></span>
                            </div>
                        </div>

                        <table class="roster-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Player Name</th>
                                    <th>Registration No.</th>
                                    <th>Gender</th>
                                    <th>Batch</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($team['members'] as $m): ?>
                                    <tr>
                                        <td style="color: var(--ink-muted);"><?= $m['member_order'] ?></td>
                                        <td style="font-weight: 600;">
                                            <?= htmlspecialchars($m['name']) ?>
                                        </td>
                                        <td>
                                            <code style="background: var(--cream-dark); padding: 0.15rem 0.4rem; font-size: 0.85rem; border-radius: 2px;">
                                                <?= htmlspecialchars($m['reg_number']) ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if ($m['gender'] === 'Female'): ?>
                                                <span class="badge badge-female">♀ Female</span>
                                            <?php else: ?>
                                                <span class="badge badge-male">♂ Male</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($m['batch_year']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($m['is_captain']): ?>
                                                <span class="badge badge-captain">★ Captain</span>
                                            <?php else: ?>
                                                <span style="color: var(--ink-muted); font-size: 0.85rem;">Member</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="team-card-footer">
                            <div>
                                Registered on <?= date('F j, Y, g:i a', strtotime($team['created_at'])) ?>
                            </div>
                            <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete team \'<?= htmlspecialchars(addslashes($team['team_name'])) ?>\'? This action cannot be undone.');">
                                <input type="hidden" name="delete_team_id" value="<?= $team['id'] ?>">
                                <button type="submit" class="btn-delete">Delete Team</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>
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

        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
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
                        setTimeout(function() {
                            feedback.style.display = 'none';
                            feedback.style.opacity = '1';
                        }, 500);
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

    // ── Countdown visibility instant toggle (mirrors scoreboard toggle) ──
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
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
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
                        setTimeout(function() {
                            feedback.style.display = 'none';
                            feedback.style.opacity = '1';
                        }, 500);
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

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('instantToggleBtn');
        var toggleBox = document.getElementById('scoreboard_toggle_switch');
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleScoreboardVisibility();
            });
        }
        if (toggleBox) {
            toggleBox.addEventListener('change', function() {
                toggleScoreboardVisibility(this.checked);
            });
        }

        var cdBtn = document.getElementById('instantCountdownToggleBtn');
        var cdBox = document.getElementById('countdown_toggle_switch');
        if (cdBtn) {
            cdBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleCountdownVisibility();
            });
        }
        if (cdBox) {
            cdBox.addEventListener('change', function() {
                toggleCountdownVisibility(this.checked);
            });
        }
    });
    </script>
</body>
</html>
