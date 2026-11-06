<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME');
$port = (int) (getenv('DB_PORT') ?: 4000);
$sslCa = getenv('DB_SSL_CA');

if (
    !$host ||
    !$username ||
    !$password ||
    !$database ||
    !$sslCa
) {
    error_log("EduQuest DB Error: One or more environment variables are missing.");
    die("Database configuration is incomplete. Check Vercel environment variables.");
}

if (!file_exists($sslCa)) {
    error_log("EduQuest DB Error: SSL certificate not found at " . $sslCa);
    die("Database SSL certificate is missing.");
}

$conn = mysqli_init();

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);

$conn->ssl_set(
    null,
    null,
    $sslCa,
    null,
    null
);

$connected = $conn->real_connect(
    $host,
    $username,
    $password,
    $database,
    $port,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$connected) {
    error_log("EduQuest TiDB Connection Error: " . $conn->connect_error);
    die("Database connection failed. Please check the deployment logs.");
}

$conn->set_charset("utf8mb4");