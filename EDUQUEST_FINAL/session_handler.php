<?php

class EduQuestSessionHandler implements SessionHandlerInterface
{
    private mysqli $conn;

    public function __construct(mysqli $connection)
    {
        $this->conn = $connection;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->conn->prepare(
            "SELECT session_data FROM user_sessions WHERE session_id = ? LIMIT 1"
        );
        if (!$stmt) {
            return "";
        }
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->bind_result($data);
        $result = $stmt->fetch() ? (string) $data : "";
        $stmt->close();
        return $result;
    }

    public function write(string $id, string $data): bool
    {
        $now = time();
        $stmt = $this->conn->prepare(
            "INSERT INTO user_sessions (session_id, session_data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                session_data = VALUES(session_data),
                last_activity = VALUES(last_activity)"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssi", $id, $data, $now);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = time() - $max_lifetime;
        $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE last_activity < ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $cutoff);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        return $deleted;
    }
}