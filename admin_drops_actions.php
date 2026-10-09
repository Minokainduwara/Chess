<?php
if (!$is_authenticated) return;

// Handle CRUD for Members
if (isset($_POST['update_members'])) {
    $team_id = (int)$_POST['team_id'];
    $members_data = $_POST['members'] ?? [];
    
    try {
        $pdo->beginTransaction();
        foreach ($members_data as $m_id => $data) {
            $stmt = $pdo->prepare("UPDATE `members` SET `name` = ?, `reg_number` = ?, `gender` = ?, `batch_year` = ?, `member_order` = ? WHERE `id` = ? AND `team_id` = ?");
            $stmt->execute([
                $data['name'],
                $data['reg_number'],
                $data['gender'],
                $data['batch_year'],
                $data['member_order'],
                $m_id,
                $team_id
            ]);
        }
        $pdo->commit();
        $success_msg = "Team members updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error updating members: " . $e->getMessage();
    }
}

// Handle Rounds
if (isset($_POST['create_round'])) {
    $round_number = (int)$_POST['round_number'];
    $deadline = $_POST['deadline'] ?: null; // format: YYYY-MM-DDTHH:MM
    $label = trim($_POST['label'] ?? '');
    if ($label === '') $label = "Round $round_number";
    
    try {
        $stmt = $pdo->prepare("INSERT INTO `rounds` (`round_number`, `label`, `status`, `deadline`) VALUES (?, ?, 'open', ?)");
        $stmt->execute([$round_number, $label, $deadline]);
        $success_msg = "Round $round_number created successfully.";
    } catch (Exception $e) {
        $error_msg = "Error creating round: " . $e->getMessage();
    }
}

if (isset($_POST['update_round_status'])) {
    $round_number = (int)$_POST['round_number'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE `rounds` SET `status` = ? WHERE `round_number` = ?");
    $stmt->execute([$status, $round_number]);
    $success_msg = "Round $round_number status updated to $status.";
}

if (isset($_POST['update_round_details'])) {
    $round_number = (int)$_POST['round_number'];
    $label = trim($_POST['label'] ?? '');
    $status = $_POST['status'];
    $deadline = $_POST['deadline'] ?: null;
    $schedule_date = $_POST['schedule_date'] ?? null;
    $schedule_time = $_POST['schedule_time'] ?? null;
    $schedule_status = $_POST['schedule_status'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE `rounds` SET `label` = ?, `status` = ?, `deadline` = ?, `schedule_date` = ?, `schedule_time` = ?, `schedule_status` = ? WHERE `round_number` = ?");
    $stmt->execute([$label, $status, $deadline, $schedule_date, $schedule_time, $schedule_status, $round_number]);
    $success_msg = "Round $round_number details updated successfully.";
}

// Handle Admin Drop Override
if (isset($_POST['admin_update_drops'])) {
    $team_id = (int)$_POST['override_team_id'];
    $round_number = (int)$_POST['override_round_number'];
    $dropped_members = $_POST['admin_dropped_members'] ?? [];
    
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?")->execute([$team_id, $round_number]);

        $stmt_drop = $pdo->prepare("INSERT INTO `drop_boards` (`team_id`, `round_number`, `member_id`) VALUES (?, ?, ?)");
        foreach ($dropped_members as $m_id) {
            $stmt_drop->execute([$team_id, $round_number, $m_id]);
        }
        $pdo->commit();
        $success_msg = "Drop boards for Team #$team_id in Round $round_number successfully overridden.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error overriding drop boards: " . $e->getMessage();
    }
}

