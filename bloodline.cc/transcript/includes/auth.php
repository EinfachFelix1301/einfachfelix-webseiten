<?php
/**
 * Discord OAuth2 Authentifizierung
 */

require_once __DIR__ . '/config.php';

class DiscordAuth {
    private const API_URL = 'https://discord.com/api/v10';
    private const OAUTH_URL = 'https://discord.com/oauth2/authorize';

    /**
     * Generiert die OAuth2-URL
     */
    public static function getLoginUrl(): string {
        $params = http_build_query([
            'client_id' => DISCORD_CLIENT_ID,
            'redirect_uri' => DISCORD_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'identify',
            'state' => self::generateState()
        ]);

        return self::OAUTH_URL . '?' . $params;
    }

    /**
     * Generiert einen CSRF-State
     */
    private static function generateState(): string {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    /**
     * Verifiziert den State
     */
    public static function verifyState(string $state): bool {
        $valid = isset($_SESSION['oauth_state']) && hash_equals($_SESSION['oauth_state'], $state);
        unset($_SESSION['oauth_state']);
        return $valid;
    }

    /**
     * Tauscht den Code gegen Token
     */
    public static function getToken(string $code): ?array {
        $data = [
            'client_id' => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => DISCORD_REDIRECT_URI
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL . '/oauth2/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Discord token error: " . $response);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Holt User-Daten von Discord
     */
    public static function getUser(string $accessToken): ?array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL . '/users/@me',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Discord user error: " . $response);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Prüft ob User eingeloggt ist
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Holt die User-ID aus der Session
     */
    public static function getUserId(): ?string {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Holt den Username aus der Session
     */
    public static function getUsername(): ?string {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Holt den Avatar aus der Session
     */
    public static function getAvatar(): ?string {
        return $_SESSION['avatar'] ?? null;
    }

    /**
     * Prüft ob User Admin ist
     */
    public static function isAdmin(): bool {
        $userId = self::getUserId();
        if (!$userId) return false;

        // Config-basierte Admins (strict comparison mit Strings)
        if (in_array($userId, ADMIN_IDS, true)) {
            return true;
        }

        // DB-basierte Admins
        try {
            require_once __DIR__ . '/db.php';
            return Database::getInstance()->isAdmin($userId);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Loggt User ein
     */
    public static function login(array $user): void {
        $_SESSION['user_id'] = (string) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['discriminator'] = $user['discriminator'] ?? '0';
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Loggt User aus
     */
    public static function logout(): void {
        session_destroy();
    }
}
