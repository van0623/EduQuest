
<?php
session_start();

require_once "db_connect.php";

$error = "";

// Redirect if an admin is already logged in.
if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "admin"
) {
    header("Location: AdminDashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {

        $error = "Please enter your username/email and password.";

    } else {

        // Find the account using its username or email.
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, username, email,
                    password_hash, role, account_status
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (
                $user["role"] === "admin" &&
                $user["account_status"] === "active" &&
                password_verify($password, $user["password_hash"])
            ) {

                // Create a new session ID after successful login.
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // Update the last login time.
                $update = $conn->prepare(
                    "UPDATE users
                     SET last_login = NOW()
                     WHERE user_id = ?"
                );

                $update->bind_param("i", $user["user_id"]);
                $update->execute();
                $update->close();

                $stmt->close();
                $conn->close();

                header("Location: AdminDashboard.php");
                exit();

            } else {

                $error = "Invalid login details or account is inactive.";
            }

        } else {

            $error = "Invalid login details or account is inactive.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | EduQuest</title>
    <link rel="stylesheet" href="Admin_styles.css">
</head>

<body>

    <main class="login-container">

        <div class="logo">
            EDU<span>QUEST</span>
            <p class="subtitle">ADMIN PORTAL</p>
        </div>

        <h1>Admin Login</h1>

        <p class="description">
            Sign in using your administrator account to manage EduQuest.
        </p>

        <?php if ($error !== ""): ?>
            <div class="error-message" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label for="login">Username or Email</label>

            <input
                type="text"
                id="login"
                name="login"
                placeholder="Enter username or email"
                value="<?= htmlspecialchars($_POST["login"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>

            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    id="togglePassword"
                    aria-label="Show password"
                >
                    Show
                </button>
            </div>

            <button type="submit" class="login-button">
                Login to Admin Portal
            </button><br>

            <p class="signup">
              
                <a href="../login.php">  Go to Student Side</a>
            </p>

            <!-- SIGNUP LINK -->
            <p class="signup">
                Don't have an account?
                <a href="../SignupPage.php">Create an Account</a>
            </p>

        </form>

        <p class="footer">
            &copy; 2026 EduQuest. Admin access only.
        </p>

    </main>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";
            togglePassword.textContent = isPassword ? "Hide" : "Show";

            togglePassword.setAttribute(
                "aria-label",
                isPassword ? "Hide password" : "Show password"
            );
        });
    </script>

</body>
</html>
