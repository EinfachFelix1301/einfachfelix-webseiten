<?php
/**
 * Datenbank-Verbindung
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Datenbankverbindung fehlgeschlagen");
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    /**
     * Speichert ein Transcript
     */
    public function saveTranscript(array $data): bool {
        $sql = "INSERT INTO transcripts
                (id, ticket_number, user_id, user_name, category, html_content, created_at, closed_at, closed_by)
                VALUES
                (:id, :ticket_number, :user_id, :user_name, :category, :html_content, :created_at, :closed_at, :closed_by)";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $data['id'],
                ':ticket_number' => $data['ticket_number'],
                ':user_id' => $data['user_id'],
                ':user_name' => $data['user_name'],
                ':category' => $data['category'],
                ':html_content' => $data['html_content'],
                ':created_at' => $data['created_at'],
                ':closed_at' => $data['closed_at'],
                ':closed_by' => $data['closed_by']
            ]);
        } catch (PDOException $e) {
            error_log("Save transcript failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Holt ein Transcript nach ID
     */
    public function getTranscript(string $id): ?array {
        $sql = "SELECT * FROM transcripts WHERE id = :id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Get transcript failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Holt alle Transcripts für einen User
     */
    public function getTranscriptsForUser(string $userId): array {
        $sql = "SELECT id, ticket_number, category, created_at, closed_at
                FROM transcripts
                WHERE user_id = :user_id
                ORDER BY closed_at DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get user transcripts failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Holt alle Transcripts (Admin)
     */
    public function getAllTranscripts(int $limit = 100, int $offset = 0): array {
        $sql = "SELECT id, ticket_number, user_id, user_name, category, created_at, closed_at
                FROM transcripts
                ORDER BY closed_at DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all transcripts failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sucht Transcripts
     */
    public function searchTranscripts(string $query, ?string $userId = null): array {
        $sql = "SELECT id, ticket_number, user_id, user_name, category, created_at, closed_at
                FROM transcripts
                WHERE (ticket_number LIKE :query OR user_name LIKE :query OR id LIKE :query)";

        $params = [':query' => "%$query%"];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $sql .= " ORDER BY closed_at DESC LIMIT 50";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search transcripts failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Prüft ob ein User Admin ist
     */
    public function isAdmin(string $discordId): bool {
        $sql = "SELECT 1 FROM admins WHERE discord_id = :id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $discordId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            // Tabelle existiert möglicherweise nicht
            return in_array($discordId, ADMIN_IDS, true);
        }
    }
}
