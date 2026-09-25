-- Fehlgeschlagene Logins pro IP (Brute-Force-Bremse für /api/login)
CREATE TABLE IF NOT EXISTS login_attempts (
  ip TEXT NOT NULL,
  created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, created_at);
