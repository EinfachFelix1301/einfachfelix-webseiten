<?php
/**
 * API Endpoint zum Abrufen eines Transcripts
 *
 * GET /api/get.php?id=<transcript_id>
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Nur GET erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ID prüfen
$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transcript ID']);
    exit;
}

// Transcript abrufen
try {
    $db = Database::getInstance();
    $transcript = $db->getTranscript($id);

    if (!$transcript) {
        http_response_code(404);
        echo json_encode(['error' => 'Transcript not found']);
        exit;
    }

    // HTML-Content nicht in API-Response (zu groß)
    unset($transcript['html_content']);

    echo json_encode([
        'success' => true,
        'transcript' => $transcript
    ]);

} catch (Exception $e) {
    error_log("Get transcript error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
