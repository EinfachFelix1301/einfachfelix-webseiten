<?php
/**
 * API Endpoint zum Auflisten von Transcripts
 *
 * GET /api/list.php
 * GET /api/list.php?user_id=<discord_id>
 * GET /api/list.php?search=<query>
 */

header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Nur GET erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Auth prüfen
if (!DiscordAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = DiscordAuth::getUserId();
$isAdmin = DiscordAuth::isAdmin();

try {
    $db = Database::getInstance();

    // Suche
    $search = $_GET['search'] ?? '';
    if (!empty($search)) {
        if ($isAdmin) {
            $transcripts = $db->searchTranscripts($search);
        } else {
            $transcripts = $db->searchTranscripts($search, $userId);
        }
    }
    // Alle Transcripts (Admin) oder eigene
    elseif ($isAdmin) {
        $limit = min((int) ($_GET['limit'] ?? 100), 500);
        $offset = max((int) ($_GET['offset'] ?? 0), 0);
        $transcripts = $db->getAllTranscripts($limit, $offset);
    } else {
        $transcripts = $db->getTranscriptsForUser($userId);
    }

    echo json_encode([
        'success' => true,
        'is_admin' => $isAdmin,
        'count' => count($transcripts),
        'transcripts' => $transcripts
    ]);

} catch (Exception $e) {
    error_log("List transcripts error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
