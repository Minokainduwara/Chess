<?php
/**
 * Team & Member Management Service
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/TournamentService.php';

class TeamService {

    /**
     * Fetch all teams with optional status filter
     */
    public static function getAllTeams(?string $status = null): array {
        $pdo = Database::getConnection();
        if ($status !== null) {
            $stmt = $pdo->prepare("SELECT * FROM `teams` WHERE `status` = ? ORDER BY `id` ASC");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("SELECT * FROM `teams` ORDER BY `id` ASC");
        }
        return $stmt->fetchAll();
    }

    /**
     * Fetch all approved teams
     */
    public static function getApprovedTeams(): array {
        return self::getAllTeams('approved');
    }

    /**
     * Fetch a team with its members
     */
    public static function getTeamWithMembers(int $teamId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `teams` WHERE `id` = ?");
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();

        if (!$team) return null;

        $stmtM = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
        $stmtM->execute([$teamId]);
        $team['members'] = $stmtM->fetchAll();

        return $team;
    }

    /**
     * Validate and register a new team with members
     */
    public static function registerTeam(array $postData): array {
        $settings = TournamentService::getSettings();
        if (($settings['registration_open'] ?? '1') !== '1') {
            return [
                'success' => false,
                'message' => $settings['registration_closed_message'] ?? 'Official team registration is currently closed.',
                'errors'  => ['registration' => 'Registration is currently closed by the tournament organizer.']
            ];
        }

        $pdo = Database::getConnection();

        $teamName        = trim($postData['team_name'] ?? '');
        $contactPhone    = trim($postData['contact_phone'] ?? '');
        $contactEmail    = trim($postData['contact_email'] ?? '');
        $captainPassword = trim($postData['captain_password'] ?? '');
        $membersRaw      = $postData['members'] ?? [];

        $errors = [];

        // 1. Team Name Validation
        if ($teamName === '') {
            $errors['team_name'] = 'Team name is required.';
        } elseif ($captainPassword === '') {
            $errors['captain_password'] = 'Captain password is required.';
        } elseif (mb_strlen($teamName) < 3 || mb_strlen($teamName) > 100) {
            $errors['team_name'] = 'Team name must be between 3 and 100 characters.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM `teams` WHERE LOWER(`team_name`) = LOWER(?)");
            $stmt->execute([$teamName]);
            if ($stmt->fetch()) {
                $errors['team_name'] = 'This team name is already registered. Please choose a unique name.';
            }
        }

        if (!is_array($membersRaw)) {
            $errors['members'] = 'Invalid member submission.';
            $membersRaw = [];
        }

        $validMembers = [];
        $femaleCount  = 0;
        $maleCount    = 0;
        $batches      = [];
        $regNumbers   = [];
        $regPattern   = '/^TG\/(\d{4})\/(\d{4})$/i';

        $memberIndex = 0;
        foreach ($membersRaw as $m) {
            $memberIndex++;
            $name   = trim($m['name'] ?? '');
            $regNo  = strtoupper(trim($m['reg_number'] ?? ''));
            $gender = trim($m['gender'] ?? '');

            // Skip empty extra rows if beyond minimum 4
            if ($name === '' && $regNo === '' && $gender === '' && $memberIndex > 4) {
                continue;
            }

            $mErrors = [];
            if ($name === '') {
                $mErrors[] = "Member #{$memberIndex} name is required.";
            } elseif (mb_strlen($name) < 2) {
                $mErrors[] = "Member #{$memberIndex} name is too short.";
            }

            if ($regNo === '') {
                $mErrors[] = "Member #{$memberIndex} registration number is required.";
            } elseif (!preg_match($regPattern, $regNo, $matches)) {
                $mErrors[] = "Member #{$memberIndex} registration number '{$regNo}' is invalid. Expected format: TG/2023/0001";
            } else {
                $batchYear = $matches[1];
                if (in_array($regNo, $regNumbers, true)) {
                    $mErrors[] = "Registration number '{$regNo}' is entered more than once in this team.";
                } else {
                    $regNumbers[] = $regNo;
                    $batches[] = $batchYear;
                }

                $stmtCheck = $pdo->prepare("
                    SELECT m.name, t.team_name 
                    FROM `members` m 
                    JOIN `teams` t ON m.team_id = t.id 
                    WHERE m.reg_number = ?
                ");
                $stmtCheck->execute([$regNo]);
                if ($existing = $stmtCheck->fetch()) {
                    $mErrors[] = "Student with registration number '{$regNo}' is already registered with team '{$existing['team_name']}'.";
                }
            }

            if ($gender !== 'Male' && $gender !== 'Female') {
                $mErrors[] = "Member #{$memberIndex} must select Gender as either Male or Female.";
            } else {
                if ($gender === 'Female') {
                    $femaleCount++;
                } else {
                    $maleCount++;
                }
            }

            if (!empty($mErrors)) {
                foreach ($mErrors as $err) {
                    $errors[] = $err;
                }
            } else {
                $validMembers[] = [
                    'order'      => count($validMembers) + 1,
                    'name'       => $name,
                    'reg_number' => $regNo,
                    'gender'     => $gender,
                    'batch_year' => $matches[1] ?? '',
                    'is_captain' => (count($validMembers) === 0) ? 1 : 0
                ];
            }
        }

        $memberCount = count($validMembers);

        if ($memberCount < 4) {
            $errors[] = "Every team must have a minimum of 4 members. You currently have {$memberCount} valid member(s).";
        } elseif ($memberCount > 6) {
            $errors[] = "Every team can have a maximum of 6 members. You submitted {$memberCount} members.";
        }

        if ($femaleCount < 1) {
            $errors[] = "Tournament rule violation: Every team MUST have at least 1 female member (girl). Currently selected: {$femaleCount} female member(s).";
        }

        $uniqueBatches = array_unique($batches);
        if (count($uniqueBatches) < 2) {
            $batchesStr = !empty($uniqueBatches) ? implode(', ', $uniqueBatches) : 'None';
            $errors[] = "Tournament rule violation: Each team MUST represent at least two distinct batches. Currently detected batches: {$batchesStr}.";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed. Please correct the highlighted issues.',
                'errors'  => $errors
            ];
        }

        // Persist team & members
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($captainPassword, PASSWORD_DEFAULT);
            $stmtTeam = $pdo->prepare("
                INSERT INTO `teams` (`team_name`, `contact_phone`, `contact_email`, `password_hash`, `status`) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmtTeam->execute([$teamName, $contactPhone, $contactEmail, $passwordHash]);
            $teamId = (int)$pdo->lastInsertId();

            $stmtMember = $pdo->prepare("
                INSERT INTO `members` (`team_id`, `member_order`, `name`, `reg_number`, `gender`, `batch_year`, `is_captain`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($validMembers as $m) {
                $stmtMember->execute([
                    $teamId,
                    $m['order'],
                    $m['name'],
                    $m['reg_number'],
                    $m['gender'],
                    $m['batch_year'],
                    $m['is_captain']
                ]);
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Team '{$teamName}' submitted with {$memberCount} members! Your registration is pending admin approval - it will appear in the Arena Standings once approved.",
                'data'    => [
                    'team_id'      => $teamId,
                    'team_name'    => $teamName,
                    'status'       => 'pending',
                    'member_count' => $memberCount,
                    'female_count' => $femaleCount,
                    'male_count'   => $maleCount,
                    'batches'      => array_values($uniqueBatches)
                ]
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Database transaction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update team approval status
     */
    public static function updateStatus(int $teamId, string $status): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `teams` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$status, $teamId]);
    }

    /**
     * Delete team and cascade members
     */
    public static function deleteTeam(int $teamId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `teams` WHERE `id` = ?");
        return $stmt->execute([$teamId]);
    }

    /**
     * Update member roster and board ordering
     */
    public static function updateMembers(int $teamId, array $membersData): array {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            foreach ($membersData as $mId => $data) {
                $stmt = $pdo->prepare("
                    UPDATE `members` 
                    SET `name` = ?, `reg_number` = ?, `gender` = ?, `batch_year` = ?, `member_order` = ? 
                    WHERE `id` = ? AND `team_id` = ?
                ");
                $stmt->execute([
                    $data['name'],
                    $data['reg_number'],
                    $data['gender'],
                    $data['batch_year'],
                    (int)$data['member_order'],
                    (int)$mId,
                    $teamId
                ]);
            }
            $pdo->commit();
            return ['success' => true, 'message' => "Team members updated successfully."];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => "Error updating members: " . $e->getMessage()];
        }
    }

    /**
     * Manual standings scoreboard row update
     */
    public static function updateStandingsRow(int $teamId, int $played, int $won, int $drawn, int $lost, float $gp, int $mp, float $sb, ?string $notes): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `teams` 
            SET `played` = ?, `won` = ?, `drawn` = ?, `lost` = ?, `game_points` = ?, `match_points` = ?, `tiebreak_sb` = ?, `standing_notes` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([$played, $won, $drawn, $lost, $gp, $mp, $sb, $notes, $teamId]);
    }
}
