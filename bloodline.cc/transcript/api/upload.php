<?php
/**
 * API Endpoint für Transcript-Upload
 * POST /api/upload.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// API-Key aus verschiedenen Quellen holen (Apache/Nginx Kompatibilität)
$apiKey = '';

// Methode 1: Standard HTTP_AUTHORIZATION
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $apiKey = trim($matches[1]);
    }
}

// Methode 2: REDIRECT_HTTP_AUTHORIZATION (Apache mit mod_rewrite)
if (empty($apiKey) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
        $apiKey = trim($matches[1]);
    }
}

// Methode 3: getallheaders() Funktion
if (empty($apiKey) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

// Methode 4: Apache-spezifisch
if (empty($apiKey) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

// API-Key prüfen (timing-sicher, leerer Key nie gültig)
if (!defined('API_KEY') || (string) API_KEY === '' || $apiKey === '' || !hash_equals((string) API_KEY, (string) $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// JSON-Body lesen
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Pflichtfelder prüfen
$required = ['id', 'ticket_number', 'user_id', 'html_content'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

// Daten aufbereiten
$transcriptData = [
    'id' => $data['id'],
    'ticket_number' => $data['ticket_number'],
    'user_id' => (int) $data['user_id'],
    'user_name' => $data['user_name'] ?? 'Unknown',
    'category' => $data['category'] ?? 'support',
    'html_content' => $data['html_content'],
    'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
    'closed_at' => $data['closed_at'] ?? date('Y-m-d H:i:s'),
    'closed_by' => (int) ($data['closed_by'] ?? 0)
];

// In Datenbank speichern
try {
    $db = Database::getInstance();
    $success = $db->saveTranscript($transcriptData);

    if ($success) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id' => $transcriptData['id'],
            'url' => SITE_URL . '/view.php?id=' . $transcriptData['id']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save transcript']);
    }
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
