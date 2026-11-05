
<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";
require_once __DIR__ . "/student_auth.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$user_id = (int) ($_SESSION["user_id"] ?? $_SESSION["student_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: login.php");
    exit;
}

$profileName = $_SESSION["username"] ?? $_SESSION["full_name"] ?? "Student";

if (empty($_SESSION["final_quest_csrf"])) {
    $_SESSION["final_quest_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["final_quest_csrf"];

if (!function_exists("e")) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

function runQuery(
    mysqli $conn,
    string $sql,
    string $types = "",
    array $params = []
): mysqli_stmt {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    return $stmt;
}

$error = "";
$fatalMessage = "";
$assessment = null;
$assessmentId = 0;
$courseId = (int) (
    $_GET["course_id"]
    ?? $_POST["course_id"]
    ?? 0
);

$questions = [];
$postedAnswers = [];
$resultAttempt = null;
$result = null;

/*
|--------------------------------------------------------------------------
| Read the selected course and its Final Quest
|--------------------------------------------------------------------------
*/

try {
    if ($courseId > 0) {
        $assessmentStmt = runQuery(
            $conn,
            "SELECT
                fa.*,
                c.course_id,
                c.course_title,
                c.course_number
             FROM final_assessments fa
             INNER JOIN courses c
                ON c.course_id = fa.course_id
             WHERE c.course_id = ?
               AND fa.status = 'published'
             LIMIT 1",
            "i",
            [$courseId]
        );

        $assessment = $assessmentStmt
            ->get_result()
            ->fetch_assoc();

        $assessmentStmt->close();
    } else {
        /*
         * When no course_id is supplied, identify a course the
         * student has already started or attempted.
         */
        $currentCourseStmt = runQuery(
            $conn,
            "SELECT mo.course_id
             FROM user_mission_progress ump
             INNER JOIN missions m
                ON m.mission_id = ump.mission_id
             INNER JOIN modules mo
                ON mo.module_id = m.module_id
             WHERE ump.user_id = ?

             UNION

             SELECT fa.course_id
             FROM final_assessment_attempts fat
             INNER JOIN final_assessments fa
                ON fa.final_assessment_id = fat.final_assessment_id
             WHERE fat.user_id = ?

             ORDER BY course_id ASC
             LIMIT 1",
            "ii",
            [$user_id, $user_id]
        );

        $currentCourse = $currentCourseStmt
            ->get_result()
            ->fetch_assoc();

        $currentCourseStmt->close();

        if ($currentCourse) {
            $courseId = (int) $currentCourse["course_id"];

            $assessmentStmt = runQuery(
                $conn,
                "SELECT
                    fa.*,
                    c.course_id,
                    c.course_title,
                    c.course_number
                 FROM final_assessments fa
                 INNER JOIN courses c
                    ON c.course_id = fa.course_id
                 WHERE c.course_id = ?
                   AND fa.status = 'published'
                 LIMIT 1",
                "i",
                [$courseId]
            );

            $assessment = $assessmentStmt
                ->get_result()
                ->fetch_assoc();

            $assessmentStmt->close();
        }
    }

    if (!$assessment) {
        $fatalMessage = $courseId > 0
            ? "There is no published Final Quest for this course yet."
            : "Please select a course before opening its Final Quest.";
    }

    if ($assessment) {
        $assessmentId = (int) $assessment["final_assessment_id"];

        /*
         * Make sure the student cannot open an assessment
         * belonging to another course by changing the URL.
         */
        if ((int) $assessment["course_id"] !== $courseId) {
            $assessment = null;
            $fatalMessage = "The selected Final Quest does not match your course.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load questions and answer options
    |--------------------------------------------------------------------------
    */

    if ($assessment) {
        $questionsStmt = runQuery(
            $conn,
            "SELECT
                question_id,
                question_text,
                points,
                question_order,
                correct_answer
             FROM final_assessment_questions
             WHERE final_assessment_id = ?
             ORDER BY question_order ASC, question_id ASC",
            "i",
            [$assessmentId]
        );

        $questionsResult = $questionsStmt->get_result();

        while ($row = $questionsResult->fetch_assoc()) {
            $row["options"] = [];
            $questions[(int) $row["question_id"]] = $row;
        }

        $questionsStmt->close();

        $optionsStmt = $conn->prepare(
            "SELECT option_id, option_text, option_order
             FROM final_assessment_options
             WHERE question_id = ?
             ORDER BY option_order ASC, option_id ASC"
        );

        foreach (array_keys($questions) as $questionId) {
            $optionsStmt->bind_param("i", $questionId);
            $optionsStmt->execute();

            $optionsResult = $optionsStmt->get_result();

            while ($option = $optionsResult->fetch_assoc()) {
                $questions[$questionId]["options"][] = $option;
            }
        }

        $optionsStmt->close();

        if (empty($questions)) {
            $fatalMessage = "This Final Quest has no questions yet.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show a saved result when requested
    |--------------------------------------------------------------------------
    */

    if (
        $assessment
        && isset($_GET["result_attempt"])
        && $fatalMessage === ""
    ) {
        $resultStmt = runQuery(
            $conn,
            "SELECT *
             FROM final_assessment_attempts
             WHERE final_attempt_id = ?
               AND user_id = ?
               AND final_assessment_id = ?
             LIMIT 1",
            "iii",
            [
                (int) $_GET["result_attempt"],
                $user_id,
                $assessmentId
            ]
        );

        $resultAttempt = $resultStmt
            ->get_result()
            ->fetch_assoc();

        $resultStmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | Grade and save the Final Quest
    |--------------------------------------------------------------------------
    */

    if (
        $assessment
        && $fatalMessage === ""
        && !$resultAttempt
        && $_SERVER["REQUEST_METHOD"] === "POST"
    ) {
        $postedToken = (string) ($_POST["csrf_token"] ?? "");

        if (!hash_equals($csrfToken, $postedToken)) {
            $error = "Your session expired. Please try again.";
        } else {
            $submittedAnswers = $_POST["answers"] ?? [];

            if (!is_array($submittedAnswers)) {
                $submittedAnswers = [];
            }

            $score = 0;
            $maximumPoints = 0;
            $unanswered = 0;
            $savedAnswers = [];

            foreach ($questions as $questionId => $question) {
                $maximumPoints += (int) $question["points"];

                $selectedOptionId = (
                    isset($submittedAnswers[$questionId])
                    && is_scalar($submittedAnswers[$questionId])
                )
                    ? (int) $submittedAnswers[$questionId]
                    : 0;

                $selectedText = null;

                foreach ($question["options"] as $option) {
                    if ((int) $option["option_id"] === $selectedOptionId) {
                        $selectedText = $option["option_text"];
                        break;
                    }
                }

                if ($selectedText === null) {
                    $unanswered++;
                    continue;
                }

                $postedAnswers[$questionId] = $selectedOptionId;

                $isCorrect = (
                    $selectedText === $question["correct_answer"]
                );

                if ($isCorrect) {
                    $score += (int) $question["points"];
                }

                $savedAnswers[$questionId] = [
                    "selected_option_id" => $selectedOptionId,
                    "selected_answer" => $selectedText,
                    "is_correct" => $isCorrect
                ];
            }

            if ($unanswered > 0) {
                $error = "Please answer all questions before submitting.";
            } else {
                $percentage = $maximumPoints > 0
                    ? ($score / $maximumPoints) * 100
                    : 0;

                $passingScore = (float) $assessment["passing_score"];
                $passed = $percentage >= $passingScore;
                $status = $passed ? "passed" : "failed";

                $assessmentTotalPoints = (float) $assessment["total_points"];

                $storedScore = $maximumPoints > 0
                    ? ($score / $maximumPoints) * $assessmentTotalPoints
                    : 0;

                $answersJson = json_encode(
                    $savedAnswers,
                    JSON_UNESCAPED_UNICODE
                );

                $feedback = $passed
                    ? "Result: PASSED. You passed the Final Quest."
                    : "Result: FAILED. Review the lessons and try again.";

                $conn->begin_transaction();

                try {
                    $attemptNumberStmt = runQuery(
                        $conn,
                        "SELECT COALESCE(MAX(attempt_number), 0) + 1
                            AS next_attempt
                         FROM final_assessment_attempts
                         WHERE user_id = ?
                           AND final_assessment_id = ?",
                        "ii",
                        [$user_id, $assessmentId]
                    );

                    $attemptNumber = (int) $attemptNumberStmt
                        ->get_result()
                        ->fetch_assoc()["next_attempt"];

                    $attemptNumberStmt->close();

                    $insertStmt = $conn->prepare(
                        "INSERT INTO final_assessment_attempts
                            (
                                user_id,
                                final_assessment_id,
                                attempt_number,
                                score,
                                status,
                                answers_json,
                                feedback,
                                started_at,
                                submitted_at,
                                graded_at
                            )
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())"
                    );

                    $insertStmt->bind_param(
                        "iiidsss",
                        $user_id,
                        $assessmentId,
                        $attemptNumber,
                        $storedScore,
                        $status,
                        $answersJson,
                        $feedback
                    );

                    $insertStmt->execute();

                    $newAttemptId = (int) $conn->insert_id;

                    $insertStmt->close();
                    $conn->commit();

                    header(
                        "Location: Final_quest.php?course_id="
                        . $courseId
                        . "&final_assessment_id="
                        . $assessmentId
                        . "&result_attempt="
                        . $newAttemptId
                    );
                    exit;

                } catch (Throwable $saveException) {
                    $conn->rollback();

                    error_log(
                        "Final Quest save error: "
                        . $saveException->getMessage()
                    );

                    $error = "Could not save your result. Please try again.";
                }
            }
        }
    }

} catch (Throwable $exception) {
    error_log("Final Quest error: " . $exception->getMessage());
    $fatalMessage = "Something went wrong while loading the Final Quest.";
}

/*
|--------------------------------------------------------------------------
| Prepare result information
|--------------------------------------------------------------------------
*/

$totalQuestions = count($questions);
$maximumPoints = 0;

foreach ($questions as $question) {
    $maximumPoints += (int) $question["points"];
}

$totalPoints = $assessment
    ? (float) $assessment["total_points"]
    : 0;

$passingScore = $assessment
    ? (float) $assessment["passing_score"]
    : 0;

if ($assessment && $resultAttempt) {
    $savedScore = (float) $resultAttempt["score"];
    $resultTotal = max(1, $totalPoints);

    $resultPercentage = min(
        100,
        ($savedScore / $resultTotal) * 100
    );

    $reviewAnswers = json_decode(
        (string) ($resultAttempt["answers_json"] ?? "{}"),
        true
    );

    if (!is_array($reviewAnswers)) {
        $reviewAnswers = [];
    }

    $correctCount = 0;

    foreach ($reviewAnswers as $answer) {
        if (!empty($answer["is_correct"])) {
            $correctCount++;
        }
    }

    $result = [
        "score" => $savedScore,
        "total" => $resultTotal,
        "percentage" => $resultPercentage,
        "passed" => strtolower((string) $resultAttempt["status"]) === "passed",
        "attempt" => (int) $resultAttempt["attempt_number"],
        "correct" => $correctCount,
        "answers" => $reviewAnswers
    ];
}

$letters = ["A", "B", "C", "D", "E", "F", "G", "H"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Final Quest - EduQuest</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/activity.css">
    <link rel="stylesheet" href="css/final_quest.css">
</head>

<body>

<div class="page-container">

    <header class="topbar">
        <div class="welcome">
            <h2>EduQuest</h2>
        </div>

        <div class="profile">
            <span class="notification" aria-label="Notifications">🔔</span>

            <button
                type="button"
                class="dark-mode-btn"
                onclick="toggleDarkMode()"
                aria-label="Toggle dark mode"
            >🌙</button>

            <div class="profile-info">
                <span class="profile-name"><?= e($profileName); ?></span>
            </div>
        </div>
    </header>

    <main class="learning-container">

        <div class="learning-header">
            <a href="MissionsPage.php" class="back-link">
                ← Back to Missions
            </a>

            <?php if ($assessment): ?>
                <span class="mission-number">Final Quest</span>
            <?php endif; ?>
        </div>

        <?php if ($fatalMessage !== ""): ?>

            <section class="learning-card quest-empty">
                <div class="quest-empty-icon">🧭</div>
                <h1>Final Quest unavailable</h1>
                <p class="lesson-intro"><?= e($fatalMessage); ?></p>

                <div class="quest-result-actions">
                    <a href="CoursePage.php" class="primary-btn">
                        Choose a Course
                    </a>
                </div>
            </section>

        <?php elseif ($result): ?>

            <div class="quest-wrapper">

                <section class="quest-intro">
                    <div>
                        <span class="quest-eyebrow">FINAL QUEST RESULT</span>
                        <h1 class="quest-heading">
                            <?= e($assessment["assessment_title"]); ?>
                        </h1>
                        <p class="quest-description">
                            <?= e($assessment["course_title"]); ?>
                        </p>
                    </div>

                    <div class="quest-level">
                        <span>ATTEMPT</span>
                        <strong><?= (int) $result["attempt"]; ?></strong>
                    </div>
                </section>

                <section class="quest-panel">

                    <div class="quest-result <?= $result["passed"] ? "passed" : "failed"; ?>">

                        <div class="quest-result-icon">
                            <?= $result["passed"] ? "🏆" : "💪"; ?>
                        </div>

                        <h2>
                            <?= $result["passed"]
                                ? "Quest completed!"
                                : "Keep practicing!"; ?>
                        </h2>

                        <p>
                            <?= $result["passed"]
                                ? "You passed this Final Quest."
                                : "Review your answers and try again."; ?>
                        </p>

                        <div class="quest-result-score">

                            <div class="quest-result-stat">
                                <strong><?= e(number_format($result["score"], 2)); ?></strong>
                                <span>POINTS EARNED</span>
                            </div>

                            <div class="quest-result-stat">
                                <strong><?= e(number_format($result["percentage"], 1)); ?>%</strong>
                                <span>FINAL SCORE</span>
                            </div>

                            <div class="quest-result-stat">
                                <strong><?= (int) $result["correct"]; ?>/<?= $totalQuestions; ?></strong>
                                <span>CORRECT</span>
                            </div>

                        </div>

                        <p>
                            Passing score:
                            <strong><?= e(number_format($passingScore, 0)); ?>%</strong>
                        </p>

                        <div class="quest-result-actions">

                            <?php if ($result["passed"]): ?>
                                <a href="CoursePage.php" class="primary-btn">
                                    Continue Learning →
                                </a>
                            <?php else: ?>
                                <a
                                    href="Final_quest.php?course_id=<?= $courseId; ?>"
                                    class="primary-btn"
                                >
                                    Try Again ↻
                                </a>
                            <?php endif; ?>

                            <a href="ProgressPage.php" class="quest-btn quest-btn-secondary">
                                View Progress
                            </a>

                        </div>

                        <div class="quest-review">
                            <h3>📋 Answer review</h3>

                            <?php $number = 0; ?>

                            <?php foreach ($questions as $questionId => $question): ?>
                                <?php
                                    $number++;
                                    $saved = $result["answers"][$questionId] ?? [];
                                    $selectedText = (string) ($saved["selected_answer"] ?? "");
                                    $isCorrect = !empty($saved["is_correct"]);
                                ?>

                                <details
                                    class="quest-review-item <?= $isCorrect ? "ok" : "bad"; ?>"
                                    <?= $isCorrect ? "" : "open"; ?>
                                >
                                    <summary>
                                        <span class="quest-review-badge">
                                            <?= $isCorrect ? "✓" : "✕"; ?>
                                        </span>

                                        <span class="quest-review-q">
                                            Q<?= $number; ?>.
                                            <?= e($question["question_text"]); ?>
                                        </span>
                                    </summary>

                                    <div class="quest-review-body">
                                        <p>
                                            <strong>Your answer:</strong>
                                            <?= e($selectedText ?: "No answer"); ?>
                                        </p>

                                        <?php if (!$isCorrect): ?>
                                            <p>
                                                <strong>Correct answer:</strong>
                                                <?= e($question["correct_answer"]); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </details>

                            <?php endforeach; ?>
                        </div>

                    </div>
                </section>
            </div>

        <?php else: ?>

            <div class="quest-wrapper">

                <section class="quest-intro">
                    <div>
                        <span class="quest-eyebrow">
                            <span class="quest-eyebrow-dot"></span>
                            FINAL QUEST
                        </span>

                        <h1 class="quest-heading">
                            <?= e($assessment["assessment_title"]); ?>
                        </h1>

                        <p class="quest-description">
                            <?= e($assessment["course_title"]); ?>
                        </p>

                        <div class="quest-meta">
                            <span class="quest-chip">
                                🎯 <?= $totalQuestions; ?> questions
                            </span>
                            <span class="quest-chip">
                                ⚡ <?= e(number_format($totalPoints, 0)); ?> points
                            </span>
                            <span class="quest-chip">
                                ✅ Pass at <?= e(number_format($passingScore, 0)); ?>%
                            </span>
                        </div>
                    </div>

                    <div class="quest-level">
                        <span>COURSE</span>
                        <strong>
                            <?= e(str_pad((string) $assessment["course_number"], 2, "0", STR_PAD_LEFT)); ?>
                        </strong>
                    </div>
                </section>

                <?php if ($error !== ""): ?>
                    <div class="quest-error" role="alert"><?= e($error); ?></div>
                <?php endif; ?>

                <section class="quest-panel">

                    <div class="quest-panel-header">
                        <span class="quest-panel-label">FINAL KNOWLEDGE CHALLENGE</span>
                        <span class="quest-question-count">
                            <?= $totalQuestions; ?> QUESTIONS
                        </span>
                    </div>

                    <form
                        method="POST"
                        action="Final_quest.php?course_id=<?= $courseId; ?>&final_assessment_id=<?= $assessmentId; ?>"
                        id="finalQuestForm"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken); ?>"
                        >

                        <input
                            type="hidden"
                            name="course_id"
                            value="<?= $courseId; ?>"
                        >

                        <input
                            type="hidden"
                            name="final_assessment_id"
                            value="<?= $assessmentId; ?>"
                        >

                        <?php $number = 0; ?>

                        <?php foreach ($questions as $questionId => $question): ?>
                            <?php
                                $number++;
                                $options = $question["options"];
                                shuffle($options);
                            ?>

                            <div class="quest-question">

                                <div class="fq-question-top">
                                    <span class="quest-question-number">
                                        QUESTION <?= str_pad((string) $number, 2, "0", STR_PAD_LEFT); ?>
                                    </span>
                                </div>

                                <h2>
                                    <?= nl2br(e($question["question_text"])); ?>
                                </h2>

                                <p class="quest-question-help">
                                    Worth <?= (int) $question["points"]; ?>
                                    points. Choose the best answer.
                                </p>

                                <div class="quest-options fq-options">

                                    <?php foreach ($options as $optionIndex => $option): ?>
                                        <label class="quest-option">

                                            <input
                                                type="radio"
                                                name="answers[<?= $questionId; ?>]"
                                                value="<?= (int) $option["option_id"]; ?>"
                                                <?= (
                                                    (int) ($postedAnswers[$questionId] ?? 0)
                                                    === (int) $option["option_id"]
                                                ) ? "checked" : ""; ?>
                                            >

                                            <span class="quest-option-letter">
                                                <?= e($letters[$optionIndex] ?? (string) ($optionIndex + 1)); ?>
                                            </span>

                                            <span class="quest-option-text">
                                                <?= e($option["option_text"]); ?>
                                            </span>

                                        </label>
                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <div class="quest-navigation">
                            <button
                                type="submit"
                                class="quest-btn quest-btn-submit"
                            >
                                Submit Final Quest 🏆
                            </button>
                        </div>

                    </form>

                </section>

            </div>

        <?php endif; ?>

    </main>
</div>

<script>
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode-active");

    const enabled = document.body.classList.contains("dark-mode-active");
    const button = document.querySelector(".dark-mode-btn");

    if (button) {
        button.textContent = enabled ? "☀️" : "🌙";
    }

    try {
        localStorage.setItem(
            "eduquest-dark-mode",
            enabled ? "enabled" : "disabled"
        );
    } catch (error) {
        // Dark mode still works if local storage is unavailable.
    }
}

(function restoreDarkMode() {
    try {
        if (localStorage.getItem("eduquest-dark-mode") === "enabled") {
            document.body.classList.add("dark-mode-active");

            const button = document.querySelector(".dark-mode-btn");

            if (button) {
                button.textContent = "☀️";
            }
        }
    } catch (error) {
    }
})();
</script>

</body>
</html>
