<?php
// Start session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    // If not an admin, kick them out to the Admin Login page
    header("Location: Admin_login.php");
    exit();
}
?>