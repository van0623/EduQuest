
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

/* =========================================
   INITIAL SETTINGS
========================================= */

$error = "";
$success = "";

$currentAdminId = (int) $_SESSION["user_id"];

/* =========================================
   HELPER FUNCTION
========================================= */

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, "UTF-8");
}

/* =========================================
   CSRF PROTECTION
========================================= */

if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["admin_csrf"];

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $submittedToken)) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /* =====================================
           EDIT ADMINISTRATOR
        ===================================== */

        if ($action === "edit_admin") {

            $adminId = filter_input(
                INPUT_POST,
                "admin_id",
                FILTER_VALIDATE_INT
            );

            $fullName = trim($_POST["full_name"] ?? "");
            $username = trim($_POST["username"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $accountStatus = trim($_POST["account_status"] ?? "");

            $allowedStatuses = [
                "active",
                "inactive",
                "suspended"
            ];

            if (!$adminId || $adminId <= 0) {

                $error = "Invalid administrator account.";

            } elseif ($adminId === $currentAdminId) {

                $error = "You cannot edit your own account using this page.";

            } elseif (
                $fullName === "" ||
                $username === "" ||
                $email === ""
            ) {

                $error = "Please complete all required fields.";

            } elseif (
                strlen($fullName) > 150 ||
                strlen($username) > 60 ||
                strlen($email) > 190
            ) {

                $error = "One or more fields exceed the allowed character limit.";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $error = "Please enter a valid email address.";

            } elseif (!in_array($accountStatus, $allowedStatuses, true)) {

                $error = "Invalid account status.";

            } else {

                // Verify that the selected account is an administrator.
                $checkAdmin = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE user_id = ?
                    AND role = 'admin'
                    LIMIT 1
                ");

                if (!$checkAdmin) {
                    die("Unable to verify administrator account.");
                }

                $checkAdmin->bind_param("i", $adminId);
                $checkAdmin->execute();
                $checkAdmin->store_result();

                if ($checkAdmin->num_rows === 0) {

                    $error = "Administrator account not found.";

                } else {

                    // Check duplicate username and email.
                    $checkDuplicate = $conn->prepare("
                        SELECT user_id
                        FROM users
                        WHERE (username = ? OR email = ?)
                        AND user_id != ?
                        LIMIT 1
                    ");

                    if (!$checkDuplicate) {
                        die("Unable to check duplicate accounts.");
                    }

                    $checkDuplicate->bind_param(
                        "ssi",
                        $username,
                        $email,
                        $adminId
                    );

                    $checkDuplicate->execute();
                    $checkDuplicate->store_result();

                    if ($checkDuplicate->num_rows > 0) {

                        $error = "Username or email is already used by another account.";

                    } else {

                        // Update administrator information only.
                        // Password changes are not handled on this page.
                        $stmt = $conn->prepare("
                            UPDATE users
                            SET
                                full_name = ?,
                                username = ?,
                                email = ?,
                                account_status = ?
                            WHERE user_id = ?
                            AND role = 'admin'
                        ");

                        if ($stmt) {

                            $stmt->bind_param(
                                "ssssi",
                                $fullName,
                                $username,
                                $email,
                                $accountStatus,
                                $adminId
                            );

                            if ($stmt->execute()) {

                                $success = "Administrator account updated successfully.";

                            } else {

                                $error = "Unable to update administrator account.";

                            }

                            $stmt->close();

                        } else {

                            $error = "Unable to prepare administrator update.";

                        }
                    }

                    $checkDuplicate->close();
                }

                $checkAdmin->close();
            }

        } else {

            $error = "Invalid action.";
        }
    }
}

/* =========================================
   FETCH ADMINISTRATORS
========================================= */

$adminsResult = $conn->query("
    SELECT
        user_id,
        full_name,
        username,
        email,
        role,
        account_status,
        profile_image,
        created_at,
        updated_at,
        last_login
    FROM users
    WHERE role = 'admin'
    ORDER BY created_at DESC
");

if (!$adminsResult) {
    die("Database error: " . e($conn->error));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Accounts | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>

    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>

</head>

<body>

<div id="admin-app" data-page="Admin Accounts">

    <main class="page-content">

        <!-- PAGE HEADING -->

        <section class="page-heading split-heading">

            <div>

                <p class="eyebrow">ACCESS MANAGEMENT</p>

                <h1>Admin Accounts</h1>

                <p class="muted">
                    Manage administrator information and account access.
                </p>

            </div>

        </section>


        <!-- SUCCESS AND ERROR MESSAGES -->

        <?php if ($error !== ""): ?>

            <div class="error-message"
                 style="margin-bottom:20px;padding:12px;background:#FFF0F0;color:#B42318;border:1px solid #F5C2C0;border-radius:7px;font-size:13px;">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="success-message"
                 style="margin-bottom:20px;padding:12px;background:#F0FFF0;color:#188423;border:1px solid #C2F5C0;border-radius:7px;font-size:13px;">

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- ADMIN ACCOUNTS TABLE -->

        <section class="panel table-panel">

            <div class="table-wrap" style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>
                            <th>Administrator</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($adminsResult->num_rows > 0): ?>

                        <?php while ($admin = $adminsResult->fetch_assoc()): ?>

                            <?php

                            $lastLogin = !empty($admin["last_login"])
                                ? date("M j, Y g:i A", strtotime($admin["last_login"]))
                                : "Never";

                            $statusClass = $admin["account_status"] === "active"
                                ? "published"
                                : "draft";

                            $adminData = htmlspecialchars(
                                json_encode($admin),
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>

                            <tr>

                                <td>
                                    <b><?= e($admin["full_name"]) ?></b>
                                    <small><?= e($admin["email"]) ?></small>
                                </td>

                                <td>
                                    <?= e($admin["username"]) ?>
                                </td>

                                <td>
                                    Administrator
                                </td>

                                <td>

                                    <span class="status <?= e($statusClass) ?>">
                                        <?= e(ucfirst($admin["account_status"])) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($lastLogin) ?>
                                </td>

                                <td>

                                    <div class="table-actions">

                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline"
                                            data-admin="<?= $adminData ?>"
                                            onclick="viewAdmin(this)">

                                            View

                                        </button>

                                        <?php if ((int) $admin["user_id"] !== $currentAdminId): ?>

                                            <button
                                                type="button"
                                                class="btn btn-small btn-outline"
                                                data-admin="<?= $adminData ?>"
                                                onclick="editAdmin(this)">

                                                Edit

                                            </button>

                                        <?php else: ?>

                                            <span class="muted">
                                                Current account
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6"
                                style="text-align:center;padding:30px;color:#777;">

                                No administrator accounts found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- VIEW ADMIN MODAL -->

        <dialog class="modal" id="viewAdminModal">

            <div class="modal-card">

                <div class="modal-heading">

                    <h2>Administrator Details</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        aria-label="Close"
                        onclick="this.closest('dialog').close()">

                        ×

                    </button>

                </div>


                <div class="admin-details">

                    <p>
                        <b>Full Name</b>
                        <span id="viewName"></span>
                    </p>

                    <p>
                        <b>Username</b>
                        <span id="viewUsername"></span>
                    </p>

                    <p>
                        <b>Email Address</b>
                        <span id="viewEmail"></span>
                    </p>

                    <p>
                        <b>Account Role</b>
                        <span id="viewRole"></span>
                    </p>

                    <p>
                        <b>Account Status</b>
                        <span id="viewStatus"></span>
                    </p>

                    <p>
                        <b>Date Created</b>
                        <span id="viewCreated"></span>
                    </p>

                    <p>
                        <b>Last Updated</b>
                        <span id="viewUpdated"></span>
                    </p>

                    <p>
                        <b>Last Login</b>
                        <span id="viewLastLogin"></span>
                    </p>

                </div>


                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="this.closest('dialog').close()">

                        Close

                    </button>

                </div>

            </div>

        </dialog>


        <!-- EDIT ADMIN MODAL -->

        <dialog class="modal" id="editAdminModal">

            <form method="POST" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <input type="hidden" name="action" value="edit_admin">

                <input type="hidden" name="admin_id" id="editAdminId">


                <div class="modal-heading">

                    <h2>Edit Administrator</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        aria-label="Close"
                        onclick="this.closest('dialog').close()">

                        ×

                    </button>

                </div>


                <p class="muted" id="editAdminDescription"></p>


                <label>

                    Full Name

                    <input
                        type="text"
                        name="full_name"
                        id="editFullName"
                        maxlength="150"
                        required>

                </label>


                <label>

                    Username

                    <input
                        type="text"
                        name="username"
                        id="editUsername"
                        maxlength="60"
                        required>

                </label>


                <label>

                    Email Address

                    <input
                        type="email"
                        name="email"
                        id="editEmail"
                        maxlength="190"
                        required>

                </label>


                <label>

                    Account Status

                    <select name="account_status" id="editStatus" required>

                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>

                    </select>

                </label>


                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="this.closest('dialog').close()">

                        Cancel

                    </button>

                    <button type="submit" class="btn btn-primary">

                        Save Changes

                    </button>

                </div>

            </form>

        </dialog>


        <footer class="page-footer">

            © 2026 EduQuest · Admin Portal

        </footer>

    </main>

</div>


<div class="toast" id="toast" role="status" aria-live="polite"></div>


<script>

/* =========================================
   VIEW ADMINISTRATOR
========================================= */

function viewAdmin(button) {

    const admin = JSON.parse(button.dataset.admin);

    document.getElementById("viewName").textContent =
        admin.full_name || "—";

    document.getElementById("viewUsername").textContent =
        admin.username || "—";

    document.getElementById("viewEmail").textContent =
        admin.email || "—";

    document.getElementById("viewRole").textContent =
        admin.role || "admin";

    document.getElementById("viewStatus").textContent =
        admin.account_status || "—";

    document.getElementById("viewCreated").textContent =
        admin.created_at || "—";

    document.getElementById("viewUpdated").textContent =
        admin.updated_at || "—";

    document.getElementById("viewLastLogin").textContent =
        admin.last_login || "Never";

    document.getElementById("viewAdminModal").showModal();

}


/* =========================================
   EDIT ADMINISTRATOR
========================================= */

function editAdmin(button) {

    const admin = JSON.parse(button.dataset.admin);

    document.getElementById("editAdminId").value =
        admin.user_id;

    document.getElementById("editAdminDescription").textContent =
        "Editing account: " + admin.full_name;

    document.getElementById("editFullName").value =
        admin.full_name || "";

    document.getElementById("editUsername").value =
        admin.username || "";

    document.getElementById("editEmail").value =
        admin.email || "";

    document.getElementById("editStatus").value =
        admin.account_status || "active";

    document.getElementById("editAdminModal").showModal();

}

</script>

</body>
</html>
