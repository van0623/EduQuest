
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$error = "";
$success = "";

/* -------------------------------------------------
   SESSION AND CSRF TOKEN
------------------------------------------------- */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/* -------------------------------------------------
   HELPER FUNCTION
------------------------------------------------- */

function clean($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* -------------------------------------------------
   HANDLE FORM SUBMISSIONS
------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        $action = $_POST['action'] ?? '';

        /* -----------------------------------------
           CREATE CHALLENGE
        ----------------------------------------- */

        if ($action === "add_challenge") {

            $mission_id = intval($_POST['mission_id'] ?? 0);
            $challenge_title = trim($_POST['challenge_title'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            $question_text = trim($_POST['question_text'] ?? '');
            $correct_answer = trim($_POST['correct_answer'] ?? '');

            $total_points = intval($_POST['total_points'] ?? 10);
            $passing_score = floatval($_POST['passing_score'] ?? 70);
            $status = $_POST['status'] ?? 'draft';

            $choices = [
                "A" => trim($_POST['choice_a'] ?? ''),
                "B" => trim($_POST['choice_b'] ?? ''),
                "C" => trim($_POST['choice_c'] ?? ''),
                "D" => trim($_POST['choice_d'] ?? '')
            ];

            $choices_json = json_encode($choices);

            if (
                $mission_id <= 0 ||
                $challenge_title === '' ||
                $question_text === '' ||
                $correct_answer === ''
            ) {

                $error = "Please complete all required fields.";

            } elseif (
                $total_points <= 0 ||
                $passing_score < 0 ||
                $passing_score > 100
            ) {

                $error = "Please enter valid points and passing score.";

            } elseif (!in_array($status, ['published', 'draft'], true)) {

                $error = "Invalid challenge status.";

            } elseif (
                empty($choices['A']) ||
                empty($choices['B']) ||
                empty($choices['C']) ||
                empty($choices['D'])
            ) {

                $error = "Please provide all four answer choices.";

            } else {

                try {

                    // Verify that the selected mission exists.
                    $checkMission = $conn->prepare("
                        SELECT mission_id
                        FROM missions
                        WHERE mission_id = ?
                    ");

                    $checkMission->bind_param("i", $mission_id);
                    $checkMission->execute();

                    $missionExists = $checkMission->get_result()->num_rows > 0;

                    $checkMission->close();

                    if (!$missionExists) {

                        $error = "The selected mission does not exist.";

                    } else {

                        // Get the next challenge order.
                        $orderStmt = $conn->prepare("
                            SELECT COALESCE(MAX(challenge_order), 0) + 1 AS next_order
                            FROM challenges
                            WHERE mission_id = ?
                        ");

                        $orderStmt->bind_param("i", $mission_id);
                        $orderStmt->execute();

                        $challenge_order = (int)$orderStmt->get_result()
                            ->fetch_assoc()['next_order'];

                        $orderStmt->close();

                        // Insert challenge.
                        $stmt = $conn->prepare("
                            INSERT INTO challenges (
                                mission_id,
                                challenge_title,
                                instructions,
                                question_type,
                                question_text,
                                choices_json,
                                correct_answer,
                                total_points,
                                passing_score,
                                challenge_order,
                                status
                            )
                            VALUES (?, ?, ?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?)
                        ");

                        $stmt->bind_param(
                            "issssssidis",
                            $mission_id,
                            $challenge_title,
                            $instructions,
                            $question_text,
                            $choices_json,
                            $correct_answer,
                            $total_points,
                            $passing_score,
                            $challenge_order,
                            $status
                        );

                        $stmt->execute();
                        $stmt->close();

                        $success = "Challenge created successfully!";
                    }

                } catch (mysqli_sql_exception $e) {

                    $error = "Unable to create challenge. Please check your database structure.";

                }
            }
        }

        /* -----------------------------------------
           UPDATE CHALLENGE
        ----------------------------------------- */

        elseif ($action === "update_challenge") {

            $challenge_id = intval($_POST['challenge_id'] ?? 0);
            $mission_id = intval($_POST['mission_id'] ?? 0);

            $challenge_title = trim($_POST['challenge_title'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');
            $question_text = trim($_POST['question_text'] ?? '');
            $correct_answer = trim($_POST['correct_answer'] ?? '');

            $total_points = intval($_POST['total_points'] ?? 10);
            $passing_score = floatval($_POST['passing_score'] ?? 70);
            $status = $_POST['status'] ?? 'draft';

            $choices = [
                "A" => trim($_POST['choice_a'] ?? ''),
                "B" => trim($_POST['choice_b'] ?? ''),
                "C" => trim($_POST['choice_c'] ?? ''),
                "D" => trim($_POST['choice_d'] ?? '')
            ];

            $choices_json = json_encode($choices);

            if (
                $challenge_id <= 0 ||
                $mission_id <= 0 ||
                $challenge_title === '' ||
                $question_text === '' ||
                $correct_answer === ''
            ) {

                $error = "Please complete all required fields.";

            } elseif (
                $total_points <= 0 ||
                $passing_score < 0 ||
                $passing_score > 100
            ) {

                $error = "Please enter valid points and passing score.";

            } elseif (!in_array($status, ['published', 'draft'], true)) {

                $error = "Invalid challenge status.";

            } elseif (
                empty($choices['A']) ||
                empty($choices['B']) ||
                empty($choices['C']) ||
                empty($choices['D'])
            ) {

                $error = "Please provide all four answer choices.";

            } else {

                try {

                    $stmt = $conn->prepare("
                        UPDATE challenges
                        SET
                            mission_id = ?,
                            challenge_title = ?,
                            instructions = ?,
                            question_type = 'multiple_choice',
                            question_text = ?,
                            choices_json = ?,
                            correct_answer = ?,
                            total_points = ?,
                            passing_score = ?,
                            status = ?
                        WHERE challenge_id = ?
                    ");

                    $stmt->bind_param(
                        "issssssidsi",
                        $mission_id,
                        $challenge_title,
                        $instructions,
                        $question_text,
                        $choices_json,
                        $correct_answer,
                        $total_points,
                        $passing_score,
                        $status,
                        $challenge_id
                    );

                    $stmt->execute();

                    if ($stmt->affected_rows >= 0) {
                        $success = "Challenge updated successfully!";
                    }

                    $stmt->close();

                } catch (mysqli_sql_exception $e) {

                    $error = "Unable to update challenge. Please check your database structure.";

                }
            }
        }

        /* -----------------------------------------
           DELETE CHALLENGE
        ----------------------------------------- */

        elseif ($action === "delete_challenge") {

            $challenge_id = intval($_POST['challenge_id'] ?? 0);

            if ($challenge_id <= 0) {

                $error = "Invalid challenge selected.";

            } else {

                try {

                    $stmt = $conn->prepare("
                        DELETE FROM challenges
                        WHERE challenge_id = ?
                    ");

                    $stmt->bind_param("i", $challenge_id);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $success = "Challenge deleted successfully!";
                    } else {
                        $error = "Challenge not found.";
                    }

                    $stmt->close();

                } catch (mysqli_sql_exception $e) {

                    $error = "This challenge cannot be deleted because it may have existing student attempts or related records.";

                }
            }
        }
    }
}

/* -------------------------------------------------
   FETCH MISSIONS
------------------------------------------------- */

$missionsList = [];

try {

    $missionsRes = $conn->query("
        SELECT
            m.mission_id,
            m.mission_title,
            modTable.module_title,
            c.course_title
        FROM missions m
        INNER JOIN modules modTable
            ON m.module_id = modTable.module_id
        INNER JOIN courses c
            ON modTable.course_id = c.course_id
        ORDER BY
            c.course_id ASC,
            modTable.module_id ASC,
            m.mission_order ASC
    ");

    while ($row = $missionsRes->fetch_assoc()) {
        $missionsList[] = $row;
    }

} catch (mysqli_sql_exception $e) {

    $error = "Unable to load missions. Check the course, module, and mission relationships in your database.";

}

/* -------------------------------------------------
   FETCH CHALLENGES
------------------------------------------------- */

$selectedStatus = $_GET['status'] ?? 'all';

if (!in_array($selectedStatus, ['all', 'published', 'draft'], true)) {
    $selectedStatus = 'all';
}

$sql = "
    SELECT
        ch.*,
        m.mission_title,
        c.course_title,
        modTable.module_title
    FROM challenges ch
    INNER JOIN missions m
        ON ch.mission_id = m.mission_id
    INNER JOIN modules modTable
        ON m.module_id = modTable.module_id
    INNER JOIN courses c
        ON modTable.course_id = c.course_id
";

if ($selectedStatus !== 'all') {
    $sql .= " WHERE ch.status = ?";
}

$sql .= " ORDER BY ch.challenge_id DESC";

$challengesList = [];

try {

    $stmt = $conn->prepare($sql);

    if ($selectedStatus !== 'all') {
        $stmt->bind_param("s", $selectedStatus);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $challengesList[] = $row;
    }

    $stmt->close();

} catch (mysqli_sql_exception $e) {

    $error = "Unable to load challenges. Please check your challenges table.";

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Challenges | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Challenges">

    <main class="page-content">

        <!-- PAGE HEADING -->

        <section class="page-heading split-heading">

            <div>
                <p class="eyebrow">ASSESSMENTS</p>

                <h1>Challenges</h1>

                <p class="muted">
                    Manage questions, correct answers, and scoring.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                onclick="document.getElementById('challengeModal').showModal()"
            >
                ＋ Create Challenge
            </button>

        </section>


        <!-- MESSAGES -->

        <?php if ($error !== ""): ?>

            <div class="error-message" style="margin-bottom:20px; padding:12px; background:#FFF0F0; color:#B42318; border:1px solid #F5C2C0; border-radius:7px; font-size:13px;">
                <?= clean($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="success-message" style="margin-bottom:20px; padding:12px; background:#F0FFF0; color:#188423; border:1px solid #C2F5C0; border-radius:7px; font-size:13px;">
                <?= clean($success) ?>
            </div>

        <?php endif; ?>


        <!-- TOOLBAR -->

        <form method="GET" action="" class="toolbar">

            <label class="search-box">
                ⌕

                <input
                    type="search"
                    id="challengeSearch"
                    placeholder="Search challenges..."
                >
            </label>

            <select
                name="status"
                onchange="this.form.submit()"
            >

                <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>
                    All statuses
                </option>

                <option value="published" <?= $selectedStatus === 'published' ? 'selected' : '' ?>>
                    Published
                </option>

                <option value="draft" <?= $selectedStatus === 'draft' ? 'selected' : '' ?>>
                    Draft
                </option>

            </select>

        </form>


        <!-- CHALLENGES TABLE -->

        <section class="panel table-panel">

            <div class="table-wrap" style="overflow-x:auto;">

                <table id="challengesTable">

                    <thead>

                        <tr>
                            <th>Challenge</th>
                            <th>Course / Mission</th>
                            <th>Points</th>
                            <th>Passing Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($challengesList) > 0): ?>

                        <?php foreach ($challengesList as $ch): ?>

                            <?php
                                $choices = json_decode($ch['choices_json'] ?? '{}', true);

                                if (!is_array($choices)) {
                                    $choices = [];
                                }

                                $challengeData = [
                                    'challenge_id' => $ch['challenge_id'],
                                    'mission_id' => $ch['mission_id'],
                                    'challenge_title' => $ch['challenge_title'],
                                    'instructions' => $ch['instructions'] ?? '',
                                    'question_text' => $ch['question_text'] ?? '',
                                    'correct_answer' => $ch['correct_answer'] ?? '',
                                    'total_points' => $ch['total_points'] ?? 10,
                                    'passing_score' => $ch['passing_score'] ?? 70,
                                    'status' => $ch['status'],
                                    'choice_a' => $choices['A'] ?? '',
                                    'choice_b' => $choices['B'] ?? '',
                                    'choice_c' => $choices['C'] ?? '',
                                    'choice_d' => $choices['D'] ?? ''
                                ];

                                $challengeJson = htmlspecialchars(
                                    json_encode($challengeData),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>

                            <tr class="challenge-row">

                                <td>

                                    <b>
                                        <?= clean($ch['challenge_title']) ?>
                                    </b>

                                    <small>
                                        <?= clean($ch['instructions'] ?: 'No instructions provided.') ?>
                                    </small>

                                </td>

                                <td>

                                    <?= clean($ch['course_title']) ?>

                                    /

                                    <?= clean($ch['mission_title']) ?>

                                </td>

                                <td>
                                    <?= intval($ch['total_points'] ?? 0) ?> pts
                                </td>

                                <td>
                                    <?= floatval($ch['passing_score']) ?>%
                                </td>

                                <td>

                                    <span class="status <?= clean(strtolower($ch['status'])) ?>">
                                        <?= clean(ucfirst($ch['status'])) ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="table-actions">

                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline"
                                            data-edit="<?= $challengeJson ?>"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-small btn-outline"
                                            data-preview="<?= $challengeJson ?>"
                                        >
                                            Preview
                                        </button>

                                        <form
                                            method="POST"
                                            action=""
                                            onsubmit="return confirm('Are you sure you want to delete this challenge?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= clean($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete_challenge"
                                            >

                                            <input
                                                type="hidden"
                                                name="challenge_id"
                                                value="<?= intval($ch['challenge_id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-small btn-outline"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center; color:#777; padding:30px;">
                                No challenges found. Click "Create Challenge" to add one.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- CREATE CHALLENGE MODAL -->

        <dialog class="modal" id="challengeModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">

                <input type="hidden" name="action" value="add_challenge">

                <div class="modal-heading">

                    <h2>Create Challenge</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>


                <label>
                    Challenge Title

                    <input
                        type="text"
                        name="challenge_title"
                        required
                        placeholder="Enter challenge title"
                    >
                </label>


                <label>
                    Instructions

                    <textarea
                        name="instructions"
                        rows="3"
                        placeholder="Explain the rules of the challenge"
                    ></textarea>
                </label>


                <label>
                    Related Mission

                    <select name="mission_id" required>

                        <option value="">Select a mission</option>

                        <?php foreach ($missionsList as $m): ?>

                            <option value="<?= intval($m['mission_id']) ?>">

                                <?= clean($m['course_title']) ?>
                                →
                                <?= clean($m['module_title']) ?>
                                →
                                <?= clean($m['mission_title']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>


                <div class="form-grid">

                    <label>
                        Passing Score (%)

                        <input
                            type="number"
                            name="passing_score"
                            min="0"
                            max="100"
                            value="70"
                            required
                        >
                    </label>


                    <label>
                        Points

                        <input
                            type="number"
                            name="total_points"
                            min="1"
                            value="10"
                            required
                        >
                    </label>

                </div>


                <label>
                    Question Text

                    <textarea
                        name="question_text"
                        rows="3"
                        required
                        placeholder="Type your main question here"
                    ></textarea>
                </label>


                <label>
                    Answer Choice A

                    <input
                        type="text"
                        name="choice_a"
                        required
                        placeholder="Choice A"
                    >
                </label>


                <label>
                    Answer Choice B

                    <input
                        type="text"
                        name="choice_b"
                        required
                        placeholder="Choice B"
                    >
                </label>


                <label>
                    Answer Choice C

                    <input
                        type="text"
                        name="choice_c"
                        required
                        placeholder="Choice C"
                    >
                </label>


                <label>
                    Answer Choice D

                    <input
                        type="text"
                        name="choice_d"
                        required
                        placeholder="Choice D"
                    >
                </label>


                <label>
                    Correct Answer

                    <select name="correct_answer" required>

                        <option value="">Select the correct answer</option>

                        <option value="A">Choice A</option>
                        <option value="B">Choice B</option>
                        <option value="C">Choice C</option>
                        <option value="D">Choice D</option>

                    </select>

                </label>


                <label>
                    Status

                    <select name="status" required>

                        <option value="draft">Draft</option>
                        <option value="published">Published</option>

                    </select>

                </label>


                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="this.closest('dialog').close()"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Challenge
                    </button>

                </div>

            </form>

        </dialog>


        <!-- EDIT CHALLENGE MODAL -->

        <dialog class="modal" id="editChallengeModal">

            <form method="POST" action="" class="modal-card">

                <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">

                <input type="hidden" name="action" value="update_challenge">

                <input type="hidden" name="challenge_id" id="edit_challenge_id">


                <div class="modal-heading">

                    <h2>Edit Challenge</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>


                <label>
                    Challenge Title

                    <input
                        type="text"
                        name="challenge_title"
                        id="edit_challenge_title"
                        required
                    >
                </label>


                <label>
                    Instructions

                    <textarea
                        name="instructions"
                        id="edit_instructions"
                        rows="3"
                    ></textarea>
                </label>


                <label>
                    Related Mission

                    <select
                        name="mission_id"
                        id="edit_mission_id"
                        required
                    >

                        <?php foreach ($missionsList as $m): ?>

                            <option value="<?= intval($m['mission_id']) ?>">

                                <?= clean($m['course_title']) ?>
                                →
                                <?= clean($m['module_title']) ?>
                                →
                                <?= clean($m['mission_title']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>


                <div class="form-grid">

                    <label>
                        Passing Score (%)

                        <input
                            type="number"
                            name="passing_score"
                            id="edit_passing_score"
                            min="0"
                            max="100"
                            required
                        >
                    </label>


                    <label>
                        Points

                        <input
                            type="number"
                            name="total_points"
                            id="edit_total_points"
                            min="1"
                            required
                        >
                    </label>

                </div>


                <label>
                    Question Text

                    <textarea
                        name="question_text"
                        id="edit_question_text"
                        rows="3"
                        required
                    ></textarea>
                </label>


                <label>
                    Answer Choice A

                    <input
                        type="text"
                        name="choice_a"
                        id="edit_choice_a"
                        required
                    >
                </label>


                <label>
                    Answer Choice B

                    <input
                        type="text"
                        name="choice_b"
                        id="edit_choice_b"
                        required
                    >
                </label>


                <label>
                    Answer Choice C

                    <input
                        type="text"
                        name="choice_c"
                        id="edit_choice_c"
                        required
                    >
                </label>


                <label>
                    Answer Choice D

                    <input
                        type="text"
                        name="choice_d"
                        id="edit_choice_d"
                        required
                    >
                </label>


                <label>
                    Correct Answer

                    <select
                        name="correct_answer"
                        id="edit_correct_answer"
                        required
                    >

                        <option value="A">Choice A</option>
                        <option value="B">Choice B</option>
                        <option value="C">Choice C</option>
                        <option value="D">Choice D</option>

                    </select>

                </label>


                <label>
                    Status

                    <select
                        name="status"
                        id="edit_status"
                        required
                    >

                        <option value="draft">Draft</option>
                        <option value="published">Published</option>

                    </select>

                </label>


                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="this.closest('dialog').close()"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Update Challenge
                    </button>

                </div>

            </form>

        </dialog>


        <!-- PREVIEW CHALLENGE MODAL -->

        <dialog class="modal" id="previewChallengeModal">

            <div class="modal-card">

                <div class="modal-heading">

                    <h2>Challenge Preview</h2>

                    <button
                        type="button"
                        class="icon-btn"
                        onclick="this.closest('dialog').close()"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>

                <h3 id="preview_title"></h3>

                <p id="preview_instructions"></p>

                <hr>

                <p id="preview_question"></p>

                <div id="preview_choices"></div>

                <p>
                    <strong>Correct Answer:</strong>
                    <span id="preview_answer"></span>
                </p>

                <p>
                    <strong>Points:</strong>
                    <span id="preview_points"></span>
                </p>

                <p>
                    <strong>Passing Score:</strong>
                    <span id="preview_passing"></span>%
                </p>

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="this.closest('dialog').close()"
                    >
                        Close
                    </button>

                </div>

            </div>

        </dialog>


        <footer class="page-footer">
            © 2026 EduQuest · Admin Portal
        </footer>

    </main>

</div>


<div class="toast" id="toast" role="status" aria-live="polite"></div>


<script>

/* -------------------------------------------------
   SEARCH CHALLENGES
------------------------------------------------- */

const challengeSearch = document.getElementById("challengeSearch");

if (challengeSearch) {

    challengeSearch.addEventListener("input", function () {

        const searchValue = this.value.toLowerCase();

        document.querySelectorAll(".challenge-row").forEach(function (row) {

            row.style.display = row.textContent.toLowerCase().includes(searchValue)
                ? ""
                : "none";

        });

    });

}


/* -------------------------------------------------
   EDIT CHALLENGE
------------------------------------------------- */

document.querySelectorAll("[data-edit]").forEach(function (button) {

    button.addEventListener("click", function () {

        const data = JSON.parse(this.dataset.edit);

        document.getElementById("edit_challenge_id").value = data.challenge_id;
        document.getElementById("edit_challenge_title").value = data.challenge_title;
        document.getElementById("edit_instructions").value = data.instructions;
        document.getElementById("edit_mission_id").value = data.mission_id;

        document.getElementById("edit_question_text").value = data.question_text;

        document.getElementById("edit_choice_a").value = data.choice_a;
        document.getElementById("edit_choice_b").value = data.choice_b;
        document.getElementById("edit_choice_c").value = data.choice_c;
        document.getElementById("edit_choice_d").value = data.choice_d;

        document.getElementById("edit_correct_answer").value = data.correct_answer;

        document.getElementById("edit_total_points").value = data.total_points;
        document.getElementById("edit_passing_score").value = data.passing_score;

        document.getElementById("edit_status").value = data.status;

        document.getElementById("editChallengeModal").showModal();

    });

});


/* -------------------------------------------------
   PREVIEW CHALLENGE
------------------------------------------------- */

document.querySelectorAll("[data-preview]").forEach(function (button) {

    button.addEventListener("click", function () {

        const data = JSON.parse(this.dataset.preview);

        document.getElementById("preview_title").textContent = data.challenge_title;

        document.getElementById("preview_instructions").textContent =
            data.instructions || "No instructions provided.";

        document.getElementById("preview_question").textContent = data.question_text;

        document.getElementById("preview_answer").textContent =
            "Choice " + data.correct_answer;

        document.getElementById("preview_points").textContent = data.total_points;

        document.getElementById("preview_passing").textContent = data.passing_score;

        const choicesContainer = document.getElementById("preview_choices");

        choicesContainer.innerHTML = "";

        const choices = {
            A: data.choice_a,
            B: data.choice_b,
            C: data.choice_c,
            D: data.choice_d
        };

        Object.keys(choices).forEach(function (key) {

            const paragraph = document.createElement("p");

            paragraph.textContent = key + ". " + choices[key];

            choicesContainer.appendChild(paragraph);

        });

        document.getElementById("previewChallengeModal").showModal();

    });

});

</script>

</body>
</html>
