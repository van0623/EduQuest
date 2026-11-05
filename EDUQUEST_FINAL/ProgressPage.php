
<?php
session_start();

require_once __DIR__ . '/Admin_Portal/db_connect.php';

$conn->set_charset("utf8mb4");

/* =========================================
   GET LOGGED-IN STUDENT
========================================= */

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

    $profileImage = str_replace("\\", "/", $profileImage);

    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, "data:") !== 0
    ) {
        $profileImage = ltrim($profileImage, "/");

        if (strpos($profileImage, "uploads/profile_images/") === 0) {
            $profileImage = "Admin_Portal/" . $profileImage;
        }
    }
}


/* =========================================
   MISSION STATISTICS
========================================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT m.mission_id) AS total_missions,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(ump.status)) = 'completed'
                THEN m.mission_id
            END
        ) AS completed_missions

    FROM missions m

    INNER JOIN modules md
        ON md.module_id = m.module_id

    INNER JOIN courses c
        ON c.course_id = md.course_id

    LEFT JOIN user_mission_progress ump
        ON ump.mission_id = m.mission_id
       AND ump.user_id = ?

    WHERE m.status = 'published'
      AND md.status = 'published'
      AND c.status = 'published'
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$missionStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalMissions = (int) ($missionStats['total_missions'] ?? 0);
$missionsCompleted = (int) ($missionStats['completed_missions'] ?? 0);


/* =========================================
   CHALLENGE STATISTICS
========================================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT ch.challenge_id) AS total_challenges,

        COUNT(
            DISTINCT CASE
                WHEN ca.feedback LIKE '%Result: PASSED.%'
                THEN ch.challenge_id
            END
        ) AS completed_challenges

    FROM challenges ch

    INNER JOIN missions m
        ON m.mission_id = ch.mission_id

    INNER JOIN modules md
        ON md.module_id = m.module_id

    INNER JOIN courses c
        ON c.course_id = md.course_id

    LEFT JOIN challenge_attempts ca
        ON ca.challenge_id = ch.challenge_id
       AND ca.user_id = ?

    WHERE ch.status = 'published'
      AND m.status = 'published'
      AND md.status = 'published'
      AND c.status = 'published'
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$challengeStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalChallenges = (int) ($challengeStats['total_challenges'] ?? 0);
$challengesCompleted = (int) ($challengeStats['completed_challenges'] ?? 0);


/* =========================================
   COURSE LESSON PROGRESS
========================================= */

$stmt = $conn->prepare("
    SELECT
        c.course_id,
        c.course_title,
        c.course_number,
        c.is_locked,

        COUNT(DISTINCT l.lesson_id) AS total_lessons,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(ulp.status)) = 'completed'
                THEN l.lesson_id
            END
        ) AS completed_lessons

    FROM courses c

    LEFT JOIN modules md
        ON md.course_id = c.course_id
       AND md.status = 'published'

    LEFT JOIN lessons l
        ON l.module_id = md.module_id
       AND l.status = 'published'

    LEFT JOIN user_lesson_progress ulp
        ON ulp.lesson_id = l.lesson_id
       AND ulp.user_id = ?

    WHERE c.status = 'published'

    GROUP BY
        c.course_id,
        c.course_title,
        c.course_number,
        c.is_locked

    ORDER BY
        c.course_number ASC,
        c.course_id ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$courses = [];

while ($row = $result->fetch_assoc()) {

    $total = (int) $row['total_lessons'];
    $completed = (int) $row['completed_lessons'];

    $row['total_lessons'] = $total;
    $row['completed_lessons'] = $completed;

    $row['progress_percent'] = $total > 0
        ? (int) round(($completed / $total) * 100)
        : 0;

    $row['display_locked'] = false;

    $courses[] = $row;
}

$stmt->close();


/* =========================================
   COURSE LOCKING

   Course 1 is always unlocked.
   Each next course unlocks when every
   published lesson in the previous course
   has been completed.
========================================= */

foreach ($courses as $index => &$course) {

    if ($index === 0) {
        $course['display_locked'] = false;
        continue;
    }

    $previousCourse = $courses[$index - 1];

    $previousTotal = (int) $previousCourse['total_lessons'];
    $previousCompleted = (int) $previousCourse['completed_lessons'];

    $previousCourseCompleted = (
        $previousTotal > 0 &&
        $previousCompleted >= $previousTotal
    );

    $course['display_locked'] = !$previousCourseCompleted;
}

unset($course);


/* =========================================
   COMPLETED COURSES
========================================= */

$coursesCompleted = 0;

foreach ($courses as $course) {

    $total = (int) $course['total_lessons'];
    $completed = (int) $course['completed_lessons'];

    if ($total > 0 && $completed >= $total) {
        $coursesCompleted++;
    }
}


/* =========================================
   MISSION PROGRESS PER COURSE
========================================= */

$stmt = $conn->prepare("
    SELECT
        c.course_id,
        c.course_title,
        c.course_number,

        COUNT(DISTINCT m.mission_id) AS total_missions,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(ump.status)) = 'completed'
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
        c.course_number

    ORDER BY
        c.course_number ASC,
        c.course_id ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$missionProgressResult = $stmt->get_result();
$missionProgressCourses = [];

while ($row = $missionProgressResult->fetch_assoc()) {

    $total = (int) $row['total_missions'];
    $completed = (int) $row['completed_missions'];

    $row['total_missions'] = $total;
    $row['completed_missions'] = $completed;

    $row['progress_percent'] = $total > 0
        ? (int) round(($completed / $total) * 100)
        : 0;

    $missionProgressCourses[] = $row;
}

$stmt->close();


/* =========================================
   MATCH MISSION PROGRESS WITH EACH COURSE
========================================= */

$missionProgressByCourse = [];

foreach ($missionProgressCourses as $missionCourse) {

    $courseId = (int) $missionCourse['course_id'];

    $missionProgressByCourse[$courseId] = $missionCourse;
}


/* =========================================
   OVERALL LESSON PROGRESS
========================================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT l.lesson_id) AS total_lessons,

        COUNT(
            DISTINCT CASE
                WHEN LOWER(TRIM(ulp.status)) = 'completed'
                THEN l.lesson_id
            END
        ) AS completed_lessons

    FROM lessons l

    INNER JOIN modules md
        ON md.module_id = l.module_id

    INNER JOIN courses c
        ON c.course_id = md.course_id

    LEFT JOIN user_lesson_progress ulp
        ON ulp.lesson_id = l.lesson_id
       AND ulp.user_id = ?

    WHERE l.status = 'published'
      AND md.status = 'published'
      AND c.status = 'published'
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$overallStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalPublishedLessons = (int) (
    $overallStats['total_lessons'] ?? 0
);

$completedPublishedLessons = (int) (
    $overallStats['completed_lessons'] ?? 0
);

$overallProgress = $totalPublishedLessons > 0
    ? (int) round(
        ($completedPublishedLessons / $totalPublishedLessons) * 100
    )
    : 0;


/* =========================================
   UNREAD NOTIFICATIONS
========================================= */

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


/* =========================================
   LATEST NOTIFICATIONS
========================================= */

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
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Progress</title>

    <link
        rel="stylesheet"
        href="css/Progress_style.css?v=<?php
            echo file_exists(__DIR__ . '/css/Progress_style.css')
                ? filemtime(__DIR__ . '/css/Progress_style.css')
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

        /* =========================================
           COMBINED COURSE PROGRESS
        ========================================= */

        .combined-progress-section {
            margin-top: 25px;
        }

        .combined-progress-section .section-description {
            margin-bottom: 20px;
        }

        .combined-course-card {
            background: var(--white, #ffffff);
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 16px;
            transition: 0.2s ease;
        }

        .combined-course-card:hover {
            border-color: #d1d1d1;
            transform: translateY(-2px);
        }

        .combined-course-card.locked {
            opacity: 0.75;
        }

        .combined-course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }

        .combined-course-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.5;
        }

        .combined-course-status {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 11px;
            border-radius: 20px;
            white-space: nowrap;
            background: #edf3e9;
            color: #4e8d27;
        }

        .combined-course-card.locked .combined-course-status {
            background: #eeeeea;
            color: #77776c;
        }

        .progress-category {
            margin-top: 16px;
        }

        .progress-category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 9px;
        }

        .progress-category-header strong {
            font-size: 14px;
            font-weight: 600;
        }

        .progress-category-header span {
            font-size: 13px;
            font-weight: 700;
        }

        .combined-progress-track {
            width: 100%;
            height: 8px;
            background: #e9e9e5;
            border-radius: 20px;
            overflow: hidden;
        }

        .combined-progress-fill {
            height: 100%;
            border-radius: 20px;
            transition: width 0.3s ease;
        }

        .lesson-progress-fill {
            background: #28bd28;
        }

        .mission-progress-fill {
            background: #28bd28;
        }

        .progress-category-details {
            display: block;
            margin-top: 7px;
            font-size: 12px;
            color: #77776c;
        }

        .progress-category + .progress-category {
            margin-top: 22px;
        }

        @media (max-width: 600px) {

            .combined-course-card {
                padding: 16px;
            }

            .combined-course-header {
                flex-direction: column;
                gap: 9px;
            }

            .combined-course-title {
                font-size: 14px;
            }

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

            <a href="MissionsPage.php" class="nav-item">
                <span>⚑</span>
                Missions
            </a>

            <a href="ProgressPage.php" class="nav-item active">
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

                <h1>Your Progress</h1>

                <p>See how far you've come.</p>

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
                        id="notificationButton"
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
                                            <?php
                                            echo htmlspecialchars(
                                                $note['notification_title'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </strong>

                                        <p>
                                            <?php
                                            echo htmlspecialchars(
                                                $note['notification_message'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </p>

                                        <small>
                                            <?php
                                            echo htmlspecialchars(
                                                $note['created_at'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
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
                                src="<?php echo htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?php echo htmlspecialchars($firstLetter, ENT_QUOTES, 'UTF-8'); ?>';"
                            >

                        <?php else: ?>

                            <?php
                            echo htmlspecialchars(
                                $firstLetter,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        <?php endif; ?>

                    </div>


                    <div>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $displayName,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </strong>

                        <small>
                            <?php echo $userRole === 'admin' ? 'Administrator' : 'Student'; ?>
                        </small>

                    </div>

                </div>

            </div>

        </header>


        <!-- OVERALL LESSON PROGRESS -->
        <section class="overall-card">

            <div>

                <span>OVERALL PROGRESS</span>

                <h2><?php echo $overallProgress; ?>%</h2>

                <p>
                    Keep completing lessons to reach your final quest.
                </p>

            </div>


            <div
                class="big-progress"
                role="progressbar"
                aria-label="Overall lesson progress"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="<?php echo $overallProgress; ?>"
            >

                <div
                    class="big-progress-fill"
                    style="width: <?php echo $overallProgress; ?>%;"
                ></div>

            </div>

        </section>


        <!-- STATISTICS -->
        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon">⚑</div>

                <div>

                    <strong><?php echo $missionsCompleted; ?></strong>

                    <span>Missions Completed</span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">✓</div>

                <div>

                    <strong><?php echo $challengesCompleted; ?></strong>

                    <span>Challenges Completed</span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">★</div>

                <div>

                    <strong><?php echo $coursesCompleted; ?></strong>

                    <span>Courses Completed</span>

                </div>

            </div>


        </section>


        <!-- COMBINED COURSE AND MISSION PROGRESS -->
        <section class="course-progress-section combined-progress-section">

            <h2>Course Progress</h2>

            <p class="section-description">
                Track your lesson and mission completion for each course.
            </p>


            <?php if (!empty($courses)): ?>

                <?php foreach ($courses as $course): ?>

                    <?php

                    $courseId = (int) $course['course_id'];

                    $courseTitle = htmlspecialchars(
                        $course['course_title'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $courseNumber = (int) $course['course_number'];


                    /* LESSON PROGRESS */
                    $lessonTotal = (int) $course['total_lessons'];

                    $lessonCompleted = (int) $course['completed_lessons'];

                    $lessonPercent = (int) $course['progress_percent'];


                    /* MISSION PROGRESS */
                    $missionData = $missionProgressByCourse[$courseId] ?? [];

                    $missionTotal = (int) (
                        $missionData['total_missions'] ?? 0
                    );

                    $missionCompleted = (int) (
                        $missionData['completed_missions'] ?? 0
                    );

                    $missionPercent = (int) (
                        $missionData['progress_percent'] ?? 0
                    );


                    /* COURSE STATUS */
                    $isLocked = (bool) $course['display_locked'];

                    $isCompleted = (
                        $lessonTotal > 0 &&
                        $lessonCompleted >= $lessonTotal
                    );

                    ?>


                    <div class="combined-course-card <?php
                        echo $isLocked ? 'locked' : '';
                    ?>">


                        <!-- COURSE HEADER -->
                        <div class="combined-course-header">

                            <strong class="combined-course-title">

                                COURSE

                                <?php
                                echo str_pad(
                                    (string) $courseNumber,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                );
                                ?>:

                                <?php echo $courseTitle; ?>

                            </strong>


                            <span class="combined-course-status">

                                <?php

                                if ($isLocked) {

                                    echo 'Locked';

                                } elseif ($isCompleted) {

                                    echo 'Completed';

                                } else {

                                    echo 'In Progress';

                                }

                                ?>

                            </span>

                        </div>


                        <!-- LESSON PROGRESS -->
                        <div class="progress-category">

                            <div class="progress-category-header">

                                <strong>Lesson Progress</strong>

                                <span>
                                    <?php echo $lessonPercent; ?>%
                                </span>

                            </div>


                            <div
                                class="combined-progress-track"
                                role="progressbar"
                                aria-label="<?php echo $courseTitle; ?> lesson progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?php echo $lessonPercent; ?>"
                            >

                                <div
                                    class="combined-progress-fill lesson-progress-fill"
                                    style="width: <?php echo $lessonPercent; ?>%;"
                                ></div>

                            </div>


                            <small class="progress-category-details">

                                <?php echo $lessonCompleted; ?>

                                of

                                <?php echo $lessonTotal; ?>

                                lessons completed

                            </small>

                        </div>


                        <!-- MISSION PROGRESS -->
                        <div class="progress-category">

                            <div class="progress-category-header">

                                <strong>Mission Progress</strong>

                                <span>
                                    <?php echo $missionPercent; ?>%
                                </span>

                            </div>


                            <div
                                class="combined-progress-track"
                                role="progressbar"
                                aria-label="<?php echo $courseTitle; ?> mission progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?php echo $missionPercent; ?>"
                            >

                                <div
                                    class="combined-progress-fill mission-progress-fill"
                                    style="width: <?php echo $missionPercent; ?>%;"
                                ></div>

                            </div>


                            <small class="progress-category-details">

                                <?php echo $missionCompleted; ?>

                                of

                                <?php echo $missionTotal; ?>

                                missions completed

                            </small>

                        </div>


                    </div>

                <?php endforeach; ?>


            <?php else: ?>

                <p>No published courses are available right now.</p>

            <?php endif; ?>


        </section>


    </main>

</div>


<script>

    /* =========================================
       DARK MODE
    ========================================= */

    function toggleDarkMode() {

        document.body.classList.toggle("dark-mode-active");

    }


    /* =========================================
       NOTIFICATIONS
    ========================================= */

    function toggleNotifications() {

        const dropdown = document.getElementById("notificationDropdown");

        const button = document.getElementById("notificationButton");

        if (!dropdown || !button) {
            return;
        }

        dropdown.classList.toggle("show");

        button.setAttribute(
            "aria-expanded",
            dropdown.classList.contains("show") ? "true" : "false"
        );

    }


    document.addEventListener("click", function(event) {

        const container = document.querySelector(".notification-container");

        const dropdown = document.getElementById("notificationDropdown");

        const button = document.getElementById("notificationButton");


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
