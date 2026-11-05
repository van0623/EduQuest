<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - EduQuest</title>
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

            <section class="result-card">

                <div class="result-icon">
                    ✓
                </div>

                <span class="section-label">
                    FINAL QUEST RESULT
                </span>

                <h1>Assessment Complete</h1>

                <p class="result-message">
                    You have successfully completed the Final Quest.
                </p>


                <div class="score-box">

                    <span>Your Score</span>

                    <strong>8 / 10</strong>

                    <p>80%</p>

                </div>


                <div class="result-status">

                    <span class="status-icon">
                        ✓
                    </span>

                    <div>
                        <h3>Course Requirements Completed</h3>

                        <p>
                            You passed the Final Quest.
                        </p>
                    </div>

                </div>


                <div class="result-actions">

                    <a href="Course_Complete.html" class="primary-btn">
                        Continue
                    </a>

                    <a href="DashboardPage.html" class="secondary-btn">
                        Back to Dashboard
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