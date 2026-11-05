
<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";

/*
|--------------------------------------------------------------------------
| 1. CHECK STUDENT LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$missionId = filter_input(INPUT_GET, "mission_id", FILTER_VALIDATE_INT);

if (!$missionId || $missionId < 1) {
    header("Location: MissionsPage.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/
function missionEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/**
 * Award active achievements for a requirement type.
 * Existing awards are not inserted again.
 */
function awardMissionAchievements(
    mysqli $conn,
    int $userId,
    string $requirementType,
    int $completedCount
): void {

    $achievementSql = "
        SELECT achievement_id
        FROM achievements
        WHERE requirement_type = ?
          AND requirement_value <= ?
          AND status = 'active'
    ";

    $achievementStmt = $conn->prepare($achievementSql);

    if (!$achievementStmt) {
        error_log("Achievement query preparation failed: " . $conn->error);
        return;
    }

    $achievementStmt->bind_param(
        "si",
        $requirementType,
        $completedCount
    );

    if (!$achievementStmt->execute()) {
        error_log("Achievement query failed: " . $achievementStmt->error);
        $achievementStmt->close();
        return;
    }

    $achievementResult = $achievementStmt->get_result();
    $achievementIds = [];

    while ($row = $achievementResult->fetch_assoc()) {
        $achievementIds[] = (int) $row["achievement_id"];
    }

    $achievementStmt->close();

    foreach ($achievementIds as $achievementId) {

        $checkSql = "
            SELECT user_achievement_id
            FROM user_achievements
            WHERE user_id = ?
              AND achievement_id = ?
            LIMIT 1
        ";

        $checkStmt = $conn->prepare($checkSql);

        if (!$checkStmt) {
            error_log("Achievement check preparation failed: " . $conn->error);
            continue;
        }

        $checkStmt->bind_param("ii", $userId, $achievementId);
        $checkStmt->execute();

        $existingAward = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingAward) {
            continue;
        }

        $insertSql = "
            INSERT INTO user_achievements
                (user_id, achievement_id, earned_at)
            VALUES (?, ?, NOW())
        ";

        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            error_log("Achievement insert preparation failed: " . $conn->error);
            continue;
        }

        $insertStmt->bind_param("ii", $userId, $achievementId);

        if (!$insertStmt->execute()) {
            error_log("Achievement insert failed: " . $insertStmt->error);
        }

        $insertStmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| 3. LOAD CURRENT MISSION
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        m.mission_id,
        m.mission_title,
        m.mission_order,
        mo.course_id,
        ump.status AS progress_status
    FROM missions m
    INNER JOIN modules mo
        ON mo.module_id = m.module_id
    LEFT JOIN user_mission_progress ump
        ON ump.mission_id = m.mission_id
        AND ump.user_id = ?
    WHERE m.mission_id = ?
      AND m.status = 'published'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Mission query failed: " . $conn->error);
    http_response_code(500);
    exit("Unable to load mission progress. Please check your database connection.");
}

$stmt->bind_param("ii", $userId, $missionId);
$stmt->execute();

$currentMission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentMission) {
    http_response_code(404);
    exit("Mission not found.");
}

$missionTitle = $currentMission["mission_title"];
$courseId = (int) $currentMission["course_id"];
$currentOrder = (int) $currentMission["mission_order"];

$isCompleted = (
    $currentMission["progress_status"] === "completed"
);

$nextMissionTitle = "";
$nextMissionId = null;

$finalQuestAvailable = false;
$allLessonsCompleted = false;
$allMissionsCompleted = false;

$totalMissions = 0;
$completedMissions = 0;

$totalLessons = 0;
$completedLessons = 0;

/*
|--------------------------------------------------------------------------
| 4. LOAD COMPLETED ACTIVITIES AND SAVED GRADES
|--------------------------------------------------------------------------
| Only passed activities are displayed.
| The latest passed attempt is used for the displayed grade.
|--------------------------------------------------------------------------
*/
$completedActivities = [];

$activityHistorySql = "
    SELECT
        a.activity_id,
        a.activity_title,
        aa.attempt_id,
        aa.attempt_number,
        aa.score,
        aa.total_points,
        aa.percentage,
        aa.status,
        aa.submitted_at
    FROM activities a
    INNER JOIN missions m
        ON m.mission_id = a.mission_id
    INNER JOIN modules mo
        ON mo.module_id = m.module_id
    INNER JOIN (
        SELECT
            activity_id,
            MAX(attempt_id) AS latest_attempt_id
        FROM activity_attempts
        WHERE user_id = ?
          AND status = 'passed'
        GROUP BY activity_id
    ) latest
        ON latest.activity_id = a.activity_id
    INNER JOIN activity_attempts aa
        ON aa.attempt_id = latest.latest_attempt_id
    WHERE mo.course_id = ?
      AND a.status = 'published'
      AND m.status = 'published'
    ORDER BY aa.submitted_at DESC
";

$activityHistoryStmt = $conn->prepare($activityHistorySql);

if ($activityHistoryStmt) {

    $activityHistoryStmt->bind_param(
        "ii",
        $userId,
        $courseId
    );

    if ($activityHistoryStmt->execute()) {

        $activityHistoryResult = $activityHistoryStmt->get_result();

        while ($activityRow = $activityHistoryResult->fetch_assoc()) {
            $completedActivities[] = $activityRow;
        }
    } else {
        error_log("Activity history query failed: " . $activityHistoryStmt->error);
    }

    $activityHistoryStmt->close();

} else {
    error_log("Activity history preparation failed: " . $conn->error);
}

/*
|--------------------------------------------------------------------------
| 5. AWARD ACHIEVEMENTS FOR EXISTING COMPLETION RECORDS
|--------------------------------------------------------------------------
| This page does not mark a mission completed.
| It awards achievements only when the database already records completion.
|--------------------------------------------------------------------------
*/
if ($isCompleted) {

    // Count the student's completed published missions.
    $globalMissionSql = "
        SELECT COUNT(DISTINCT m.mission_id) AS completed_count
        FROM missions m
        INNER JOIN user_mission_progress ump
            ON ump.mission_id = m.mission_id
        WHERE ump.user_id = ?
          AND ump.status = 'completed'
          AND m.status = 'published'
    ";

    $globalMissionStmt = $conn->prepare($globalMissionSql);

    if ($globalMissionStmt) {

        $globalMissionStmt->bind_param("i", $userId);
        $globalMissionStmt->execute();

        $globalMissionCount = $globalMissionStmt
            ->get_result()
            ->fetch_assoc();

        $globalMissionStmt->close();

        awardMissionAchievements(
            $conn,
            $userId,
            "missions_completed",
            (int) ($globalMissionCount["completed_count"] ?? 0)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COUNT COMPLETED PUBLISHED LESSONS
    |--------------------------------------------------------------------------
    */
    $globalLessonSql = "
        SELECT COUNT(DISTINCT l.lesson_id) AS completed_count
        FROM lessons l
        INNER JOIN modules mo
            ON mo.module_id = l.module_id
        INNER JOIN user_lesson_progress ulp
            ON ulp.lesson_id = l.lesson_id
           AND ulp.user_id = ?
           AND ulp.status = 'completed'
        WHERE l.status = 'published'
          AND mo.status = 'published'
    ";

    $globalLessonStmt = $conn->prepare($globalLessonSql);

    if ($globalLessonStmt) {

        $globalLessonStmt->bind_param("i", $userId);
        $globalLessonStmt->execute();

        $globalLessonCount = $globalLessonStmt
            ->get_result()
            ->fetch_assoc();

        $globalLessonStmt->close();

        awardMissionAchievements(
            $conn,
            $userId,
            "lessons_completed",
            (int) ($globalLessonCount["completed_count"] ?? 0)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. CHECK NEXT MISSION
    |--------------------------------------------------------------------------
    */
    $nextSql = "
        SELECT
            m.mission_id,
            m.mission_title
        FROM missions m
        INNER JOIN modules mo
            ON mo.module_id = m.module_id
        WHERE mo.course_id = ?
          AND m.status = 'published'
          AND (
                m.mission_order > ?
                OR (
                    m.mission_order = ?
                    AND m.mission_id > ?
                )
          )
        ORDER BY m.mission_order ASC, m.mission_id ASC
        LIMIT 1
    ";

    $nextStmt = $conn->prepare($nextSql);

    if ($nextStmt) {

        $nextStmt->bind_param(
            "iiii",
            $courseId,
            $currentOrder,
            $currentOrder,
            $missionId
        );

        $nextStmt->execute();

        $nextMission = $nextStmt->get_result()->fetch_assoc();
        $nextStmt->close();

        if ($nextMission) {

            $nextMissionId = (int) $nextMission["mission_id"];
            $nextMissionTitle = $nextMission["mission_title"];

            /*
             * Unlock the next mission without resetting
             * progress that already exists.
             */
            $unlockSql = "
                INSERT INTO user_mission_progress
                    (user_id, mission_id, status, progress_percent)
                VALUES (?, ?, 'not_started', 0)
                ON DUPLICATE KEY UPDATE
                    status = IF(
                        status = 'locked',
                        'not_started',
                        status
                    )
            ";

            $unlockStmt = $conn->prepare($unlockSql);

            if ($unlockStmt) {

                $unlockStmt->bind_param(
                    "ii",
                    $userId,
                    $nextMissionId
                );

                if (!$unlockStmt->execute()) {
                    error_log("Next mission unlock failed: " . $unlockStmt->error);
                }

                $unlockStmt->close();
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 7. CHECK WHETHER ALL PUBLISHED MISSIONS ARE COMPLETED
    |--------------------------------------------------------------------------
    */
    $countMissionSql = "
        SELECT
            COUNT(DISTINCT m.mission_id) AS total_missions,
            COUNT(
                DISTINCT CASE
                    WHEN ump.status = 'completed'
                    THEN m.mission_id
                END
            ) AS completed_missions
        FROM missions m
        INNER JOIN modules mo
            ON mo.module_id = m.module_id
        LEFT JOIN user_mission_progress ump
            ON ump.mission_id = m.mission_id
           AND ump.user_id = ?
        WHERE mo.course_id = ?
          AND mo.status = 'published'
          AND m.status = 'published'
    ";

    $countMissionStmt = $conn->prepare($countMissionSql);

    if ($countMissionStmt) {

        $countMissionStmt->bind_param("ii", $userId, $courseId);
        $countMissionStmt->execute();

        $missionCounts = $countMissionStmt
            ->get_result()
            ->fetch_assoc();

        $countMissionStmt->close();

        $totalMissions = (int) ($missionCounts["total_missions"] ?? 0);
        $completedMissions = (int) ($missionCounts["completed_missions"] ?? 0);
    }

    $allMissionsCompleted = (
        $totalMissions > 0 &&
        $completedMissions >= $totalMissions
    );

    /*
    |--------------------------------------------------------------------------
    | 8. CHECK WHETHER ALL PUBLISHED LESSONS ARE COMPLETED
    |--------------------------------------------------------------------------
    */
    $countLessonSql = "
        SELECT
            COUNT(DISTINCT l.lesson_id) AS total_lessons,
            COUNT(
                DISTINCT CASE
                    WHEN ulp.status = 'completed'
                    THEN l.lesson_id
                END
            ) AS completed_lessons
        FROM lessons l
        INNER JOIN modules mo
            ON mo.module_id = l.module_id
        LEFT JOIN user_lesson_progress ulp
            ON ulp.lesson_id = l.lesson_id
           AND ulp.user_id = ?
        WHERE mo.course_id = ?
          AND mo.status = 'published'
          AND l.status = 'published'
    ";

    $countLessonStmt = $conn->prepare($countLessonSql);

    if ($countLessonStmt) {

        $countLessonStmt->bind_param("ii", $userId, $courseId);
        $countLessonStmt->execute();

        $lessonCounts = $countLessonStmt
            ->get_result()
            ->fetch_assoc();

        $countLessonStmt->close();

        $totalLessons = (int) ($lessonCounts["total_lessons"] ?? 0);
        $completedLessons = (int) ($lessonCounts["completed_lessons"] ?? 0);
    }

    $allLessonsCompleted = (
        $totalLessons > 0 &&
        $completedLessons >= $totalLessons
    );

    /*
    |--------------------------------------------------------------------------
    | 9. AWARD COURSE MASTER
    |--------------------------------------------------------------------------
    | Count courses where every published lesson is completed.
    */
    $completedCoursesSql = "
        SELECT COUNT(*) AS completed_courses
        FROM courses c
        WHERE c.status = 'published'
          AND EXISTS (
              SELECT 1
              FROM modules mo
              INNER JOIN lessons l
                  ON l.module_id = mo.module_id
              WHERE mo.course_id = c.course_id
                AND mo.status = 'published'
                AND l.status = 'published'
          )
          AND NOT EXISTS (
              SELECT 1
              FROM modules mo
              INNER JOIN lessons l
                  ON l.module_id = mo.module_id
              LEFT JOIN user_lesson_progress ulp
                  ON ulp.lesson_id = l.lesson_id
                 AND ulp.user_id = ?
              WHERE mo.course_id = c.course_id
                AND mo.status = 'published'
                AND l.status = 'published'
                AND (
                    ulp.status IS NULL
                    OR ulp.status <> 'completed'
                )
          )
    ";

    $completedCoursesStmt = $conn->prepare($completedCoursesSql);

    if ($completedCoursesStmt) {

        $completedCoursesStmt->bind_param("i", $userId);
        $completedCoursesStmt->execute();

        $completedCoursesRow = $completedCoursesStmt
            ->get_result()
            ->fetch_assoc();

        $completedCoursesStmt->close();

        awardMissionAchievements(
            $conn,
            $userId,
            "courses_completed",
            (int) ($completedCoursesRow["completed_courses"] ?? 0)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 10. FINAL QUEST ELIGIBILITY
    |--------------------------------------------------------------------------
    */
    $finalQuestAvailable = $allMissionsCompleted;
}

/*
|--------------------------------------------------------------------------
| 11. PAGE DISPLAY
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $isCompleted ? "Mission Complete" : "Mission Not Complete" ?>
        - EduQuest
    </title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* COMPLETED ACTIVITY HISTORY */

        .activity-history-section {
            width: 100%;
            margin-top: 30px;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #e8e8e8;
            border-radius: 16px;
        }

        .activity-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
        }

        .activity-history-header h2 {
            margin: 7px 0;
            color: #202020;
            font-size: 23px;
        }

        .activity-history-header p {
            color: #777777;
            font-size: 14px;
        }

        .activity-count {
            padding: 8px 13px;
            background: #eef5e9;
            color: #39732d;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .activity-history-list {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .activity-history-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px;
            border: 1px solid #eeeeee;
            border-radius: 12px;
            background: #fafafa;
        }

        .activity-history-info {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .activity-check {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e6f4e4;
            color: #39843a;
            border-radius: 50%;
            font-weight: bold;
        }

        .activity-history-info h3 {
            color: #252525;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .activity-history-info p {
            color: #777777;
            font-size: 12px;
        }

        .activity-grade {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .activity-grade strong {
            color: #39843a;
            font-size: 21px;
        }

        .activity-grade span {
            color: #777777;
            font-size: 12px;
        }

        .review-activity-btn {
            display: inline-block;
            padding: 10px 16px;
            background: #252525;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: 0.2s ease;
        }

        .review-activity-btn:hover {
            background: #444444;
        }

        .empty-activity-history {
            padding: 35px 15px;
            text-align: center;
            background: #fafafa;
            border: 1px dashed #dddddd;
            border-radius: 12px;
        }

        .empty-activity-history > span {
            font-size: 30px;
        }

        .empty-activity-history h3 {
            margin: 12px 0 7px;
            color: #333333;
        }

        .empty-activity-history p {
            color: #777777;
            font-size: 13px;
        }

        @media (max-width: 650px) {

            .activity-history-section {
                padding: 18px;
            }

            .activity-history-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .activity-history-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .activity-grade {
                align-items: flex-start;
            }

            .review-activity-btn {
                width: 100%;
                text-align: center;
            }
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

                <span class="notification">🔔</span>

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
                        <?= missionEscape($_SESSION["full_name"] ?? "Student") ?>
                    </span>
                </div>

            </div>

        </header>

        <main class="learning-container">

            <section class="completion-card">

                <?php if ($isCompleted): ?>

                    <div class="completion-icon">✓</div>

                    <span class="section-label">
                        MISSION COMPLETE
                    </span>

                    <h1>Mission Completed!</h1>

                    <p>
                        Great job! You successfully completed
                        <?= missionEscape($missionTitle) ?>.
                    </p>

                    <?php if ($finalQuestAvailable): ?>

                        <div class="unlock-box">

                            <span class="unlock-icon">🏆</span>

                            <div>
                                <h3>Final Quest Unlocked!</h3>

                                <p>
                                    You completed all
                                    <?= $totalMissions ?> published missions
                                    in this course. You can now take the
                                    Final Quest.
                                </p>
                            </div>

                        </div>

                        <?php if ($allLessonsCompleted): ?>

                            <div class="unlock-box">

                                <span class="unlock-icon">🎓</span>

                                <div>
                                    <h3>Course Lessons Completed!</h3>

                                    <p>
                                        You completed all
                                        <?= $totalLessons ?> published lessons
                                        in this course.
                                    </p>
                                </div>

                            </div>

                        <?php endif; ?>

                        <div class="completion-actions">

                            <a
                                href="Final_quest.php?course_id=<?= $courseId ?>"
                                class="primary-btn"
                            >
                                Start Final Quest
                            </a>

                            <a
                                href="DashboardPage.php"
                                class="secondary-btn"
                            >
                                Back to Dashboard
                            </a>

                        </div>

                    <?php elseif ($nextMissionId !== null): ?>

                        <div class="unlock-box">

                            <span class="unlock-icon">🔓</span>

                            <div>
                                <h3>Next Mission Unlocked</h3>

                                <p>
                                    You can now continue to
                                    <?= missionEscape($nextMissionTitle) ?>.
                                </p>
                            </div>

                        </div>

                        <div class="completion-actions">

                            <a
                                href="MissionsPage.php?mission_id=<?= $nextMissionId ?>"
                                class="primary-btn"
                            >
                                Continue to Next Mission
                            </a>

                            <a
                                href="DashboardPage.php"
                                class="secondary-btn"
                            >
                                Back to Dashboard
                            </a>

                        </div>

                    <?php else: ?>

                        <div class="unlock-box">

                            <span class="unlock-icon">ℹ️</span>

                            <div>
                                <h3>No Next Mission Available</h3>

                                <p>
                                    There are no further published missions
                                    available from this point. Check that all
                                    missions in this course are published and
                                    marked as completed.
                                </p>
                            </div>

                        </div>

                        <div class="completion-actions">

                            <a
                                href="MissionsPage.php"
                                class="primary-btn"
                            >
                                Return to Missions
                            </a>

                            <a
                                href="DashboardPage.php"
                                class="secondary-btn"
                            >
                                Back to Dashboard
                            </a>

                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="completion-icon">!</div>

                    <span class="section-label">
                        MISSION INCOMPLETE
                    </span>

                    <h1>Challenge Not Completed Yet</h1>

                    <p>
                        <?= missionEscape($missionTitle) ?> has not
                        been marked as completed in your progress.
                        Complete and pass its challenge before
                        continuing to the next mission.
                    </p>

                    <div class="completion-actions">

                        <a
                            href="Challenge.php?mission_id=<?= (int) $missionId ?>"
                            class="primary-btn"
                        >
                            Return to Challenge
                        </a>

                        <a
                            href="MissionsPage.php?mission_id=<?= (int) $missionId ?>"
                            class="secondary-btn"
                        >
                            Back to Missions
                        </a>

                    </div>

                <?php endif; ?>

            </section>


        </main>

    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode-active");
        }
    </script>

</body>
</html>
