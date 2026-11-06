
<?php
session_start();

require_once __DIR__ . 'db_connect.php';

$conn->set_charset("utf8mb4");

/* =========================================================
   CHECK LOGIN - ALLOW STUDENTS AND ADMINS
========================================================= */

$user = null;

if (!empty($_SESSION['user_id'])) {
    $sessionUserId = (int) $_SESSION['user_id'];

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

    $stmt->bind_param("i", $sessionUserId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

} else {
    $loginValue = $_SESSION['email']
        ?? $_SESSION['user_email']
        ?? $_SESSION['username']
        ?? null;

    if ($loginValue !== null) {
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

        $stmt->bind_param("ss", $loginValue, $loginValue);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        }
    }
}

if (!$user) {
    header("Location: Login.php");
    exit;
}

$userId = (int) $user['user_id'];

$displayName = !empty($user['username'])
    ? $user['username']
    : $user['full_name'];

$firstLetter = strtoupper(substr($displayName, 0, 1));

$userRole = $user['role'];


/* =============================
   PROFILE IMAGE PATH
============================= */

$profileImage = trim((string) ($user["profile_image"] ?? ""));

if (!empty($profileImage)) {

    // Convert Windows directory separators to URL separators.
    $profileImage = str_replace("\\", "/", $profileImage);

    // Keep external image URLs and data URLs unchanged.
    if (
        !preg_match('/^https?:\/\//i', $profileImage) &&
        strpos($profileImage, "data:") !== 0
    ) {
        // Remove leading slashes.
        $profileImage = ltrim($profileImage, "/");

        // Only admin profile images need the Admin_Portal prefix.
        if (strpos($profileImage, "uploads/profile_images/") === 0) {
            $profileImage = "Admin_Portal/" . $profileImage;
        }
    }
}

// GET ALL ACHIEVEMENTS AND THE LOGGED-IN USER'S STATUS

$stmt = $conn->prepare("
    SELECT
        a.achievement_id,
        a.achievement_name,
        a.achievement_description,
        a.icon,
        a.requirement_type,
        a.requirement_value,
        ua.user_achievement_id
    FROM achievements a
    LEFT JOIN user_achievements ua
        ON ua.achievement_id = a.achievement_id
        AND ua.user_id = ?
    ORDER BY a.achievement_id ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$achievements = [];
$totalAchievements = 0;
$achievementsUnlocked = 0;

while ($row = $result->fetch_assoc()) {

    $row['is_unlocked'] = !empty($row['user_achievement_id']);

    if ($row['is_unlocked']) {
        $achievementsUnlocked++;
    }

    $achievements[] = $row;
    $totalAchievements++;
}

$stmt->close();


// FETCH UNREAD NOTIFICATIONS COUNT
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

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $notificationCount = $stmt->get_result()->fetch_assoc();
    $unreadNotifications = (int) ($notificationCount['total'] ?? 0);

    $stmt->close();
}


// FETCH LATEST NOTIFICATIONS
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

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
}


// CONVERT DATABASE ICON NAMES TO DISPLAY ICONS
function getAchievementIcon($icon)
{
    $icons = [
        'book-open' => '📖',
        'flag' => '⚑',
        'target' => '◎',
        'trophy' => '🏆'
    ];

    return $icons[$icon] ?? '★';
}


// ESCAPE TEXT BEFORE DISPLAYING IT
function escapeHTML($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduQuest - Achievements</title>

    <link
        rel="stylesheet"
        href="css/Achievements_style.css?v=<?php
            echo file_exists(__DIR__ . '/css/Achievements_style.css')
                ? filemtime(__DIR__ . '/css/Achievements_style.css')
                : time();
        ?>"
    >

    <style>
        .profile-picture img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            EDU<span>QUEST</span>
        </div>

        <nav class="navigation">

            <a href="DashboardPage.php" class="nav-item">
                <span>⌂</span>
                Dashboard
            </a>

            <a href="CoursePage.php" class="nav-item">
                <span>▣</span>
                Courses
            </a>

            <a href="MissionsPage.php" class="nav-item">
                <span>⚑</span>
                Missions
            </a>

            <a href="ProgressPage.php" class="nav-item">
                <span>▤</span>
                Progress
            </a>

            <a href="AchievementsPage.php" class="nav-item active">
                <span>★</span>
                Achievements
            </a>

            <a href="ActivityRecords.php" class="nav-item">
                <span>📝</span>
                Activity Records
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="SettingsPage.php" class="nav-item">
                <span>⚙</span>
                Settings
            </a>

            <a href="logout.php" class="nav-item">
                <span>↪</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Achievements</h1>
                <p>Earn achievements as you learn.</p>
            </div>

            <div class="profile">

                <!-- NOTIFICATIONS -->
                <div class="notification-container">

                    <button
                        type="button"
                        class="notification-btn"
                        onclick="toggleNotifications()"
                        aria-label="Notifications"
                        aria-expanded="false"
                        id="notificationButton"
                    >

                        🔔

                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge">
                                <?php echo $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>

                    </button>

                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                    >

                        <div class="notification-header">

                            <h3>Notifications</h3>

                            <span>
                                <?php echo $unreadNotifications; ?> New
                            </span>

                        </div>

                        <?php if (empty($notifications)): ?>

                            <div class="notification-item">
                                <div class="notification-content">
                                    <p>No notifications available.</p>
                                </div>
                            </div>

                        <?php else: ?>

                            <?php foreach ($notifications as $note): ?>

                                <div class="notification-item">

                                    <div class="notification-icon">📢</div>

                                    <div class="notification-content">

                                        <strong>
                                            <?php
                                            echo escapeHTML(
                                                $note['notification_title']
                                            );
                                            ?>
                                        </strong>

                                        <p>
                                            <?php
                                            echo escapeHTML(
                                                $note['notification_message']
                                            );
                                            ?>
                                        </p>

                                        <small>
                                            <?php
                                            echo escapeHTML(
                                                $note['created_at']
                                            );
                                            ?>
                                        </small>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        <div class="notification-footer">
                            <a href="NotificationPage.php">
                                View All Notifications
                            </a>
                        </div>

                    </div>

                </div>


                <!-- DARK MODE -->
                <button
                    type="button"
                    class="dark-mode"
                    onclick="toggleDarkMode()"
                    aria-label="Toggle dark mode"
                >
                    ☾
                </button>


                <!-- USER PROFILE -->
                <div class="profile-info">

                    <div class="profile-picture">
                        <?php if (!empty($profileImage)): ?>
                            <img
                                src="<?php echo escapeHTML($profileImage); ?>"
                                alt="Profile Picture"
                                onerror="this.style.display='none'; this.parentElement.textContent='<?php echo escapeHTML($firstLetter); ?>';"
                            >
                        <?php else: ?>
                            <?php echo escapeHTML($firstLetter); ?>
                        <?php endif; ?>
                    </div>

                    <div>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $displayName,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </strong>

                        <small>
                            <?php echo $userRole === 'admin' ? 'Administrator' : 'Student'; ?>
                        </small>
                    </div>

                </div>

            </div>

        </header>


        <!-- ACHIEVEMENT SUMMARY -->
        <section class="achievement-summary">

            <span>ACHIEVEMENTS</span>

            <h2>
                <?php echo $achievementsUnlocked; ?>
                /
                <?php echo $totalAchievements; ?>
                achievements unlocked
            </h2>

            <p>Achievements unlocked</p>

        </section>


        <!-- ACHIEVEMENT CARDS -->
        <section class="achievement-section">

            <h2>Your Achievements</h2>

            <div class="achievement-grid">

                <?php if (!empty($achievements)): ?>

                    <?php foreach ($achievements as $achievement): ?>

                        <div class="achievement-card <?php
                            echo $achievement['is_unlocked']
                                ? 'unlocked'
                                : '';
                        ?>">

                            <div class="badge">
                                <?php
                                echo escapeHTML(
                                    getAchievementIcon($achievement['icon'])
                                );
                                ?>
                            </div>

                            <h3>
                                <?php
                                echo escapeHTML(
                                    $achievement['achievement_name']
                                );
                                ?>
                            </h3>

                            <p>
                                <?php
                                echo escapeHTML(
                                    $achievement['achievement_description']
                                );
                                ?>
                            </p>

                            <span>
                                <?php
                                echo $achievement['is_unlocked']
                                    ? 'Unlocked'
                                    : 'Locked';
                                ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>No achievements have been added yet.</p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>


<!-- JAVASCRIPT -->
<script>
    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode-active");
    }

    function toggleNotifications() {
        const dropdown = document.getElementById("notificationDropdown");
        const button = document.getElementById("notificationButton");

        if (!dropdown || !button) {
            return;
        }

        dropdown.classList.toggle("show");

        button.setAttribute(
            "aria-expanded",
            dropdown.classList.contains("show") ? "true" : "false"
        );
    }

    // CLOSE NOTIFICATIONS WHEN CLICKING OUTSIDE
    document.addEventListener("click", function(event) {
        const container = document.querySelector(".notification-container");
        const dropdown = document.getElementById("notificationDropdown");
        const button = document.getElementById("notificationButton");

        if (
            container &&
            dropdown &&
            !container.contains(event.target)
        ) {
            dropdown.classList.remove("show");

            if (button) {
                button.setAttribute("aria-expanded", "false");
            }
        }
    });
</script>

</body>
</html>
