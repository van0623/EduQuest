
// ob_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

// require_once __DIR__ . '/db_connect.php';
// require_once __DIR__ . '/session_handler.php';

// if (session_status() === PHP_SESSION_NONE) {
//     session_set_save_handler(new DbSessionHandler($conn), true);
//     session_start();
// }
<?php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "STEP 1: Init started.<br>";

require_once __DIR__ . '/db_connect.php';

echo "STEP 2: Database file loaded.<br>";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("ERROR: Database connection is not initialized.");
}

echo "STEP 3: Database connection available.<br>";

require_once __DIR__ . '/session_handler.php';

echo "STEP 4: Session handler loaded.<br>";

if (!class_exists('DbSessionHandler')) {
    die("ERROR: DbSessionHandler class not found.");
}

if (session_status() === PHP_SESSION_NONE) {

    session_set_save_handler(new DbSessionHandler($conn), true);

    echo "STEP 5: Session handler registered.<br>";

    session_start();

    echo "STEP 6: Session started successfully.<br>";
}

echo "INIT COMPLETED SUCCESSFULLY.<br>";