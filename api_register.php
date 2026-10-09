<?php
/**
 * Team Registration Processing Endpoint
 * Validates tournament rules and persists team & members to MySQL
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/db.php';

$response = [
    'success' => false,
    'message' => '',
    'errors'  => []
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST allowed.';
    echo json_encode($response);
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    $response['message'] = 'Database connection failed. Please ensure MySQL service is running.';
    echo json_encode($response);
    exit;
}

// 1. Sanitize & extract team inputs
$team_name     = trim($_POST['team_name'] ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$members_raw   = $_POST['members'] ?? [];

$errors = [];

// Validate Team Name
if ($team_name === '') {
    $errors['team_name'] = 'Team name is required.';
} elseif (mb_strlen($team_name) < 3 || mb_strlen($team_name) > 100) {
    $errors['team_name'] = 'Team name must be between 3 and 100 characters.';
} else {
    // Check if team name already exists
    $stmt = $pdo->prepare("SELECT id FROM `teams` WHERE LOWER(`team_name`) = LOWER(?)");
    $stmt->execute([$team_name]);
    if ($stmt->fetch()) {
        $errors['team_name'] = 'This team name is already registered. Please choose a unique name.';
    }
}

// Filter and validate members array
if (!is_array($members_raw)) {
    $errors['members'] = 'Invalid member submission.';
    $members_raw = [];
}

$valid_members = [];
$female_count  = 0;
$male_count    = 0;
$batches       = [];
$reg_numbers   = [];

$reg_pattern = '/^TG\/(\d{4})\/(\d{4})$/i';

$member_index = 0;
foreach ($members_raw as $m) {
    $member_index++;
    $name    = trim($m['name'] ?? '');
    $reg_no  = strtoupper(trim($m['reg_number'] ?? ''));
    $gender  = trim($m['gender'] ?? '');

    // Skip completely empty extra rows if any
    if ($name === '' && $reg_no === '' && $gender === '' && $member_index > 4) {
        continue;
    }

    $m_errors = [];
    if ($name === '') {
        $m_errors[] = "Member #{$member_index} name is required.";
    } elseif (mb_strlen($name) < 2) {
        $m_errors[] = "Member #{$member_index} name is too short.";
    }

    if ($reg_no === '') {
        $m_errors[] = "Member #{$member_index} registration number is required.";
    } elseif (!preg_match($reg_pattern, $reg_no, $matches)) {
        $m_errors[] = "Member #{$member_index} registration number '{$reg_no}' is invalid. Expected format: TG/2023/0001";
    } else {
        $batch_year = $matches[1];
        // Check uniqueness within the team
        if (in_array($reg_no, $reg_numbers, true)) {
            $m_errors[] = "Registration number '{$reg_no}' is entered more than once in this team.";
        } else {
            $reg_numbers[] = $reg_no;
            $batches[] = $batch_year;
        }

        // Check if registration number already registered in another team in DB
        $stmt_check = $pdo->prepare("
            SELECT m.name, t.team_name 
            FROM `members` m 
            JOIN `teams` t ON m.team_id = t.id 
            WHERE m.reg_number = ?
        ");
        $stmt_check->execute([$reg_no]);
        if ($existing = $stmt_check->fetch()) {
            $m_errors[] = "Student with registration number '{$reg_no}' is already registered with team '{$existing['team_name']}'.";
        }
    }

    if ($gender !== 'Male' && $gender !== 'Female') {
        $m_errors[] = "Member #{$member_index} must select Gender as either Male or Female.";
    } else {
        if ($gender === 'Female') {
            $female_count++;
        } else {
            $male_count++;
        }
    }

    if (!empty($m_errors)) {
        foreach ($m_errors as $err) {
            $errors[] = $err;
        }
    } else {
        $valid_members[] = [
            'order'      => count($valid_members) + 1,
            'name'       => $name,
            'reg_number' => $reg_no,
            'gender'     => $gender,
            'batch_year' => $matches[1] ?? '',
            'is_captain' => (count($valid_members) === 0) ? 1 : 0
        ];
    }
}

$member_count = count($valid_members);

// 2. Rule: Member Count (Min 4, Max 6)
if ($member_count < 4) {
    $errors[] = "Every team must have a minimum of 4 members. You currently have {$member_count} valid member(s).";
} elseif ($member_count > 6) {
    $errors[] = "Every team can have a maximum of 6 members. You submitted {$member_count} members.";
}

// 3. Rule: Gender Quota (Must have at least 1 girl)
if ($female_count < 1) {
    $errors[] = "Tournament rule violation: Every team MUST have at least 1 female member (girl). Currently selected: {$female_count} female member(s).";
}

// 4. Rule: Batch Representation (Must represent at least two batches)
$unique_batches = array_unique($batches);
if (count($unique_batches) < 2) {
    $batches_str = !empty($unique_batches) ? implode(', ', $unique_batches) : 'None';
    $errors[] = "Tournament rule violation: Each team MUST represent at least two distinct batches (e.g. 2022 & 2023). Currently detected batches: {$batches_str}.";
}

// 5. If errors, return immediately
if (!empty($errors)) {
    $response['message'] = 'Validation failed. Please correct the highlighted issues.';
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// 6. Begin DB Transaction and Insert
try {
    $pdo->beginTransaction();

    $stmt_team = $pdo->prepare("
        INSERT INTO `teams` (`team_name`, `contact_phone`, `contact_email`) 
        VALUES (?, ?, ?)
    ");
    $stmt_team->execute([$team_name, $contact_phone, $contact_email]);
    $team_id = (int)$pdo->lastInsertId();

    $stmt_member = $pdo->prepare("
        INSERT INTO `members` (`team_id`, `member_order`, `name`, `reg_number`, `gender`, `batch_year`, `is_captain`)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($valid_members as $m) {
        $stmt_member->execute([
            $team_id,
            $m['order'],
            $m['name'],
            $m['reg_number'],
            $m['gender'],
            $m['batch_year'],
            $m['is_captain']
        ]);
    }

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Team '{$team_name}' registered successfully with {$member_count} members!";
    $response['data'] = [
        'team_id'         => $team_id,
        'team_name'       => $team_name,
        'member_count'    => $member_count,
        'female_count'    => $female_count,
        'male_count'      => $male_count,
        'batches'         => array_values($unique_batches)
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Database transaction failed: ' . $e->getMessage();
}

echo json_encode($response);
