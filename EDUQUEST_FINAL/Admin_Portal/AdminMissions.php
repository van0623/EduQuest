
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/* CHECK THAT THE SELECTED MODULE EXISTS */
function validModule($conn, $moduleId) {
    $stmt = $conn->prepare("SELECT module_id FROM modules WHERE module_id = ?");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $valid = $result->num_rows > 0;
    $stmt->close();

    return $valid;
}

/* HANDLE ADD, EDIT AND DELETE */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $postedToken)) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        /* ADD MISSION */
        if ($action === "add_mission") {
            $module_id = (int) ($_POST["module_id"] ?? 0);
            $mission_number = (int) ($_POST["mission_number"] ?? 1);
            $mission_title = trim($_POST["mission_title"] ?? "");
            $mission_description = trim($_POST["mission_description"] ?? "");
            $mission_type = trim($_POST["mission_type"] ?? "Learning Mission");
            $mission_order = (int) ($_POST["mission_order"] ?? 1);
            $status = $_POST["status"] ?? "draft";

            if (
                $module_id > 0 &&
                $mission_number > 0 &&
                $mission_order > 0 &&
                $mission_title !== "" &&
                validModule($conn, $module_id) &&
                in_array($status, ["draft", "published"], true)
            ) {
                $stmt = $conn->prepare("
                    INSERT INTO missions
                    (module_id, mission_number, mission_title, mission_description,
                     mission_type, mission_order, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iisssis",
                    $module_id,
                    $mission_number,
                    $mission_title,
                    $mission_description,
                    $mission_type,
                    $mission_order,
                    $status
                );

                if ($stmt->execute()) {
                    $success = "Mission added successfully!";
                } else {
                    $error = "Failed to add mission: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error = "Please complete all required fields with valid information.";
            }
        }

        /* UPDATE MISSION */
        elseif ($action === "edit_mission") {
            $mission_id = (int) ($_POST["mission_id"] ?? 0);
            $module_id = (int) ($_POST["module_id"] ?? 0);
            $mission_number = (int) ($_POST["mission_number"] ?? 1);
            $mission_title = trim($_POST["mission_title"] ?? "");
            $mission_description = trim($_POST["mission_description"] ?? "");
            $mission_type = trim($_POST["mission_type"] ?? "Learning Mission");
            $mission_order = (int) ($_POST["mission_order"] ?? 1);
            $status = $_POST["status"] ?? "draft";

            if (
                $mission_id > 0 &&
                $module_id > 0 &&
                $mission_number > 0 &&
                $mission_order > 0 &&
                $mission_title !== "" &&
                validModule($conn, $module_id) &&
                in_array($status, ["draft", "published"], true)
            ) {
                $stmt = $conn->prepare("
                    UPDATE missions
                    SET module_id = ?,
                        mission_number = ?,
                        mission_title = ?,
                        mission_description = ?,
                        mission_type = ?,
                        mission_order = ?,
                        status = ?
                    WHERE mission_id = ?
                ");

                $stmt->bind_param(
                    "iisssisi",
                    $module_id,
                    $mission_number,
                    $mission_title,
                    $mission_description,
                    $mission_type,
                    $mission_order,
                    $status,
                    $mission_id
                );

                if ($stmt->execute()) {
                    $success = "Mission updated successfully!";
                } else {
                    $error = "Failed to update mission: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error = "Please check the mission details and try again.";
            }
        }

        /* DELETE MISSION */
        elseif ($action === "delete_mission") {
            $mission_id = (int) ($_POST["mission_id"] ?? 0);

            if ($mission_id > 0) {
                $stmt = $conn->prepare("DELETE FROM missions WHERE mission_id = ?");
                $stmt->bind_param("i", $mission_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success = "Mission deleted successfully!";
                    } else {
                        $error = "Mission not found.";
                    }
                } else {
                    $error = "This mission cannot be deleted because it may have related records. " .
                             "Please check its dependent records first.";
                }

                $stmt->close();
            } else {
                $error = "Invalid mission selected.";
            }
        }
    }
}

/* FETCH COURSES */
$courses = [];
$coursesRes = $conn->query("
    SELECT course_id, course_title
    FROM courses
    ORDER BY course_number ASC
");

if ($coursesRes) {
    while ($row = $coursesRes->fetch_assoc()) {
        $courses[] = $row;
    }
}

$selectedCourseId = isset($_GET["course_id"])
    ? (int) $_GET["course_id"]
    : (!empty($courses) ? (int) $courses[0]["course_id"] : 0);

$selectedModuleId = isset($_GET["module_id"])
    ? (int) $_GET["module_id"]
    : 0;

$selectedStatus = $_GET["status"] ?? "all";

/* FETCH MODULES FOR SELECTED COURSE */
$modules = [];

if ($selectedCourseId > 0) {
    $modStmt = $conn->prepare("
        SELECT module_id, module_number, module_title
        FROM modules
        WHERE course_id = ?
        ORDER BY module_number ASC
    ");

    $modStmt->bind_param("i", $selectedCourseId);
    $modStmt->execute();

    $modRes = $modStmt->get_result();

    while ($m = $modRes->fetch_assoc()) {
        $modules[] = $m;
    }

    $modStmt->close();
}

/* BUILD MISSIONS QUERY */
$sql = "
    SELECT
        m.*,
        modTable.module_title,
        modTable.module_number,
        c.course_title
    FROM missions m
    INNER JOIN modules modTable
        ON m.module_id = modTable.module_id
    INNER JOIN courses c
        ON modTable.course_id = c.course_id
    WHERE c.course_id = ?
";

$params = [$selectedCourseId];
$types = "i";

if ($selectedModuleId > 0) {
    $sql .= " AND m.module_id = ?";
    $params[] = $selectedModuleId;
    $types .= "i";
}

if (in_array($selectedStatus, ["published", "draft"], true)) {
    $sql .= " AND m.status = ?";
    $params[] = $selectedStatus;
    $types .= "s";
}

$sql .= " ORDER BY m.mission_order ASC, m.mission_number ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$missionsResult = $stmt->get_result();

$missions = [];

while ($row = $missionsResult->fetch_assoc()) {
    $missions[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missions | EduQuest Admin</title>
    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>
<body>

<div id="admin-app" data-page="Missions">
    <main class="page-content">

        <section class="page-heading split-heading">
            <div>
                <p class="eyebrow">LEARNING FLOW</p>
                <h1>Missions</h1>
                <p class="muted">Connect lessons, activities, and challenges.</p>
            </div>

            <button class="btn btn-primary" type="button" data-modal="missionModal">
                ＋ Add Mission
            </button>
        </section>

        <?php if ($error !== ""): ?>
            <div class="error-message" style="margin-bottom:20px;padding:12px;background:#FFF0F0;color:#B42318;border:1px solid #F5C2C0;border-radius:7px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="success-message" style="margin-bottom:20px;padding:12px;background:#F0FFF0;color:#188423;border:1px solid #C2F5C0;border-radius:7px;">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <!-- FILTERS -->
        <form method="GET" action="" class="toolbar" style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">

            <label class="field-inline">
                Course
                <select name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c["course_id"] ?>"
                            <?= ((int) $c["course_id"] === $selectedCourseId) ? "selected" : "" ?>>
                            <?= e($c["course_title"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field-inline">
                Module
                <select name="module_id" onchange="this.form.submit()">
                    <option value="0">All modules</option>

                    <?php foreach ($modules as $m): ?>
                        <option value="<?= (int) $m["module_id"] ?>"
                            <?= ((int) $m["module_id"] === $selectedModuleId) ? "selected" : "" ?>>
                            Module <?= (int) $m["module_number"] ?>:
                            <?= e($m["module_title"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field-inline">
                Status
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?= $selectedStatus === "all" ? "selected" : "" ?>>All statuses</option>
                    <option value="published" <?= $selectedStatus === "published" ? "selected" : "" ?>>Published</option>
                    <option value="draft" <?= $selectedStatus === "draft" ? "selected" : "" ?>>Draft</option>
                </select>
            </label>
        </form>

        <!-- MISSIONS TABLE -->
        <section class="panel table-panel">
            <div class="table-wrap" style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Mission</th>
                            <th>Module</th>
                            <th>Mission Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!empty($missions)): ?>

                        <?php foreach ($missions as $mission): ?>
                            <tr>
                                <td><?= sprintf("%02d", (int) $mission["mission_order"]) ?></td>

                                <td>
                                    <b><?= e($mission["mission_title"]) ?></b>
                                    <small><?= e($mission["mission_description"] ?? "No description") ?></small>
                                </td>

                                <td>Module <?= (int) $mission["module_number"] ?></td>

                                <td><?= e($mission["mission_type"]) ?></td>

                                <td>
                                    <span class="status <?= e(strtolower($mission["status"])) ?>">
                                        <?= e(ucfirst($mission["status"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <!-- EDIT -->
                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline edit-mission-btn"
                                            data-id="<?= (int) $mission["mission_id"] ?>"
                                            data-module="<?= (int) $mission["module_id"] ?>"
                                            data-number="<?= (int) $mission["mission_number"] ?>"
                                            data-title="<?= e($mission["mission_title"]) ?>"
                                            data-description="<?= e($mission["mission_description"] ?? "") ?>"
                                            data-type="<?= e($mission["mission_type"]) ?>"
                                            data-order="<?= (int) $mission["mission_order"] ?>"
                                            data-status="<?= e($mission["status"]) ?>">
                                            Edit
                                        </button>

                                        <!-- PREVIEW -->
                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline preview-mission-btn"
                                            data-title="<?= e($mission["mission_title"]) ?>"
                                            data-description="<?= e($mission["mission_description"] ?? "") ?>"
                                            data-module="Module <?= (int) $mission["module_number"] ?>: <?= e($mission["module_title"]) ?>"
                                            data-course="<?= e($mission["course_title"]) ?>"
                                            data-number="<?= (int) $mission["mission_number"] ?>"
                                            data-type="<?= e($mission["mission_type"]) ?>"
                                            data-order="<?= (int) $mission["mission_order"] ?>"
                                            data-status="<?= e($mission["status"]) ?>">
                                            Preview
                                        </button>

                                        <!-- DELETE -->
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this mission? This action cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_mission">
                                            <input type="hidden" name="mission_id" value="<?= (int) $mission["mission_id"] ?>">
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
                            <td colspan="6" style="text-align:center;color:#777;padding:30px;">
                                No missions found for the selected filter.
                                Click "Add Mission" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ADD MISSION MODAL -->
        <dialog class="modal" id="missionModal">
            <form method="POST" action="" class="modal-card">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_mission">

                <div class="modal-heading">
                    <h2>Add Mission</h2>
                    <button type="button" class="icon-btn" onclick="this.closest('dialog').close()">×</button>
                </div>

                <label>
                    Mission Title
                    <input type="text" name="mission_title" required placeholder="Enter mission title">
                </label>

                <label>
                    Module
                    <select name="module_id" required>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= (int) $m["module_id"] ?>">
                                Module <?= (int) $m["module_number"] ?>:
                                <?= e($m["module_title"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Mission Number
                    <input type="number" name="mission_number" min="1" value="1" required>
                </label>

                <label>
                    Mission Order
                    <input type="number" name="mission_order" min="1" value="1" required>
                </label>

                <label>
                    Mission Type
                    <input type="text" name="mission_type" value="Learning Mission">
                </label>

                <label>
                    Mission Description
                    <textarea name="mission_description" rows="3"></textarea>
                </label>

                <label>
                    Status
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Mission</button>
                </div>
            </form>
        </dialog>

        <!-- EDIT MISSION MODAL -->
        <dialog class="modal" id="editMissionModal">
            <form method="POST" action="" class="modal-card">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit_mission">
                <input type="hidden" name="mission_id" id="edit_mission_id">

                <div class="modal-heading">
                    <h2>Edit Mission</h2>
                    <button type="button" class="icon-btn" onclick="this.closest('dialog').close()">×</button>
                </div>

                <label>
                    Mission Title
                    <input type="text" name="mission_title" id="edit_mission_title" required>
                </label>

                <label>
                    Module
                    <select name="module_id" id="edit_module_id" required>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= (int) $m["module_id"] ?>">
                                Module <?= (int) $m["module_number"] ?>:
                                <?= e($m["module_title"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Mission Number
                    <input type="number" name="mission_number" id="edit_mission_number" min="1" required>
                </label>

                <label>
                    Mission Order
                    <input type="number" name="mission_order" id="edit_mission_order" min="1" required>
                </label>

                <label>
                    Mission Type
                    <input type="text" name="mission_type" id="edit_mission_type" required>
                </label>

                <label>
                    Mission Description
                    <textarea name="mission_description" id="edit_mission_description" rows="3"></textarea>
                </label>

                <label>
                    Status
                    <select name="status" id="edit_mission_status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </dialog>

        <!-- PREVIEW MISSION MODAL -->
        <dialog class="modal" id="previewMissionModal">
            <div class="modal-card">
                <div class="modal-heading">
                    <h2>Mission Preview</h2>
                    <button type="button" class="icon-btn" onclick="this.closest('dialog').close()">×</button>
                </div>

                <div class="mission-preview">
                    <p class="eyebrow" id="preview_course"></p>
                    <h2 id="preview_title"></h2>
                    <p id="preview_description"></p>

                    <hr>

                    <p><strong>Module:</strong> <span id="preview_module"></span></p>
                    <p><strong>Mission Number:</strong> <span id="preview_number"></span></p>
                    <p><strong>Mission Type:</strong> <span id="preview_type"></span></p>
                    <p><strong>Mission Order:</strong> <span id="preview_order"></span></p>
                    <p><strong>Status:</strong> <span id="preview_status"></span></p>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="this.closest('dialog').close()">Close Preview</button>
                </div>
            </div>
        </dialog>

        <footer class="page-footer">© 2026 EduQuest · Admin Portal</footer>

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

    .mission-preview {
        line-height: 1.7;
        padding: 10px 0;
    }

    .mission-preview h2 {
        margin: 8px 0;
    }

    .mission-preview p {
        overflow-wrap: anywhere;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* EDIT BUTTON */
    document.querySelectorAll(".edit-mission-btn").forEach(function (button) {
        button.addEventListener("click", function () {
            document.getElementById("edit_mission_id").value = this.dataset.id;
            document.getElementById("edit_module_id").value = this.dataset.module;
            document.getElementById("edit_mission_number").value = this.dataset.number;
            document.getElementById("edit_mission_title").value = this.dataset.title;
            document.getElementById("edit_mission_description").value = this.dataset.description;
            document.getElementById("edit_mission_type").value = this.dataset.type;
            document.getElementById("edit_mission_order").value = this.dataset.order;
            document.getElementById("edit_mission_status").value = this.dataset.status;

            document.getElementById("editMissionModal").showModal();
        });
    });

    /* PREVIEW BUTTON */
    document.querySelectorAll(".preview-mission-btn").forEach(function (button) {
        button.addEventListener("click", function () {
            document.getElementById("preview_title").textContent = this.dataset.title;
            document.getElementById("preview_description").textContent =
                this.dataset.description || "No description provided.";
            document.getElementById("preview_module").textContent = this.dataset.module;
            document.getElementById("preview_course").textContent = this.dataset.course;
            document.getElementById("preview_number").textContent = this.dataset.number;
            document.getElementById("preview_type").textContent = this.dataset.type;
            document.getElementById("preview_order").textContent = this.dataset.order;
            document.getElementById("preview_status").textContent = this.dataset.status;

            document.getElementById("previewMissionModal").showModal();
        });
    });

});
</script>

</body>
</html>
