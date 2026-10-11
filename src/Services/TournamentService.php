<?php
/**
 * Tournament Service (Standings, Settings & Player Rankings)
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';

class TournamentService {

    /**
     * Get tournament settings as a key-value dictionary
     */
    public static function getSettings(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM `tournament_settings`");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Update individual tournament setting
     */
    public static function updateSetting(string $key, string $value): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `tournament_settings` (`setting_key`, `setting_value`) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE `setting_value` = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }

    /**
     * Get public approved team standings ordered by priority: MP -> GP -> SB -> id
     */
    public static function getStandings(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT id, team_name, played, won, drawn, lost, game_points, match_points, standing_notes, tiebreak_sb
            FROM `teams`
            WHERE `status` = 'approved'
            ORDER BY match_points DESC, game_points DESC, tiebreak_sb DESC, id ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get all individual player standings based on aggregated board results
     */
    public static function getIndividualStandings(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT m.id, m.name, t.team_name,
                   SUM(CASE
                       WHEN mb.result = '1-0' AND mb.team_a_member_id = m.id THEN 1.0
                       WHEN mb.result = '0-1' AND mb.team_b_member_id = m.id THEN 1.0
                       WHEN mb.result = '0.5-0.5' AND (mb.team_a_member_id = m.id OR mb.team_b_member_id = m.id) THEN 0.5
                       ELSE 0.0
                   END) as individual_score
            FROM `members` m
            JOIN `teams` t ON m.team_id = t.id
            LEFT JOIN `match_boards` mb ON (mb.team_a_member_id = m.id OR mb.team_b_member_id = m.id)
            GROUP BY m.id
            ORDER BY individual_score DESC, m.name ASC
        ");
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Recalculate standings table from all completed matches
     */
    public static function recalculateStandings(): void {
        $pdo = Database::getConnection();

        // 1. Reset all approved teams' stats
        $pdo->exec("
            UPDATE `teams` 
            SET `played` = 0, `won` = 0, `drawn` = 0, `lost` = 0, `game_points` = 0.0, `match_points` = 0 
            WHERE `status` = 'approved'
        ");

        // 2. Fetch all completed matches
        $stmtM = $pdo->query("SELECT * FROM `matches` WHERE `status` = 'completed'");
        while ($m = $stmtM->fetch()) {
            $aId = (int)$m['team_a_id'];
            $bId = (int)$m['team_b_id'];

            $aW = $m['team_a_gp'] > $m['team_b_gp'] ? 1 : 0;
            $aD = $m['team_a_gp'] == $m['team_b_gp'] ? 1 : 0;
            $aL = $m['team_a_gp'] < $m['team_b_gp'] ? 1 : 0;

            $bW = $m['team_b_gp'] > $m['team_a_gp'] ? 1 : 0;
            $bD = $m['team_b_gp'] == $m['team_a_gp'] ? 1 : 0;
            $bL = $m['team_b_gp'] < $m['team_a_gp'] ? 1 : 0;

            $stmtUpdA = $pdo->prepare("
                UPDATE `teams` 
                SET `played` = `played` + 1, `won` = `won` + ?, `drawn` = `drawn` + ?, `lost` = `lost` + ?, 
                    `game_points` = `game_points` + ?, `match_points` = `match_points` + ? 
                WHERE `id` = ?
            ");
            $stmtUpdA->execute([$aW, $aD, $aL, $m['team_a_gp'], $m['team_a_mp'], $aId]);

            $stmtUpdB = $pdo->prepare("
                UPDATE `teams` 
                SET `played` = `played` + 1, `won` = `won` + ?, `drawn` = `drawn` + ?, `lost` = `lost` + ?, 
                    `game_points` = `game_points` + ?, `match_points` = `match_points` + ? 
                WHERE `id` = ?
            ");
            $stmtUpdB->execute([$bW, $bD, $bL, $m['team_b_gp'], $m['team_b_mp'], $bId]);
        }
    }

    /**
     * Recalculate match points for all completed matches using currently configured MP rules,
     * then rebuild the standings table.
     */
    public static function recalculateAllMatchesAndStandings(): void {
        $pdo = Database::getConnection();
        $settings = self::getSettings();
        $mpWin        = (int)($settings['mp_win'] ?? 2);
        $mpDraw       = (int)($settings['mp_draw'] ?? 1);
        $mpLoss       = (int)($settings['mp_loss'] ?? 0);
        $mpLossZeroGp = (int)($settings['mp_loss_zero_gp'] ?? -1);

        $stmtMatches = $pdo->query("SELECT id, team_a_gp, team_b_gp FROM `matches` WHERE `status` = 'completed'");
        $stmtUpdMatch = $pdo->prepare("UPDATE `matches` SET `team_a_mp` = ?, `team_b_mp` = ? WHERE `id` = ?");

        while ($m = $stmtMatches->fetch(PDO::FETCH_ASSOC)) {
            $aGp = (float)$m['team_a_gp'];
            $bGp = (float)$m['team_b_gp'];

            if ($aGp > $bGp) {
                $aMp = $mpWin;
                $bMp = ($bGp == 0.0) ? $mpLossZeroGp : $mpLoss;
            } elseif ($aGp < $bGp) {
                $bMp = $mpWin;
                $aMp = ($aGp == 0.0) ? $mpLossZeroGp : $mpLoss;
            } else {
                $aMp = $mpDraw;
                $bMp = $mpDraw;
            }

            $stmtUpdMatch->execute([$aMp, $bMp, (int)$m['id']]);
        }

        self::recalculateStandings();
    }
}
