<?php
require_once 'includes/auth.php';
redirectIfLoggedIn();
require_once 'includes/header.php';
?>

<div class="hero">
    <h1>Your Personal Health Journey Begins Here</h1>
    <p>Track your weight, BMI, daily water intake, calories, and exercises in one beautifully designed dashboard.</p>
    <a href="registration.php" class="btn btn-primary" style="font-size: 1.25rem; padding: 1rem 2.5rem;">Start Tracking Now</a>
    
    <!-- Placeholder for hero image. You could generate one if needed -->
    <div style="margin-top: 3rem; display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
        <div class="card" style="width: 250px;">
            <h3>Weight Tracking</h3>
            <p class="widget-value">📊</p>
            <p>Log and visualize your weight trend.</p>
        </div>
        <div class="card" style="width: 250px;">
            <h3>Nutrition & Hydration</h3>
            <p class="widget-value">🥗</p>
            <p>Keep your calories and water intake in check.</p>
        </div>
        <div class="card" style="width: 250px;">
            <h3>Activity Logs</h3>
            <p class="widget-value">🏃</p>
            <p>Monitor your weekly exercises.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
