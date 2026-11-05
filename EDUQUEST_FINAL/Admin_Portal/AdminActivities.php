
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";

/* CSRF TOKEN */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

/* ESCAPE OUTPUT */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/* CHECK IF MISSION EXISTS */
function validMission($conn, $missionId) {
    $stmt = $conn->prepare("
        SELECT mission_id
        FROM missions
        WHERE mission_id = ?
    ");

    $stmt->bind_param("i", $missionId);
    $stmt->execute();

    $result = $stmt->get_result();
    $valid = $result->num_rows > 0;

    $stmt->close();

    return $valid;
}

/* HANDLE FORM SUBMISSIONS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $postedToken)) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        /* =====================================
           ADD ACTIVITY
        ===================================== */
        if ($action === "add_activity") {

            $mission_id = (int) ($_POST["mission_id"] ?? 0);
            $activity_title = trim($_POST["activity_title"] ?? "");
            $activity_description = trim($_POST["activity_description"] ?? "");
            $instructions = trim($_POST["instructions"] ?? "");
            $activity_type = trim($_POST["activity_type"] ?? "lesson");
            $status = $_POST["status"] ?? "draft";

            $allowedTypes = [
                "Multiple Choice",
                "Matching",
                "Practical Task",
                "lesson"
            ];

            if (
                $mission_id > 0 &&
                $activity_title !== "" &&
                validMission($conn, $mission_id) &&
                in_array($activity_type, $allowedTypes, true) &&
                in_array($status, ["draft", "published"], true)
            ) {

                /* GET NEXT ACTIVITY ORDER */
                $ordStmt = $conn->prepare("
                    SELECT COALESCE(MAX(activity_order), 0) + 1 AS next_order
                    FROM activities
                    WHERE mission_id = ?
                ");

                $ordStmt->bind_param("i", $mission_id);
                $ordStmt->execute();

                $activity_order = (int) $ordStmt->get_result()->fetch_assoc()["next_order"];

                $ordStmt->close();

              $stmt = $conn->prepare("
                    INSERT INTO activities
                    (
                        mission_id,
                        activity_title,
                        activity_description,
                        instructions,
                        activity_type,
                        activity_order,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "issssis",
                    $mission_id,
                    $activity_title,
                    $activity_description,
                    $instructions,
                    $activity_type,
                    $activity_order,
                    $status
                );

                if ($stmt->execute()) {
                    $success = "Activity added successfully!";
                } else {
                    $error = "Failed to add activity: " . $stmt->error;
                }

                $stmt->close();

            } else {
                $error = "Please provide valid activity information.";
            }
        }

        /* =====================================
           EDIT ACTIVITY
        ===================================== */
        elseif ($action === "edit_activity") {

            $activity_id = (int) ($_POST["activity_id"] ?? 0);
            $mission_id = (int) ($_POST["mission_id"] ?? 0);
            $activity_title = trim($_POST["activity_title"] ?? "");
            $activity_description = trim($_POST["activity_description"] ?? "");
            $instructions = trim($_POST["instructions"] ?? "");
            $activity_type = trim($_POST["activity_type"] ?? "lesson");
            $status = $_POST["status"] ?? "draft";

            $allowedTypes = [
                "Multiple Choice",
                "Matching",
                "Practical Task",
                "lesson"
            ];

            if (
                $activity_id > 0 &&
                $mission_id > 0 &&
                $activity_title !== "" &&
                validMission($conn, $mission_id) &&
                in_array($activity_type, $allowedTypes, true) &&
                in_array($status, ["draft", "published"], true)
            ) {

                $stmt = $conn->prepare("
                    UPDATE activities
                    SET
                        mission_id = ?,
                        activity_title = ?,
                        activity_description = ?,
                        instructions = ?,
                        activity_type = ?,
                        status = ?
                    WHERE activity_id = ?
                ");

                $stmt->bind_param(
                    "isssssi",
                    $mission_id,
                    $activity_title,
                    $activity_description,
                    $instructions,
                    $activity_type,
                    $status,
                    $activity_id
                );

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {
                        $success = "Activity updated successfully!";
                    } else {
                        $check = $conn->prepare("
                            SELECT activity_id
                            FROM activities
                            WHERE activity_id = ?
                        ");

                        $check->bind_param("i", $activity_id);
                        $check->execute();

                        if ($check->get_result()->num_rows > 0) {
                            $success = "No changes were made to the activity.";
                        } else {
                            $error = "Activity not found.";
                        }

                        $check->close();
                    }

                } else {
                    $error = "Failed to update activity: " . $stmt->error;
                }

                $stmt->close();

            } else {
                $error = "Please check the activity information and try again.";
            }
        }

        /* =====================================
           DELETE ACTIVITY
        ===================================== */
        elseif ($action === "delete_activity") {

            $activity_id = (int) ($_POST["activity_id"] ?? 0);

            if ($activity_id > 0) {

                $stmt = $conn->prepare("
                    DELETE FROM activities
                    WHERE activity_id = ?
                ");

                $stmt->bind_param("i", $activity_id);

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {
                        $success = "Activity deleted successfully!";
                    } else {
                        $error = "Activity not found.";
                    }

                } else {
                    $error = "This activity cannot be deleted because it may have related student records. Please check its dependent records first.";
                }

                $stmt->close();

            } else {
                $error = "Invalid activity selected.";
            }
        }
    }
}

/* =====================================
   FETCH FILTER
===================================== */
$selectedType = $_GET["activity_type"] ?? "all";

$allowedFilters = [
    "all",
    "Multiple Choice",
    "Matching",
    "Practical Task",
    "lesson"
];

if (!in_array($selectedType, $allowedFilters, true)) {
    $selectedType = "all";
}

/* =====================================
   FETCH MISSIONS FOR DROPDOWNS
===================================== */
$missionsList = [];

$missionsRes = $conn->query("
    SELECT
        m.mission_id,
        m.mission_title,
        modTable.module_title,
        modTable.module_number,
        c.course_title
    FROM missions m
    INNER JOIN modules modTable
        ON m.module_id = modTable.module_id
    INNER JOIN courses c
        ON modTable.course_id = c.course_id
    ORDER BY
        c.course_id ASC,
        modTable.module_number ASC,
        m.mission_order ASC
");

if ($missionsRes) {
    while ($mRow = $missionsRes->fetch_assoc()) {
        $missionsList[] = $mRow;
    }
}

/* =====================================
   FETCH ACTIVITIES
===================================== */
$sql = "
    SELECT
        a.*,
        m.mission_title,
        modTable.module_title,
        c.course_title
    FROM activities a
    INNER JOIN missions m
        ON a.mission_id = m.mission_id
    INNER JOIN modules modTable
        ON m.module_id = modTable.module_id
    INNER JOIN courses c
        ON modTable.course_id = c.course_id
";

$params = [];
$types = "";

if ($selectedType !== "all") {
    $sql .= " WHERE a.activity_type = ?";
    $params[] = $selectedType;
    $types .= "s";
}

$sql .= " ORDER BY a.activity_id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$activitiesResult = $stmt->get_result();

$activities = [];

while ($row = $activitiesResult->fetch_assoc()) {
    $activities[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activities | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Activities">

    <main class="page-content">

        <!-- PAGE HEADING -->
        <section class="page-heading split-heading">

            <div>
                <p class="eyebrow">LEARNING TASKS</p>
                <h1>Activities</h1>
                <p class="muted">Create and assign activities to missions.</p>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                data-modal="activityModal">
                ＋ Add Activity
            </button>

        </section>

        <!-- MESSAGES -->
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

        <!-- FILTER TOOLBAR -->
        <form method="GET" action="" class="toolbar"
            style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">

            <label class="search-box">
                ⌕
                <input
                    type="search"
                    placeholder="Search activities..."
                    data-filter="activitiesTable">
            </label>

            <select name="activity_type" onchange="this.form.submit()">

                <option value="all" <?= $selectedType === "all" ? "selected" : "" ?>>
                    All activity types
                </option>

                <option value="Multiple Choice" <?= $selectedType === "Multiple Choice" ? "selected" : "" ?>>
                    Multiple Choice
                </option>

                <option value="Matching" <?= $selectedType === "Matching" ? "selected" : "" ?>>
                    Matching
                </option>

                <option value="Practical Task" <?= $selectedType === "Practical Task" ? "selected" : "" ?>>
                    Practical Task
                </option>

                <option value="lesson" <?= $selectedType === "lesson" ? "selected" : "" ?>>
                    Lesson
                </option>

            </select>

        </form>

        <!-- ACTIVITIES TABLE -->
        <section class="panel table-panel">

            <div class="table-wrap" style="overflow-x:auto;">

                <table id="activitiesTable">

                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Type</th>
                            <th>Course / Mission</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($activities)): ?>

                        <?php foreach ($activities as $activity): ?>

                            <tr>

                                <td>
                                    <b><?= e($activity["activity_title"]) ?></b>

                                    <small>
                                        <?= e($activity["activity_description"] ?? "No description") ?>
                                    </small>
                                </td>

                                <td>
                                    <?= e($activity["activity_type"]) ?>
                                </td>

                                <td>
                                    <?= e($activity["course_title"]) ?>
                                    /
                                    <?= e($activity["mission_title"]) ?>
                                </td>

                                <td>
                                    <span class="status <?= e(strtolower($activity["status"])) ?>">
                                        <?= e(ucfirst($activity["status"])) ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="table-actions">

                                        <!-- EDIT BUTTON -->
                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline edit-activity-btn"

                                            data-id="<?= (int) $activity["activity_id"] ?>"
                                            data-mission="<?= (int) $activity["mission_id"] ?>"
                                            data-title="<?= e($activity["activity_title"]) ?>"
                                            data-description="<?= e($activity["activity_description"] ?? "") ?>"
                                            data-instructions="<?= e($activity["instructions"] ?? "") ?>"
                                            data-type="<?= e($activity["activity_type"]) ?>"
                                            data-status="<?= e($activity["status"]) ?>">

                                            Edit

                                        </button>

                                        <!-- PREVIEW BUTTON -->
                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline preview-activity-btn"

                                            data-title="<?= e($activity["activity_title"]) ?>"
                                            data-description="<?= e($activity["activity_description"] ?? "") ?>"
                                            data-content="<?= e($activity["activity_content"] ?? "") ?>"
                                            data-type="<?= e($activity["activity_type"]) ?>"
                                            data-course="<?= e($activity["course_title"]) ?>"
                                            data-mission="<?= e($activity["mission_title"]) ?>"
                                            data-status="<?= e($activity["status"]) ?>">

                                            Preview

                                        </button>

                                        <!-- DELETE BUTTON -->
                                        <form
                                            method="POST"
                                            action=""
                                            onsubmit="return confirm('Are you sure you want to delete this activity? This action cannot be undone.');">

                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_activity">

                                            <input
                                                type="hidden"
                                                name="activity_id"
                                                value="<?= (int) $activity["activity_id"] ?>">

                                            <button type="submit" class="btn btn-small delete-btn">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" style="text-align:center;color:#777;padding:30px;">
                                No activities found. Click "Add Activity" to create one.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <!-- ADD ACTIVITY MODAL -->
        <dialog class="modal" id="activityModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_activity">

                <div class="modal-heading">
                    <h2>Add Activity</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Activity Title
                    <input
                        type="text"
                        name="activity_title"
                        required
                        placeholder="Enter activity title">
                </label>

                <label>
                    Related Mission

                    <select name="mission_id" required>

                        <option value="">Select a mission</option>

                        <?php foreach ($missionsList as $m): ?>

                            <option value="<?= (int) $m["mission_id"] ?>">

                                <?= e($m["course_title"]) ?>
                                →
                                Module <?= (int) $m["module_number"] ?>:
                                <?= e($m["mission_title"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Activity Type

                    <select name="activity_type">
                        <option value="Multiple Choice">Multiple Choice</option>
                        <option value="Matching">Matching</option>
                        <option value="Practical Task">Practical Task</option>
                        <option value="lesson">Lesson</option>
                    </select>
                </label>

                <label>
                    Instructions / Short Description
                    <textarea name="activity_description" rows="3"
                        placeholder="Explain the task"></textarea>
                </label>

                <label>
                    Activity Content
                    <textarea name="instructions" rows="4"></textarea>
                        placeholder="Enter activity content or learning material"></textarea>
                </label>

                <label>
                    Status

                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
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
                        Save Activity
                    </button>

                </div>

            </form>

        </dialog>

        <!-- EDIT ACTIVITY MODAL -->
        <dialog class="modal" id="editActivityModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit_activity">
                <input type="hidden" name="activity_id" id="edit_activity_id">

                <div class="modal-heading">
                    <h2>Edit Activity</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Activity Title
                    <input type="text" name="activity_title" id="edit_activity_title" required>
                </label>

                <label>
                    Related Mission

                    <select name="mission_id" id="edit_mission_id" required>

                        <?php foreach ($missionsList as $m): ?>

                            <option value="<?= (int) $m["mission_id"] ?>">

                                <?= e($m["course_title"]) ?>
                                →
                                Module <?= (int) $m["module_number"] ?>:
                                <?= e($m["mission_title"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Activity Type

                    <select name="activity_type" id="edit_activity_type">
                        <option value="Multiple Choice">Multiple Choice</option>
                        <option value="Matching">Matching</option>
                        <option value="Practical Task">Practical Task</option>
                        <option value="lesson">Lesson</option>
                    </select>
                </label>

                <label>
                    Instructions / Short Description
                    <textarea name="activity_description" id="edit_activity_description" rows="3"></textarea>
                </label>

                <label>
                    Activity Content
                    <textarea name="activity_content" id="edit_activity_content" rows="4"></textarea>
                </label>

                <label>
                    Status

                    <select name="status" id="edit_activity_status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
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

        <!-- PREVIEW ACTIVITY MODAL -->
        <dialog class="modal" id="previewActivityModal">

            <div class="modal-card">

                <div class="modal-heading">
                    <h2>Activity Preview</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <div class="activity-preview">

                    <p class="eyebrow" id="preview_course"></p>

                    <h2 id="preview_title"></h2>

                    <p>
                        <strong>Mission:</strong>
                        <span id="preview_mission"></span>
                    </p>

                    <p>
                        <strong>Activity Type:</strong>
                        <span id="preview_type"></span>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <span id="preview_status"></span>
                    </p>

                    <hr>

                    <h3>Instructions</h3>
                    <p id="preview_description"></p>

                    <h3>Activity Content</h3>
                    <div id="preview_content" class="preview-content"></div>

                </div>

                <div class="form-actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="this.closest('dialog').close()">
                        Close Preview
                    </button>
                </div>

            </div>

        </dialog>

        <footer class="page-footer">
            © 2026 EduQuest · Admin Portal
        </footer>

    </main>

</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<style>
    .table-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .table-actions form {
        margin: 0;
    }

    .delete-btn {
        background: #fff0f0;
        color: #b42318;
        border: 1px solid #f2c4c4;
        cursor: pointer;
    }

    .delete-btn:hover {
        background: #b42318;
        color: #fff;
    }

    .activity-preview {
        line-height: 1.7;
        padding: 10px 0;
    }

    .activity-preview h2 {
        margin: 8px 0 15px;
    }

    .activity-preview h3 {
        margin: 18px 0 6px;
    }

    .activity-preview p,
    .preview-content {
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .preview-content {
        padding: 12px;
        background: #f5f5f2;
        border-radius: 7px;
        color: #333;
        min-height: 60px;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* EDIT ACTIVITY */
    document.querySelectorAll(".edit-activity-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("edit_activity_id").value = this.dataset.id;
            document.getElementById("edit_mission_id").value = this.dataset.mission;
            document.getElementById("edit_activity_title").value = this.dataset.title;
            document.getElementById("edit_activity_description").value = this.dataset.description;
            document.getElementById("edit_activity_instructions").value = this.dataset.instructions;
            document.getElementById("edit_activity_type").value = this.dataset.type;
            document.getElementById("edit_activity_status").value = this.dataset.status;

            document.getElementById("editActivityModal").showModal();

        });

    });

    /* PREVIEW ACTIVITY */
    document.querySelectorAll(".preview-activity-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("preview_title").textContent = this.dataset.title;

            document.getElementById("preview_course").textContent =
                this.dataset.course;

            document.getElementById("preview_mission").textContent =
                this.dataset.mission;

            document.getElementById("preview_type").textContent =
                this.dataset.type;

            document.getElementById("preview_status").textContent =
                this.dataset.status;

            document.getElementById("preview_description").textContent =
                this.dataset.description || "No instructions provided.";

            document.getElementById("preview_content").textContent =
                this.dataset.instructions || "No instructions provided.";

            document.getElementById("previewActivityModal").showModal();

        });

    });

    /* SEARCH ACTIVITIES */
    const searchInput = document.querySelector('[data-filter="activitiesTable"]');

    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll("#activitiesTable tbody tr");

            rows.forEach(function (row) {

                row.style.display = row.textContent.toLowerCase().includes(searchValue)
                    ? ""
                    : "none";

            });

        });

    }

});
</script>

</body>
</html>
