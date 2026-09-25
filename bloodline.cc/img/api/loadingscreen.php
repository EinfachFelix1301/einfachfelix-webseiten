<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

// QUELLE: bl_base/loadingscreen/sv_config.lua - bei aenderung dort hier nachziehen.
// Priority-konvention: HOEHER = wichtiger (kommt in liste zuerst).
$config = [
    'bot_token'         => getenv('DISCORD_BOT_TOKEN') ?: '',
    // Eigene IDs eintragen (Discord-Server, Changelog-Kanal, Team-Rollen)
    'guild_id'          => '000000000000000000',
    'changelog_channel' => '000000000000000000',
    'max_changelog'     => 50,

    // roleId => [displayName, group, priority] — eigene Rollen-IDs eintragen
    'team_roles' => [
        '000000000000000001' => ['Projektinhaber',           'Serververwaltung',    18],
        '000000000000000002' => ['Projektverwaltung',        'Serververwaltung',    17],
        '000000000000000003' => ['Teamleitung',              'Teamverwaltung',      16],
        '000000000000000004' => ['Stv. Teamleitung',         'Teamverwaltung',      15],
        '000000000000000005' => ['Community Management',     'Communityverwaltung', 14],
        '000000000000000006' => ['Head Fraktionsverwaltung', 'Fraktionsverwaltung', 13],
        '000000000000000007' => ['Fraktionsverwaltung',      'Fraktionsverwaltung', 12],
    ],
];

function discordRequest($endpoint, $token) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://discord.com/api/v10' . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bot ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200 ? json_decode($response, true) : null;
}

function fetchTeam($config) {
    $team = [];
    if (empty($config['team_roles'])) return $team;

    $members = discordRequest('/guilds/' . $config['guild_id'] . '/members?limit=1000', $config['bot_token']);
    if (!$members) return $team;

    foreach ($members as $member) {
        $bestRc = null;
        foreach ($member['roles'] as $roleId) {
            if (!isset($config['team_roles'][$roleId])) continue;
            $rc = $config['team_roles'][$roleId];
            // Hoehere priority = wichtiger
            if ($bestRc === null || $rc[2] > $bestRc[2]) {
                $bestRc = $rc;
            }
        }
        if ($bestRc === null) continue;

        $displayName = $member['nick'] ?? $member['user']['global_name'] ?? $member['user']['username'];

        if (!empty($member['avatar'])) {
            $avatar = sprintf('https://cdn.discordapp.com/guilds/%s/users/%s/avatars/%s.png?size=128',
                $config['guild_id'], $member['user']['id'], $member['avatar']);
        } elseif (!empty($member['user']['avatar'])) {
            $avatar = sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=128',
                $member['user']['id'], $member['user']['avatar']);
        } else {
            $avatar = 'https://bloodline.cc/img/bloodline/bl_transparent.png';
        }

        $team[] = ['name' => $displayName, 'role' => $bestRc[0], 'group' => $bestRc[1], 'priority' => $bestRc[2], 'avatar' => $avatar];
    }

    // Hoehere priority zuerst
    usort($team, fn($a, $b) => $b['priority'] - $a['priority']);
    return $team;
}

function fetchChangelog($config) {
    $changelog = [];
    $messages  = discordRequest('/channels/' . $config['changelog_channel'] . '/messages?limit=' . $config['max_changelog'], $config['bot_token']);
    if (!$messages) return $changelog;

    $tagPatterns = [
        '/^\[\+\]/'        => 'neu',
        '/^\[NEU\]/i'      => 'neu',
        '/^\[NEW\]/i'      => 'neu',
        '/^\[ADD\]/i'      => 'neu',
        '/^\[-\]/'         => 'remove',
        '/^\[ENTFERNT\]/i' => 'remove',
        '/^\[REMOVED\]/i'  => 'remove',
        '/^\[\/\]/'        => 'fix',
        '/^\[FIX\]/i'      => 'fix',
        '/^\[FIXED\]/i'    => 'fix',
        '/^\[UPDATE\]/i'   => 'update',
        '/^\[UPDATED\]/i'  => 'update',
    ];

    foreach ($messages as $msg) {
        if (empty($msg['content'])) continue;

        $timestamp = $msg['timestamp'];
        $date      = 'Unbekannt';
        if ($timestamp) {
            $dt     = new DateTime($timestamp);
            $months = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
            $date   = $dt->format('d') . '. ' . $months[$dt->format('n') - 1] . ' ' . $dt->format('Y');
        }

        preg_match('/v\d+\.\d+\.?\d*/i', $msg['content'], $vm);
        $version = $vm[0] ?? ('Update ' . $date);
        $changes = [];

        foreach (preg_split('/\r?\n/', $msg['content']) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (preg_match('/^(\/\/|#|--)/', $line)) continue;
            // Deko-Zeilen (nur Sonderzeichen, keine Buchstaben)
            if (preg_match('/^[^\p{L}\p{N}]+$/u', $line)) continue;
            // Legend-Zeile
            if (preg_match('/added.*removed.*updated/i', $line)) continue;
            // Versions-Header ohne Tag
            if (!preg_match('/^\[/', $line) && preg_match('/^v\d+\.\d+/i', $line)) continue;

            $tag  = null;
            $text = $line;
            foreach ($tagPatterns as $pattern => $tagName) {
                if (preg_match($pattern, $line)) {
                    $tag  = $tagName;
                    $text = preg_replace($pattern, '', $line);
                    break;
                }
            }
            if ($tag === null) continue;

            $text = preg_replace('/^[\-\*\•\s]+/', '', trim($text));
            $text = preg_replace('/<@[!&]?\d+>|@everyone|@here/', '', $text);
            $text = trim($text);
            if (!empty($text)) {
                $changes[] = ['tag' => $tag, 'text' => $text];
            }
        }

        if (!empty($changes)) {
            $changelog[] = ['version' => $version, 'date' => $date, 'changes' => $changes];
        }
    }

    return $changelog;
}

$cacheFile = __DIR__ . '/loadingscreen_cache.json';

// force/nocache verkuerzt den file-cache von 300s auf 60s (frischer Discord-pull).
// Nur server.lua nutzt das (alle 5min/resource-start). Auch mit force hoechstens
// ein Discord-pull pro 60s -> kein Rate-Limit-/Ban-Risiko fuer den Bot-Token.
$forceFresh = isset($_GET['force']) || isset($_GET['nocache']);
$cacheTtl   = $forceFresh ? 60 : 300;

if (file_exists($cacheFile) && filesize($cacheFile) > 0 && (time() - filemtime($cacheFile)) < $cacheTtl) {
    echo file_get_contents($cacheFile);
    exit;
}

$data = [
    'team'      => fetchTeam($config),
    'changelog' => fetchChangelog($config),
    'updated'   => date('c'),
];

if (empty($data['team'])) {
    $data['team'] = [
        ['name' => 'Seltonmt', 'role' => 'Projektinhaber',    'group' => 'Highteam', 'avatar' => 'https://bloodline.cc/img/bloodline/bl_transparent.png'],
        ['name' => 'Lennart',  'role' => 'Projektinhaber',    'group' => 'Highteam', 'avatar' => 'https://bloodline.cc/img/bloodline/bl_transparent.png'],
        ['name' => 'Felix',    'role' => 'Projektverwaltung', 'group' => 'Highteam', 'avatar' => 'https://bloodline.cc/img/bloodline/bl_transparent.png'],
    ];
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($json === false) {
    error_log('[Loadingscreen] json_encode failed: ' . json_last_error_msg());
    $json = json_encode(['team' => $data['team'], 'changelog' => [], 'updated' => date('c')], JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
}

if ($json !== false) {
    file_put_contents($cacheFile, $json);
    echo $json;
} else {
    http_response_code(500);
    echo '{"error":"json_encode failed","team":[],"changelog":[]}';
}
