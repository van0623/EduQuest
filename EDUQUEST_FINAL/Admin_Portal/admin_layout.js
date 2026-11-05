document.addEventListener("DOMContentLoaded", function () {
    const app = document.getElementById("admin-app");

    if (!app) return;

    const currentPage = app.dataset.page || "Dashboard";

    const navigation = [
        { name: "Dashboard", icon: "⌂", link: "AdminDashboard.php" },
        { name: "Courses", icon: "▤", link: "AdminCourses.php" },
        { name: "Modules & Lessons", icon: "▣", link: "AdminModules.php" },
        { name: "Missions", icon: "⚑", link: "AdminMissions.php" },
        { name: "Activities", icon: "✎", link: "AdminActivities.php" },
        { name: "Challenges", icon: "◇", link: "AdminChallenges.php" },
        { name: "Students", icon: "♙", link: "AdminStudents.php" },
        { name: "Reports", icon: "▥", link: "AdminReports.php" },
        { name: "Admin Accounts", icon: "♟", link: "AdminAccount.php" },
        { name: "Notifications & Activity", icon: "	♧", link: "AdminActivity.php" }
    ];

    const sidebarLinks = navigation.map(function (item) {
        const active = currentPage === item.name ? "active" : "";

        return `
            <a href="${item.link}" class="${active}">
                <span class="nav-icon">${item.icon}</span>
                <span>${item.name}</span>
            </a>
        `;
    }).join("");

    const layout = document.createElement("div");
    layout.className = "admin-shell";

    layout.innerHTML = `
        <aside class="sidebar">
            <a href="AdminDashboard.php" class="brand">
                <span class="brand-mark">E</span>
                <span>EDU<em>QUEST</em></span>
            </a>

            <p class="nav-label">ADMIN MENU</p>

            <nav class="side-nav">
                ${sidebarLinks}
            </nav>

            <div class="sidebar-bottom">
                <nav class="side-nav">
                    <a href="AdminSettings.php"
                       class="${currentPage === "Settings" ? "active" : ""}">
                        <span class="nav-icon">⚙</span>
                        <span>Settings</span>
                    </a>

                  <a href="AdminLogout.php" class="logout">
                        <span class="nav-icon">↪</span>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </aside>

        <div class="admin-main">
            <header class="topbar">
                <div class="topbar-title">
                    <h2>${currentPage}</h2>
                    <p>Manage your EduQuest learning platform</p>
                </div>

                <div class="topbar-actions">
                    <div class="notification-wrap">
                        <button class="top-icon"
                                id="notificationButton"
                                type="button"
                                aria-label="Notifications"
                                aria-expanded="false">
                            🔔
                            <span class="notification-dot"></span>
                        </button>

                        <div class="notification-dropdown"
                             id="notificationDropdown">
                            <h3>Notifications</h3>

                            <div class="notification-entry">
                                <b>New student registered</b>
                                <p>A new student account was created.</p>
                            </div>

                            <div class="notification-entry">
                                <b>Course update</b>
                                <p>Check the latest course activity.</p>
                            </div>

                            <a href="AdminActivity.php">
                                View All Activity →
                            </a>
                        </div>
                    </div>

                    <button class="top-icon"
                            id="darkModeButton"
                            type="button"
                            aria-label="Toggle dark mode">
                        ☾
                    </button>

                    <div class="admin-profile">
                        ${
                            window.adminProfileImage
                                ? `<img
                                    src="${window.adminProfileImage}"
                                    alt="Admin Profile"
                                    class="avatar"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <div class="avatar" style="display:none;">
                                    ${(window.adminFullName || "A").charAt(0).toUpperCase()}
                                </div>`
                                : `<div class="avatar">
                                    ${(window.adminFullName || "A").charAt(0).toUpperCase()}
                                </div>`
                        }

                        <div class="profile-copy">
                            <b>${window.adminFullName || "Admin"}</b>
                            <span>Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-page-content"></div>
        </div>
    `;

    const originalContent = app.querySelector(".page-content");

    if (originalContent) {
        layout.querySelector(".admin-page-content").appendChild(
            originalContent
        );
    }

    app.appendChild(layout);

    // Dark mode
    const darkModeButton = document.getElementById("darkModeButton");

    if (localStorage.getItem("eduquestAdminDarkMode") === "enabled") {
        document.body.classList.add("dark-mode");
        darkModeButton.textContent = "☀";
    }

    darkModeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        const isDark = document.body.classList.contains("dark-mode");

        localStorage.setItem(
            "eduquestAdminDarkMode",
            isDark ? "enabled" : "disabled"
        );

        darkModeButton.textContent = isDark ? "☀" : "☾";
    });

    // Notifications dropdown
    const notificationButton =
        document.getElementById("notificationButton");

    const notificationDropdown =
        document.getElementById("notificationDropdown");

    notificationButton.addEventListener("click", function () {
        const isOpen = notificationDropdown.classList.toggle("show");

        notificationButton.setAttribute(
            "aria-expanded",
            String(isOpen)
        );
    });

    document.addEventListener("click", function (event) {
        if (
            !event.target.closest(".notification-wrap") &&
            notificationDropdown.classList.contains("show")
        ) {
            notificationDropdown.classList.remove("show");
            notificationButton.setAttribute("aria-expanded", "false");
        }
    });

    // Modal windows
    document.querySelectorAll("[data-modal]").forEach(function (button) {
        button.addEventListener("click", function () {
            const modalId = button.dataset.modal;
            const modal = document.getElementById(modalId);

            if (modal) {
                modal.showModal();
            }
        });
    });

    // Close a modal by clicking its Cancel or Close button
    document.querySelectorAll(".modal form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            const submitButton = event.submitter;

            if (
                submitButton &&
                submitButton.dataset.toast
            ) {
                event.preventDefault();
                showToast(submitButton.dataset.toast);
            }
        });
    });

    // Toast messages
    function showToast(message) {
        const toast = document.getElementById("toast");

        if (!toast) return;

        toast.textContent = message;
        toast.classList.add("show");

        window.clearTimeout(showToast.timer);

        showToast.timer = window.setTimeout(function () {
            toast.classList.remove("show");
        }, 3000);
    }

    document.querySelectorAll("[data-toast]").forEach(function (button) {
        button.addEventListener("click", function (event) {
            if (button.closest(".modal form")) return;

            event.preventDefault();
            showToast(button.dataset.toast);
        });
    });

    // Table search
    document.querySelectorAll("[data-filter]").forEach(function (input) {
        input.addEventListener("input", function () {
            const tableId = input.dataset.filter;
            const table = document.getElementById(tableId);

            if (!table) return;

            const searchText = input.value.toLowerCase();

            table.querySelectorAll("tbody tr").forEach(function (row) {
                const rowText = row.textContent.toLowerCase();

                row.style.display = rowText.includes(searchText)
                    ? ""
                    : "none";
            });
        });
    });

    // Tabs
    document.querySelectorAll("[data-tab]").forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.dataset.tab;
            const targetPanel = document.getElementById(targetId);

            if (!targetPanel) return;

            const tabContainer = button.closest(".tabs");

            if (!tabContainer) return;

            tabContainer.querySelectorAll("[data-tab]").forEach(
                function (tabButton) {
                    tabButton.classList.remove("active");
                }
            );

            const parent = targetPanel.parentElement;

            parent.querySelectorAll(".tab-panel").forEach(
                function (panel) {
                    panel.classList.remove("active");
                }
            );

            button.classList.add("active");
            targetPanel.classList.add("active");
        });
    });

    // Dark mode option inside Settings
    const settingsDarkMode = document.getElementById("settingsDarkMode");

    if (settingsDarkMode) {
        settingsDarkMode.checked =
            document.body.classList.contains("dark-mode");

        settingsDarkMode.addEventListener("change", function () {
            document.body.classList.toggle(
                "dark-mode",
                settingsDarkMode.checked
            );

            localStorage.setItem(
                "eduquestAdminDarkMode",
                settingsDarkMode.checked ? "enabled" : "disabled"
            );

            darkModeButton.textContent =
                settingsDarkMode.checked ? "☀" : "☾";
        });
    }
});