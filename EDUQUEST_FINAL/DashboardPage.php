
<?php
if (headers_sent($file, $line)) {
    die("Output started in $file on line $line");
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
require_once "student_auth.php";

$conn->set_charset("utf8mb4");

/* =============================
   HELPER
============================= */

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/* =============================
   NOTIFICATIONS TABLE
============================= */

@$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        audience VARCHAR(100) DEFAULT 'All students',
        status VARCHAR(50) DEFAULT 'draft',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

try {
    $conn->query("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Notification'");
} catch (mysqli_sql_exception $e) {}

try {
    $conn->query("ALTER TABLE notifications ADD COLUMN message TEXT NOT NULL DEFAULT ''");
} catch (mysqli_sql_exception $e) {}

try {
    $conn->query("ALTER TABLE notifications ADD COLUMN audience VARCHAR(100) DEFAULT 'All students'");
} catch (mysqli_sql_exception $e) {}

try {
    $conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0");
} catch (mysqli_sql_exception $e) {}

/* =============================
   VALIDATE STUDENT AND ADMIN
============================= */

$user = null;

if (!empty($_SESSION["user_id"])) {
    $stmt = $conn->prepare("
        SELECT user_id, full_name, username, email, profile_image, role
        FROM users
        WHERE user_id = ?
          AND role IN ('student', 'admin')
          AND account_status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} else {
    $loginIdentifier = $_SESSION["email"]
        ?? $_SESSION["user_email"]
        ?? $_SESSION["username"]
        ?? null;

    if ($loginIdentifier !== null) {
        $stmt = $conn->prepare("
            SELECT user_id, full_name, username, email, profile_image, role
            FROM users
            WHERE (email = ? OR username = ?)
              AND role IN ('student', 'admin')
              AND account_status = 'active'
            LIMIT 1
        ");

        $stmt->bind_param("ss", $loginIdentifier, $loginIdentifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$user) {
    header("Location: login.php");
    exit;
}

$userId = (int) $user["user_id"];

$displayName = !empty($user["username"])
    ? $user["username"]
    : $user["full_name"];

$firstLetter = strtoupper(substr($displayName, 0, 1));

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

/* =============================
   FETCH COURSES AND LESSON PROGRESS
============================= */

$courses = [];
$unlockedCourseIds = [];
$previousCourseCompleted = true;

$stmt = $conn->prepare("
    SELECT
        c.course_id,
        c.course_title,
        c.course_description,
        c.course_number,
        c.thumbnail,
        c.is_locked,

        COUNT(DISTINCT l.lesson_id) AS total_lessons,

        COUNT(DISTINCT CASE
            WHEN ulp.status = 'completed'
            THEN l.lesson_id
        END) AS completed_lessons

    FROM courses c

    LEFT JOIN modules mo
        ON mo.course_id = c.course_id
       AND mo.status = 'published'

    LEFT JOIN lessons l
        ON l.module_id = mo.module_id
       AND l.status = 'published'

    LEFT JOIN user_lesson_progress ulp
        ON ulp.lesson_id = l.lesson_id
       AND ulp.user_id = ?

    WHERE c.status = 'published'

    GROUP BY
        c.course_id,
        c.course_title,
        c.course_description,
        c.course_number,
        c.thumbnail,
        c.is_locked

    ORDER BY c.course_number ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $totalLessons = (int) $row["total_lessons"];
    $completedLessons = (int) $row["completed_lessons"];

    $progress = 0;

    if ($totalLessons > 0) {
        $progress = ($completedLessons / $totalLessons) * 100;
    }

    $row["total_lessons"] = $totalLessons;
    $row["completed_lessons"] = $completedLessons;
    $row["progress_percent"] = round(max(0, min(100, $progress)), 2);

    // A course is complete when all its published lessons are completed.
    $courseCompleted = (
        $totalLessons > 0 &&
        $completedLessons >= $totalLessons
    );

    // Unlock the first published course, then unlock courses in order.
    $row["is_locked"] = $previousCourseCompleted ? 0 : 1;

    if ((int) $row["is_locked"] === 0) {
        $unlockedCourseIds[] = (int) $row["course_id"];
    }

    $courses[] = $row;

    // An empty course does not unlock the next course.
    $previousCourseCompleted = $courseCompleted;
}

$stmt->close();

/* =============================
   COUNT COMPLETED COURSES
   BASED ON COMPLETED LESSONS
============================= */

$coursesCompleted = 0;

foreach ($courses as $course) {
    if (
        (int) $course["total_lessons"] > 0 &&
        (int) $course["completed_lessons"] >= (int) $course["total_lessons"]
    ) {
        $coursesCompleted++;
    }
}

/* =============================
   FETCH CURRENT MISSION
============================= */

$currentMission = null;

if (!empty($unlockedCourseIds)) {
    $stmt = $conn->prepare("
        SELECT
            m.mission_id,
            m.mission_title,
            m.mission_description,
            m.mission_number,
            m.mission_type,
            mo.module_title,
            c.course_id,
            c.course_title,
            COALESCE(ump.progress_percent, 0) AS progress_percent,
            COALESCE(ump.status, 'not_started') AS mission_status

        FROM missions m

        INNER JOIN modules mo
            ON m.module_id = mo.module_id

        INNER JOIN courses c
            ON mo.course_id = c.course_id

        LEFT JOIN user_mission_progress ump
            ON ump.mission_id = m.mission_id
           AND ump.user_id = ?

        WHERE m.status = 'published'
          AND mo.status = 'published'
          AND c.status = 'published'
          AND (
              ump.status IS NULL
              OR ump.status IN ('not_started', 'in_progress')
          )

        ORDER BY
            CASE
                WHEN ump.status = 'in_progress' THEN 0
                ELSE 1
            END,
            c.course_number ASC,
            mo.module_number ASC,
            m.mission_order ASC
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (in_array((int) $row["course_id"], $unlockedCourseIds, true)) {
            $currentMission = $row;
            break;
        }
    }

    $stmt->close();
}

/* =============================
   MISSION STATISTICS
============================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT m.mission_id) AS total_missions,
        COUNT(DISTINCT CASE
            WHEN ump.status = 'completed' THEN m.mission_id
        END) AS completed_missions

    FROM missions m

    INNER JOIN modules mo
        ON m.module_id = mo.module_id

    INNER JOIN courses c
        ON mo.course_id = c.course_id

    LEFT JOIN user_mission_progress ump
        ON ump.mission_id = m.mission_id
       AND ump.user_id = ?

    WHERE m.status = 'published'
      AND mo.status = 'published'
      AND c.status = 'published'
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$missionStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalMissions = (int) ($missionStats["total_missions"] ?? 0);
$completedMissions = (int) ($missionStats["completed_missions"] ?? 0);

/* =============================
   CHALLENGES PASSED
============================= */

$challengesPassed = 0;

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT ca.challenge_id) AS total
    FROM challenge_attempts ca

    INNER JOIN challenges ch
        ON ca.challenge_id = ch.challenge_id

    WHERE ca.user_id = ?
      AND ca.status IN ('submitted', 'graded')
      AND ca.score IS NOT NULL
      AND ch.total_points > 0
      AND (ca.score / ch.total_points * 100) >= ch.passing_score
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$challengesPassed = (int) (
    $stmt->get_result()->fetch_assoc()["total"] ?? 0
);

$stmt->close();

/* =============================
   ACHIEVEMENTS
============================= */

$achievementsUnlocked = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM user_achievements
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$achievementsUnlocked = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);
$stmt->close();

/* =============================
   NOTIFICATIONS
============================= */

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

$stmt->bind_param("i", $userId);
$stmt->execute();
$unreadNotifications = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);
$stmt->close();

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

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduQuest Dashboard</title>
    <link rel="stylesheet" href="css/Dashboard_style.css">

    <style>
        .avatar img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>

<body>

<div class="dashboard-layout">

    <aside class="sidebar">

        <a href="DashboardPage.php" class="brand">
            <span>EDU</span><strong>QUEST</strong>
        </a>

        <p class="menu-label">MAIN MENU</p>

        <nav class="sidebar-nav">

            <a href="DashboardPage.php" class="nav-link active">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="CoursePage.php" class="nav-link">
                <span class="nav-icon">▤</span>
                <span>Courses</span>
            </a>

            <a href="MissionsPage.php" class="nav-link">
                <span class="nav-icon">⚑</span>
                <span>Missions</span>
            </a>

            <a href="ProgressPage.php" class="nav-link">
                <span class="nav-icon">▥</span>
                <span>Progress</span>
            </a>

            <a href="AchievementsPage.php" class="nav-link">
                <span class="nav-icon">★</span>
                <span>Achievements</span>
            </a>

            <a href="ActivityRecords.php" class="nav-link">
                <span class="nav-icon">📝</span>
                <span>Activity Records</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="SettingsPage.php" class="nav-link">
                <span class="nav-icon">⚙</span>
                <span>Settings</span>
            </a>

            <a href="logout.php" class="nav-link">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>

    <div class="main-area">

        <header class="topbar">

            <div class="welcome-text">
                <h1>Good day, <?= e($displayName) ?>!</h1>
                <p>Ready for your next mission?</p>
            </div>

            <div class="topbar-actions">

                <div class="notification-wrap">

                    <button
                        type="button"
                        class="icon-button"
                        id="notificationButton"
                        aria-label="Notifications"
                        aria-expanded="false"
                    >
                        🔔

                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>
                    </button>

                    <div class="notification-dropdown" id="notificationDropdown">

                        <h3>Notifications</h3>

                        <?php if (empty($notifications)): ?>

                            <p style="padding: 10px; font-size: 13px; color: #64748b;">
                                No notifications yet.
                            </p>

                        <?php else: ?>

                            <?php foreach ($notifications as $notification): ?>

                                <div class="notification-item">

                                    <strong>
                                        <?= e($notification["notification_title"]) ?>
                                    </strong>

                                    <p>
                                        <?= e($notification["notification_message"]) ?>
                                    </p>

                                    <small>
                                        <?= e($notification["created_at"]) ?>
                                    </small>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        <a href="NotificationPage.php" class="view-link">
                            View all notifications →
                        </a>

                    </div>

                </div>

                <button
                    type="button"
                    class="icon-button"
                    id="darkModeButton"
                    aria-label="Toggle dark mode"
                >☾</button>

                <div class="profile">

                    <div class="avatar">
                        <?php if (!empty($profileImage)): ?>
                            <img
                                src="<?= e($profileImage) ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?= e($firstLetter) ?>';">
                        <?php else: ?>
                            <?= e($firstLetter) ?>
                        <?php endif; ?>
                    </div>

                    <div class="profile-info">
                        <strong><?= e($displayName) ?></strong>
                        <span><?= e(ucfirst($user["role"])) ?></span>
                    </div>

                </div>
            </div>

        </header>

        <main class="dashboard-content">

            <section class="page-heading">
                <h2>Your Learning Dashboard</h2>
                <p>Track your progress and continue your learning journey.</p>
            </section>

            <!-- CURRENT MISSION -->

            <section class="continue-section">

                <div class="continue-info">

                    <span class="section-tag">YOUR CURRENT MISSION</span>

                    <?php if ($currentMission): ?>

                        <h2><?= e($currentMission["mission_title"]) ?></h2>

                        <p class="mission-course">
                            <?= e($currentMission["course_title"]) ?>
                            ·
                            <?= e($currentMission["module_title"]) ?>
                        </p>

                        <p class="mission-description">
                            <?= e(
                                $currentMission["mission_description"]
                                ?: "Continue this mission to make progress in your course."
                            ) ?>
                        </p>

                        <div class="mission-progress-heading">
                            <span>Mission Progress</span>

                            <strong>
                                <?= round((float) $currentMission["progress_percent"]) ?>%
                            </strong>
                        </div>

                        <div class="progress-track">
                            <div
                                class="progress-fill"
                                style="width: <?= max(0, min(100, (float) $currentMission["progress_percent"])) ?>%;"
                            ></div>
                        </div>

                        <a
                            href="MissionsPage.php?mission_id=<?= (int) $currentMission["mission_id"] ?>"
                            class="primary-button"
                        >
                            Continue Mission <span>→</span>
                        </a>

                    <?php else: ?>

                        <h2>You're All Caught Up!</h2>

                        <p class="mission-description">
                            There are no available unfinished missions right now.
                            Check your courses for more learning activities.
                        </p>

                        <a href="CoursePage.php" class="primary-button">
                            View Courses <span>→</span>
                        </a>

                    <?php endif; ?>

                </div>

                <div class="mission-illustration">
                    <div class="mission-circle">
                        <span>⚑</span>
                    </div>

                    <p>Every mission brings you closer to your goal.</p>
                </div>

            </section>

            <!-- COURSES -->

            <section class="courses-section">

                <div class="section-heading">

                    <div>
                        <h2>Your Courses</h2>
                        <p>Choose a course and continue learning.</p>
                    </div>

                    <a href="CoursePage.php" class="view-link">
                        View all →
                    </a>

                </div>

                <div class="course-grid">

                    <?php if (empty($courses)): ?>

                        <p class="empty-state">
                            No published courses are available yet.
                        </p>

                    <?php else: ?>

                        <?php foreach ($courses as $course): ?>

                            <?php
                            $isLocked = (int) $course["is_locked"] === 1;
                            $progress = (float) $course["progress_percent"];
                            ?>

                            <article class="course-card <?= $isLocked ? "locked-card" : "" ?>">

                                <div class="course-top">

                                    <span class="course-number">
                                        <?= sprintf("%02d", (int) $course["course_number"]) ?>
                                    </span>

                                    <span class="course-status <?= $isLocked ? "locked-status" : "" ?>">
                                        <?= $isLocked ? "Locked" : "Available" ?>
                                    </span>

                                </div>

                                <div class="course-icon <?= $isLocked ? "gray-icon" : "green-icon" ?>">
                                    <?php if ($isLocked): ?>
                                        🔒
                                    <?php else: ?>
                                        &lt;/&gt;
                                    <?php endif; ?>
                                </div>

                                <h3><?= e($course["course_title"]) ?></h3>

                                <p>
                                    <?= e(
                                        $course["course_description"]
                                        ?: "Start learning and complete the lessons in this course."
                                    ) ?>
                                </p>

                                <div class="course-footer">

                                    <span>
                                        <?= (int) $course["completed_lessons"] ?>
                                        /
                                        <?= (int) $course["total_lessons"] ?>
                                        Lessons
                                    </span>

                                    <strong>
                                        <?= $isLocked ? "Locked" : round($progress) . "%" ?>
                                    </strong>

                                </div>

                                <div class="progress-track">
                                    <div
                                        class="progress-fill"
                                        style="width: <?= $isLocked ? 0 : $progress ?>%;"
                                    ></div>
                                </div>

                                <?php if (!$isLocked): ?>

                                    <a
                                        href="LessonPage.php?course_id=<?= (int) $course["course_id"] ?>"
                                        class="course-link"
                                    >
                                        Continue Learning →
                                    </a>

                                <?php else: ?>

                                    <span class="course-link disabled-link">
                                        Complete Previous Course
                                    </span>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <!-- PROGRESS STATISTICS -->

            <section class="progress-section">

                <div class="section-heading">
                    <div>
                        <h2>Your Progress</h2>
                        <p>Your learning activity from your account.</p>
                    </div>
                </div>

                <div class="stats-grid">

                    <article class="stat-card">
                        <div class="stat-icon">⚑</div>

                        <div>
                            <p>Missions Completed</p>

                            <h3>
                                <?= $completedMissions ?>
                                <span>/ <?= $totalMissions ?></span>
                            </h3>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">✓</div>

                        <div>
                            <p>Challenges Passed</p>
                            <h3><?= $challengesPassed ?></h3>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">★</div>

                        <div>
                            <p>Achievements Unlocked</p>
                            <h3><?= $achievementsUnlocked ?></h3>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">▤</div>

                        <div>
                            <p>Courses Completed</p>
                            <h3><?= $coursesCompleted ?></h3>
                        </div>
                    </article>

                </div>

            </section>

        </main>

    </div>

</div>

<script>
    const darkModeButton = document.getElementById("darkModeButton");
    const notificationButton = document.getElementById("notificationButton");
    const notificationDropdown = document.getElementById("notificationDropdown");

    // Restore saved dark-mode preference.
    if (localStorage.getItem("eduquest-dark-mode") === "enabled") {
        document.body.classList.add("dark-mode-active");
        darkModeButton.textContent = "☀";
    }

    darkModeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode-active");

        const enabled = document.body.classList.contains("dark-mode-active");

        darkModeButton.textContent = enabled ? "☀" : "☾";

        localStorage.setItem(
            "eduquest-dark-mode",
            enabled ? "enabled" : "disabled"
        );
    });

    notificationButton.addEventListener("click", function (event) {
        event.stopPropagation();

        const isOpen = notificationDropdown.classList.toggle("show");

        notificationButton.setAttribute("aria-expanded", String(isOpen));
    });

    notificationDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".notification-wrap")) {
            notificationDropdown.classList.remove("show");
            notificationButton.setAttribute("aria-expanded", "false");
        }
    });
</script>

</body>
</html>
