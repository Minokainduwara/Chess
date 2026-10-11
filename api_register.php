<?php
/**
 * Team Registration Processing Endpoint
 * Delegates to TeamService
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST allowed.',
        'errors'  => []
    ]);
    exit;
}

require_once __DIR__ . '/src/Services/TeamService.php';

$response = TeamService::registerTeam($_POST);

echo json_encode($response);
exit;
