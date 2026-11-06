<?php

mysqli_report(MYSQLI_REPORT_OFF);

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'eduquest_db';

$port = (int) (getenv('DB_PORT') ?: 3306);

$sslCa = getenv('DB_SSL_CA') ?: '';

/*
|--------------------------------------------------------------------------
| Initialize Database Connection
|--------------------------------------------------------------------------
*/

$conn = mysqli_init();

if (!$conn) {
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| TiDB Cloud SSL Connection
|--------------------------------------------------------------------------
*/

if ($host !== 'localhost' && $host !== '127.0.0.1') {

    if (empty($sslCa) || !file_exists($sslCa)) {
        error_log("Database SSL certificate is missing.");
        die("Database connection failed.");
    }

    $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

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

} else {

    /*
    |--------------------------------------------------------------------------
    | Local XAMPP Connection
    |--------------------------------------------------------------------------
    */

    $connected = $conn->real_connect(
        $host,
        $username,
        $password,
        $database,
        $port
    );
}

/*
|--------------------------------------------------------------------------
| Check Connection
|--------------------------------------------------------------------------
*/

if (!$connected) {
    error_log("Database connection error: " . $conn->connect_error);
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| Set Character Encoding
|--------------------------------------------------------------------------
*/

$conn->set_charset("utf8mb4");

?>