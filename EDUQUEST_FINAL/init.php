
<?php

ob_start();

/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once __DIR__ . '/db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$conn->set_charset("utf8mb4");

/* =========================================================
   DATABASE SESSION HANDLER
========================================================= */

class EduQuestSessionHandler implements SessionHandlerInterface
{
    private $conn;

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
            "SELECT session_data
             FROM user_sessions
             WHERE session_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            error_log("Session read prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $id);

        if (!$stmt->execute()) {
            error_log("Session read failed: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $stmt->bind_result($sessionData);

        $data = "";

        if ($stmt->fetch()) {
            $data = (string) $sessionData;
        }

        $stmt->close();

        return $data;
    }

    public function write(string $id, string $data): bool
    {
        $lastActivity = time();

        $stmt = $this->conn->prepare(
            "INSERT INTO user_sessions
                (session_id, session_data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                session_data = VALUES(session_data),
                last_activity = VALUES(last_activity)"
        );

        if (!$stmt) {
            error_log("Session write prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param(
            "ssi",
            $id,
            $data,
            $lastActivity
        );

        $success = $stmt->execute();

        if (!$success) {
            error_log("Session write failed: " . $stmt->error);
        }

        $stmt->close();

        return $success;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM user_sessions WHERE session_id = ?"
        );

        if (!$stmt) {
            error_log("Session destroy prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $id);

        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function gc(int $max_lifetime): int|false
    {
        $expiredBefore = time() - $max_lifetime;

        $stmt = $this->conn->prepare(
            "DELETE FROM user_sessions WHERE last_activity < ?"
        );

        if (!$stmt) {
            error_log("Session cleanup prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $expiredBefore);

        $success = $stmt->execute();
        $deleted = $success ? $stmt->affected_rows : false;

        $stmt->close();

        return $deleted;
    }
}

/* =========================================================
   START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (
            isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
            $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https"
        )
    );

    session_name("EDUQUESTSESSID");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    $handler = new EduQuestSessionHandler($conn);

    // Register the database session handler
    $registered = session_set_save_handler($handler, true);

    if (!$registered) {

        $lastError = error_get_last();

        error_log(
            "EDUQUEST SESSION REGISTRATION FAILED: " .
            "PHP VERSION: " . PHP_VERSION .
            " | SESSION STATUS: " . session_status() .
            " | SESSION MODULE: " . session_module_name() .
            " | HEADERS SENT: " . (headers_sent() ? "YES" : "NO") .
            " | LAST ERROR: " . ($lastError["message"] ?? "No PHP error recorded")
        );

        die("Session handler registration failed. Check Vercel logs.");
    }

    // Start the session
    if (!session_start()) {
        error_log("EDUQUEST SESSION START FAILED.");
        die("Unable to start database session.");
    }
}   