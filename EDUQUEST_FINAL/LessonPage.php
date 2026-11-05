
<?php
session_start();

require_once "Admin_Portal/db_connect.php";
require_once "student_auth.php";

$conn->set_charset("utf8mb4");

/* =============================
   HELPER FUNCTIONS
============================= */

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function saveLessonProgress(
    mysqli $conn,
    int $userId,
    int $lessonId,
    bool $completed = false
): bool {

    $stmt = $conn->prepare("
        SELECT status
        FROM user_lesson_progress
        WHERE user_id = ? AND lesson_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $userId, $lessonId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if ($completed) {
            $stmt = $conn->prepare("
                UPDATE user_lesson_progress
                SET
                    status = 'completed',
                    started_at = COALESCE(started_at, NOW()),
                    completed_at = COALESCE(completed_at, NOW()),
                    updated_at = NOW()
                WHERE user_id = ? AND lesson_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE user_lesson_progress
                SET
                    status = IF(
                        status = 'completed',
                        'completed',
                        'in_progress'
                    ),
                    started_at = COALESCE(started_at, NOW()),
                    updated_at = NOW()
                WHERE user_id = ? AND lesson_id = ?
            ");
        }

        $stmt->bind_param("ii", $userId, $lessonId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    if ($completed) {
        $stmt = $conn->prepare("
            INSERT INTO user_lesson_progress
                (user_id, lesson_id, status, started_at, completed_at, updated_at)
            VALUES (?, ?, 'completed', NOW(), NOW(), NOW())
        ");
    } else {
        $stmt = $conn->prepare("
            INSERT INTO user_lesson_progress
                (user_id, lesson_id, status, started_at, completed_at, updated_at)
            VALUES (?, ?, 'in_progress', NOW(), NULL, NOW())
        ");
    }

    $stmt->bind_param("ii", $userId, $lessonId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

/* =============================
   SESSION AND CSRF TOKEN
============================= */

$userId = (int) ($_SESSION["user_id"] ?? 0);

if (empty($_SESSION["lesson_csrf_token"])) {
    $_SESSION["lesson_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["lesson_csrf_token"];

$studentName = $_SESSION["username"]
    ?? $_SESSION["full_name"]
    ?? "Student";

/* =============================
   GET SELECTED COURSE AND LESSON
============================= */

$courseId = (int) (filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
) ?: 0);

$lessonId = (int) (filter_input(
    INPUT_GET,
    "lesson_id",
    FILTER_VALIDATE_INT
) ?: 0);

$moduleId = (int) (filter_input(
    INPUT_GET,
    "module_id",
    FILTER_VALIDATE_INT
) ?: 0);

$missionId = (int) (filter_input(
    INPUT_GET,
    "mission_id",
    FILTER_VALIDATE_INT
) ?: 0);

/* =============================
   RECOVER COURSE AND MODULE
============================= */

if ($lessonId > 0) {
    $stmt = $conn->prepare("
        SELECT m.course_id, m.module_id
        FROM lessons l
        INNER JOIN modules m ON m.module_id = l.module_id
        WHERE l.lesson_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $lessonId);
    $stmt->execute();
    $lessonInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($lessonInfo) {
        if ($courseId < 1) {
            $courseId = (int) $lessonInfo["course_id"];
        }

        if ($moduleId < 1) {
            $moduleId = (int) $lessonInfo["module_id"];
        }
    }
}

if ($courseId < 1 && $moduleId > 0) {
    $stmt = $conn->prepare("
        SELECT course_id
        FROM modules
        WHERE module_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $courseId = (int) $row["course_id"];
    }
}

if ($missionId > 0) {
    $stmt = $conn->prepare("
        SELECT m.course_id, m.module_id
        FROM missions mi
        INNER JOIN modules m ON m.module_id = mi.module_id
        WHERE mi.mission_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $missionId);
    $stmt->execute();
    $missionInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($missionInfo) {
        if ($courseId < 1) {
            $courseId = (int) $missionInfo["course_id"];
        }

        if ($moduleId < 1) {
            $moduleId = (int) $missionInfo["module_id"];
        }
    }
}

/* =============================
   LOAD COURSE AND LESSONS
============================= */

$course = null;
$lesson = null;
$lessons = [];
$errorMessage = "";
$successMessage = "";

$currentIndex = 0;
$nextLesson = null;


$activityId = 0;
$activityPassed = false;

if ($userId < 1) {
    $errorMessage = "Your login session is unavailable. Please log in again.";
} elseif ($courseId < 1) {
    $errorMessage = "No course was selected. Please return to your dashboard.";
} else {

    $stmt = $conn->prepare("
        SELECT
            course_id,
            course_title,
            course_description,
            course_number
        FROM courses
        WHERE course_id = ?
          AND status = 'published'
        LIMIT 1
    ");

    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$course) {
        $errorMessage = "Course not found or it is not currently available.";
    } else {

        $stmt = $conn->prepare("
            SELECT
                l.lesson_id,
                l.lesson_number,
                l.lesson_title,
                l.lesson_content,
                l.estimated_minutes,
                m.module_id,
                m.module_number,
                m.module_title,
                COALESCE(ulp.status, 'not_started') AS progress_status,
                ulp.started_at,
                ulp.completed_at
            FROM lessons l
            INNER JOIN modules m
                ON l.module_id = m.module_id
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.lesson_id
               AND ulp.user_id = ?
            WHERE m.course_id = ?
              AND m.status = 'published'
              AND l.status = 'published'
            ORDER BY
                m.module_number ASC,
                l.lesson_number ASC,
                l.lesson_id ASC
        ");

        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }

        $stmt->close();

        if (empty($lessons)) {
            $errorMessage = "This course does not have any published lessons yet.";
        } else {

            /* =============================
               SELECT CURRENT LESSON
            ============================= */

            if ($lessonId > 0) {
                foreach ($lessons as $index => $item) {
                    if ((int) $item["lesson_id"] === $lessonId) {
                        $lesson = $item;
                        $currentIndex = $index;
                        break;
                    }
                }

                if (!$lesson) {
                    $errorMessage =
                        "This lesson does not belong to the selected course, or it is unavailable.";
                }
            } elseif ($moduleId > 0) {
                foreach ($lessons as $index => $item) {
                    if ((int) $item["module_id"] === $moduleId) {
                        $lesson = $item;
                        $currentIndex = $index;
                        break;
                    }
                }

                if (!$lesson) {
                    $errorMessage = "No published lessons were found for this module.";
                }
            } else {
                $lesson = $lessons[0];
                $currentIndex = 0;
            }

            /* =============================
               RECOVER MISSION FOR THIS MODULE
               IF THE URL LOST mission_id
            ============================= */

            if ($lesson && $missionId < 1) {
                $currentModuleId = (int) $lesson["module_id"];

                $stmt = $conn->prepare("
                    SELECT mission_id
                    FROM missions
                    WHERE module_id = ?
                    ORDER BY mission_order ASC, mission_id ASC
                    LIMIT 1
                ");

                $stmt->bind_param("i", $currentModuleId);
                $stmt->execute();
                $missionInfo = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($missionInfo) {
                    $missionId = (int) $missionInfo["mission_id"];
                }
            }

            /* =============================
               CHECK WHETHER THE ACTIVITY WAS PASSED
            ============================= */

            if ($lesson && $missionId > 0) {
                $stmt = $conn->prepare("
                    SELECT a.activity_id
                    FROM activities a
                    INNER JOIN missions mi ON mi.mission_id = a.mission_id
                    WHERE mi.mission_id = ?
                      AND mi.status = 'published'
                      AND a.status = 'published'
                    ORDER BY a.activity_order ASC, a.activity_id ASC
                    LIMIT 1
                ");

                $stmt->bind_param("i", $missionId);
                $stmt->execute();
                $activityInfo = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($activityInfo) {
                    $activityId = (int) $activityInfo["activity_id"];

                    $stmt = $conn->prepare("
                        SELECT 1
                        FROM activity_attempts
                        WHERE user_id = ?
                          AND activity_id = ?
                          AND status = 'passed'
                        LIMIT 1
                    ");

                    $stmt->bind_param("ii", $userId, $activityId);
                    $stmt->execute();
                    $activityPassed = (bool) $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            }

            /* =============================
               HANDLE MARK AS COMPLETE
            ============================= */

            if (
                $lesson &&
                $_SERVER["REQUEST_METHOD"] === "POST" &&
                isset($_POST["complete_lesson"])
            ) {
                $submittedToken = (string) ($_POST["csrf_token"] ?? "");

                if (!hash_equals($csrfToken, $submittedToken)) {
                    $errorMessage =
                        "Your request could not be verified. Please refresh the page and try again.";
                } else {
                    $postedLessonId = filter_input(
                        INPUT_POST,
                        "lesson_id",
                        FILTER_VALIDATE_INT
                    );

                    if (
                        !$postedLessonId ||
                        (int) $postedLessonId !== (int) $lesson["lesson_id"]
                    ) {
                        $errorMessage = "Invalid lesson selection.";
                    } else {
                        try {
                            $saved = saveLessonProgress(
                                $conn,
                                $userId,
                                (int) $lesson["lesson_id"],
                                true
                            );

                            if ($saved) {
                                $redirectUrl = "LessonPage.php?course_id="
                                    . (int) $course["course_id"]
                                    . "&lesson_id="
                                    . (int) $lesson["lesson_id"];

                                if ($missionId > 0) {
                                    $redirectUrl .= "&mission_id=" . $missionId;
                                }

                                $redirectUrl .= "&completed=1";

                                header("Location: " . $redirectUrl);
                                exit;
                            }

                            $errorMessage =
                                "We couldn't save your progress. Please try again.";

                        } catch (Throwable $exception) {
                            error_log(
                                "EduQuest lesson completion error: "
                                . $exception->getMessage()
                            );

                            $errorMessage =
                                "We couldn't save your progress. Please check your database setup and try again.";
                        }
                    }
                }
            }

            /* =============================
               SAVE LESSON OPENING
            ============================= */

            if ($lesson && $_SERVER["REQUEST_METHOD"] === "GET") {
                try {
                    saveLessonProgress(
                        $conn,
                        $userId,
                        (int) $lesson["lesson_id"],
                        false
                    );
                } catch (Throwable $exception) {
                    error_log(
                        "EduQuest lesson opening error: "
                        . $exception->getMessage()
                    );

                    $errorMessage =
                        "We couldn't save your lesson progress. Please check your database setup.";
                }
            }

            /* =============================
               REFRESH SAVED PROGRESS
            ============================= */

            if ($lesson && $errorMessage === "") {
                $stmt = $conn->prepare("
                    SELECT status, started_at, completed_at
                    FROM user_lesson_progress
                    WHERE user_id = ? AND lesson_id = ?
                    LIMIT 1
                ");

                $selectedLessonId = (int) $lesson["lesson_id"];

                $stmt->bind_param("ii", $userId, $selectedLessonId);
                $stmt->execute();
                $savedProgress = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($savedProgress) {
                    $lesson["progress_status"] = $savedProgress["status"];
                    $lesson["started_at"] = $savedProgress["started_at"];
                    $lesson["completed_at"] = $savedProgress["completed_at"];
                }

                foreach ($lessons as $index => $item) {
                    if ((int) $item["lesson_id"] === $selectedLessonId) {
                        $lessons[$index]["progress_status"] =
                            $lesson["progress_status"];

                        $lessons[$index]["completed_at"] =
                            $lesson["completed_at"];

                        break;
                    }
                }

                /* =============================
                   FIND NEXT LESSON IN SAME MODULE
                ============================= */

                $nextLesson = null;
                $currentModuleId = (int) $lesson["module_id"];
                $currentLessonNumber = (int) $lesson["lesson_number"];

                foreach ($lessons as $candidate) {
                    if (
                        (int) $candidate["module_id"] === $currentModuleId &&
                        (int) $candidate["lesson_number"] > $currentLessonNumber
                    ) {
                        $nextLesson = $candidate;
                        break;
                    }
                }

                if (
                    isset($_GET["completed"]) &&
                    $_GET["completed"] === "1" &&
                    $lesson["progress_status"] === "completed"
                ) {
                    $successMessage =
                        "Lesson completed! Your progress has been saved.";
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

    <title>
        <?php if ($course): ?>
            <?= e($course["course_title"]) ?> - EduQuest
        <?php else: ?>
            Lesson - EduQuest
        <?php endif; ?>
    </title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .lesson-course-info {
            margin: 18px 0;
            color: inherit;
        }

        .lesson-course-info p {
            margin: 6px 0;
        }

        .lesson-layout {
            display: grid;
            grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .lesson-list-card {
            padding: 20px;
            border: 1px solid #E6E6E6;
            border-radius: 14px;
            background: #FFFFFF;
        }

        .lesson-list-card h2 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .lesson-list-card .course-name {
            margin-bottom: 18px;
            color: #737373;
            font-size: 13px;
        }

        .lesson-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .lesson-list a {
            display: block;
            padding: 12px;
            border-radius: 9px;
            color: #202020;
            text-decoration: none;
            background: #F3F4F2;
            overflow-wrap: anywhere;
        }

        .lesson-list a:hover,
        .lesson-list a.active {
            background: #E9F9E2;
            outline: 1px solid #5DD62C;
        }

        .lesson-list a span {
            display: block;
            margin-bottom: 5px;
            color: #337418;
            font-size: 12px;
        }

        .lesson-status {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 20px;
            background: #EAEAEA;
            color: #333333;
            font-size: 11px;
            font-weight: 600;
        }

        .lesson-status.completed {
            background: #E2F7D9;
            color: #245D13;
        }

        .lesson-status.in-progress {
            background: #FFF0C2;
            color: #785800;
        }

        .lesson-content {
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .lesson-content h2,
        .lesson-content h3 {
            margin: 24px 0 10px;
        }

        .lesson-content p {
            margin: 10px 0;
        }

        .lesson-content ul,
        .lesson-content ol {
            padding-left: 24px;
            margin: 12px 0;
        }

        .lesson-content li {
            margin-bottom: 8px;
        }

        .lesson-content img,
        .lesson-content table {
            max-width: 100%;
        }

        .lesson-unavailable {
            padding: 24px;
            border-radius: 12px;
            background: #FFFFFF;
            border: 1px solid #E6E6E6;
        }

        .lesson-unavailable h1 {
            margin-bottom: 12px;
        }

        .progress-message {
            margin: 16px 0;
            padding: 12px 16px;
            border-radius: 10px;
            background: #E2F7D9;
            color: #245D13;
            line-height: 1.5;
        }

        .progress-error {
            margin: 16px 0;
            padding: 12px 16px;
            border-radius: 10px;
            background: #FCE8E6;
            color: #8B1E16;
            line-height: 1.5;
        }

        .lesson-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 28px;
        }

        .complete-lesson-btn {
            display: inline-block;
            padding: 12px 20px;
            border: 0;
            border-radius: 8px;
            background: #5DD62C;
            color: #0F0F0F;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .complete-lesson-btn:hover {
            background: #4EC120;
        }

        .completed-button {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            background: #E2F7D9;
            color: #245D13;
            font-weight: 700;
        }

        .activity-locked-message {
            margin: 0;
            padding: 12px 16px;
            border: 1px solid #E6D7A8;
            border-radius: 8px;
            background: #FFF8E5;
            color: #725714;
            font-weight: 600;
            line-height: 1.5;
        }

        body.dark-mode-active .activity-locked-message {
            border-color: #66552A;
            background: #3A321F;
            color: #FFE39A;
        }

        @media (max-width: 760px) {
            .lesson-layout {
                grid-template-columns: 1fr;
            }

            .lesson-list-card {
                order: 2;
            }
        }

        body.dark-mode-active .lesson-list-card,
        body.dark-mode-active .lesson-unavailable {
            background: #202020;
            border-color: #444444;
            color: #F8F8F8;
        }

        body.dark-mode-active .lesson-list a {
            background: #2B2B2B;
            color: #F8F8F8;
        }

        body.dark-mode-active .lesson-list a.active,
        body.dark-mode-active .lesson-list a:hover {
            background: #253B20;
            outline-color: #5DD62C;
        }

        body.dark-mode-active .lesson-status {
            background: #383838;
            color: #F8F8F8;
        }

        body.dark-mode-active .lesson-status.completed {
            background: #253B20;
            color: #B7F5A0;
        }

        body.dark-mode-active .lesson-status.in-progress {
            background: #4A3A12;
            color: #FFE39A;
        }
    </style>
</head>

<body>

<div class="page-container">

    <header class="topbar">

        <div class="welcome">
            <h2>EduQuest</h2>
        </div>

        <div class="profile">

            <span class="notification" aria-label="Notifications">
                🔔
            </span>

            <button
                type="button"
                class="dark-mode-btn"
                onclick="toggleDarkMode()"
                aria-label="Toggle dark mode"
            >
                🌙
            </button>

            <div class="profile-info">
                <span class="profile-name">
                    <?= e($studentName) ?>
                </span>
            </div>

        </div>

    </header>

    <main class="learning-container">

        <?php if ($errorMessage !== ""): ?>

            <section class="lesson-unavailable">
                <h1>Lesson Unavailable</h1>

                <p><?= e($errorMessage) ?></p>

                <div class="learning-actions">
                    <a href="DashboardPage.php" class="primary-btn">
                        ← Back to Dashboard
                    </a>
                </div>
            </section>

        <?php else: ?>

            <div class="learning-header">

                <a href="DashboardPage.php" class="back-link">
                    ← Back to Dashboard
                </a>

                <span class="mission-number">
                    Course <?= (int) $course["course_number"] ?>
                </span>

            </div>

            <section class="lesson-course-info">

                <h1><?= e($course["course_title"]) ?></h1>

                <?php if (!empty($course["course_description"])): ?>
                    <p><?= e($course["course_description"]) ?></p>
                <?php endif; ?>

            </section>

            <?php if ($successMessage !== ""): ?>
                <div class="progress-message" role="status">
                    ✓ <?= e($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>
                <div class="progress-error" role="alert">
                    <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <div class="lesson-layout">

                <!-- LESSON NAVIGATION -->

                <aside class="lesson-list-card">

                    <h2>Course Lessons</h2>

                    <p class="course-name">
                        <?= count($lessons) ?> published
                        <?= count($lessons) === 1 ? "lesson" : "lessons" ?>
                    </p>

                    <nav class="lesson-list">

                        <?php foreach ($lessons as $item): ?>

                            <?php
                            $itemStatus = $item["progress_status"] ?? "not_started";

                            $statusLabel = match ($itemStatus) {
                                "completed" => "Completed",
                                "in_progress" => "In Progress",
                                default => "Not Started"
                            };

                            $statusClass = match ($itemStatus) {
                                "completed" => "completed",
                                "in_progress" => "in-progress",
                                default => ""
                            };
                            ?>

                            <a
                                href="LessonPage.php?course_id=<?= (int) $course["course_id"] ?>&amp;lesson_id=<?= (int) $item["lesson_id"] ?><?= $missionId > 0 ? '&amp;mission_id=' . (int) $missionId : '' ?>"
                                class="<?= (int) $item["lesson_id"] === (int) $lesson["lesson_id"] ? "active" : "" ?>"
                                <?= (int) $item["lesson_id"] === (int) $lesson["lesson_id"] ? 'aria-current="page"' : "" ?>
                            >

                                <span>
                                    Module <?= (int) $item["module_number"] ?>
                                    · Lesson <?= (int) $item["lesson_number"] ?>
                                </span>

                                <strong>
                                    <?= e($item["lesson_title"]) ?>
                                </strong>

                                <small class="lesson-status <?= e($statusClass) ?>">
                                    <?= e($statusLabel) ?>
                                </small>

                            </a>

                        <?php endforeach; ?>

                    </nav>

                </aside>

                <!-- SELECTED LESSON -->

                <section class="learning-card">

                    <span class="section-label">
                        LEARN
                    </span>

                    <h1><?= e($lesson["lesson_title"]) ?></h1>

                    <p class="lesson-intro">
                        <?= e($lesson["module_title"]) ?>
                        · Estimated time:
                        <?= (int) $lesson["estimated_minutes"] ?> minutes
                    </p>

                    <div class="progress-section">

                        <div class="progress-info">
                            <span>Lesson</span>

                            <span>
                                <?= $currentIndex + 1 ?>
                                of
                                <?= count($lessons) ?>
                            </span>
                        </div>

                        <div class="progress-bar">
                            <div
                                class="progress-fill"
                                style="width: <?= round(($currentIndex + 1) / count($lessons) * 100) ?>%;">
                            </div>
                        </div>

                    </div>

                    <div class="lesson-content">

                        <?php if (trim((string) $lesson["lesson_content"]) !== ""): ?>

                            <?php
                            // Lesson content is assumed to be authored by a trusted administrator.
                            echo $lesson["lesson_content"];
                            ?>

                        <?php else: ?>

                            <p>
                                Lesson content is not available yet.
                                Please check back later.
                            </p>

                        <?php endif; ?>

                    </div>

                    <div class="lesson-actions">

                        <?php if ($lesson["progress_status"] === "completed"): ?>

                            <span class="completed-button">
                                ✓ Lesson Completed
                            </span>

                        <?php else: ?>

                            <form
                                method="POST"
                                action="LessonPage.php?course_id=<?= (int) $course["course_id"] ?>&amp;lesson_id=<?= (int) $lesson["lesson_id"] ?><?= $missionId > 0 ? '&amp;mission_id=' . (int) $missionId : '' ?>"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($csrfToken) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="lesson_id"
                                    value="<?= (int) $lesson["lesson_id"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="complete_lesson"
                                    value="1"
                                    class="complete-lesson-btn"
                                >
                                    ✓ Mark as Complete
                                </button>

                            </form>

                        <?php endif; ?>

                        <?php if ($nextLesson): ?>

                            <a
                                href="LessonPage.php?course_id=<?= (int) $course["course_id"] ?>&amp;lesson_id=<?= (int) $nextLesson["lesson_id"] ?><?= $missionId > 0 ? '&amp;mission_id=' . (int) $missionId : '' ?>"
                                class="primary-btn"
                            >
                                Next Lesson →
                            </a>

                        <?php elseif (
                            $lesson["progress_status"] === "completed" &&
                            $missionId > 0 &&
                            $activityPassed
                        ): ?>

                            <a
                                href="Challenge.php?mission_id=<?= (int) $missionId ?>"
                                class="primary-btn"
                            >
                                Continue to Challenge →
                            </a>

                        <?php elseif (
                            $lesson["progress_status"] === "completed" &&
                            $missionId > 0 &&
                            $activityId > 0
                        ): ?>

                            <a
                                href="Activity.php?activity_id=<?= (int) $activityId ?>&amp;mission_id=<?= (int) $missionId ?>&amp;lesson_id=<?= (int) $lesson['lesson_id'] ?>"
                                class="primary-btn"
                            >
                                Continue to Activity →
                            </a>

                        <?php elseif (
                            $lesson["progress_status"] === "completed" &&
                            $missionId < 1
                        ): ?>

                            <a
                                href="Activity.php?lesson_id=<?= (int) $lesson["lesson_id"] ?>&amp;module_id=<?= (int) $lesson["module_id"] ?>"
                                class="primary-btn"
                            >
                                Continue to Activity →
                            </a>

                        <?php else: ?>

                            <p class="activity-locked-message" role="status">
                                🔒 Complete this lesson first to unlock the activity.
                            </p>

                        <?php endif; ?>

                    </div>

                </section>

            </div>

        <?php endif; ?>

    </main>

</div>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode-active");

        const button = document.querySelector(".dark-mode-btn");

        if (button) {
            button.textContent =
                document.body.classList.contains("dark-mode-active")
                    ? "☀️"
                    : "🌙";
        }
    }
</script>

</body>
</html>
