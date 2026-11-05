
<?php
require_once "db_connect.php";
require_once "admin_auth.php";

$message = "";
$editCourse = null;


// HANDLE COURSE ACTIONS
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    // ADD COURSE
    if ($action === "add") {

        $title = trim($_POST["course_title"] ?? "");
        $description = trim($_POST["course_description"] ?? "");
        $number = (int) ($_POST["course_number"] ?? 0);
        $status = $_POST["status"] ?? "draft";

        if (
            $title === "" ||
            $number < 1 ||
            !in_array($status, ["draft", "published"], true)
        ) {
            $message = "Please enter valid course details.";
        } else {

            $stmt = $conn->prepare(
                "INSERT INTO courses
                (course_title, course_description, course_number, status)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssis",
                $title,
                $description,
                $number,
                $status
            );

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: AdminCourses.php?message=added");
                exit;
            } else {
                $message = "Could not add course. Check if the course number already exists.";
            }

            $stmt->close();
        }
    }


    // UPDATE COURSE
    elseif ($action === "update") {

        $id = (int) ($_POST["course_id"] ?? 0);
        $title = trim($_POST["course_title"] ?? "");
        $description = trim($_POST["course_description"] ?? "");
        $number = (int) ($_POST["course_number"] ?? 0);
        $status = $_POST["status"] ?? "draft";

        if (
            $id < 1 ||
            $title === "" ||
            $number < 1 ||
            !in_array(
                $status,
                ["draft", "published", "archived"],
                true
            )
        ) {
            $message = "Please enter valid course details.";
        } else {

            $stmt = $conn->prepare(
                "UPDATE courses
                SET course_title = ?,
                    course_description = ?,
                    course_number = ?,
                    status = ?
                WHERE course_id = ?"
            );

            $stmt->bind_param(
                "ssisi",
                $title,
                $description,
                $number,
                $status,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: AdminCourses.php?message=updated");
                exit;
            } else {
                $message = "Could not update course. Check if the course number already exists.";
            }

            $stmt->close();
        }
    }


    // ARCHIVE COURSE
    elseif ($action === "archive") {

        $id = (int) ($_POST["course_id"] ?? 0);

        if ($id > 0) {

            $stmt = $conn->prepare(
                "UPDATE courses
                SET status = 'archived'
                WHERE course_id = ?"
            );

            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: AdminCourses.php?message=archived");
                exit;
            }

            $stmt->close();
        }

        $message = "Could not archive course.";
    }
}


// SUCCESS MESSAGES
if (isset($_GET["message"])) {

    $messages = [
        "added" => "Course added successfully.",
        "updated" => "Course updated successfully.",
        "archived" => "Course archived successfully."
    ];

    $message = $messages[$_GET["message"]] ?? "";
}


// LOAD COURSE FOR EDITING
if (isset($_GET["edit"])) {

    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM courses WHERE course_id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editCourse = $result->fetch_assoc();

    $stmt->close();
}


// LOAD COURSES AND THEIR CONTENT COUNTS
$sql = "
    SELECT
        c.*,
        (
            SELECT COUNT(*)
            FROM modules m
            WHERE m.course_id = c.course_id
        ) AS module_count,
        (
            SELECT COUNT(*)
            FROM missions mi
            INNER JOIN modules m2
                ON mi.module_id = m2.module_id
            WHERE m2.course_id = c.course_id
        ) AS mission_count
    FROM courses c
    ORDER BY c.course_number ASC
";

$courses = $conn->query($sql);


// ESCAPE OUTPUT
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Courses | EduQuest Admin</title>

    <link rel="stylesheet" href="Admin_styles.css">

    <script src="admin_layout.js" defer></script>
    <script>
    window.adminFullName = <?= json_encode($_SESSION['full_name'] ?? 'Admin') ?>;
    window.adminProfileImage = <?= json_encode($_SESSION['profile_image'] ?? '') ?>;
    </script>

    <!-- ADMIN COURSE FORM STYLES -->
    <style>
        .settings-panel form {
            display: grid;
            gap: 16px;
        }
    
        .settings-panel form label {
            display: grid;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
        }

        .settings-panel form input,
        .settings-panel form textarea,
        .settings-panel form select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            font-family: "Public Sans", Arial, sans-serif;
            font-size: 12px;
            font-weight: 400;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .settings-panel form input::placeholder,
        .settings-panel form textarea::placeholder {
            color: #999999;
            opacity: 1;
            font-weight: 400;
        }

        .settings-panel form input:focus,
        .settings-panel form textarea:focus,
        .settings-panel form select:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(93, 214, 44, 0.12);
        }

        .settings-panel form textarea {
            resize: vertical;
            min-height: 90px;
            line-height: 1.6;
        }

        .settings-panel form label small {
            color: var(--muted);
            font-size: 10px;
            font-weight: 400;
        }

        .settings-panel form .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 5px;
        }

        body.dark-mode .settings-panel form input::placeholder,
        body.dark-mode .settings-panel form textarea::placeholder {
            color: #A3A3A3;
        }

        @media (max-width: 760px) {
            .settings-panel form .form-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

<div id="admin-app" data-page="Courses">

    <main class="page-content">

        <!-- PAGE HEADING -->
        <section class="page-heading split-heading">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>

                <h1>Courses</h1>

                <p class="muted">
                    Add, edit, publish, or archive EduQuest courses.
                </p>
            </div>

            <a
                href="AdminCourses.php"
                class="btn btn-primary"
            >
                + Add Course
            </a>

        </section>


        <!-- SUCCESS OR ERROR MESSAGE -->
        <?php if ($message !== ""): ?>

            <div class="notice" role="status">
                <?= e($message) ?>
            </div>

        <?php endif; ?>


        <!-- ADD OR EDIT COURSE FORM -->
        <section class="panel settings-panel">

            <div class="panel-heading">

                <div>
                    <h2>
                        <?= $editCourse ? "Edit Course" : "Add New Course" ?>
                    </h2>

                    <p class="muted">
                        Enter the course information below.
                    </p>
                </div>

            </div>


            <form method="POST" action="AdminCourses.php">

                <input
                    type="hidden"
                    name="action"
                    value="<?= $editCourse ? "update" : "add" ?>"
                >


                <?php if ($editCourse): ?>

                    <input
                        type="hidden"
                        name="course_id"
                        value="<?= (int) $editCourse["course_id"] ?>"
                    >

                <?php endif; ?>


                <!-- COURSE TITLE -->
                <label for="course_title">

                    Course Title

                    <input
                        type="text"
                        id="course_title"
                        name="course_title"
                        required
                        maxlength="150"
                        placeholder="Enter course title"
                        value="<?= e($editCourse["course_title"] ?? "") ?>"
                    >

                </label>


                <!-- COURSE DESCRIPTION -->
                <label for="course_description">

                    Course Description

                    <textarea
                        id="course_description"
                        name="course_description"
                        rows="3"
                        maxlength="1000"
                        placeholder="Describe what students will learn"
                    ><?= e($editCourse["course_description"] ?? "") ?></textarea>

                </label>


                <!-- COURSE NUMBER -->
                <label for="course_number">

                    Course Number / Order

                    <input
                        type="number"
                        id="course_number"
                        name="course_number"
                        min="1"
                        max="255"
                        required
                        placeholder="Example: 4"
                        value="<?= e($editCourse["course_number"] ?? "") ?>"
                    >

                    <small>
                        Each course must have a different number.
                    </small>

                </label>


                <!-- COURSE STATUS -->
                <label for="status">

                    Status

                    <?php
                    $currentStatus = $editCourse["status"] ?? "draft";
                    ?>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="draft"
                            <?= $currentStatus === "draft" ? "selected" : "" ?>
                        >
                            Draft
                        </option>

                        <option
                            value="published"
                            <?= $currentStatus === "published" ? "selected" : "" ?>
                        >
                            Published
                        </option>

                        <?php if ($editCourse): ?>

                            <option
                                value="archived"
                                <?= $currentStatus === "archived" ? "selected" : "" ?>
                            >
                                Archived
                            </option>

                        <?php endif; ?>

                    </select>

                </label>


                <!-- FORM BUTTONS -->
                <div class="form-actions">

                    <?php if ($editCourse): ?>

                        <a
                            href="AdminCourses.php"
                            class="btn btn-outline"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <?= $editCourse ? "Save Changes" : "Add Course" ?>
                    </button>

                </div>

            </form>

        </section>


        <!-- COURSE LIST -->
        <section class="panel table-panel">

            <div class="panel-heading">

                <div>
                    <h2>All Courses</h2>

                    <p class="muted">
                        Courses saved in the EduQuest database.
                    </p>
                </div>

                <label class="search-box">

                    ⌕

                    <input
                        type="search"
                        placeholder="Search courses..."
                        data-filter="coursesTable"
                        aria-label="Search courses"
                    >

                </label>

            </div>


            <div class="table-wrap">

                <table id="coursesTable">

                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Modules</th>
                            <th>Missions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>


                    <tbody>

                    <?php if ($courses && $courses->num_rows > 0): ?>

                        <?php while ($course = $courses->fetch_assoc()): ?>

                            <tr>

                                <!-- COURSE INFORMATION -->
                                <td>

                                    <b>
                                        <?= str_pad(
                                            (string) $course["course_number"],
                                            2,
                                            "0",
                                            STR_PAD_LEFT
                                        ) ?>

                                        ·

                                        <?= e($course["course_title"]) ?>
                                    </b>

                                    <small>
                                        <?= e($course["course_description"]) ?>
                                    </small>

                                </td>


                                <!-- MODULE COUNT -->
                                <td>
                                    <?= (int) $course["module_count"] ?>
                                </td>


                                <!-- MISSION COUNT -->
                                <td>
                                    <?= (int) $course["mission_count"] ?>
                                </td>


                                <!-- STATUS -->
                                <td>

                                    <span class="status <?= e($course["status"]) ?>">
                                        <?= e(ucfirst($course["status"])) ?>
                                    </span>

                                </td>


                                <!-- ACTIONS -->
                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="AdminCourses.php?edit=<?= (int) $course["course_id"] ?>"
                                        >
                                            Edit
                                        </a>


                                        <?php if ($course["status"] !== "archived"): ?>

                                            <form
                                                method="POST"
                                                action="AdminCourses.php"
                                                onsubmit="return confirm('Archive this course? It will be hidden from the active student course list, but its records will remain in the database.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="archive"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="course_id"
                                                    value="<?= (int) $course["course_id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-small btn-outline"
                                                >
                                                    Archive
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">
                                No courses found. Add your first course above.
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


<!-- TOAST NOTIFICATION -->
<div
    class="toast"
    id="toast"
    role="status"
    aria-live="polite"
></div>


</body>
</html>
