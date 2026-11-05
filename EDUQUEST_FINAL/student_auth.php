
<?php
// Start session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow both students and admins
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    !in_array($_SESSION["role"], ["student", "admin"], true)
) {
    header("Location: login.php");
    exit();
}
?>
