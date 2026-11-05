
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";
$activities = [];
$notifications = [];

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function adminActivityEscape($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}


function adminActivitySafeQuery($conn, $sql)
{
    try {
        return $conn->query($sql);
    } catch (mysqli_sql_exception $e) {
        error_log("EduQuest activity query failed: " . $e->getMessage());
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| 1. ENSURE NOTIFICATIONS TABLE EXISTS
|--------------------------------------------------------------------------
*/

try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT(10) UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            notification_type VARCHAR(100) DEFAULT 'announcement',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            audience VARCHAR(100) DEFAULT 'All students'
        ) ENGINE=InnoDB
    ");
} catch (mysqli_sql_exception $e) {
    error_log("Notifications table setup failed: " . $e->getMessage());
    $error = "Unable to access the notifications table. Please check your database.";
}

/*
|--------------------------------------------------------------------------
| 2. HANDLE CREATE NOTIFICATION FORM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "add_notification"
) {
    $title = trim($_POST["title"] ?? "");
    $message = trim($_POST["message"] ?? "");
    $audience = trim($_POST["audience"] ?? "All students");
    $sendOption = trim($_POST["send_option"] ?? "Save as draft");

    $allowedAudiences = [
        "All students",
        "Students in a selected course"
    ];

    if (!in_array($audience, $allowedAudiences, true)) {
        $audience = "All students";
    }

    $notificationType = (
        $sendOption === "Send now"
    ) ? "announcement" : "draft";


    $adminUserId = $_SESSION["user_id"] ?? null;

    if ($title === "" || $message === "") {
        $error = "Please provide both a notification title and message.";
    } else {
        try {
           
            if ($adminUserId !== null) {
                $checkUser = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE user_id = ?
                    LIMIT 1
                ");

                $checkUser->bind_param("i", $adminUserId);
                $checkUser->execute();
                $userResult = $checkUser->get_result();

                if ($userResult->num_rows === 0) {
                    $adminUserId = null;
                }

                $checkUser->close();
            }

            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id,
                    title,
                    message,
                    notification_type,
                    is_read,
                    audience,
                    created_at
                )
                VALUES (?, ?, ?, ?, 0, ?, NOW())
            ");

            $stmt->bind_param(
                "issss",
                $adminUserId,
                $title,
                $message,
                $notificationType,
                $audience
            );

            $stmt->execute();
            $stmt->close();

            if ($notificationType === "announcement") {
                $success = "Announcement saved successfully!";
            } else {
                $success = "Notification draft saved successfully!";
            }

        } catch (mysqli_sql_exception $e) {
            error_log("Notification insert failed: " . $e->getMessage());
            $error = "Unable to save the notification. Please check your database configuration.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| 3. FETCH NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notificationsRes = adminActivitySafeQuery(
    $conn,
    "
        SELECT
            notification_id,
            user_id,
            title,
            message,
            notification_type,
            is_read,
            audience,
            created_at
        FROM notifications
        ORDER BY notification_id DESC
    "
);

if ($notificationsRes) {
    while ($notification = $notificationsRes->fetch_assoc()) {
        $notifications[] = $notification;
    }
}

/*
|--------------------------------------------------------------------------
| 4. FETCH RECENT STUDENT REGISTRATIONS
|--------------------------------------------------------------------------
*/

$regRes = adminActivitySafeQuery(
    $conn,
    "
        SELECT full_name, created_at
        FROM users
        WHERE role = 'student'
        ORDER BY created_at DESC
        LIMIT 10
    "
);

if ($regRes) {
    while ($r = $regRes->fetch_assoc()) {
        $activities[] = [
            "time" => $r["created_at"],
            "admin" => "System / Student",
            "action" => "Registered",
            "item" => $r["full_name"],
            "details" => "New student account created"
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 5. FETCH CHALLENGE SUBMISSIONS
|--------------------------------------------------------------------------
*/

$chalRes = adminActivitySafeQuery(
    $conn,
    "
        SELECT
            u.full_name,
            ch.challenge_title,
            ca.score,
            ca.max_score,
            ca.submitted_at
        FROM challenge_attempts ca
        INNER JOIN users u
            ON ca.user_id = u.user_id
        INNER JOIN challenges ch
            ON ca.challenge_id = ch.challenge_id
        WHERE ca.submitted_at IS NOT NULL
        ORDER BY ca.submitted_at DESC
        LIMIT 10
    "
);

if ($chalRes) {
    while ($c = $chalRes->fetch_assoc()) {
        $activities[] = [
            "time" => $c["submitted_at"],
            "admin" => $c["full_name"],
            "action" => "Submitted",
            "item" => $c["challenge_title"],
            "details" => "Score: "
                . floatval($c["score"])
                . " / "
                . floatval($c["max_score"])
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 6. FETCH COMPLETED MISSIONS
|--------------------------------------------------------------------------
*/

$misRes = adminActivitySafeQuery(
    $conn,
    "
        SELECT
            u.full_name,
            m.mission_title,
            ump.completed_at
        FROM user_mission_progress ump
        INNER JOIN users u
            ON ump.user_id = u.user_id
        INNER JOIN missions m
            ON ump.mission_id = m.mission_id
        WHERE ump.status = 'completed'
          AND ump.completed_at IS NOT NULL
        ORDER BY ump.completed_at DESC
        LIMIT 10
    "
);

if ($misRes) {
    while ($m = $misRes->fetch_assoc()) {
        $activities[] = [
            "time" => $m["completed_at"],
            "admin" => $m["full_name"],
            "action" => "Completed",
            "item" => $m["mission_title"],
            "details" => "Mission successfully completed"
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 7. SORT ACTIVITY LOG BY DATE
|--------------------------------------------------------------------------
*/

usort($activities, function ($a, $b) {
    return strtotime($b["time"]) <=> strtotime($a["time"]);
});

$activities = array_slice($activities, 0, 15);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications &amp; Activity | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">

    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Notifications & Activity">

    <main class="page-content">

        <!-- PAGE HEADING -->
        <section class="page-heading">
            <div>
                <p class="eyebrow">PLATFORM UPDATES</p>

                <h1>Notifications &amp; Activity</h1>

                <p class="muted">
                    Send announcements and review actions made in the portal.
                </p>
            </div>
        </section>

        <!-- ERROR MESSAGE -->
        <?php if ($error !== ""): ?>
            <div
                class="error-message"
                role="alert"
                style="
                    margin-bottom:20px;
                    padding:12px;
                    background:#FFF0F0;
                    color:#B42318;
                    border:1px solid #F5C2C0;
                    border-radius:7px;
                    font-size:13px;
                "
            >
                <?= adminActivityEscape($error) ?>
            </div>
        <?php endif; ?>

        <!-- SUCCESS MESSAGE -->
        <?php if ($success !== ""): ?>
            <div
                class="success-message"
                role="status"
                style="
                    margin-bottom:20px;
                    padding:12px;
                    background:#F0FFF0;
                    color:#188423;
                    border:1px solid #C2F5C0;
                    border-radius:7px;
                    font-size:13px;
                "
            >
                <?= adminActivityEscape($success) ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs" role="tablist">

            <button
                type="button"
                class="tab active"
                id="notificationsTab"
                data-tab="notificationsPanel"
                role="tab"
                aria-selected="true"
                aria-controls="notificationsPanel"
            >
                Notifications
            </button>

            <button
                type="button"
                class="tab"
                id="activityTab"
                data-tab="activityPanel"
                role="tab"
                aria-selected="false"
                aria-controls="activityPanel"
            >
                Activity Log
            </button>

        </div>

        <!-- NOTIFICATIONS PANEL -->
        <section
            class="tab-panel active"
            id="notificationsPanel"
            role="tabpanel"
            aria-labelledby="notificationsTab"
        >

            <div class="page-heading split-heading">

                <div>
                    <h2>Notifications</h2>

                    <p class="muted">
                        Create announcements for students.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-modal="notificationModal"
                >
                    ＋ Create Notification
                </button>

            </div>

            <section class="panel table-panel">

                <div class="table-wrap" style="overflow-x:auto;">

                    <table>

                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Date Created</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($notifications)): ?>

                            <?php foreach ($notifications as $n): ?>

                                <?php
                                $type = strtolower(
                                    $n["notification_type"] ?? "announcement"
                                );

                                $typeClass = (
                                    $type === "draft"
                                ) ? "draft" : "published";

                                $messagePreview = (string)$n["message"];

                                if (strlen($messagePreview) > 50) {
                                    $messagePreview = substr(
                                        $messagePreview,
                                        0,
                                        50
                                    ) . "...";
                                }
                                ?>

                                <tr>

                                    <td>
                                        <b>
                                            <?= adminActivityEscape($n["title"]) ?>
                                        </b>

                                        <small>
                                            <?= adminActivityEscape($messagePreview) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= adminActivityEscape($n["audience"]) ?>
                                    </td>

                                    <td>
                                        <?php
                                        $createdTimestamp = strtotime(
                                            $n["created_at"]
                                        );
                                        ?>

                                        <?= $createdTimestamp
                                            ? date("M j, Y", $createdTimestamp)
                                            : "—"
                                        ?>
                                    </td>

                                    <td>
                                        <span class="status <?= $typeClass ?>">
                                            <?= adminActivityEscape(
                                                ucfirst($type)
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline"
                                            data-view-notification="<?= (int)$n["notification_id"] ?>"
                                        >
                                            View
                                        </button>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td
                                    colspan="5"
                                    style="
                                        text-align:center;
                                        color:#777;
                                        padding:30px;
                                    "
                                >
                                    No notifications created yet.
                                    Click "Create Notification" to add one.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </section>

        <!-- ACTIVITY LOG PANEL -->
        <section
            class="tab-panel"
            id="activityPanel"
            role="tabpanel"
            aria-labelledby="activityTab"
            hidden
        >

            <section class="panel table-panel">

                <div class="panel-heading">

                    <div>
                        <h2>Activity Log</h2>

                        <p class="muted">
                            Recent student and platform actions.
                        </p>
                    </div>

                    <label class="search-box">
                        ⌕
                        <input
                            type="search"
                            placeholder="Search activity..."
                            data-filter="activityTable"
                            aria-label="Search activity log"
                        >
                    </label>

                </div>

                <div class="table-wrap" style="overflow-x:auto;">

                    <table id="activityTable">

                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>User / Actor</th>
                                <th>Action</th>
                                <th>Item</th>
                                <th>Details</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($activities)): ?>

                            <?php foreach ($activities as $act): ?>

                                <?php
                                $activityTimestamp = strtotime($act["time"]);
                                ?>

                                <tr>

                                    <td>
                                        <?= $activityTimestamp
                                            ? date(
                                                "M j, Y · g:i A",
                                                $activityTimestamp
                                            )
                                            : "—"
                                        ?>
                                    </td>

                                    <td>
                                        <b>
                                            <?= adminActivityEscape($act["admin"]) ?>
                                        </b>
                                    </td>

                                    <td>
                                        <?= adminActivityEscape($act["action"]) ?>
                                    </td>

                                    <td>
                                        <?= adminActivityEscape($act["item"]) ?>
                                    </td>

                                    <td>
                                        <?= adminActivityEscape($act["details"]) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td
                                    colspan="5"
                                    style="
                                        text-align:center;
                                        color:#777;
                                        padding:30px;
                                    "
                                >
                                    No recent activity recorded in the database.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </section>

        <!-- CREATE NOTIFICATION MODAL -->
        <dialog class="modal" id="notificationModal">

            <form method="POST" action="" class="modal-card">

                <input
                    type="hidden"
                    name="action"
                    value="add_notification"
                >

                <div class="modal-heading">

                    <h2>Create Notification</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        aria-label="Close"
                        data-close-modal
                    >
                        ×
                    </button>

                </div>

                <label>
                    Notification Title

                    <input
                        type="text"
                        name="title"
                        maxlength="255"
                        required
                        placeholder="Enter title"
                    >
                </label>

                <label>
                    Message

                    <textarea
                        name="message"
                        rows="4"
                        required
                        placeholder="Write your announcement"
                    ></textarea>
                </label>

                <label>
                    Audience

                    <select name="audience">
                        <option value="All students">
                            All students
                        </option>

                        <option value="Students in a selected course">
                            Students in a selected course
                        </option>
                    </select>
                </label>

                <label>
                    Send option

                    <select name="send_option">
                        <option value="Save as draft">
                            Save as draft
                        </option>

                        <option value="Send now">
                            Send now
                        </option>
                    </select>
                </label>

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        data-close-modal
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Notification
                    </button>

                </div>

            </form>

        </dialog>

        <!-- VIEW NOTIFICATION MODAL -->
        <dialog class="modal" id="viewNotificationModal">

            <div class="modal-card">

                <div class="modal-heading">

                    <h2>Notification Details</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        aria-label="Close"
                        data-close-modal
                    >
                        ×
                    </button>

                </div>

                <h3 id="viewNotificationTitle"></h3>

                <p
                    id="viewNotificationMessage"
                    style="white-space:pre-wrap; overflow-wrap:anywhere;"
                ></p>

                <p class="muted" id="viewNotificationAudience"></p>

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        data-close-modal
                    >
                        Close
                    </button>

                </div>

            </div>

        </dialog>

        <footer class="page-footer">
            © 2026 EduQuest · Admin Portal
        </footer>

    </main>

</div>

<div
    class="toast"
    id="toast"
    role="status"
    aria-live="polite"
></div>

<!-- PAGE INTERACTIONS -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | TAB SWITCHING
    |--------------------------------------------------------------------------
    */

    const tabs = document.querySelectorAll(".tabs [data-tab]");
    const panels = document.querySelectorAll(".tab-panel");

    tabs.forEach(function (tab) {
        tab.addEventListener("click", function () {

            const targetId = tab.dataset.tab;

            tabs.forEach(function (item) {
                item.classList.remove("active");
                item.setAttribute("aria-selected", "false");
            });

            panels.forEach(function (panel) {
                panel.classList.remove("active");
                panel.hidden = true;
            });

            tab.classList.add("active");
            tab.setAttribute("aria-selected", "true");

            const targetPanel = document.getElementById(targetId);

            if (targetPanel) {
                targetPanel.classList.add("active");
                targetPanel.hidden = false;
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE NOTIFICATION MODAL
    |--------------------------------------------------------------------------
    */

    const createModal = document.getElementById("notificationModal");

    document.querySelectorAll('[data-modal="notificationModal"]')
        .forEach(function (button) {
            button.addEventListener("click", function () {
                if (createModal) {
                    createModal.showModal();
                }
            });
        });

    /*
    |--------------------------------------------------------------------------
    | CLOSE MODALS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-close-modal]")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                const dialog = button.closest("dialog");

                if (dialog) {
                    dialog.close();
                }
            });
        });

    /*
    |--------------------------------------------------------------------------
    | SEARCH ACTIVITY TABLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-filter]").forEach(function (input) {

        input.addEventListener("input", function () {

            const table = document.getElementById(input.dataset.filter);

            if (!table) {
                return;
            }

            const searchText = input.value.toLowerCase().trim();
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(function (row) {
                const matches = row.textContent
                    .toLowerCase()
                    .includes(searchText);

                row.style.display = matches ? "" : "none";
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | VIEW NOTIFICATION DETAILS
    |--------------------------------------------------------------------------
    */

    const viewModal = document.getElementById("viewNotificationModal");
    const notificationData = <?= json_encode(
        array_map(function ($n) {
            return [
                "id" => (int)$n["notification_id"],
                "title" => $n["title"],
                "message" => $n["message"],
                "audience" => $n["audience"],
                "type" => $n["notification_type"]
            ];
        }, $notifications),
        JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    ) ?>;

    document.querySelectorAll("[data-view-notification]")
        .forEach(function (button) {

            button.addEventListener("click", function () {

                const id = Number(button.dataset.viewNotification);

                const notification = notificationData.find(function (item) {
                    return item.id === id;
                });

                if (!notification || !viewModal) {
                    return;
                }

                document.getElementById("viewNotificationTitle")
                    .textContent = notification.title;

                document.getElementById("viewNotificationMessage")
                    .textContent = notification.message;

                document.getElementById("viewNotificationAudience")
                    .textContent =
                        "Audience: " + notification.audience
                        + " | Type: " + notification.type;

                viewModal.showModal();
            });
        });

});
</script>

</body>
</html>
