<?php
require_once "db_connect.php";
require_once "admin_auth.php";

// 1. Get and validate student ID from query string
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    header("Location: AdminStudents.php");
    exit();
}

// 2. Fetch Student Profile
$userStmt = $conn->prepare("SELECT user_id, full_name, email, created_at, last_login, profile_image FROM users WHERE user_id = ? AND role = 'student'");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    header("Location: AdminStudents.php");
    exit();
}

$student = $userResult->fetch_assoc();
$userStmt->close();

// Generate Avatar Initials
$nameParts = explode(" ", trim($student['full_name']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

// 3. Fetch Student Performance Metrics
// Overall Progress
$overallProgStmt = $conn->prepare("SELECT AVG(progress_percent) AS avg_progress, COUNT(course_id) AS courses_started FROM user_course_progress WHERE user_id = ?");
$overallProgStmt->bind_param("i", $user_id);
$overallProgStmt->execute();
$progData = $overallProgStmt->get_result()->fetch_assoc();
$overallProgress = round($progData['avg_progress'] ?? 0);
$coursesStarted = intval($progData['courses_started'] ?? 0);
$overallProgStmt->close();

// Completed Missions vs Total Missions
$compMissionsStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_mission_progress WHERE user_id = ? AND status = 'completed'");
$compMissionsStmt->bind_param("i", $user_id);
$compMissionsStmt->execute();
$completedMissions = $compMissionsStmt->get_result()->fetch_assoc()['cnt'];
$compMissionsStmt->close();

$totMissionsRes = $conn->query("SELECT COUNT(*) AS cnt FROM missions");
$totalMissions = $totMissionsRes ? $totMissionsRes->fetch_assoc()['cnt'] : 0;

// Average Challenge Score
$challengeStmt = $conn->prepare("SELECT AVG((score / NULLIF(score, 0)) * 100) AS avg_score FROM challenge_attempts WHERE user_id = ? AND status = 'graded'");
$challengeStmt->bind_param("i", $user_id);
$challengeStmt->execute();
$avgChallengeScore = round($challengeStmt->get_result()->fetch_assoc()['avg_score'] ?? 0);
$challengeStmt->close();

// 4. Fetch Course Progress Breakdown for All Courses
$coursesProgQuery = "
    SELECT c.course_id, c.course_title, 
           COALESCE(p.progress_percent, 0) AS progress_percent,
           p.status AS progress_status
    FROM courses c
    LEFT JOIN user_course_progress p ON c.course_id = p.course_id AND p.user_id = ?
    ORDER BY c.course_number ASC
";
$cpStmt = $conn->prepare($coursesProgQuery);
$cpStmt->bind_param("i", $user_id);
$cpStmt->execute();
$coursesProgResult = $cpStmt->get_result();
$cpStmt->close();

// 5. Fetch Recent Student Activity (Missions Completed & Challenge Attempts)
$activities = [];

// Mission Completion Log
$misActStmt = $conn->prepare("
    SELECT m.mission_title, c.course_title, ump.completed_at AS act_time, 'mission' AS act_type
    FROM user_mission_progress ump
    INNER JOIN missions m ON ump.mission_id = m.mission_id
    INNER JOIN modules modTable ON m.module_id = modTable.module_id
    INNER JOIN courses c ON modTable.course_id = c.course_id
    WHERE ump.user_id = ? AND ump.status = 'completed' AND ump.completed_at IS NOT NULL
    ORDER BY ump.completed_at DESC LIMIT 5
");
$misActStmt->bind_param("i", $user_id);
$misActStmt->execute();
$misRes = $misActStmt->get_result();
while ($row = $misRes->fetch_assoc()) {
    $activities[] = $row;
}
$misActStmt->close();

// Challenge Attempts Log
$chalActStmt = $conn->prepare("
    SELECT ch.challenge_title, ca.score, ca.score, ca.submitted_at AS act_time, 'challenge' AS act_type
    FROM challenge_attempts ca
    INNER JOIN challenges ch ON ca.challenge_id = ch.challenge_id
    WHERE ca.user_id = ? AND ca.submitted_at IS NOT NULL
    ORDER BY ca.submitted_at DESC LIMIT 5
");
$chalActStmt->bind_param("i", $user_id);
$chalActStmt->execute();
$chalRes = $chalActStmt->get_result();
while ($row = $chalRes->fetch_assoc()) {
    $activities[] = $row;
}
$chalActStmt->close();

// Sort merged activities by timestamp descending
usort($activities, function($a, $b) {
    return strtotime($b['act_time']) - strtotime($a['act_time']);
});
$activities = array_slice($activities, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details | EduQuest Admin</title>
    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
        <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>
<body>
    <div id="admin-app" data-page="Student Details">
        <main class="page-content">

            <section class="page-heading">
                <a class="back-link" href="AdminStudents.php">
                    ← Back to Students
                </a>

                <p class="eyebrow">STUDENT RECORD</p>
                <h1>Student Details</h1>
                <p class="muted">
                    View-only overview of this student's learning activity.
                </p>
            </section>

            <!-- PROFILE SUMMARY PANEL -->
            <section class="panel profile-panel">
                <div class="student-avatar large-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?></div>

                <div class="profile-main">
                    <h2><?= htmlspecialchars($student['full_name'], ENT_QUOTES, "UTF-8") ?></h2>
                    <p class="muted"><?= htmlspecialchars($student['email'], ENT_QUOTES, "UTF-8") ?></p>

                    <div class="tag-row">
                        <span class="status published">Student</span>
                        <span class="muted">Registered <?= date("M j, Y", strtotime($student['created_at'])) ?></span>
                    </div>
                </div>

                <div class="profile-total">
                    <span class="muted">Overall progress</span>
                    <strong><?= $overallProgress ?>%</strong>
                </div>
            </section>

            <!-- METRICS STATS GRID -->
            <section class="stats-grid">
                <article class="stat-card">
                    <p>Courses Started</p>
                    <h2><?= $coursesStarted ?></h2>
                </article>

                <article class="stat-card">
                    <p>Missions Completed</p>
                    <h2><?= $completedMissions ?> / <?= $totalMissions ?></h2>
                </article>

                <article class="stat-card">
                    <p>Average Challenge Score</p>
                    <h2><?= $avgChallengeScore ?>%</h2>
                </article>

                <article class="stat-card">
                    <p>Last Activity</p>
                    <h2 class="small-stat">
                        <?= $student['last_login'] ? date("M j", strtotime($student['last_login'])) : 'None' ?>
                    </h2>
                </article>
            </section>

            <!-- COURSE PROGRESS BREAKDOWN -->
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Course Progress</h2>
                        <p class="muted">Completion by course</p>
                    </div>
                </div>

                <div class="course-progress-list">
                    <?php if ($coursesProgResult && $coursesProgResult->num_rows > 0): ?>
                        <?php while ($cp = $coursesProgResult->fetch_assoc()): 
                            $pct = round($cp['progress_percent']);
                            $statusText = ($pct > 0 || $cp['progress_status'] === 'in_progress' || $cp['progress_status'] === 'completed') ? $pct . "%" : "Not started";
                        ?>
                            <div>
                                <div class="progress-label">
                                    <b><?= htmlspecialchars($cp['course_title'], ENT_QUOTES, "UTF-8") ?></b>
                                    <span><?= $statusText ?></span>
                                </div>
                                <div class="bar-track">
                                    <div style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="muted">No course progress data found for this student.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- RECENT LEARNING ACTIVITY FEED -->
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Recent Learning Activity</h2>
                        <p class="muted">Student activity timeline</p>
                    </div>
                </div>

                <div class="activity-list">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $act): ?>
                            <div class="activity-item">
                                <?php if ($act['act_type'] === 'mission'): ?>
                                    <span class="activity-mark">✓</span>
                                    <div>
                                        <b>Completed <?= htmlspecialchars($act['mission_title'], ENT_QUOTES, "UTF-8") ?></b>
                                        <p class="muted"><?= htmlspecialchars($act['course_title'], ENT_QUOTES, "UTF-8") ?></p>
                                    </div>
                                <?php else: ?>
                                    <span class="activity-mark">◇</span>
                                    <div>
                                        <b>Submitted <?= htmlspecialchars($act['challenge_title'], ENT_QUOTES, "UTF-8") ?></b>
                                        <p class="muted">Score: <?= floatval($act['score']) ?> / <?= floatval($act['score']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <time><?= date("M j", strtotime($act['act_time'])) ?></time>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div>
                                <p class="muted">No recent learning activity recorded for this student.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <footer class="page-footer">© 2026 EduQuest · Admin Portal</footer>
        </main>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>