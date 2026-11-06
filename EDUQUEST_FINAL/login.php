
<?php
session_start();

require_once __DIR__ . 'db_connect.php';

$error = "";
$success = "";
$view = "login";
$resetEmail = "";

// CSRF protection
if (empty($_SESSION["login_csrf"])) {
    $_SESSION["login_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["login_csrf"];

// Escape output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Determine which form to display
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["forgot"])) {
    $view = "forgot";
}

// Redirect already logged-in users when opening normal login
if (
    isset($_SESSION["user_id"]) &&
    $view === "login" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: Admin_Portal/AdminDashboard.php");
    } else {
        header("Location: DashboardPage.php");
    }

    exit();
}

// ==================================================
// HANDLE FORM SUBMISSIONS
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedCsrf = $_POST["csrf_token"] ?? "";
    $action = $_POST["action"] ?? "";

    if (!hash_equals($_SESSION["login_csrf"], $submittedCsrf)) {

        $error = "Invalid request. Please refresh the page and try again.";

        $view = in_array($action, ["forgot", "reset"], true)
            ? $action
            : "login";

        $resetEmail = trim($_POST["email"] ?? "");

    } else {

        // ==========================================
        // NORMAL LOGIN
        // ==========================================

        if ($action === "login") {

            $view = "login";

            $email = trim($_POST["email"] ?? "");
            $password = $_POST["password"] ?? "";

            if ($email === "" || $password === "") {

                $error = "Please enter your email and password.";

            } else {

                $stmt = $conn->prepare("
                    SELECT user_id, full_name, username, email,
                           password_hash, role, account_status
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");

                if ($stmt) {

                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    if ($result && $result->num_rows === 1) {

                        $user = $result->fetch_assoc();

                        if (
                            password_verify($password, $user["password_hash"]) &&
                            $user["account_status"] === "active"
                        ) {

                            session_regenerate_id(true);

                            $_SESSION["user_id"] = $user["user_id"];
                            $_SESSION["username"] = $user["username"];
                            $_SESSION["email"] = $user["email"];
                            $_SESSION["full_name"] = $user["full_name"];
                            $_SESSION["role"] = $user["role"];

                            if ($user["role"] === "admin") {
                                header("Location: Admin_Portal/AdminDashboard.php");
                            } else {
                                header("Location: DashboardPage.php");
                            }

                            exit();

                        } elseif ($user["account_status"] !== "active") {

                            $error = "Your account is not active. Please contact the administrator.";

                        } else {

                            $error = "Incorrect email or password.";
                        }

                    } else {

                        $error = "Incorrect email or password.";
                    }

                    $stmt->close();

                } else {

                    $error = "Unable to process your login. Please try again.";
                }
            }
        }

        // ==========================================
        // FORGOT PASSWORD - CHECK EMAIL
        // ==========================================

        elseif ($action === "forgot") {

            $view = "forgot";

            $email = trim($_POST["email"] ?? "");

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $error = "Please enter a valid email address.";

            } else {

                $stmt = $conn->prepare("
                    SELECT user_id, email
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");

                if ($stmt) {

                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    $result = $stmt->get_result();
                    $user = $result ? $result->fetch_assoc() : null;

                    $stmt->close();

                    if ($user) {

                        // Email exists. Display the new password form.
                        $view = "reset";
                        $resetEmail = $user["email"];

                    } else {

                        $error = "No account was found with that email address.";
                    }

                } else {

                    $error = "Unable to check your email address. Please try again.";
                }
            }
        }

        // ==========================================
        // RESET PASSWORD
        // ==========================================

        elseif ($action === "reset") {

            $view = "reset";

            $resetEmail = trim($_POST["email"] ?? "");

            $newPassword = $_POST["new_password"] ?? "";
            $confirmPassword = $_POST["confirm_password"] ?? "";

            if (!filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {

                $view = "forgot";
                $error = "Please enter a valid email address.";

            } elseif (strlen($newPassword) < 8) {

                $error = "Your password must contain at least 8 characters.";

            } elseif ($newPassword !== $confirmPassword) {

                $error = "Your passwords do not match.";

            } else {

                // Verify that the email exists.
                $checkStmt = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");

                if ($checkStmt) {

                    $checkStmt->bind_param("s", $resetEmail);
                    $checkStmt->execute();

                    $result = $checkStmt->get_result();
                    $user = $result ? $result->fetch_assoc() : null;

                    $checkStmt->close();

                    if ($user) {

                        $userId = (int) $user["user_id"];

                        // Hash the new password.
                        $newPasswordHash = password_hash(
                            $newPassword,
                            PASSWORD_DEFAULT
                        );

                        // Update the password in the database.
                        $updateStmt = $conn->prepare("
                            UPDATE users
                            SET password_hash = ?
                            WHERE user_id = ?
                        ");

                        if ($updateStmt) {

                            $updateStmt->bind_param(
                                "si",
                                $newPasswordHash,
                                $userId
                            );

                            if ($updateStmt->execute()) {

                                $success = "Your password has been changed successfully. You can now log in.";

                                $view = "login";
                                $resetEmail = "";

                                // Clear previous form values.
                                $_POST = [];

                            } else {

                                $error = "Unable to update your password. Please try again.";
                            }

                            $updateStmt->close();

                        } else {

                            $error = "Unable to process your password update. Please try again.";
                        }

                    } else {

                        $view = "forgot";
                        $error = "The account could not be found.";
                    }

                } else {

                    $error = "Unable to verify your account. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Login</title>

    <link rel="stylesheet" href="css/LoginPage_styles.css">

    <style>
        .forgot-password {
            display: flex;
            justify-content: flex-end;
            margin: 8px 0 18px;
        }

        .forgot-password a,
        .back-to-login a {
            color: #D94F16;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-password a:hover,
        .back-to-login a:hover {
            color: #A93A0E;
            text-decoration: underline;
        }

        .success-message {
            padding: 12px 14px;
            margin-bottom: 18px;
            background: #EFFAF1;
            border: 1px solid #C8E9CE;
            border-radius: 7px;
            color: #187A31;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-description {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .reset-form input,
        .forgot-form input {
            width: 100%;
        }

        .back-to-login {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
        }

        .form-error {
            padding: 12px 14px;
            margin-bottom: 18px;
            background: #FFF0ED;
            border: 1px solid #F3C5BA;
            border-radius: 7px;
            color: #A83219;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>

<body>

    <?php if ($error !== ""): ?>

        <div class="toast-error" id="loginError" role="alert">

            <div class="toast-icon">!</div>

            <div class="toast-content">
                <strong>
                    <?= $view === "login" ? "Login Failed" : "Request Failed" ?>
                </strong>

                <p><?= e($error) ?></p>
            </div>

            <button
                type="button"
                class="toast-close"
                onclick="closeLoginError()"
                aria-label="Close error message">
                &times;
            </button>

            <div class="toast-progress"></div>

        </div>

    <?php endif; ?>


    <div class="page">

        <!-- LEFT SIDE -->
        <div class="left-panel">

            <div class="image-grid">

                <div class="grid-box">
                    <img
                        src="images/office-administration.jpeg"
                        alt="Office administration and filing systems"
                    >
                </div>

                <div class="grid-box">
                    <img
                        src="images/business-communication.jpeg"
                        alt="Business communication and correspondence"
                    >
                </div>

                <div class="grid-box">
                    <img
                        src="images/records-management.jpeg"
                        alt="Office productivity and records management"
                    >
                </div>

            </div>

        </div>


        <!-- RIGHT SIDE -->
        <div class="right-panel">

            <div class="login-container">

                <?php if ($success !== ""): ?>

                    <div class="success-message">
                        <?= e($success) ?>
                    </div>

                <?php endif; ?>


                <!-- ================================= -->
                <!-- LOGIN FORM -->
                <!-- ================================= -->

                <?php if ($view === "login"): ?>

                    <h2>Sign in to <span>EduQuest</span></h2>

                    <p class="welcome">
                        Welcome to <strong>EduQuest</strong>, please input
                        your details below.
                    </p>

                    <form method="POST" action="">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="login"
                        >

                        <input
                            type="email"
                            name="email"
                            placeholder="Email Address"
                            value="<?= e($_POST["email"] ?? "") ?>"
                            autocomplete="email"
                            required
                        >

                        <div class="password-container">

                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('password', this)"
                                aria-label="Show password">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 640 640"
                                    aria-hidden="true">

                                    <path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/>

                                </svg>

                            </button>

                        </div>

                        <div class="forgot-password">
                            <a href="login.php?forgot=1">
                                Forgot Password?
                            </a>
                        </div>

                        <button type="submit" class="login-button">
                            Log in
                        </button>

                    </form>

                    <p class="signup">
                        Don't have an account?
                        <a href="SignupPage.php">Create an Account</a>
                    </p>


                <!-- ================================= -->
                <!-- FORGOT PASSWORD FORM -->
                <!-- ================================= -->

                <?php elseif ($view === "forgot"): ?>

                    <h2>Forgot <span>Password?</span></h2>

                    <p class="welcome form-description">
                        Enter the email address connected to your EduQuest
                        account to continue changing your password.
                    </p>

                    <form method="POST" action="" class="forgot-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="forgot"
                        >

                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            value="<?= e($_POST["email"] ?? "") ?>"
                            required
                        >

                        <button type="submit" class="login-button">
                            Continue
                        </button>

                    </form>

                    <p class="back-to-login">
                        Remember your password?
                        <a href="login.php">Back to Login</a>
                    </p>


                <!-- ================================= -->
                <!-- RESET PASSWORD FORM -->
                <!-- ================================= -->

                <?php elseif ($view === "reset"): ?>

                    <h2>Reset <span>Password</span></h2>

                    <p class="welcome form-description">
                        Create a new password for your EduQuest account.
                        Your password must contain at least 8 characters.
                    </p>

                    <form method="POST" action="" class="reset-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reset"
                        >

                        <input
                            type="hidden"
                            name="email"
                            value="<?= e($resetEmail) ?>"
                        >

                        <div class="password-container">

                            <input
                                type="password"
                                name="new_password"
                                id="new_password"
                                placeholder="New Password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('new_password', this)"
                                aria-label="Show password">
                                Show
                            </button>

                        </div>

                        <div class="password-container">

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                placeholder="Confirm New Password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('confirm_password', this)"
                                aria-label="Show password">
                                Show
                            </button>

                        </div>

                        <button type="submit" class="login-button">
                            Change Password
                        </button>

                    </form>

                    <p class="back-to-login">
                        <a href="login.php">Back to Login</a>
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- IMAGE POPUP -->
    <div class="image-lightbox" id="imageLightbox" aria-hidden="true">

        <button
            type="button"
            class="lightbox-close"
            id="lightboxClose"
            aria-label="Close enlarged image"
        >&times;</button>

        <img src="" alt="Enlarged image" id="lightboxImage">

    </div>


    <!-- JAVASCRIPT -->
    <script>

        // SHOW / HIDE PASSWORD
        function togglePassword(inputId, button) {

            const password = document.getElementById(inputId);

            if (!password) {
                return;
            }

            password.type =
                password.type === "password" ? "text" : "password";

            button.setAttribute(
                "aria-label",
                password.type === "password"
                    ? "Show password"
                    : "Hide password"
            );
        }


        // CLOSE ERROR MESSAGE
        function closeLoginError() {

            const toast = document.getElementById("loginError");

            if (toast) {

                toast.classList.add("toast-hiding");

                setTimeout(function () {
                    toast.remove();
                }, 250);
            }
        }


        document.addEventListener("DOMContentLoaded", function () {

            const toast = document.getElementById("loginError");

            if (toast) {

                setTimeout(function () {
                    closeLoginError();
                }, 5000);
            }

        });


        // IMAGE LIGHTBOX
        document.addEventListener("DOMContentLoaded", () => {

            const imageBoxes = document.querySelectorAll(".grid-box");
            const lightbox = document.getElementById("imageLightbox");
            const lightboxImage = document.getElementById("lightboxImage");
            const closeButton = document.getElementById("lightboxClose");

            if (!lightbox || !lightboxImage || !closeButton) {
                return;
            }

            let previouslyFocusedElement = null;


            function openLightbox(image) {

                previouslyFocusedElement = document.activeElement;

                lightboxImage.src = image.currentSrc || image.src;
                lightboxImage.alt = image.alt || "Enlarged image";

                lightbox.classList.add("active");
                lightbox.setAttribute("aria-hidden", "false");

                document.body.classList.add("lightbox-open");

                closeButton.focus();
            }


            function closeLightbox() {

                lightbox.classList.remove("active");
                lightbox.setAttribute("aria-hidden", "true");

                document.body.classList.remove("lightbox-open");

                lightboxImage.src = "";

                if (previouslyFocusedElement) {
                    previouslyFocusedElement.focus();
                }
            }


            imageBoxes.forEach((box) => {

                const image = box.querySelector("img");

                if (!image) {
                    return;
                }

                box.setAttribute("tabindex", "0");
                box.setAttribute("role", "button");
                box.setAttribute("aria-label", "Enlarge image");

                box.addEventListener("click", () => {
                    openLightbox(image);
                });

                box.addEventListener("keydown", (event) => {

                    if (event.key === "Enter" || event.key === " ") {

                        event.preventDefault();
                        openLightbox(image);
                    }

                });

            });


            closeButton.addEventListener("click", closeLightbox);


            lightbox.addEventListener("click", (event) => {

                if (event.target === lightbox) {
                    closeLightbox();
                }

            });


            document.addEventListener("keydown", (event) => {

                if (
                    event.key === "Escape" &&
                    lightbox.classList.contains("active")
                ) {
                    closeLightbox();
                }

            });

        });

    </script>

</body>
</html>
