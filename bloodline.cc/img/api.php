<?php
// ========================================
// BLOODLINE GALLERY - API
// ========================================

// Error reporting für Debug
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

// Version des API-Codes — wird an JEDE Antwort gehängt. So lässt sich prüfen,
// ob der Server wirklich den neuen Code fährt (Deploy/OpCache-Check).
define('API_VERSION', '2026-09-11-phone');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug-Logging nur wenn GALLERY_DEBUG (siehe config.php)
$debugLog = [];
function logDebug($message) {
    global $debugLog;
    if (!defined('GALLERY_DEBUG') || !GALLERY_DEBUG) {
        return;
    }
    $debugLog[] = $message;
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function debugServerLimits() {
    return [
        'app_max_upload_mb' => MAX_UPLOAD_SIZE,
        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        'php_post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
    ];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Unbekannte Aktion'];

// CSRF-Schutz: Cross-Origin-POSTs abweisen (nur eigene Hosts erlaubt)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $oHost = parse_url($origin, PHP_URL_HOST);
        if (!in_array($oHost, ['bloodline.cc', 'www.bloodline.cc', 'media.bloodline.cc'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cross-Origin abgelehnt']);
            exit;
        }
    }
}

logDebug("API called with action: $action");
logDebug("POST data: " . json_encode($_POST));
logDebug("Session ID: " . session_id());

// Sicherheit: relativen Pfad normalisieren. Gibt null zurueck bei '..'-Segmenten,
// Dotfile-Segmenten oder wenn der (naechste existierende) Pfad per realpath
// ausserhalb des Gallery-Roots liegt (z. B. Symlinks).
function cleanRelPath($path) {
    $path = str_replace(['\\', "\0"], '/', (string) $path);
    $parts = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..' || $seg[0] === '.') return null;
        $parts[] = $seg;
    }
    $path = implode('/', $parts);
    if ($path === '') return '';

    $root = realpath(__DIR__);
    $probe = __DIR__ . '/' . $path;
    while (!file_exists($probe) && dirname($probe) !== $probe) {
        $probe = dirname($probe);
    }
    $real = realpath($probe);
    if ($root === false || $real === false) return null;
    if ($real !== $root && strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) return null;
    return $path;
}

// Wie cleanRelPath, bricht bei ungueltigem Pfad aber mit Fehler ab
function validatePath($path) {
    $clean = cleanRelPath($path);
    if ($clean === null) {
        echo json_encode(['success' => false, 'message' => 'Ungültiger Pfad', 'api_version' => API_VERSION]);
        exit;
    }
    return $clean;
}

// Geschuetzte Dateinamen (Daten/Config/Code) — nie hochladen, ueberschreiben,
// umbenennen oder loeschen. Case-insensitive, gilt in jedem Ordner.
function isReservedName($name) {
    $n = strtolower(basename(str_replace('\\', '/', (string) $name)));
    if ($n === '' || $n[0] === '.') return true; // Dotfiles (.htaccess, .user.ini, ...)
    $reserved = [
        'settings.json', 'keys.json', 'antraege.json', 'whitelist.json', 'dc_names.json',
        'loadingscreen_cache.json', 'config.php', 'config.example.php', 'debug.log',
    ];
    if (in_array($n, $reserved, true)) return true;
    // Ausfuehrbarer Code, auch als Mehrfach-Endung (x.php.jpg)
    return (bool) preg_match('/\.(php\d*|phtml|phar|pht|phps)(\.|$)/i', $n);
}

// App-Ordner im Gallery-Root (nicht loeschen/umbenennen)
function isProtectedDir($relPath) {
    return in_array(strtolower(trim((string) $relPath, '/')), ['api', 'css', 'js'], true);
}

switch ($action) {

    // ========== PING (Version-/Deploy-Check) ==========
    case 'ping':
        $response = ['success' => true, 'features' => ['dupcheck' => true, 'overwrite' => true]];
        break;

    // ========== ORDNER ERSTELLEN ==========
    case 'create_folder':
        logDebug("create_folder: canManageFolders=" . (canManageFolders() ? 'true' : 'false'));
        logDebug("create_folder: isLoggedIn=" . (isLoggedIn() ? 'true' : 'false'));
        logDebug("create_folder: SESSION=" . json_encode($_SESSION));

        if (!canManageFolders()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            logDebug("create_folder: Permission denied");
            break;
        }

        $path = validatePath($_POST['path'] ?? '');
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['name'] ?? '');
        logDebug("create_folder: path=$path, name=$name");

        if (empty($name)) {
            $response = ['success' => false, 'message' => 'Ungültiger Ordnername'];
            break;
        }

        $fullPath = __DIR__;
        if (!empty($path)) {
            $fullPath .= '/' . $path;
        }
        $fullPath .= '/' . $name;
        logDebug("create_folder: fullPath=$fullPath");

        if (is_dir($fullPath)) {
            $response = ['success' => false, 'message' => 'Ordner existiert bereits'];
            break;
        }

        if (@mkdir($fullPath, 0755, true)) {
            $response = ['success' => true, 'message' => 'Ordner erstellt'];
            logDebug("create_folder: SUCCESS");
        } else {
            $error = error_get_last();
            $response = ['success' => false, 'message' => 'Fehler beim Erstellen'];
            logDebug("create_folder: FAILED - " . ($error ? $error['message'] : 'unknown'));
        }
        break;

    // ========== ORDNER LÖSCHEN ==========
    case 'delete_folder':
        if (!canManageFolders()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $path = validatePath($_POST['path'] ?? '');

        if (empty($path)) {
            $response = ['success' => false, 'message' => 'Kein Pfad angegeben'];
            break;
        }
        if (isProtectedDir($path)) {
            $response = ['success' => false, 'message' => 'Ordner ist geschützt'];
            break;
        }

        $fullPath = __DIR__ . '/' . $path;

        if (!is_dir($fullPath) || is_link($fullPath)) {
            $response = ['success' => false, 'message' => 'Ordner nicht gefunden'];
            break;
        }

        // Rekursiv löschen
        // Symlinks werden nur entfernt, nie verfolgt
        function deleteDir($dir) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                (is_dir($path) && !is_link($path)) ? deleteDir($path) : unlink($path);
            }
            return rmdir($dir);
        }
        // Geschuetzte Dateien (Config/Daten/Dotfiles) im Baum? Dann gar nicht loeschen.
        function dirHasReserved($dir) {
            foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $file) {
                $path = $dir . '/' . $file;
                if (isReservedName($file)) return true;
                if (is_dir($path) && !is_link($path) && dirHasReserved($path)) return true;
            }
            return false;
        }

        if (dirHasReserved($fullPath)) {
            $response = ['success' => false, 'message' => 'Ordner enthält geschützte Dateien'];
            break;
        }

        if (deleteDir($fullPath)) {
            $response = ['success' => true, 'message' => 'Ordner gelöscht'];
        } else {
            $response = ['success' => false, 'message' => 'Fehler beim Löschen'];
        }
        break;

    // ========== DUPLIKATE PRÜFEN ==========
    case 'check_duplicates':
        if (!canUpload()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $path = validatePath($_POST['path'] ?? '');
        $targetDir = __DIR__;
        if (!empty($path)) {
            $targetDir .= '/' . $path;
        }

        $fileNames = json_decode($_POST['files'] ?? '[]', true);
        $duplicates = [];

        // Alle existierenden Dateien laden (für case-insensitive + WinSCP-Vergleich)
        $existingFiles = @scandir($targetDir);
        $existingLower = [];
        $existingSanitizedLower = [];
        if ($existingFiles) {
            foreach ($existingFiles as $f) {
                if ($f === '.' || $f === '..') continue;
                $existingLower[strtolower($f)] = $f;
                // Auch sanitizierten Namen indexieren (fängt WinSCP-Dateien mit Leerzeichen etc.)
                $fExt = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $fStem = pathinfo($f, PATHINFO_FILENAME);
                $fIsVideo = is_gallery_video_ext($fExt);
                $fSanitized = $fIsVideo ? sanitize_upload_stem_video($fStem) : sanitize_upload_stem($fStem);
                if (!$fIsVideo && preg_match('/^weapon_/i', $fSanitized)) {
                    $fSanitized = 'WEAPON_' . substr($fSanitized, 7);
                }
                $existingSanitizedLower[strtolower($fSanitized . '.' . $fExt)] = $f;
            }
        }

        foreach ($fileNames as $fileName) {
            $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $isVideo = is_gallery_video_ext($ext);

            $safeName = $isVideo
                ? sanitize_upload_stem_video($nameWithoutExt)
                : sanitize_upload_stem($nameWithoutExt);

            if (!$isVideo && preg_match('/^weapon_/i', $safeName)) {
                $safeName = 'WEAPON_' . substr($safeName, 7);
            }

            $safeName = $safeName . '.' . $ext;
            $safeNameLower = strtolower($safeName);

            // Case-insensitive Prüfung + WinSCP-sanitized Prüfung
            if (isset($existingLower[$safeNameLower]) || isset($existingSanitizedLower[$safeNameLower])) {
                $duplicates[] = $fileName;
            }
        }

        $response = ['success' => true, 'duplicates' => $duplicates];
        break;

    // ========== BASE64 UPLOAD (umgeht WAF/Nginx-Datei-Sperren) ==========
    case 'upload_base64':
        @set_time_limit(0);
        if (!canUpload()) { echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']); exit; }

        $b64_uploadId    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['upload_id'] ?? '');
        $b64_chunkIndex  = max(0, (int)($_POST['chunk_index'] ?? 0));
        $b64_totalChunks = max(1, (int)($_POST['total_chunks'] ?? 1));
        $b64_path        = validatePath($_POST['path'] ?? '');
        $b64_customName  = trim($_POST['custom_name'] ?? '');
        $b64_targetFmt   = strtolower(trim($_POST['target_format'] ?? ''));
        $b64_origExt     = strtolower(trim($_POST['original_ext'] ?? ''));
        $b64_data        = $_POST['file_base64'] ?? '';

        if (!$b64_uploadId || $b64_data === '') {
            echo json_encode(['success' => false, 'message' => 'Keine Daten']); exit;
        }

        $b64_tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'blb64_' . $b64_uploadId;
        if (!is_dir($b64_tmpDir)) @mkdir($b64_tmpDir, 0775, true);
        if (!is_dir($b64_tmpDir)) { echo json_encode(['success' => false, 'message' => 'Temp-Ordner fehlt']); exit; }

        $b64_chunkFile = $b64_tmpDir . DIRECTORY_SEPARATOR . 'chunk_' . sprintf('%05d', $b64_chunkIndex);
        $b64_decoded = base64_decode($b64_data, true);
        if ($b64_decoded === false) { echo json_encode(['success' => false, 'message' => 'Base64-Dekodierung fehlgeschlagen']); exit; }
        file_put_contents($b64_chunkFile, $b64_decoded);

        $b64_received = count(glob($b64_tmpDir . DIRECTORY_SEPARATOR . 'chunk_*') ?: []);
        if ($b64_received < $b64_totalChunks) {
            echo json_encode(['success' => true, 'done' => false, 'received' => $b64_received, 'total' => $b64_totalChunks]); exit;
        }

        // Alle Chunks da – zusammensetzen
        $b64_assembled = $b64_tmpDir . DIRECTORY_SEPARATOR . 'assembled';
        $b64_fp = @fopen($b64_assembled, 'wb');
        if (!$b64_fp) { echo json_encode(['success' => false, 'message' => 'Assembled-Datei nicht erstellbar']); exit; }
        for ($b64_i = 0; $b64_i < $b64_totalChunks; $b64_i++) {
            $b64_cp = $b64_tmpDir . DIRECTORY_SEPARATOR . 'chunk_' . sprintf('%05d', $b64_i);
            if (file_exists($b64_cp)) { fwrite($b64_fp, file_get_contents($b64_cp)); @unlink($b64_cp); }
        }
        fclose($b64_fp);

        // Zielverzeichnis & Dateiname
        $b64_tDir = __DIR__;
        if (!empty($b64_path)) $b64_tDir .= '/' . $b64_path;
        if (!is_dir($b64_tDir) || !is_writable($b64_tDir)) {
            @unlink($b64_assembled); @rmdir($b64_tmpDir);
            echo json_encode(['success' => false, 'message' => 'Zielordner nicht beschreibbar']); exit;
        }

        $b64_ext    = !empty($b64_origExt) ? $b64_origExt : $b64_targetFmt;
        $b64_finExt = $b64_targetFmt ?: $b64_ext;
        $b64_allowed = getAllowedExtensions();
        if (!in_array($b64_ext, $b64_allowed, true) || !in_array($b64_finExt, $b64_allowed, true)) {
            @unlink($b64_assembled); @rmdir($b64_tmpDir);
            echo json_encode(['success' => false, 'message' => 'Dateityp nicht erlaubt']); exit;
        }

        $b64_isVid = is_gallery_video_ext($b64_ext);
        $b64_stem  = $b64_customName !== ''
            ? ($b64_isVid ? sanitize_upload_stem_video($b64_customName) : sanitize_upload_stem($b64_customName))
            : ($b64_isVid ? 'video' : 'file');
        if (!$b64_isVid && preg_match('/^weapon_/i', $b64_stem)) $b64_stem = 'WEAPON_' . substr($b64_stem, 7);
        $b64_safeName  = $b64_stem . '.' . $b64_finExt;
        $b64_targetPath = $b64_tDir . '/' . $b64_safeName;
        if (isReservedName($b64_safeName)) {
            @unlink($b64_assembled); @rmdir($b64_tmpDir);
            echo json_encode(['success' => false, 'message' => 'Dateiname ist geschützt']); exit;
        }

        // Existierende löschen (inkl. WinSCP)
        $b64_bnLower = strtolower($b64_safeName);
        foreach (@scandir($b64_tDir) ?: [] as $b64_ef) {
            if ($b64_ef === '.' || $b64_ef === '..' || isReservedName($b64_ef)) continue;
            $b64_efMatch = strtolower($b64_ef) === $b64_bnLower;
            if (!$b64_efMatch) {
                $b64_efExt = strtolower(pathinfo($b64_ef, PATHINFO_EXTENSION));
                if ($b64_efExt === $b64_finExt) {
                    $b64_efStem = pathinfo($b64_ef, PATHINFO_FILENAME);
                    $b64_efIsV  = is_gallery_video_ext($b64_efExt);
                    $b64_efSan  = $b64_efIsV ? sanitize_upload_stem_video($b64_efStem) : sanitize_upload_stem($b64_efStem);
                    if (!$b64_efIsV && preg_match('/^weapon_/i', $b64_efSan)) $b64_efSan = 'WEAPON_' . substr($b64_efSan, 7);
                    $b64_efMatch = strtolower($b64_efSan . '.' . $b64_efExt) === $b64_bnLower;
                }
            }
            if ($b64_efMatch) { @chmod($b64_tDir . '/' . $b64_ef, 0666); @unlink($b64_tDir . '/' . $b64_ef); }
        }

        $b64_saved = @rename($b64_assembled, $b64_targetPath);
        if (!$b64_saved) { $b64_saved = @copy($b64_assembled, $b64_targetPath); @unlink($b64_assembled); }
        @rmdir($b64_tmpDir);
        logDebug("upload_base64: saved=$b64_safeName result=" . ($b64_saved ? 'ok' : 'fail'));

        echo json_encode($b64_saved
            ? ['success' => true, 'done' => true, 'uploaded' => [$b64_safeName], 'message' => '1 Datei(en) hochgeladen']
            : ['success' => false, 'message' => 'Datei konnte nicht gespeichert werden']);
        exit;

    // ========== CHUNKED UPLOAD ==========
    case 'upload_chunk':
        @set_time_limit(0);
        if (!canUpload()) { echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']); exit; }

        $uploadId    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['upload_id'] ?? '');
        $chunkIndex  = max(0, (int)($_POST['chunk_index'] ?? 0));
        $totalChunks = max(1, (int)($_POST['total_chunks'] ?? 1));
        $chunkPath2  = validatePath($_POST['path'] ?? '');
        $customName2 = trim($_POST['custom_name'] ?? '');
        $targetFmt2  = strtolower(trim($_POST['target_format'] ?? ''));
        $origExt2    = strtolower(trim($_POST['original_ext'] ?? ''));

        if (!$uploadId || empty($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Chunk-Fehler']); exit;
        }

        $tmpDir2 = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bl_' . $uploadId;
        if (!is_dir($tmpDir2)) @mkdir($tmpDir2, 0775, true);
        if (!is_dir($tmpDir2)) { echo json_encode(['success' => false, 'message' => 'Temp-Ordner fehlt']); exit; }

        $chunkFile2 = $tmpDir2 . DIRECTORY_SEPARATOR . 'chunk_' . sprintf('%05d', $chunkIndex);
        if (!@move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile2)) {
            echo json_encode(['success' => false, 'message' => 'Chunk speichern fehlgeschlagen']); exit;
        }

        $received2 = count(glob($tmpDir2 . DIRECTORY_SEPARATOR . 'chunk_*') ?: []);
        if ($received2 < $totalChunks) {
            echo json_encode(['success' => true, 'done' => false, 'received' => $received2, 'total' => $totalChunks]); exit;
        }

        // Alle Chunks da – zusammensetzen
        $assembled2 = $tmpDir2 . DIRECTORY_SEPARATOR . 'assembled';
        $fp2 = @fopen($assembled2, 'wb');
        if (!$fp2) { echo json_encode(['success' => false, 'message' => 'Assembled-Datei nicht erstellbar']); exit; }
        for ($ci = 0; $ci < $totalChunks; $ci++) {
            $cp2 = $tmpDir2 . DIRECTORY_SEPARATOR . 'chunk_' . sprintf('%05d', $ci);
            if (file_exists($cp2)) { fwrite($fp2, file_get_contents($cp2)); @unlink($cp2); }
        }
        fclose($fp2);

        // Zielverzeichnis
        $tDir2 = __DIR__;
        if (!empty($chunkPath2)) $tDir2 .= '/' . $chunkPath2;
        if (!is_dir($tDir2) || !is_writable($tDir2)) {
            @unlink($assembled2); @rmdir($tmpDir2);
            echo json_encode(['success' => false, 'message' => 'Zielordner nicht beschreibbar']); exit;
        }

        $ext2     = !empty($origExt2) ? $origExt2 : $targetFmt2;
        $finExt2  = $targetFmt2 ?: $ext2;
        $allowed2 = getAllowedExtensions();
        if (!in_array($ext2, $allowed2, true) || !in_array($finExt2, $allowed2, true)) {
            @unlink($assembled2); @rmdir($tmpDir2);
            echo json_encode(['success' => false, 'message' => 'Dateityp nicht erlaubt']); exit;
        }

        $isVid2 = is_gallery_video_ext($ext2);
        $stem2  = $customName2 !== ''
            ? ($isVid2 ? sanitize_upload_stem_video($customName2) : sanitize_upload_stem($customName2))
            : ($isVid2 ? 'video' : 'file');
        if (!$isVid2 && preg_match('/^weapon_/i', $stem2)) $stem2 = 'WEAPON_' . substr($stem2, 7);
        $safeName2  = $stem2 . '.' . $finExt2;
        $targetPath2 = $tDir2 . '/' . $safeName2;
        if (isReservedName($safeName2)) {
            @unlink($assembled2); @rmdir($tmpDir2);
            echo json_encode(['success' => false, 'message' => 'Dateiname ist geschützt']); exit;
        }

        // Existierende löschen (inkl. WinSCP)
        $bnLower2 = strtolower($safeName2);
        foreach (@scandir($tDir2) ?: [] as $ef2) {
            if ($ef2 === '.' || $ef2 === '..' || isReservedName($ef2)) continue;
            $efMatch2 = strtolower($ef2) === $bnLower2;
            if (!$efMatch2) {
                $efExt2 = strtolower(pathinfo($ef2, PATHINFO_EXTENSION));
                if ($efExt2 === $finExt2) {
                    $efStem2 = pathinfo($ef2, PATHINFO_FILENAME);
                    $efIsV2  = is_gallery_video_ext($efExt2);
                    $efSan2  = $efIsV2 ? sanitize_upload_stem_video($efStem2) : sanitize_upload_stem($efStem2);
                    if (!$efIsV2 && preg_match('/^weapon_/i', $efSan2)) $efSan2 = 'WEAPON_' . substr($efSan2, 7);
                    $efMatch2 = strtolower($efSan2 . '.' . $efExt2) === $bnLower2;
                }
            }
            if ($efMatch2) { @chmod($tDir2 . '/' . $ef2, 0666); @unlink($tDir2 . '/' . $ef2); }
        }

        $saved2 = @rename($assembled2, $targetPath2);
        if (!$saved2) { $saved2 = @copy($assembled2, $targetPath2); @unlink($assembled2); }
        @rmdir($tmpDir2);

        if ($saved2) {
            echo json_encode(['success' => true, 'done' => true, 'uploaded' => [$safeName2], 'message' => '1 Datei(en) hochgeladen']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datei konnte nicht gespeichert werden']);
        }
        exit;

    // ========== BILD HOCHLADEN ==========
    case 'upload':
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '7200');
        logDebug("upload: canUpload=" . (canUpload() ? 'true' : 'false'));
        logDebug("upload: SESSION=" . json_encode($_SESSION));

        if (!canUpload()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            logDebug("upload: Permission denied");
            break;
        }

        $path = validatePath($_POST['path'] ?? '');
        $targetDir = __DIR__;
        if (!empty($path)) {
            $targetDir .= '/' . $path;
        }
        logDebug("upload: targetDir=$targetDir");

        if (!is_dir($targetDir)) {
            $response = ['success' => false, 'message' => 'Zielordner nicht gefunden'];
            logDebug("upload: Target dir not found");
            break;
        }

        if (empty($_FILES['files'])) {
            $response = ['success' => false, 'message' => 'Keine Dateien'];
            logDebug("upload: No files");
            break;
        }
        logDebug("upload: FILES=" . json_encode($_FILES));

        // Benutzerdefinierter Name und Zielformat
        $customName = $_POST['custom_name'] ?? '';
        $targetFormat = strtolower($_POST['target_format'] ?? '');
        $originalExt = strtolower($_POST['original_ext'] ?? '');
        logDebug("upload: customName=$customName, targetFormat=$targetFormat, originalExt=$originalExt");

        // Dateien die übersprungen werden sollen (Duplikate die nicht überschrieben werden)
        $skipFiles = json_decode($_POST['skip'] ?? '[]', true);
        if (!is_array($skipFiles)) $skipFiles = [];
        logDebug("upload: skipFiles=" . json_encode($skipFiles));

        // Overwrite-Flag: ohne dieses werden existierende Dateien NICHT überschrieben,
        // sondern als Duplikat zurückgemeldet (Frontend fragt dann nach).
        $overwrite = !empty($_POST['overwrite']) && $_POST['overwrite'] !== 'false';
        logDebug("upload: overwrite=" . ($overwrite ? 'true' : 'false'));

        $allowedExt = ALLOWED_EXTENSIONS;
        $uploaded = [];
        $duplicates = [];
        $errors = [];
        $maxSize = MAX_UPLOAD_SIZE * 1024 * 1024;

        $files = $_FILES['files'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            // Überspringe Dateien die in der Skip-Liste sind
            if (in_array($fileName, $skipFiles, true)) {
                logDebug("upload: Skipping $fileName (in skip list)");
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "$fileName: Upload-Fehler ($fileError)";
                continue;
            }

            if ($fileSize > $maxSize) {
                $errors[] = "$fileName: Zu groß (max " . MAX_UPLOAD_SIZE . "MB)";
                continue;
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "$fileName: Ungültiger Dateityp ($ext)";
                continue;
            }

            // Zielformat zuerst (beeinflusst Video-Sanitizing)
            $finalExt = !empty($targetFormat) && in_array($targetFormat, $allowedExt) ? $targetFormat : $ext;
            $isVideo = is_gallery_video_ext($finalExt);

            if (!empty($customName)) {
                $safeName = $isVideo
                    ? sanitize_upload_stem_video($customName)
                    : sanitize_upload_stem($customName);
            } else {
                $stem = pathinfo($fileName, PATHINFO_FILENAME);
                $safeName = $isVideo ? sanitize_upload_stem_video($stem) : sanitize_upload_stem($stem);
            }

            if (!$isVideo && preg_match('/^weapon_/i', $safeName)) {
                $safeName = 'WEAPON_' . substr($safeName, 7);
            }

            // Prüfen ob Konvertierung erforderlich
            $needsConversion = ($ext !== $finalExt);

            // Finaler Dateiname
            $safeName = $safeName . '.' . $finalExt;
            if (isReservedName($safeName)) {
                $errors[] = "$fileName: Dateiname ist geschützt";
                continue;
            }

            $targetPath = $targetDir . '/' . $safeName;
            logDebug("upload: Processing $fileName -> $safeName, targetPath=$targetPath, needsConversion=$needsConversion");

            // Prüfe ob Verzeichnis beschreibbar
            if (!is_writable($targetDir)) {
                $errors[] = "$fileName: Zielordner nicht beschreibbar";
                logDebug("upload: Directory not writable: $targetDir");
                continue;
            }

            // Existierende Dateien finden (case-insensitive + WinSCP-sanitized)
            $baseNameLower = strtolower(basename($targetPath));
            $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            $matchedExisting = [];
            $existingFiles = @scandir($targetDir);
            if ($existingFiles) {
                foreach ($existingFiles as $existingFile) {
                    if ($existingFile === '.' || $existingFile === '..' || isReservedName($existingFile)) continue;
                    $matches = strtolower($existingFile) === $baseNameLower;
                    if (!$matches) {
                        // WinSCP-Dateien: sanitizierten Namen vergleichen
                        $eExt = strtolower(pathinfo($existingFile, PATHINFO_EXTENSION));
                        if ($eExt === $targetExt) {
                            $eStem = pathinfo($existingFile, PATHINFO_FILENAME);
                            $eIsVideo = is_gallery_video_ext($eExt);
                            $eSanitized = $eIsVideo ? sanitize_upload_stem_video($eStem) : sanitize_upload_stem($eStem);
                            if (!$eIsVideo && preg_match('/^weapon_/i', $eSanitized)) {
                                $eSanitized = 'WEAPON_' . substr($eSanitized, 7);
                            }
                            $matches = strtolower($eSanitized . '.' . $eExt) === $baseNameLower;
                        }
                    }
                    if ($matches) $matchedExisting[] = $existingFile;
                }
            }

            // Duplikat gefunden und NICHT überschreiben -> zurückmelden (mit URL der ALTEN Datei), nicht speichern
            if (!empty($matchedExisting) && !$overwrite) {
                $existingName = $matchedExisting[0];
                $existingUrl = MEDIA_PUBLIC_BASE . '/' . ($path !== '' ? $path . '/' : '') . rawurlencode($existingName);
                $duplicates[] = ['name' => $fileName, 'existing' => $existingName, 'url' => $existingUrl];
                logDebug("upload: DUPLICATE (kein overwrite): $fileName -> vorhanden: $existingName");
                continue;
            }
            // Überschreiben: alle passenden Varianten löschen
            if (!empty($matchedExisting) && $overwrite) {
                foreach ($matchedExisting as $existingFile) {
                    $existingPath = $targetDir . '/' . $existingFile;
                    logDebug("upload: Overwrite - lösche $existingFile");
                    @chmod($existingPath, 0666);
                    @unlink($existingPath);
                }
            }

            // Bildkonvertierung
            if ($needsConversion && $ext !== 'svg' && $finalExt !== 'svg') {
                $sourceImage = null;

                // Quellbild laden
                switch ($ext) {
                    case 'png':
                        $sourceImage = @imagecreatefrompng($tmpName);
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $sourceImage = @imagecreatefromjpeg($tmpName);
                        break;
                    case 'webp':
                        $sourceImage = @imagecreatefromwebp($tmpName);
                        break;
                    case 'gif':
                        $sourceImage = @imagecreatefromgif($tmpName);
                        break;
                    case 'bmp':
                        $sourceImage = @imagecreatefrombmp($tmpName);
                        break;
                }

                if ($sourceImage === false || $sourceImage === null) {
                    $errors[] = "$fileName: Bild konnte nicht gelesen werden";
                    logDebug("upload: Failed to load source image");
                    continue;
                }

                // Transparenz erhalten für PNG/WebP
                if (in_array($finalExt, ['png', 'webp'])) {
                    imagesavealpha($sourceImage, true);
                    imagealphablending($sourceImage, false);
                }

                $conversionSuccess = false;

                // In Zielformat speichern
                switch ($finalExt) {
                    case 'png':
                        $conversionSuccess = imagepng($sourceImage, $targetPath, 9);
                        break;
                    case 'jpg':
                    case 'jpeg':
                        // Bei JPG: Transparenz in Weiß umwandeln
                        $width = imagesx($sourceImage);
                        $height = imagesy($sourceImage);
                        $jpgImage = imagecreatetruecolor($width, $height);
                        $white = imagecolorallocate($jpgImage, 255, 255, 255);
                        imagefill($jpgImage, 0, 0, $white);
                        imagecopy($jpgImage, $sourceImage, 0, 0, 0, 0, $width, $height);
                        $conversionSuccess = imagejpeg($jpgImage, $targetPath, 90);
                        imagedestroy($jpgImage);
                        break;
                    case 'webp':
                        $conversionSuccess = imagewebp($sourceImage, $targetPath, 90);
                        break;
                    case 'gif':
                        $conversionSuccess = imagegif($sourceImage, $targetPath);
                        break;
                }

                imagedestroy($sourceImage);

                if ($conversionSuccess) {
                    @chmod($targetPath, 0644);
                    $uploaded[] = basename($targetPath);
                    logDebug("upload: Converted $ext to $finalExt: " . basename($targetPath));
                } else {
                    $errors[] = "$fileName: Konvertierung fehlgeschlagen";
                    logDebug("upload: Conversion failed");
                }
            } else {
                // Keine Konvertierung - normale Datei verschieben
                logDebug("upload: Moving $tmpName to $targetPath");
                $success = false;

                // Versuch 1: move_uploaded_file
                if (@move_uploaded_file($tmpName, $targetPath)) {
                    $success = true;
                    logDebug("upload: move_uploaded_file SUCCESS");
                } else {
                    logDebug("upload: move_uploaded_file failed, trying copy");
                    // Versuch 2: copy
                    if (@copy($tmpName, $targetPath)) {
                        @unlink($tmpName);
                        $success = true;
                        logDebug("upload: copy SUCCESS");
                    } else {
                        logDebug("upload: copy also failed");
                    }
                }

                if ($success) {
                    @chmod($targetPath, 0644);
                    $uploaded[] = basename($targetPath);
                    logDebug("upload: File saved: " . basename($targetPath));
                } else {
                    $lastError = error_get_last();
                    $errorMsg = $lastError ? $lastError['message'] : 'Unbekannter Fehler';
                    $errors[] = "$fileName: Speichern fehlgeschlagen - $errorMsg";
                    logDebug("upload: FAILED - $errorMsg");
                }
            }
        }

        $response = [
            'success' => count($uploaded) > 0 || count($duplicates) > 0,
            'uploaded' => $uploaded,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'message' => count($uploaded) > 0
                ? count($uploaded) . ' Datei(en) hochgeladen'
                : (count($duplicates) > 0
                    ? count($duplicates) . ' Datei(en) existieren bereits'
                    : (count($errors) > 0 ? $errors[0] : 'Upload fehlgeschlagen'))
        ];
        if (defined('GALLERY_DEBUG') && GALLERY_DEBUG) {
            $response['server_limits'] = debugServerLimits();
        }
        break;

    // ========== DATEI LÖSCHEN (Bilder + andere Dateien) ==========
    case 'delete_image':
        if (!canUpload()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $path = validatePath($_POST['path'] ?? '');

        if (empty($path)) {
            $response = ['success' => false, 'message' => 'Kein Pfad'];
            break;
        }
        if (isReservedName($path)) {
            $response = ['success' => false, 'message' => 'Datei ist geschützt'];
            break;
        }

        $fullPath = __DIR__ . '/' . $path;

        if (!file_exists($fullPath) || is_dir($fullPath)) {
            $response = ['success' => false, 'message' => 'Datei nicht gefunden'];
            break;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            $response = ['success' => false, 'message' => 'Dateityp nicht erlaubt'];
            break;
        }

        if (unlink($fullPath)) {
            $response = ['success' => true, 'message' => 'Datei gelöscht'];
        } else {
            $response = ['success' => false, 'message' => 'Fehler beim Löschen'];
        }
        break;

    // ========== DATEI UMBENENNEN (Bilder + andere Dateien) ==========
    case 'rename_image':
        logDebug("rename_image: canUpload=" . (canUpload() ? 'true' : 'false'));

        if (!canUpload()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $oldPath = validatePath($_POST['old_path'] ?? '');
        $newName = $_POST['new_name'] ?? '';
        logDebug("rename_image: oldPath=$oldPath, newName=$newName");

        if (empty($oldPath) || empty($newName)) {
            $response = ['success' => false, 'message' => 'Ungültige Parameter'];
            break;
        }
        if (isReservedName($oldPath)) {
            $response = ['success' => false, 'message' => 'Datei ist geschützt'];
            break;
        }

        $oldFullPath = __DIR__ . '/' . $oldPath;

        if (!file_exists($oldFullPath) || is_dir($oldFullPath)) {
            $response = ['success' => false, 'message' => 'Datei nicht gefunden'];
            break;
        }

        // Alte Erweiterung behalten
        $oldExt = strtolower(pathinfo($oldFullPath, PATHINFO_EXTENSION));
        if (!in_array($oldExt, ALLOWED_EXTENSIONS)) {
            $response = ['success' => false, 'message' => 'Dateityp nicht erlaubt'];
            break;
        }

        $newStem = pathinfo($newName, PATHINFO_FILENAME);
        $newNameClean = is_gallery_video_ext($oldExt)
            ? sanitize_upload_stem_video($newStem)
            : sanitize_upload_stem($newStem);

        $parentDir = dirname($oldFullPath);
        $newFullPath = $parentDir . '/' . $newNameClean . '.' . $oldExt;
        logDebug("rename_image: newFullPath=$newFullPath");

        if (isReservedName($newNameClean . '.' . $oldExt)) {
            $response = ['success' => false, 'message' => 'Dateiname ist geschützt'];
            break;
        }

        if (file_exists($newFullPath)) {
            $response = ['success' => false, 'message' => 'Dateiname existiert bereits'];
            break;
        }

        if (rename($oldFullPath, $newFullPath)) {
            $response = ['success' => true, 'message' => 'Datei umbenannt', 'newName' => $newNameClean . '.' . $oldExt];
            logDebug("rename_image: SUCCESS");
        } else {
            $error = error_get_last();
            $response = ['success' => false, 'message' => 'Fehler beim Umbenennen'];
            logDebug("rename_image: FAILED - " . ($error ? $error['message'] : 'unknown'));
        }
        break;

    // ========== ORDNER UMBENENNEN ==========
    case 'rename_folder':
        logDebug("rename_folder: canManageFolders=" . (canManageFolders() ? 'true' : 'false'));
        logDebug("rename_folder: SESSION=" . json_encode($_SESSION));

        if (!canManageFolders()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            logDebug("rename_folder: Permission denied");
            break;
        }

        $oldPath = validatePath($_POST['old_path'] ?? '');
        $newName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['new_name'] ?? '');
        logDebug("rename_folder: oldPath=$oldPath, newName=$newName");

        if (empty($oldPath) || empty($newName)) {
            $response = ['success' => false, 'message' => 'Ungültige Parameter'];
            break;
        }

        if (isProtectedDir($oldPath)) {
            $response = ['success' => false, 'message' => 'Ordner ist geschützt'];
            break;
        }

        $oldFullPath = __DIR__ . '/' . $oldPath;
        $parentDir = dirname($oldFullPath);
        $newFullPath = $parentDir . '/' . $newName;
        logDebug("rename_folder: oldFullPath=$oldFullPath, newFullPath=$newFullPath");

        if (!is_dir($oldFullPath)) {
            $response = ['success' => false, 'message' => 'Ordner nicht gefunden'];
            logDebug("rename_folder: Old folder not found");
            break;
        }

        if (is_dir($newFullPath)) {
            $response = ['success' => false, 'message' => 'Name existiert bereits'];
            break;
        }

        if (rename($oldFullPath, $newFullPath)) {
            $response = ['success' => true, 'message' => 'Ordner umbenannt'];
            logDebug("rename_folder: SUCCESS");
        } else {
            $error = error_get_last();
            $response = ['success' => false, 'message' => 'Fehler beim Umbenennen'];
            logDebug("rename_folder: FAILED - " . ($error ? $error['message'] : 'unknown'));
        }
        break;

    // ========== MEHRERE DATEIEN LÖSCHEN ==========
    case 'delete_multiple':
        if (!canUpload()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $paths = json_decode($_POST['paths'] ?? '[]', true);
        if (!is_array($paths) || empty($paths)) {
            $response = ['success' => false, 'message' => 'Keine Dateien angegeben'];
            break;
        }

        $deleted = 0;
        $errors = [];

        foreach ($paths as $path) {
            $path = cleanRelPath(is_string($path) ? $path : '');
            if (empty($path)) continue;
            if (isReservedName($path)) {
                $errors[] = basename($path) . ': Geschützt';
                continue;
            }

            $fullPath = __DIR__ . '/' . $path;
            if (!file_exists($fullPath) || is_dir($fullPath)) {
                $errors[] = basename($path) . ': Nicht gefunden';
                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                $errors[] = basename($path) . ': Typ nicht erlaubt';
                continue;
            }

            if (@unlink($fullPath)) {
                $deleted++;
            } else {
                $errors[] = basename($path) . ': Fehler';
            }
        }

        $response = [
            'success' => $deleted > 0,
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => $deleted > 0 ? "$deleted Datei(en) gelöscht" : 'Keine Dateien gelöscht'
        ];
        break;

    // ========== EINSTELLUNGEN LADEN (Recht: settings) ==========
    case 'get_settings':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $s = loadSettings();
        $response = [
            'success'    => true,
            'roles'      => $s['roles'] ?? [],
            'users'      => $s['users'] ?? [],
            'names'      => fetchDiscordUsernames(array_keys($s['users'] ?? [])),
            'antrag'     => $s['antrag'] ?? [],
            'allPerms'   => ALL_PERMS,
            'permLabels' => PERM_LABELS,
            'me'         => $_SESSION['discord_user_id'] ?? '',
        ];
        break;

    // ========== ROLLE ANLEGEN / AENDERN ==========
    case 'save_role':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $roleKey = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $_POST['key'] ?? ''));
        $label   = trim($_POST['label'] ?? '');
        $color   = trim($_POST['color'] ?? '#ff3b3b');
        $perms   = $_POST['perms'] ?? [];
        if (is_string($perms)) $perms = array_filter(explode(',', $perms));
        $perms   = array_values(array_intersect(ALL_PERMS, (array)$perms));

        if ($roleKey === '' || $label === '') {
            $response = ['success' => false, 'message' => 'Key und Name nötig'];
            break;
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#ff3b3b';

        $s = loadSettings();
        $s['roles'][$roleKey] = ['label' => $label, 'color' => $color, 'perms' => $perms];
        if (saveSettings($s)) {
            $response = ['success' => true, 'message' => 'Rolle gespeichert'];
        } else {
            $response = ['success' => false, 'message' => 'Fehler beim Speichern'];
        }
        break;

    // ========== ROLLE LOESCHEN ==========
    case 'delete_role':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $roleKey = $_POST['key'] ?? '';
        $s = loadSettings();
        if (!isset($s['roles'][$roleKey])) {
            $response = ['success' => false, 'message' => 'Rolle nicht gefunden'];
            break;
        }
        // Nicht loeschen wenn danach niemand mehr 'settings' haette
        $test = $s;
        unset($test['roles'][$roleKey]);
        $stillAdmin = false;
        foreach (($test['users'] ?? []) as $uid => $urs) {
            foreach ((array)$urs as $rk) {
                if ($rk !== $roleKey && in_array('settings', $test['roles'][$rk]['perms'] ?? [], true)) { $stillAdmin = true; break 2; }
            }
        }
        if (!$stillAdmin) {
            $response = ['success' => false, 'message' => 'Abgelehnt: danach hätte niemand mehr das Recht „Einstellungen“.'];
            break;
        }
        unset($s['roles'][$roleKey]);
        foreach ($s['users'] as $uid => $urs) {
            $s['users'][$uid] = array_values(array_filter((array)$urs, fn($r) => $r !== $roleKey));
        }
        $response = saveSettings($s)
            ? ['success' => true, 'message' => 'Rolle gelöscht']
            : ['success' => false, 'message' => 'Fehler beim Speichern'];
        break;

    // ========== USER + ROLLEN SETZEN ==========
    case 'set_user_roles':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $userId = preg_replace('/[^0-9]/', '', $_POST['user_id'] ?? '');
        $roles  = $_POST['roles'] ?? [];
        if (is_string($roles)) $roles = array_filter(explode(',', $roles));

        if (empty($userId) || strlen($userId) < 17) {
            $response = ['success' => false, 'message' => 'Ungültige Discord ID'];
            break;
        }
        $s = loadSettings();
        $validRoles = array_values(array_intersect(array_keys($s['roles']), (array)$roles));

        // Selbstschutz: eigenes 'settings'-Recht nicht entfernen
        $me = $_SESSION['discord_user_id'] ?? '';
        if ($userId === $me) {
            $hasSettings = false;
            foreach ($validRoles as $rk) {
                if (in_array('settings', $s['roles'][$rk]['perms'] ?? [], true)) { $hasSettings = true; break; }
            }
            if (!$hasSettings) {
                $response = ['success' => false, 'message' => 'Du kannst dir selbst das Recht „Einstellungen“ nicht entziehen'];
                break;
            }
        }

        $s['users'][$userId] = $validRoles;
        $response = saveSettings($s)
            ? ['success' => true, 'message' => 'Gespeichert']
            : ['success' => false, 'message' => 'Fehler beim Speichern'];
        break;

    // ========== USER ENTFERNEN ==========
    case 'remove_user':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $userId = preg_replace('/[^0-9]/', '', $_POST['user_id'] ?? '');
        $me = $_SESSION['discord_user_id'] ?? '';
        if (empty($userId)) {
            $response = ['success' => false, 'message' => 'Ungültige Discord ID'];
            break;
        }
        if ($userId === $me) {
            $response = ['success' => false, 'message' => 'Du kannst dich nicht selbst entfernen'];
            break;
        }
        $s = loadSettings();
        unset($s['users'][$userId]);
        $response = saveSettings($s)
            ? ['success' => true, 'message' => 'User entfernt']
            : ['success' => false, 'message' => 'Fehler beim Speichern'];
        break;

    // ========== ANTRAG-ZIELORDNER SPEICHERN ==========
    case 'save_antrag_folders':
        if (!canManageUsers()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $pending = cleanRelPath($_POST['pending_folder'] ?? '') ?? '';
        $targets = $_POST['target_folders'] ?? [];
        if (is_string($targets)) $targets = array_filter(array_map('trim', explode("\n", $targets)));
        $clean = [];
        foreach ((array)$targets as $t) {
            $t = cleanRelPath(is_string($t) ? $t : '');
            if ($t !== null && $t !== '') $clean[] = $t;
        }
        if ($pending === '')  $pending = 'phone/eingereicht';
        if (empty($clean))    $clean = ['phone/ic_bilder'];

        $s = loadSettings();
        $s['antrag']['pending_folder'] = $pending;
        $s['antrag']['target_folders'] = array_values(array_unique($clean));
        $response = saveSettings($s)
            ? ['success' => true, 'message' => 'Zielordner gespeichert', 'antrag' => $s['antrag']]
            : ['success' => false, 'message' => 'Fehler beim Speichern'];
        break;

    // ========== TEST ENDPUNKT ==========
    case 'test':
        $response = ['success' => true, 'message' => 'API funktioniert'];
        // Server-/Session-Details nur fuer Admins (keine Pfade/PHP-Version/Session-ID oeffentlich)
        if (isAdmin()) {
            $response['gallery_debug'] = defined('GALLERY_DEBUG') && GALLERY_DEBUG;
            $response['session'] = [
                'loggedIn' => isLoggedIn(),
                'canUpload' => canUpload(),
                'canManageFolders' => canManageFolders(),
                'canManageUsers' => canManageUsers(),
                'isAdmin' => true,
                'user' => getUser()
            ];
            $response['server'] = [
                'php_version' => PHP_VERSION,
                'dir' => __DIR__,
                'writable' => is_writable(__DIR__)
            ];
            $response['server_limits'] = debugServerLimits();
        }
        break;

    // ========== MEDIA-ANTRAG EINREICHEN (Discord-Login erforderlich) ==========
    case 'antrag_submit':
    case 'submit_request':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => 'Bitte zuerst mit Discord anmelden', 'needLogin' => true];
            break;
        }
        // Name/Notiz: Design sendet name/note, Fallback auf alte Keys
        $serverName  = trim($_POST['name'] ?? $_POST['einreicher_name'] ?? '');
        $notiz       = trim($_POST['note'] ?? $_POST['notiz'] ?? '');
        $wunschInput = trim($_POST['wunschname'] ?? '');

        if ($serverName === '') {
            $response = ['success' => false, 'message' => 'Bitte deinen Namen im Server angeben'];
            break;
        }
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $response = ['success' => false, 'message' => 'Kein Bild hochgeladen'];
            break;
        }

        $antragFile = $_FILES['file'];
        $antragExt = strtolower(pathinfo($antragFile['name'], PATHINFO_EXTENSION));
        if (!in_array($antragExt, ANTRAG_IMAGE_EXTENSIONS, true)) {
            $response = ['success' => false, 'message' => 'Nur Bilder erlaubt: ' . implode(', ', ANTRAG_IMAGE_EXTENSIONS)];
            break;
        }
        if ($antragFile['size'] > ANTRAG_MAX_SIZE * 1024 * 1024) {
            $response = ['success' => false, 'message' => 'Bild zu groß (max ' . ANTRAG_MAX_SIZE . ' MB)'];
            break;
        }

        // Echtes Bild? (Schutz gegen umbenannte Dateien)
        $imgCheck = @getimagesize($antragFile['tmp_name']);
        if ($imgCheck === false) {
            $response = ['success' => false, 'message' => 'Datei ist kein gültiges Bild'];
            break;
        }

        // Dateiname automatisch link-sicher (aus Wunsch oder Original)
        $baseStem = $wunschInput !== '' ? $wunschInput : pathinfo($antragFile['name'], PATHINFO_FILENAME);
        $wunschname = sanitize_upload_stem($baseStem);

        $pendingDir = getAntragPendingDir();
        if (!is_dir($pendingDir)) {
            @mkdir($pendingDir, 0775, true);
        }
        if (!is_dir($pendingDir) || !is_writable($pendingDir)) {
            $response = ['success' => false, 'message' => 'Eingereicht-Ordner nicht beschreibbar'];
            break;
        }

        $pendingName = bin2hex(random_bytes(16)) . '.' . $antragExt;
        $pendingPath = $pendingDir . '/' . $pendingName;

        $movedOk = @move_uploaded_file($antragFile['tmp_name'], $pendingPath);
        if (!$movedOk) $movedOk = @copy($antragFile['tmp_name'], $pendingPath);
        if (!$movedOk) {
            $response = ['success' => false, 'message' => 'Bild speichern fehlgeschlagen'];
            break;
        }
        @chmod($pendingPath, 0644);

        $antraege = loadAntraege();
        $antraege[] = [
            'id'            => bin2hex(random_bytes(8)),
            'discord_id'    => $_SESSION['discord_user_id'] ?? null,
            'discord_name'  => $_SESSION['discord_username'] ?? null,
            'server_name'   => mb_substr($serverName, 0, 60),
            'wunschname'    => $wunschname,
            'grund'         => mb_substr($notiz, 0, 1000),
            'pending_file'  => $pendingName,
            'ext'           => $antragExt,
            'status'        => 'pending',
            'created_at'    => time(),
            'reviewed_by'   => null,
            'reviewed_at'   => null,
            'reject_reason' => null,
            'final_url'     => null,
        ];
        saveAntraege($antraege);
        logDebug("antrag_submit: neuer Antrag von " . $_SESSION['discord_user_id']);

        $response = ['success' => true, 'message' => 'Antrag eingereicht! Das Team prüft ihn.'];
        break;

    // ========== ANTRAEGE LISTE (Recht: antraege) ==========
    case 'antrag_list':
        if (!canManageAntraege()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $antraege = loadAntraege();
        usort($antraege, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));

        $pendingUrl = getAntragPendingUrl();
        $out = array_map(function ($a) use ($pendingUrl) {
            $a['pending_url'] = ($a['status'] ?? '') === 'pending' && !empty($a['pending_file'])
                ? $pendingUrl . $a['pending_file']
                : null;
            return $a;
        }, $antraege);

        $response = [
            'success'      => true,
            'antraege'     => $out,
            'pendingCount' => getPendingAntragCount(),
            'targetFolders'=> getAntragTargetFolders(),
        ];
        break;

    // ========== ANTRAG GENEHMIGEN (Recht: antraege) ==========
    case 'antrag_approve':
        if (!canManageAntraege()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $antragId = $_POST['id'] ?? '';
        $editName = trim($_POST['name'] ?? '');
        $targetRel = trim($_POST['target'] ?? '');

        // Zielordner muss aus der erlaubten Liste stammen
        $allowedTargets = getAntragTargetFolders();
        if ($targetRel === '') $targetRel = $allowedTargets[0];
        if (!in_array($targetRel, $allowedTargets, true)) {
            $response = ['success' => false, 'message' => 'Ungültiger Zielordner'];
            break;
        }

        $antraege = loadAntraege();
        $idx = null;
        foreach ($antraege as $i => $a) {
            if (($a['id'] ?? '') === $antragId) { $idx = $i; break; }
        }
        if ($idx === null) {
            $response = ['success' => false, 'message' => 'Antrag nicht gefunden'];
            break;
        }
        if ($antraege[$idx]['status'] !== 'pending') {
            $response = ['success' => false, 'message' => 'Antrag bereits bearbeitet'];
            break;
        }

        $ext = $antraege[$idx]['ext'];
        $stem = sanitize_upload_stem($editName !== '' ? $editName : $antraege[$idx]['wunschname']);
        $srcPath = getAntragPendingDir() . '/' . $antraege[$idx]['pending_file'];

        if (!is_file($srcPath)) {
            $response = ['success' => false, 'message' => 'Pending-Datei fehlt'];
            break;
        }

        $targetDir = antragFolderDir($targetRel);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        // Kollision: name.png -> name-1.png -> name-2.png
        $finalName = $stem . '.' . $ext;
        $n = 1;
        while (file_exists($targetDir . '/' . $finalName)) {
            $finalName = $stem . '-' . $n . '.' . $ext;
            $n++;
        }
        $finalPath = $targetDir . '/' . $finalName;

        $moved = @rename($srcPath, $finalPath);
        if (!$moved) {
            $moved = @copy($srcPath, $finalPath);
            if ($moved) @unlink($srcPath);
        }
        if (!$moved) {
            $response = ['success' => false, 'message' => 'Verschieben nach ' . $targetRel . ' fehlgeschlagen'];
            break;
        }
        @chmod($finalPath, 0644);

        $finalUrl = antragFolderUrl($targetRel) . $finalName;

        // Phone Sync: Bild ins Handy des Einreichers (Fotos-App)
        $phoneSync = null;
        if (!empty($antraege[$idx]['discord_id'])) {
            $phoneSync = syncPhotoToPhone($antraege[$idx]['discord_id'], $finalUrl, @filesize($finalPath) ?: 0);
        }

        $antraege[$idx]['status']        = 'approved';
        $antraege[$idx]['reviewed_by']   = $_SESSION['discord_username'] ?? $_SESSION['discord_user_id'];
        $antraege[$idx]['reviewed_at']   = time();
        $antraege[$idx]['final_url']     = $finalUrl;
        $antraege[$idx]['target_folder'] = $targetRel;
        $antraege[$idx]['phone_synced']  = $phoneSync;
        $antraege[$idx]['pending_file']  = null;
        saveAntraege($antraege);
        logDebug("antrag_approve: $antragId -> $targetRel/$finalName, phone=" . ($phoneSync ?: 'none'));

        $syncMsg = $phoneSync ? (' · ans Handy ' . $phoneSync) : '';
        $response = ['success' => true, 'message' => 'Genehmigt' . $syncMsg, 'url' => $finalUrl, 'phone' => $phoneSync, 'pendingCount' => getPendingAntragCount()];
        break;

    // ========== ANTRAG ABLEHNEN (Recht: antraege) ==========
    case 'antrag_reject':
    case 'reject_request':
        if (!canManageAntraege()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }

        $antragId = $_POST['id'] ?? '';
        $reason   = trim($_POST['reason'] ?? '');

        $antraege = loadAntraege();
        $idx = null;
        foreach ($antraege as $i => $a) {
            if (($a['id'] ?? '') === $antragId) { $idx = $i; break; }
        }
        if ($idx === null) {
            $response = ['success' => false, 'message' => 'Antrag nicht gefunden'];
            break;
        }
        if ($antraege[$idx]['status'] !== 'pending') {
            $response = ['success' => false, 'message' => 'Antrag bereits bearbeitet'];
            break;
        }

        // Pending-Datei BEHALTEN (fuer evtl. Wieder-Oeffnen), nur Status setzen
        $antraege[$idx]['status']        = 'rejected';
        $antraege[$idx]['reviewed_by']   = $_SESSION['discord_username'] ?? ($_SESSION['discord_user_id'] ?? 'Team');
        $antraege[$idx]['reviewed_at']   = time();
        $antraege[$idx]['reject_reason'] = mb_substr($reason, 0, 500);
        saveAntraege($antraege);
        logDebug("antrag_reject: $antragId");

        $response = ['success' => true, 'message' => 'Abgelehnt', 'pendingCount' => getPendingAntragCount()];
        break;

    // ========== ANTRAG WIEDER OEFFNEN (Recht: antraege) ==========
    case 'antrag_reopen':
    case 'reopen_request':
        if (!canManageAntraege()) {
            $response = ['success' => false, 'message' => 'Keine Berechtigung'];
            break;
        }
        $antragId = $_POST['id'] ?? '';
        $antraege = loadAntraege();
        $idx = null;
        foreach ($antraege as $i => $a) {
            if (($a['id'] ?? '') === $antragId) { $idx = $i; break; }
        }
        if ($idx === null) {
            $response = ['success' => false, 'message' => 'Antrag nicht gefunden'];
            break;
        }
        $wasStatus = $antraege[$idx]['status'];
        // Falls genehmigt war: finale Datei zurueck in den Eingereicht-Ordner holen
        if ($wasStatus === 'approved' && !empty($antraege[$idx]['final_url'])) {
            $targetRel = $antraege[$idx]['target_folder'] ?? getAntragTargetFolders()[0];
            $finalName = basename(parse_url($antraege[$idx]['final_url'], PHP_URL_PATH));
            $finalPath = antragFolderDir($targetRel) . '/' . $finalName;
            $pendingDir = getAntragPendingDir();
            if (!is_dir($pendingDir)) @mkdir($pendingDir, 0775, true);
            $newPending = bin2hex(random_bytes(16)) . '.' . ($antraege[$idx]['ext'] ?? 'png');
            if (is_file($finalPath)) {
                if (@rename($finalPath, $pendingDir . '/' . $newPending) || (@copy($finalPath, $pendingDir . '/' . $newPending) && @unlink($finalPath))) {
                    $antraege[$idx]['pending_file'] = $newPending;
                }
            }
            $antraege[$idx]['final_url'] = null;
            $antraege[$idx]['target_folder'] = null;
        }
        $antraege[$idx]['status']        = 'pending';
        $antraege[$idx]['reviewed_by']   = null;
        $antraege[$idx]['reviewed_at']   = null;
        $antraege[$idx]['reject_reason'] = null;
        saveAntraege($antraege);
        logDebug("antrag_reopen: $antragId");

        $response = ['success' => true, 'message' => 'Wieder offen', 'pendingCount' => getPendingAntragCount()];
        break;

    // ========== WHITELIST: USER HINZUFUEGEN (Recht: settings) ==========
    case 'add_user':
        if (!canManageUsers()) { $response = ['success' => false, 'message' => 'Keine Berechtigung']; break; }
        $userId = preg_replace('/[^0-9]/', '', $_POST['user_id'] ?? '');
        if (strlen($userId) < 17) { $response = ['success' => false, 'message' => 'Ungültige Discord-ID']; break; }
        $s = loadSettings();
        if (!isset($s['users'][$userId]) || empty($s['users'][$userId])) {
            $s['users'][$userId] = ['user'];
        }
        $response = saveSettings($s) ? ['success' => true, 'message' => 'Hinzugefügt'] : ['success' => false, 'message' => 'Speichern fehlgeschlagen'];
        break;

    // ========== WHITELIST: ADMIN AN/AUS (Recht: settings) ==========
    case 'toggle_admin':
        if (!canManageUsers()) { $response = ['success' => false, 'message' => 'Keine Berechtigung']; break; }
        $userId = preg_replace('/[^0-9]/', '', $_POST['user_id'] ?? '');
        $me = $_SESSION['discord_user_id'] ?? '';
        if (strlen($userId) < 17) { $response = ['success' => false, 'message' => 'Ungültige Discord-ID']; break; }
        if ($userId === $me) { $response = ['success' => false, 'message' => 'Eigenen Admin-Status nicht änderbar']; break; }
        $s = loadSettings();
        $roles = $s['users'][$userId] ?? [];
        if (in_array('admin', $roles, true)) {
            $s['users'][$userId] = ['user'];
            $msg = 'Admin entzogen';
        } else {
            $s['users'][$userId] = ['admin'];
            $msg = 'Zum Admin gemacht';
        }
        $response = saveSettings($s) ? ['success' => true, 'message' => $msg] : ['success' => false, 'message' => 'Speichern fehlgeschlagen'];
        break;

    // ========== WHITELIST: USER ENTFERNEN (Recht: settings) ==========
    case 'remove_user':
        if (!canManageUsers()) { $response = ['success' => false, 'message' => 'Keine Berechtigung']; break; }
        $userId = preg_replace('/[^0-9]/', '', $_POST['user_id'] ?? '');
        $me = $_SESSION['discord_user_id'] ?? '';
        if ($userId === $me) { $response = ['success' => false, 'message' => 'Du kannst dich nicht selbst entfernen']; break; }
        $s = loadSettings();
        unset($s['users'][$userId]);
        $response = saveSettings($s) ? ['success' => true, 'message' => 'Entfernt'] : ['success' => false, 'message' => 'Speichern fehlgeschlagen'];
        break;

    default:
        $response = ['success' => false, 'message' => 'Unbekannte Aktion: ' . $action];
}

// Version immer anhängen (Deploy-/OpCache-Check im Frontend)
if (is_array($response)) {
    $response['api_version'] = API_VERSION;
}

// Debug-Infos anhängen (nur bei GALLERY_DEBUG)
if (defined('GALLERY_DEBUG') && GALLERY_DEBUG && !empty($debugLog)) {
    $response['_debug'] = $debugLog;
}

echo json_encode($response);
?>
