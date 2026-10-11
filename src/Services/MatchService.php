<?php
/**
 * Match & Pairing Service
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/TournamentService.php';

class MatchService {

    /**
     * Fetch all public matches (scheduled and completed) grouped by round
     */
    public static function getPublicMatches(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name 
            FROM `matches` m 
            JOIN `teams` t1 ON m.team_a_id = t1.id 
            JOIN `teams` t2 ON m.team_b_id = t2.id 
            WHERE m.status IN ('scheduled', 'completed') 
            ORDER BY m.round_number ASC, m.id ASC
        ");

        $grouped = [];
        while ($row = $stmt->fetch()) {
            $grouped[$row['round_number']][] = $row;
        }
        return $grouped;
    }

    /**
     * Fetch all matches grouped by round for admin view
     */
    public static function getAllMatchesGrouped(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name 
            FROM `matches` m 
            JOIN `teams` t1 ON m.team_a_id = t1.id 
            JOIN `teams` t2 ON m.team_b_id = t2.id 
            ORDER BY m.round_number ASC, m.id ASC
        ");

        $grouped = [];
        while ($row = $stmt->fetch()) {
            $grouped[$row['round_number']][] = $row;
        }
        return $grouped;
    }

    /**
     * Create a new match pairing with full round-robin validation
     */
    public static function createMatch(int $roundNumber, int $teamAId, int $teamBId): array {
        if ($teamAId === $teamBId) {
            return ['success' => false, 'message' => "A team cannot play itself."];
        }

        $pdo = Database::getConnection();

        // 1. Check if either team is already scheduled in this round
        $stmtCheckRound = $pdo->prepare("
            SELECT COUNT(*) FROM `matches` 
            WHERE `round_number` = ? AND (`team_a_id` IN (?, ?) OR `team_b_id` IN (?, ?))
        ");
        $stmtCheckRound->execute([$roundNumber, $teamAId, $teamBId, $teamAId, $teamBId]);
        if ((int)$stmtCheckRound->fetchColumn() > 0) {
            return ['success' => false, 'message' => "One or both teams are already scheduled to play in Round {$roundNumber}."];
        }

        // 2. Check if they have already played each other in any round
        $stmtCheckHist = $pdo->prepare("
            SELECT COUNT(*) FROM `matches` 
            WHERE (`team_a_id` = ? AND `team_b_id` = ?) OR (`team_a_id` = ? AND `team_b_id` = ?)
        ");
        $stmtCheckHist->execute([$teamAId, $teamBId, $teamBId, $teamAId]);
        if ((int)$stmtCheckHist->fetchColumn() > 0) {
            return ['success' => false, 'message' => "These two teams have already played each other in this tournament."];
        }

        // 3. Insert scheduled match
        $stmt = $pdo->prepare("INSERT INTO `matches` (`round_number`, `team_a_id`, `team_b_id`, `status`) VALUES (?, ?, ?, 'scheduled')");
        $stmt->execute([$roundNumber, $teamAId, $teamBId]);

        return ['success' => true, 'message' => "Match successfully scheduled for Round {$roundNumber}."];
    }

    /**
     * Delete an existing match pairing
     */
    public static function deleteMatch(int $matchId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT status FROM `matches` WHERE id = ?");
        $stmt->execute([$matchId]);
        $status = $stmt->fetchColumn();

        if ($status === 'completed') {
            return ['success' => false, 'message' => "Cannot delete a completed match. Change its status first."];
        }

        $pdo->prepare("DELETE FROM `matches` WHERE id = ?")->execute([$matchId]);
        return ['success' => true, 'message' => "Match deleted successfully."];
    }

    /**
     * Get a specific match with team details and board results
     */
    public static function getMatchWithBoards(int $matchId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, t1.team_name as ta_name, t2.team_name as tb_name 
            FROM `matches` m 
            JOIN `teams` t1 ON m.team_a_id = t1.id 
            JOIN `teams` t2 ON m.team_b_id = t2.id 
            WHERE m.id = ?
        ");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();

        if (!$match) return null;

        $stmtB = $pdo->prepare("SELECT * FROM `match_boards` WHERE match_id = ? ORDER BY board_number ASC");
        $stmtB->execute([$matchId]);
        $boards = [];
        while ($b = $stmtB->fetch()) {
            $boards[$b['board_number']] = $b;
        }
        $match['boards'] = $boards;

        return $match;
    }

    /**
     * Save board results and recalculate points & standings
     */
    public static function saveMatchResults(int $matchId, string $status, array $boards, array $teamAMemberIds, array $teamBMemberIds): array {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $teamAGp = 0.0;
            $teamBGp = 0.0;

            // Clear existing board records
            $pdo->prepare("DELETE FROM `match_boards` WHERE match_id = ?")->execute([$matchId]);

            $stmtIns = $pdo->prepare("
                INSERT INTO `match_boards` (`match_id`, `board_number`, `team_a_member_id`, `team_b_member_id`, `result`) 
                VALUES (?, ?, ?, ?, ?)
            ");

            for ($i = 1; $i <= 4; $i++) {
                $res = $boards[$i] ?? null;
                $aM = !empty($teamAMemberIds[$i]) ? (int)$teamAMemberIds[$i] : null;
                $bM = !empty($teamBMemberIds[$i]) ? (int)$teamBMemberIds[$i] : null;

                if ($res === '1-0') {
                    $teamAGp += 1.0;
                } elseif ($res === '0.5-0.5') {
                    $teamAGp += 0.5;
                    $teamBGp += 0.5;
                } elseif ($res === '0-1') {
                    $teamBGp += 1.0;
                }

                $stmtIns->execute([$matchId, $i, $aM, $bM, $res]);
            }

            // Calculate Match Points (MP) based on configured tournament settings
            $settings = TournamentService::getSettings();
            $mpWin        = (int)($settings['mp_win'] ?? 2);
            $mpDraw       = (int)($settings['mp_draw'] ?? 1);
            $mpLoss       = (int)($settings['mp_loss'] ?? 0);
            $mpLossZeroGp = (int)($settings['mp_loss_zero_gp'] ?? -1);

            $teamAMp = 0;
            $teamBMp = 0;

            if ($status === 'completed') {
                if ($teamAGp > $teamBGp) {
                    $teamAMp = $mpWin;
                    $teamBMp = ($teamBGp == 0.0) ? $mpLossZeroGp : $mpLoss;
                } elseif ($teamAGp < $teamBGp) {
                    $teamBMp = $mpWin;
                    $teamAMp = ($teamAGp == 0.0) ? $mpLossZeroGp : $mpLoss;
                } else {
                    $teamAMp = $mpDraw;
                    $teamBMp = $mpDraw;
                }
            }

            // Update match record
            $stmtUpd = $pdo->prepare("
                UPDATE `matches` 
                SET `status` = ?, `team_a_gp` = ?, `team_b_gp` = ?, `team_a_mp` = ?, `team_b_mp` = ? 
                WHERE `id` = ?
            ");
            $stmtUpd->execute([$status, $teamAGp, $teamBGp, $teamAMp, $teamBMp, $matchId]);

            $pdo->commit();

            // Recalculate global tournament standings if completed
            if ($status === 'completed') {
                TournamentService::recalculateStandings();
            }

            return ['success' => true, 'message' => "Board results saved and standings updated."];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => "Failed to save board results: " . $e->getMessage()];
        }
    }

    /**
     * Compute 6x6 pairing matrix data for teams
     */
    public static function getPairingMatrix(array $teams): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM `matches`");
        $allMatches = $stmt->fetchAll();

        $matrix = [];
        foreach ($allMatches as $m) {
            $a = $m['team_a_id'];
            $b = $m['team_b_id'];
            $scoreText = ($m['status'] === 'completed') ? "({$m['team_a_gp']}-{$m['team_b_gp']})" : "";
            $label = "R{$m['round_number']} {$scoreText}";

            $matrix[$a][$b] = $label;
            $matrix[$b][$a] = $label;
        }

        return $matrix;
    }
}
