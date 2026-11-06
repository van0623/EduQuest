
<?php

ob_start();

/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once __DIR__ . '/db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log("EduQuest: Database connection is not available.");
    die("Database connection is not available.");
}

if (!$conn->set_charset("utf8mb4")) {
    error_log("EduQuest: Unable to set database character encoding.");
    die("Unable to configure database connection.");
}

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

    /* OPEN SESSION */

    public function open(string $path, string $name): bool
    {
        return true;
    }

    /* CLOSE SESSION */

    public function close(): bool
    {
        return true;
    }

    /* READ SESSION */

    public function read(string $id): string|false
    {
        $stmt = $this->conn->prepare(
            "SELECT session_data
             FROM user_sessions
             WHERE session_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            error_log(
                "EduQuest session read prepare failed: " .
                $this->conn->error
            );

            return false;
        }

        $stmt->bind_param("s", $id);

        if (!$stmt->execute()) {
            error_log(
                "EduQuest session read failed: " .
                $stmt->error
            );

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

    /* WRITE SESSION */

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
            error_log(
                "EduQuest session write prepare failed: " .
                $this->conn->error
            );

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
            error_log(
                "EduQuest session write failed: " .
                $stmt->error
            );
        }

        $stmt->close();

        return $success;
    }

    /* DESTROY SESSION */

    public function destroy(string $id): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM user_sessions
             WHERE session_id = ?"
        );

        if (!$stmt) {
            error_log(
                "EduQuest session destroy prepare failed: " .
                $this->conn->error
            );

            return false;
        }

        $stmt->bind_param("s", $id);

        $success = $stmt->execute();

        if (!$success) {
            error_log(
                "EduQuest session destroy failed: " .
                $stmt->error
            );
        }

        $stmt->close();

        return $success;
    }

    /* CLEAN EXPIRED SESSIONS */

    public function gc(int $max_lifetime): int|false
    {
        $expiredBefore = time() - $max_lifetime;

        $stmt = $this->conn->prepare(
            "DELETE FROM user_sessions
             WHERE last_activity < ?"
        );

        if (!$stmt) {
            error_log(
                "EduQuest session cleanup prepare failed: " .
                $this->conn->error
            );

            return false;
        }

        $stmt->bind_param("i", $expiredBefore);

        $success = $stmt->execute();

        $deleted = $success
            ? $stmt->affected_rows
            : false;

        if (!$success) {
            error_log(
                "EduQuest session cleanup failed: " .
                $stmt->error
            );
        }

        $stmt->close();

        return $deleted;
    }
}

/* =========================================================
   START DATABASE SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    /* DETECT HTTPS */

    $isHttps = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (
            isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
            strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
        )
    );

    /* SESSION CONFIGURATION */

    session_name("EDUQUESTSESSID");

    if (!session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ])) {
        error_log("EduQuest: Unable to configure session cookies.");
        die("Unable to configure session cookies.");
    }

    /* CREATE SESSION HANDLER */

    $handler = new EduQuestSessionHandler($conn);

    /* REGISTER DATABASE SESSION HANDLER */

    if (!session_set_save_handler($handler, true)) {

        error_log(
            "EduQuest: Unable to register database session handler. " .
            "Session status: " . session_status() .
            " | Headers sent: " . (headers_sent() ? "YES" : "NO")
        );

        die("Unable to configure database session handler.");
    }


/* =========================================================
   START DATABASE SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    // If output already started, find out where
    if (headers_sent($outFile, $outLine)) {
        error_log("EduQuest: output already started in $outFile on line $outLine");
        die("Output started in $outFile on line $outLine");
    }

    $isHttps = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (
            isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
            strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
        )
    );

    session_name("EDUQUESTSESSID");

    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "secure"   => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_set_save_handler(new EduQuestSessionHandler($conn), true);

    if (!session_start()) {
        error_log("EduQuest: database session could not be started.");
        die("Unable to start database session.");
    }
}
}