<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Health Dashboard</title>
    <!-- Use Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Chart.js for data viz -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Set theme as early as possible to prevent flash
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar">
        <div class="nav-top">
            <div class="logo">HealthDash</div>
            <div class="nav-right-mobile">
                <button id="themeToggle" class="theme-btn" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">🌙</button>
                <button class="hamburger" id="hamburgerBtn" aria-label="Toggle Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="weight-history.php">⚖️ Weight</a></li>
            <li><a href="water-history.php">💧 Water</a></li>
            <li><a href="calories-history.php">🔥 Calories</a></li>
            <li><a href="exercise-history.php">🏃 Exercise</a></li>
            <li><a href="sleep.php">😴 Sleep</a></li>
            <li><a href="mood.php">😊 Mood</a></li>
            <li><a href="insights.php">🤖 Insights</a></li>
            <li><a href="goals.php">🎯 Goals</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="deficit-foods.php">🥗 Food Guide</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li class="nav-logout"><a href="logout.php" class="btn btn-outline">Logout</a></li>
        </ul>
    </nav>
    <?php else: ?>
    <nav class="navbar">
        <div class="nav-top">
            <div class="logo">HealthDash</div>
            <div class="nav-right-mobile">
                <button id="themeToggleGuest" class="theme-btn" aria-label="Toggle Dark Mode">🌙</button>
                <button class="hamburger" id="hamburgerBtnGuest" aria-label="Toggle Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
        <ul class="nav-links" id="navLinksGuest">
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php" class="btn btn-outline">Login</a></li>
            <li><a href="registration.php" class="btn btn-primary">Sign Up</a></li>
        </ul>
    </nav>
    <?php endif; ?>
    <div class="main-container">
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme');
            const toggleBtns = [document.getElementById('themeToggle'), document.getElementById('themeToggleGuest')].filter(Boolean);
            
            function updateIcon(theme) {
                toggleBtns.forEach(btn => btn.textContent = theme === 'dark' ? '☀️' : '🌙');
            }

            if (currentTheme) {
                updateIcon(currentTheme);
            }

            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    let targetTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', targetTheme);
                    localStorage.setItem('theme', targetTheme);
                    updateIcon(targetTheme);
                });
            });

            // Hamburger menu toggle
            const hamburgers = [document.getElementById('hamburgerBtn'), document.getElementById('hamburgerBtnGuest')].filter(Boolean);
            const navMenus = [document.getElementById('navLinks'), document.getElementById('navLinksGuest')].filter(Boolean);

            hamburgers.forEach((btn, i) => {
                btn.addEventListener('click', () => {
                    btn.classList.toggle('active');
                    if (navMenus[i]) navMenus[i].classList.toggle('active');
                });
            });

            // Close menu when a nav link is clicked (mobile)
            navMenus.forEach(menu => {
                menu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        menu.classList.remove('active');
                        hamburgers.forEach(h => h.classList.remove('active'));
                    });
                });
            });
        });
    </script>
