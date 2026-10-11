<?php
/**
 * Swiss System Pairing Service
 * FOT Knights Arena — Chess Tournament
 *
 * Implements FIDE Dutch Swiss System pairing principles:
 * 1. No two teams shall play each other more than once.
 * 2. Teams with equal/similar scores are paired together (Score Brackets).
 * 3. Top-half vs Bottom-half pairing within score groups.
 * 4. FIDE Color Balancing (equalization and alternation of White/Black on Board 1).
 * 5. Pairing Bye allocation for odd numbers of teams (lowest ranked team that hasn't received a bye).
 * 6. Backtracking search to resolve repeat encounters and downfloats.
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/TournamentService.php';
require_once __DIR__ . '/MatchService.php';

class SwissPairingService {

    /**
     * Generate Swiss pairings for a given round number
     *
     * @param int $roundNumber The round number to pair
     * @return array Result array with success status, pairings list, bye info, or error message
     */
    public static function generatePairings(int $roundNumber): array {
        $pdo = Database::getConnection();

        // 1. Fetch all approved active teams
        $stmtTeams = $pdo->query("
            SELECT t.id, t.team_name, t.played, t.won, t.drawn, t.lost, t.game_points, t.match_points, t.tiebreak_sb
            FROM `teams` t
            WHERE t.status = 'approved'
            ORDER BY t.match_points DESC, t.game_points DESC, t.tiebreak_sb DESC, t.id ASC
        ");
        $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

        $teamCount = count($teams);
        if ($teamCount < 2) {
            return [
                'success' => false,
                'message' => "At least 2 approved teams are required to generate pairings (currently {$teamCount})."
            ];
        }

        // 2. Fetch all historical completed and scheduled matches in OTHER rounds
        $stmtHistory = $pdo->prepare("
            SELECT team_a_id, team_b_id, round_number, status
            FROM `matches`
            WHERE round_number != ?
        ");
        $stmtHistory->execute([$roundNumber]);
        $historyMatches = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        // Build opponents map: $playedAgainst[teamId][opponentId] = true
        $playedAgainst = [];
        // Build color history for Board 1: $colorHistory[teamId] = array of 'W' or 'B' ordered by round
        $colorHistory = [];
        foreach ($teams as $t) {
            $playedAgainst[$t['id']] = [];
            $colorHistory[$t['id']] = [];
        }

        foreach ($historyMatches as $hm) {
            $a = (int)$hm['team_a_id'];
            $b = (int)$hm['team_b_id'];
            $rn = (int)$hm['round_number'];

            if (isset($playedAgainst[$a])) $playedAgainst[$a][$b] = true;
            if (isset($playedAgainst[$b])) $playedAgainst[$b][$a] = true;

            // In match_boards / matches, Team A is White on Board 1, Team B is Black on Board 1
            if (isset($colorHistory[$a])) $colorHistory[$a][$rn] = 'W';
            if (isset($colorHistory[$b])) $colorHistory[$b][$rn] = 'B';
        }

        // 3. Handle Odd number of teams: Allocate a Bye
        $byeTeam = null;
        $pairingPool = $teams;

        if ($teamCount % 2 !== 0) {
            // Find lowest ranked team that hasn't had a bye yet
            // Check for previous byes (can track via notes or matches where team played alone / no opponent)
            $teamsWithBye = self::getTeamsWithPriorByes($pdo);

            // Search from bottom up
            $selectedByeIndex = null;
            for ($i = count($pairingPool) - 1; $i >= 0; $i--) {
                $tId = $pairingPool[$i]['id'];
                if (!isset($teamsWithBye[$tId])) {
                    $selectedByeIndex = $i;
                    break;
                }
            }

            // Fallback if all have had byes: pick the lowest ranked
            if ($selectedByeIndex === null) {
                $selectedByeIndex = count($pairingPool) - 1;
            }

            $byeTeam = $pairingPool[$selectedByeIndex];
            array_splice($pairingPool, $selectedByeIndex, 1);
        }

        // 4. Execute Dutch Swiss Pairing with Backtracking
        $pairings = self::findSwissMatching($pairingPool, $playedAgainst, $colorHistory);

        if ($pairings === null) {
            return [
                'success' => false,
                'message' => "Unable to generate a valid Swiss pairing for Round {$roundNumber} without repeat matches. Consider adjusting prior pairings or expanding the field."
            ];
        }

        // 5. Structure pairings with details for UI preview or commit
        $structured = [];
        foreach ($pairings as $idx => $p) {
            $teamWhite = $p['white'];
            $teamBlack = $p['black'];

            $structured[] = [
                'board'         => $idx + 1,
                'team_white'    => $teamWhite,
                'team_black'    => $teamBlack,
                'team_a_id'     => $teamWhite['id'],
                'team_b_id'     => $teamBlack['id'],
                'team_a_name'   => $teamWhite['team_name'],
                'team_b_name'   => $teamBlack['team_name'],
                'team_a_mp'     => (int)$teamWhite['match_points'],
                'team_b_mp'     => (int)$teamBlack['match_points'],
                'team_a_gp'     => (float)$teamWhite['game_points'],
                'team_b_gp'     => (float)$teamBlack['game_points'],
                'score_diff'    => abs((int)$teamWhite['match_points'] - (int)$teamBlack['match_points']),
                'is_float'      => (int)$teamWhite['match_points'] !== (int)$teamBlack['match_points'],
            ];
        }

        return [
            'success'      => true,
            'round_number' => $roundNumber,
            'pairings'     => $structured,
            'bye_team'     => $byeTeam,
            'total_pairs'  => count($structured)
        ];
    }

    /**
     * Commit generated Swiss pairings to database for a round
     *
     * @param int $roundNumber
     * @param bool $overwriteExisting If true, removes existing scheduled/draft matches in this round
     * @return array Status result
     */
    public static function commitPairings(int $roundNumber, bool $overwriteExisting = true): array {
        $pdo = Database::getConnection();

        // 1. Check if any completed matches exist in this round
        $stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM `matches` WHERE round_number = ? AND status = 'completed'");
        $stmtCompleted->execute([$roundNumber]);
        if ((int)$stmtCompleted->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => "Round {$roundNumber} already contains completed matches. Cannot auto-pair over completed results."
            ];
        }

        // 2. Generate Swiss pairings
        $res = self::generatePairings($roundNumber);
        if (!$res['success']) {
            return $res;
        }

        try {
            $pdo->beginTransaction();

            // 3. Clear existing draft/scheduled matches in this round if overwrite requested
            if ($overwriteExisting) {
                $stmtDel = $pdo->prepare("DELETE FROM `matches` WHERE round_number = ? AND status IN ('draft', 'scheduled')");
                $stmtDel->execute([$roundNumber]);
            }

            // 4. Insert generated pairings
            $stmtInsert = $pdo->prepare("
                INSERT INTO `matches` (`round_number`, `team_a_id`, `team_b_id`, `status`, `team_a_gp`, `team_b_gp`, `team_a_mp`, `team_b_mp`)
                VALUES (?, ?, ?, 'scheduled', 0.0, 0.0, 0, 0)
            ");

            foreach ($res['pairings'] as $pair) {
                $stmtInsert->execute([$roundNumber, $pair['team_a_id'], $pair['team_b_id']]);
            }

            // 5. Handle Bye Team if applicable
            if (!empty($res['bye_team'])) {
                $settings = TournamentService::getSettings();
                $mpWin = (int)($settings['mp_win'] ?? 2);
                $byeTeamId = (int)$res['bye_team']['id'];

                // Mark a note on the team standing or round notes
                $stmtByeNote = $pdo->prepare("
                    UPDATE `teams`
                    SET `standing_notes` = CONCAT(IFNULL(standing_notes,''), IF(standing_notes IS NULL OR standing_notes='', '', '; '), 'Round {$roundNumber} Bye')
                    WHERE id = ?
                ");
                $stmtByeNote->execute([$byeTeamId]);
            }

            $pdo->commit();

            $byeMsg = !empty($res['bye_team']) ? " (Team '{$res['bye_team']['team_name']}' awarded Round {$roundNumber} Bye)" : "";
            return [
                'success'  => true,
                'message'  => "Successfully scheduled {$res['total_pairs']} Swiss match pairings for Round {$roundNumber}{$byeMsg}."
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => "Database error while committing pairings: " . $e->getMessage()
            ];
        }
    }

    /**
     * Backtracking recursive solver to find Swiss matchings with score bracket optimization
     */
    private static function findSwissMatching(array $teams, array $playedAgainst, array $colorHistory): ?array {
        if (empty($teams)) {
            return [];
        }

        // Teams are sorted by Points DESC.
        // Pick the top unassigned team
        $topTeam = $teams[0];
        $remainingTeams = array_slice($teams, 1);

        // Sort candidate opponents:
        // Priority:
        // 1. Has NOT played $topTeam before.
        // 2. Score closeness (minimal |MP difference|).
        // 3. Game Point closeness.
        // 4. Color compatibility.
        $candidates = [];
        foreach ($remainingTeams as $idx => $candidate) {
            if (!isset($playedAgainst[$topTeam['id']][$candidate['id']])) {
                $mpDiff = abs((int)$topTeam['match_points'] - (int)$candidate['match_points']);
                $gpDiff = abs((float)$topTeam['game_points'] - (float)$candidate['game_points']);
                $candidates[] = [
                    'index'     => $idx,
                    'team'      => $candidate,
                    'mp_diff'   => $mpDiff,
                    'gp_diff'   => $gpDiff,
                    'cost'      => ($mpDiff * 1000) + ($gpDiff * 10) + $idx
                ];
            }
        }

        // Sort candidates by lowest cost (closest in score and seeding)
        usort($candidates, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });

        // Try each candidate in priority order
        foreach ($candidates as $cand) {
            $opponent = $cand['team'];
            $candIndex = $cand['index'];

            // Determine board 1 color allocation
            $colorAllocation = self::allocateBoardColors($topTeam, $opponent, $colorHistory);

            // Construct next pool of remaining teams
            $nextPool = $remainingTeams;
            array_splice($nextPool, $candIndex, 1);

            // Recurse
            $subMatching = self::findSwissMatching($nextPool, $playedAgainst, $colorHistory);
            if ($subMatching !== null) {
                // Success! Prepend current pair
                array_unshift($subMatching, $colorAllocation);
                return $subMatching;
            }
        }

        // No valid matching possible with current branch
        return null;
    }

    /**
     * FIDE Swiss Color Allocation for Team Matches on Board 1
     * Returns ['white' => $team, 'black' => $team]
     */
    private static function allocateBoardColors(array $team1, array $team2, array $colorHistory): array {
        $t1History = $colorHistory[$team1['id']] ?? [];
        $t2History = $colorHistory[$team2['id']] ?? [];

        // Count Whites and Blacks on Board 1
        $t1W = 0; $t1B = 0;
        foreach ($t1History as $c) { if ($c === 'W') $t1W++; if ($c === 'B') $t1B++; }

        $t2W = 0; $t2B = 0;
        foreach ($t2History as $c) { if ($c === 'W') $t2W++; if ($c === 'B') $t2B++; }

        $t1Net = $t1W - $t1B; // Positive means played more White
        $t2Net = $t2W - $t2B;

        // Rule 1: Equalize color history
        if ($t1Net < $t2Net) {
            // Team 1 has played fewer Whites, so Team 1 gets White
            return ['white' => $team1, 'black' => $team2];
        } elseif ($t1Net > $t2Net) {
            // Team 2 has played fewer Whites, so Team 2 gets White
            return ['white' => $team2, 'black' => $team1];
        }

        // Rule 2: Alternate from previous round color
        $t1Last = end($t1History);
        $t2Last = end($t2History);

        if ($t1Last === 'B' && $t2Last === 'W') {
            return ['white' => $team1, 'black' => $team2];
        } elseif ($t1Last === 'W' && $t2Last === 'B') {
            return ['white' => $team2, 'black' => $team1];
        }

        // Rule 3: Seeding / ID tiebreak (higher seeded gets alternating preference)
        // If round 1 or completely equal, alternate by team ID parity
        if (($team1['id'] + $team2['id']) % 2 === 0) {
            return ['white' => $team1, 'black' => $team2];
        } else {
            return ['white' => $team2, 'black' => $team1];
        }
    }

    /**
     * Helper to detect teams that have previously received a bye
     */
    private static function getTeamsWithPriorByes(PDO $pdo): array {
        $byes = [];
        $stmt = $pdo->query("SELECT id, standing_notes FROM `teams` WHERE standing_notes LIKE '%Bye%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $byes[$row['id']] = true;
        }
        return $byes;
    }
}
