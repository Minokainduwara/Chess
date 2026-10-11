<?php
/**
 * Tournament Rules & Constants Configuration
 * FOT Knights Arena — Chess Tournament
 */

return [
    'tournament_name' => 'FOT Knights Arena',
    'organizer'       => 'Faculty of Technology, University of Ruhuna',
    'timezone'        => 'Asia/Colombo',
    'boards_per_match'=> 4,
    'total_rounds'    => 5,
    'total_teams'     => 6,
    'min_members'     => 4,
    'max_members'     => 6,
    'min_females'     => 1,
    'min_batches'     => 2,
    
    // Scoring constants
    'points' => [
        'match_win'         => 2,
        'match_draw'        => 1,
        'match_loss'        => 0,
        'match_loss_zero_gp'=> -1, // Penalty for losing all 4 boards (0 GP)
        'game_win'          => 1.0,
        'game_draw'         => 0.5,
        'game_loss'         => 0.0
    ]
];
