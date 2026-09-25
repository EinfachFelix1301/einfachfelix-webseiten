<?php
/**
 * Hauptseite - Transcript Übersicht
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$isLoggedIn = DiscordAuth::isLoggedIn();
$isAdmin = $isLoggedIn && DiscordAuth::isAdmin();
$username = DiscordAuth::getUsername();
$avatar = DiscordAuth::getAvatar();
$userId = DiscordAuth::getUserId();

// Transcripts laden
$transcripts = [];
if ($isLoggedIn) {
    try {
        $db = Database::getInstance();
        if ($isAdmin) {
            $transcripts = $db->getAllTranscripts(50);
        } else {
            $transcripts = $db->getTranscriptsForUser($userId);
        }
    } catch (Exception $e) {
        // Ignorieren
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="icon" type="image/png" href="https://bloodline.cc/img/bloodline/bl_transparent.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>BLOOD<span>LINE</span> TRANSCRIPTS</h1>
            <div class="user-info">
                <?php if ($isLoggedIn): ?>
                    <img src="https://cdn.discordapp.com/avatars/<?= $userId ?>/<?= $avatar ?>.png" alt="Avatar" class="avatar">
                    <span class="user-name"><?= htmlspecialchars($username) ?></span>
                    <?php if ($isAdmin): ?>
                        <span class="badge admin">Admin</span>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Anmelden</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <?php if (!$isLoggedIn): ?>
                <div class="login-prompt">
                    <h2>TICKET <span>TRANSCRIPTS</span></h2>
                    <p>Melde dich mit Discord an, um deine Ticket-Transcripts einzusehen.</p>
                    <a href="login.php" class="btn btn-primary btn-large">
                        <svg width="20" height="20" viewBox="0 0 71 55" fill="currentColor">
                            <path d="M60.1 4.9A58.5 58.5 0 0 0 45.4.2a.2.2 0 0 0-.2.1 40.7 40.7 0 0 0-1.8 3.7 54 54 0 0 0-16.2 0A37.3 37.3 0 0 0 25.4.3a.2.2 0 0 0-.2-.1A58.4 58.4 0 0 0 10.5 5 59.5 59.5 0 0 0 .4 45a.3.3 0 0 0 .1.2A58.9 58.9 0 0 0 18.3 55a.2.2 0 0 0 .3-.1 42.2 42.2 0 0 0 3.6-5.9.2.2 0 0 0-.1-.3 38.8 38.8 0 0 1-5.5-2.6.2.2 0 0 1 0-.4l1.1-.9a.2.2 0 0 1 .2 0 42 42 0 0 0 35.7 0h.2l1.1.9a.2.2 0 0 1 0 .3 36.4 36.4 0 0 1-5.5 2.7.2.2 0 0 0-.1.3 47.3 47.3 0 0 0 3.6 5.9.2.2 0 0 0 .3.1A58.7 58.7 0 0 0 71 45.2a.2.2 0 0 0 .1-.2c1.2-12.4-.9-23.2-10-44a.2.2 0 0 0-.1-.1zM23.7 37c-3.8 0-6.9-3.5-6.9-7.7s3-7.7 6.9-7.7c3.9 0 7 3.5 6.9 7.7 0 4.3-3 7.7-6.9 7.7zm25.5 0c-3.8 0-6.9-3.5-6.9-7.7s3-7.7 6.9-7.7c3.9 0 7 3.5 6.9 7.7 0 4.3-3 7.7-6.9 7.7z"/>
                        </svg>
                        MIT DISCORD ANMELDEN
                    </a>
                </div>
            <?php else: ?>
                <div class="search-bar">
                    <input type="text" id="search" placeholder="Suche nach Ticket-Nummer oder User-ID...">
                    <button onclick="searchTranscripts()">Suchen</button>
                </div>

                <div class="transcripts-list" id="transcripts">
                    <?php if (empty($transcripts)): ?>
                        <div class="empty-state">
                            <p>Keine Transcripts gefunden.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <?php if ($isAdmin): ?><th>User</th><?php endif; ?>
                                    <th>Kategorie</th>
                                    <th>Erstellt</th>
                                    <th>Geschlossen</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transcripts as $t): ?>
                                    <tr>
                                        <td><code>#<?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                        <?php if ($isAdmin): ?>
                                            <td><?= htmlspecialchars($t['user_name'] ?? $t['user_id']) ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="category <?= htmlspecialchars($t['category']) ?>">
                                                <?= htmlspecialchars(ucfirst($t['category'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($t['closed_at'])) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-small">Ansehen</a>
                                            <a href="download.php?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-small btn-secondary">Download</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> <a href="https://bloodline.cc">Bloodline</a></p>
        </footer>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>
