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
        <div class="logo">HealthDash</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="weight-history.php">Weight</a></li>
            <li><a href="water-history.php">Water</a></li>
            <li><a href="calories-history.php">Calories</a></li>
            <li><a href="exercise-history.php">Exercise</a></li>
            <li><a href="deficit-foods.php">Food Guide</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><button id="themeToggle" style="background: transparent; border: none; cursor: pointer; font-size: 1.25rem;" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">🌙</button></li>
            <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
        </ul>
    </nav>
    <?php else: ?>
    <nav class="navbar">
        <div class="logo">HealthDash</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><button id="themeToggleGuest" style="background: transparent; border: none; cursor: pointer; font-size: 1.25rem;">🌙</button></li>
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
        });
    </script>
