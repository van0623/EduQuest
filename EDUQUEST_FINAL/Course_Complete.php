<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Complete - EduQuest</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="page-container">

        <header class="topbar">

            <div class="welcome">
                <h2>EduQuest</h2>
            </div>

            <div class="profile">
                <span class="notification">🔔</span>

                <button class="dark-mode-btn" onclick="toggleDarkMode()">
                    🌙
                </button>

                <div class="profile-info">
                    <span class="profile-name">Student</span>
                </div>
            </div>

        </header>


        <main class="learning-container">

            <section class="course-complete-card">

                <div class="course-complete-icon">
                    ★
                </div>

                <span class="section-label">
                    COURSE COMPLETE
                </span>

                <h1>Congratulations!</h1>

                <p class="course-complete-message">
                    You have successfully completed
                    <strong>Web Development Basics</strong>.
                </p>


                <div class="course-summary">

                    <div class="summary-item">

                        <strong>4</strong>

                        <span>
                            Missions Completed
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>10</strong>

                        <span>
                            Final Quest Questions
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>80%</strong>

                        <span>
                            Final Score
                        </span>

                    </div>

                </div>


                <div class="achievement-unlock">

                    <span class="achievement-icon">
                        🏆
                    </span>

                    <div>
                        <h3>Achievement Unlocked</h3>

                        <p>
                            Web Development Beginner
                        </p>
                    </div>

                </div>


                <div class="completion-actions">

                    <a href="DashboardPage.html" class="primary-btn">
                        Back to Dashboard
                    </a>

                    <a href="CoursePage.html" class="secondary-btn">
                        Explore Other Courses
                    </a>

                </div>

            </section>

        </main>

    </div>


    <script>
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode-active");
        }
    </script>

</body>

</html>