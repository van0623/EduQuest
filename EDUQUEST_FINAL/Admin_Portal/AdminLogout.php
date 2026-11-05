<?php
// 1. Initialize the session
session_start();

// 2. Unset all individual session variables
$_SESSION = array();

// 3. Delete the session cookie if it exists (ensures complete cookie destruction)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Destroy the session data on the server
session_destroy();

// 5. Redirect securely to your login page and stop script execution
header("Location: Admin_login.php");
exit();
?>