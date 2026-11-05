<?php
session_start();

require_once __DIR__ . '/Admin_Portal/db_connect.php';

$conn->set_charset("utf8mb4");

// 1. Authenticate student session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Fetch user profile info
$stmt = $conn->prepare("SELECT user_id, full_name, username, email FROM users WHERE user_id = ? AND role = 'student' LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: login.php");
    exit;
}

$displayName = !empty($user['username']) ? $user['username'] : $user['full_name'];
$firstLetter = strtoupper(substr($displayName, 0, 1));

// Mark all as read action if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR audience = 'All students' OR user_id IS NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: NotificationPage.php");
    exit;
}

// Fetch all notifications for this student
$notifications = [];
$stmt = $conn->prepare("
    SELECT notification_id, title AS notification_title, message AS notification_message, is_read, created_at
    FROM notifications
    WHERE (user_id = ? OR audience = 'All students' OR user_id IS NULL)
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Unread count
$unreadNotifications = 0;
foreach ($notifications as $n) {
    if ((int)$n['is_read'] === 0) {
        $unreadNotifications++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | EduQuest</title>
    <link rel="stylesheet" href="css/Dashboard_style.css">
    <style>
        .notifications-list { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; max-width: 800px; }
        .notification-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .notification-card.unread { border-left: 4px solid #3b82f6; background: #f8fafc; }
        .notification-card h3 { margin: 0 0 5px 0; font-size: 16px; color: #0f172a; }
        .notification-card p { margin: 0 0 10px 0; font-size: 14px; color: #475569; }
        .notification-card small { font-size: 12px; color: #94a3b8; }
        .mark-read-btn { background: #e2e8f0; color: #334155; border: none; padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .mark-read-btn:hover { background: #cbd5e1; }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="DashboardPage.php" class="brand"><span>EDU</span><strong>QUEST</strong></a>
        <p class="menu-label">MAIN MENU</p>
        <nav class="sidebar-nav">
            <a href="DashboardPage.php" class="nav-link"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a href="CoursePage.php" class="nav-link"><span class="nav-icon">▤</span><span>Courses</span></a>
            <a href="MissionsPage.php" class="nav-link"><span class="nav-icon">⚑</span><span>Missions</span></a>
            <a href="ProgressPage.php" class="nav-link"><span class="nav-icon">▥</span><span>Progress</span></a>
            <a href="AchievementsPage.php" class="nav-link"><span class="nav-icon">★</span><span>Achievements</span></a>
        </nav>
        <div class="sidebar-bottom">
            <a href="SettingsPage.php" class="nav-link"><span class="nav-icon">⚙</span><span>Settings</span></a>
            <a href="logout.php" class="nav-link"><span class="nav-icon">↪</span><span>Logout</span></a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        <header class="topbar">
            <div class="welcome-text">
                <h1>All Notifications</h1>
                <p>Stay updated with announcements and platform activities.</p>
            </div>
            <div class="topbar-actions">
                <button type="button" class="icon-button" id="darkModeButton">☾</button>
                <div class="profile">
                    <div class="avatar"><?php echo htmlspecialchars($firstLetter, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-info">
                        <strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>Student</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <?php if ($unreadNotifications > 0): ?>
                <a href="NotificationPage.php?mark_read=all" class="mark-read-btn">Mark all as read</a>
            <?php endif; ?>

            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <p class="empty-state">No notifications found.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $note): ?>
                        <div class="notification-card <?php echo ((int)$note['is_read'] === 0) ? 'unread' : ''; ?>">
                            <div>
                                <h3><?php echo htmlspecialchars($note['notification_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($note['notification_message'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <small><?php echo htmlspecialchars($note['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
    const darkModeButton = document.getElementById("darkModeButton");
    if (localStorage.getItem("eduquest-dark-mode") === "enabled") {
        document.body.classList.add("dark-mode-active");
        darkModeButton.textContent = "☀";
    }
    darkModeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode-active");
        const enabled = document.body.classList.contains("dark-mode-active");
        darkModeButton.textContent = enabled ? "☀" : "☾";
        localStorage.setItem("eduquest-dark-mode", enabled ? "enabled" : "disabled");
    });
</script>

</body>
</html>