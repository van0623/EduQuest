
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$conn->set_charset("utf8mb4");

/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function reportEscape($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function reportFetchAll($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Report query error: " . $conn->error);
    }

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function reportFetchValue($conn, $sql, $types = "", $params = []) {
    $rows = reportFetchAll($conn, $sql, $types, $params);
    return $rows[0] ?? [];
}

/* =========================================================
   FILTERS
========================================================= */

$allowedDateRanges = ["all", "7", "30"];

$allowedReportTypes = [
    "course_completion",
    "challenge_scores",
    "mission_progress"
];

$selectedDateRange = $_GET["date_range"] ?? "all";
$selectedCourseId = max(0, (int)($_GET["course_id"] ?? 0));
$selectedReportType = $_GET["report_type"] ?? "course_completion";

if (!in_array($selectedDateRange, $allowedDateRanges, true)) {
    $selectedDateRange = "all";
}

if (!in_array($selectedReportType, $allowedReportTypes, true)) {
    $selectedReportType = "course_completion";
}

/* =========================================================
   DATE CONDITIONS
========================================================= */

$dateCondition = "";

if ($selectedDateRange === "7") {
    $dateCondition = " AND %s >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($selectedDateRange === "30") {
    $dateCondition = " AND %s >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

/* =========================================================
   FETCH COURSES
========================================================= */

$courses = reportFetchAll(
    $conn,
    "SELECT course_id, course_title
     FROM courses
     ORDER BY course_number ASC"
);

/* =========================================================
   COURSE FILTER
========================================================= */

$courseCondition = "";
$courseTypes = "";
$courseParams = [];

if ($selectedCourseId > 0) {
    $courseCondition = " AND c.course_id = ?";
    $courseTypes = "i";
    $courseParams[] = $selectedCourseId;
}

/* =========================================================
   REPORT DATA
========================================================= */

$reportRows = [];
$reportTitle = "";
$reportDescription = "";

$totalStudents = 0;
$missionsCompleted = 0;
$averageChallengeScore = 0;
$completionRate = 0;

$statusCompleted = 0;
$statusInProgress = 0;
$statusNotStarted = 0;

/* =========================================================
   TOTAL STUDENTS
========================================================= */

$studentData = reportFetchValue(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

$totalStudents = (int)($studentData["total"] ?? 0);

/* =========================================================
   REPORT 1: COURSE COMPLETION
========================================================= */

if ($selectedReportType === "course_completion") {

    $reportTitle = "Course Completion Report";
    $reportDescription = "View student progress and completion rates for each course.";

    $sql = "
        SELECT
            c.course_id,
            c.course_title,
            COUNT(DISTINCT ucp.user_id) AS students_started,
            COUNT(DISTINCT CASE
                WHEN ucp.progress_percent >= 100
                THEN ucp.user_id
            END) AS students_completed,
            COALESCE(AVG(ucp.progress_percent), 0) AS average_progress
        FROM courses c
        LEFT JOIN user_course_progress ucp
            ON c.course_id = ucp.course_id
        LEFT JOIN users u
            ON ucp.user_id = u.user_id
            AND u.role = 'student'
        WHERE 1 = 1
    ";

    if ($selectedCourseId > 0) {
        $sql .= " AND c.course_id = ?";
    }

    if ($selectedDateRange === "7") {
        $sql .= " AND (
            ucp.updated_at IS NULL
            OR ucp.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        )";
    } elseif ($selectedDateRange === "30") {
        $sql .= " AND (
            ucp.updated_at IS NULL
            OR ucp.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )";
    }

    $sql .= "
        GROUP BY c.course_id, c.course_title, c.course_number
        ORDER BY c.course_number ASC
    ";

    $reportRows = reportFetchAll(
        $conn,
        $sql,
        $courseTypes,
        $courseParams
    );

    $progressTotal = 0;
    $progressCount = 0;

    foreach ($reportRows as $row) {
        $progressTotal += (float)$row["average_progress"];
        $progressCount++;
    }

    $completionRate = $progressCount > 0
        ? $progressTotal / $progressCount
        : 0;

    $studentStatus = reportFetchValue(
        $conn,
        "
        SELECT
            COUNT(*) AS total_students,
            SUM(CASE WHEN progress_data.progress_percent >= 100
                THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN progress_data.progress_percent > 0
                AND progress_data.progress_percent < 100
                THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN progress_data.progress_percent = 0
                OR progress_data.progress_percent IS NULL
                THEN 1 ELSE 0 END) AS not_started_count
        FROM users u
        LEFT JOIN (
            SELECT
                user_id,
                MAX(progress_percent) AS progress_percent
            FROM user_course_progress
            WHERE (? = 0 OR course_id = ?)
            GROUP BY user_id
        ) progress_data ON progress_data.user_id = u.user_id
        WHERE u.role = 'student'
        ",
        "ii",
        [$selectedCourseId, $selectedCourseId]
    );

    $statusCompleted = (int)($studentStatus["completed_count"] ?? 0);
    $statusInProgress = (int)($studentStatus["in_progress_count"] ?? 0);
    $statusNotStarted = (int)($studentStatus["not_started_count"] ?? 0);

    $missionsCompleted = 0;
    $averageChallengeScore = 0;
}

/* =========================================================
   REPORT 2: CHALLENGE SCORES
========================================================= */

elseif ($selectedReportType === "challenge_scores") {

    $reportTitle = "Challenge Scores Report";
    $reportDescription = "Review student challenge attempts, average scores, and passing rates.";

    $sql = "
        SELECT
            ch.challenge_id,
            ch.challenge_title,
            COUNT(ca.attempt_id) AS total_attempts,
            COALESCE(
                AVG(
                    (ca.score / NULLIF(ch.total_points, 0)) * 100
                ),
                0
            ) AS average_score,
            COALESCE(
                SUM(
                    CASE
                        WHEN ca.score >= ch.total_points * (ch.passing_score / 100)
                        THEN 1
                        ELSE 0
                    END
                ) / NULLIF(COUNT(ca.attempt_id), 0) * 100,
                0
            ) AS pass_rate
        FROM challenges ch
        INNER JOIN missions m
            ON ch.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        LEFT JOIN challenge_attempts ca
            ON ch.challenge_id = ca.challenge_id
            AND ca.status = 'graded'
            AND ca.user_id IN (
                SELECT user_id
                FROM users
                WHERE role = 'student'
            )
    ";

    if ($selectedDateRange === "7") {
        $sql .= "
            AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
    } elseif ($selectedDateRange === "30") {
        $sql .= "
            AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
    }

    $sql .= " WHERE 1 = 1";

    if ($selectedCourseId > 0) {
        $sql .= " AND c.course_id = ?";
    }

    $sql .= "
        GROUP BY
            ch.challenge_id,
            ch.challenge_title,
            ch.total_points,
            ch.passing_score
        ORDER BY ch.challenge_id DESC
    ";

    $reportRows = reportFetchAll(
        $conn,
        $sql,
        $courseTypes,
        $courseParams
    );

    $scoreData = reportFetchValue(
        $conn,
        "
        SELECT
            COALESCE(
                AVG(
                    (ca.score / NULLIF(ch.total_points, 0)) * 100
                ),
                0
            ) AS average_score
        FROM challenge_attempts ca
        INNER JOIN challenges ch
            ON ca.challenge_id = ch.challenge_id
        INNER JOIN missions m
            ON ch.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        INNER JOIN users u
            ON ca.user_id = u.user_id
        WHERE ca.status = 'graded'
          AND u.role = 'student'
          " . ($selectedDateRange === "7"
                ? " AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
                : ($selectedDateRange === "30"
                    ? " AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                    : "")) . "
          " . ($selectedCourseId > 0 ? " AND c.course_id = ?" : ""),
        $courseTypes,
        $courseParams
    );

    $averageChallengeScore = (float)($scoreData["average_score"] ?? 0);

    $missionsCompleted = 0;
    $completionRate = 0;
    $statusCompleted = 0;
    $statusInProgress = 0;
    $statusNotStarted = 0;
}

/* =========================================================
   REPORT 3: MISSION PROGRESS
========================================================= */

else {

    $reportTitle = "Mission Progress Report";
    $reportDescription = "Track completed and ongoing missions for each student.";

    $sql = "
        SELECT
            ump.mission_progress_id,
            u.full_name,
            u.email,
            m.mission_title,
            c.course_title,
            ump.status,
            ump.progress_percent,
            ump.started_at,
            ump.completed_at
        FROM user_mission_progress ump
        INNER JOIN users u
            ON ump.user_id = u.user_id
        INNER JOIN missions m
            ON ump.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        WHERE u.role = 'student'
    ";

    if ($selectedCourseId > 0) {
        $sql .= " AND c.course_id = ?";
    }

    if ($selectedDateRange === "7") {
        $sql .= "
            AND COALESCE(ump.completed_at, ump.started_at)
                >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
    } elseif ($selectedDateRange === "30") {
        $sql .= "
            AND COALESCE(ump.completed_at, ump.started_at)
                >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
    }

    $sql .= "
        ORDER BY
            COALESCE(ump.completed_at, ump.started_at, ump.updated_at) DESC
    ";

    $reportRows = reportFetchAll(
        $conn,
        $sql,
        $courseTypes,
        $courseParams
    );

    $missionData = reportFetchValue(
        $conn,
        "
        SELECT
            COUNT(*) AS total_records,
            SUM(CASE WHEN ump.status = 'completed'
                THEN 1 ELSE 0 END) AS completed_count
        FROM user_mission_progress ump
        INNER JOIN users u
            ON ump.user_id = u.user_id
        INNER JOIN missions m
            ON ump.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        WHERE u.role = 'student'
        " .
        ($selectedCourseId > 0 ? " AND c.course_id = ?" : "") .
        ($selectedDateRange === "7"
            ? " AND COALESCE(ump.completed_at, ump.started_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            : ($selectedDateRange === "30"
                ? " AND COALESCE(ump.completed_at, ump.started_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                : "")),
        $courseTypes,
        $courseParams
    );

    $missionsCompleted = (int)($missionData["completed_count"] ?? 0);

    $totalMissionRecords = (int)($missionData["total_records"] ?? 0);

    $completionRate = $totalMissionRecords > 0
        ? ($missionsCompleted / $totalMissionRecords) * 100
        : 0;

    $averageChallengeScore = 0;
    $statusCompleted = 0;
    $statusInProgress = 0;
    $statusNotStarted = 0;
}

/* =========================================================
   EXPORT CSV
========================================================= */

if (isset($_GET["export"]) && $_GET["export"] === "csv") {

    $filename = $selectedReportType . "_report_" . date("Y-m-d") . ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $filename);

    $output = fopen("php://output", "w");

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    if ($selectedReportType === "course_completion") {

        fputcsv($output, [
            "Course",
            "Students Started",
            "Students Completed",
            "Average Progress (%)"
        ]);

        foreach ($reportRows as $row) {
            fputcsv($output, [
                $row["course_title"],
                $row["students_started"],
                $row["students_completed"],
                round((float)$row["average_progress"], 2)
            ]);
        }

    } elseif ($selectedReportType === "challenge_scores") {

        fputcsv($output, [
            "Challenge",
            "Total Attempts",
            "Average Score (%)",
            "Pass Rate (%)"
        ]);

        foreach ($reportRows as $row) {
            fputcsv($output, [
                $row["challenge_title"],
                $row["total_attempts"],
                round((float)$row["average_score"], 2),
                round((float)$row["pass_rate"], 2)
            ]);
        }

    } else {

        fputcsv($output, [
            "Student",
            "Email",
            "Mission",
            "Course",
            "Status",
            "Progress (%)",
            "Started At",
            "Completed At"
        ]);

        foreach ($reportRows as $row) {
            fputcsv($output, [
                $row["full_name"],
                $row["email"],
                $row["mission_title"],
                $row["course_title"],
                $row["status"],
                $row["progress_percent"],
                $row["started_at"],
                $row["completed_at"]
            ]);
        }
    }

    fclose($output);
    exit;
}

/* =========================================================
   CHALLENGE SCORE SUMMARY FOR OTHER REPORTS
========================================================= */

if ($selectedReportType !== "challenge_scores") {

    $scoreSql = "
        SELECT
            COALESCE(
                AVG((ca.score / NULLIF(ch.total_points, 0)) * 100),
                0
            ) AS average_score
        FROM challenge_attempts ca
        INNER JOIN challenges ch
            ON ca.challenge_id = ch.challenge_id
        INNER JOIN missions m
            ON ch.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        INNER JOIN users u
            ON ca.user_id = u.user_id
        WHERE ca.status = 'graded'
          AND u.role = 'student'
    ";

    if ($selectedDateRange === "7") {
        $scoreSql .= " AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($selectedDateRange === "30") {
        $scoreSql .= " AND ca.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    if ($selectedCourseId > 0) {
        $scoreSql .= " AND c.course_id = ?";
    }

    $scoreData = reportFetchValue(
        $conn,
        $scoreSql,
        $courseTypes,
        $courseParams
    );

    $averageChallengeScore = (float)($scoreData["average_score"] ?? 0);
}

/* =========================================================
   MISSION SUMMARY FOR OTHER REPORTS
========================================================= */

if ($selectedReportType !== "mission_progress") {

    $missionSql = "
        SELECT COUNT(*) AS total
        FROM user_mission_progress ump
        INNER JOIN users u
            ON ump.user_id = u.user_id
        INNER JOIN missions m
            ON ump.mission_id = m.mission_id
        INNER JOIN modules md
            ON m.module_id = md.module_id
        INNER JOIN courses c
            ON md.course_id = c.course_id
        WHERE ump.status = 'completed'
          AND u.role = 'student'
    ";

    if ($selectedDateRange === "7") {
        $missionSql .= " AND ump.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($selectedDateRange === "30") {
        $missionSql .= " AND ump.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    if ($selectedCourseId > 0) {
        $missionSql .= " AND c.course_id = ?";
    }

    $missionData = reportFetchValue(
        $conn,
        $missionSql,
        $courseTypes,
        $courseParams
    );

    $missionsCompleted = (int)($missionData["total"] ?? 0);
}

/* =========================================================
   COMPLETION RATE SUMMARY
========================================================= */

if ($selectedReportType !== "course_completion") {

    $completionSql = "
        SELECT COALESCE(AVG(ucp.progress_percent), 0) AS average_progress
        FROM user_course_progress ucp
        INNER JOIN users u
            ON ucp.user_id = u.user_id
        INNER JOIN courses c
            ON ucp.course_id = c.course_id
        WHERE u.role = 'student'
    ";

    if ($selectedDateRange === "7") {
        $completionSql .= " AND ucp.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($selectedDateRange === "30") {
        $completionSql .= " AND ucp.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    if ($selectedCourseId > 0) {
        $completionSql .= " AND c.course_id = ?";
    }

    $completionData = reportFetchValue(
        $conn,
        $completionSql,
        $courseTypes,
        $courseParams
    );

    $completionRate = (float)($completionData["average_progress"] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
        <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Reports">

    <main class="page-content">

        <!-- PAGE HEADING -->
        <section class="page-heading split-heading">

            <div>
                <p class="eyebrow">ANALYTICS & PERFORMANCE</p>
                <h1>Reports</h1>
                <p class="muted">
                    Monitor student progress and learning performance.
                </p>
            </div>

            <a
                class="btn btn-primary"
                href="?<?= reportEscape(http_build_query([
                    "date_range" => $selectedDateRange,
                    "course_id" => $selectedCourseId,
                    "report_type" => $selectedReportType,
                    "export" => "csv"
                ])) ?>"
            >
                ↓ Export Report
            </a>

        </section>

        <!-- FILTERS -->
        <form method="GET" class="toolbar">

            <label class="field-inline">
                Date Range

                <select name="date_range">
                    <option value="all" <?= $selectedDateRange === "all" ? "selected" : "" ?>>
                        All Time
                    </option>

                    <option value="7" <?= $selectedDateRange === "7" ? "selected" : "" ?>>
                        Last 7 Days
                    </option>

                    <option value="30" <?= $selectedDateRange === "30" ? "selected" : "" ?>>
                        Last 30 Days
                    </option>
                </select>
            </label>

            <label class="field-inline">
                Course

                <select name="course_id">
                    <option value="0">All Courses</option>

                    <?php foreach ($courses as $course): ?>
                        <option
                            value="<?= (int)$course["course_id"] ?>"
                            <?= $selectedCourseId === (int)$course["course_id"] ? "selected" : "" ?>
                        >
                            <?= reportEscape($course["course_title"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field-inline">
                Report Type

                <select name="report_type">
                    <option value="course_completion" <?= $selectedReportType === "course_completion" ? "selected" : "" ?>>
                        Course Completion
                    </option>

                    <option value="challenge_scores" <?= $selectedReportType === "challenge_scores" ? "selected" : "" ?>>
                        Challenge Scores
                    </option>

                    <option value="mission_progress" <?= $selectedReportType === "mission_progress" ? "selected" : "" ?>>
                        Mission Progress
                    </option>
                </select>
            </label>

            <button type="submit" class="btn btn-outline">
                Apply Filters
            </button>

        </form>

        <!-- SUMMARY CARDS -->
        <section class="stats-grid">

            <article class="stat-card">
                <div class="stat-info">
                    <span>Students</span>
                    <h2><?= number_format($totalStudents) ?></h2>
                    <small>Registered student accounts</small>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-info">
                    <span>Completion Rate</span>
                    <h2><?= number_format($completionRate, 1) ?>%</h2>
                    <small>Average course progress</small>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-info">
                    <span>Average Challenge Score</span>
                    <h2><?= number_format($averageChallengeScore, 1) ?>%</h2>
                    <small>Based on graded attempts</small>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-info">
                    <span>Missions Completed</span>
                    <h2><?= number_format($missionsCompleted) ?></h2>
                    <small>Completed student missions</small>
                </div>
            </article>

        </section>

        <!-- REPORT CONTENT -->
        <section class="panel table-panel">

            <div class="panel-heading">
                <div>
                    <h2><?= reportEscape($reportTitle) ?></h2>
                    <p class="muted"><?= reportEscape($reportDescription) ?></p>
                </div>
            </div>

            <div class="table-wrap">

                <table>

                    <?php if ($selectedReportType === "course_completion"): ?>

                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Students Started</th>
                                <th>Students Completed</th>
                                <th>Average Progress</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($reportRows)): ?>

                                <?php foreach ($reportRows as $row): ?>

                                    <tr>
                                        <td>
                                            <strong><?= reportEscape($row["course_title"]) ?></strong>
                                        </td>

                                        <td><?= number_format((int)$row["students_started"]) ?></td>

                                        <td><?= number_format((int)$row["students_completed"]) ?></td>

                                        <td>
                                            <strong>
                                                <?= number_format((float)$row["average_progress"], 1) ?>%
                                            </strong>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="empty-state">
                                        No course completion records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    <?php elseif ($selectedReportType === "challenge_scores"): ?>

                        <thead>
                            <tr>
                                <th>Challenge</th>
                                <th>Total Attempts</th>
                                <th>Average Score</th>
                                <th>Pass Rate</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($reportRows)): ?>

                                <?php foreach ($reportRows as $row): ?>

                                    <tr>
                                        <td>
                                            <strong><?= reportEscape($row["challenge_title"]) ?></strong>
                                        </td>

                                        <td><?= number_format((int)$row["total_attempts"]) ?></td>

                                        <td>
                                            <?= number_format((float)$row["average_score"], 1) ?>%
                                        </td>

                                        <td>
                                            <?= number_format((float)$row["pass_rate"], 1) ?>%
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="empty-state">
                                        No challenge attempts found for the selected filters.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    <?php else: ?>

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Mission</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Completed At</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($reportRows)): ?>

                                <?php foreach ($reportRows as $row): ?>

                                    <tr>
                                        <td>
                                            <strong><?= reportEscape($row["full_name"]) ?></strong>
                                            <small><?= reportEscape($row["email"]) ?></small>
                                        </td>

                                        <td><?= reportEscape($row["mission_title"]) ?></td>

                                        <td><?= reportEscape($row["course_title"]) ?></td>

                                        <td>
                                            <span class="status <?= reportEscape(strtolower($row["status"])) ?>">
                                                <?= reportEscape(ucwords(str_replace("_", " ", $row["status"]))) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= number_format((float)$row["progress_percent"], 1) ?>%
                                        </td>

                                        <td>
                                            <?= !empty($row["completed_at"])
                                                ? reportEscape(date("M d, Y h:i A", strtotime($row["completed_at"])))
                                                : "—" ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="empty-state">
                                        No mission progress records found for the selected filters.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    <?php endif; ?>

                </table>

            </div>

        </section>

        <footer class="page-footer">
            <p>© 2026 EduQuest · Admin Portal</p>
        </footer>

    </main>

</div>

</body>
</html>
