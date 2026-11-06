
<?php
session_start();

require_once __DIR__ . '/init.php';

$conn->set_charset("utf8mb4");

/* =========================================================
   HELPER
========================================================= */

function missionPageEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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

/* =============================
   PROFILE IMAGE PATH
============================= */

$profileImage = trim((string) ($user["profile_image"] ?? ""));

if (!empty($profileImage)) {

    // Convert Windows directory separators to URL separators.
    $profileImage = str_replace("\\", "/", $profileImage);

    // Keep external image URLs and data URLs unchanged.
    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, "data:") !== 0
    ) {
        // Remove leading slashes.
        $profileImage = ltrim($profileImage, "/");

        // Only admin profile images need the Admin_Portal prefix.
        if (strpos($profileImage, "uploads/profile_images/") === 0) {
            $profileImage = "Admin_Portal/" . $profileImage;
        }
    }
}

/* =========================================================
   READ URL PARAMETERS
========================================================= */

$requestedMissionId = null;
$requestedCourseId = null;

if (isset($_GET['mission_id'])) {
    $value = filter_var($_GET['mission_id'], FILTER_VALIDATE_INT);

    if ($value === false || $value < 1) {
        header("Location: CoursePage.php");
        exit;
    }

    $requestedMissionId = (int) $value;
}

if (isset($_GET['course_id'])) {
    $value = filter_var($_GET['course_id'], FILTER_VALIDATE_INT);

    if ($value === false || $value < 1) {
        header("Location: CoursePage.php");
        exit;
    }

    $requestedCourseId = (int) $value;
}

/* =========================================================
   FIND THE COURSE CONTAINING THE REQUESTED MISSION
========================================================= */

$missionCourseId = null;

if ($requestedMissionId !== null) {
    $stmt = $conn->prepare("
        SELECT md.course_id
        FROM missions m
        INNER JOIN modules md
            ON md.module_id = m.module_id
        INNER JOIN courses c
            ON c.course_id = md.course_id
        WHERE m.mission_id = ?
          AND m.status = 'published'
          AND md.status = 'published'
          AND c.status = 'published'
        LIMIT 1
    ");

    if (!$stmt) {
        error_log("Mission lookup failed: " . $conn->error);
        http_response_code(500);
        exit("Unable to load the selected mission.");
    }

    $stmt->bind_param("i", $requestedMissionId);
    $stmt->execute();
    $missionCourse = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$missionCourse) {
        http_response_code(404);
        exit("The selected mission was not found or is not published.");
    }

    $missionCourseId = (int) $missionCourse['course_id'];
}

/*
 * A mission's actual course takes priority over course_id.
 * Otherwise, use the course selected in the dropdown.
 */
$targetCourseId = $missionCourseId ?? $requestedCourseId;

/* =========================================================
   FETCH PUBLISHED COURSES AND MISSION PROGRESS
========================================================= */

$courses = [];

$stmt = $conn->prepare("
    SELECT
        c.course_id,
        c.course_title,
        c.course_number,
        c.is_locked,

        COUNT(DISTINCT m.mission_id) AS total_missions,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(ump.status) = 'completed'
                THEN m.mission_id
            END
        ) AS completed_missions

    FROM courses c

    LEFT JOIN modules md
        ON md.course_id = c.course_id
       AND md.status = 'published'

    LEFT JOIN missions m
        ON m.module_id = md.module_id
       AND m.status = 'published'

    LEFT JOIN user_mission_progress ump
        ON ump.mission_id = m.mission_id
       AND ump.user_id = ?

    WHERE c.status = 'published'

    GROUP BY
        c.course_id,
        c.course_title,
        c.course_number,
        c.is_locked

    ORDER BY c.course_number ASC, c.course_id ASC
");

if (!$stmt) {
    error_log("Course query failed: " . $conn->error);
    http_response_code(500);
    exit("Unable to load courses.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total = (int) $row['total_missions'];
    $completed = (int) $row['completed_missions'];

    $row['total_missions'] = $total;
    $row['completed_missions'] = $completed;

    $row['course_progress'] = $total > 0
        ? (int) round(($completed / $total) * 100)
        : 0;

    $row['course_completed'] = (
        $total > 0 && $completed >= $total
    );

    $courses[] = $row;
}

$stmt->close();

/* =========================================================
   SELECT THE COURSE
========================================================= */

$course = null;

if ($targetCourseId !== null) {
    foreach ($courses as $row) {
        if ((int) $row['course_id'] === $targetCourseId) {
            $course = $row;
            break;
        }
    }
} else {
    $course = $courses[0] ?? null;
}

if (!$course) {
    header("Location: CoursePage.php");
    exit;
}

$courseId = (int) $course['course_id'];

/* =========================================================
   FETCH LESSON PROGRESS FOR THE SELECTED COURSE
========================================================= */

$totalLessons = 0;
$completedLessons = 0;
$lessonProgressPercent = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT l.lesson_id) AS total_lessons,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(ulp.status) = 'completed'
                THEN l.lesson_id
            END
        ) AS completed_lessons

    FROM lessons l

    INNER JOIN modules md
        ON md.module_id = l.module_id

    LEFT JOIN user_lesson_progress ulp
        ON ulp.lesson_id = l.lesson_id
       AND ulp.user_id = ?

    WHERE md.course_id = ?
      AND md.status = 'published'
      AND l.status = 'published'
");

if (!$stmt) {
    error_log("Lesson progress query failed: " . $conn->error);
    http_response_code(500);
    exit(
        "Unable to load lesson progress. Check the lesson table and column names."
    );
}

$stmt->bind_param("ii", $userId, $courseId);
$stmt->execute();

$lessonProgress = $stmt->get_result()->fetch_assoc();

$totalLessons = (int) ($lessonProgress['total_lessons'] ?? 0);
$completedLessons = (int) ($lessonProgress['completed_lessons'] ?? 0);

$stmt->close();

$lessonProgressPercent = $totalLessons > 0
    ? (int) round(($completedLessons / $totalLessons) * 100)
    : 0;

/* =========================================================
   FETCH PUBLISHED MISSIONS AND INDIVIDUAL PROGRESS
========================================================= */

$missions = [];

$stmt = $conn->prepare("
    SELECT
        m.mission_id,
        m.mission_number,
        m.mission_title,
        m.mission_description,
        m.mission_type,
        m.mission_order,

        md.module_number,
        md.module_title,

        COALESCE(ump.status, 'not_started') AS student_status,
        COALESCE(ump.progress_percent, 0) AS student_progress

    FROM missions m

    INNER JOIN modules md
        ON md.module_id = m.module_id

    LEFT JOIN user_mission_progress ump
        ON ump.mission_id = m.mission_id
       AND ump.user_id = ?

    WHERE md.course_id = ?
      AND md.status = 'published'
      AND m.status = 'published'

    ORDER BY
        md.module_number ASC,
        m.mission_order ASC,
        m.mission_number ASC,
        m.mission_id ASC
");

if (!$stmt) {
    error_log("Mission list query failed: " . $conn->error);
    http_response_code(500);
    exit("Unable to load missions.");
}

$stmt->bind_param("ii", $userId, $courseId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['student_progress'] = max(
        0,
        min(100, (int) $row['student_progress'])
    );

    $missions[] = $row;
}

$stmt->close();

/* =========================================================
   SELECT THE CURRENT MISSION

   Priority:
   1. Mission passed in the URL.
   2. A mission marked in_progress.
   3. A mission with saved progress.
   4. First mission not completed.
========================================================= */

$selectedMission = null;

if ($requestedMissionId !== null) {
    foreach ($missions as $mission) {
        if ((int) $mission['mission_id'] === $requestedMissionId) {
            $selectedMission = $mission;
            break;
        }
    }

    if (!$selectedMission) {
        http_response_code(404);
        exit("Selected mission is not available in this course.");
    }
}

if ($selectedMission === null) {
    foreach ($missions as $mission) {
        if (strtolower($mission['student_status']) === 'in_progress') {
            $selectedMission = $mission;
            break;
        }
    }
}

if ($selectedMission === null) {
    foreach ($missions as $mission) {
        if (
            (int) $mission['student_progress'] > 0 &&
            strtolower($mission['student_status']) !== 'completed'
        ) {
            $selectedMission = $mission;
            break;
        }
    }
}

if ($selectedMission === null) {
    foreach ($missions as $mission) {
        if (strtolower($mission['student_status']) !== 'completed') {
            $selectedMission = $mission;
            break;
        }
    }
}

/* =========================================================
   CALCULATE OVERALL MISSION PROGRESS
========================================================= */

$totalMissions = count($missions);
$completedMissions = 0;

foreach ($missions as $mission) {
    if (strtolower($mission['student_status']) === 'completed') {
        $completedMissions++;
    }
}

$courseMissionProgress = $totalMissions > 0
    ? (int) round(($completedMissions / $totalMissions) * 100)
    : 0;

/* =========================================================
   DETERMINE MISSION DISPLAY STATUS
========================================================= */

foreach ($missions as $index => $mission) {
    $status = strtolower($mission['student_status']);

    if ($status === 'completed') {
        $missions[$index]['display_status'] = 'completed';
    } elseif (
        $selectedMission !== null &&
        (int) $mission['mission_id'] ===
        (int) $selectedMission['mission_id']
    ) {
        $missions[$index]['display_status'] = 'active';
    } else {
        $missions[$index]['display_status'] = 'locked';
    }
}

/* =========================================================
   CURRENT MISSION PROGRESS
========================================================= */

$currentMissionProgress = 0;
$currentMissionStatus = 'not_started';

if ($selectedMission !== null) {
    $currentMissionProgress = (int) $selectedMission['student_progress'];
    $currentMissionStatus = strtolower(
        $selectedMission['student_status']
    );

    if ($currentMissionStatus === 'completed') {
        $currentMissionProgress = 100;
    }
}

$currentMissionProgress = max(
    0,
    min(100, $currentMissionProgress)
);

/* =========================================================
   FETCH UNREAD NOTIFICATIONS
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

    $notificationCount = $stmt->get_result()->fetch_assoc();

    $unreadNotifications = (int) (
        $notificationCount['total'] ?? 0
    );

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
   PROFILE DETAILS
========================================================= */

$username = missionPageEscape($displayName);

$courseNumber = str_pad(
    (string) $course['course_number'],
    2,
    '0',
    STR_PAD_LEFT
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Missions</title>

    <link
        rel="stylesheet"
        href="css/Missions_style.css?v=<?php
            echo file_exists(__DIR__ . '/css/Missions_style.css')
                ? filemtime(__DIR__ . '/css/Missions_style.css')
                : time();
        ?>"
    >

    <style>
        .profile-picture img {
            width: 100%;
            height: 100%;
            display: block;
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

            <a
                href="MissionsPage.php?course_id=<?php echo $courseId; ?>"
                class="nav-item active"
            >
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

            <a href="SettingsPage.php" class="nav-item">
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

        <!-- TOP BAR -->
        <header class="topbar">

            <div>
                <h1>Missions</h1>
                <p>Choose a course and track your learning progress.</p>
            </div>

            <div class="profile">

                <!-- NOTIFICATIONS -->
                <div class="notification-container">

                    <button
                        class="notification-btn"
                        onclick="toggleNotifications()"
                        aria-label="Notifications"
                        aria-expanded="false"
                        type="button"
                    >
                        🔔

                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge">
                                <?php echo $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>

                    </button>

                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                    >

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

                                    <div class="notification-icon">📢</div>

                                    <div class="notification-content">

                                        <strong>
                                            <?php echo missionPageEscape(
                                                $note['notification_title']
                                            ); ?>
                                        </strong>

                                        <p>
                                            <?php echo missionPageEscape(
                                                $note['notification_message']
                                            ); ?>
                                        </p>

                                        <small>
                                            <?php echo missionPageEscape(
                                                $note['created_at']
                                            ); ?>
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

                <!-- DARK MODE -->
                <button
                    class="dark-mode"
                    onclick="toggleDarkMode()"
                    type="button"
                    aria-label="Toggle dark mode"
                >
                    ☾
                </button>

                <!-- PROFILE -->
                <div class="profile-picture">

                    <?php if (!empty($profileImage)): ?>

                        <img
                            src="<?php echo missionPageEscape($profileImage); ?>"
                            alt="Profile Picture"
                            onerror="this.style.display='none'; this.parentElement.textContent='<?php echo missionPageEscape($firstLetter); ?>';"
                        >

                    <?php else: ?>

                        <?php echo missionPageEscape($firstLetter); ?>

                    <?php endif; ?>

                </div>

                <div>
                    <strong><?php echo $username; ?></strong><br>
                    <small><?php echo missionPageEscape(ucfirst($userRole)); ?></small>
                </div>

            </div>

        </header>

        <!-- MISSIONS PAGE -->
        <section class="mission-page">

            <!-- COURSE TITLE -->
            <div class="course-title">

                <span>
                    COURSE <?php echo missionPageEscape($courseNumber); ?>
                </span>

                <h2>
                    <?php echo missionPageEscape($course['course_title']); ?>
                </h2>

                <p>
                    Complete the lessons and missions to make progress
                    in your selected course.
                </p>

            </div>

            <!-- COURSE DROPDOWN -->
            <div class="course-selector">

                <label for="course_id">Select Course</label>

                <form method="GET" action="MissionsPage.php">

                    <select
                        name="course_id"
                        id="course_id"
                        onchange="this.form.submit()"
                    >

                        <?php foreach ($courses as $availableCourse): ?>

                            <option
                                value="<?php echo (int) $availableCourse['course_id']; ?>"
                                <?php
                                echo (int) $availableCourse['course_id'] === $courseId
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                COURSE <?php
                                echo str_pad(
                                    (string) $availableCourse['course_number'],
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                );
                                ?>
                                -
                                <?php echo missionPageEscape(
                                    $availableCourse['course_title']
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </form>

            </div>

            <!-- LESSON PROGRESS -->
            <div class="course-progress progress-section">

                <div class="progress-section-heading">
                    <span>Lesson Progress</span>

                    <strong>
                        <?php echo $lessonProgressPercent; ?>%
                    </strong>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        style="width: <?php
                            echo $lessonProgressPercent;
                        ?>%;"
                    ></div>
                </div>

                <small>
                    <?php echo $completedLessons; ?>
                    of
                    <?php echo $totalLessons; ?>
                    lessons completed in this course
                </small>

            </div>

            <!-- CURRENT MISSION -->
            <?php if ($selectedMission !== null): ?>

                <div class="current-mission-panel">

                    <div class="current-mission-label">
                        Your Current Mission
                    </div>

                    <div class="current-mission-meta">
                        Course <?php echo missionPageEscape($courseNumber); ?>
                        · Module
                        <?php echo missionPageEscape(
                            $selectedMission['module_number']
                        ); ?>:
                        <?php echo missionPageEscape(
                            $selectedMission['module_title']
                        ); ?>
                    </div>

                    <h3>
                        <?php echo missionPageEscape(
                            $selectedMission['mission_title']
                        ); ?>
                    </h3>

                    <p>
                        <?php echo missionPageEscape(
                            $selectedMission['mission_description']
                                ?? 'Continue this mission to make progress.'
                        ); ?>
                    </p>

                    <div class="current-progress-heading">
                        <span>Mission Progress</span>

                        <strong>
                            <?php echo $currentMissionProgress; ?>%
                        </strong>
                    </div>

                    <div
                        class="current-progress-track"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="<?php echo $currentMissionProgress; ?>"
                        aria-label="Current mission progress"
                    >
                        <div
                            class="current-progress-fill"
                            style="width: <?php
                                echo $currentMissionProgress;
                            ?>%;"
                        ></div>
                    </div>

                    <?php if ($currentMissionStatus === 'completed'): ?>

                        <p style="margin-top:14px;">
                            You have completed this mission.
                        </p>

                    <?php else: ?>

                        <a
                            href="LessonPage.php?mission_id=<?php
                                echo (int) $selectedMission['mission_id'];
                            ?>"
                            class="current-mission-action"
                        >
                            Continue Mission →
                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="current-mission-panel">

                    <div class="current-mission-label">
                        All Missions Completed
                    </div>

                    <h3>Great job!</h3>

                    <p>
                        You have completed all published missions
                        available in this course.
                    </p>

                </div>

            <?php endif; ?>

            <!-- OVERALL MISSION PROGRESS -->
            <div class="course-progress progress-section">

                <div class="progress-section-heading">
                    <span>Overall Mission Progress</span>

                    <strong>
                        <?php echo $courseMissionProgress; ?>%
                    </strong>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        style="width: <?php
                            echo $courseMissionProgress;
                        ?>%;"
                    ></div>
                </div>

                <small>
                    <?php echo $completedMissions; ?>
                    of
                    <?php echo $totalMissions; ?>
                    missions completed in this course
                </small>

            </div>

            <!-- MISSION LIST -->
            <div class="mission-list">

                <?php if (empty($missions)): ?>

                    <div class="mission-card">

                        <div class="mission-info">
                            <h3>No missions available yet</h3>

                            <p>
                                Published missions for this course
                                will appear here.
                            </p>
                        </div>

                    </div>

                <?php else: ?>

                    <?php foreach ($missions as $mission): ?>

                        <?php
                        $displayStatus = $mission['display_status'];

                        $isSelected = (
                            $selectedMission !== null &&
                            (int) $mission['mission_id'] ===
                            (int) $selectedMission['mission_id']
                        );

                        $displayNumber = str_pad(
                            (string) $mission['mission_number'],
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                        $missionLabel = 'MISSION ' . $displayNumber;

                        $moduleLabel = 'Module '
                            . $mission['module_number']
                            . ': '
                            . $mission['module_title'];

                        $cardClass = $displayStatus;

                        if ($isSelected) {
                            $cardClass .= ' selected-mission';
                        }

                        $cardProgress = (int) $mission['student_progress'];

                        if ($displayStatus === 'completed') {
                            $cardProgress = 100;
                        }
                        ?>

                        <div class="mission-card <?php
                            echo missionPageEscape($cardClass);
                        ?>">

                            <div class="mission-icon">

                                <?php if ($displayStatus === 'completed'): ?>
                                    ✓
                                <?php else: ?>
                                    <?php echo missionPageEscape(
                                        $displayNumber
                                    ); ?>
                                <?php endif; ?>

                            </div>

                            <div class="mission-info">

                                <span>
                                    <?php echo missionPageEscape(
                                        $missionLabel
                                    ); ?>
                                    ·
                                    <?php echo missionPageEscape(
                                        $moduleLabel
                                    ); ?>
                                </span>

                                <h3>
                                    <?php echo missionPageEscape(
                                        $mission['mission_title']
                                    ); ?>
                                </h3>

                                <p>
                                    <?php echo missionPageEscape(
                                        $mission['mission_description']
                                            ?? 'No description available.'
                                    ); ?>
                                </p>

                                <?php if ($isSelected): ?>

                                    <small>
                                        You are currently viewing this mission.
                                    </small>

                                    <div class="mission-card-progress">

                                        <div
                                            class="mission-card-progress-heading"
                                        >
                                            <span>Mission Progress</span>

                                            <strong>
                                                <?php echo $cardProgress; ?>%
                                            </strong>
                                        </div>

                                        <div
                                            class="mission-card-progress-track"
                                        >
                                            <div
                                                class="mission-card-progress-fill"
                                                style="width: <?php
                                                    echo $cardProgress;
                                                ?>%;"
                                            ></div>
                                        </div>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <?php if ($displayStatus === 'completed'): ?>

                                <div class="mission-status">
                                    Completed
                                </div>

                            <?php elseif ($isSelected): ?>

                                <a
                                    href="LessonPage.php?mission_id=<?php
                                        echo (int) $mission['mission_id'];
                                    ?>"
                                    class="mission-button"
                                >
                                    Continue →
                                </a>

                            <?php else: ?>

                                <div class="mission-status">
                                    <?php if (
                                        strtolower($mission['student_status'])
                                        === 'in_progress'
                                    ): ?>
                                        In Progress
                                    <?php else: ?>
                                        🔒 Locked
                                    <?php endif; ?>
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode-active");
    }

    function toggleNotifications() {
        const dropdown = document.getElementById("notificationDropdown");
        const button = document.querySelector(".notification-btn");

        if (!dropdown || !button) {
            return;
        }

        const isOpen = dropdown.classList.toggle("show");

        button.setAttribute("aria-expanded", String(isOpen));
    }

    document.addEventListener("click", function(event) {
        const container = document.querySelector(".notification-container");
        const dropdown = document.getElementById("notificationDropdown");
        const button = document.querySelector(".notification-btn");

        if (
            container &&
            dropdown &&
            !container.contains(event.target)
        ) {
            dropdown.classList.remove("show");

            if (button) {
                button.setAttribute("aria-expanded", "false");
            }
        }
    });
</script>

</body>
</html>
