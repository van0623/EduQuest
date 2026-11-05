
<?php
session_start();

require_once "Admin_Portal/db_connect.php";

$errorMessage = "";

$fullName = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $fullName === "" ||
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $errorMessage = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $errorMessage = "Password must be at least 8 characters long.";

    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";

    } else {

        // Check whether the username or email already exists
        $check = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $errorMessage = "Username or email already exists.";

        } else {

            // Hash the password before saving it
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "student";
            $accountStatus = "active";

            // Insert the new student into the database
            $stmt = $conn->prepare(
                "INSERT INTO users
                (
                    full_name,
                    username,
                    email,
                    password_hash,
                    role,
                    account_status
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $fullName,
                $username,
                $email,
                $passwordHash,
                $role,
                $accountStatus
            );

            if ($stmt->execute()) {

                // Get the newly created user's ID
                $newUserId = $conn->insert_id;

                // Create a new session for the student
                session_regenerate_id(true);

                $_SESSION["user_id"] = $newUserId;
                $_SESSION["username"] = $username;
                $_SESSION["full_name"] = $fullName;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = $role;

                $stmt->close();
                $check->close();
                $conn->close();

                // Redirect after successful registration
                header("Location: DashboardPage.php");
                exit();

            } else {

                $errorMessage = "Unable to create your account. Please try again.";

                $stmt->close();
            }
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Create an Account</title>

    <link rel="stylesheet" href="css/SignupPage_styles.css">
</head>

<body>

<div class="signup-page">
    <div class="signup-container">

        <h1>Create Account</h1>
        <p class="subtitle">Create your account to get started.</p>

        <?php if ($errorMessage !== ""): ?>
            <div class="error-message" role="alert">
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars(
                        $fullName,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars(
                        $username,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="signup-btn">
                Sign Up
            </button>

        </form>

        <p class="login-text">
            Already have an account?
            <a href="login.php">Log In</a>
        </p>

    </div>
</div>

</body>
</html>
