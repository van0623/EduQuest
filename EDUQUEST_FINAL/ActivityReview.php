<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";
require_once __DIR__ . "/student_auth.php";

$conn->set_charset("utf8mb4");

$userId = (int) $_SESSION["user_id"];
$attemptId = (int) ($_GET["attempt_id"] ?? 0);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if ($attemptId <= 0) {
    header("Location: ActivityRecords.php");
    exit;
}

/* Get the selected attempt belonging to the logged-in student */
$stmt = $conn->prepare("
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
        a.activity_description,
        a.instructions
    FROM activity_attempts aa
    INNER JOIN activities a
        ON a.activity_id = aa.activity_id
    WHERE aa.attempt_id = ?
      AND aa.user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $attemptId, $userId);
$stmt->execute();

$attempt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$attempt) {
    header("Location: ActivityRecords.php");
    exit;
}

/* Get the questions and the student's saved answers */
$stmt = $conn->prepare("
    SELECT
        q.question_id,
        q.question_text,
        q.question_type,
        q.points,
        q.question_order,
        q.explanation,
        aa.answer_text,
        aa.selected_option_id,
        aa.is_correct,
        aa.points_awarded,
        selected.option_text AS selected_option_text
    FROM activity_questions q
    LEFT JOIN activity_answers aa
        ON aa.question_id = q.question_id
       AND aa.attempt_id = ?
    LEFT JOIN activity_options selected
        ON selected.option_id = aa.selected_option_id
    WHERE q.activity_id = ?
    ORDER BY q.question_order ASC, q.question_id ASC
");

$activityId = (int) $attempt["activity_id"];

$stmt->bind_param("ii", $attemptId, $activityId);
$stmt->execute();

$questionResult = $stmt->get_result();

$questions = [];

while ($question = $questionResult->fetch_assoc()) {
    $questions[] = $question;
}

$stmt->close();

/* Format submission date */
$submittedDate = !empty($attempt["submitted_at"])
    ? date("M d, Y h:i A", strtotime($attempt["submitted_at"]))
    : "Not available";

$status = strtolower(trim((string) $attempt["status"]));

$statusClass = $status === "passed"
    ? "passed"
    : ($status === "failed" ? "failed" : "other");

$totalQuestions = count($questions);
$correctAnswers = 0;

foreach ($questions as $question) {
    if ((int) ($question["is_correct"] ?? 0) === 1) {
        $correctAnswers++;
    }
}

$percent = max(0, min(100, (float) $attempt["percentage"]));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Review | EduQuest</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

/* =========================================
   VARIABLES
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
    --surface-2: #F4F4F2;
    --text: var(--black);
    --muted: var(--gray);
    --line: var(--border);

    --red: #D93636;
    --red-soft: #FCEAEA;
    --green-soft: #E8F8E0;
    --green-text: var(--dark-green);

    --radius: 18px;
    --card-shadow: 0 5px 18px rgba(15, 15, 15, 0.05);
}

body.dark-mode-active {
    --bg: #0A0A0A;
    --surface: #161616;
    --surface-2: #1E1E1E;
    --text: #F2F2F2;
    --muted: #A0A0A0;
    --line: #303030;

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
   LAYOUT
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
   MAIN + TOPBAR
========================================= */

.main-content {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    width: calc(100% - 245px);
    margin-left: 245px;
    min-width: 0;
}

.topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    min-height: 78px;
    padding: 12px 5%;
    background: var(--surface);
    border-bottom: 1px solid var(--line);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border: 1px solid var(--line);
    border-radius: 40px;
    background: var(--surface);
    color: var(--text);
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.back-link:hover {
    border-color: var(--green);
    background: var(--green);
    color: var(--black);
}

.icon-button {
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
   PAGE CONTAINER
========================================= */

.container {
    flex: 1;
    width: 90%;
    max-width: 1000px;
    min-width: 0;
    margin: 0 auto;
    padding: 45px 0 55px;
}

/* =========================================
   HEADING
========================================= */

.heading {
    margin-bottom: 32px;
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

h1 {
    margin-bottom: 12px;
    font-size: 34px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.035em;
}

.description {
    max-width: 700px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
}

.instructions-box {
    max-width: 700px;
    margin-top: 16px;
    padding: 14px 18px;
    border-left: 4px solid var(--green);
    border-radius: 0 12px 12px 0;
    background: var(--surface);
    box-shadow: var(--card-shadow);
}

.instructions-box .answer-label {
    margin-bottom: 4px;
}

.instructions-box p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

/* =========================================
   SUMMARY CARDS
========================================= */

.summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.summary-card {
    position: relative;
    min-width: 0;
    padding: 24px 24px 24px;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    box-shadow: var(--card-shadow);
}

.summary-card::before {
    position: absolute;
    top: 0;
    left: 24px;
    width: 34px;
    height: 4px;
    border-radius: 0 0 4px 4px;
    background: var(--green);
    content: "";
}

.summary-label {
    margin-bottom: 14px;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.summary-value {
    font-size: 26px;
    font-weight: 800;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

.summary-value small {
    color: var(--muted);
    font-size: 14px;
    font-weight: 600;
}

.summary-value.green {
    color: var(--green-text);
}

.summary .summary-card:first-child {
    border-color: var(--black);
    background:
        radial-gradient(260px 140px at 100% 0%, rgba(93, 214, 44, 0.22), transparent 70%),
        var(--black);
}

.summary .summary-card:first-child .summary-label {
    color: #B5B5B5;
}

.summary .summary-card:first-child .summary-value {
    color: var(--green);
}

.result-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 14px;
    border-radius: 40px;
    font-size: 12px;
    font-weight: 800;
    text-transform: capitalize;
}

.result-status::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    content: "";
}

.passed {
    background: var(--green-soft);
    color: var(--green-text);
}

.failed {
    background: var(--red-soft);
    color: var(--red);
}

.other {
    background: rgba(119, 119, 119, 0.15);
    color: var(--muted);
}

/* meta strip */

.meta-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 22px;
    margin-bottom: 8px;
    padding: 14px 20px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    color: var(--muted);
    font-size: 12px;
}

.meta-strip strong {
    color: var(--text);
    font-weight: 700;
}

/* =========================================
   REVIEW SECTION
========================================= */

.review-section {
    margin-top: 40px;
}

.section-title {
    margin-bottom: 20px;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.question-card {
    position: relative;
    margin-bottom: 14px;
    padding: 26px 28px 26px 32px;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    box-shadow: var(--card-shadow);
}

.question-card::before {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 5px;
    background: var(--gray);
    opacity: 0.45;
    content: "";
}

.question-card.is-correct::before {
    background: var(--green);
    opacity: 1;
}

.question-card.is-incorrect::before {
    background: var(--red);
    opacity: 1;
}

.question-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.question-number {
    color: var(--muted);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
}

.question-points {
    padding: 5px 12px;
    border-radius: 40px;
    background: var(--surface-2);
    color: var(--text);
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.question-text {
    margin-bottom: 20px;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.6;
    letter-spacing: -0.01em;
}

.answer-box {
    padding: 16px 18px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface-2);
}

.answer-label {
    margin-bottom: 6px;
    color: var(--muted);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.answer-text {
    font-size: 14px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.answer-text.empty {
    color: var(--muted);
    font-style: italic;
}

.answer-result {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 16px;
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 12px;
    font-weight: 800;
}

.answer-result.correct {
    background: var(--green-soft);
    color: var(--green-text);
}

.answer-result.incorrect {
    background: var(--red-soft);
    color: var(--red);
}

.answer-result.skipped {
    background: rgba(119, 119, 119, 0.15);
    color: var(--muted);
}

.explanation {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed var(--line);
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

.explanation strong {
    color: var(--text);
}

.empty-questions {
    padding: 50px 30px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--muted);
    font-size: 14px;
    text-align: center;
}

/* =========================================
   BOTTOM ACTIONS
========================================= */

.bottom-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    padding: 0 24px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    transition: 0.2s ease;
}

.primary-btn {
    background: var(--green);
    color: var(--black);
    box-shadow: 0 8px 22px rgba(93, 214, 44, 0.25);
}

.primary-btn:hover {
    transform: translateY(-2px);
}

.secondary-btn {
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--text);
}

.secondary-btn:hover {
    border-color: var(--green);
    background: var(--green-soft);
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
   RESPONSIVE
========================================= */

@media (max-width: 1000px) {

    .summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

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

    .container {
        width: 92%;
        padding-top: 30px;
    }

    h1 {
        font-size: 27px;
    }

    .question-card {
        padding: 20px 18px 20px 24px;
    }

    .question-text {
        font-size: 15px;
    }
}

@media (max-width: 480px) {

    .summary {
        gap: 10px;
    }

    .summary-card {
        padding: 20px 16px;
    }

    .summary-card::before {
        left: 16px;
    }

    .summary-value {
        font-size: 21px;
    }

    h1 {
        font-size: 24px;
    }

    .back-link {
        padding: 8px 13px;
        font-size: 11px;
    }

    .bottom-actions .action-btn {
        width: 100%;
    }
}

@media (max-width: 380px) {

    .sidebar {
        width: 62px;
        padding: 20px 7px;
    }

    .main-content {
        width: calc(100% - 62px);
        margin-left: 62px;
    }

    .summary {
        grid-template-columns: 1fr;
    }
}



    </style>
</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
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

    <!-- MAIN -->
    <div class="main-content">

        <header class="topbar">

            <a href="ActivityRecords.php" class="back-link">
                ← Back to Activity Records
            </a>

            <button
                type="button"
                class="icon-button"
                id="darkModeButton"
                aria-label="Toggle dark mode"
            >
                ☾
            </button>

        </header>

        <main class="container">

            <section class="heading">

                <div class="eyebrow">Activity Submission Review</div>

                <h1><?= e($attempt["activity_title"]) ?></h1>

                <?php if (!empty($attempt["activity_description"])): ?>
                    <p class="description">
                        <?= e($attempt["activity_description"]) ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($attempt["instructions"])): ?>
                    <div class="instructions-box">
                        <div class="answer-label">Instructions</div>
                        <p><?= nl2br(e($attempt["instructions"])) ?></p>
                    </div>
                <?php endif; ?>

            </section>

            <section class="summary">

                <div class="summary-card">
                    <div class="summary-label">Attempt Number</div>
                    <div class="summary-value">
                        #<?= (int) $attempt["attempt_number"] ?>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Final Score</div>
                    <div class="summary-value">
                        <?= number_format((float) $attempt["score"], 2) ?>
                        <small>/ <?= number_format((float) $attempt["total_points"], 2) ?></small>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Percentage</div>
                    <div class="summary-value green">
                        <?= number_format($percent, 1) ?>%
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Result</div>
                    <div class="summary-value">
                        <span class="result-status <?= e($statusClass) ?>">
                            <?= e($status ?: "Unknown") ?>
                        </span>
                    </div>
                </div>

            </section>

            <div class="meta-strip">
                <span>Submitted: <strong><?= e($submittedDate) ?></strong></span>
                <span>Correct answers: <strong><?= $correctAnswers ?> / <?= $totalQuestions ?></strong></span>
            </div>

            <section class="review-section">

                <h2 class="section-title">Your Answer Review</h2>

                <?php if (!empty($questions)): ?>

                    <?php foreach ($questions as $index => $question): ?>

                        <?php
                            $isAnswered = $question["answer_text"] !== null
                                || $question["selected_option_id"] !== null;

                            $isCorrect = (int) ($question["is_correct"] ?? 0) === 1;

                            $yourAnswer = "No answer submitted";

                            if ($question["selected_option_text"] !== null) {
                                $yourAnswer = $question["selected_option_text"];
                            } elseif ($question["answer_text"] !== null && $question["answer_text"] !== "") {
                                $decodedAnswer = json_decode($question["answer_text"], true);

                                if (is_array($decodedAnswer)) {
                                    $yourAnswer = implode(", ", array_map(
                                        "strval",
                                        $decodedAnswer
                                    ));
                                } else {
                                    $yourAnswer = $question["answer_text"];
                                }
                            }

                            if (!$isAnswered) {
                                $cardClass = "";
                            } else {
                                $cardClass = $isCorrect ? "is-correct" : "is-incorrect";
                            }
                        ?>

                        <article class="question-card <?= $cardClass ?>">

                            <div class="question-top">
                                <span class="question-number">
                                    QUESTION <?= $index + 1 ?>
                                </span>

                                <span class="question-points">
                                    <?= number_format((float) $question["points_awarded"], 2) ?>
                                    /
                                    <?= number_format((float) $question["points"], 2) ?>
                                    pts
                                </span>
                            </div>

                            <div class="question-text">
                                <?= nl2br(e($question["question_text"])) ?>
                            </div>

                            <div class="answer-box">
                                <div class="answer-label">Your Answer</div>

                                <div class="answer-text <?= $isAnswered ? "" : "empty" ?>">
                                    <?= nl2br(e($yourAnswer)) ?>
                                </div>
                            </div>

                            <?php if (!$isAnswered): ?>

                                <div class="answer-result skipped">
                                    – Not Answered
                                </div>

                            <?php else: ?>

                                <div class="answer-result <?= $isCorrect ? "correct" : "incorrect" ?>">
                                    <?= $isCorrect ? "✓ Correct Answer" : "✕ Incorrect Answer" ?>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($question["explanation"])): ?>
                                <div class="explanation">
                                    <strong>Explanation:</strong>
                                    <?= nl2br(e($question["explanation"])) ?>
                                </div>
                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty-questions">
                        No questions were found for this activity.
                    </div>

                <?php endif; ?>

            </section>

            <div class="bottom-actions">
                <a href="ActivityRecords.php" class="action-btn primary-btn">
                    Back to Activity Records
                </a>

                <a href="DashboardPage.php" class="action-btn secondary-btn">
                    Back to Dashboard
                </a>
            </div>

        </main>

        <footer class="footer">
            © <?= date("Y") ?> EduQuest. Keep learning and keep improving.
        </footer>

    </div>

</div>

<script>

/* =========================================
   DARK MODE (shared with other pages)
========================================= */

const darkModeButton = document.getElementById("darkModeButton");

if (localStorage.getItem("eduquest-dark-mode") === "enabled") {

    document.body.classList.add("dark-mode-active");

    if (darkModeButton) {
        darkModeButton.textContent = "☀";
    }
}

if (darkModeButton) {

    darkModeButton.addEventListener("click", function () {

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