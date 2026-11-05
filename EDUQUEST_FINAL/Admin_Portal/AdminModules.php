
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";

/* =========================================
   HELPER FUNCTIONS
========================================= */

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function getDeleteBlockers($conn, $table, $column, $id) {
    $blockers = [];

    $stmt = $conn->prepare("
        SELECT TABLE_NAME, COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME = ?
        AND REFERENCED_COLUMN_NAME = ?
    ");

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($reference = $result->fetch_assoc()) {
        $childTable = str_replace("`", "``", $reference["TABLE_NAME"]);
        $childColumn = str_replace("`", "``", $reference["COLUMN_NAME"]);

        $query = "
            SELECT COUNT(*) AS total
            FROM `$childTable`
            WHERE `$childColumn` = ?
        ";

        $countStmt = $conn->prepare($query);
        $countStmt->bind_param("i", $id);
        $countStmt->execute();

        $count = $countStmt->get_result()->fetch_assoc();

        if ((int)$count["total"] > 0) {
            $blockers[] = $reference["TABLE_NAME"];
        }

        $countStmt->close();
    }

    $stmt->close();

    return array_unique($blockers);
}

function redirectToCourse($courseId) {
    header("Location: " . basename($_SERVER["PHP_SELF"]) . "?course_id=" . (int)$courseId);
    exit;
}

/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION["module_csrf"])) {
    $_SESSION["module_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["module_csrf"];

/* =========================================
   SELECTED COURSE
========================================= */

$selectedCourseId = isset($_GET["course_id"])
    ? (int)$_GET["course_id"]
    : 0;

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $action = $_POST["action"] ?? "";

        $postedCourseId = (int)($_POST["course_id"] ?? 0);

        if ($postedCourseId > 0) {
            $selectedCourseId = $postedCourseId;
        }

        /* =====================================
           ADD MODULE
        ===================================== */

        if ($action === "add_module") {

            $courseId = (int)($_POST["course_id"] ?? 0);
            $moduleTitle = trim($_POST["module_title"] ?? "");
            $moduleDescription = trim($_POST["module_description"] ?? "");
            $moduleNumber = (int)($_POST["module_number"] ?? 0);
            $status = $_POST["status"] ?? "draft";

            if (
                $courseId <= 0 ||
                $moduleTitle === "" ||
                $moduleNumber <= 0
            ) {
                $error = "Please complete all required module fields.";

            } elseif (!in_array($status, ["draft", "published"], true)) {
                $error = "Invalid module status.";

            } else {

                $checkCourse = $conn->prepare("
                    SELECT course_id
                    FROM courses
                    WHERE course_id = ?
                    LIMIT 1
                ");

                $checkCourse->bind_param("i", $courseId);
                $checkCourse->execute();
                $checkCourse->store_result();

                if ($checkCourse->num_rows === 0) {
                    $error = "Selected course does not exist.";
                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO modules (
                            course_id,
                            module_number,
                            module_title,
                            module_description,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "iisss",
                        $courseId,
                        $moduleNumber,
                        $moduleTitle,
                        $moduleDescription,
                        $status
                    );

                    if ($stmt->execute()) {
                        $success = "Module added successfully!";
                    } else {
                        $error = "Failed to add module: " . $stmt->error;
                    }

                    $stmt->close();
                }

                $checkCourse->close();
            }
        }

        /* =====================================
           EDIT MODULE
        ===================================== */

        elseif ($action === "edit_module") {

            $moduleId = (int)($_POST["module_id"] ?? 0);
            $courseId = (int)($_POST["course_id"] ?? 0);
            $moduleTitle = trim($_POST["module_title"] ?? "");
            $moduleDescription = trim($_POST["module_description"] ?? "");
            $moduleNumber = (int)($_POST["module_number"] ?? 0);
            $status = $_POST["status"] ?? "draft";

            if (
                $moduleId <= 0 ||
                $courseId <= 0 ||
                $moduleTitle === "" ||
                $moduleNumber <= 0
            ) {
                $error = "Please complete all required module fields.";

            } elseif (!in_array($status, ["draft", "published", "archived"], true)) {
                $error = "Invalid module status.";

            } else {

                $stmt = $conn->prepare("
                    UPDATE modules
                    SET module_number = ?,
                        module_title = ?,
                        module_description = ?,
                        status = ?
                    WHERE module_id = ?
                    AND course_id = ?
                ");

                $stmt->bind_param(
                    "isssii",
                    $moduleNumber,
                    $moduleTitle,
                    $moduleDescription,
                    $status,
                    $moduleId,
                    $courseId
                );

                if ($stmt->execute() && $stmt->affected_rows >= 0) {
                    $success = "Module updated successfully!";
                } else {
                    $error = "Unable to update module.";
                }

                $stmt->close();
            }
        }

        /* =====================================
           ADD LESSON
        ===================================== */

        elseif ($action === "add_lesson") {

            $moduleId = (int)($_POST["module_id"] ?? 0);
            $lessonTitle = trim($_POST["lesson_title"] ?? "");
            $lessonContent = trim($_POST["lesson_content"] ?? "");
            $estimatedMinutes = (int)($_POST["estimated_minutes"] ?? 10);
            $status = $_POST["status"] ?? "draft";

            if (
                $moduleId <= 0 ||
                $lessonTitle === "" ||
                $estimatedMinutes <= 0
            ) {
                $error = "Please complete all required lesson fields.";

            } elseif (!in_array($status, ["draft", "published"], true)) {
                $error = "Invalid lesson status.";

            } else {

                $checkModule = $conn->prepare("
                    SELECT module_id
                    FROM modules
                    WHERE module_id = ?
                    AND course_id = ?
                    LIMIT 1
                ");

                $checkModule->bind_param(
                    "ii",
                    $moduleId,
                    $selectedCourseId
                );

                $checkModule->execute();
                $checkModule->store_result();

                if ($checkModule->num_rows === 0) {
                    $error = "Selected module was not found.";
                } else {

                    $numStmt = $conn->prepare("
                        SELECT COALESCE(MAX(lesson_number), 0) + 1 AS next_number
                        FROM lessons
                        WHERE module_id = ?
                    ");

                    $numStmt->bind_param("i", $moduleId);
                    $numStmt->execute();

                    $lessonNumber = (int)$numStmt
                        ->get_result()
                        ->fetch_assoc()["next_number"];

                    $numStmt->close();

                    $stmt = $conn->prepare("
                        INSERT INTO lessons (
                            module_id,
                            lesson_number,
                            lesson_title,
                            lesson_content,
                            estimated_minutes,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "iissis",
                        $moduleId,
                        $lessonNumber,
                        $lessonTitle,
                        $lessonContent,
                        $estimatedMinutes,
                        $status
                    );

                    if ($stmt->execute()) {
                        $success = "Lesson added successfully!";
                    } else {
                        $error = "Failed to add lesson: " . $stmt->error;
                    }

                    $stmt->close();
                }

                $checkModule->close();
            }
        }

        /* =====================================
           EDIT LESSON
        ===================================== */

        elseif ($action === "edit_lesson") {

            $lessonId = (int)($_POST["lesson_id"] ?? 0);
            $moduleId = (int)($_POST["module_id"] ?? 0);
            $lessonTitle = trim($_POST["lesson_title"] ?? "");
            $lessonContent = trim($_POST["lesson_content"] ?? "");
            $lessonNumber = (int)($_POST["lesson_number"] ?? 0);
            $estimatedMinutes = (int)($_POST["estimated_minutes"] ?? 10);
            $status = $_POST["status"] ?? "draft";

            if (
                $lessonId <= 0 ||
                $moduleId <= 0 ||
                $lessonTitle === "" ||
                $lessonNumber <= 0 ||
                $estimatedMinutes <= 0
            ) {
                $error = "Please complete all required lesson fields.";

            } elseif (!in_array($status, ["draft", "published", "archived"], true)) {
                $error = "Invalid lesson status.";

            } else {

                $check = $conn->prepare("
                    SELECT l.lesson_id
                    FROM lessons l
                    INNER JOIN modules m
                        ON l.module_id = m.module_id
                    WHERE l.lesson_id = ?
                    AND l.module_id = ?
                    AND m.course_id = ?
                    LIMIT 1
                ");

                $check->bind_param(
                    "iii",
                    $lessonId,
                    $moduleId,
                    $selectedCourseId
                );

                $check->execute();
                $check->store_result();

                if ($check->num_rows === 0) {
                    $error = "Lesson not found.";
                } else {

                    $stmt = $conn->prepare("
                        UPDATE lessons
                        SET lesson_number = ?,
                            lesson_title = ?,
                            lesson_content = ?,
                            estimated_minutes = ?,
                            status = ?
                        WHERE lesson_id = ?
                        AND module_id = ?
                    ");

                    $stmt->bind_param(
                        "issisii",
                        $lessonNumber,
                        $lessonTitle,
                        $lessonContent,
                        $estimatedMinutes,
                        $status,
                        $lessonId,
                        $moduleId
                    );

                    if ($stmt->execute()) {
                        $success = "Lesson updated successfully!";
                    } else {
                        $error = "Unable to update lesson: " . $stmt->error;
                    }

                    $stmt->close();
                }

                $check->close();
            }
        }

        /* =====================================
           DELETE MODULE
        ===================================== */

        elseif ($action === "delete_module") {

            $moduleId = (int)($_POST["module_id"] ?? 0);
            $courseId = (int)($_POST["course_id"] ?? 0);

            if ($moduleId <= 0 || $courseId <= 0) {
                $error = "Invalid module selected.";
            } else {

                $check = $conn->prepare("
                    SELECT module_id
                    FROM modules
                    WHERE module_id = ?
                    AND course_id = ?
                    LIMIT 1
                ");

                $check->bind_param("ii", $moduleId, $courseId);
                $check->execute();
                $check->store_result();

                if ($check->num_rows === 0) {
                    $error = "Module not found.";
                } else {

                    $blockers = getDeleteBlockers(
                        $conn,
                        "modules",
                        "module_id",
                        $moduleId
                    );

                    if (!empty($blockers)) {
                        $error = "This module cannot be deleted because it has related records in: "
                            . implode(", ", $blockers)
                            . ". Archive the module instead to preserve its records.";
                    } else {

                        $delete = $conn->prepare("
                            DELETE FROM modules
                            WHERE module_id = ?
                            AND course_id = ?
                        ");

                        $delete->bind_param("ii", $moduleId, $courseId);

                        if ($delete->execute() && $delete->affected_rows > 0) {
                            $success = "Module deleted successfully!";
                        } else {
                            $error = "Unable to delete the selected module.";
                        }

                        $delete->close();
                    }
                }

                $check->close();
            }
        }

        /* =====================================
           DELETE LESSON
        ===================================== */

        elseif ($action === "delete_lesson") {

            $lessonId = (int)($_POST["lesson_id"] ?? 0);
            $moduleId = (int)($_POST["module_id"] ?? 0);
            $courseId = (int)($_POST["course_id"] ?? 0);

            if ($lessonId <= 0 || $moduleId <= 0 || $courseId <= 0) {
                $error = "Invalid lesson selected.";
            } else {

                $check = $conn->prepare("
                    SELECT l.lesson_id
                    FROM lessons l
                    INNER JOIN modules m
                        ON l.module_id = m.module_id
                    WHERE l.lesson_id = ?
                    AND l.module_id = ?
                    AND m.course_id = ?
                    LIMIT 1
                ");

                $check->bind_param(
                    "iii",
                    $lessonId,
                    $moduleId,
                    $courseId
                );

                $check->execute();
                $check->store_result();

                if ($check->num_rows === 0) {
                    $error = "Lesson not found.";
                } else {

                    $blockers = getDeleteBlockers(
                        $conn,
                        "lessons",
                        "lesson_id",
                        $lessonId
                    );

                    /*
                     * If student progress exists, archive the lesson.
                     */
                    if (in_array("user_lesson_progress", $blockers, true)) {

                        $archive = $conn->prepare("
                            UPDATE lessons
                            SET status = 'archived'
                            WHERE lesson_id = ?
                            AND module_id = ?
                        ");

                        $archive->bind_param("ii", $lessonId, $moduleId);

                        if ($archive->execute()) {
                            $success = "This lesson has student progress records, so it was archived instead of permanently deleted.";
                        } else {
                            $error = "Unable to archive the lesson.";
                        }

                        $archive->close();

                    } elseif (!empty($blockers)) {

                        $error = "This lesson cannot be deleted because it has related records in: "
                            . implode(", ", $blockers)
                            . ". Remove or reassign those records first.";

                    } else {

                        $delete = $conn->prepare("
                            DELETE FROM lessons
                            WHERE lesson_id = ?
                            AND module_id = ?
                        ");

                        $delete->bind_param("ii", $lessonId, $moduleId);

                        if ($delete->execute() && $delete->affected_rows > 0) {
                            $success = "Lesson deleted successfully!";
                        } else {
                            $error = "Unable to delete the selected lesson.";
                        }

                        $delete->close();
                    }
                }

                $check->close();
            }
        }
    }
}

/* =========================================
   FETCH COURSES
========================================= */

$coursesResult = $conn->query("
    SELECT course_id, course_title
    FROM courses
    ORDER BY course_number ASC
");

$courses = [];

while ($row = $coursesResult->fetch_assoc()) {
    $courses[] = $row;
}

if ($selectedCourseId <= 0 && !empty($courses)) {
    $selectedCourseId = (int)$courses[0]["course_id"];
}

$validCourse = false;

foreach ($courses as $course) {
    if ((int)$course["course_id"] === $selectedCourseId) {
        $validCourse = true;
        break;
    }
}

if (!$validCourse) {
    $selectedCourseId = !empty($courses)
        ? (int)$courses[0]["course_id"]
        : 0;
}

/* =========================================
   FETCH MODULES AND LESSONS
========================================= */

$modules = [];

if ($selectedCourseId > 0) {

    $modStmt = $conn->prepare("
        SELECT *
        FROM modules
        WHERE course_id = ?
        ORDER BY module_number ASC
    ");

    $modStmt->bind_param("i", $selectedCourseId);
    $modStmt->execute();

    $modulesResult = $modStmt->get_result();

    while ($module = $modulesResult->fetch_assoc()) {

        $lessonStmt = $conn->prepare("
            SELECT *
            FROM lessons
            WHERE module_id = ?
            ORDER BY lesson_number ASC
        ");

        $lessonStmt->bind_param("i", $module["module_id"]);
        $lessonStmt->execute();

        $lessonsResult = $lessonStmt->get_result();

        $module["lessons"] = [];

        while ($lesson = $lessonsResult->fetch_assoc()) {
            $module["lessons"][] = $lesson;
        }

        $lessonStmt->close();

        $modules[] = $module;
    }

    $modStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modules &amp; Lessons | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>

    <script>
        window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
        window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Modules & Lessons">

    <main class="page-content">

        <section class="page-heading split-heading">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Modules &amp; Lessons</h1>
                <p class="muted">
                    Organize course modules and learning materials.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                data-modal="moduleModal">
                ＋ Add Module
            </button>

        </section>

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

        <!-- COURSE SELECTOR -->

        <form method="GET" action="" class="toolbar"
              style="display:flex;gap:15px;align-items:center;">

            <label class="field-inline"
                   style="display:flex;align-items:center;gap:10px;">

                Select course

                <select name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $c): ?>
                        <option
                            value="<?= (int)$c["course_id"] ?>"
                            <?= ((int)$c["course_id"] === $selectedCourseId) ? "selected" : "" ?>>
                            <?= e($c["course_title"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </label>

            <button
                type="button"
                class="btn btn-outline"
                data-modal="lessonModal">
                ＋ Add Lesson
            </button>

        </form>

        <!-- MODULES AND LESSONS -->

        <section class="module-list">

            <?php if (!empty($modules)): ?>

                <?php foreach ($modules as $module): ?>

                    <?php $lessonCount = count($module["lessons"]); ?>

                    <details class="module-card" open>

                        <summary>
                            <span>
                                <b>
                                    Module <?= (int)$module["module_number"] ?>
                                    · <?= e($module["module_title"]) ?>
                                </b>

                                <small>
                                    <?= $lessonCount ?>
                                    lesson<?= $lessonCount === 1 ? "" : "s" ?>
                                    · <?= e(ucfirst($module["status"])) ?>
                                </small>
                            </span>

                            <span class="summary-actions">
                                Module actions
                            </span>
                        </summary>

                        <!-- MODULE ACTIONS -->

                        <div class="module-actions"
                             style="display:flex;justify-content:flex-end;gap:8px;padding:10px 15px;">

                            <button
                                type="button"
                                class="btn btn-small btn-outline"
                                onclick="openEditModule(this)"
                                data-module-id="<?= (int)$module["module_id"] ?>"
                                data-module-number="<?= (int)$module["module_number"] ?>"
                                data-module-title="<?= e($module["module_title"]) ?>"
                                data-module-description="<?= e($module["module_description"] ?? "") ?>"
                                data-module-status="<?= e($module["status"]) ?>">
                                Edit Module
                            </button>

                            <form method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this module? Related records will prevent deletion.');">

                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_module">
                                <input type="hidden" name="module_id" value="<?= (int)$module["module_id"] ?>">
                                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                                <button type="submit"
                                        class="btn btn-small btn-outline delete-btn">
                                    Delete Module
                                </button>
                            </form>

                        </div>

                        <!-- LESSONS -->

                        <?php if ($lessonCount > 0): ?>

                            <?php foreach ($module["lessons"] as $lesson): ?>

                                <div class="lesson-row">

                                    <span class="lesson-icon">▤</span>

                                    <div>
                                        <b>
                                            Lesson <?= (int)$lesson["lesson_number"] ?>:
                                            <?= e($lesson["lesson_title"]) ?>
                                        </b>

                                        <small>
                                            <?= (int)$lesson["estimated_minutes"] ?> minutes
                                            · <?= e(ucfirst($lesson["status"])) ?>
                                        </small>
                                    </div>

                                    <button
                                        type="button"
                                        class="btn btn-small btn-outline"
                                        onclick="openEditLesson(this)"
                                        data-lesson-id="<?= (int)$lesson["lesson_id"] ?>"
                                        data-module-id="<?= (int)$module["module_id"] ?>"
                                        data-lesson-number="<?= (int)$lesson["lesson_number"] ?>"
                                        data-lesson-title="<?= e($lesson["lesson_title"]) ?>"
                                        data-lesson-content="<?= e($lesson["lesson_content"]) ?>"
                                        data-estimated-minutes="<?= (int)$lesson["estimated_minutes"] ?>"
                                        data-lesson-status="<?= e($lesson["status"]) ?>">
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-small btn-outline"
                                        onclick="previewLesson(this)"
                                        data-lesson-title="<?= e($lesson["lesson_title"]) ?>"
                                        data-lesson-content="<?= e($lesson["lesson_content"]) ?>"
                                        data-lesson-number="<?= (int)$lesson["lesson_number"] ?>"
                                        data-estimated-minutes="<?= (int)$lesson["estimated_minutes"] ?>">
                                        Preview
                                    </button>

                                    <form method="POST"
                                          onsubmit="return confirm('Delete this lesson? If students have progress records, the lesson will be archived instead.');">

                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete_lesson">
                                        <input type="hidden" name="lesson_id" value="<?= (int)$lesson["lesson_id"] ?>">
                                        <input type="hidden" name="module_id" value="<?= (int)$module["module_id"] ?>">
                                        <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                                        <button type="submit"
                                                class="btn btn-small btn-outline delete-btn">
                                            Delete
                                        </button>
                                    </form>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty-state">
                                No lessons yet. Add a lesson to get started.
                            </div>

                        <?php endif; ?>

                    </details>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state"
                     style="padding:40px;text-align:center;background:#FFF;border-radius:8px;border:1px solid #E5E5E5;">
                    No modules found for this course.
                    Click "Add Module" to create one.
                </div>

            <?php endif; ?>

        </section>

        <!-- ADD MODULE MODAL -->

        <dialog class="modal" id="moduleModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_module">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                <div class="modal-heading">
                    <h2>Add Module</h2>

                    <button type="button" class="icon-btn"
                            aria-label="Close"
                            onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Module Title
                    <input type="text" name="module_title"
                           required maxlength="200"
                           placeholder="Enter module title">
                </label>

                <label>
                    Module Description
                    <textarea name="module_description"
                              rows="3"
                              placeholder="Brief module summary"></textarea>
                </label>

                <label>
                    Module Order (Number)
                    <input type="number" name="module_number"
                           min="1" value="1" required>
                </label>

                <label>
                    Status
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline"
                            onclick="this.closest('dialog').close()">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Module
                    </button>
                </div>

            </form>
        </dialog>

        <!-- EDIT MODULE MODAL -->

        <dialog class="modal" id="editModuleModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit_module">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                <input type="hidden" name="module_id" id="editModuleId">

                <div class="modal-heading">
                    <h2>Edit Module</h2>

                    <button type="button" class="icon-btn"
                            onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Module Title
                    <input type="text" name="module_title"
                           id="editModuleTitle" maxlength="200" required>
                </label>

                <label>
                    Module Description
                    <textarea name="module_description"
                              id="editModuleDescription" rows="3"></textarea>
                </label>

                <label>
                    Module Order
                    <input type="number" name="module_number"
                           id="editModuleNumber" min="1" required>
                </label>

                <label>
                    Status
                    <select name="status" id="editModuleStatus">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline"
                            onclick="this.closest('dialog').close()">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>
        </dialog>

        <!-- ADD LESSON MODAL -->

        <dialog class="modal" id="lessonModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_lesson">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                <div class="modal-heading">
                    <h2>Add Lesson</h2>

                    <button type="button" class="icon-btn"
                            onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Lesson Title
                    <input type="text" name="lesson_title"
                           maxlength="200" required
                           placeholder="Enter lesson title">
                </label>

                <label>
                    Module
                    <select name="module_id" required>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= (int)$module["module_id"] ?>">
                                Module <?= (int)$module["module_number"] ?>:
                                <?= e($module["module_title"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Lesson Content
                    <textarea name="lesson_content" rows="7"
                              placeholder="Write the learning material here..."></textarea>
                </label>

                <label>
                    Estimated Minutes
                    <input type="number" name="estimated_minutes"
                           min="1" value="10" required>
                </label>

                <label>
                    Status
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline"
                            onclick="this.closest('dialog').close()">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary"
                            <?= empty($modules) ? "disabled" : "" ?>>
                        Save Lesson
                    </button>
                </div>

            </form>
        </dialog>

        <!-- EDIT LESSON MODAL -->

        <dialog class="modal" id="editLessonModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit_lesson">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                <input type="hidden" name="lesson_id" id="editLessonId">
                <input type="hidden" name="module_id" id="editLessonModuleId">

                <div class="modal-heading">
                    <h2>Edit Lesson</h2>

                    <button type="button" class="icon-btn"
                            onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <label>
                    Lesson Title
                    <input type="text" name="lesson_title"
                           id="editLessonTitle" maxlength="200" required>
                </label>

                <label>
                    Lesson Number
                    <input type="number" name="lesson_number"
                           id="editLessonNumber" min="1" required>
                </label>

                <label>
                    Lesson Content
                    <textarea name="lesson_content"
                              id="editLessonContent" rows="8"></textarea>
                </label>

                <label>
                    Estimated Minutes
                    <input type="number" name="estimated_minutes"
                           id="editLessonMinutes" min="1" required>
                </label>

                <label>
                    Status
                    <select name="status" id="editLessonStatus">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline"
                            onclick="this.closest('dialog').close()">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>
        </dialog>

        <!-- PREVIEW LESSON MODAL -->

        <dialog class="modal" id="previewLessonModal">

            <div class="modal-card">

                <div class="modal-heading">
                    <h2 id="previewLessonTitle">Lesson Preview</h2>

                    <button type="button" class="icon-btn"
                            onclick="this.closest('dialog').close()">
                        ×
                    </button>
                </div>

                <p class="muted" id="previewLessonInfo"></p>

                <div id="previewLessonContent"
                     class="lesson-preview-content"></div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary"
                            onclick="document.getElementById('previewLessonModal').close()">
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

/* DELETE BUTTON */

.delete-btn {
    color: #B42318 !important;
    border-color: #E7B5B2 !important;
}

.delete-btn:hover {
    background: #FFF0F0 !important;
    color: #8F1710 !important;
}

/* MODULE ACTIONS */

.module-actions form,
.lesson-row form {
    margin: 0;
}

/* LESSON ROW */

.lesson-row {
    gap: 10px;
}

.lesson-row > div {
    flex: 1;
    min-width: 120px;
}

/* PREVIEW CONTENT */

.lesson-preview-content {
    margin-top: 18px;
    padding: 18px;
    background: #F7F7F5;
    border: 1px solid #E5E5E5;
    border-radius: 8px;
    color: #292929;
    line-height: 1.7;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    max-height: 55vh;
    overflow-y: auto;
}

#previewLessonInfo {
    margin-top: 5px;
    font-size: 13px;
}

/* RESPONSIVE */

@media (max-width: 700px) {

    .lesson-row {
        flex-wrap: wrap;
    }

    .lesson-row > div {
        flex-basis: calc(100% - 45px);
    }

    .module-actions {
        justify-content: flex-start !important;
    }

}

</style>

<script>

/* =========================================
   EDIT MODULE
========================================= */

function openEditModule(button) {

    document.getElementById("editModuleId").value =
        button.dataset.moduleId;

    document.getElementById("editModuleTitle").value =
        button.dataset.moduleTitle;

    document.getElementById("editModuleDescription").value =
        button.dataset.moduleDescription;

    document.getElementById("editModuleNumber").value =
        button.dataset.moduleNumber;

    document.getElementById("editModuleStatus").value =
        button.dataset.moduleStatus;

    document.getElementById("editModuleModal").showModal();
}

/* =========================================
   EDIT LESSON
========================================= */

function openEditLesson(button) {

    document.getElementById("editLessonId").value =
        button.dataset.lessonId;

    document.getElementById("editLessonModuleId").value =
        button.dataset.moduleId;

    document.getElementById("editLessonTitle").value =
        button.dataset.lessonTitle;

    document.getElementById("editLessonNumber").value =
        button.dataset.lessonNumber;

    document.getElementById("editLessonContent").value =
        button.dataset.lessonContent;

    document.getElementById("editLessonMinutes").value =
        button.dataset.estimatedMinutes;

    document.getElementById("editLessonStatus").value =
        button.dataset.lessonStatus;

    document.getElementById("editLessonModal").showModal();
}

/* =========================================
   PREVIEW LESSON
========================================= */

function previewLesson(button) {

    const title = button.dataset.lessonTitle;
    const content = button.dataset.lessonContent;
    const number = button.dataset.lessonNumber;
    const minutes = button.dataset.estimatedMinutes;

    document.getElementById("previewLessonTitle").textContent =
        "Lesson " + number + ": " + title;

    document.getElementById("previewLessonInfo").textContent =
        minutes + " minutes";
        
    document.getElementById("previewLessonContent").textContent =
        content.trim() !== ""
            ? content
            : "No lesson content has been added yet.";

    document.getElementById("previewLessonModal").showModal();
}

</script>

</body>
</html>
