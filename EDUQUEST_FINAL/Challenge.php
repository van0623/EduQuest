<?php
session_start();

require_once __DIR__ . "/Admin_Portal/db_connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit;
}

$conn->set_charset("utf8mb4");

$userId = (int) $_SESSION["user_id"];
$MIN_MEANINGFUL_WORDS = 20;
$DEFAULT_PASSING_SCORE = 70.0;

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function challengeEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function countMeaningfulWords($answer)
{
    $stopWords = [
        "the", "and", "for", "are", "but", "not", "you", "your",
        "with", "this", "that", "these", "those", "was", "were",
        "is", "am", "be", "been", "being", "to", "of", "in", "on",
        "at", "by", "as", "an", "a", "or", "if", "it", "its", "we",
        "our", "they", "their", "he", "she", "his", "her", "them",
        "from", "then", "than", "there", "here", "can", "could",
        "would", "should", "will", "may", "might", "must", "have",
        "has", "had", "do", "does", "did", "i", "me", "my", "mine",
        "so", "such", "also", "very", "more", "most", "some", "any",
        "each", "all", "both", "what", "when", "where", "which",
        "who", "why", "how", "because", "while", "after", "before",
        "during", "about", "into", "through", "up", "down", "out",
        "off", "over", "under", "again", "once", "only", "own",
        "same", "just", "other", "another", "much", "many", "few",
        "less"
    ];

    preg_match_all(
        '/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u',
        $answer,
        $matches
    );

    $count = 0;

    foreach ($matches[0] as $word) {
        $word = mb_strtolower($word, "UTF-8");

        if (mb_strlen($word, "UTF-8") < 3) {
            continue;
        }

        if (in_array($word, $stopWords, true)) {
            continue;
        }

        $count++;
    }

    return $count;
}

/*
|--------------------------------------------------------------------------
| CHECK SAVED PASS
|--------------------------------------------------------------------------
*/

function hasPassedChallenge($feedback)
{
    return stripos((string) $feedback, "Result: PASSED.") !== false;
}

/*
|--------------------------------------------------------------------------
| CHALLENGE GRADING CRITERIA
|--------------------------------------------------------------------------
*/

function getChallengeCriteria($missionId)
{
    $criteria = [
        1 => [
            ["weight" => 25, "keywords" => ["prioritize", "priorities", "priority", "deadline", "deadlines", "urgent", "important"]],
            ["weight" => 25, "keywords" => ["organize", "organization", "checklist", "schedule", "plan", "planning"]],
            ["weight" => 20, "keywords" => ["communicate", "communication", "inform", "update", "coworker", "supervisor"]],
            ["weight" => 15, "keywords" => ["document", "documents", "records", "office supplies", "office task"]],
            ["weight" => 15, "keywords" => ["accuracy", "accurate", "review", "check", "verify", "complete"]]
        ],

        2 => [
            ["weight" => 25, "keywords" => ["organize", "organization", "arrange", "sort", "order", "categorize"]],
            ["weight" => 25, "keywords" => ["assigned places", "proper location", "label", "labels", "storage"]],
            ["weight" => 20, "keywords" => ["checklist", "procedure", "routine", "steps", "consistent"]],
            ["weight" => 15, "keywords" => ["safety", "safe", "walkways", "clear workspace", "clean workspace"]],
            ["weight" => 15, "keywords" => ["equipment", "supplies", "return", "maintain", "workplace"]]
        ],

        3 => [
            ["weight" => 25, "keywords" => ["alphabetical", "alphabetical order", "numerical", "chronological", "subject-based"]],
            ["weight" => 25, "keywords" => ["filing system", "filing method", "file", "filing", "categorize"]],
            ["weight" => 20, "keywords" => ["label", "labels", "index", "naming convention", "folder"]],
            ["weight" => 15, "keywords" => ["retrieve", "retrieval", "locate", "search", "find documents"]],
            ["weight" => 15, "keywords" => ["confidential", "authorized access", "access control", "protect records"]]
        ],

        4 => [
            ["weight" => 25, "keywords" => ["accurate", "accuracy", "complete records", "verify", "correct information"]],
            ["weight" => 25, "keywords" => ["confidentiality", "confidential", "privacy", "private information"]],
            ["weight" => 20, "keywords" => ["authorized", "authorization", "permission", "restricted access"]],
            ["weight" => 15, "keywords" => ["retention", "retention policy", "secure disposal", "disposal"]],
            ["weight" => 15, "keywords" => ["report errors", "report missing records", "supervisor", "accountability", "responsibility"]]
        ],

        5 => [
            ["weight" => 25, "keywords" => ["audience", "purpose", "main point", "clear message", "clarity"]],
            ["weight" => 25, "keywords" => ["respectful", "professional tone", "polite", "courteous", "appropriate tone"]],
            ["weight" => 20, "keywords" => ["listen", "listening", "confirm", "clarify", "understand"]],
            ["weight" => 15, "keywords" => ["relevant details", "facts", "accurate information", "complete information"]],
            ["weight" => 15, "keywords" => ["next step", "action items", "follow up", "deadline", "response"]]
        ],

        6 => [
            ["weight" => 25, "keywords" => ["subject line", "clear subject", "subject"]],
            ["weight" => 25, "keywords" => ["greeting", "salutation", "dear"]],
            ["weight" => 20, "keywords" => ["purpose", "main point", "message body", "relevant details"]],
            ["weight" => 15, "keywords" => ["closing", "signature", "name", "thank you", "regards"]],
            ["weight" => 15, "keywords" => ["proofread", "grammar", "spelling", "recipient", "attachments"]]
        ],

        7 => [
            ["weight" => 25, "keywords" => ["caller name", "name", "contact details", "phone number", "contact number"]],
            ["weight" => 25, "keywords" => ["date", "time", "date and time", "call time"]],
            ["weight" => 20, "keywords" => ["reason for calling", "purpose of the call", "message", "call details"]],
            ["weight" => 15, "keywords" => ["listen", "confirm", "repeat information", "verify details"]],
            ["weight" => 15, "keywords" => ["transfer", "follow up", "forward message", "responsible person", "next action"]]
        ],

        8 => [
            ["weight" => 25, "keywords" => ["clear", "clarity", "concise", "short sentences", "simple wording"]],
            ["weight" => 25, "keywords" => ["professional", "respectful", "polite", "appropriate tone", "courteous"]],
            ["weight" => 20, "keywords" => ["grammar", "spelling", "punctuation", "proofread", "edit"]],
            ["weight" => 15, "keywords" => ["purpose", "main point", "request", "action", "deadline"]],
            ["weight" => 15, "keywords" => ["review", "revise", "accuracy", "complete", "check message"]]
        ],

        9 => [
            ["weight" => 25, "keywords" => ["prioritize", "priority", "priorities", "urgent", "important"]],
            ["weight" => 25, "keywords" => ["deadline", "deadlines", "schedule", "time management", "time"]],
            ["weight" => 20, "keywords" => ["break tasks", "smaller tasks", "steps", "plan", "planning"]],
            ["weight" => 15, "keywords" => ["checklist", "organize", "resources", "tools", "routine"]],
            ["weight" => 15, "keywords" => ["review work", "accuracy", "quality", "avoid errors", "complete tasks"]]
        ],

        10 => [
            ["weight" => 25, "keywords" => ["headings", "heading", "sections", "organization", "structure"]],
            ["weight" => 25, "keywords" => ["readable font", "font size", "readability", "font", "consistent font"]],
            ["weight" => 20, "keywords" => ["spacing", "alignment", "formatting", "consistent formatting"]],
            ["weight" => 15, "keywords" => ["margins", "page breaks", "page numbers", "layout"]],
            ["weight" => 15, "keywords" => ["proofread", "spelling", "grammar", "review", "file format"]]
        ],

        11 => [
            ["weight" => 25, "keywords" => ["rows", "columns", "cells", "cell", "spreadsheet"]],
            ["weight" => 25, "keywords" => ["verify", "source data", "check entries", "accurate data", "accuracy"]],
            ["weight" => 20, "keywords" => ["formula", "formulas", "sum", "calculate", "totals"]],
            ["weight" => 15, "keywords" => ["duplicate", "duplicates", "duplicate records", "missing values", "entry errors"]],
            ["weight" => 15, "keywords" => ["consistent format", "data format", "review values", "proofread", "double check"]]
        ],

        12 => [
            ["weight" => 25, "keywords" => ["authorized", "authorized access", "permission", "restricted access"]],
            ["weight" => 25, "keywords" => ["strong password", "passwords", "secure password", "multi-factor authentication"]],
            ["weight" => 20, "keywords" => ["secure storage", "backup", "encryption", "approved storage"]],
            ["weight" => 15, "keywords" => ["report incident", "report immediately", "supervisor", "security team", "data breach"]],
            ["weight" => 15, "keywords" => ["confidential", "privacy", "do not share", "protect information", "personal information"]]
        ]
    ];

    return $criteria[$missionId] ?? [];
}

function evaluateChallengeAnswer($missionId, $answer, $minimumWords, $passingScore)
{
    $criteria = getChallengeCriteria($missionId);
    $wordCount = countMeaningfulWords($answer);

    if (empty($criteria)) {
        return [
            "word_count" => $wordCount,
            "score" => 0,
            "matched" => 0,
            "total" => 0,
            "passed" => false,
            "feedback" => "No grading criteria are configured for this mission. Result: FAILED."
        ];
    }

    $score = 0;
    $matched = 0;
    $total = count($criteria);

    foreach ($criteria as $criterion) {
        foreach ($criterion["keywords"] as $keyword) {
            $keywordPattern = preg_quote($keyword, "/");
            $keywordPattern = preg_replace('/\\\\ /', '\\s+', $keywordPattern);

            $pattern = '/(?<![\p{L}\p{N}])'
                . $keywordPattern
                . '(?![\p{L}\p{N}])/iu';

            if (preg_match($pattern, $answer)) {
                $score += (int) $criterion["weight"];
                $matched++;
                break;
            }
        }
    }

    $score = min(100, $score);
    $enoughWords = $wordCount >= $minimumWords;
    $passed = $enoughWords && $score >= $passingScore;

    if (!$enoughWords) {
        $feedback = "Your answer contains {$wordCount} meaningful words. "
            . "Write at least {$minimumWords} meaningful words and include relevant information. "
            . "Your keyword score was {$score}/100. Result: FAILED.";
    } elseif ($passed) {
        $feedback = "Your answer meets the minimum length and passed the keyword criteria. "
            . "{$matched} of {$total} criteria matched. Score: {$score}/100. Result: PASSED.";
    } else {
        $feedback = "Your answer meets the minimum length, but it needs more relevant details. "
            . "{$matched} of {$total} criteria matched. Score: {$score}/100. "
            . "Add more accurate information about the scenario and try again. Result: FAILED.";
    }

    return [
        "word_count" => $wordCount,
        "score" => $score,
        "matched" => $matched,
        "total" => $total,
        "passed" => $passed,
        "feedback" => $feedback
    ];
}

/*
|--------------------------------------------------------------------------
| LOAD CHALLENGE
|--------------------------------------------------------------------------
*/

$missionId = isset($_GET["mission_id"]) ? (int) $_GET["mission_id"] : 0;
$challengeId = isset($_GET["challenge_id"]) ? (int) $_GET["challenge_id"] : 0;

if ($missionId <= 0 && $challengeId > 0) {
    $lookup = $conn->prepare(
        "SELECT mission_id FROM challenges WHERE challenge_id = ? LIMIT 1"
    );
    $lookup->bind_param("i", $challengeId);
    $lookup->execute();
    $lookupRow = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if ($lookupRow) {
        $missionId = (int) $lookupRow["mission_id"];
    }
}

$challenge = null;
$accessError = "";
$lessonId = 0;
$activityPassed = false;
$previousPassedAttempt = null;
$latestAttempt = null;
$latestPercentage = 0;
$latestPassed = false;
$errorMessage = "";

if ($missionId <= 0) {
    $accessError = "Invalid mission. Please return to your course and select a valid mission.";
} else {
    $stmt = $conn->prepare("
        SELECT
            ch.challenge_id,
            ch.mission_id,
            ch.challenge_title,
            ch.challenge_description,
            ch.instructions,
            ch.total_points,
            ch.passing_score,
            m.mission_title,
            m.module_id,
            mo.module_title,
            mo.course_id,
            c.course_title
        FROM challenges ch
        INNER JOIN missions m ON m.mission_id = ch.mission_id
        INNER JOIN modules mo ON mo.module_id = m.module_id
        INNER JOIN courses c ON c.course_id = mo.course_id
        WHERE ch.mission_id = ?
          AND ch.status = 'published'
        LIMIT 1
    ");

    $stmt->bind_param("i", $missionId);
    $stmt->execute();
    $challenge = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$challenge) {
        $accessError = "No published challenge was found for this mission.";
    }
}

/*
|--------------------------------------------------------------------------
| VERIFY ACTIVITY AND FIND LESSON
|--------------------------------------------------------------------------
*/

if ($challenge) {
    $moduleId = (int) $challenge["module_id"];

    $lessonStmt = $conn->prepare("
        SELECT lesson_id
        FROM lessons
        WHERE module_id = ?
        ORDER BY lesson_id ASC
        LIMIT 1
    ");
    $lessonStmt->bind_param("i", $moduleId);
    $lessonStmt->execute();
    $lessonRow = $lessonStmt->get_result()->fetch_assoc();
    $lessonStmt->close();

    if ($lessonRow) {
        $lessonId = (int) $lessonRow["lesson_id"];
    }

    $activityStmt = $conn->prepare("
        SELECT aa.attempt_id
        FROM activity_attempts aa
        INNER JOIN activities a ON a.activity_id = aa.activity_id
        WHERE aa.user_id = ?
          AND a.mission_id = ?
          AND aa.status = 'passed'
        ORDER BY aa.attempt_id DESC
        LIMIT 1
    ");
    $activityStmt->bind_param("ii", $userId, $missionId);
    $activityStmt->execute();
    $activityPassed = $activityStmt->get_result()->num_rows > 0;
    $activityStmt->close();

    if (!$activityPassed) {
        $accessError = "Please complete and pass the activity for this mission before starting the challenge.";
    }
}

/*
|--------------------------------------------------------------------------
| FIND A PREVIOUSLY SAVED PASS
|--------------------------------------------------------------------------
*/

if ($challenge && $accessError === "") {
    $challengeIdValue = (int) $challenge["challenge_id"];

    $passedStmt = $conn->prepare("
        SELECT
            attempt_id,
            attempt_number,
            answer_text,
            score,
            status,
            feedback,
            submitted_at
        FROM challenge_attempts
        WHERE user_id = ?
          AND challenge_id = ?
          AND status = 'graded'
          AND feedback LIKE '%Result: PASSED.%'
        ORDER BY attempt_id DESC
        LIMIT 1
    ");

    $passedStmt->bind_param("ii", $userId, $challengeIdValue);
    $passedStmt->execute();
    $previousPassedAttempt = $passedStmt->get_result()->fetch_assoc();
    $passedStmt->close();
}

/*
|--------------------------------------------------------------------------
| PREPARE MISSION PROGRESS
|--------------------------------------------------------------------------
*/

if ($challenge && $accessError === "") {
    $progressStmt = $conn->prepare("
        INSERT INTO user_mission_progress
            (user_id, mission_id, status, progress_percent, started_at)
        VALUES (?, ?, 'in_progress', 50, NOW())
        ON DUPLICATE KEY UPDATE
            status = CASE
                WHEN status = 'completed' THEN 'completed'
                ELSE 'in_progress'
            END,
            progress_percent = CASE
                WHEN status = 'completed' THEN 100
                ELSE GREATEST(progress_percent, 50)
            END,
            started_at = COALESCE(started_at, NOW())
    ");

    $progressStmt->bind_param("ii", $userId, $missionId);
    $progressStmt->execute();
    $progressStmt->close();
}

/*
|--------------------------------------------------------------------------
| SUBMIT AND SAVE CHALLENGE RESULT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["submit_challenge"])
    && $challenge
    && $accessError === ""
) {
    $postedToken = $_POST["csrf_token"] ?? "";
    $sessionToken = $_SESSION["challenge_csrf_token"] ?? "";

    if (
        !is_string($postedToken)
        || !is_string($sessionToken)
        || $sessionToken === ""
        || !hash_equals($sessionToken, $postedToken)
    ) {
        $errorMessage = "Your session validation failed. Refresh the page and try again.";
    } elseif ($previousPassedAttempt) {
        header("Location: Challenge.php?mission_id=" . $missionId . "&completed=1");
        exit;
    } else {
        $answer = trim((string) ($_POST["answer_text"] ?? ""));

        if ($answer === "") {
            $errorMessage = "Please enter your answer before submitting.";
        } elseif (mb_strlen($answer, "UTF-8") > 20000) {
            $errorMessage = "Your answer is too long. Please shorten it and try again.";
        } else {
            $passingScore = isset($challenge["passing_score"])
                ? (float) $challenge["passing_score"]
                : $DEFAULT_PASSING_SCORE;

            $grading = evaluateChallengeAnswer(
                (int) $challenge["mission_id"],
                $answer,
                $MIN_MEANINGFUL_WORDS,
                $passingScore
            );

            $percentage = (float) $grading["score"];
            $totalPoints = max(1, (int) $challenge["total_points"]);
            $pointsEarned = round($totalPoints * ($percentage / 100), 2);

            $feedback = $grading["feedback"];
            $challengeIdValue = (int) $challenge["challenge_id"];

            $conn->begin_transaction();

            try {
                /*
                 * Get the next attempt number.
                 */
                $attemptStmt = $conn->prepare("
                    SELECT COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt
                    FROM challenge_attempts
                    WHERE user_id = ?
                      AND challenge_id = ?
                ");

                $attemptStmt->bind_param(
                    "ii",
                    $userId,
                    $challengeIdValue
                );
                $attemptStmt->execute();
                $attemptRow = $attemptStmt->get_result()->fetch_assoc();
                $attemptNumber = (int) ($attemptRow["next_attempt"] ?? 1);
                $attemptStmt->close();

                /*
                 * Check again before saving a new attempt.
                 */
                $recheckStmt = $conn->prepare("
                    SELECT attempt_id
                    FROM challenge_attempts
                    WHERE user_id = ?
                      AND challenge_id = ?
                      AND status = 'graded'
                      AND feedback LIKE '%Result: PASSED.%'
                    LIMIT 1
                ");

                $recheckStmt->bind_param(
                    "ii",
                    $userId,
                    $challengeIdValue
                );
                $recheckStmt->execute();
                $alreadyPassed = $recheckStmt->get_result()->num_rows > 0;
                $recheckStmt->close();

                if ($alreadyPassed) {
                    $conn->rollback();

                    header(
                        "Location: Challenge.php?mission_id="
                        . $missionId
                        . "&completed=1"
                    );
                    exit;
                }

                /*
                 * Save using the existing challenge_attempts columns.
                 * There is no result column in this INSERT.
                 */
                $saveStmt = $conn->prepare("
                    INSERT INTO challenge_attempts
                    (
                        user_id,
                        challenge_id,
                        attempt_number,
                        answer_text,
                        score,
                        status,
                        feedback,
                        started_at,
                        submitted_at,
                        graded_at
                    )
                    VALUES (?, ?, ?, ?, ?, 'graded', ?, NOW(), NOW(), NOW())
                ");

                $saveStmt->bind_param(
                    "iiisds",
                    $userId,
                    $challengeIdValue,
                    $attemptNumber,
                    $answer,
                    $pointsEarned,
                    $feedback
                );

                $saveStmt->execute();
                $saveStmt->close();

                if ($grading["passed"]) {
                    /*
                     * Mark the mission as completed.
                     */
                    $missionStmt = $conn->prepare("
                        INSERT INTO user_mission_progress
                        (
                            user_id,
                            mission_id,
                            status,
                            progress_percent,
                            started_at,
                            completed_at
                        )
                        VALUES (?, ?, 'completed', 100, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            status = 'completed',
                            progress_percent = 100,
                            completed_at = COALESCE(completed_at, NOW())
                    ");

                    $missionStmt->bind_param("ii", $userId, $missionId);
                    $missionStmt->execute();
                    $missionStmt->close();

                    /*
                     * Recalculate course progress.
                     */
                    $courseId = (int) $challenge["course_id"];

                    $countStmt = $conn->prepare("
                        SELECT
                            COUNT(m.mission_id) AS total_missions,
                            SUM(
                                CASE
                                    WHEN ump.status = 'completed' THEN 1
                                    ELSE 0
                                END
                            ) AS completed_missions
                        FROM missions m
                        LEFT JOIN user_mission_progress ump
                            ON ump.mission_id = m.mission_id
                           AND ump.user_id = ?
                        INNER JOIN modules mo
                            ON mo.module_id = m.module_id
                        WHERE mo.course_id = ?
                    ");

                    $countStmt->bind_param("ii", $userId, $courseId);
                    $countStmt->execute();
                    $courseCounts = $countStmt->get_result()->fetch_assoc();
                    $countStmt->close();

                    $totalMissions = (int) ($courseCounts["total_missions"] ?? 0);
                    $completedMissions = (int) ($courseCounts["completed_missions"] ?? 0);

                    $coursePercentage = $totalMissions > 0
                        ? round(($completedMissions / $totalMissions) * 100, 2)
                        : 0;

                    $courseStatus = (
                        $totalMissions > 0
                        && $completedMissions >= $totalMissions
                    ) ? "completed" : "in_progress";

                    $courseCompletedAt = $courseStatus === "completed"
                        ? date("Y-m-d H:i:s")
                        : null;

                    $courseStmt = $conn->prepare("
                        INSERT INTO user_course_progress
                        (
                            user_id,
                            course_id,
                            progress_percent,
                            status,
                            started_at,
                            completed_at
                        )
                        VALUES (?, ?, ?, ?, NOW(), ?)
                        ON DUPLICATE KEY UPDATE
                            progress_percent = GREATEST(
                                progress_percent,
                                VALUES(progress_percent)
                            ),
                            status = CASE
                                WHEN status = 'completed' THEN 'completed'
                                WHEN VALUES(status) = 'completed' THEN 'completed'
                                ELSE 'in_progress'
                            END,
                            completed_at = CASE
                                WHEN VALUES(status) = 'completed'
                                    THEN COALESCE(completed_at, NOW())
                                ELSE completed_at
                            END
                    ");

                    $courseStmt->bind_param(
                        "iidss",
                        $userId,
                        $courseId,
                        $coursePercentage,
                        $courseStatus,
                        $courseCompletedAt
                    );
                    $courseStmt->execute();
                    $courseStmt->close();
                }

                $conn->commit();

                $_SESSION["challenge_csrf_token"] = bin2hex(random_bytes(32));

                header(
                    "Location: Challenge.php?mission_id="
                    . $missionId
                    . "&submitted=1"
                );
                exit;
            } catch (Throwable $e) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                    // Keep the original database error for the server log.
                }

                error_log(
                    "EduQuest challenge grading error: " . $e->getMessage()
                );

                $errorMessage = "We could not save your challenge attempt. Please try again.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD SAVED RESULTS
|--------------------------------------------------------------------------
*/

$showResult = isset($_GET["submitted"]) && $_GET["submitted"] === "1";

if ($challenge && $accessError === "") {
    $challengeIdValue = (int) $challenge["challenge_id"];

    $latestStmt = $conn->prepare("
        SELECT
            attempt_id,
            attempt_number,
            answer_text,
            score,
            status,
            feedback,
            submitted_at
        FROM challenge_attempts
        WHERE user_id = ?
          AND challenge_id = ?
        ORDER BY attempt_id DESC
        LIMIT 1
    ");

    $latestStmt->bind_param("ii", $userId, $challengeIdValue);
    $latestStmt->execute();
    $latestAttempt = $latestStmt->get_result()->fetch_assoc();
    $latestStmt->close();

    /*
     * A previous passing attempt takes priority over a later failed attempt.
     */
    if (!$previousPassedAttempt) {
        $passedStmt = $conn->prepare("
            SELECT
                attempt_id,
                attempt_number,
                answer_text,
                score,
                status,
                feedback,
                submitted_at
            FROM challenge_attempts
            WHERE user_id = ?
              AND challenge_id = ?
              AND status = 'graded'
              AND feedback LIKE '%Result: PASSED.%'
            ORDER BY attempt_id DESC
            LIMIT 1
        ");

        $passedStmt->bind_param("ii", $userId, $challengeIdValue);
        $passedStmt->execute();
        $previousPassedAttempt = $passedStmt->get_result()->fetch_assoc();
        $passedStmt->close();
    }

    if ($previousPassedAttempt) {
        $latestAttempt = $previousPassedAttempt;
        $latestPassed = true;

        /*
         * Restore mission completion for previously saved passing attempts.
         */
        $completeProgressStmt = $conn->prepare("
            INSERT INTO user_mission_progress
            (
                user_id,
                mission_id,
                status,
                progress_percent,
                started_at,
                completed_at
            )
            VALUES (?, ?, 'completed', 100, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = 'completed',
                progress_percent = 100,
                completed_at = COALESCE(completed_at, NOW())
        ");

        $completeProgressStmt->bind_param("ii", $userId, $missionId);
        $completeProgressStmt->execute();
        $completeProgressStmt->close();

        /*
         * Ensure course progress reflects the saved mission completion.
         */
        $courseId = (int) $challenge["course_id"];

        $countStmt = $conn->prepare("
            SELECT
                COUNT(m.mission_id) AS total_missions,
                SUM(
                    CASE
                        WHEN ump.status = 'completed' THEN 1
                        ELSE 0
                    END
                ) AS completed_missions
            FROM missions m
            LEFT JOIN user_mission_progress ump
                ON ump.mission_id = m.mission_id
               AND ump.user_id = ?
            INNER JOIN modules mo
                ON mo.module_id = m.module_id
            WHERE mo.course_id = ?
        ");

        $countStmt->bind_param("ii", $userId, $courseId);
        $countStmt->execute();
        $courseCounts = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();

        $totalMissions = (int) ($courseCounts["total_missions"] ?? 0);
        $completedMissions = (int) ($courseCounts["completed_missions"] ?? 0);

        $coursePercentage = $totalMissions > 0
            ? round(($completedMissions / $totalMissions) * 100, 2)
            : 0;

        $courseStatus = (
            $totalMissions > 0
            && $completedMissions >= $totalMissions
        ) ? "completed" : "in_progress";

        $courseCompletedAt = $courseStatus === "completed"
            ? date("Y-m-d H:i:s")
            : null;

        $courseStmt = $conn->prepare("
            INSERT INTO user_course_progress
            (
                user_id,
                course_id,
                progress_percent,
                status,
                started_at,
                completed_at
            )
            VALUES (?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                progress_percent = GREATEST(
                    progress_percent,
                    VALUES(progress_percent)
                ),
                status = CASE
                    WHEN status = 'completed' THEN 'completed'
                    WHEN VALUES(status) = 'completed' THEN 'completed'
                    ELSE 'in_progress'
                END,
                completed_at = CASE
                    WHEN VALUES(status) = 'completed'
                        THEN COALESCE(completed_at, NOW())
                    ELSE completed_at
                END
        ");

        $courseStmt->bind_param(
            "iidss",
            $userId,
            $courseId,
            $coursePercentage,
            $courseStatus,
            $courseCompletedAt
        );
        $courseStmt->execute();
        $courseStmt->close();
    } elseif ($latestAttempt) {
        $latestPassed = hasPassedChallenge($latestAttempt["feedback"]);
    }

    if ($latestAttempt) {
        $totalPoints = max(1, (int) $challenge["total_points"]);

        $latestPercentage = (int) round(
            ((float) $latestAttempt["score"] / $totalPoints) * 100
        );
    }
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["challenge_csrf_token"])
    || !is_string($_SESSION["challenge_csrf_token"])
    || $_SESSION["challenge_csrf_token"] === ""
) {
    $_SESSION["challenge_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["challenge_csrf_token"];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $challenge
            ? challengeEscape($challenge["challenge_title"]) . " | EduQuest"
            : "Challenge | EduQuest" ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --black: #0F0F0F;
            --dark: #202020;
            --green: #5DD62C;
            --deep-green: #337418;
            --offwhite: #F8F8F8;
            --white: #FFFFFF;
            --text: #171717;
            --muted: #737373;
            --border: #E6E6E6;
            --surface-soft: #F3F4F2;
            --success-bg: #EDF9E8;
            --success-text: #286512;
            --error-bg: #FFF0F0;
            --error-text: #A32020;
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: var(--offwhite);
            color: var(--text);
            font-family: "Public Sans", Arial, sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        textarea {
            font: inherit;
        }

        .topbar {
            min-height: 76px;
            background: var(--black);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 6%;
        }

        .brand {
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -0.7px;
        }

        .brand span {
            color: var(--green);
        }

        .back-link {
            color: #E8E8E8;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--green);
        }

        .page {
            width: min(960px, calc(100% - 32px));
            margin: 38px auto 60px;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 20px;
        }

        .breadcrumb span:last-child {
            color: var(--deep-green);
            font-weight: 700;
        }

        .heading {
            margin-bottom: 25px;
        }

        .eyebrow {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        h1 {
            font-size: clamp(27px, 4vw, 36px);
            line-height: 1.2;
            letter-spacing: -1px;
            overflow-wrap: anywhere;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            margin-top: 10px;
            max-width: 720px;
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(20px, 4vw, 34px);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .panel-heading {
            margin-bottom: 18px;
        }

        .panel-heading h2 {
            font-size: 18px;
            line-height: 1.4;
        }

        .panel-heading p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 5px;
        }

        .scenario {
            padding: 18px;
            border-radius: 12px;
            background: var(--surface-soft);
            border-left: 4px solid var(--green);
            margin-bottom: 22px;
        }

        .scenario-label {
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .scenario p {
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .instructions {
            font-size: 13px;
            color: #454545;
            margin-bottom: 20px;
        }

        .rules {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .rule {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            background: #FCFCFC;
        }

        .rule strong {
            display: block;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .rule span {
            color: var(--muted);
            font-size: 11px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        textarea {
            display: block;
            width: 100%;
            min-height: 230px;
            padding: 16px;
            border: 1px solid #D8D8D8;
            border-radius: 12px;
            background: #FFFFFF;
            color: var(--text);
            resize: vertical;
            outline: none;
            font-size: 14px;
            line-height: 1.8;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(93, 214, 44, 0.12);
        }

        .field-help {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
        }

        .button-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 46px;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 12px 19px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--green);
            color: var(--black);
        }

        .btn-primary:hover {
            background: #4FC520;
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text);
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-soft);
        }

        .notice {
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 20px;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .notice strong {
            display: block;
            margin-bottom: 4px;
        }

        .notice-success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #CBE8BD;
        }

        .notice-error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #F0CDCD;
        }

        .notice-info {
            background: var(--surface-soft);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .result-score {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .score-number {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .score-label {
            color: var(--muted);
            font-size: 11px;
        }

        .result-status {
            display: inline-block;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.6px;
        }

        .status-passed {
            color: var(--success-text);
            background: #DDF4D3;
        }

        .status-failed {
            color: var(--error-text);
            background: #FFE0E0;
        }

        .feedback-text {
            font-size: 13px;
            line-height: 1.8;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .footer {
            color: var(--muted);
            text-align: center;
            font-size: 11px;
            padding: 20px 0;
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 15px 18px;
            }

            .brand {
                font-size: 20px;
            }

            .page {
                margin-top: 25px;
            }

            .rules {
                grid-template-columns: 1fr;
            }

            .rule {
                padding: 12px;
            }

            .button-row .btn {
                width: 100%;
            }

            textarea {
                min-height: 200px;
            }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="CoursePage.php" class="brand">
        EDU<span>QUEST</span>
    </a>

    <?php if (!($challenge && $lessonId > 0)): ?>
        <a class="back-link" href="CoursePage.php">← Back to Courses</a>
    <?php endif; ?>
</header>

<main class="page">

<?php if ($accessError !== ""): ?>

    <section class="panel">
        <div class="notice notice-error">
            <strong>Unable to open challenge</strong>
            <?= challengeEscape($accessError) ?>
        </div>

        <?php if ($lessonId > 0 && $challenge): ?>
            <a
                class="btn btn-secondary"
                href="LessonPage.php?lesson_id=<?= (int) $lessonId ?>&amp;module_id=<?= (int) $challenge["module_id"] ?>&amp;mission_id=<?= (int) $challenge["mission_id"] ?>"
            >
                ← Back to Lesson
            </a>
        <?php else: ?>
            <a class="btn btn-primary" href="CoursePage.php">Return to Courses</a>
        <?php endif; ?>
    </section>

<?php elseif ($challenge): ?>

    <div class="breadcrumb">
        <span><?= challengeEscape($challenge["course_title"]) ?></span>
        <span>›</span>
        <span><?= challengeEscape($challenge["module_title"]) ?></span>
        <span>›</span>
        <span><?= challengeEscape($challenge["mission_title"]) ?></span>
    </div>

    <section class="heading">
        <p class="eyebrow">Mission Challenge</p>

        <h1><?= challengeEscape($challenge["challenge_title"]) ?></h1>

        <p class="subtitle">
            Apply what you learned to this workplace scenario.
            Your response will be graded automatically after submission.
        </p>
    </section>

    <?php if ($errorMessage !== ""): ?>
        <div class="notice notice-error" role="alert">
            <strong>Submission error</strong>
            <?= challengeEscape($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($previousPassedAttempt): ?>

        <section class="panel" aria-live="polite">
            <div class="notice notice-success">
                <div class="result-score">
                    <div>
                        <div class="score-label">SAVED CHALLENGE SCORE</div>
                        <div class="score-number"><?= $latestPercentage ?>/100</div>
                    </div>

                    <span class="result-status status-passed">PASSED</span>
                </div>

                <strong>Challenge already completed!</strong>

                <p class="feedback-text">
                    <?= challengeEscape(
                        $previousPassedAttempt["feedback"]
                        ?? "Your passing result is saved."
                    ) ?>
                </p>

                <p class="feedback-text" style="margin-top: 8px;">
                    Your passing result is saved. You do not need to submit this Challenge again.
                </p>
            </div>

            <a
                class="btn btn-primary"
                href="Mission_complete.php?mission_id=<?= (int) $challenge["mission_id"] ?>"
            >
                Continue to Mission Complete →
            </a>
        </section>

    <?php elseif ($showResult && $latestAttempt): ?>

        <section class="panel" aria-live="polite">

            <?php if ($latestPassed): ?>

                <div class="notice notice-success">
                    <div class="result-score">
                        <div>
                            <div class="score-label">YOUR LATEST SCORE</div>
                            <div class="score-number"><?= $latestPercentage ?>/100</div>
                        </div>

                        <span class="result-status status-passed">PASSED</span>
                    </div>

                    <strong>Challenge completed!</strong>

                    <p class="feedback-text">
                        <?= challengeEscape($latestAttempt["feedback"]) ?>
                    </p>
                </div>

                <a
                    class="btn btn-primary"
                    href="Mission_complete.php?mission_id=<?= (int) $challenge["mission_id"] ?>"
                >
                    Continue to Mission Complete →
                </a>

            <?php else: ?>

                <div class="notice notice-error">
                    <div class="result-score">
                        <div>
                            <div class="score-label">YOUR LATEST SCORE</div>
                            <div class="score-number"><?= $latestPercentage ?>/100</div>
                        </div>

                        <span class="result-status status-failed">TRY AGAIN</span>
                    </div>

                    <strong>Keep working on your answer.</strong>

                    <p class="feedback-text">
                        <?= challengeEscape($latestAttempt["feedback"]) ?>
                    </p>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

    <?php if (!$previousPassedAttempt): ?>

        <section class="panel">
            <div class="panel-heading">
                <h2>Challenge Instructions</h2>

                <p>
                    Read the scenario carefully and explain your response
                    using relevant details from the lesson.
                </p>
            </div>

            <div class="scenario">
                <div class="scenario-label">Scenario</div>

                <p>
                    <?= challengeEscape(
                        $challenge["challenge_description"]
                        ?: $challenge["challenge_title"]
                    ) ?>
                </p>
            </div>

            <p class="instructions">
                <?= challengeEscape(
                    $challenge["instructions"]
                    ?: "Explain the steps you would take and why they are appropriate."
                ) ?>
            </p>

            <div class="rules">
                <div class="rule">
                    <strong><?= (int) $MIN_MEANINGFUL_WORDS ?> words</strong>
                    <span>Minimum meaningful words</span>
                </div>

                <div class="rule">
                    <strong>
                        <?= challengeEscape(
                            rtrim(
                                rtrim(
                                    number_format((float) $challenge["passing_score"], 2, ".", ""),
                                    "0"
                                ),
                                "."
                            )
                        ) ?>/100
                    </strong>
                    <span>Required passing score</span>
                </div>

                <div class="rule">
                    <strong><?= (int) $challenge["total_points"] ?> points</strong>
                    <span>Maximum challenge points</span>
                </div>
            </div>

            <form
                method="POST"
                action="Challenge.php?mission_id=<?= (int) $challenge["mission_id"] ?>"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= challengeEscape($csrfToken) ?>"
                >

                <label class="field-label" for="answer_text">Your Answer</label>

                <textarea
                    id="answer_text"
                    name="answer_text"
                    placeholder="Write your response here. Explain the steps you would take and include relevant information..."
                    maxlength="20000"
                    required
                ><?= challengeEscape(
                    $errorMessage !== ""
                        ? ($_POST["answer_text"] ?? "")
                        : ""
                ) ?></textarea>

                <div class="field-help">
                    <span>
                        Include relevant terms, actions, and explanations.
                    </span>

                    <span id="word-count">0 meaningful words</span>
                </div>

                <div class="button-row">
                    <button
                        type="submit"
                        name="submit_challenge"
                        value="1"
                        class="btn btn-primary"
                    >
                        Submit Challenge
                    </button>

                    <?php if ($lessonId > 0): ?>
                        <a
                            class="btn btn-secondary"
                            href="LessonPage.php?lesson_id=<?= (int) $lessonId ?>&amp;module_id=<?= (int) $challenge["module_id"] ?>&amp;mission_id=<?= (int) $challenge["mission_id"] ?>"
                        >
                            ← Back to Lesson
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

    <?php endif; ?>

<?php endif; ?>

<footer class="footer">
    © 2026 EduQuest · Your Learning Mission
</footer>

</main>

<script>
const answerBox = document.getElementById("answer_text");
const wordCountLabel = document.getElementById("word-count");

if (answerBox && wordCountLabel) {
    const stopWords = new Set([
        "the", "and", "for", "are", "but", "not", "you", "your",
        "with", "this", "that", "these", "those", "was", "were",
        "is", "am", "be", "been", "being", "to", "of", "in",
        "on", "at", "by", "as", "an", "a", "or", "if", "it",
        "its", "we", "our", "they", "their", "he", "she", "his",
        "her", "them", "from", "then", "than", "there", "here",
        "can", "could", "would", "should", "will", "may", "might",
        "must", "have", "has", "had", "do", "does", "did", "i",
        "me", "my", "mine", "so", "such", "also", "very", "more",
        "most", "some", "any", "each", "all", "both", "what",
        "when", "where", "which", "who", "why", "how", "because",
        "while", "after", "before", "during", "about", "into",
        "through", "up", "down", "out", "off", "over", "under",
        "again", "once", "only", "own", "same", "just", "other",
        "another", "much", "many", "few", "less"
    ]);

    function updateMeaningfulWordCount() {
        const words = answerBox.value.match(
            /[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/gu
        ) || [];

        const meaningful = words.filter(word => {
            const normalized = word.toLocaleLowerCase();

            return normalized.length >= 3
                && !stopWords.has(normalized);
        });

        wordCountLabel.textContent =
            meaningful.length + " meaningful words";
    }

    answerBox.addEventListener("input", updateMeaningfulWordCount);
    updateMeaningfulWordCount();
}
</script>

</body>
</html>