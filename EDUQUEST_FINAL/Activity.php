<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";
require_once __DIR__ . "/student_auth.php";

$conn->set_charset("utf8mb4");

$userId = (int) $_SESSION["user_id"];

$activityId = (int) ($_GET["activity_id"] ?? $_POST["activity_id"] ?? 0);
$missionId = (int) ($_GET["mission_id"] ?? 0);
$lessonId = (int) ($_GET["lesson_id"] ?? $_POST["lesson_id"] ?? 0);

$showAnswerReview = true;

/* ---------- Fun settings ----------
   $maxHints            Hint power-ups per attempt (0 = turn hints off).
   $hintPenaltyPercent  Percent of a question's points lost when a hint was
                        used on it (0 = hints are free).                    */
$maxHints = 3;
$hintPenaltyPercent = 50;

$maxHints = max(0, (int) $maxHints);
$hintPenaltyPercent = min(100, max(0, (int) $hintPenaltyPercent));

$errorMessage = "";
$activity = null;
$questions = [];
$optionsByQuestion = [];
$result = null;
$review = [];
$hintsUsed = [];

$csrfToken = $_SESSION["activity_csrf"] ?? bin2hex(random_bytes(32));
$_SESSION["activity_csrf"] = $csrfToken;

if (!function_exists("e")) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

if (!function_exists("normalizeAnswer")) {
    function normalizeAnswer(string $answer): string
    {
        return strtolower(trim($answer));
    }
}

if (!function_exists("formatClock")) {
    function formatClock(int $seconds): string
    {
        return str_pad((string) intdiv($seconds, 60), 2, "0", STR_PAD_LEFT)
            . ":"
            . str_pad((string) ($seconds % 60), 2, "0", STR_PAD_LEFT);
    }
}

if (!function_exists("gradeQuestion")) {
    function gradeQuestion(array $question, array $options, string $rawAnswer): array
    {
        $type = $question["question_type"];
        $correctAnswer = trim((string) ($question["correct_answer"] ?? ""));

        $selectedOptionId = null;
        $answerText = null;
        $isCorrect = false;
        $fraction = 0.0;
        $correctOptionId = null;
        $correctLabel = $correctAnswer;
        $yourLabel = "";
        $detail = null;

        foreach ($options as $option) {
            if ((int) ($option["is_correct"] ?? 0) === 1) {
                $correctOptionId = (int) $option["option_id"];
                $correctLabel = (string) $option["option_text"];
                break;
            }
        }

        if (in_array($type, ["multiple_choice", "true_false"], true)) {
            if (!empty($options)) {
                $submittedOptionId = ctype_digit($rawAnswer)
                    ? (int) $rawAnswer
                    : 0;

                foreach ($options as $option) {
                    if ((int) $option["option_id"] === $submittedOptionId) {
                        $selectedOptionId = $submittedOptionId;
                        $yourLabel = (string) $option["option_text"];
                        break;
                    }
                }

                $isCorrect = $selectedOptionId !== null
                    && $correctOptionId !== null
                    && $selectedOptionId === $correctOptionId;
            } else {
                $answerText = $rawAnswer;
                $yourLabel = $rawAnswer;

                $isCorrect = $correctAnswer !== ""
                    && normalizeAnswer($rawAnswer) === normalizeAnswer($correctAnswer);
            }

        } elseif ($type === "short_answer") {
            $answerText = $rawAnswer;
            $yourLabel = $rawAnswer;

            $isCorrect = $correctAnswer !== ""
                && normalizeAnswer($rawAnswer) === normalizeAnswer($correctAnswer);

        } elseif ($type === "ordering") {
            $given = json_decode($rawAnswer, true);
            $given = is_array($given) ? array_map("intval", $given) : [];

            $correctIds = array_map(
                fn($o) => (int) $o["option_id"],
                $options
            );

            $textById = [];

            foreach ($options as $o) {
                $textById[(int) $o["option_id"]] = (string) $o["option_text"];
            }

            $hits = 0;
            $detail = [];

            foreach ($correctIds as $i => $id) {
                $ok = (($given[$i] ?? null) === $id);
                $detail[] = $ok;

                if ($ok) {
                    $hits++;
                }
            }

            $total = count($correctIds);
            $fraction = $total > 0 ? $hits / $total : 0.0;
            $isCorrect = $total > 0 && $hits === $total;
            $answerText = json_encode($given);

            $yourLabel = implode(
                " → ",
                array_map(fn($id) => $textById[$id] ?? "?", $given)
            );

            $correctLabel = implode(" → ", array_values($textById));

        } elseif ($type === "matching") {
            $pairs = json_decode($rawAnswer, true);
            $pairs = is_array($pairs) ? $pairs : [];

            $hits = 0;
            $detail = [];
            $yourParts = [];
            $correctParts = [];

            foreach ($options as $o) {
                $oid = (string) $o["option_id"];

                $given = (
                    isset($pairs[$oid]) && is_string($pairs[$oid])
                ) ? $pairs[$oid] : "";

                $expected = (string) ($o["match_text"] ?? "");

                $ok = $given !== ""
                    && normalizeAnswer($given) === normalizeAnswer($expected);

                $detail[$oid] = $ok;

                if ($ok) {
                    $hits++;
                }

                $yourParts[] = $o["option_text"] . " → "
                    . ($given !== "" ? $given : "?");

                $correctParts[] = $o["option_text"] . " → " . $expected;
            }

            $total = count($options);
            $fraction = $total > 0 ? $hits / $total : 0.0;
            $isCorrect = $total > 0 && $hits === $total;
            $answerText = $rawAnswer;
            $yourLabel = implode("; ", $yourParts);
            $correctLabel = implode("; ", $correctParts);

        } elseif ($type === "fill_blank") {
            $given = json_decode($rawAnswer, true);

            $given = is_array($given)
                ? array_map(
                    fn($g) => is_string($g) ? $g : "",
                    $given
                )
                : [];

            $expectedList = array_map("trim", explode("|", $correctAnswer));
            $hits = 0;
            $detail = [];

            foreach ($expectedList as $i => $expected) {
                $g = $given[$i] ?? "";

                $ok = $g !== ""
                    && normalizeAnswer($g) === normalizeAnswer($expected);

                $detail[] = $ok;

                if ($ok) {
                    $hits++;
                }
            }

            $total = count($expectedList);

            $fraction = ($correctAnswer !== "" && $total > 0)
                ? $hits / $total
                : 0.0;

            $isCorrect = $correctAnswer !== ""
                && $total > 0
                && $hits === $total;

            $answerText = json_encode($given);
            $yourLabel = implode(", ", $given);
            $correctLabel = implode(", ", $expectedList);
        }

        if ($isCorrect) {
            $fraction = 1.0;
        }

        return [
            "selected_option_id" => $selectedOptionId,
            "answer_text" => $answerText,
            "is_correct" => $isCorrect,
            "fraction" => $fraction,
            "correct_option_id" => $correctOptionId,
            "correct_label" => $correctLabel,
            "your_label" => $yourLabel,
            "detail" => $detail
        ];
    }
}

/**
 * Builds a hint for one question WITHOUT revealing the correct answer outright. 
 */
if (!function_exists("buildHint")) {
    function buildHint(array $question, array $options): ?array
    {
        $type = $question["question_type"];
        $correctAnswer = trim((string) ($question["correct_answer"] ?? ""));

        if (in_array($type, ["multiple_choice", "true_false"], true)) {
            if (count($options) < 3) {
                return null;
            }

            $wrongIds = [];

            foreach ($options as $option) {
                if ((int) ($option["is_correct"] ?? 0) !== 1) {
                    $wrongIds[] = (int) $option["option_id"];
                }
            }

            sort($wrongIds);

            // There must be at least one correct option for this to be safe.
            if (count($options) - count($wrongIds) < 1) {
                return null;
            }

            $removeCount = min(2, count($options) - 2, count($wrongIds));

            if ($removeCount < 1) {
                return null;
            }

            return [
                "kind" => "remove_options",
                "option_ids" => array_slice($wrongIds, 0, $removeCount),
                "text" => "We crossed out " . $removeCount . " wrong "
                    . ($removeCount === 1 ? "answer." : "answers.")
            ];
        }

        if ($type === "short_answer") {
            if ($correctAnswer === "") {
                return null;
            }

            $words = preg_split('/\s+/u', $correctAnswer, -1, PREG_SPLIT_NO_EMPTY);
            $parts = [];
            $letterCount = 0;

            foreach ($words as $word) {
                $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
                $letterCount += count($chars);

                $masked = [array_shift($chars)];
                $rest = count($chars);

                for ($i = 0; $i < $rest; $i++) {
                    $masked[] = "_";
                }

                $parts[] = implode(" ", $masked);
            }

            return [
                "kind" => "pattern",
                "pattern" => implode("   ", $parts),
                "text" => "It starts with “" . mb_substr($correctAnswer, 0, 1)
                    . "” and has " . $letterCount . " letters."
            ];
        }

        if ($type === "fill_blank") {
            if ($correctAnswer === "") {
                return null;
            }

            $expected = array_map(
                fn($x) => normalizeAnswer(trim($x)),
                explode("|", $correctAnswer)
            );

            $distractors = [];

            foreach ($options as $option) {
                $text = (string) $option["option_text"];

                if (!in_array(normalizeAnswer($text), $expected, true)) {
                    $distractors[] = $text;
                }
            }

            sort($distractors);
            $distractors = array_slice($distractors, 0, 2);

            if (empty($distractors)) {
                return null;
            }

            return [
                "kind" => "remove_words",
                "words" => $distractors,
                "text" => "We removed " . count($distractors) . " word"
                    . (count($distractors) === 1 ? "" : "s")
                    . " that do not belong."
            ];
        }

        if ($type === "ordering" && count($options) >= 3) {
            return [
                "kind" => "reveal_first",
                "option_id" => (int) $options[0]["option_id"],
                "text" => "We moved the correct first step to the top."
            ];
        }

        if ($type === "matching" && count($options) >= 3) {
            $first = $options[0];

            if (trim((string) ($first["match_text"] ?? "")) === "") {
                return null;
            }

            return [
                "kind" => "reveal_pair",
                "option_id" => (int) $first["option_id"],
                "match_text" => (string) $first["match_text"],
                "text" => "We matched one pair for you."
            ];
        }

        return null;
    }
}

/* =====================================================
   LOAD ACTIVITY
   ===================================================== */

try {

    $stmt = null;

    if ($lessonId > 0) {

        $stmt = $conn->prepare("
            SELECT
                a.activity_id,
                a.mission_id,
                a.activity_title,
                a.activity_description,
                a.instructions,
                a.activity_type,
                a.total_points,
                a.passing_score,
                m.mission_number,
                m.mission_title
            FROM lessons l
            INNER JOIN missions m
                ON m.module_id = l.module_id
            INNER JOIN activities a
                ON a.mission_id = m.mission_id
            WHERE l.lesson_id = ?
              AND l.status = 'published'
              AND m.status = 'published'
              AND a.status = 'published'
            ORDER BY
                m.mission_order ASC,
                a.activity_order ASC,
                a.activity_id ASC
            LIMIT 1
        ");

        $stmt->bind_param("i", $lessonId);

    } elseif ($activityId > 0) {

        $stmt = $conn->prepare("
            SELECT
                a.activity_id,
                a.mission_id,
                a.activity_title,
                a.activity_description,
                a.instructions,
                a.activity_type,
                a.total_points,
                a.passing_score,
                m.mission_number,
                m.mission_title
            FROM activities a
            INNER JOIN missions m
                ON m.mission_id = a.mission_id
            WHERE a.activity_id = ?
              AND a.status = 'published'
              AND m.status = 'published'
              AND (? = 0 OR a.mission_id = ?)
            LIMIT 1
        ");

        $stmt->bind_param(
            "iii",
            $activityId,
            $missionId,
            $missionId
        );

    } elseif ($missionId > 0) {

        $stmt = $conn->prepare("
            SELECT
                a.activity_id,
                a.mission_id,
                a.activity_title,
                a.activity_description,
                a.instructions,
                a.activity_type,
                a.total_points,
                a.passing_score,
                m.mission_number,
                m.mission_title
            FROM activities a
            INNER JOIN missions m
                ON m.mission_id = a.mission_id
            WHERE a.mission_id = ?
              AND a.status = 'published'
              AND m.status = 'published'
            ORDER BY
                a.activity_order ASC,
                a.activity_id ASC
            LIMIT 1
        ");

        $stmt->bind_param("i", $missionId);

    } else {
        $errorMessage = "No activity was selected. Please return to your lesson and choose an activity.";
    }

    /* =====================================================
       EXECUTE ACTIVITY QUERY
       ===================================================== */

    if ($stmt !== null) {
        $stmt->execute();
        $activity = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$activity) {
            $errorMessage = "No published activity was found for this lesson. Please check the lesson's module and mission.";
        } else {
            $activityId = (int) $activity["activity_id"];
            $missionId = (int) $activity["mission_id"];
        }
    }

    /* =====================================================
       SHOW THE RESULT OF A JUST-SUBMITTED ATTEMPT
       ===================================================== */

    if (
        $activity
        && $_SERVER["REQUEST_METHOD"] === "GET"
        && empty($errorMessage)
    ) {
        $flash = $_SESSION["activity_flash"] ?? null;

        if (
            is_array($flash)
            && (int) ($flash["activity_id"] ?? 0) === $activityId
            && (time() - (int) ($flash["time"] ?? 0)) <= 120
        ) {
            $result = $flash["result"] ?? null;
            $review = $flash["review"] ?? [];
            unset($_SESSION["activity_flash"]);
        } elseif (is_array($flash) && (time() - (int) ($flash["time"] ?? 0)) > 120) {
            unset($_SESSION["activity_flash"]);
        }
    }

    /* =====================================================
       CHECK IF THE STUDENT ALREADY PASSED
       ===================================================== */

    if (
        $activity
        && !$result
        && $_SERVER["REQUEST_METHOD"] === "GET"
        && empty($errorMessage)
    ) {
        $resolvedActivityId = (int) $activity["activity_id"];
        $resolvedMissionId = (int) $activity["mission_id"];

        $stmt = $conn->prepare("
            SELECT 1
            FROM activity_attempts
            WHERE user_id = ?
              AND activity_id = ?
              AND status = 'passed'
            LIMIT 1
        ");

        $stmt->bind_param("ii", $userId, $resolvedActivityId);
        $stmt->execute();

        $alreadyPassed = (bool) $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($alreadyPassed) {
            header("Location: Challenge.php?mission_id=" . $resolvedMissionId);
            exit;
        }
        
    }

    /* =====================================================
       LOAD QUESTIONS + OPTIONS
       ===================================================== */

    if ($activity) {
        $stmt = $conn->prepare("
            SELECT
                question_id,
                question_text,
                question_type,
                points,
                question_order,
                correct_answer,
                explanation
            FROM activity_questions
            WHERE activity_id = ?
              AND status = 'published'
            ORDER BY question_order ASC, question_id ASC
        ");

        $stmt->bind_param("i", $activityId);
        $stmt->execute();

        $questionResult = $stmt->get_result();

        while ($question = $questionResult->fetch_assoc()) {
            $questions[] = $question;
            $optionsByQuestion[(int) $question["question_id"]] = [];
        }

        $stmt->close();

        if (!empty($questions)) {
            $optionStmt = $conn->prepare("
                SELECT
                    option_id,
                    question_id,
                    option_text,
                    match_text,
                    option_order,
                    is_correct
                FROM activity_options
                WHERE question_id = ?
                ORDER BY option_order ASC, option_id ASC
            ");

            foreach ($questions as $question) {
                $questionId = (int) $question["question_id"];

                $optionStmt->bind_param("i", $questionId);
                $optionStmt->execute();

                $optionResult = $optionStmt->get_result();

                while ($option = $optionResult->fetch_assoc()) {
                    $optionsByQuestion[$questionId][] = $option;
                }
            }

            $optionStmt->close();
        }

        if (empty($questions)) {
            $errorMessage = "No published questions are available for this activity yet.";
        }

        // Hints already used in this attempt (kept in the session, so a
        // page reload cannot be used to get unlimited hints).
        $hintsUsed = $_SESSION["activity_hints"][$activityId] ?? [];

        if (!is_array($hintsUsed)) {
            $hintsUsed = [];
        }
    }

    /* =====================================================
       AJAX: INSTANT CHECK + HINTS
       ===================================================== */

    if (
        $activity
        && !empty($questions)
        && $_SERVER["REQUEST_METHOD"] === "POST"
        && in_array(($_POST["action"] ?? ""), ["check_answer", "hint"], true)
    ) {
        header("Content-Type: application/json; charset=utf-8");

        $ajaxAction = (string) $_POST["action"];

        try {
            $postedToken = (string) ($_POST["csrf_token"] ?? "");

            if (!hash_equals($csrfToken, $postedToken)) {
                http_response_code(403);
                echo json_encode([
                    "ok" => false,
                    "message" => "Invalid token."
                ]);
                exit;
            }

            $checkQuestionId = (int) ($_POST["question_id"] ?? 0);
            $targetQuestion = null;

            foreach ($questions as $candidate) {
                if ((int) $candidate["question_id"] === $checkQuestionId) {
                    $targetQuestion = $candidate;
                    break;
                }
            }

            if ($targetQuestion === null) {
                http_response_code(404);
                echo json_encode([
                    "ok" => false,
                    "message" => "Question not found."
                ]);
                exit;
            }

            $targetOptions = $optionsByQuestion[$checkQuestionId] ?? [];

            /* ---------- Hint ---------- */

            if ($ajaxAction === "hint") {
                $hint = $maxHints > 0
                    ? buildHint($targetQuestion, $targetOptions)
                    : null;

                if ($hint === null) {
                    echo json_encode([
                        "ok" => false,
                        "message" => "No hint is available for this question."
                    ]);
                    exit;
                }

                $alreadyUsed = !empty($hintsUsed[$checkQuestionId]);

                if (!$alreadyUsed && count($hintsUsed) >= $maxHints) {
                    echo json_encode([
                        "ok" => false,
                        "message" => "You have no hints left for this mission."
                    ]);
                    exit;
                }

                if (!$alreadyUsed) {
                    $hintsUsed[$checkQuestionId] = true;
                    $_SESSION["activity_hints"][$activityId] = $hintsUsed;
                }

                echo json_encode([
                    "ok" => true,
                    "hint" => $hint,
                    "already_used" => $alreadyUsed,
                    "remaining" => max(0, $maxHints - count($hintsUsed)),
                    "penalty" => $hintPenaltyPercent
                ]);
                exit;
            }

            /* ---------- Check answer ---------- */

            $rawCheckAnswer = $_POST["answer"] ?? "";

            if (!is_string($rawCheckAnswer) && !is_numeric($rawCheckAnswer)) {
                $rawCheckAnswer = "";
            }

            $checked = gradeQuestion(
                $targetQuestion,
                $targetOptions,
                trim((string) $rawCheckAnswer)
            );

            $questionPoints = (float) $targetQuestion["points"];
            $usedHint = !empty($hintsUsed[$checkQuestionId]);
            $multiplier = $usedHint ? max(0, 1 - $hintPenaltyPercent / 100) : 1;

            echo json_encode([
                "ok" => true,
                "correct" => $checked["is_correct"],
                "partial" => !$checked["is_correct"] && $checked["fraction"] > 0,
                "points" => $questionPoints,
                "points_awarded" => round(
                    $questionPoints * $checked["fraction"] * $multiplier,
                    2
                ),
                "hint_used" => $usedHint,
                "correct_option_id" => $checked["correct_option_id"],
                "correct_answer" => $checked["correct_label"],
                "detail" => $checked["detail"],
                "explanation" => (string) ($targetQuestion["explanation"] ?? "")
            ]);

        } catch (Throwable $checkException) {
            error_log("EduQuest AJAX Error: " . $checkException->getMessage());

            http_response_code(500);

            echo json_encode([
                "ok" => false,
                "message" => "Could not complete that request."
            ]);
        }

        exit;
    }

    /* =====================================================
       SUBMIT AND GRADE ANSWERS
       ===================================================== */

    if (
        $_SERVER["REQUEST_METHOD"] === "POST"
        && $activity
        && empty($errorMessage)
    ) {
        $postedToken = (string) ($_POST["csrf_token"] ?? "");

        if (!hash_equals($csrfToken, $postedToken)) {
            throw new RuntimeException("Invalid submission token.");
        }

        if ((int) ($_POST["activity_id"] ?? 0) !== $activityId) {
            throw new RuntimeException("Invalid activity submission.");
        }

        $submittedAnswers = $_POST["answers"] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $score = 0.0;
        $maximumPoints = 0.0;
        $gradedAnswers = [];
        $hintsUsedCount = 0;

        foreach ($questions as $question) {
            $questionId = (int) $question["question_id"];
            $points = (float) $question["points"];

            $maximumPoints += $points;

            $rawAnswer = $submittedAnswers[$questionId] ?? "";

            if (!is_string($rawAnswer) && !is_numeric($rawAnswer)) {
                $rawAnswer = "";
            }

            $rawAnswer = trim((string) $rawAnswer);

            $graded = gradeQuestion(
                $question,
                $optionsByQuestion[$questionId] ?? [],
                $rawAnswer
            );

            $usedHint = !empty($hintsUsed[$questionId]);
            $multiplier = $usedHint ? max(0, 1 - $hintPenaltyPercent / 100) : 1;

            if ($usedHint) {
                $hintsUsedCount++;
            }

            $isCorrect = $graded["is_correct"];
            $pointsAwarded = round($points * $graded["fraction"] * $multiplier, 2);
            $score += $pointsAwarded;

            $gradedAnswers[] = [
                "question_id" => $questionId,
                "selected_option_id" => $graded["selected_option_id"],
                "answer_text" => $graded["answer_text"],
                "is_correct" => $isCorrect ? 1 : 0,
                "points_awarded" => $pointsAwarded
            ];

            $review[] = [
                "number" => count($review) + 1,
                "question_text" => (string) $question["question_text"],
                "your_answer" => $graded["your_label"] !== ""
                    ? $graded["your_label"]
                    : "No answer",
                "correct_answer" => $graded["correct_label"],
                "explanation" => (string) ($question["explanation"] ?? ""),
                "is_correct" => $isCorrect,
                "hint_used" => $usedHint,
                "points" => $points,
                "points_awarded" => $pointsAwarded
            ];
        }

        $percentage = $maximumPoints > 0
            ? round(($score / $maximumPoints) * 100, 2)
            : 0.0;

        $passingScore = (float) $activity["passing_score"];
        $passed = $maximumPoints > 0 && $percentage >= $passingScore;
        $attemptStatus = $passed ? "passed" : "failed";

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                SELECT
                    COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt,
                    MAX(percentage) AS best_pct
                FROM activity_attempts
                WHERE user_id = ?
                  AND activity_id = ?
            ");

            $stmt->bind_param("ii", $userId, $activityId);
            $stmt->execute();

            $attemptRow = $stmt->get_result()->fetch_assoc();
            $attemptNumber = (int) $attemptRow["next_attempt"];

            $previousBest = $attemptRow["best_pct"] !== null
                ? (float) $attemptRow["best_pct"]
                : null;

            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO activity_attempts (
                    user_id,
                    activity_id,
                    attempt_number,
                    score,
                    total_points,
                    percentage,
                    status,
                    started_at,
                    submitted_at,
                    graded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
            ");

            $stmt->bind_param(
                "iiiddds",
                $userId,
                $activityId,
                $attemptNumber,
                $score,
                $maximumPoints,
                $percentage,
                $attemptStatus
            );

            $stmt->execute();

            $attemptId = (int) $conn->insert_id;

            $stmt->close();

            $answerStmt = $conn->prepare("
                INSERT INTO activity_answers (
                    attempt_id,
                    question_id,
                    selected_option_id,
                    answer_text,
                    is_correct,
                    points_awarded,
                    answered_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach ($gradedAnswers as $answer) {
                $questionId = (int) $answer["question_id"];
                $selectedOptionId = $answer["selected_option_id"];
                $answerText = $answer["answer_text"];
                $isCorrect = (int) $answer["is_correct"];
                $pointsAwarded = (float) $answer["points_awarded"];

                $answerStmt->bind_param(
                    "iiisid",
                    $attemptId,
                    $questionId,
                    $selectedOptionId,
                    $answerText,
                    $isCorrect,
                    $pointsAwarded
                );

                $answerStmt->execute();
            }

            $answerStmt->close();

            $conn->commit();

            /* ---------- Build the result screen data ---------- */

            $questionCount = count($questions);

            $correctCount = count(array_filter(
                $gradedAnswers,
                fn($answer) => $answer["is_correct"] === 1
            ));

            $stars = 0;

            if ($passed) {
                $stars = $percentage >= 90
                    ? 3
                    : ($percentage >= 75 ? 2 : 1);
            }

            // Display-only values reported by the browser.
            $clientSeconds = min(86400, max(0, (int) ($_POST["client_seconds"] ?? 0)));
            $bestStreak = min($questionCount, max(0, (int) ($_POST["best_streak"] ?? 0)));

            $newBest = $previousBest !== null && $percentage > $previousBest;

            if (!$passed) {
                $rankIcon = "🌱";
                $rankLabel = "Rookie";
            } elseif ($percentage >= 95) {
                $rankIcon = "👑";
                $rankLabel = "Master";
            } elseif ($percentage >= 85) {
                $rankIcon = "🥇";
                $rankLabel = "Pro";
            } else {
                $rankIcon = "🧭";
                $rankLabel = "Explorer";
            }

            $badges = [];

            if ($questionCount > 0 && $correctCount === $questionCount) {
                $badges[] = ["💯", "Flawless", "Every answer was correct"];
            }

            if ($passed && $attemptNumber === 1) {
                $badges[] = ["🌟", "First Try", "Passed on your first attempt"];
            }

            if ($passed && $previousBest !== null && $previousBest < $passingScore) {
                $badges[] = ["💪", "Comeback", "You passed after a tough try"];
            }

            if ($passed && $maxHints > 0 && $hintsUsedCount === 0) {
                $badges[] = ["🧠", "No Hints", "Solved it all on your own"];
            }

            if ($bestStreak >= 5) {
                $badges[] = ["🔥", "Streak Master", $bestStreak . " correct answers in a row"];
            }

            if (
                $passed
                && $clientSeconds > 0
                && $clientSeconds <= $questionCount * 15
            ) {
                $badges[] = ["⚡", "Speed Runner", "Finished in " . formatClock($clientSeconds)];
            }

            if ($newBest) {
                $badges[] = [
                    "🏅",
                    "New Personal Best",
                    "You beat your previous best of " . round($previousBest) . "%"
                ];
            }

            $result = [
                "attempt_number" => $attemptNumber,
                "score" => $score,
                "total_points" => $maximumPoints,
                "percentage" => $percentage,
                "passed" => $passed,
                "passing_score" => $passingScore,
                "stars" => $stars,
                "correct_count" => $correctCount,
                "question_count" => $questionCount,
                "rank_icon" => $rankIcon,
                "rank_label" => $rankLabel,
                "previous_best" => $previousBest !== null ? round($previousBest, 1) : null,
                "new_best" => $newBest,
                "hints_used" => $hintsUsedCount,
                "best_streak" => $bestStreak,
                "seconds" => $clientSeconds,
                "time_label" => formatClock($clientSeconds),
                "badges" => $badges
            ];

            // A new attempt starts with a fresh set of hints.
            unset($_SESSION["activity_hints"][$activityId]);

            // Post/Redirect/Get: show the result once, never re-submit on refresh.
            $_SESSION["activity_flash"] = [
                "activity_id" => $activityId,
                "time" => time(),
                "result" => $result,
                "review" => $review
            ];

            header(
                "Location: Activity.php?activity_id=" . $activityId
                . "&lesson_id=" . $lessonId
            );
            exit;

        } catch (Throwable $exception) {
            $conn->rollback();
            throw $exception;
        }
    }

} catch (Throwable $exception) {
    error_log("EduQuest Activity Error: " . $exception->getMessage());

    $errorMessage = "Something went wrong while loading or saving your activity. Please try again.";
}

/* =========================================================
   MISSION PROGRESS
   ========================================================= */

$missionProgress = 0;

if ($activity) {
    $currentMissionId = (int) $activity["mission_id"];

    $stmt = $conn->prepare("
        SELECT progress_percent
        FROM user_mission_progress
        WHERE user_id = ?
          AND mission_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $userId, $currentMissionId);
    $stmt->execute();

    $progressRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($progressRow) {
        $missionProgress = (float) $progressRow["progress_percent"];
    }
}

$hintsLeft = max(0, $maxHints - count($hintsUsed));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $activity ? e($activity["activity_title"]) : "Mission Challenge"; ?>
        - EduQuest
    </title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/ActivityPage_style.css?v=<?= (int) @filemtime(__DIR__ . '/css/ActivityPage_style.css'); ?>">
    <link rel="stylesheet" href="css/activity_fun.css?v=<?= (int) @filemtime(__DIR__ . '/css/activity_fun.css'); ?>">
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
                <span class="profile-name">
                    <?= e(
                        $_SESSION["username"]
                        ?? $_SESSION["full_name"]
                        ?? "Student"
                    ); ?>
                </span>
            </div>
        </div>
    </header>

    <main class="learning-container">

        <div class="learning-header">
            <a
                href="<?= $lessonId > 0
                    ? 'LessonPage.php?lesson_id=' . $lessonId . '&mission_id=' . (int) ($activity['mission_id'] ?? $missionId)
                    : 'LessonPage.php'; ?>"
                class="back-link"
            >
                ← Back to Lesson
            </a>

            <?php if ($activity): ?>
                <span class="mission-number">
                    Mission <?= str_pad(
                        (string) $activity["mission_number"],
                        2,
                        "0",
                        STR_PAD_LEFT
                    ); ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (!$activity || $errorMessage !== ""): ?>

            <section class="learning-card quest-empty">
                <div class="quest-empty-icon">🧭</div>
                <h1>Mission unavailable</h1>
                <p class="lesson-intro">
                    <?= e(
                        $errorMessage
                        ?: "Return to your lesson to select an activity."
                    ); ?>
                </p>
                <div class="quest-result-actions">
                    <a href="LessonPage.php" class="primary-btn">
                        Back to Lesson
                    </a>
                </div>
            </section>

        <?php else: ?>

            <div class="quest-wrapper">

                <section class="quest-intro">
                    <div>
                        <span class="quest-eyebrow">
                            <span class="quest-eyebrow-dot"></span>
                            YOUR NEXT MISSION
                        </span>

                        <h1 class="quest-heading">
                            <?= e($activity["activity_title"]); ?>
                        </h1>

                        <p class="quest-description">
                            <?= e(
                                $activity["activity_description"]
                                ?: "Think carefully, choose your answers, and complete this learning challenge."
                            ); ?>
                        </p>

                        <div class="quest-meta">
                            <span class="quest-chip">🎯 <?= count($questions); ?> question<?= count($questions) === 1 ? "" : "s"; ?></span>
                            <span class="quest-chip">✅ Pass at <?= e($activity["passing_score"]); ?>%</span>
                            <?php if (!empty($activity["activity_type"])): ?>
                                <span class="quest-chip">🧩 <?= e(ucwords(str_replace("_", " ", (string) $activity["activity_type"]))); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($activity["mission_title"])): ?>
                                <span class="quest-chip">🗺️ <?= e($activity["mission_title"]); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="quest-level">
                        <span>MISSION</span>
                        <strong>
                            <?= str_pad(
                                (string) $activity["mission_number"],
                                2,
                                "0",
                                STR_PAD_LEFT
                            ); ?>
                        </strong>
                    </div>
                </section>

                <?php if (!$result): ?>

                    <div class="quest-dashboard">

                        <div class="quest-progress-card">
                            <div class="quest-progress-top">
                                <span>Challenge progress</span>
                                <span id="quest-progress-text">0% complete</span>
                            </div>

                            <div class="quest-track-wrap">
                                <span class="quest-rocket" id="quest-rocket" aria-hidden="true">🚀</span>

                                <div class="quest-track">
                                    <div
                                        class="quest-track-fill"
                                        id="quest-progress-fill"
                                    ></div>
                                </div>
                            </div>

                            <div class="quest-progress-note" id="quest-progress-note">
                                Every answer moves you closer to the finish.
                            </div>

                            <div class="quest-progress-mission">
                                Mission progress so far: <?= e(round($missionProgress)); ?>%
                            </div>
                        </div>

                        <div class="quest-stat-card">
                            <div class="quest-stat-icon">⚡</div>
                            <div>
                                <small>Possible XP</small>
                                <strong>
                                    <?= e($activity["total_points"]); ?> XP
                                </strong>
                            </div>
                        </div>

                    </div>

                    <div class="quest-hud" aria-label="Live stats">

                        <div class="quest-hud-tile">
                            <span class="quest-hud-icon">⏱️</span>
                            <div>
                                <small>TIME</small>
                                <strong id="hud-time">00:00</strong>
                            </div>
                        </div>

                        <div class="quest-hud-tile" id="hud-streak-tile">
                            <span class="quest-hud-icon">🔥</span>
                            <div>
                                <small>STREAK</small>
                                <strong id="hud-streak">0</strong>
                            </div>
                        </div>

                        <div class="quest-hud-tile" id="hud-xp-tile">
                            <span class="quest-hud-icon">⚡</span>
                            <div>
                                <small>XP EARNED</small>
                                <strong id="hud-xp">0</strong>
                            </div>
                        </div>

                        <div class="quest-hud-tile">
                            <span class="quest-hud-icon">🎯</span>
                            <div>
                                <small>ACCURACY</small>
                                <strong id="hud-accuracy">—</strong>
                            </div>
                        </div>

                        <?php if ($maxHints > 0): ?>
                            <div class="quest-hud-tile" id="hud-hints-tile">
                                <span class="quest-hud-icon">💡</span>
                                <div>
                                    <small>HINTS LEFT</small>
                                    <strong id="hud-hints"><?= (int) $hintsLeft; ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                <?php endif; ?>

                <section class="quest-panel">

                    <?php if ($result): ?>

                        <?php if ($result["passed"]): ?>
                            <canvas id="quest-confetti" class="quest-confetti" aria-hidden="true"></canvas>
                        <?php endif; ?>

                        <div
                            class="quest-result <?= $result["passed"] ? "passed" : "failed"; ?>"
                            data-perfect="<?= ((int) $result["correct_count"] === (int) $result["question_count"] && (int) $result["question_count"] > 0) ? "1" : "0"; ?>"
                        >

                            <div class="quest-result-icon">
                                <?= $result["passed"] ? "🏆" : "💪"; ?>
                            </div>

                            <div class="quest-stars" aria-label="<?= (int) $result["stars"]; ?> out of 3 stars">
                                <?php for ($star = 1; $star <= 3; $star++): ?>
                                    <span
                                        class="quest-star <?= $star <= (int) $result["stars"] ? "earned" : ""; ?>"
                                        style="--delay: <?= number_format($star * 0.25, 2, ".", ""); ?>s"
                                    >★</span>
                                <?php endfor; ?>
                            </div>

                            <div>
                                <span class="quest-rank">
                                    <?= e($result["rank_icon"]); ?>
                                    RANK: <?= e(strtoupper((string) $result["rank_label"])); ?>
                                </span>
                            </div>

                            <span class="quest-eyebrow">
                                <?= $result["passed"]
                                    ? "MISSION CLEARED"
                                    : "KEEP TRAINING"; ?>
                            </span>

                            <h2>
                                <?= $result["passed"]
                                    ? "Challenge completed!"
                                    : "Almost there!"; ?>
                            </h2>

                            <p>
                                <?= $result["passed"]
                                    ? "You reached the passing score. Great work on this mission."
                                    : "Your attempt has been saved. Review the answers below and try again to reach the passing score."; ?>
                            </p>

                            <div class="quest-result-score">

                                <div class="quest-result-stat">
                                    <strong data-count="<?= e($result["score"]); ?>">
                                        <?= e($result["score"]); ?>
                                    </strong>
                                    <span>POINTS EARNED</span>
                                </div>

                                <div class="quest-result-stat">
                                    <strong
                                        data-count="<?= e($result["percentage"]); ?>"
                                        data-suffix="%"
                                    >
                                        <?= e($result["percentage"]); ?>%
                                    </strong>
                                    <span>FINAL SCORE</span>
                                </div>

                                <div class="quest-result-stat">
                                    <strong>
                                        <?= (int) $result["correct_count"]; ?>/<?= (int) $result["question_count"]; ?>
                                    </strong>
                                    <span>CORRECT</span>
                                </div>

                            </div>

                            <p>
                                Passing score:
                                <strong><?= e($result["passing_score"]); ?>%</strong>
                                · Attempt #<?= (int) $result["attempt_number"]; ?>
                            </p>

                            <div class="quest-result-chips">
                                <?php if ((int) $result["seconds"] > 0): ?>
                                    <span class="quest-chip">⏱️ <?= e($result["time_label"]); ?></span>
                                <?php endif; ?>

                                <?php if ((int) $result["best_streak"] > 0): ?>
                                    <span class="quest-chip">🔥 Best streak <?= (int) $result["best_streak"]; ?></span>
                                <?php endif; ?>

                                <?php if ($maxHints > 0): ?>
                                    <span class="quest-chip">
                                        💡 <?= (int) $result["hints_used"]; ?>
                                        hint<?= (int) $result["hints_used"] === 1 ? "" : "s"; ?> used
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($result["new_best"])): ?>
                                <p class="quest-best">
                                    🏅 New personal best! Your previous best was
                                    <strong><?= e($result["previous_best"]); ?>%</strong>.
                                </p>
                            <?php elseif ($result["previous_best"] !== null): ?>
                                <p class="quest-best">
                                    Your best so far:
                                    <strong><?= e($result["previous_best"]); ?>%</strong>
                                </p>
                            <?php endif; ?>

                            <div class="quest-result-actions">

                                <?php if ($result["passed"]): ?>

                            <a
                                href="Challenge.php?mission_id=<?= (int) $activity['mission_id'] ?>"
                                class="challenge-btn"
                            >
                                Continue to Challenge
                                <span class="challenge-btn-arrow">→</span>
                            </a>

                                <?php else: ?>

                                    <a
                                        href="Activity.php?activity_id=<?= (int) $activity["activity_id"]; ?>&lesson_id=<?= (int) $lessonId; ?>"
                                        class="primary-btn"
                                    >
                                        Try Again ↻
                                    </a>

                                <?php endif; ?>

                                    <a
                                    href="LessonPage.php?lesson_id=<?= (int) $lessonId ?>"
                                    class="lesson-btn"
                                >
                                    <span class="lesson-btn-arrow">←</span>
                                    Back to Lesson
                                </a>

                                <button
                                    type="button"
                                    class="quest-btn quest-btn-secondary"
                                    id="quest-copy"
                                    data-text="<?= e(
                                        "I scored " . $result["percentage"] . "% on “"
                                        . $activity["activity_title"] . "” in EduQuest! "
                                        . str_repeat("⭐", (int) $result["stars"])
                                    ); ?>"
                                >📋 Copy my score</button>

                            </div>

                            <?php if (!empty($result["badges"])): ?>

                                <div class="quest-badges-wrap">
                                    <h3 class="quest-badges-title">🎖️ Badges earned</h3>

                                    <div class="quest-badges">
                                        <?php foreach ($result["badges"] as $badgeIndex => $badge): ?>
                                            <div class="quest-badge" style="--i: <?= (int) $badgeIndex; ?>">
                                                <span class="quest-badge-icon"><?= e($badge[0]); ?></span>
                                                <div>
                                                    <strong><?= e($badge[1]); ?></strong>
                                                    <small><?= e($badge[2]); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php endif; ?>

                            <?php if ($showAnswerReview && !empty($review)): ?>

                                <div class="quest-review">

                                    <h3>📋 Answer review</h3>

                                    <?php foreach ($review as $item): ?>

                                        <details
                                            class="quest-review-item <?= $item["is_correct"] ? "ok" : "bad"; ?>"
                                            <?= $item["is_correct"] ? "" : "open"; ?>
                                        >
                                            <summary>
                                                <span class="quest-review-badge">
                                                    <?= $item["is_correct"] ? "✓" : "✕"; ?>
                                                </span>

                                                <span class="quest-review-q">
                                                    Q<?= (int) $item["number"]; ?>.
                                                    <?= e($item["question_text"]); ?>
                                                </span>

                                                <span class="quest-review-pts">
                                                    <?= e($item["points_awarded"]); ?>/<?= e($item["points"]); ?> pts
                                                </span>
                                            </summary>

                                            <div class="quest-review-body">
                                                <p>
                                                    <strong>Your answer:</strong>
                                                    <?= e($item["your_answer"]); ?>
                                                </p>

                                                <?php if (!$item["is_correct"] && $item["correct_answer"] !== ""): ?>
                                                    <p>
                                                        <strong>Correct answer:</strong>
                                                        <?= e($item["correct_answer"]); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if (!empty($item["hint_used"])): ?>
                                                    <p class="quest-review-note">
                                                        💡 You used a hint on this question<?= $hintPenaltyPercent > 0
                                                            ? " (" . (int) $hintPenaltyPercent . "% fewer points)."
                                                            : "."; ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($item["explanation"] !== ""): ?>
                                                    <p class="quest-review-note">
                                                        💡 <?= e($item["explanation"]); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </details>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php elseif (empty($questions)): ?>

                        <div class="quest-empty">
                            <div class="quest-empty-icon">📚</div>
                            <h2>No questions yet</h2>
                            <p class="quest-description">
                                This activity has no published questions available.
                            </p>
                        </div>

                    <?php else: ?>

                        <?php if (!empty($activity["instructions"])): ?>
                            <p class="quest-description" style="margin-bottom: 20px;">
                                <strong>Mission briefing:</strong>
                                <?= nl2br(e($activity["instructions"])); ?>
                            </p>
                        <?php endif; ?>

                        <div class="quest-panel-header">
                            <span class="quest-panel-label">
                                KNOWLEDGE CHALLENGE
                            </span>

                            <div class="quest-panel-tools">
                                <button
                                    type="button"
                                    class="quest-icon-btn"
                                    id="quest-sound"
                                    aria-label="Toggle sound effects"
                                    aria-pressed="true"
                                >🔊</button>

                                <span class="quest-question-count" id="quest-counter">
                                    QUESTION 01 / <?= str_pad(
                                        (string) count($questions),
                                        2,
                                        "0",
                                        STR_PAD_LEFT
                                    ); ?>
                                </span>
                            </div>
                        </div>

                        <div class="quest-dots" id="quest-dots">
                            <?php foreach ($questions as $index => $question): ?>
                                <button
                                    type="button"
                                    class="quest-dot <?= $index === 0 ? "current" : ""; ?>"
                                    data-dot="<?= $index; ?>"
                                    aria-label="Go to question <?= $index + 1; ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>

                        <div class="quest-mascot" aria-live="polite">
                            <span class="quest-mascot-face" id="mascot-face">🦉</span>
                            <div class="quest-mascot-bubble" id="mascot-bubble">
                                Ready? Take your time. I will cheer you on!
                            </div>
                        </div>

                        <form
                            method="POST"
                            action=""
                            id="quest-form"
                            autocomplete="off"
                            data-hints-enabled="<?= $maxHints > 0 ? "1" : "0"; ?>"
                            data-hints-left="<?= (int) $hintsLeft; ?>"
                            data-hint-penalty="<?= (int) $hintPenaltyPercent; ?>"
                        >

                            <input
                                type="hidden"
                                name="activity_id"
                                value="<?= (int) $activity["activity_id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="lesson_id"
                                value="<?= (int) $lessonId; ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($csrfToken); ?>"
                            >

                            <input type="hidden" name="client_seconds" id="client-seconds" value="0">
                            <input type="hidden" name="best_streak" id="best-streak" value="0">

                            <?php foreach ($questions as $index => $question): ?>

                                <?php
                                    $questionId = (int) $question["question_id"];
                                    $questionType = $question["question_type"];
                                    $questionOptions = $optionsByQuestion[$questionId] ?? [];
                                    $letters = ["A", "B", "C", "D", "E", "F", "G", "H"];

                                    $hintable = $maxHints > 0
                                        && buildHint($question, $questionOptions) !== null;
                                ?>

                                <div
                                    class="quest-question"
                                    data-question="<?= $index; ?>"
                                    data-qid="<?= $questionId; ?>"
                                    data-type="<?= e($questionType); ?>"
                                    data-points="<?= e($question["points"]); ?>"
                                    data-hintable="<?= $hintable ? "1" : "0"; ?>"
                                    data-hint-used="<?= !empty($hintsUsed[$questionId]) ? "1" : "0"; ?>"
                                    <?= $index === 0 ? "" : "hidden"; ?>
                                >

                                    <span class="quest-question-number">
                                        QUESTION <?= str_pad(
                                            (string) ($index + 1),
                                            2,
                                            "0",
                                            STR_PAD_LEFT
                                        ); ?>
                                    </span>

                                    <h2 class="<?= mb_strlen((string) $question["question_text"]) > 140 ? "is-long" : ""; ?>">
                                        <?php if ($questionType === "fill_blank"): ?>
                                            <?php $blankParts = explode("[blank]", (string) $question["question_text"]); ?>
                                            <?php foreach ($blankParts as $partIndex => $part): ?>
                                                <?= nl2br(e($part)); ?><?php if ($partIndex < count($blankParts) - 1): ?><button type="button" class="fb-slot" aria-label="Blank <?= $partIndex + 1; ?>"></button><?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?= nl2br(e($question["question_text"])); ?>
                                        <?php endif; ?>
                                    </h2>

                                    <p class="quest-question-help">
                                        Worth <?= e($question["points"]); ?>
                                        point<?= (float) $question["points"] == 1 ? "" : "s"; ?>.
                                        Take your time and choose carefully.
                                    </p>

                                    <?php if (
                                        in_array(
                                            $questionType,
                                            ["multiple_choice", "true_false"],
                                            true
                                        ) && !empty($questionOptions)
                                    ): ?>

                                        <div class="quest-options">

                                            <?php foreach (
                                                $questionOptions as $optionIndex => $option
                                            ): ?>

                                                <label class="quest-option">

                                                    <input
                                                        type="radio"
                                                        name="answers[<?= $questionId; ?>]"
                                                        value="<?= (int) $option["option_id"]; ?>"
                                                        data-question-input="<?= $index; ?>"
                                                    >

                                                    <span class="quest-option-letter">
                                                        <?= e(
                                                            $letters[$optionIndex]
                                                            ?? (string) ($optionIndex + 1)
                                                        ); ?>
                                                    </span>

                                                    <span class="quest-option-text">
                                                        <?= e($option["option_text"]); ?>
                                                    </span>

                                                </label>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php elseif (
                                        $questionType === "true_false"
                                        && empty($questionOptions)
                                    ): ?>

                                        <div class="quest-options">

                                            <?php foreach (["True", "False"] as $optionIndex => $optionText): ?>

                                                <label class="quest-option">

                                                    <input
                                                        type="radio"
                                                        name="answers[<?= $questionId; ?>]"
                                                        value="<?= e($optionText); ?>"
                                                        data-question-input="<?= $index; ?>"
                                                    >

                                                    <span class="quest-option-letter">
                                                        <?= $optionIndex === 0 ? "T" : "F"; ?>
                                                    </span>

                                                    <span class="quest-option-text">
                                                        <?= e($optionText); ?>
                                                    </span>

                                                </label>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php elseif ($questionType === "short_answer"): ?>

                                        <input
                                            type="text"
                                            name="answers[<?= $questionId; ?>]"
                                            class="quest-short-answer"
                                            placeholder="Type your answer here..."
                                            data-question-input="<?= $index; ?>"
                                        >

                                    <?php elseif ($questionType === "ordering" && !empty($questionOptions)): ?>

                                        <?php
                                            $shuffled = $questionOptions;
                                            $originalIds = array_column($questionOptions, "option_id");
                                            $tries = 0;

                                            do {
                                                shuffle($shuffled);
                                                $tries++;
                                            } while (
                                                $tries < 6
                                                && count($shuffled) > 1
                                                && array_column($shuffled, "option_id") === $originalIds
                                            );
                                        ?>

                                        <p class="quest-question-help">🔢 Drag the cards (or use ▲ ▼) to put them in the right order.</p>

                                        <div class="ord-list">
                                            <?php foreach ($shuffled as $i => $option): ?>
                                                <div class="ord-item" draggable="true" data-id="<?= (int) $option["option_id"]; ?>">
                                                    <span class="ord-handle" aria-hidden="true">⠿</span>
                                                    <span class="ord-num"><?= $i + 1; ?></span>
                                                    <span class="ord-text"><?= e($option["option_text"]); ?></span>
                                                    <span class="ord-btns">
                                                        <button type="button" class="ord-up" aria-label="Move up">▲</button>
                                                        <button type="button" class="ord-down" aria-label="Move down">▼</button>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <input type="hidden" name="answers[<?= $questionId; ?>]" value="" data-answer-json>

                                    <?php elseif ($questionType === "matching" && !empty($questionOptions)): ?>

                                        <?php
                                            $rights = array_map(fn($o) => (string) $o["match_text"], $questionOptions);
                                            shuffle($rights);
                                        ?>

                                        <p class="quest-question-help">🔗 Tap an item on the left, then tap its match on the right. Tap a pair again to undo it.</p>

                                        <div class="match-board">
                                            <div class="match-col">
                                                <?php foreach ($questionOptions as $option): ?>
                                                    <button type="button" class="match-item match-left" data-id="<?= (int) $option["option_id"]; ?>">
                                                        <?= e($option["option_text"]); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="match-col">
                                                <?php foreach ($rights as $right): ?>
                                                    <button type="button" class="match-item match-right" data-value="<?= e($right); ?>">
                                                        <?= e($right); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <input type="hidden" name="answers[<?= $questionId; ?>]" value="" data-answer-json>

                                    <?php elseif ($questionType === "fill_blank"): ?>

                                        <?php
                                            $bank = array_map(fn($o) => (string) $o["option_text"], $questionOptions);

                                            if (empty($bank)) {
                                                $bank = array_map("trim", explode("|", (string) ($question["correct_answer"] ?? "")));
                                            }

                                            shuffle($bank);
                                        ?>

                                        <p class="quest-question-help">✏️ Tap a word to drop it into the next blank. Tap a filled blank to remove it.</p>

                                        <div class="fb-bank">
                                            <?php foreach ($bank as $word): ?>
                                                <button type="button" class="fb-chip" data-word="<?= e($word); ?>">
                                                    <?= e($word); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>

                                        <input type="hidden" name="answers[<?= $questionId; ?>]" value="" data-answer-json>

                                    <?php else: ?>

                                        <p class="quest-error">
                                            This question type is not supported by this activity page.
                                        </p>

                                    <?php endif; ?>

                                    <div class="quest-hintbox" data-hintbox hidden>
                                        <span class="quest-hintbox-icon">💡</span>
                                        <div data-hintbox-text></div>
                                    </div>

                                    <div class="quest-feedback" data-feedback hidden aria-live="polite">
                                        <span class="quest-feedback-icon"></span>
                                        <div>
                                            <strong class="quest-feedback-title"></strong>
                                            <p class="quest-feedback-text"></p>
                                        </div>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                            <div
                                class="quest-validation"
                                id="quest-validation"
                                role="alert"
                            >
                                Choose an answer before moving to the next question.
                            </div>

                            <div class="quest-navigation">

                                <button
                                    type="button"
                                    class="quest-btn quest-btn-secondary"
                                    id="quest-prev"
                                    disabled
                                >
                                    ← Previous
                                </button>

                                <div class="quest-nav-right">

                                    <?php if ($maxHints > 0): ?>
                                        <button
                                            type="button"
                                            class="quest-btn quest-btn-hint"
                                            id="quest-hint"
                                        >
                                            💡 Hint (<?= (int) $hintsLeft; ?>)
                                        </button>
                                    <?php endif; ?>

                                    <button
                                        type="button"
                                        class="quest-btn quest-btn-primary"
                                        id="quest-action"
                                    >
                                        Check Answer ✔
                                    </button>

                                    <button
                                        type="submit"
                                        class="quest-btn quest-btn-submit"
                                        id="quest-submit"
                                        hidden
                                    >
                                        Finish Mission ✓
                                    </button>

                                </div>

                            </div>

                            <p class="quest-hint">
                                💡 Press <kbd>A</kbd>–<kbd>D</kbd> to choose an answer,
                                <kbd>H</kbd> for a hint,
                                <kbd>Enter</kbd> to check or continue.
                                Your final score is graded when you finish the mission.
                            </p>

                        </form>

                        <div
                            class="quest-modal"
                            id="quest-modal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="quest-modal-title"
                            hidden
                        >
                            <div class="quest-modal-card">
                                <h3 id="quest-modal-title">Ready to finish your mission?</h3>
                                <p>Your answers will be submitted and graded, and this attempt will be saved.</p>

                                <div class="quest-modal-stats">
                                    <div class="quest-modal-stat">
                                        <strong id="modal-answered">0/0</strong>
                                        <span>ANSWERED</span>
                                    </div>
                                    <div class="quest-modal-stat">
                                        <strong id="modal-accuracy">—</strong>
                                        <span>ACCURACY</span>
                                    </div>
                                    <div class="quest-modal-stat">
                                        <strong id="modal-time">00:00</strong>
                                        <span>TIME</span>
                                    </div>
                                </div>

                                <div class="quest-modal-actions">
                                    <button
                                        type="button"
                                        class="quest-btn quest-btn-secondary"
                                        id="quest-modal-cancel"
                                    >
                                        Keep reviewing
                                    </button>

                                    <button
                                        type="button"
                                        class="quest-btn quest-btn-primary"
                                        id="quest-modal-confirm"
                                    >
                                        Submit &amp; grade ✓
                                    </button>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </section>

            </div>

        <?php endif; ?>

    </main>

</div>

<div class="quest-toast-wrap" id="quest-toasts" aria-live="polite"></div>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode-active");

        const button = document.querySelector(".dark-mode-btn");

        if (button) {
            button.textContent =
                document.body.classList.contains("dark-mode-active")
                    ? "☀️"
                    : "🌙";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        /* =====================================================
           RESULT SCREEN: count-up numbers + confetti + copy
           ===================================================== */

        function formatCount(value, suffix) {
            return String(parseFloat(value.toFixed(2))) + (suffix || "");
        }

        function launchConfetti(pieceCount) {
            const canvas = document.getElementById("quest-confetti");

            if (!canvas || reduceMotion) {
                return;
            }

            const ctx = canvas.getContext("2d");
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            const colors = ["#5DD62C", "#337418", "#FFD23F", "#4DA3FF", "#FF6B6B", "#FFFFFF"];

            const pieces = Array.from({ length: pieceCount }, function () {
                return {
                    x: Math.random() * canvas.width,
                    y: -Math.random() * canvas.height * 0.5,
                    w: 6 + Math.random() * 6,
                    h: 8 + Math.random() * 8,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    vx: -2 + Math.random() * 4,
                    vy: 2 + Math.random() * 4,
                    rot: Math.random() * Math.PI,
                    vr: -0.2 + Math.random() * 0.4
                };
            });

            let frames = 0;

            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                pieces.forEach(function (piece) {
                    piece.x += piece.vx;
                    piece.y += piece.vy;
                    piece.rot += piece.vr;

                    ctx.save();
                    ctx.translate(piece.x, piece.y);
                    ctx.rotate(piece.rot);
                    ctx.fillStyle = piece.color;
                    ctx.fillRect(-piece.w / 2, -piece.h / 2, piece.w, piece.h);
                    ctx.restore();
                });

                frames++;

                if (frames < 230) {
                    requestAnimationFrame(draw);
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    canvas.remove();
                }
            }

            draw();
        }

        function initResultScreen() {
            const resultBox = document.querySelector(".quest-result");

            if (!resultBox) {
                return;
            }

            resultBox.querySelectorAll("[data-count]").forEach(function (el) {
                const target = parseFloat(el.dataset.count) || 0;
                const suffix = el.dataset.suffix || "";

                if (reduceMotion) {
                    el.textContent = formatCount(target, suffix);
                    return;
                }

                const duration = 1200;
                const start = performance.now();

                function tick(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);

                    el.textContent = formatCount(target * eased, suffix);

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    } else {
                        el.textContent = formatCount(target, suffix);
                    }
                }

                requestAnimationFrame(tick);
            });

            if (resultBox.classList.contains("passed")) {
                launchConfetti(resultBox.dataset.perfect === "1" ? 260 : 140);
            }

            const copyButton = document.getElementById("quest-copy");

            if (copyButton) {
                copyButton.addEventListener("click", function () {
                    const text = copyButton.dataset.text || "";

                    function done() {
                        copyButton.textContent = "Copied ✓";

                        setTimeout(function () {
                            copyButton.textContent = "📋 Copy my score";
                        }, 2000);
                    }

                    function fallback() {
                        const area = document.createElement("textarea");

                        area.value = text;
                        area.style.position = "fixed";
                        area.style.opacity = "0";
                        document.body.appendChild(area);
                        area.select();

                        try {
                            document.execCommand("copy");
                            done();
                        } catch (error) {
                            /* Copy is optional. */
                        }

                        area.remove();
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(done).catch(fallback);
                    } else {
                        fallback();
                    }
                });
            }
        }

        initResultScreen();

        /* =====================================================
           QUESTION FLOW
           ===================================================== */

        const form = document.getElementById("quest-form");

        if (!form) {
            return;
        }

        const questionPanels = Array.from(form.querySelectorAll("[data-question]"));
        const dots = Array.from(document.querySelectorAll("[data-dot]"));

        const previousButton = document.getElementById("quest-prev");
        const actionButton = document.getElementById("quest-action");
        const submitButton = document.getElementById("quest-submit");
        const soundButton = document.getElementById("quest-sound");
        const hintButton = document.getElementById("quest-hint");

        const counter = document.getElementById("quest-counter");
        const progressText = document.getElementById("quest-progress-text");
        const progressFill = document.getElementById("quest-progress-fill");
        const progressNote = document.getElementById("quest-progress-note");
        const rocket = document.getElementById("quest-rocket");
        const validation = document.getElementById("quest-validation");

        const hudTime = document.getElementById("hud-time");
        const hudStreak = document.getElementById("hud-streak");
        const hudStreakTile = document.getElementById("hud-streak-tile");
        const hudXp = document.getElementById("hud-xp");
        const hudXpTile = document.getElementById("hud-xp-tile");
        const hudAccuracy = document.getElementById("hud-accuracy");
        const hudHints = document.getElementById("hud-hints");

        const mascotFace = document.getElementById("mascot-face");
        const mascotBubble = document.getElementById("mascot-bubble");
        const toastWrap = document.getElementById("quest-toasts");

        const clientSecondsField = document.getElementById("client-seconds");
        const bestStreakField = document.getElementById("best-streak");

        const modal = document.getElementById("quest-modal");
        const modalCancel = document.getElementById("quest-modal-cancel");
        const modalConfirm = document.getElementById("quest-modal-confirm");
        const modalAnswered = document.getElementById("modal-answered");
        const modalAccuracy = document.getElementById("modal-accuracy");
        const modalTime = document.getElementById("modal-time");

        const totalQuestions = questionPanels.length;
        const hintsEnabled = form.dataset.hintsEnabled === "1";
        const hintPenalty = Number(form.dataset.hintPenalty || 0);

        let currentQuestion = 0;
        let isChecking = false;
        let submitConfirmed = false;
        let lastFocused = null;

        let streak = 0;
        let bestStreak = 0;
        let xpEarned = 0;
        let correctChecked = 0;
        let verifiedCount = 0;
        let elapsed = 0;
        let hintsLeft = Number(form.dataset.hintsLeft || 0);
        let halfwayShown = false;

        // null = not checked yet, otherwise { correct: true | false | null }
        const results = new Array(totalQuestions).fill(null);

        /* ---------- Helpers ---------- */

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const rest = seconds % 60;

            return String(minutes).padStart(2, "0") + ":" + String(rest).padStart(2, "0");
        }

        function formatNumber(value) {
            return String(Math.round(value * 100) / 100);
        }

        function pick(list) {
            return list[Math.floor(Math.random() * list.length)];
        }

        function restartAnimation(el, className) {
            if (!el || reduceMotion) {
                return;
            }

            el.classList.remove(className);
            void el.offsetWidth;
            el.classList.add(className);
        }

        function floatText(tile, text) {
            if (!tile || reduceMotion) {
                return;
            }

            const span = document.createElement("span");
            span.className = "quest-float";
            span.textContent = text;
            tile.appendChild(span);

            setTimeout(function () {
                span.remove();
            }, 1200);
        }

        function showValidation(message) {
            validation.textContent = message;
            validation.classList.add("show");
        }

        function hideValidation() {
            validation.classList.remove("show");
        }

        /* ---------- Mascot, toasts, bursts ---------- */

        const mascotLines = {
            start: [
                "Ready? Take your time. I will cheer you on!",
                "New mission, new XP. Let's go!"
            ],
            correct: [
                "Nice one! 🎉",
                "Brilliant! Keep it up!",
                "Spot on!",
                "You nailed it!"
            ],
            partial: [
                "So close! Part of that was right.",
                "Almost! Read the tip and learn from it."
            ],
            wrong: [
                "No worries, every mistake is a lesson.",
                "Good try! Read the tip and keep going.",
                "That one was tricky. On to the next!"
            ],
            hint: [
                "Here is a little help. Use it wisely!",
                "Hints cost a few points, but learning is priceless."
            ],
            last: [
                "Last question! Make it count.",
                "Final stretch! You've got this."
            ],
            done: [
                "Everything is checked! Hit Finish when you are ready."
            ]
        };

        function mascotSay(mood, lines) {
            if (!mascotFace || !mascotBubble) {
                return;
            }

            const faces = {
                idle: "🦉",
                happy: "🤩",
                sad: "🤔",
                fire: "😎",
                hint: "💡",
                cheer: "🥳"
            };

            mascotFace.textContent = faces[mood] || "🦉";
            mascotBubble.textContent = pick(lines);
            restartAnimation(mascotFace, "bounce");
        }

        function toast(icon, title, text) {
            if (!toastWrap) {
                return;
            }

            const item = document.createElement("div");
            const iconEl = document.createElement("span");
            const body = document.createElement("div");
            const strong = document.createElement("strong");

            item.className = "quest-toast";
            iconEl.className = "quest-toast-icon";
            iconEl.textContent = icon;
            strong.textContent = title;

            body.appendChild(strong);

            if (text) {
                const small = document.createElement("small");
                small.textContent = text;
                body.appendChild(small);
            }

            item.appendChild(iconEl);
            item.appendChild(body);
            toastWrap.appendChild(item);

            setTimeout(function () {
                item.remove();
            }, 3400);
        }

        function burst(el, emojis) {
            if (reduceMotion || !el) {
                return;
            }

            const rect = el.getBoundingClientRect();
            const wrap = document.createElement("div");

            wrap.className = "quest-burst";
            wrap.style.left = (rect.left + rect.width / 2) + "px";
            wrap.style.top = (rect.top + rect.height / 2) + "px";

            for (let i = 0; i < 10; i++) {
                const span = document.createElement("span");
                const angle = (Math.PI * 2 * i) / 10 + Math.random() * 0.5;
                const distance = 50 + Math.random() * 70;

                span.textContent = emojis[i % emojis.length];
                span.style.setProperty("--dx", Math.cos(angle) * distance + "px");
                span.style.setProperty("--dy", Math.sin(angle) * distance + "px");
                span.style.setProperty("--rot", (Math.random() * 120 - 60) + "deg");

                wrap.appendChild(span);
            }

            document.body.appendChild(wrap);

            setTimeout(function () {
                wrap.remove();
            }, 950);
        }

        const streakMilestones = {
            3: ["🔥", "Hot streak!", "3 correct in a row"],
            5: ["⚡", "On fire!", "5 correct in a row"],
            7: ["🚀", "Unstoppable!", "7 correct in a row"],
            10: ["👑", "Legendary!", "10 correct in a row"]
        };

        /* ---------- Sound ---------- */

        let audioCtx = null;
        let soundOn = true;

        try {
            soundOn = window.localStorage.getItem("eduquestSound") !== "off";
        } catch (error) {
            soundOn = true;
        }

        function updateSoundButton() {
            soundButton.textContent = soundOn ? "🔊" : "🔇";
            soundButton.setAttribute("aria-pressed", soundOn ? "true" : "false");
        }

        function playTone(notes) {
            if (!soundOn) {
                return;
            }

            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    return;
                }

                if (!audioCtx) {
                    audioCtx = new AudioContextClass();
                }

                let time = audioCtx.currentTime;

                notes.forEach(function (frequency) {
                    const oscillator = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();

                    oscillator.type = "sine";
                    oscillator.frequency.value = frequency;

                    gain.gain.setValueAtTime(0.0001, time);
                    gain.gain.exponentialRampToValueAtTime(0.12, time + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, time + 0.18);

                    oscillator.connect(gain);
                    gain.connect(audioCtx.destination);

                    oscillator.start(time);
                    oscillator.stop(time + 0.2);

                    time += 0.12;
                });
            } catch (error) {
                /* Sound is optional; ignore any audio errors. */
            }
        }

        soundButton.addEventListener("click", function () {
            soundOn = !soundOn;

            try {
                window.localStorage.setItem("eduquestSound", soundOn ? "on" : "off");
            } catch (error) {
                /* Storage unavailable; keep the setting for this page only. */
            }

            updateSoundButton();
            playTone([660]);
        });

        updateSoundButton();

        /* ---------- Timer ---------- */

        setInterval(function () {
            elapsed++;
            hudTime.textContent = formatTime(elapsed);
        }, 1000);

        /* ---------- Answer helpers ---------- */

        function getAnswer(index) {
            const panel = questionPanels[index];

            if (!panel) {
                return "";
            }

            const jsonField = panel.querySelector("[data-answer-json]");

            if (jsonField) {
                return jsonField.value;
            }

            const checkedRadio = panel.querySelector('input[type="radio"]:checked');

            if (checkedRadio) {
                return checkedRadio.value;
            }

            const textInput = panel.querySelector('input[type="text"]');

            return textInput ? textInput.value.trim() : "";
        }

        function questionIsAnswered(index) {
            const panel = questionPanels[index];

            if (!panel) {
                return false;
            }

            const jsonField = panel.querySelector("[data-answer-json]");

            if (jsonField) {
                let parsed = null;

                try {
                    parsed = JSON.parse(jsonField.value);
                } catch (error) {
                    return false;
                }

                const type = panel.dataset.type;

                if (type === "ordering") {
                    return Array.isArray(parsed) && parsed.length > 0;
                }

                if (type === "matching") {
                    return parsed !== null
                        && Object.keys(parsed).length === panel.querySelectorAll(".match-left").length;
                }

                if (type === "fill_blank") {
                    return Array.isArray(parsed)
                        && parsed.length > 0
                        && parsed.every(function (word) { return word !== ""; });
                }

                return false;
            }

            if (panel.querySelectorAll('input[type="radio"]').length > 0) {
                return panel.querySelector('input[type="radio"]:checked') !== null;
            }

            const textInput = panel.querySelector('input[type="text"]');

            return textInput ? textInput.value.trim().length > 0 : false;
        }

        function answeredCount() {
            return questionPanels.filter(function (panel, index) {
                return questionIsAnswered(index);
            }).length;
        }

        function firstUncheckedIndex() {
            const index = results.findIndex(function (item) {
                return item === null;
            });

            return index === -1 ? totalQuestions - 1 : index;
        }

        function allChecked() {
            return results.every(function (item) {
                return item !== null;
            });
        }

        function lockQuestion(index) {
            const panel = questionPanels[index];

            panel.classList.add("locked");

            panel.querySelectorAll('input[type="text"]').forEach(function (input) {
                input.readOnly = true;
            });

            panel.querySelectorAll(".ord-item").forEach(function (item) {
                item.draggable = false;
            });
        }

        // Block changing answers on locked questions (radios stay enabled so they still submit).
        form.addEventListener("click", function (event) {
            const lockedPanel = event.target.closest(".quest-question.locked");

            if (lockedPanel && event.target.closest(".quest-option")) {
                event.preventDefault();
            }
        }, true);

        form.addEventListener("keydown", function (event) {
            const lockedPanel = event.target.closest(".quest-question.locked");

            if (
                lockedPanel
                && ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " "].includes(event.key)
                && event.target.type === "radio"
            ) {
                event.preventDefault();
            }
        });

        /* ---------- HUD / progress ---------- */

        function updateHud() {
            hudXp.textContent = formatNumber(xpEarned);
            hudStreak.textContent = String(streak);

            hudStreakTile.classList.toggle("hot", streak >= 3);

            hudAccuracy.textContent = verifiedCount > 0
                ? Math.round((correctChecked / verifiedCount) * 100) + "%"
                : "—";

            if (hudHints) {
                hudHints.textContent = String(hintsLeft);
            }
        }

        function updateProgress() {
            const answered = answeredCount();
            const percentage = totalQuestions > 0
                ? Math.round((answered / totalQuestions) * 100)
                : 0;

            progressText.textContent = percentage + "% complete";
            progressFill.style.width = percentage + "%";

            if (rocket) {
                rocket.style.left = "calc(" + percentage + "% - " + (percentage / 100 * 22) + "px)";
            }

            if (answered === totalQuestions) {
                progressNote.textContent = "All answers ready. You can finish your mission!";
            } else if (answered === 0) {
                progressNote.textContent = "Every answer moves you closer to the finish.";
            } else {
                progressNote.textContent =
                    answered + " of " + totalQuestions + " questions answered. Keep going!";
            }

            const reachable = firstUncheckedIndex();

            dots.forEach(function (dot, index) {
                const state = results[index];

                dot.classList.toggle("current", index === currentQuestion);
                dot.classList.toggle("correct", state !== null && state.correct === true);
                dot.classList.toggle("wrong", state !== null && state.correct === false);
                dot.classList.toggle(
                    "answered",
                    questionIsAnswered(index)
                    && (state === null || state.correct === null)
                );

                dot.disabled = index > reachable;
            });
        }

        function updateHintUi() {
            if (hudHints) {
                hudHints.textContent = String(hintsLeft);
            }

            if (!hintButton) {
                return;
            }

            const panel = questionPanels[currentQuestion];
            const hintable = panel && panel.dataset.hintable === "1";
            const used = panel && panel.dataset.hintUsed === "1";
            const checked = results[currentQuestion] !== null;

            hintButton.textContent = used ? "💡 Show hint" : "💡 Hint (" + hintsLeft + ")";

            hintButton.disabled = isChecking
                || checked
                || !hintable
                || (!used && hintsLeft <= 0);

            hintButton.title = hintable ? "" : "No hint for this question";
        }

        function updateNavigation() {
            const checked = results[currentQuestion] !== null;
            const isLast = currentQuestion === totalQuestions - 1;

            previousButton.disabled = currentQuestion === 0;

            if (!checked) {
                actionButton.hidden = false;
                actionButton.textContent = "Check Answer ✔";
            } else if (!isLast) {
                actionButton.hidden = false;
                actionButton.textContent = "Next Question →";
            } else {
                actionButton.hidden = true;
            }

            submitButton.hidden = !allChecked();

            counter.textContent =
                "QUESTION " +
                String(currentQuestion + 1).padStart(2, "0") +
                " / " +
                String(totalQuestions).padStart(2, "0");

            updateProgress();
            updateHintUi();
        }

        function showQuestion(index, moveFocus) {
            if (index < 0 || index >= totalQuestions) {
                return;
            }

            currentQuestion = index;

            questionPanels.forEach(function (panel, panelIndex) {
                panel.hidden = panelIndex !== currentQuestion;
            });

            hideValidation();
            updateNavigation();

            if (moveFocus) {
                const textInput = questionPanels[index].querySelector('input[type="text"]');

                if (textInput && !textInput.readOnly) {
                    textInput.focus({ preventScroll: true });
                }
            }
        }

        /* ---------- Feedback ---------- */

        function showFeedback(index, data, streakNow) {
            const panel = questionPanels[index];
            const box = panel.querySelector("[data-feedback]");
            const icon = box.querySelector(".quest-feedback-icon");
            const title = box.querySelector(".quest-feedback-title");
            const text = box.querySelector(".quest-feedback-text");

            panel.querySelectorAll(".quest-option").forEach(function (label) {
                const input = label.querySelector("input");

                if (!input) {
                    return;
                }

                let isCorrectOption = false;

                if (data.correct_option_id !== null && data.correct_option_id !== undefined) {
                    isCorrectOption = String(data.correct_option_id) === input.value;
                } else if (data.correct_answer) {
                    isCorrectOption =
                        input.value.trim().toLowerCase() ===
                        String(data.correct_answer).trim().toLowerCase();
                }

                label.classList.remove("is-eliminated");
                label.classList.toggle("is-correct", isCorrectOption);
                label.classList.toggle("is-wrong", input.checked && !isCorrectOption);
            });

            const textInput = panel.querySelector('input[type="text"]');

            if (textInput) {
                textInput.classList.add(data.correct ? "is-correct" : "is-wrong");
            }

            const qType = panel.dataset.type;

            if (qType === "ordering" && Array.isArray(data.detail)) {
                panel.querySelectorAll(".ord-item").forEach(function (item, i) {
                    item.classList.add(data.detail[i] ? "is-correct" : "is-wrong");
                });
            }

            if (qType === "matching" && data.detail) {
                panel.querySelectorAll(".match-left").forEach(function (left) {
                    const ok = data.detail[left.dataset.id] === true;
                    left.classList.add(ok ? "is-correct" : "is-wrong");

                    const pairNo = left.dataset.pair;

                    if (pairNo) {
                        const right = panel.querySelector('.match-right[data-pair="' + pairNo + '"]');

                        if (right) {
                            right.classList.add(ok ? "is-correct" : "is-wrong");
                        }
                    }
                });
            }

            if (qType === "fill_blank" && Array.isArray(data.detail)) {
                panel.querySelectorAll(".fb-slot").forEach(function (slot, i) {
                    slot.classList.add(data.detail[i] ? "is-correct" : "is-wrong");
                });
            }

            let message = "";

            if (
                !data.correct
                && ["short_answer", "ordering", "matching", "fill_blank"].includes(qType)
                && data.correct_answer
            ) {
                message += "Correct answer: " + data.correct_answer + ". ";
            }

            if (data.explanation) {
                message += data.explanation;
            }

            if (message === "") {
                message = data.correct
                    ? "Nice work. Keep the momentum going!"
                    : "Review the lesson and you will get it next time.";
            }

            box.className = "quest-feedback " + (data.correct ? "correct" : "wrong");
            icon.textContent = data.correct ? "🎉" : "💡";

            const earned = formatNumber(Number(data.points_awarded) || 0);
            const hintNote = data.hint_used && hintPenalty > 0
                ? " (hint −" + hintPenalty + "%)"
                : "";

            if (data.correct) {
                const bonus = streakNow >= 3 ? " 🔥 " + streakNow + " in a row!" : "";
                title.textContent = "Correct! +" + earned + " XP" + hintNote + bonus;
            } else if (data.partial) {
                title.textContent = "Almost there! +" + earned + " XP" + hintNote;
            } else {
                title.textContent = "Not quite";
            }

            text.textContent = message;
            box.hidden = false;
        }

        function showUnverifiedFeedback(index) {
            const box = questionPanels[index].querySelector("[data-feedback]");

            box.className = "quest-feedback neutral";
            box.querySelector(".quest-feedback-icon").textContent = "📝";
            box.querySelector(".quest-feedback-title").textContent = "Answer saved";
            box.querySelector(".quest-feedback-text").textContent =
                "We could not check this one right now, but your answer is saved and will be graded when you finish.";
            box.hidden = false;
        }

        /* ---------- Server requests ---------- */

        async function postAction(action, extra) {
            const body = new URLSearchParams();

            body.set("action", action);
            body.set("activity_id", form.elements["activity_id"].value);
            body.set("csrf_token", form.elements["csrf_token"].value);

            Object.keys(extra || {}).forEach(function (key) {
                body.set(key, extra[key]);
            });

            const response = await fetch(window.location.href, {
                method: "POST",
                headers: { "Accept": "application/json" },
                credentials: "same-origin",
                body: body
            });

            return response.json();
        }

        /* ---------- Check answer ---------- */

        async function checkCurrentQuestion() {
            const index = currentQuestion;
            const panel = questionPanels[index];

            isChecking = true;
            actionButton.disabled = true;
            actionButton.textContent = "Checking…";
            hideValidation();
            updateHintUi();

            let data = null;

            try {
                data = await postAction("check_answer", {
                    question_id: panel.dataset.qid,
                    answer: getAnswer(index)
                });

                if (!data || data.ok !== true) {
                    throw new Error("Unexpected response");
                }
            } catch (error) {
                data = null;
            }

            lockQuestion(index);

            if (data) {
                verifiedCount++;
                results[index] = { correct: data.correct === true };

                const gained = Number(data.points_awarded) || 0;

                if (gained > 0) {
                    xpEarned += gained;
                    floatText(hudXpTile, "+" + formatNumber(gained));
                    restartAnimation(hudXpTile, "pop");
                }

                if (data.correct === true) {
                    streak++;
                    correctChecked++;
                    bestStreak = Math.max(bestStreak, streak);

                    if (streak >= 2) {
                        restartAnimation(hudStreakTile, "pop");
                    }

                    playTone([523, 659, 784]);
                } else {
                    streak = 0;
                    playTone([220, 165]);
                    restartAnimation(panel, "shake");

                    setTimeout(function () {
                        panel.classList.remove("shake");
                    }, 500);
                }

                showFeedback(index, data, streak);

                if (data.correct === true) {
                    burst(
                        panel.querySelector(".quest-option.is-correct")
                            || panel.querySelector("[data-feedback]"),
                        ["✨", "⭐", "🎉"]
                    );

                    mascotSay(streak >= 3 ? "fire" : "happy", mascotLines.correct);

                    const milestone = streakMilestones[streak];

                    if (milestone) {
                        toast(milestone[0], milestone[1], milestone[2]);
                        playTone([523, 659, 784, 1047]);
                    }
                } else if (data.partial) {
                    mascotSay("sad", mascotLines.partial);
                } else {
                    mascotSay("sad", mascotLines.wrong);
                }

                if (
                    totalQuestions >= 4
                    && !halfwayShown
                    && verifiedCount === Math.ceil(totalQuestions / 2)
                ) {
                    halfwayShown = true;
                    toast("🎯", "Halfway there!", "Keep that momentum going");
                }
            } else {
                results[index] = { correct: null };
                showUnverifiedFeedback(index);
            }

            if (allChecked()) {
                mascotSay("cheer", mascotLines.done);
            }

            isChecking = false;
            actionButton.disabled = false;

            updateHud();
            updateNavigation();
        }

        /* ---------- Hints ---------- */

        function applyHint(panel, hint, firstTime) {
            const box = panel.querySelector("[data-hintbox]");
            const textEl = box.querySelector("[data-hintbox-text]");
            const line = document.createElement("span");

            textEl.textContent = "";
            line.textContent = hint.text || "Here is a hint.";
            textEl.appendChild(line);

            if (hint.kind === "pattern") {
                const pattern = document.createElement("span");

                pattern.className = "quest-hint-pattern";
                pattern.textContent = hint.pattern;
                textEl.appendChild(pattern);
            }

            if (firstTime && hintPenalty > 0) {
                const note = document.createElement("small");

                note.className = "quest-hint-note";
                note.textContent =
                    "Using a hint makes this question worth " + hintPenalty + "% less.";
                textEl.appendChild(note);
            }

            box.hidden = false;

            if (hint.kind === "remove_options" && Array.isArray(hint.option_ids)) {
                const radios = Array.from(panel.querySelectorAll('input[type="radio"]'));

                hint.option_ids.forEach(function (id) {
                    const radio = radios.find(function (input) {
                        return input.value === String(id);
                    });

                    if (!radio) {
                        return;
                    }

                    radio.checked = false;
                    radio.disabled = true;

                    const label = radio.closest(".quest-option");

                    if (label) {
                        label.classList.add("is-eliminated");
                    }
                });

                updateProgress();
            }

            if (hint.kind === "remove_words" && Array.isArray(hint.words)) {
                const chips = Array.from(panel.querySelectorAll(".fb-chip"));

                hint.words.forEach(function (word) {
                    const chip = chips.find(function (item) {
                        return item.dataset.word === word && !item.classList.contains("used");
                    });

                    if (chip) {
                        chip.classList.add("is-eliminated");
                    }
                });
            }

            if (hint.kind === "reveal_first" && typeof panel.fqRevealFirst === "function") {
                panel.fqRevealFirst(hint.option_id);
            }

            if (hint.kind === "reveal_pair" && typeof panel.fqRevealPair === "function") {
                panel.fqRevealPair(hint.option_id, hint.match_text);
            }
        }

        async function useHint() {
            if (!hintsEnabled || !hintButton || isChecking || hintButton.disabled) {
                return;
            }

            const index = currentQuestion;
            const panel = questionPanels[index];

            hintButton.disabled = true;

            let data = null;

            try {
                data = await postAction("hint", {
                    question_id: panel.dataset.qid
                });
            } catch (error) {
                data = null;
            }

            if (!data || data.ok !== true) {
                showValidation(
                    data && data.message
                        ? data.message
                        : "Could not load a hint right now."
                );
                updateHintUi();
                return;
            }

            const firstTime = panel.dataset.hintUsed !== "1";

            panel.dataset.hintUsed = "1";
            hintsLeft = Number(data.remaining) || 0;

            applyHint(panel, data.hint, firstTime);
            playTone([784, 988]);
            mascotSay("hint", mascotLines.hint);

            updateHud();
            updateHintUi();
        }

        if (hintButton) {
            hintButton.addEventListener("click", useHint);
        }

        /* ---------- Primary action ---------- */

        async function handleAction() {
            if (isChecking) {
                return;
            }

            if (results[currentQuestion] !== null) {
                if (currentQuestion < totalQuestions - 1) {
                    showQuestion(currentQuestion + 1, true);

                    if (
                        currentQuestion === totalQuestions - 1
                        && totalQuestions > 1
                        && results[currentQuestion] === null
                    ) {
                        mascotSay("idle", mascotLines.last);
                    }
                }

                return;
            }

            if (!questionIsAnswered(currentQuestion)) {
                const type = questionPanels[currentQuestion].dataset.type;

                if (type === "matching") {
                    showValidation("Match every item on the left before checking.");
                } else if (type === "fill_blank") {
                    showValidation("Fill in every blank before checking.");
                } else {
                    showValidation("Choose or type an answer before checking it.");
                }

                return;
            }

            await checkCurrentQuestion();
        }

        actionButton.addEventListener("click", handleAction);

        previousButton.addEventListener("click", function () {
            if (currentQuestion > 0) {
                showQuestion(currentQuestion - 1, false);
            }
        });

        dots.forEach(function (dot, index) {
            dot.addEventListener("click", function () {
                if (!dot.disabled) {
                    showQuestion(index, false);
                }
            });
        });

        form.addEventListener("change", function () {
            hideValidation();
            updateProgress();
        });

        form.addEventListener("input", function () {
            updateProgress();
        });

        /* ---------- Review modal + submit ---------- */

        function openModal() {
            lastFocused = document.activeElement;

            modalAnswered.textContent = answeredCount() + "/" + totalQuestions;
            modalAccuracy.textContent = verifiedCount > 0
                ? Math.round((correctChecked / verifiedCount) * 100) + "%"
                : "—";
            modalTime.textContent = formatTime(elapsed);

            modal.hidden = false;
            modalConfirm.focus();
        }

        function closeModal() {
            modal.hidden = true;

            if (lastFocused && typeof lastFocused.focus === "function") {
                lastFocused.focus();
            }
        }

        modalCancel.addEventListener("click", closeModal);

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        modalConfirm.addEventListener("click", function () {
            submitConfirmed = true;
            modalConfirm.disabled = true;
            modalConfirm.textContent = "Grading…";

            if (clientSecondsField) {
                clientSecondsField.value = String(elapsed);
            }

            if (bestStreakField) {
                bestStreakField.value = String(bestStreak);
            }

            form.submit();
        });

        form.addEventListener("submit", function (event) {
            if (submitConfirmed) {
                return;
            }

            event.preventDefault();

            for (let index = 0; index < totalQuestions; index++) {
                if (!questionIsAnswered(index)) {
                    showQuestion(index, false);
                    showValidation(
                        "You still have an unanswered question. Answer it before finishing your mission."
                    );
                    return;
                }
            }

            openModal();
        });

        /* ---------- Keyboard shortcuts ---------- */

        document.addEventListener("keydown", function (event) {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (!modal.hidden) {
                if (event.key === "Escape") {
                    closeModal();
                }

                return;
            }

            const target = event.target;
            const isTextInput = target && target.matches && target.matches('input[type="text"]');

            if (event.key === "Enter") {
                if (isTextInput) {
                    event.preventDefault();
                    handleAction();
                    return;
                }

                if (target && target.closest && target.closest("button, a, summary")) {
                    return;
                }

                event.preventDefault();

                if (!actionButton.hidden) {
                    handleAction();
                } else if (!submitButton.hidden) {
                    submitButton.click();
                }

                return;
            }

            if (isTextInput || event.key.length !== 1) {
                return;
            }

            const panel = questionPanels[currentQuestion];

            if (!panel) {
                return;
            }

            const key = event.key.toLowerCase();
            const radios = panel.querySelectorAll('input[type="radio"]');

            // H = hint (unless this question has 8 options and H is an answer letter).
            if (key === "h" && hintsEnabled && radios.length < 8) {
                useHint();
                return;
            }

            if (panel.classList.contains("locked")) {
                return;
            }

            const letterIndex = "abcdefgh".indexOf(key);

            if (letterIndex === -1) {
                return;
            }

            if (radios[letterIndex] && !radios[letterIndex].disabled) {
                radios[letterIndex].checked = true;
                radios[letterIndex].dispatchEvent(new Event("change", { bubbles: true }));
            }
        });

        /* =====================================================
           INTERACTIVE QUESTION WIDGETS
           ===================================================== */

        function setJson(panel, value) {
            const field = panel.querySelector("[data-answer-json]");

            field.value = JSON.stringify(value);
            field.dispatchEvent(new Event("input", { bubbles: true }));
        }

        /* ---------- Ordering ---------- */

        function initOrdering(panel) {
            const list = panel.querySelector(".ord-list");
            let dragged = null;

            function sync() {
                const items = Array.from(list.querySelectorAll(".ord-item"));

                items.forEach(function (item, i) {
                    item.querySelector(".ord-num").textContent = i + 1;
                    item.querySelector(".ord-up").disabled = i === 0;
                    item.querySelector(".ord-down").disabled = i === items.length - 1;
                });

                setJson(panel, items.map(function (item) {
                    return Number(item.dataset.id);
                }));
            }

            list.addEventListener("click", function (event) {
                const item = event.target.closest(".ord-item");

                if (!item) {
                    return;
                }

                if (event.target.closest(".ord-up") && item.previousElementSibling) {
                    list.insertBefore(item, item.previousElementSibling);
                    sync();
                } else if (event.target.closest(".ord-down") && item.nextElementSibling) {
                    list.insertBefore(item.nextElementSibling, item);
                    sync();
                }
            });

            list.addEventListener("dragstart", function (event) {
                dragged = event.target.closest(".ord-item");

                if (dragged) {
                    dragged.classList.add("dragging");
                    event.dataTransfer.effectAllowed = "move";
                    event.dataTransfer.setData("text/plain", "");
                }
            });

            list.addEventListener("dragover", function (event) {
                event.preventDefault();

                const over = event.target.closest(".ord-item");

                if (!dragged || !over || over === dragged) {
                    return;
                }

                const rect = over.getBoundingClientRect();
                const after = event.clientY > rect.top + rect.height / 2;

                list.insertBefore(dragged, after ? over.nextElementSibling : over);
            });

            list.addEventListener("dragend", function () {
                if (dragged) {
                    dragged.classList.remove("dragging");
                    dragged = null;
                    sync();
                }
            });

            // Hint: move the correct first step to the top and pin it.
            panel.fqRevealFirst = function (optionId) {
                const item = Array.from(list.querySelectorAll(".ord-item")).find(function (el) {
                    return el.dataset.id === String(optionId);
                });

                if (!item) {
                    return;
                }

                list.insertBefore(item, list.firstElementChild);
                item.classList.add("is-pinned");
                sync();
            };

            sync();
        }

        /* ---------- Matching ---------- */

        function initMatching(panel) {
            const lefts = Array.from(panel.querySelectorAll(".match-left"));
            const rights = Array.from(panel.querySelectorAll(".match-right"));
            const pairOf = new Map();
            const colors = [
                "#5DD62C", "#4DA3FF", "#FFB020", "#C77DFF",
                "#FF6B6B", "#2EC4B6", "#F15BB5", "#8D99AE"
            ];
            let activeLeft = null;

            function save() {
                const out = {};

                pairOf.forEach(function (right, left) {
                    out[left.dataset.id] = right.dataset.value;
                });

                setJson(panel, out);
            }

            function paint() {
                let n = 0;

                lefts.concat(rights).forEach(function (el) {
                    el.removeAttribute("data-pair");
                    el.style.removeProperty("--pc");
                    el.classList.remove("active");
                });

                pairOf.forEach(function (right, left) {
                    n++;

                    [left, right].forEach(function (el) {
                        el.dataset.pair = n;
                        el.style.setProperty("--pc", colors[(n - 1) % colors.length]);
                    });
                });

                if (activeLeft) {
                    activeLeft.classList.add("active");
                }
            }

            panel.querySelector(".match-board").addEventListener("click", function (event) {
                const button = event.target.closest(".match-item");

                if (!button) {
                    return;
                }

                if (button.classList.contains("match-left")) {
                    if (pairOf.has(button)) {
                        pairOf.delete(button);
                        activeLeft = button;
                    } else {
                        activeLeft = activeLeft === button ? null : button;
                    }
                } else {
                    let owner = null;

                    pairOf.forEach(function (right, left) {
                        if (right === button) {
                            owner = left;
                        }
                    });

                    if (activeLeft) {
                        if (owner) {
                            pairOf.delete(owner);
                        }

                        pairOf.set(activeLeft, button);
                        activeLeft = null;
                    } else if (owner) {
                        pairOf.delete(owner);
                    }
                }

                paint();
                save();
            });

            // Hint: pair one item for the student.
            panel.fqRevealPair = function (optionId, value) {
                const left = lefts.find(function (el) {
                    return el.dataset.id === String(optionId);
                });

                if (!left) {
                    return;
                }

                const taken = Array.from(pairOf.values());

                const right = rights.find(function (el) {
                    return el.dataset.value === value && taken.indexOf(el) === -1;
                }) || rights.find(function (el) {
                    return el.dataset.value === value;
                });

                if (!right) {
                    return;
                }

                pairOf.forEach(function (pairedRight, pairedLeft) {
                    if (pairedRight === right) {
                        pairOf.delete(pairedLeft);
                    }
                });

                pairOf.set(left, right);
                activeLeft = null;

                paint();
                save();

                left.classList.add("is-hinted");
                right.classList.add("is-hinted");
            };
        }

        /* ---------- Fill in the blank ---------- */

        function initFillBlank(panel) {
            const slots = Array.from(panel.querySelectorAll(".fb-slot"));
            const chips = Array.from(panel.querySelectorAll(".fb-chip"));
            const filled = new Array(slots.length).fill(null);

            function save() {
                setJson(panel, filled.map(function (chip) {
                    return chip ? chip.dataset.word : "";
                }));
            }

            function paint() {
                slots.forEach(function (slot, i) {
                    slot.textContent = filled[i] ? filled[i].dataset.word : "";
                    slot.classList.toggle("filled", filled[i] !== null);
                });

                chips.forEach(function (chip) {
                    chip.classList.toggle("used", filled.includes(chip));
                });
            }

            chips.forEach(function (chip) {
                chip.addEventListener("click", function () {
                    if (chip.classList.contains("used")) {
                        return;
                    }

                    const i = filled.indexOf(null);

                    if (i === -1) {
                        return;
                    }

                    filled[i] = chip;
                    paint();
                    save();
                });
            });

            slots.forEach(function (slot, i) {
                slot.addEventListener("click", function () {
                    if (filled[i]) {
                        filled[i] = null;
                        paint();
                        save();
                    }
                });
            });
        }

        questionPanels.forEach(function (panel) {
            const type = panel.dataset.type;

            if (type === "ordering") {
                initOrdering(panel);
            } else if (type === "matching") {
                initMatching(panel);
            } else if (type === "fill_blank") {
                initFillBlank(panel);
            }
        });

        /* ---------- Start ---------- */

        updateHud();
        showQuestion(0, false);
        mascotSay("idle", mascotLines.start);
    });
</script>

</body>
</html>