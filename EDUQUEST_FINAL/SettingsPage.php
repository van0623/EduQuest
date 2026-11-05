
<?php
session_start();

require_once __DIR__ . '/Admin_Portal/db_connect.php';

$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";

/* =========================================================
   CHECK LOGIN - ALLOW STUDENTS AND ADMINS
========================================================= */

$user = null;

if (!empty($_SESSION['user_id'])) {

    $sessionUserId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT
            user_id,
            full_name,
            username,
            email,
            password_hash,
            profile_image,
            role
        FROM users
        WHERE user_id = ?
          AND role IN ('student', 'admin')
          AND account_status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param("i", $sessionUserId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

} else {

    $loginValue = $_SESSION['email']
        ?? $_SESSION['user_email']
        ?? $_SESSION['username']
        ?? null;

    if ($loginValue !== null) {

        $stmt = $conn->prepare("
            SELECT
                user_id,
                full_name,
                username,
                email,
                password_hash,
                profile_image,
                role
            FROM users
            WHERE (email = ? OR username = ?)
              AND role IN ('student', 'admin')
              AND account_status = 'active'
            LIMIT 1
        ");

        $stmt->bind_param("ss", $loginValue, $loginValue);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
        }
    }
}

if (!$user) {
    header("Location: Login.php");
    exit;
}

$userId = (int) $user['user_id'];

$displayName = !empty($user['username'])
    ? $user['username']
    : $user['full_name'];

$firstLetter = strtoupper(substr($displayName, 0, 1));

$userRole = $user['role'];


/* =========================================================
   PROFILE IMAGE PATH
========================================================= */

$profileImage = trim((string) ($user['profile_image'] ?? ''));

if (!empty($profileImage)) {

    $profileImage = str_replace("\\", "/", $profileImage);

    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, 'data:') !== 0
    ) {

        $profileImage = ltrim($profileImage, '/');

        if (strpos($profileImage, 'uploads/profile_images/') === 0) {
            $profileImage = 'Admin_Portal/' . $profileImage;
        }
    }
}


/* =========================================================
   HANDLE SETTINGS FORMS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* =====================================================
       UPDATE PROFILE INFORMATION AND PROFILE PICTURE
    ===================================================== */

    if ($action === 'update_profile') {

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($fullName === '' || $email === '') {

            $message = "Please fill in all profile fields.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } else {

            // CHECK IF ANOTHER USER ALREADY USES THIS EMAIL
            $stmt = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                  AND user_id != ?
                LIMIT 1
            ");

            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();

            $emailExists = $stmt->get_result()->num_rows > 0;

            $stmt->close();

            if ($emailExists) {

                $message = "This email address is already in use.";
                $messageType = "error";

            } else {

                // KEEP THE CURRENT PROFILE IMAGE
                $profileImage = $user['profile_image'] ?? null;

                $newImagePath = null;

                // CHECK IF A NEW IMAGE WAS UPLOADED
                if (
                    isset($_FILES['profile_image']) &&
                    $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {

                        $message = "There was a problem uploading your image.";
                        $messageType = "error";

                    } elseif (
                        $_FILES['profile_image']['size'] > 2 * 1024 * 1024
                    ) {

                        $message = "Profile image must not exceed 2 MB.";
                        $messageType = "error";

                    } else {

                        // CHECK ACTUAL IMAGE TYPE
                        $finfo = new finfo(FILEINFO_MIME_TYPE);

                        $mimeType = $finfo->file(
                            $_FILES['profile_image']['tmp_name']
                        );

                        $allowedTypes = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp'
                        ];

                        if (!isset($allowedTypes[$mimeType])) {

                            $message = "Only JPG, PNG, and WEBP images are allowed.";
                            $messageType = "error";

                        } elseif (
                            getimagesize($_FILES['profile_image']['tmp_name']) === false
                        ) {

                            $message = "The uploaded file is not a valid image.";
                            $messageType = "error";

                        } else {

                            // CREATE UPLOAD FOLDER
                            $uploadDirectory = __DIR__ . '/uploads/profile_pics/';

                            if (
                                !is_dir($uploadDirectory) &&
                                !mkdir($uploadDirectory, 0755, true)
                            ) {

                                $message = "Unable to create the profile image folder.";
                                $messageType = "error";

                            } else {

                                // GENERATE UNIQUE FILE NAME
                                $extension = $allowedTypes[$mimeType];

                                $fileName = 'profile_' . $userId . '_' .
                                    bin2hex(random_bytes(8)) . '.' . $extension;

                                $destination = $uploadDirectory . $fileName;

                                if (
                                    move_uploaded_file(
                                        $_FILES['profile_image']['tmp_name'],
                                        $destination
                                    )
                                ) {

                                    $newImagePath = 'uploads/profile_pics/' . $fileName;

                                    $profileImage = $newImagePath;

                                } else {

                                    $message = "Unable to save the uploaded image.";
                                    $messageType = "error";
                                }
                            }
                        }
                    }
                }

                // SAVE PROFILE IF THERE WAS NO UPLOAD ERROR
                if ($messageType !== "error") {

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            email = ?,
                            profile_image = ?
                        WHERE user_id = ?
                    ");

                    $stmt->bind_param(
                        "sssi",
                        $fullName,
                        $email,
                        $profileImage,
                        $userId
                    );

                    if ($stmt->execute()) {

                        $message = "Profile information updated successfully.";
                        $messageType = "success";

                        // UPDATE SESSION INFORMATION
                        $_SESSION['email'] = $email;
                        $_SESSION['full_name'] = $fullName;

                        // DELETE OLD IMAGE AFTER SUCCESSFUL UPDATE
                        if (
                            !empty($newImagePath) &&
                            !empty($user['profile_image'])
                        ) {

                            $oldImage = __DIR__ . '/' . $user['profile_image'];

                            $oldImageDirectory = realpath(
                                __DIR__ . '/uploads/profile_pics'
                            );

                            $oldImageRealPath = realpath($oldImage);

                            if (
                                $oldImageRealPath !== false &&
                                $oldImageDirectory !== false &&
                                strpos(
                                    $oldImageRealPath,
                                    $oldImageDirectory . DIRECTORY_SEPARATOR
                                ) === 0 &&
                                is_file($oldImageRealPath)
                            ) {
                                unlink($oldImageRealPath);
                            }
                        }

                    } else {

                        $message = "Unable to update your profile.";
                        $messageType = "error";

                        // REMOVE NEW IMAGE IF DATABASE UPDATE FAILED
                        if (!empty($newImagePath)) {

                            $newImageFile = __DIR__ . '/' . $newImagePath;

                            if (is_file($newImageFile)) {
                                unlink($newImageFile);
                            }
                        }
                    }

                    $stmt->close();
                }
            }
        }

    /* =====================================================
       CHANGE PASSWORD
    ===================================================== */

    } elseif ($action === 'change_password') {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {

            $message = "Please fill in all password fields.";
            $messageType = "error";

        } elseif (!password_verify($currentPassword, $user['password_hash'])) {

            $message = "Your current password is incorrect.";
            $messageType = "error";

        } elseif (strlen($newPassword) < 8) {

            $message = "Your new password must be at least 8 characters.";
            $messageType = "error";

        } elseif ($newPassword !== $confirmPassword) {

            $message = "The new passwords do not match.";
            $messageType = "error";

        } else {

            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE user_id = ?
            ");

            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {

                $message = "Password changed successfully.";
                $messageType = "success";

                $user['password_hash'] = $hashedPassword;

            } else {

                $message = "Unable to change your password.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }

    // REFRESH USER INFORMATION AFTER FORM SUBMISSION
    $stmt = $conn->prepare("
        SELECT
            user_id,
            full_name,
            username,
            email,
            password_hash,
            profile_image,
            role,
            account_status
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$user) {
        session_destroy();
        header("Location: Login.php");
        exit;
    }

    $displayName = !empty($user['username'])
        ? $user['username']
        : $user['full_name'];

    $firstLetter = strtoupper(substr($displayName, 0, 1));

    $userRole = $user['role'];

    /* REFRESH PROFILE IMAGE PATH AFTER FORM SUBMISSION */
    $profileImage = trim((string) ($user['profile_image'] ?? ''));

    if (!empty($profileImage)) {

        $profileImage = str_replace("\\", "/", $profileImage);

        if (
            !preg_match('/^https?:\/\//i', $profileImage) &&
            strpos($profileImage, 'data:') !== 0
        ) {

            $profileImage = ltrim($profileImage, '/');

            if (strpos($profileImage, 'uploads/profile_images/') === 0) {
                $profileImage = 'Admin_Portal/' . $profileImage;
            }
        }
    }
}


/* =========================================================
   FETCH UNREAD NOTIFICATIONS COUNT
========================================================= */

$unreadNotifications = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE (
        user_id = ?
        OR audience = 'All students'
        OR user_id IS NULL
    )
    AND is_read = 0
");

if ($stmt) {

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $unreadNotifications = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();
}


/* =========================================================
   FETCH LATEST NOTIFICATIONS
========================================================= */

$notifications = [];

$stmt = $conn->prepare("
    SELECT
        title AS notification_title,
        message AS notification_message,
        is_read,
        created_at
    FROM notifications
    WHERE (
        user_id = ?
        OR audience = 'All students'
        OR user_id IS NULL
    )
    ORDER BY created_at DESC
    LIMIT 5
");

if ($stmt) {

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   PROFILE DISPLAY INFORMATION
========================================================= */

$username = !empty($user['username'])
    ? $user['username']
    : $user['full_name'];

$firstLetter = strtoupper(substr($username, 0, 1));


/* =========================================================
   ESCAPE OUTPUT
========================================================= */

function escapeHTML($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Settings</title>

    <link rel="stylesheet"
          href="css/Settings_style.css?v=<?php echo filemtime(__DIR__ . '/css/Settings_style.css'); ?>">

    <style>
        .profile-picture img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 50%;
        }

        .current-profile-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            EDU<span>QUEST</span>
        </div>

        <nav class="navigation">

            <a href="DashboardPage.php" class="nav-item">
                <span>⌂</span>
                Dashboard
            </a>

            <a href="CoursePage.php" class="nav-item">
                <span>▣</span>
                Courses
            </a>

            <a href="MissionsPage.php" class="nav-item">
                <span>⚑</span>
                Missions
            </a>

            <a href="ProgressPage.php" class="nav-item">
                <span>▤</span>
                Progress
            </a>

            <a href="AchievementsPage.php" class="nav-item">
                <span>★</span>
                Achievements
            </a>

            <a href="ActivityRecords.php" class="nav-item">
                <span>📝</span>
                Activity Records
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="SettingsPage.php" class="nav-item active">
                <span>⚙</span>
                Settings
            </a>

            <a href="logout.php" class="nav-item">
                <span>↪</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Settings</h1>
                <p>Manage your account preferences.</p>
            </div>

            <div class="profile">

                <!-- NOTIFICATIONS -->
                <div class="notification-container">

                    <button
                        type="button"
                        class="notification-btn"
                        onclick="toggleNotifications()"
                        aria-label="Notifications">

                        🔔

                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge">
                                <?php echo $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>

                    </button>


                    <div
                        class="notification-dropdown"
                        id="notificationDropdown">

                        <div class="notification-header">

                            <h3>Notifications</h3>

                            <span>
                                <?php echo $unreadNotifications; ?> New
                            </span>

                        </div>


                        <?php if (empty($notifications)): ?>

                            <div class="notification-item">

                                <div class="notification-content">
                                    <p>No notifications available.</p>
                                </div>

                            </div>

                        <?php else: ?>

                            <?php foreach ($notifications as $note): ?>

                                <div class="notification-item">

                                    <div class="notification-icon">
                                        📢
                                    </div>

                                    <div class="notification-content">

                                        <strong>
                                            <?php echo escapeHTML($note['notification_title']); ?>
                                        </strong>

                                        <p>
                                            <?php echo escapeHTML($note['notification_message']); ?>
                                        </p>

                                        <small>
                                            <?php echo escapeHTML($note['created_at']); ?>
                                        </small>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                        <div class="notification-footer">

                            <a href="NotificationPage.php">
                                View All Notifications
                            </a>

                        </div>

                    </div>

                </div>


                <!-- DARK MODE BUTTON -->
                <button
                    type="button"
                    class="dark-mode"
                    onclick="toggleDarkMode()"
                    aria-label="Toggle dark mode">

                    ☾

                </button>


                <!-- PROFILE INFORMATION -->
                <div class="profile-info">

                    <div class="profile-picture">

                        <?php if (!empty($profileImage)): ?>

                            <img
                                src="<?php echo escapeHTML($profileImage); ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?php echo escapeHTML($firstLetter); ?>';">

                        <?php else: ?>

                            <?php echo escapeHTML($firstLetter); ?>

                        <?php endif; ?>

                    </div>

                    <div>

                        <strong>
                            <?php echo escapeHTML($username); ?>
                        </strong>

                        <small>
                            <?php echo $userRole === 'admin' ? 'Administrator' : 'Student'; ?>
                        </small>

                    </div>

                </div>

            </div>

        </header>


        <!-- STATUS MESSAGE -->
        <?php if ($message !== ''): ?>

            <div class="message <?php echo escapeHTML($messageType); ?>">
                <?php echo escapeHTML($message); ?>
            </div>

        <?php endif; ?>


        <section class="settings-container">


            <!-- PROFILE INFORMATION -->
            <div class="settings-card">

                <div class="settings-title">

                    <h2>Profile Information</h2>

                    <p>Update your personal information and profile picture.</p>

                </div>


                <form
                    method="POST"
                    action="SettingsPage.php"
                    enctype="multipart/form-data">

                    <input
                        type="hidden"
                        name="action"
                        value="update_profile">


                    <div class="form-row">


                        <!-- FULL NAME -->
                        <div class="form-group">

                            <label for="full_name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?php echo escapeHTML($user['full_name']); ?>"
                                placeholder="Full Name"
                                maxlength="255"
                                required>

                        </div>


                        <!-- EMAIL -->
                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo escapeHTML($user['email']); ?>"
                                placeholder="Email Address"
                                required>

                        </div>

                    </div>


                    <!-- PROFILE PICTURE UPLOAD -->
                    <div class="form-group profile-upload-group">

                        <label for="profile_image">
                            Profile Picture
                        </label>


                        <div class="profile-upload-preview">

                            <?php if (!empty($profileImage)): ?>

                                <img
                                    src="<?php echo escapeHTML($profileImage); ?>"
                                    alt="Current Profile Picture"
                                    class="current-profile-image"
                                    onerror="this.style.display='none'; this.parentElement.insertAdjacentHTML('afterbegin', '<div class=&quot;profile-placeholder&quot;><?php echo escapeHTML($firstLetter); ?></div>');">

                            <?php else: ?>

                                <div class="profile-placeholder">
                                    <?php echo escapeHTML($firstLetter); ?>
                                </div>

                            <?php endif; ?>


                            <div class="profile-upload-details">

                                <input
                                    type="file"
                                    id="profile_image"
                                    name="profile_image"
                                    accept="image/jpeg,image/png,image/webp">

                                <small>
                                    JPG, PNG, or WEBP. Maximum file size: 2 MB.
                                </small>

                            </div>

                        </div>

                    </div>


                    <button type="submit" class="save-button">
                        Save Changes
                    </button>

                </form>

            </div>



            <!-- ACCOUNT SETTINGS -->
            <div class="settings-card">

                <div class="settings-title">

                    <h2>Account Settings</h2>

                    <p>Manage your account preferences.</p>

                </div>


                <!-- DARK MODE -->
                <div class="setting-row">

                    <div>

                        <strong>Dark Mode</strong>

                        <p>Change the appearance of EduQuest.</p>

                    </div>

                    <button
                        type="button"
                        class="setting-button"
                        id="darkModeButton"
                        onclick="toggleDarkMode()">

                        ON/OFF

                    </button>

                </div>


                <!-- CHANGE PASSWORD -->
                <div class="setting-row">

                    <div>

                        <strong>Change Password</strong>

                        <p>Update your account password.</p>

                    </div>

                    <button
                        type="button"
                        class="setting-button"
                        onclick="togglePasswordForm()">

                        Change

                    </button>

                </div>


                <!-- CHANGE PASSWORD FORM -->
                <form
                    method="POST"
                    action="SettingsPage.php"
                    class="password-form"
                    id="passwordForm"
                    hidden>

                    <input
                        type="hidden"
                        name="action"
                        value="change_password">


                    <div class="form-group">

                        <label for="current_password">
                            Current Password
                        </label>

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required>

                    </div>


                    <div class="form-group">

                        <label for="new_password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required>

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            required>

                    </div>


                    <button type="submit" class="save-button">
                        Update Password
                    </button>

                </form>

            </div>


        </section>

    </main>

</div>



<script>

/* =========================================================
   DARK MODE
========================================================= */

function toggleDarkMode() {

    document.body.classList.toggle("dark-mode-active");

    const isDarkMode =
        document.body.classList.contains("dark-mode-active");

    localStorage.setItem(
        "eduquestDarkMode",
        isDarkMode ? "enabled" : "disabled"
    );

    updateDarkModeButton();
}


function updateDarkModeButton() {

    const button = document.getElementById("darkModeButton");

    if (button) {

        button.textContent =
            document.body.classList.contains("dark-mode-active")
                ? "ON"
                : "OFF";

    }
}


/* RESTORE DARK MODE PREFERENCE */
if (localStorage.getItem("eduquestDarkMode") === "enabled") {

    document.body.classList.add("dark-mode-active");

}

updateDarkModeButton();


/* =========================================================
   NOTIFICATIONS
========================================================= */

function toggleNotifications() {

    const dropdown =
        document.getElementById("notificationDropdown");

    dropdown.classList.toggle("show");

}


/* =========================================================
   CHANGE PASSWORD FORM
========================================================= */

function togglePasswordForm() {

    const form = document.getElementById("passwordForm");

    form.hidden = !form.hidden;

}


/* =========================================================
   CLOSE NOTIFICATIONS WHEN CLICKING OUTSIDE
========================================================= */

document.addEventListener("click", function(event) {

    const container =
        document.querySelector(".notification-container");

    const dropdown =
        document.getElementById("notificationDropdown");

    if (container && !container.contains(event.target)) {

        dropdown.classList.remove("show");

    }

});


/* =========================================================
   PROFILE IMAGE PREVIEW
========================================================= */

const profileImageInput =
    document.getElementById("profile_image");

if (profileImageInput) {

    profileImageInput.addEventListener("change", function() {

        const file = this.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {

            alert("Please select a JPG, PNG, or WEBP image.");

            this.value = "";

            return;
        }

        if (file.size > 2 * 1024 * 1024) {

            alert("Image must not exceed 2 MB.");

            this.value = "";

            return;
        }

        const previewContainer =
            document.querySelector(".profile-upload-preview");

        const oldPreview =
            previewContainer.querySelector(
                ".current-profile-image, .profile-placeholder"
            );

        const image = document.createElement("img");

        image.className = "current-profile-image";
        image.alt = "Selected Profile Picture";
        image.src = URL.createObjectURL(file);

        if (oldPreview) {
            oldPreview.replaceWith(image);
        }

    });

}

</script>

</body>
</html>
