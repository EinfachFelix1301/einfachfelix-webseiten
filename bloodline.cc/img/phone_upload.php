<?php
// ========================================
// BLOODLINE - PHONE UPLOAD ENDPOINT (Phone Custom)
// Akzeptiert: (A) base64-JSON { image, filename, mime, field }  [Phone Mode='base64']
//             (B) multipart $_FILES['file']                     [Phone / Mode='multipart']
// Antwort:    { "success": true, "url": "..." }  bzw. { "success": false, "message": "..." }
// ========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

// ========== KONFIGURATION ==========
define('PHONE_API_KEY', getenv('PHONE_API_KEY') ?: '');
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'mp4', 'webm', 'mp3', 'wav', 'ogg', 'm4a'];
$maxSize   = MAX_UPLOAD_SIZE;                 // MB
$uploadDir = __DIR__ . '/sd_phone';           // Storage-Ordner fuer Handy-Uploads
$baseUrl   = 'https://bloodline.cc/img/sd_phone/';

$MIME_EXT = [
    'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
    'image/gif' => 'gif', 'image/bmp' => 'bmp',
    'video/mp4' => 'mp4', 'video/webm' => 'webm',
    'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav',
    'audio/x-wav' => 'wav', 'audio/wave' => 'wav', 'audio/webm' => 'webm',
    'audio/mp4' => 'm4a', 'audio/aac' => 'm4a',
];

// ========== TEST-MODUS (kein Auth noetig) ==========
// Nur Erreichbarkeit melden — keine Pfade/PHP-Version/Server-Limits ohne Auth.
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Endpoint erreichbar',
        'modes'   => ['base64-json', 'multipart'],
    ], JSON_PRETTY_PRINT);
    exit;
}

function respond($ok, $extra = []) {
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

// ========== AUTH PRUEFEN ==========
$authHeader = '';
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (!empty($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

$apiKey = '';
if (preg_match('/^(Key|Bearer)\s+(.+)$/i', $authHeader, $matches)) {
    $apiKey = trim($matches[2]);
} else {
    $apiKey = trim($authHeader);
}

if (PHONE_API_KEY === '' || empty($apiKey) || !hash_equals(PHONE_API_KEY, (string)$apiKey)) {
    http_response_code(403);
    respond(false, ['message' => 'Unauthorized']);
}

// ========== UPLOAD-ORDNER ==========
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}
if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    @chmod($uploadDir, 0775);
    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        respond(false, ['message' => 'Upload dir not writable: ' . $uploadDir]);
    }
}

// ========== EINGANG BESTIMMEN ==========
$rawBytes = null;   // finale Datei-Bytes
$ext      = null;

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    // ----- (A) base64-JSON: { image, filename, mime, field } -----
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) {
        respond(false, ['message' => 'Invalid JSON body']);
    }
    $image = $data['image'] ?? '';
    if (!is_string($image) || $image === '') {
        respond(false, ['message' => 'No image in JSON']);
    }
    // data-URL-Prefix entfernen, auch mit Codec-Params
    // (data:audio/webm;codecs=opus;base64,XXXX). Kein PCRE auf dem Payload —
    // große Sprachmemos sprengen sonst pcre.backtrack_limit.
    if (strncmp($image, 'data:', 5) === 0) {
        $comma = strpos($image, ',');
        if ($comma !== false) {
            $header = substr($image, 5, $comma - 5);
            $image  = substr($image, $comma + 1);
            if (empty($data['mime'])) {
                $semi = strpos($header, ';');
                $data['mime'] = strtolower(trim($semi === false ? $header : substr($header, 0, $semi)));
            }
        }
    }
    $image = preg_replace('/\s+/', '', (string)$image);
    $rawBytes = base64_decode($image, true);
    if ($rawBytes === false || $rawBytes === '') {
        $rawBytes = base64_decode($image, false);
    }
    if ($rawBytes === false || $rawBytes === '') {
        respond(false, ['message' => 'Base64 decode failed']);
    }
    // Extension aus filename, sonst aus mime
    $fname = (string)($data['filename'] ?? '');
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
        $mime = strtolower((string)($data['mime'] ?? ''));
        $ext = $MIME_EXT[$mime] ?? 'png';
    }
} else {
    // ----- (B) multipart $_FILES['file'] -----
    if (empty($_FILES['file'])) {
        respond(false, ['message' => 'No file uploaded']);
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(false, ['message' => 'Upload error code: ' . $file['error']]);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $rawBytes = @file_get_contents($file['tmp_name']);
    if ($rawBytes === false) {
        respond(false, ['message' => 'Could not read uploaded file']);
    }
}

// ========== VALIDIERUNG ==========
if (!in_array($ext, $allowedExtensions, true)) {
    respond(false, ['message' => 'File type not allowed: ' . $ext]);
}
if (strlen($rawBytes) > $maxSize * 1024 * 1024) {
    respond(false, ['message' => 'File too large']);
}

// ========== SPEICHERN ==========
$uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $uniqueName;

$saved = @file_put_contents($targetPath, $rawBytes) !== false;
if (!$saved) {
    $err = error_get_last();
    respond(false, ['message' => 'Save failed: ' . ($err ? $err['message'] : 'unknown')]);
}
@chmod($targetPath, 0644);

respond(true, ['url' => $baseUrl . $uniqueName]);
?>
