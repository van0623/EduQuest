
<?php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/db_connect.php';

/* Check database connection */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

/* Set database charset */
$conn->set_charset("utf8mb4");

/* Start normal PHP session */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
