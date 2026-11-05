
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db_connect.php";
require_once "admin_auth.php";

/* -------------------------------------------------
   HELPER FUNCTIONS
------------------------------------------------- */

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (empty($date)) {
        return 'Not available';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date("M j, Y", $timestamp) : 'Not available';
}

function redirectToStudents($status, $search = '', $courseId = 0) {
    $url = "AdminStudents.php?status=" . urlencode($status);

    if ($search !== '') {
        $url .= "&search=" . urlencode($search);
    }

    if ($courseId > 0) {
        $url .= "&course_id=" . (int)$courseId;
    }

    header("Location: " . $url);
    exit;
}

/* -------------------------------------------------
   CSRF TOKEN
------------------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* -------------------------------------------------
   HANDLE STUDENT ACTIONS
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("Invalid request. Please refresh the page and try again.");
    }

    $action = $_POST['action'] ?? '';
    $studentId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    $currentStatus = $_POST['current_status'] ?? 'active';
    $searchValue = trim($_POST['search'] ?? '');
    $courseValue = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

    if ($studentId <= 0) {
        $_SESSION['student_message'] = "Invalid student account.";
        $_SESSION['student_message_type'] = "error";

        redirectToStudents($currentStatus, $searchValue, $courseValue);
    }


    $checkStmt = $conn->prepare("
        SELECT user_id, account_status
        FROM users
        WHERE user_id = ?
        AND role = 'student'
        LIMIT 1
    ");

    if (!$checkStmt) {
        die("Unable to verify student account.");
    }

    $checkStmt->bind_param("i", $studentId);
    $checkStmt->execute();

    $studentResult = $checkStmt->get_result();
    $studentAccount = $studentResult->fetch_assoc();

    $checkStmt->close();

    if (!$studentAccount) {
        $_SESSION['student_message'] = "Student account not found.";
        $_SESSION['student_message_type'] = "error";

        redirectToStudents($currentStatus, $searchValue, $courseValue);
    }

    $newStatus = '';

    if ($action === 'soft_delete') {

        if ($studentAccount['account_status'] === 'deleted') {
            $_SESSION['student_message'] = "This student is already deleted.";
            $_SESSION['student_message_type'] = "error";

            redirectToStudents($currentStatus, $searchValue, $courseValue);
        }

        $newStatus = 'deleted';

    } elseif ($action === 'restore') {

        if ($studentAccount['account_status'] !== 'deleted') {
            $_SESSION['student_message'] = "This student account is not deleted.";
            $_SESSION['student_message_type'] = "error";

            redirectToStudents($currentStatus, $searchValue, $courseValue);
        }

        $newStatus = 'active';

    } elseif ($action === 'suspend') {

        if ($studentAccount['account_status'] === 'deleted') {
            $_SESSION['student_message'] = "Restore this account before suspending it.";
            $_SESSION['student_message_type'] = "error";

            redirectToStudents($currentStatus, $searchValue, $courseValue);
        }

        $newStatus = 'suspended';

    } elseif ($action === 'activate') {

        if ($studentAccount['account_status'] === 'deleted') {
            $_SESSION['student_message'] = "Restore this account before activating it.";
            $_SESSION['student_message_type'] = "error";

            redirectToStudents($currentStatus, $searchValue, $courseValue);
        }

        $newStatus = 'active';

    } else {

        $_SESSION['student_message'] = "Invalid student action.";
        $_SESSION['student_message_type'] = "error";

        redirectToStudents($currentStatus, $searchValue, $courseValue);
    }

    /* Update account status only.
       The student account and learning records are preserved. */

    $updateStmt = $conn->prepare("
        UPDATE users
        SET account_status = ?
        WHERE user_id = ?
        AND role = 'student'
    ");

    if (!$updateStmt) {
        die("Unable to prepare student account update.");
    }

    $updateStmt->bind_param("si", $newStatus, $studentId);

    if ($updateStmt->execute()) {

        if ($action === 'soft_delete') {
            $_SESSION['student_message'] =
                "Student account was successfully moved to Deleted Students.";

        } elseif ($action === 'restore') {
            $_SESSION['student_message'] =
                "Student account was successfully restored.";

        } elseif ($action === 'suspend') {
            $_SESSION['student_message'] =
                "Student account was successfully suspended.";

        } else {
            $_SESSION['student_message'] =
                "Student account was successfully activated.";
        }

        $_SESSION['student_message_type'] = "success";

    } else {

        $_SESSION['student_message'] = "Unable to update student account.";
        $_SESSION['student_message_type'] = "error";
    }

    $updateStmt->close();

    redirectToStudents($currentStatus, $searchValue, $courseValue);
}

/* -------------------------------------------------
   FETCH STATISTICS
------------------------------------------------- */

$totalStudents = 0;

$result = $conn->query("
    SELECT COUNT(*) AS count
    FROM users
    WHERE role = 'student'
    AND account_status != 'deleted'
");

if ($result) {
    $totalStudents = (int)$result->fetch_assoc()['count'];
}

/* Active learners */
$activeLearners = 0;

$result = $conn->query("
    SELECT COUNT(*) AS count
    FROM users
    WHERE role = 'student'
    AND account_status = 'active'
");

if ($result) {
    $activeLearners = (int)$result->fetch_assoc()['count'];
}

/* Average progress of non-deleted students */
$avgProgress = 0;

$result = $conn->query("
    SELECT AVG(p.progress_percent) AS avg_rate
    FROM user_course_progress p
    INNER JOIN users u ON p.user_id = u.user_id
    WHERE u.role = 'student'
    AND u.account_status != 'deleted'
");

if ($result) {
    $row = $result->fetch_assoc();
    $avgProgress = (int)round((float)($row['avg_rate'] ?? 0));
}

/* Deleted students count */
$deletedStudents = 0;

$result = $conn->query("
    SELECT COUNT(*) AS count
    FROM users
    WHERE role = 'student'
    AND account_status = 'deleted'
");

if ($result) {
    $deletedStudents = (int)$result->fetch_assoc()['count'];
}

/* -------------------------------------------------
   FETCH COURSES
------------------------------------------------- */

$coursesList = [];

$coursesRes = $conn->query("
    SELECT course_id, course_title
    FROM courses
    ORDER BY course_number ASC
");

if ($coursesRes) {
    while ($course = $coursesRes->fetch_assoc()) {
        $coursesList[] = $course;
    }
}

/* -------------------------------------------------
   READ FILTERS
------------------------------------------------- */

$allowedStatuses = ['all', 'active', 'suspended', 'deleted'];

$selectedStatus = $_GET['status'] ?? 'active';

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'active';
}

$selectedCourseId = isset($_GET['course_id'])
    ? max(0, (int)$_GET['course_id'])
    : 0;

$search = trim($_GET['search'] ?? '');

if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

/* -------------------------------------------------
   FETCH STUDENTS
------------------------------------------------- */

$sql = "
    SELECT
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.created_at,
        u.last_login,
        u.account_status,

        COUNT(DISTINCT p.course_id) AS courses_started,

        COALESCE(AVG(p.progress_percent), 0) AS avg_user_progress

    FROM users u

    LEFT JOIN user_course_progress p
        ON u.user_id = p.user_id

    WHERE u.role = 'student'
";

$params = [];
$types = "";

/* Status filter */
if ($selectedStatus !== 'all') {
    $sql .= " AND u.account_status = ?";
    $params[] = $selectedStatus;
    $types .= "s";
}

/* Course filter */
if ($selectedCourseId > 0) {
    $sql .= "
        AND EXISTS (
            SELECT 1
            FROM user_course_progress cp
            WHERE cp.user_id = u.user_id
            AND cp.course_id = ?
        )
    ";

    $params[] = $selectedCourseId;
    $types .= "i";
}

/* Search filter */
if ($search !== '') {
    $sql .= "
        AND (
            u.full_name LIKE ?
            OR u.username LIKE ?
            OR u.email LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types .= "sss";
}

$sql .= "
    GROUP BY
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.created_at,
        u.last_login,
        u.account_status

    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load student records.");
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$studentsResult = $stmt->get_result();
$studentCount = $studentsResult->num_rows;

/* Flash message */
$studentMessage = $_SESSION['student_message'] ?? '';
$studentMessageType = $_SESSION['student_message_type'] ?? '';

unset($_SESSION['student_message']);
unset($_SESSION['student_message_type']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">
    <script src="admin_layout.js" defer></script>
        <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>
</head>

<body>

<div id="admin-app" data-page="Students">

    <main class="page-content">

        <!-- PAGE HEADING -->
        <section class="page-heading">

            <p class="eyebrow">LEARNER MANAGEMENT</p>

            <h1>Students</h1>

            <p class="muted">
                Manage student accounts, monitor learning progress,
                and restore previously deleted accounts.
            </p>

        </section>

        <!-- STATISTICS -->
        <section class="stats-grid compact-stats">

            <article class="stat-card">
                <p>Total Students</p>
                <h2><?= $totalStudents ?></h2>
            </article>

            <article class="stat-card">
                <p>Active Learners</p>
                <h2><?= $activeLearners ?></h2>
            </article>

            <article class="stat-card">
                <p>Average Progress</p>
                <h2><?= $avgProgress ?>%</h2>
            </article>

        </section>

        <!-- NOTIFICATION -->
        <?php if ($studentMessage !== ''): ?>

            <div class="student-message <?= e($studentMessageType) ?>"
                 style="padding:14px 18px; margin-bottom:18px; border-radius:8px;
                 background:<?= $studentMessageType === 'success' ? '#e5f5e9' : '#fce8e8' ?>;
                 color:<?= $studentMessageType === 'success' ? '#236b37' : '#a12626' ?>;">

                <?= e($studentMessage) ?>

            </div>

        <?php endif; ?>

        <!-- STATUS FILTERS -->
        <section class="student-status-filters"
                 style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">

            <a href="AdminStudents.php?status=active"
               class="btn btn-small <?= $selectedStatus === 'active' ? '' : 'btn-outline' ?>">
                Active
            </a>

            <a href="AdminStudents.php?status=suspended"
               class="btn btn-small <?= $selectedStatus === 'suspended' ? '' : 'btn-outline' ?>">
                Suspended
            </a>

            <a href="AdminStudents.php?status=all"
               class="btn btn-small <?= $selectedStatus === 'all' ? '' : 'btn-outline' ?>">
                All Students
            </a>

        </section>

        <!-- SEARCH AND COURSE FILTER -->
        <form method="GET" action="AdminStudents.php"
              class="toolbar"
              style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">

            <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">

            <label class="search-box">
                ⌕
                <input
                    type="search"
                    name="search"
                    placeholder="Search by name, username, or email..."
                    value="<?= e($search) ?>"
                >
            </label>

            <select name="course_id" onchange="this.form.submit()">

                <option value="0">All courses</option>

                <?php foreach ($coursesList as $course): ?>

                    <option
                        value="<?= (int)$course['course_id'] ?>"
                        <?= ((int)$course['course_id'] === $selectedCourseId) ? 'selected' : '' ?>
                    >
                        <?= e($course['course_title']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit" class="btn btn-small btn-outline">
                Search
            </button>

            <a href="AdminStudents.php?status=<?= e($selectedStatus) ?>"
               class="btn btn-small btn-outline">
                Clear Filters
            </a>

        </form>

        <!-- STUDENTS TABLE -->
        <section class="panel table-panel">

            <div style="padding:18px 20px;">
                <h2 style="margin:0;">
                    <?php
                    if ($selectedStatus === 'active') {
                        echo "Active Students";
                    } elseif ($selectedStatus === 'suspended') {
                        echo "Suspended Students";
                    } elseif ($selectedStatus === 'deleted') {
                        echo "Deleted Students";
                    } else {
                        echo "All Students";
                    }
                    ?>
                </h2>

                <p class="muted" style="margin-top:5px;">
                    <?= $studentCount ?> student account(s) found.
                </p>
            </div>

            <div class="table-wrap" style="overflow-x:auto;">

                <table id="studentsTable">

                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date Registered</th>
                            <th>Courses Started</th>
                            <th>Overall Progress</th>
                            <th>Last Activity</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($studentCount > 0): ?>

                        <?php while ($student = $studentsResult->fetch_assoc()): ?>

                            <?php
                            $progress = (float)$student['avg_user_progress'];
                            $progress = max(0, min(100, $progress));
                            $progress = (int)round($progress);

                            $lastActive = !empty($student['last_login'])
                                ? formatDate($student['last_login'])
                                : 'Never logged in';

                            $accountStatus = $student['account_status'];
                            ?>

                            <tr>

                                <!-- STUDENT -->
                                <td>
                                    <b><?= e($student['full_name']) ?></b>

                                    <small>
                                        <?= e($student['email']) ?>
                                    </small>

                                    <small>
                                        @<?= e($student['username']) ?>
                                    </small>
                                </td>

                                <!-- REGISTRATION DATE -->
                                <td>
                                    <?= e(formatDate($student['created_at'])) ?>
                                </td>

                                <!-- COURSES -->
                                <td>
                                    <?= (int)$student['courses_started'] ?>
                                </td>

                                <!-- PROGRESS -->
                                <td>
                                    <div class="table-progress">

                                        <div class="bar-track">
                                            <div style="width:<?= $progress ?>%"></div>
                                        </div>

                                        <span><?= $progress ?>%</span>

                                    </div>
                                </td>

                                <!-- LAST ACTIVITY -->
                                <td>
                                    <?= e($lastActive) ?>
                                </td>

                                <!-- ACCOUNT STATUS -->
                                <td>

                                    <?php if ($accountStatus === 'active'): ?>

                                        <span class="status-badge"
                                              style="color:#27834a;">
                                            Active
                                        </span>

                                    <?php elseif ($accountStatus === 'suspended'): ?>

                                        <span class="status-badge"
                                              style="color:#bd8623;">
                                            Suspended
                                        </span>

                                    <?php elseif ($accountStatus === 'deleted'): ?>

                                        <span class="status-badge"
                                              style="color:#c44242;">
                                            Deleted
                                        </span>

                                    <?php else: ?>

                                        <?= e(ucfirst($accountStatus)) ?>

                                    <?php endif; ?>

                                </td>

                                <!-- ACTIONS -->
                                <td>

                                    <div style="display:flex; gap:7px; flex-wrap:wrap;">

                                        <a
                                            class="btn btn-small btn-outline"
                                            href="AdminStudentDetails.php?user_id=<?= (int)$student['user_id'] ?>"
                                        >
                                            View Details
                                        </a>

                                        <?php if ($accountStatus === 'deleted'): ?>

                                            <!-- RESTORE -->
                                            <form method="POST"
                                                  action="AdminStudents.php"
                                                  onsubmit="return confirm('Restore this student account?');">

                                                <input type="hidden" name="csrf_token"
                                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input type="hidden" name="user_id"
                                                       value="<?= (int)$student['user_id'] ?>">

                                                <input type="hidden" name="action"
                                                       value="restore">

                                                <input type="hidden" name="current_status"
                                                       value="<?= e($selectedStatus) ?>">

                                                <input type="hidden" name="search"
                                                       value="<?= e($search) ?>">

                                                <input type="hidden" name="course_id"
                                                       value="<?= $selectedCourseId ?>">

                                                <button type="submit"
                                                        class="btn btn-small">
                                                    Restore
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <?php if ($accountStatus === 'active'): ?>

                                                <!-- SUSPEND -->
                                                <form method="POST"
                                                      action="AdminStudents.php"
                                                      onsubmit="return confirm('Suspend this student account?');">

                                                    <input type="hidden" name="csrf_token"
                                                           value="<?= e($_SESSION['csrf_token']) ?>">

                                                    <input type="hidden" name="user_id"
                                                           value="<?= (int)$student['user_id'] ?>">

                                                    <input type="hidden" name="action"
                                                           value="suspend">

                                                    <input type="hidden" name="current_status"
                                                           value="<?= e($selectedStatus) ?>">

                                                    <input type="hidden" name="search"
                                                           value="<?= e($search) ?>">

                                                    <input type="hidden" name="course_id"
                                                           value="<?= $selectedCourseId ?>">

                                                    <button type="submit"
                                                            class="btn btn-small btn-outline">
                                                        Suspend
                                                    </button>

                                                </form>

                                            <?php elseif ($accountStatus === 'suspended'): ?>

                                                <!-- ACTIVATE -->
                                                <form method="POST"
                                                      action="AdminStudents.php"
                                                      onsubmit="return confirm('Activate this student account?');">

                                                    <input type="hidden" name="csrf_token"
                                                           value="<?= e($_SESSION['csrf_token']) ?>">

                                                    <input type="hidden" name="user_id"
                                                           value="<?= (int)$student['user_id'] ?>">

                                                    <input type="hidden" name="action"
                                                           value="activate">

                                                    <input type="hidden" name="current_status"
                                                           value="<?= e($selectedStatus) ?>">

                                                    <input type="hidden" name="search"
                                                           value="<?= e($search) ?>">

                                                    <input type="hidden" name="course_id"
                                                           value="<?= $selectedCourseId ?>">

                                                    <button type="submit"
                                                            class="btn btn-small">
                                                        Activate
                                                    </button>

                                                </form>

                                            <?php endif; ?>

    

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7"
                                style="text-align:center; padding:30px; color:#777;">

                                No student accounts found.

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <!-- FOOTER -->
        <footer class="page-footer">
            © 2026 EduQuest · Admin Portal
        </footer>

    </main>

</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

</body>
</html>

<?php
$stmt->close();
?>
