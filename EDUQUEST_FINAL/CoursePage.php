
<?php

/* =========================================================
   INITIALIZATION
========================================================= */

require_once __DIR__ . '/init.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$conn->set_charset("utf8mb4");

/* =========================================================
   HELPER
========================================================= */

function coursePageEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   GET LOGGED-IN STUDENT OR ADMIN
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

    if (!$stmt) {
        error_log("CoursePage user query error: " . $conn->error);
        die("Unable to load your account.");
    }

    $stmt->bind_param("i", $sessionUserId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

} else {

    $loginIdentifier = $_SESSION['email']
        ?? $_SESSION['user_email']
        ?? $_SESSION['username']
        ?? null;

    if ($loginIdentifier !== null) {

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

        if (!$stmt) {
            error_log("CoursePage user lookup error: " . $conn->error);
            die("Unable to load your account.");
        }

        $stmt->bind_param(
            "ss",
            $loginIdentifier,
            $loginIdentifier
        );

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

/* =========================================================
   VALIDATE ACCOUNT BEFORE DISPLAYING HTML
========================================================= */

if (!$user) {
    header("Location: login.php");
    exit;
}

$userId = (int) $user['user_id'];

$displayName = !empty($user['username'])
    ? $user['username']
    : $user['full_name'];

$username = coursePageEscape($displayName);

$firstLetter = strtoupper(
    substr($displayName ?: 'U', 0, 1)
);

$userRole = $user['role'];

/* =========================================================
   PROFILE IMAGE
========================================================= */

$profileImage = trim(
    (string) ($user['profile_image'] ?? '')
);

if (!empty($profileImage)) {

    $profileImage = str_replace("\\", "/", $profileImage);

    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, "data:") !== 0
    ) {

        $profileImage = ltrim($profileImage, "/");

        if (
            strpos(
                $profileImage,
                "uploads/profile_images/"
            ) === 0
        ) {

            $profileImage = "Admin_Portal/" . $profileImage;

        }
    }
}

/* =========================================================
   FETCH ALL PUBLISHED COURSES AND LESSON PROGRESS
========================================================= */

$courses = [];

$sql = "
    SELECT
        c.course_id,
        c.course_title,
        c.course_description,
        c.course_number,
        c.is_locked,

        COUNT(DISTINCT mo.module_id) AS total_modules,

        COUNT(DISTINCT l.lesson_id) AS total_lessons,

        COUNT(
            DISTINCT CASE
                WHEN ulp.status = 'completed'
                THEN l.lesson_id
            END
        ) AS completed_lessons

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
        c.is_locked

    ORDER BY
        c.course_number ASC,
        c.course_id ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("CoursePage course query error: " . $conn->error);
    die("Unable to load courses. Please check the deployment logs.");
}

$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    error_log("CoursePage course execution error: " . $stmt->error);
    die("Unable to retrieve courses.");
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $totalModules = (int) $row['total_modules'];

    $totalLessons = (int) $row['total_lessons'];

    $completedLessons = (int) $row['completed_lessons'];

    $progress = 0;

    if ($totalLessons > 0) {

        $progress = (
            $completedLessons / $totalLessons
        ) * 100;

    }

    $progress = max(0, min(100, $progress));

    $row['total_modules'] = $totalModules;

    $row['total_lessons'] = $totalLessons;

    $row['completed_lessons'] = $completedLessons;

    $row['progress_percent'] = round($progress, 2);

    $row['course_completed'] = (
        $totalLessons > 0 &&
        $completedLessons >= $totalLessons
    );

    $courses[] = $row;

}

$stmt->close();

/* =========================================================
   DETERMINE COURSE UNLOCKING
========================================================= */

$previousCourseCompleted = true;

foreach ($courses as $index => $course) {

    $isLocked = !$previousCourseCompleted;

    $courses[$index]['display_locked'] = $isLocked;

    $previousCourseCompleted = (bool) $course['course_completed'];
}
/* =========================================================
   FETCH UNREAD NOTIFICATIONS
========================================================= */

$unreadNotifications = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE (user_id = ? OR user_id IS NULL)
      AND is_read = 0
");

if ($stmt) {

    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {

        $notificationCount = $stmt->get_result()->fetch_assoc();

        $unreadNotifications = (int) (
            $notificationCount['total'] ?? 0
        );

    } else {

        error_log(
            "CoursePage notification count execution error: "
            . $stmt->error
        );

    }

    $stmt->close();

} else {

    error_log(
        "CoursePage notification count error: "
        . $conn->error
    );

}

/* =========================================================
   FETCH LATEST NOTIFICATIONS

   Uses actual notification columns:
   message, is_read, created_at
========================================================= */

$notifications = [];

$stmt = $conn->prepare("
    SELECT
        message AS notification_message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
       OR user_id IS NULL
    ORDER BY created_at DESC
    LIMIT 5
");

if ($stmt) {

    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $notifications[] = $row;

        }

    } else {

        error_log(
            "CoursePage notification list execution error: "
            . $stmt->error
        );

    }

    $stmt->close();

} else {

    error_log(
        "CoursePage notification list error: "
        . $conn->error
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Courses</title>

    <link
        rel="stylesheet"
        href="css/Course_style.css?v=<?php
            echo is_file(__DIR__ . '/css/Course_style.css')
                ? filemtime(__DIR__ . '/css/Course_style.css')
                : '1';
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

            <a href="CoursePage.php" class="nav-item active">
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

                <h1>Courses</h1>

                <p>Explore your learning missions.</p>

            </div>

            <div class="profile">

                <!-- NOTIFICATIONS -->

                <div class="notification-container">

                    <button
                        type="button"
                        class="notification-btn"
                        onclick="toggleNotifications()"
                        aria-label="Notifications"
                        aria-expanded="false"
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

                                    <div class="notification-icon">
                                        📢
                                    </div>

                                    <div class="notification-content">

                                        <strong>Notification</strong>

                                        <p>
                                            <?php
                                            echo coursePageEscape(
                                                $note['notification_message']
                                            );
                                            ?>
                                        </p>

                                        <small>
                                            <?php
                                            echo coursePageEscape(
                                                $note['created_at']
                                            );
                                            ?>
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
                    type="button"
                    class="dark-mode"
                    onclick="toggleDarkMode()"
                    aria-label="Toggle dark mode"
                >
                    ☾
                </button>

                <!-- PROFILE -->

                <div class="profile-info">

                    <div class="profile-picture">

                        <?php if (!empty($profileImage)): ?>

                            <img
                                src="<?php echo coursePageEscape($profileImage); ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?php echo coursePageEscape($firstLetter); ?>';"
                            >

                        <?php else: ?>

                            <?php echo coursePageEscape($firstLetter); ?>

                        <?php endif; ?>

                    </div>

                    <div>

                        <strong>
                            <?php echo $username; ?>
                        </strong>

                        <small>
                            <?php
                            echo coursePageEscape(
                                ucfirst($userRole)
                            );
                            ?>
                        </small>

                    </div>

                </div>

            </div>

        </header>

        <!-- COURSE LIST -->

        <section class="course-page">

            <div class="page-heading">

                <h2>Your Courses</h2>

                <p>
                    Complete lessons to unlock new learning content.
                </p>

            </div>

            <div class="course-list">

                <?php if (!empty($courses)): ?>

                    <?php foreach ($courses as $course): ?>

                        <?php

                        $courseId = (int) $course['course_id'];

                        $courseTitle = coursePageEscape(
                            $course['course_title']
                        );

                        $courseDescription = coursePageEscape(
                            $course['course_description'] ?? ''
                        );

                        $courseNumber = (int) $course['course_number'];

                        $totalLessons = (int) $course['total_lessons'];

                        $completedLessons = (int) $course['completed_lessons'];

                        $progress = (float) $course['progress_percent'];

                        $isLocked = (bool) $course['display_locked'];

                        ?>

                        <div class="course-card <?php
                            echo $isLocked ? 'locked' : '';
                        ?>">

                            <div class="course-number">

                                COURSE <?php
                                    echo str_pad(
                                        (string) $courseNumber,
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    );
                                ?>

                            </div>

                            <h3>
                                <?php echo $courseTitle; ?>
                            </h3>

                            <p>
                                <?php echo $courseDescription; ?>
                            </p>

                            <?php if ($isLocked): ?>

                                <!-- LOCKED COURSE -->

                                <div class="locked-text">

                                    🔒 Complete the previous course to unlock this course.

                                </div>

                                <div class="course-info">

                                    <span>
                                        <?php echo $totalLessons; ?> Lessons
                                    </span>

                                    <span>
                                        <?php echo $completedLessons; ?>
                                        / <?php echo $totalLessons; ?> Completed
                                    </span>

                                </div>

                                <div class="course-bottom">

                                    <strong>Locked</strong>

                                    <button type="button" disabled>
                                        Locked
                                    </button>

                                </div>

                            <?php else: ?>

                                <!-- UNLOCKED COURSE -->

                                <div class="course-info">

                                    <span>
                                        <?php echo $totalLessons; ?> Lessons
                                    </span>

                                    <span>
                                        <?php echo $completedLessons; ?>
                                        / <?php echo $totalLessons; ?> Completed
                                    </span>

                                </div>

                                <div
                                    class="progress-bar"
                                    role="progressbar"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="<?php
                                        echo (int) round($progress);
                                    ?>"
                                >

                                    <div
                                        class="progress-fill"
                                        style="width: <?php
                                            echo $progress;
                                        ?>%;"
                                    ></div>

                                </div>

                                <div class="course-bottom">

                                    <strong>
                                        <?php
                                        echo (int) round($progress);
                                        ?>%
                                    </strong>

                                    <a href="LessonPage.php?course_id=<?php
                                        echo $courseId;
                                    ?>">
                                        Continue →
                                    </a>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>No published courses are available right now.</p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<!-- JAVASCRIPT -->

<script>

    /* =====================================================
       DARK MODE
    ===================================================== */

    function toggleDarkMode() {

        document.body.classList.toggle("dark-mode-active");

    }

    /* =====================================================
       NOTIFICATIONS DROPDOWN
    ===================================================== */

    function toggleNotifications() {

        const dropdown = document.getElementById(
            "notificationDropdown"
        );

        const button = document.querySelector(
            ".notification-btn"
        );

        if (!dropdown || !button) {
            return;
        }

        const isOpen = dropdown.classList.toggle("show");

        button.setAttribute(
            "aria-expanded",
            String(isOpen)
        );

    }

    /* =====================================================
       CLOSE NOTIFICATIONS WHEN CLICKING OUTSIDE
    ===================================================== */

    document.addEventListener("click", function(event) {

        const container = document.querySelector(
            ".notification-container"
        );

        const dropdown = document.getElementById(
            "notificationDropdown"
        );

        const button = document.querySelector(
            ".notification-btn"
        );

        if (
            container &&
            dropdown &&
            !container.contains(event.target)
        ) {

            dropdown.classList.remove("show");

            if (button) {

                button.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }

    });

</script>

</body>
</html>
