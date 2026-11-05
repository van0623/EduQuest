<?php
require_once "db_connect.php";
require_once "admin_auth.php";

// 1. Fetch Total Students Count
$totalStudents = 0;
$studentResult = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'student'");
if ($studentResult) {
    $totalStudents = $studentResult->fetch_assoc()['count'];
}

// 2. Fetch Total Admins Count
$totalAdmins = 0;
$adminResult = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'admin'");
if ($adminResult) {
    $totalAdmins = $adminResult->fetch_assoc()['count'];
}

// 3. Fetch Total Courses Count
$totalCourses = 0;
$courseCountResult = $conn->query("SELECT COUNT(*) AS count FROM courses");
if ($courseCountResult) {
    $totalCourses = $courseCountResult->fetch_assoc()['count'];
}

// 4. Fetch Total Missions Count
$totalMissions = 0;
$missionResult = $conn->query("SELECT COUNT(*) AS count FROM missions");
if ($missionResult) {
    $totalMissions = $missionResult->fetch_assoc()['count'];
}

// 5. Fetch Total Challenges Count
$totalChallenges = 0;
$challengeResult = $conn->query("SELECT COUNT(*) AS count FROM challenges");
if ($challengeResult) {
    $totalChallenges = $challengeResult->fetch_assoc()['count'];
}

// 6. Fetch Course Completion Rates (Calculated live from user_course_progress and courses tables)
$coursesResult = $conn->query("
    SELECT c.course_title, c.status, 
           COALESCE(AVG(p.progress_percent), 0) AS avg_completion 
    FROM courses c 
    LEFT JOIN user_course_progress p ON c.course_id = p.course_id 
    GROUP BY c.course_id, c.course_title, c.status 
    ORDER BY c.course_id ASC
");

// 7. Fetch Overall Average Student Progress across all courses
$avgProgress = 0;
$progressQuery = $conn->query("SELECT AVG(progress_percent) AS avg_rate FROM user_course_progress");
if ($progressQuery) {
    $progressRow = $progressQuery->fetch_assoc();
    $avgProgress = round($progressRow['avg_rate'] ?? 0);
}

// 8. Fetch Recent Student Signups for Activity Feed
$recentStudentsQuery = "SELECT full_name, username, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5";
$recentStudentsResult = $conn->query($recentStudentsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EduQuest Admin</title>
    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>
<body>
    <div id="admin-app" data-page="Dashboard">
        <main class="page-content">

                <section class="page-heading">
                    <div>
                        <p class="eyebrow">OVERVIEW</p>
                        <h1>Dashboard</h1>
                        <p class="muted">
                            Welcome back,
                            <strong><?= htmlspecialchars($_SESSION["full_name"] ?? "Admin", ENT_QUOTES, "UTF-8") ?></strong>!
                            Monitor your EduQuest learning platform.
                        </p>
                    </div>

                    <div class="heading-actions">
                        <a href="../DashboardPage.php" class="view-website-btn" target="_blank">
                            <span>↗</span>
                            View Website
                        </a>
                    </div>
                </section>

            <!-- STATS GRID (Fully Dynamic from Database) -->
            <section class="stats-grid">
                <article class="stat-card">
                    <span class="stat-icon">♙</span>
                    <p>Total Students</p>
                    <h2><?= $totalStudents ?></h2>
                    <small>Registered student accounts</small>
                </article>

                <article class="stat-card">
                    <span class="stat-icon">🛡️</span>
                    <p>Total Admins</p>
                    <h2><?= $totalAdmins ?></h2>
                    <small>Administrator accounts</small>
                </article>

                <article class="stat-card">
                    <span class="stat-icon">▤</span>
                    <p>Total Courses</p>
                    <h2><?= $totalCourses ?></h2>
                    <small>Active learning courses</small>
                </article>

                <article class="stat-card">
                    <span class="stat-icon">◎</span>
                    <p>Total Missions</p>
                    <h2><?= $totalMissions ?></h2>
                    <small>Across all modules</small>
                </article>

                <article class="stat-card">
                    <span class="stat-icon">◇</span>
                    <p>Total Challenges</p>
                    <h2><?= $totalChallenges ?></h2>
                    <small>Created challenges</small>
                </article>
            </section>

            <section class="content-grid">
                <!-- COURSE COMPLETION (Dynamic from courses & user_course_progress tables) -->
                <article class="panel wide-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Course Completion</h2>
                            <p class="muted">Live progress rates from student databases</p>
                        </div>
                        <a class="text-link" href="AdminReports.html">
                            View reports →
                        </a>
                    </div>

                    <div class="bar-chart">
                        <?php if ($coursesResult && $coursesResult->num_rows > 0): ?>
                            <?php while ($course = $coursesResult->fetch_assoc()): 
                                $rate = round($course['avg_completion']);
                            ?>
                                <div class="bar-row">
                                    <span><?= htmlspecialchars($course['course_title'], ENT_QUOTES, "UTF-8") ?> <small class="muted">(<?= htmlspecialchars($course['status']) ?>)</small></span>
                                    <div class="bar-track">
                                        <div style="width: <?= $rate ?>%"></div>
                                    </div>
                                    <b><?= $rate ?>%</b>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bar-row">
                                <span class="muted">No course completion data found.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <!-- STUDENT PROGRESS SUMMARY (Dynamic from user_course_progress table) -->
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Student Progress</h2>
                            <p class="muted">Average platform completion</p>
                        </div>
                    </div>

                    <div class="progress-summary">
                        <div class="progress-ring">
                            <strong><?= $avgProgress ?>%</strong>
                        </div>

                        <div>
                            <b>Average completion</b>
                            <p class="muted">Across all student progress tracking</p>
                        </div>
                    </div>

                    <div class="legend">
                        <span><i class="dot green"></i> Completed</span>
                        <span><i class="dot gray"></i> In progress</span>
                        <span><i class="dot black"></i> Not started</span>
                    </div>
                </article>
            </section>

            <!-- RECENT ACTIVITY (Live Database Feed from users table) -->
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Recent Activity</h2>
                        <p class="muted">Latest student signups in the portal</p>
                    </div>
                </div>

                <div class="activity-list">
                    <?php if ($recentStudentsResult && $recentStudentsResult->num_rows > 0): ?>
                        <?php while ($student = $recentStudentsResult->fetch_assoc()): ?>
                            <div class="activity-item">
                                <span class="activity-mark">＋</span>
                                <div>
                                    <b>New student registered: <?= htmlspecialchars($student['full_name'], ENT_QUOTES, "UTF-8") ?></b>
                                    <p class="muted">
                                        Username: @<?= htmlspecialchars($student['username'], ENT_QUOTES, "UTF-8") ?>
                                    </p>
                                </div>
                                <time><?= date("M j, Y, g:i a", strtotime($student['created_at'])) ?></time>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div>
                                <p class="muted">No recent student activity found.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <footer class="page-footer">
                © 2026 EduQuest · Admin Portal
            </footer>
        </main>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>