<?php
ob_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/session_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_save_handler(new DbSessionHandler($conn), true);
    session_start();
}