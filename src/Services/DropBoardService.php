<?php
/**
 * Drop Board & Rounds Service
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';

class DropBoardService {

    /**
     * Get the active currently open round
     */
    public static function getActiveOpenRound(): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM `rounds` WHERE `status` = 'open' ORDER BY `round_number` ASC LIMIT 1");
        $round = $stmt->fetch();
        return $round ?: null;
    }

    /**
     * Get all rounds ordered
     */
    public static function getAllRounds(string $order = 'ASC'): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM `rounds` ORDER BY `round_number` {$order}");
        return $stmt->fetchAll();
    }

    /**
     * Check if a round is currently open and within deadline
     */
    public static function isRoundOpenForDrops(int $roundNumber, &$errorMsg = ''): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `rounds` WHERE `round_number` = ?");
        $stmt->execute([$roundNumber]);
        $round = $stmt->fetch();

        if (!$round) {
            $errorMsg = "Round {$roundNumber} does not exist.";
            return false;
        }

        if ($round['status'] !== 'open') {
            $errorMsg = "Round {$roundNumber} is currently closed.";
            return false;
        }

        date_default_timezone_set('Asia/Colombo');
        if (!empty($round['deadline']) && strtotime($round['deadline']) < time()) {
            $errorMsg = "The drop board deadline for Round {$roundNumber} has passed.";
            return false;
        }

        return true;
    }

    /**
     * Authoritative calculation of exactly 4 playing members for a team and round
     */
    public static function getPlayingMembers(int $teamId, int $roundNumber): array {
        $pdo = Database::getConnection();

        // 1. Get all members ordered by member_order
        $stmtM = $pdo->prepare("SELECT * FROM `members` WHERE `team_id` = ? ORDER BY `member_order` ASC");
        $stmtM->execute([$teamId]);
        $allMembers = $stmtM->fetchAll();

        // 2. Get submitted drops
        $stmtD = $pdo->prepare("SELECT `member_id` FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
        $stmtD->execute([$teamId, $roundNumber]);
        $submittedDrops = $stmtD->fetchAll(PDO::FETCH_COLUMN);

        $playing = [];
        $actualDrops = $submittedDrops;
        $playingCount = 0;

        foreach ($allMembers as $m) {
            if (!in_array($m['id'], $actualDrops) && $playingCount < 4) {
                $playing[] = $m;
                $playingCount++;
            } elseif (!in_array($m['id'], $actualDrops)) {
                // If not explicitly dropped but 4 boards are already filled, auto-drop them
                $actualDrops[] = $m['id'];
            }
        }

        return $playing;
    }

    /**
     * Get drop board member IDs for a team and round
     */
    public static function getDropsForTeamAndRound(int $teamId, int $roundNumber): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT `member_id` FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
        $stmt->execute([$teamId, $roundNumber]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Save captain drop submissions (subject to deadline and exact drop count)
     */
    public static function saveCaptainDrops(int $teamId, int $roundNumber, array $droppedMemberIds): array {
        $error = '';
        if (!self::isRoundOpenForDrops($roundNumber, $error)) {
            return ['success' => false, 'message' => $error];
        }

        $pdo = Database::getConnection();
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `members` WHERE `team_id` = ?");
        $stmtCount->execute([$teamId]);
        $totalMembers = (int)$stmtCount->fetchColumn();

        $requiredDrops = $totalMembers - 4;
        if (count($droppedMemberIds) !== $requiredDrops) {
            return ['success' => false, 'message' => "You must select exactly {$requiredDrops} member(s) to drop."];
        }

        return self::persistDrops($teamId, $roundNumber, $droppedMemberIds);
    }

    /**
     * Admin override for drops (bypasses round deadlines)
     */
    public static function saveAdminDropOverride(int $teamId, int $roundNumber, array $droppedMemberIds): array {
        return self::persistDrops($teamId, $roundNumber, $droppedMemberIds);
    }

    /**
     * Internal helper to persist drops in a transaction
     */
    private static function persistDrops(int $teamId, int $roundNumber, array $droppedMemberIds): array {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $stmtDel = $pdo->prepare("DELETE FROM `drop_boards` WHERE `team_id` = ? AND `round_number` = ?");
            $stmtDel->execute([$teamId, $roundNumber]);

            $stmtIns = $pdo->prepare("INSERT INTO `drop_boards` (`team_id`, `round_number`, `member_id`) VALUES (?, ?, ?)");
            foreach ($droppedMemberIds as $mId) {
                $stmtIns->execute([$teamId, $roundNumber, (int)$mId]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "Drop boards saved for Round {$roundNumber}."];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to save drop boards: ' . $e->getMessage()];
        }
    }

    /**
     * Create a new round
     */
    public static function createRound(int $roundNumber, string $label, ?string $deadline): array {
        $pdo = Database::getConnection();
        $label = trim($label) ?: "Round {$roundNumber}";

        try {
            $stmt = $pdo->prepare("INSERT INTO `rounds` (`round_number`, `label`, `status`, `deadline`) VALUES (?, ?, 'open', ?)");
            $stmt->execute([$roundNumber, $label, $deadline ?: null]);
            return ['success' => true, 'message' => "Round {$roundNumber} created successfully."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error creating round: " . $e->getMessage()];
        }
    }

    /**
     * Update round schedule & details
     */
    public static function updateRoundDetails(int $roundNumber, string $label, string $status, ?string $deadline, ?string $date, ?string $time, ?string $schedStatus): array {
        $pdo = Database::getConnection();
        $label = trim($label) ?: "Round {$roundNumber}";

        try {
            $stmt = $pdo->prepare("
                UPDATE `rounds` 
                SET `label` = ?, `status` = ?, `deadline` = ?, `schedule_date` = ?, `schedule_time` = ?, `schedule_status` = ?
                WHERE `round_number` = ?
            ");
            $stmt->execute([$label, $status, $deadline ?: null, $date, $time, $schedStatus, $roundNumber]);
            return ['success' => true, 'message' => "Round {$roundNumber} details updated successfully."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error updating round: " . $e->getMessage()];
        }
    }
}
