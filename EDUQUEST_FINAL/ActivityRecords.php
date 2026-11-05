<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";
require_once __DIR__ . "/student_auth.php";

$conn->set_charset("utf8mb4");

/* =========================================
   HELPER
========================================= */

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/* =========================================
   VALIDATE USER AND FETCH PROFILE
========================================= */

$user = null;

if (!empty($_SESSION["user_id"])) {

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

        $stmt->bind_param(
            "ss",
            $loginIdentifier,
            $loginIdentifier
        );

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

/* =========================================
   PROFILE INFORMATION
========================================= */

$displayName = !empty($user["username"])
    ? $user["username"]
    : $user["full_name"];

$firstLetter = strtoupper(
    substr(trim($displayName), 0, 1)
);

/* =========================================
   PROFILE IMAGE PATH
========================================= */

$profileImage = trim(
    (string) ($user["profile_image"] ?? "")
);

if (!empty($profileImage)) {

    $profileImage = str_replace("\\", "/", $profileImage);

    // Keep external image URLs and data URLs unchanged.
    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, "data:") !== 0
    ) {

        $profileImage = ltrim($profileImage, "/");

        // Admin profile images need the Admin_Portal prefix.
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

/* =========================================
   PAGE INFORMATION
========================================= */

$pageTitle = "My Activity Records";

$records = [];

/* =========================================
   GET LATEST ACTIVITY ATTEMPT PER ACTIVITY
========================================= */

$activitySql = "
    SELECT
        aa.attempt_id,
        aa.activity_id,
        aa.attempt_number,
        aa.score,
        aa.total_points,
        aa.percentage,
        aa.status,
        aa.started_at,
        aa.submitted_at,
        a.activity_title,
        a.activity_description
    FROM activity_attempts aa
    INNER JOIN activities a
        ON a.activity_id = aa.activity_id
    INNER JOIN (
        SELECT
            activity_id,
            MAX(attempt_id) AS latest_attempt_id
        FROM activity_attempts
        WHERE user_id = ?
        GROUP BY activity_id
    ) latest
        ON latest.latest_attempt_id = aa.attempt_id
    WHERE aa.user_id = ?
";

$activityStmt = $conn->prepare($activitySql);

if (!$activityStmt) {
    die("Unable to load activity records.");
}

$activityStmt->bind_param("ii", $userId, $userId);
$activityStmt->execute();

$activityResult = $activityStmt->get_result();

while ($row = $activityResult->fetch_assoc()) {

    $row["record_type"] = "Activity";
    $row["record_title"] = $row["activity_title"];
    $row["record_description"] = $row["activity_description"];

    $records[] = $row;
}

$activityStmt->close();

/* =========================================
   GET LATEST CHALLENGE ATTEMPT PER CHALLENGE
========================================= */

$challengeSql = "
    SELECT
        ca.attempt_id,
        ca.challenge_id,
        ca.attempt_number,
        ca.score,
        ch.total_points,
        CASE
            WHEN ch.total_points > 0
            THEN ROUND((ca.score / ch.total_points) * 100, 2)
            ELSE 0
        END AS percentage,
        ca.status,
        ca.feedback,
        ca.started_at,
        ca.submitted_at,
        ch.challenge_title
    FROM challenge_attempts ca
    INNER JOIN challenges ch
        ON ch.challenge_id = ca.challenge_id
    INNER JOIN (
        SELECT
            challenge_id,
            MAX(attempt_id) AS latest_attempt_id
        FROM challenge_attempts
        WHERE user_id = ?
        GROUP BY challenge_id
    ) latest
        ON latest.latest_attempt_id = ca.attempt_id
    WHERE ca.user_id = ?
";

$challengeStmt = $conn->prepare($challengeSql);

if (!$challengeStmt) {
    die("Unable to load challenge records. Please check your challenge table columns.");
}

$challengeStmt->bind_param("ii", $userId, $userId);
$challengeStmt->execute();

$challengeResult = $challengeStmt->get_result();

while ($row = $challengeResult->fetch_assoc()) {

    $isPassed = stripos(
        $row["feedback"] ?? "",
        "Result: PASSED."
    ) !== false;

    $row["record_type"] = "Challenge";
    $row["record_title"] = $row["challenge_title"];
    $row["record_description"] = "Challenge mission";
    $row["percentage"] = (float) $row["percentage"];

    $row["display_status"] = $isPassed ? "Passed" : "Failed";

    $records[] = $row;
}

$challengeStmt->close();

/* =========================================
   GET LATEST FINAL QUEST ATTEMPT PER ASSESSMENT
========================================= */

$finalQuestSql = "
    SELECT
        faa.final_attempt_id AS attempt_id,
        faa.final_assessment_id,
        faa.attempt_number,
        faa.score,
        fa.total_points,
        CASE
            WHEN fa.total_points > 0
            THEN ROUND((faa.score / fa.total_points) * 100, 2)
            ELSE 0
        END AS percentage,
        faa.status,
        faa.started_at,
        faa.submitted_at,
        fa.assessment_title,
        fa.course_id
    FROM final_assessment_attempts faa
    INNER JOIN final_assessments fa
        ON fa.final_assessment_id = faa.final_assessment_id
    INNER JOIN (
        SELECT
            final_assessment_id,
            MAX(final_attempt_id) AS latest_attempt_id
        FROM final_assessment_attempts
        WHERE user_id = ?
        GROUP BY final_assessment_id
    ) latest
        ON latest.latest_attempt_id = faa.final_attempt_id
    WHERE faa.user_id = ?
";

$finalQuestStmt = $conn->prepare($finalQuestSql);

if (!$finalQuestStmt) {
    die("Unable to load Final Quest records. Please check your final assessment table columns.");
}

$finalQuestStmt->bind_param("ii", $userId, $userId);
$finalQuestStmt->execute();

$finalQuestResult = $finalQuestStmt->get_result();

while ($row = $finalQuestResult->fetch_assoc()) {

    $row["record_type"] = "Final Quest";
    $row["record_title"] = $row["assessment_title"];
    $row["record_description"] = "Final course assessment";
    $row["percentage"] = (float) $row["percentage"];

    $row["display_status"] = ucfirst(
        strtolower(trim($row["status"] ?? ""))
    );

    $records[] = $row;
}

$finalQuestStmt->close();

/* =========================================
   SORT RECORDS BY SUBMISSION DATE
========================================= */

usort($records, function ($a, $b) {

    $dateA = !empty($a["submitted_at"])
        ? strtotime($a["submitted_at"])
        : 0;

    $dateB = !empty($b["submitted_at"])
        ? strtotime($b["submitted_at"])
        : 0;

    if ($dateA === $dateB) {
        return (int) $b["attempt_id"] <=> (int) $a["attempt_id"];
    }

    return $dateB <=> $dateA;
});

/* =========================================
   CALCULATE STATISTICS
========================================= */

$totalAttempts = count($records);
$totalActivities = 0;
$totalChallenges = 0;
$totalFinalQuests = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($records as $record) {

    if ($record["record_type"] === "Activity") {

        $totalActivities++;

        $status = strtolower(
            trim($record["status"] ?? "")
        );

    } elseif ($record["record_type"] === "Challenge") {

        $totalChallenges++;

        $status = strtolower(
            trim($record["display_status"] ?? "")
        );

    } else {

        $totalFinalQuests++;

        $status = strtolower(
            trim($record["display_status"] ?? "")
        );
    }

    if ($status === "passed") {
        $totalPassed++;
    } elseif ($status === "failed") {
        $totalFailed++;
    }
}

/* =========================================
   FETCH UNREAD NOTIFICATIONS
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

if (!$stmt) {
    die("Unable to load notification count.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$unreadNotifications = (int) (
    $stmt->get_result()->fetch_assoc()["total"] ?? 0
);

$stmt->close();

/* =========================================
   FETCH LATEST 5 NOTIFICATIONS
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

if (!$stmt) {
    die("Unable to load notifications.");
}

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

    <title><?= e($pageTitle) ?> | EduQuest</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

/* =========================================
   GLOBAL VARIABLES
========================================= */

:root {
    --black: #0F0F0F;
    --dark-gray: #202020;
    --green: #5DD62C;
    --dark-green: #337418;
    --off-white: #F8F8F8;
    --white: #FFFFFF;
    --gray: #777777;
    --border: #E5E5E5;

    --bg: var(--off-white);
    --surface: var(--white);
    --text: var(--black);
    --muted: var(--gray);
    --line: var(--border);

    --head-bg: #F4F4F2;
    --row-hover: rgba(93, 214, 44, 0.06);

    --red: #D93636;
    --red-soft: #FCEAEA;

    --green-soft: #E8F8E0;
    --green-text: var(--dark-green);

    --radius: 18px;
    --card-shadow: 0 5px 18px rgba(15, 15, 15, 0.05);
}

/* =========================================
   DARK MODE VARIABLES
========================================= */

body.dark-mode-active {
    --bg: #0A0A0A;
    --surface: #161616;
    --text: #F2F2F2;
    --muted: #A0A0A0;
    --line: #303030;

    --head-bg: #1C1C1C;
    --row-hover: rgba(93, 214, 44, 0.07);

    --red: #FF7777;
    --red-soft: #3A1E1E;

    --green-soft: #1F3517;
    --green-text: var(--green);

    --card-shadow: 0 5px 18px rgba(0, 0, 0, 0.4);
}

/* =========================================
   RESET
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: 'Poppins', 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    transition: background 0.25s ease, color 0.25s ease;
}

a {
    color: inherit;
    text-decoration: none;
}

button {
    font-family: inherit;
}

/* =========================================
   DASHBOARD LAYOUT
========================================= */

.dashboard-layout {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    width: 245px;
    height: 100vh;
    padding: 30px 18px;
    background: var(--dark-gray);
    flex-shrink: 0;
}

.brand {
    display: inline-block;
    margin: 0 15px 48px;
    color: var(--off-white);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 1px;
    white-space: nowrap;
}

.brand span {
    color: var(--off-white);
}

.brand strong {
    color: var(--green);
}

.menu-label {
    margin: 0 15px 15px;
    color: #929292;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
}

.sidebar-nav,
.sidebar-bottom {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 30px;
}

.nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 13px;
    min-height: 47px;
    padding: 0 15px;
    border-radius: 8px;
    color: #D1D1D1;
    font-size: 14px;
    transition: background 0.2s ease, color 0.2s ease;
}

.nav-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    flex-shrink: 0;
    font-size: 18px;
}

.nav-link:hover {
    color: var(--white);
    background: #303030;
}

.nav-link.active {
    color: var(--white);
    background: var(--dark-green);
    font-weight: 600;
}

.nav-link.active::before {
    position: absolute;
    top: 10px;
    bottom: 10px;
    left: 0;
    width: 3px;
    content: "";
    background: var(--green);
    border-radius: 0 4px 4px 0;
}

/* =========================================
   MAIN CONTENT
========================================= */

.main-content {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    width: calc(100% - 245px);
    margin-left: 245px;
    min-width: 0;
}

/* =========================================
   TOPBAR
========================================= */

.topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 20px;
    min-height: 78px;
    padding: 12px 5%;
    background: var(--surface);
    border-bottom: 1px solid var(--line);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.025);
}

.topbar-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 15px;
}

/* =========================================
   ICON BUTTONS
========================================= */

.icon-button {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface);
    color: var(--text);
    font-size: 19px;
    cursor: pointer;
    transition: 0.2s ease;
}

.icon-button:hover {
    border-color: var(--green);
    background: var(--green-soft);
}

/* =========================================
   NOTIFICATIONS
========================================= */

.notification-wrap {
    position: relative;
}

.notification-dot {
    position: absolute;
    top: 7px;
    right: 7px;
    width: 9px;
    height: 9px;
    border: 2px solid var(--surface);
    border-radius: 50%;
    background: #E53935;
}

.notification-dropdown {
    position: absolute;
    z-index: 1000;
    top: 52px;
    right: 0;
    display: none;
    width: 350px;
    max-width: calc(100vw - 30px);
    max-height: 420px;
    overflow-y: auto;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.notification-dropdown.show {
    display: block;
    animation: dropdownShow 0.2s ease;
}

@keyframes dropdownShow {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-dropdown h3 {
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
    color: var(--text);
    font-size: 16px;
}

.notification-dropdown > p {
    padding: 20px;
    color: var(--muted);
    font-size: 13px;
}

.notification-item {
    padding: 16px 20px;
    border-bottom: 1px solid var(--line);
    transition: background 0.2s ease;
}

.notification-item:hover {
    background: var(--row-hover);
}

.notification-item.unread {
    background: var(--row-hover);
}

.notification-item strong {
    display: block;
    margin-bottom: 6px;
    color: var(--text);
    font-size: 13px;
    line-height: 1.4;
}

.notification-item p {
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.notification-item small {
    color: var(--muted);
    font-size: 11px;
}

.view-link {
    display: block;
    padding: 16px 20px;
    color: var(--green-text);
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.view-link:hover {
    text-decoration: underline;
}

/* =========================================
   PROFILE
========================================= */

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #D9F5CE;
    border-radius: 50%;
    background: var(--green);
    color: var(--black);
    font-size: 16px;
    font-weight: 800;
}

.avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: 50%;
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.profile-info strong {
    max-width: 150px;
    overflow: hidden;
    color: var(--text);
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-info span {
    color: var(--muted);
    font-size: 12px;
}

/* =========================================
   PAGE CONTAINER
========================================= */

.container {
    flex: 1;
    width: 90%;
    max-width: 1280px;
    min-width: 0;
    margin: 0 auto;
    padding: 45px 0 55px;
}

/* =========================================
   PAGE HEADING
========================================= */

.page-heading {
    margin-bottom: 36px;
}

.eyebrow {
    display: inline-block;
    margin-bottom: 16px;
    padding: 6px 13px;
    border: 1px solid rgba(93, 214, 44, 0.45);
    border-radius: 40px;
    background: var(--green-soft);
    color: var(--green-text);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.page-heading h1 {
    margin-bottom: 12px;
    color: var(--text);
    font-size: 36px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.035em;
}

.page-heading p {
    max-width: 620px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
}

/* =========================================
   STATISTICS
========================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    position: relative;
    min-width: 0;
    padding: 24px 26px 26px;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    box-shadow: var(--card-shadow);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.stat-card::before {
    position: absolute;
    top: 0;
    left: 26px;
    width: 34px;
    height: 4px;
    border-radius: 0 0 4px 4px;
    background: var(--green);
    content: "";
}

.stat-card:hover {
    transform: translateY(-3px);
    border-color: rgba(93, 214, 44, 0.6);
}

.stat-label {
    margin-bottom: 14px;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.stat-value {
    color: var(--text);
    font-size: 40px;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.stat-value.green {
    color: var(--green-text);
}

.stat-value.red {
    color: var(--red);
}

.stats-grid .stat-card:first-child {
    border-color: var(--black);
    background:
        radial-gradient(260px 140px at 100% 0%, rgba(93, 214, 44, 0.22), transparent 70%),
        var(--black);
}

.stats-grid .stat-card:first-child .stat-label {
    color: #B5B5B5;
}

.stats-grid .stat-card:first-child .stat-value {
    color: var(--green);
}

/* =========================================
   RECORDS SECTION
========================================= */

.records-section {
    width: 100%;
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 22px;
    background: var(--surface);
    box-shadow: var(--card-shadow);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 24px 30px;
    border-bottom: 1px solid var(--line);
}

.section-header h2 {
    color: var(--text);
    font-size: 20px;
    font-weight: 800;
}

.section-header span {
    padding: 6px 13px;
    border-radius: 40px;
    background: var(--black);
    color: var(--green);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

/* =========================================
   TABLE
========================================= */

.table-wrapper {
    width: 100%;
    min-width: 0;
    overflow: hidden;
}

.table-wrapper table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.table-wrapper th:nth-child(1),
.table-wrapper td:nth-child(1) { width: 38%; }

.table-wrapper th:nth-child(2),
.table-wrapper td:nth-child(2) { width: 20%; }

.table-wrapper th:nth-child(3),
.table-wrapper td:nth-child(3) { width: 13%; }

.table-wrapper th:nth-child(4),
.table-wrapper td:nth-child(4) { width: 16%; }

.table-wrapper th:nth-child(5),
.table-wrapper td:nth-child(5) { width: 13%; }

.table-wrapper thead {
    background: var(--head-bg);
}

.table-wrapper th {
    padding: 16px 12px;
    color: var(--muted);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-align: left;
    text-transform: uppercase;
}

.table-wrapper th:first-child,
.table-wrapper td:first-child {
    padding-left: 30px;
}

.table-wrapper th:last-child,
.table-wrapper td:last-child {
    padding-right: 30px;
}

.table-wrapper td {
    padding: 22px 12px;
    border-top: 1px solid var(--line);
    color: var(--text);
    font-size: 13px;
    vertical-align: middle;
}

.table-wrapper tbody tr {
    transition: background 0.18s ease;
}

.table-wrapper tbody tr:hover {
    background: var(--row-hover);
}

/* =========================================
   RECORD CELL
========================================= */

.record-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.record-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.record-type::before {
    width: 6px;
    height: 6px;
    flex-shrink: 0;
    border-radius: 50%;
    background: currentColor;
    content: "";
}

.record-type.activity {
    background: var(--black);
    color: var(--green);
}

.record-type.challenge {
    background: var(--green-soft);
    color: var(--green-text);
}

.record-type.final-quest {
    background: var(--black);
    color: #FFFFFF;
}

.attempt-chip {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
}

.activity-name {
    margin-bottom: 4px;
    color: var(--text);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.activity-description {
    display: -webkit-box;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.55;
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* =========================================
   SCORE CELL
========================================= */

.score-line {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 4px 10px;
    margin-bottom: 9px;
}

.score {
    color: var(--text);
    font-size: 14px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.percentage {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.score-bar {
    width: 100%;
    max-width: 120px;
    height: 6px;
    border-radius: 20px;
    background: var(--line);
    overflow: hidden;
}

.score-bar-fill {
    height: 100%;
    border-radius: 20px;
    background: var(--gray);
}

.score-bar-fill.passed {
    background: linear-gradient(90deg, var(--dark-green), var(--green));
}

.score-bar-fill.failed {
    background: var(--red);
}

/* =========================================
   STATUS
========================================= */

.status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 40px;
    font-size: 11px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
}

.status::before {
    width: 6px;
    height: 6px;
    flex-shrink: 0;
    border-radius: 50%;
    background: currentColor;
    content: "";
}

.status.passed {
    background: var(--green-soft);
    color: var(--green-text);
}

.status.failed {
    background: var(--red-soft);
    color: var(--red);
}

.status.other {
    background: rgba(119, 119, 119, 0.15);
    color: var(--muted);
}

/* =========================================
   DATE
========================================= */

.date-main {
    color: var(--text);
    font-size: 13px;
    font-weight: 600;
}

.date-time {
    margin-top: 3px;
    color: var(--muted);
    font-size: 11px;
}

/* =========================================
   VIEW BUTTON
========================================= */

.view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 14px;
    border-radius: 10px;
    background: var(--black);
    color: var(--green);
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
    text-decoration: none;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.view-btn:hover {
    background: var(--green);
    color: var(--black);
    transform: translateX(2px);
}

/* =========================================
   EMPTY STATE
========================================= */

.empty-state {
    padding: 80px 20px;
    text-align: center;
}

.empty-icon {
    display: grid;
    place-items: center;
    width: 84px;
    height: 84px;
    margin: 0 auto 22px;
    border-radius: 24px;
    background: var(--black);
    box-shadow: 0 0 0 8px rgba(93, 214, 44, 0.12);
    font-size: 36px;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: var(--text);
    font-size: 22px;
    font-weight: 800;
}

.empty-state p {
    max-width: 380px;
    margin: 0 auto 26px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
}

.start-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 24px;
    border-radius: 12px;
    background: var(--green);
    color: var(--black);
    font-size: 13px;
    font-weight: 800;
    box-shadow: 0 8px 22px rgba(93, 214, 44, 0.25);
    transition: transform 0.2s ease;
}

.start-btn:hover {
    transform: translateY(-2px);
}

/* =========================================
   FOOTER
========================================= */

.footer {
    padding: 25px 15px;
    border-top: 1px solid var(--line);
    color: var(--muted);
    font-size: 12px;
    text-align: center;
}

/* =========================================
   RESPONSIVE - TABLET
========================================= */

@media (max-width: 1100px) {

    .container {
        width: 94%;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .table-wrapper th:first-child,
    .table-wrapper td:first-child {
        padding-left: 20px;
    }

    .table-wrapper th:last-child,
    .table-wrapper td:last-child {
        padding-right: 20px;
    }
}

/* =========================================
   RESPONSIVE - MOBILE (TABLE BECOMES CARDS)
========================================= */

@media (max-width: 760px) {

    .sidebar {
        width: 75px;
        padding: 25px 10px;
    }

    .brand {
        margin: 0 auto 45px;
        font-size: 0;
    }

    .brand span,
    .brand strong {
        font-size: 13px;
    }

    .menu-label,
    .nav-link > span:last-child {
        display: none;
    }

    .nav-link {
        justify-content: center;
        padding: 0;
    }

    .nav-icon {
        font-size: 20px;
    }

    .main-content {
        width: calc(100% - 75px);
        margin-left: 75px;
    }

    .topbar {
        min-height: 68px;
        padding: 10px 4%;
    }

    .topbar-actions {
        gap: 8px;
    }

    .profile-info {
        display: none;
    }

    .container {
        padding-top: 30px;
    }

    .page-heading h1 {
        font-size: 29px;
    }

    .section-header {
        padding: 18px 20px;
    }

    .section-header h2 {
        font-size: 16px;
    }

    /* Table becomes record cards */

    .table-wrapper {
        overflow: visible;
        padding: 14px;
    }

    .table-wrapper table,
    .table-wrapper tbody {
        display: block;
        width: 100%;
    }

    .table-wrapper thead {
        display: none;
    }

    .table-wrapper tbody tr {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 14px;
        padding: 18px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: var(--surface);
    }

    .table-wrapper tbody tr:last-child {
        margin-bottom: 0;
    }

    .table-wrapper tbody td,
    .table-wrapper td:first-child,
    .table-wrapper td:last-child {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 7px;
        width: 100%;
        min-width: 0;
        padding: 0;
        border: none;
    }

    .table-wrapper tbody td::before {
        display: block;
        color: var(--muted);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        content: attr(data-label);
    }

    /* The record cell is the card header, so no label */
    .table-wrapper tbody td:nth-child(1) {
        grid-column: 1 / -1;
    }

    .table-wrapper tbody td:nth-child(1)::before {
        display: none;
    }

    .table-wrapper tbody td:nth-child(2) {
        grid-column: 1 / -1;
    }

    .score-bar {
        max-width: 100%;
    }

    .table-wrapper tbody td:nth-child(5) {
        grid-column: 1 / -1;
    }

    .table-wrapper tbody td:nth-child(5) .view-btn {
        width: 100%;
        min-height: 40px;
    }

}

@media (max-width: 480px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 34px;
    }

    .notification-dropdown {
        right: -60px;
    }

    .page-heading h1 {
        font-size: 26px;
    }

    .section-header {
        gap: 8px;
        padding: 16px;
    }

    .section-header h2 {
        font-size: 14px;
    }

    .table-wrapper {
        padding: 10px;
    }

    .table-wrapper tbody tr {
        padding: 14px;
        gap: 13px;
    }

}

/* =========================================
   SMALL SCREEN SIDEBAR
========================================= */

@media (max-width: 380px) {

    .sidebar {
        width: 62px;
        padding: 20px 7px;
    }

    .main-content {
        width: calc(100% - 62px);
        margin-left: 62px;
    }

    .topbar-actions {
        gap: 6px;
    }

    .icon-button {
        width: 36px;
        height: 36px;
    }

    .avatar {
        width: 36px;
        height: 36px;
    }

}

    </style>

</head>

<body>

<div class="dashboard-layout">

    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <aside class="sidebar">

        <a href="DashboardPage.php" class="brand">
            <span>EDU</span><strong>QUEST</strong>
        </a>

        <p class="menu-label">MAIN MENU</p>

        <nav class="sidebar-nav">

            <a href="DashboardPage.php" class="nav-link">
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

            <a href="ActivityRecords.php" class="nav-link active">
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

    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <div class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-actions">

                <!-- NOTIFICATIONS -->

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

                            <p>No notifications yet.</p>

                        <?php else: ?>

                            <?php foreach ($notifications as $notification): ?>

                                <div class="notification-item <?= (int) $notification["is_read"] === 0 ? "unread" : "" ?>">

                                    <strong>
                                        <?= e($notification["notification_title"] ?? "Notification") ?>
                                    </strong>

                                    <p>
                                        <?= e($notification["notification_message"] ?? "") ?>
                                    </p>

                                    <small>
                                        <?= !empty($notification["created_at"])
                                            ? e(date("M d, Y h:i A", strtotime($notification["created_at"])))
                                            : "" ?>
                                    </small>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        <a href="NotificationPage.php" class="view-link">
                            View all notifications →
                        </a>

                    </div>

                </div>

                <!-- DARK MODE -->

                <button
                    type="button"
                    class="icon-button"
                    id="darkModeButton"
                    aria-label="Toggle dark mode"
                >
                    ☾
                </button>

                <!-- PROFILE -->

                <div class="profile">

                    <div class="avatar">

                        <?php if (!empty($profileImage)): ?>

                            <img
                                src="<?= e($profileImage) ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?= e($firstLetter) ?>';"
                            >

                        <?php else: ?>

                            <?= e($firstLetter) ?>

                        <?php endif; ?>

                    </div>

                    <div class="profile-info">

                        <strong>
                            <?= e($displayName) ?>
                        </strong>

                        <span>
                            <?= e(ucfirst($user["role"] ?? "student")) ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>

        <!-- PAGE CONTENT -->

        <main class="container">

            <section class="page-heading">

                <div class="eyebrow">
                    Your Learning Progress
                </div>

                <h1>My Activity Records</h1>

                <p>
                    View your previous activity, challenge, and Final Quest attempts,
                    scores, and results. Revisit your submitted assessments anytime.
                </p>

            </section>

            <!-- STATISTICS -->

            <section class="stats-grid">

                <div class="stat-card">

                    <div class="stat-label">
                        Total Attempts
                    </div>

                    <div class="stat-value">
                        <?= $totalAttempts ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-label">
                        Activities Attempted
                    </div>

                    <div class="stat-value">
                        <?= $totalActivities ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-label">
                        Passed Attempts
                    </div>

                    <div class="stat-value green">
                        <?= $totalPassed ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-label">
                        Failed Attempts
                    </div>

                    <div class="stat-value red">
                        <?= $totalFailed ?>
                    </div>

                </div>

            </section>

            <!-- ACTIVITY, CHALLENGE AND FINAL QUEST HISTORY -->

            <section class="records-section">

                <div class="section-header">

                    <h2>Learning Records</h2>

                    <span>
                        <?= $totalAttempts ?>
                        <?= $totalAttempts === 1 ? "record" : "records" ?>
                    </span>

                </div>

                <?php if (!empty($records)): ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>
                                <tr>
                                    <th>Record</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($records as $record): ?>

                                    <?php

                                        $isChallenge = $record["record_type"] === "Challenge";
                                        $isFinalQuest = $record["record_type"] === "Final Quest";

                                        $status = strtolower(trim(
                                            ($isChallenge || $isFinalQuest)
                                                ? ($record["display_status"] ?? "")
                                                : ($record["status"] ?? "")
                                        ));

                                        $statusClass = "other";

                                        if ($status === "passed") {
                                            $statusClass = "passed";
                                        } elseif ($status === "failed") {
                                            $statusClass = "failed";
                                        }

                                        $submittedAt = $record["submitted_at"] ?? null;

                                        $dateText = $submittedAt
                                            ? date("M d, Y", strtotime($submittedAt))
                                            : "Not available";

                                        $timeText = $submittedAt
                                            ? date("h:i A", strtotime($submittedAt))
                                            : "";

                                        $percent = max(0, min(100, (float) $record["percentage"]));

                                    ?>

                                    <tr>

                                        <!-- RECORD -->

                                        <td data-label="Record">

                                            <div class="record-meta">

                                                <span class="record-type <?= $isFinalQuest ? "final-quest" : ($isChallenge ? "challenge" : "activity") ?>">
                                                    <?= e($record["record_type"]) ?>
                                                </span>

                                                <span class="attempt-chip">
                                                    Attempt #<?= (int) $record["attempt_number"] ?>
                                                </span>

                                            </div>

                                            <div class="activity-name">
                                                <?= e($record["record_title"]) ?>
                                            </div>

                                            <div class="activity-description">
                                                <?= e($record["record_description"] ?? "") ?>
                                            </div>

                                        </td>

                                        <!-- SCORE -->

                                        <td data-label="Score">

                                            <div class="score-line">

                                                <span class="score">
                                                    <?= e($record["score"]) ?> / <?= e($record["total_points"]) ?>
                                                </span>

                                                <span class="percentage">
                                                    <?= number_format($percent, 1) ?>%
                                                </span>

                                            </div>

                                            <div class="score-bar">

                                                <div
                                                    class="score-bar-fill <?= e($statusClass) ?>"
                                                    style="width: <?= $percent ?>%;"
                                                ></div>

                                            </div>

                                        </td>

                                        <!-- RESULT -->

                                        <td data-label="Result">

                                            <span class="status <?= e($statusClass) ?>">
                                                <?= e($status ?: "Unknown") ?>
                                            </span>

                                        </td>

                                        <!-- DATE -->

                                        <td data-label="Submitted">

                                            <div class="date-main">
                                                <?= e($dateText) ?>
                                            </div>

                                            <?php if ($timeText !== ""): ?>

                                                <div class="date-time">
                                                    <?= e($timeText) ?>
                                                </div>

                                            <?php endif; ?>

                                        </td>

                                        <!-- ACTION -->

                                        <td data-label="Action">

                                            <?php if ($isFinalQuest): ?>

                                                <a
                                                    href="Final_quest.php?course_id=<?= (int) $record['course_id'] ?>&final_assessment_id=<?= (int) $record['final_assessment_id'] ?>&result_attempt=<?= (int) $record['attempt_id'] ?>"
                                                    class="view-btn"
                                                >
                                                    View Result →
                                                </a>

                                            <?php elseif ($isChallenge): ?>

                                                <a
                                                    href="Challenge.php?mission_id=<?= (int) $record['challenge_id'] ?>"
                                                    class="view-btn"
                                                >
                                                    View Result →
                                                </a>

                                            <?php else: ?>

                                                <a
                                                    href="ActivityReview.php?attempt_id=<?= (int) $record['attempt_id'] ?>"
                                                    class="view-btn"
                                                >
                                                    View Result →
                                                </a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <!-- EMPTY STATE -->

                    <div class="empty-state">

                        <div class="empty-icon">
                            📚
                        </div>

                        <h3>No Records Yet</h3>

                        <p>
                            You haven't submitted any activities, challenges, or Final Quests yet.
                            Complete a lesson and start your first assessment!
                        </p>

                        <a href="DashboardPage.php" class="start-btn">
                            Go to Dashboard
                        </a>

                    </div>

                <?php endif; ?>

            </section>

        </main>

        <!-- FOOTER -->

        <footer class="footer">

            © <?= date("Y") ?> EduQuest. Keep learning and keep improving.

        </footer>

    </div>

</div>

<script>

/* =========================================
   NOTIFICATION DROPDOWN
========================================= */

const notificationButton = document.getElementById("notificationButton");
const notificationDropdown = document.getElementById("notificationDropdown");

if (notificationButton && notificationDropdown) {

    notificationButton.addEventListener("click", function(event) {

        event.stopPropagation();

        const isOpen = notificationDropdown.classList.toggle("show");

        notificationButton.setAttribute(
            "aria-expanded",
            String(isOpen)
        );

    });

    notificationDropdown.addEventListener("click", function(event) {
        event.stopPropagation();
    });

    document.addEventListener("click", function(event) {

        if (!event.target.closest(".notification-wrap")) {

            notificationDropdown.classList.remove("show");

            notificationButton.setAttribute("aria-expanded", "false");

        }

    });

}

/* =========================================
   DARK MODE
========================================= */

const darkModeButton = document.getElementById("darkModeButton");

// Restore saved theme.
if (localStorage.getItem("eduquest-dark-mode") === "enabled") {

    document.body.classList.add("dark-mode-active");

    if (darkModeButton) {
        darkModeButton.textContent = "☀";
    }

}

if (darkModeButton) {

    darkModeButton.addEventListener("click", function() {

        document.body.classList.toggle("dark-mode-active");

        const isDarkMode = document.body.classList.contains("dark-mode-active");

        darkModeButton.textContent = isDarkMode ? "☀" : "☾";

        localStorage.setItem(
            "eduquest-dark-mode",
            isDarkMode ? "enabled" : "disabled"
        );

    });

}

</script>

</body>
</html>