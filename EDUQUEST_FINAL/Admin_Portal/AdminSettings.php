
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";
$admin_id = (int) ($_SESSION['user_id'] ?? 0);

/* =========================================================
   CREATE ADMIN PREFERENCES TABLE IF IT DOES NOT EXIST
========================================================= */

$conn->query("
    CREATE TABLE IF NOT EXISTS admin_preferences (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL UNIQUE,
        dark_mode TINYINT(1) NOT NULL DEFAULT 0,
        default_date_range VARCHAR(20) NOT NULL DEFAULT 'all',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_admin_preferences_user
            FOREIGN KEY (user_id)
            REFERENCES users(user_id)
            ON DELETE CASCADE
    )
");

/* =========================================================
   GET CURRENT ADMIN INFORMATION
========================================================= */

$headerAdminStmt = $conn->prepare("
    SELECT full_name, profile_image
    FROM users
    WHERE user_id = ?
");

$headerAdminStmt->bind_param("i", $admin_id);
$headerAdminStmt->execute();

$headerAdminData = $headerAdminStmt->get_result()->fetch_assoc();

$headerAdminStmt->close();

$_SESSION['full_name'] = $headerAdminData['full_name'] ?? 'Admin';
$_SESSION['profile_image'] = $headerAdminData['profile_image'] ?? '';

/* =========================================================
   PROFILE IMAGE SETTINGS
========================================================= */

$uploadDirectory = __DIR__ . "/uploads/profile_images/";
$uploadPath = "uploads/profile_images/";
$maxFileSize = 2 * 1024 * 1024;

/* =========================================================
   GET SAVED ADMIN PREFERENCES
========================================================= */

$darkMode = 0;
$defaultDateRange = 'all';

$preferencesStmt = $conn->prepare("
    SELECT dark_mode, default_date_range
    FROM admin_preferences
    WHERE user_id = ?
    LIMIT 1
");

$preferencesStmt->bind_param("i", $admin_id);
$preferencesStmt->execute();

$preferencesData = $preferencesStmt->get_result()->fetch_assoc();

$preferencesStmt->close();

if ($preferencesData) {
    $darkMode = (int) $preferencesData['dark_mode'];
    $defaultDateRange = $preferencesData['default_date_range'];
}

/* =========================================================
   HANDLE FORM SUBMISSIONS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? '';

    /* =====================================================
       1. UPDATE ADMIN PROFILE
    ===================================================== */

    if ($action === 'update_profile') {

        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please provide a valid full name and email address.";

        } else {

            // CHECK IF EMAIL IS ALREADY USED BY ANOTHER ACCOUNT
            $chk = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                  AND user_id != ?
                LIMIT 1
            ");

            $chk->bind_param("si", $email, $admin_id);
            $chk->execute();
            $chk->store_result();

            $emailExists = $chk->num_rows > 0;

            $chk->close();

            if ($emailExists) {

                $error = "This email address is already in use by another account.";

            } else {

                // GET CURRENT PROFILE IMAGE
                $currentImage = "";

                $currentStmt = $conn->prepare("
                    SELECT profile_image
                    FROM users
                    WHERE user_id = ?
                ");

                $currentStmt->bind_param("i", $admin_id);
                $currentStmt->execute();

                $currentData = $currentStmt->get_result()->fetch_assoc();

                if ($currentData) {
                    $currentImage = $currentData['profile_image'] ?? '';
                }

                $currentStmt->close();

                $newImagePath = $currentImage;
                $uploadedFilePath = "";

                // CHECK IF A NEW IMAGE WAS SELECTED
                if (
                    isset($_FILES['profile_image']) &&
                    $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    $file = $_FILES['profile_image'];

                    if ($file['error'] !== UPLOAD_ERR_OK) {

                        $error = "There was a problem uploading your image.";

                    } elseif ($file['size'] > $maxFileSize) {

                        $error = "Profile image must not exceed 2 MB.";

                    } else {

                        // VALIDATE ACTUAL FILE TYPE
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($file['tmp_name']);

                        $allowedTypes = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp'
                        ];

                        if (!isset($allowedTypes[$mimeType])) {

                            $error = "Only JPG, PNG, and WEBP images are allowed.";

                        } elseif (
                            getimagesize($file['tmp_name']) === false
                        ) {

                            $error = "The uploaded file is not a valid image.";

                        } else {

                            // CREATE UPLOAD FOLDER
                            if (
                                !is_dir($uploadDirectory) &&
                                !mkdir($uploadDirectory, 0755, true)
                            ) {

                                $error = "Unable to create the profile image folder.";

                            } else {

                                // GENERATE UNIQUE FILE NAME
                                $extension = $allowedTypes[$mimeType];

                                $fileName = "admin_" . $admin_id . "_" .
                                    bin2hex(random_bytes(8)) . "." . $extension;

                                $destination = $uploadDirectory . $fileName;

                                if (move_uploaded_file($file['tmp_name'], $destination)) {

                                    $newImagePath = $uploadPath . $fileName;
                                    $uploadedFilePath = $destination;

                                } else {

                                    $error = "Failed to save the uploaded image.";

                                }
                            }
                        }
                    }
                }

                // SAVE PROFILE INFORMATION
                if ($error === "") {

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, profile_image = ?
                        WHERE user_id = ?
                    ");

                    $stmt->bind_param(
                        "sssi",
                        $full_name,
                        $email,
                        $newImagePath,
                        $admin_id
                    );

                    if ($stmt->execute()) {

                        $success = "Admin profile updated successfully!";

                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['profile_image'] = $newImagePath;

                        // DELETE OLD IMAGE AFTER SUCCESSFUL UPDATE
                        if (
                            $uploadedFilePath !== "" &&
                            $currentImage !== "" &&
                            strpos($currentImage, $uploadPath) === 0
                        ) {

                            $oldImage = __DIR__ . "/" . $currentImage;

                            $oldImageDirectory = realpath($uploadDirectory);
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

                        $error = "Failed to update profile: " . $conn->error;

                        // REMOVE NEW IMAGE IF DATABASE UPDATE FAILED
                        if (
                            $uploadedFilePath !== "" &&
                            is_file($uploadedFilePath)
                        ) {
                            unlink($uploadedFilePath);
                        }
                    }

                    $stmt->close();
                }
            }
        }
    }

    /* =====================================================
       2. CHANGE PASSWORD
    ===================================================== */

    elseif ($action === 'change_password') {

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (
            $current_password === '' ||
            $new_password === '' ||
            $confirm_password === ''
        ) {

            $error = "Please fill in all password fields.";

        } elseif (strlen($new_password) < 8) {

            $error = "Your new password must be at least 8 characters.";

        } elseif ($new_password !== $confirm_password) {

            $error = "New passwords do not match.";

        } else {

            $pwdStmt = $conn->prepare("
                SELECT password_hash
                FROM users
                WHERE user_id = ?
            ");

            $pwdStmt->bind_param("i", $admin_id);
            $pwdStmt->execute();

            $res = $pwdStmt->get_result()->fetch_assoc();

            $pwdStmt->close();

            if (
                $res &&
                password_verify($current_password, $res['password_hash'])
            ) {

                $new_hash = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                $pwdUpd = $conn->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE user_id = ?
                ");

                $pwdUpd->bind_param("si", $new_hash, $admin_id);

                if ($pwdUpd->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }

                $pwdUpd->close();

            } else {

                $error = "Incorrect current password.";

            }
        }
    }

    /* =====================================================
       3. SAVE PORTAL PREFERENCES
    ===================================================== */

    elseif ($action === 'update_preferences') {

        // CHECKBOX RETURNS 1 WHEN ENABLED
        // HIDDEN INPUT RETURNS 0 WHEN DISABLED
        $darkMode = isset($_POST['dark_mode'])
            ? (int) $_POST['dark_mode']
            : 0;

        $darkMode = $darkMode === 1 ? 1 : 0;

        $defaultDateRange = $_POST['default_date_range'] ?? 'all';

        $allowedDateRanges = [
            'all',
            '30',
            '7'
        ];

        if (!in_array($defaultDateRange, $allowedDateRanges, true)) {

            $error = "Invalid report date range selected.";

        } else {

            // INSERT OR UPDATE THE ADMIN'S PREFERENCES
            $preferencesUpdateStmt = $conn->prepare("
                INSERT INTO admin_preferences (
                    user_id,
                    dark_mode,
                    default_date_range
                )
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    dark_mode = VALUES(dark_mode),
                    default_date_range = VALUES(default_date_range)
            ");

            $preferencesUpdateStmt->bind_param(
                "iis",
                $admin_id,
                $darkMode,
                $defaultDateRange
            );

            if ($preferencesUpdateStmt->execute()) {

                $success = "Portal preferences saved successfully!";

            } else {

                $error = "Unable to save portal preferences.";

            }

            $preferencesUpdateStmt->close();
        }
    }

    /* =====================================================
       REFRESH ADMIN INFORMATION AFTER FORM SUBMISSION
    ===================================================== */

    $headerAdminStmt = $conn->prepare("
        SELECT full_name, profile_image
        FROM users
        WHERE user_id = ?
    ");

    $headerAdminStmt->bind_param("i", $admin_id);
    $headerAdminStmt->execute();

    $headerAdminData = $headerAdminStmt->get_result()->fetch_assoc();

    $headerAdminStmt->close();

    if ($headerAdminData) {
        $_SESSION['full_name'] = $headerAdminData['full_name'] ?? 'Admin';
        $_SESSION['profile_image'] = $headerAdminData['profile_image'] ?? '';
    }

    // REFRESH SAVED PREFERENCES
    $preferencesStmt = $conn->prepare("
        SELECT dark_mode, default_date_range
        FROM admin_preferences
        WHERE user_id = ?
        LIMIT 1
    ");

    $preferencesStmt->bind_param("i", $admin_id);
    $preferencesStmt->execute();

    $preferencesData = $preferencesStmt->get_result()->fetch_assoc();

    $preferencesStmt->close();

    if ($preferencesData) {
        $darkMode = (int) $preferencesData['dark_mode'];
        $defaultDateRange = $preferencesData['default_date_range'];
    }
}


/* =========================================================
   FETCH CURRENT ADMIN PROFILE DATA
========================================================= */

$profileStmt = $conn->prepare("
    SELECT full_name, email, profile_image
    FROM users
    WHERE user_id = ?
");

$profileStmt->bind_param("i", $admin_id);
$profileStmt->execute();

$adminData = $profileStmt->get_result()->fetch_assoc();

$profileStmt->close();

/* DEFAULT PROFILE IMAGE */
$profileImage = !empty($adminData['profile_image'])
    ? $adminData['profile_image']
    : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">

    <script src="admin_layout.js" defer></script>

    <script>
        window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
        window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
        window.adminDarkMode = <?= (int) $darkMode ?>;
    </script>

    <style>
        .profile-image-upload {
            display: flex;
            align-items: center;
            gap: 22px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .profile-image-preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            background: #e8e8e8;
            border: 3px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image-placeholder {
            font-size: 34px;
            font-weight: 600;
            color: #777;
        }

        .profile-image-controls {
            flex: 1;
            min-width: 200px;
        }

        .profile-image-controls input[type="file"] {
            display: block;
            width: 100%;
            margin-top: 10px;
        }

        .profile-image-controls small {
            display: block;
            margin-top: 8px;
        }

        /* PORTAL PREFERENCES */
        .toggle-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .toggle-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #5DD62C;
        }
    </style>
</head>

<body>

<div id="admin-app" data-page="Settings">

    <main class="page-content">

        <section class="page-heading">
            <div>
                <p class="eyebrow">PREFERENCES</p>
                <h1>Settings</h1>
                <p class="muted">
                    Manage your admin profile and portal preferences.
                </p>
            </div>
        </section>

        <?php if ($error !== ""): ?>
            <div class="error-message"
                 style="margin-bottom:20px;padding:12px;background:#FFF0F0;color:#B42318;border:1px solid #F5C2C0;border-radius:7px;font-size:13px;">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="success-message"
                 style="margin-bottom:20px;padding:12px;background:#F0FFF0;color:#188423;border:1px solid #C2F5C0;border-radius:7px;font-size:13px;">
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <section class="settings-layout">

            <!-- SETTINGS NAVIGATION -->
            <nav class="settings-nav">
                <a href="#profileSettings">Admin Profile</a>
                <a href="#securitySettings">Security</a>
                <a href="#portalSettings">Portal Preferences</a>
            </nav>

            <div class="settings-content">

                <!-- =========================================
                     ADMIN PROFILE
                ========================================== -->

                <form method="POST"
                      action=""
                      enctype="multipart/form-data"
                      class="panel settings-panel"
                      id="profileSettings">

                    <input type="hidden" name="action" value="update_profile">

                    <div class="panel-heading">
                        <div>
                            <h2>Admin Profile</h2>
                            <p class="muted">Update your personal information.</p>
                        </div>
                    </div>

                    <div class="profile-image-upload">

                        <div class="profile-image-preview">

                            <?php if ($profileImage !== ""): ?>

                                <img
                                    id="profilePreview"
                                    src="<?= htmlspecialchars($profileImage, ENT_QUOTES, "UTF-8") ?>"
                                    alt="Admin Profile Image"
                                >

                            <?php else: ?>

                                <img
                                    id="profilePreview"
                                    src=""
                                    alt="Admin Profile Image"
                                    style="display:none;"
                                >

                                <span
                                    class="profile-image-placeholder"
                                    id="profilePlaceholder">
                                    <?= strtoupper(substr($adminData['full_name'] ?? 'A', 0, 1)) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="profile-image-controls">

                            <label for="profileImageInput">
                                Profile Image
                            </label>

                            <input
                                type="file"
                                name="profile_image"
                                id="profileImageInput"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <small class="muted">
                                JPG, PNG, or WEBP. Maximum file size: 2 MB.
                            </small>

                        </div>

                    </div>

                    <label>
                        Full Name

                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars($adminData['full_name'] ?? '', ENT_QUOTES, "UTF-8") ?>"
                            required
                        >
                    </label>

                    <label>
                        Email Address

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($adminData['email'] ?? '', ENT_QUOTES, "UTF-8") ?>"
                            required
                        >
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Save Profile
                        </button>
                    </div>

                </form>


                <!-- =========================================
                     SECURITY
                ========================================== -->

                <form method="POST"
                      action=""
                      class="panel settings-panel"
                      id="securitySettings">

                    <input type="hidden" name="action" value="change_password">

                    <div class="panel-heading">
                        <div>
                            <h2>Security</h2>
                            <p class="muted">Change your password.</p>
                        </div>
                    </div>

                    <label>
                        Current Password

                        <input
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                            placeholder="Enter current password"
                        >
                    </label>

                    <label>
                        New Password

                        <input
                            type="password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                            placeholder="Enter new password"
                        >
                    </label>

                    <label>
                        Confirm New Password

                        <input
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                            placeholder="Confirm new password"
                        >
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Change Password
                        </button>
                    </div>

                </form>


                <!-- =========================================
                     PORTAL PREFERENCES
                ========================================== -->

                <form method="POST"
                      action=""
                      class="panel settings-panel"
                      id="portalSettings">

                    <input type="hidden" name="action" value="update_preferences">

                    <div class="panel-heading">
                        <div>
                            <h2>Portal Preferences</h2>
                            <p class="muted">
                                Customize your admin experience.
                            </p>
                        </div>
                    </div>

                    <!-- DARK MODE -->
                    <label class="toggle-label" for="settingsDarkMode">

                        <span>
                            <strong>Dark Mode</strong>
                            <small class="muted">
                                Enable or disable dark mode for your admin portal.
                            </small>
                        </span>

                        <input
                            type="hidden"
                            name="dark_mode"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="dark_mode"
                            id="settingsDarkMode"
                            value="1"
                            <?= $darkMode === 1 ? 'checked' : '' ?>
                        >

                    </label>

                    <!-- DEFAULT REPORT DATE RANGE -->
                    <label for="defaultDateRange">
                        Default Report Date Range

                        <select
                            name="default_date_range"
                            id="defaultDateRange"
                        >

                            <option
                                value="all"
                                <?= $defaultDateRange === 'all' ? 'selected' : '' ?>
                            >
                                All time
                            </option>

                            <option
                                value="30"
                                <?= $defaultDateRange === '30' ? 'selected' : '' ?>
                            >
                                Last 30 days
                            </option>

                            <option
                                value="7"
                                <?= $defaultDateRange === '7' ? 'selected' : '' ?>
                            >
                                Last 7 days
                            </option>

                        </select>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Save Preferences
                        </button>
                    </div>

                </form>

            </div>

        </section>

        <footer class="page-footer">
            © 2026 EduQuest · Admin Portal
        </footer>

    </main>

</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>


<script>

/* =========================================================
   PROFILE IMAGE PREVIEW
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const imageInput = document.getElementById("profileImageInput");
    const imagePreview = document.getElementById("profilePreview");
    const imagePlaceholder = document.getElementById("profilePlaceholder");

    if (imageInput) {

        imageInput.addEventListener("change", function () {

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

                alert("The image must not exceed 2 MB.");

                this.value = "";

                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {

                imagePreview.src = event.target.result;
                imagePreview.style.display = "block";

                if (imagePlaceholder) {
                    imagePlaceholder.style.display = "none";
                }
            };

            reader.readAsDataURL(file);
        });
    }


    /* =========================================
       RESTORE SAVED DARK MODE
    ========================================= */

    const darkModeEnabled = Number(window.adminDarkMode) === 1;

    document.body.classList.toggle(
        "dark-mode-active",
        darkModeEnabled
    );

    const darkModeCheckbox =
        document.getElementById("settingsDarkMode");

    if (darkModeCheckbox) {

        darkModeCheckbox.checked = darkModeEnabled;

        darkModeCheckbox.addEventListener("change", function () {

            document.body.classList.toggle(
                "dark-mode-active",
                this.checked
            );

        });
    }

});

</script>

</body>
</html>
