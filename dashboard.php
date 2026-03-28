<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Latest Weight
$stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_weight = $stmt->fetchColumn() ?: '--';

// Today's Water
$stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_water = $stmt->fetchColumn() ?: 0;

// Today's Calories
$stmt = $pdo->prepare("SELECT SUM(calories) FROM calories_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_calories = $stmt->fetchColumn() ?: 0;

// Latest Exercise
$stmt = $pdo->prepare("SELECT activity, duration_mins FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_exercise = $stmt->fetch();

require_once 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 2rem 0;">
    <h2 style="margin-bottom: 2rem;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        
        <div class="card" style="text-align: center;">
            <h3>Latest Weight</h3>
            <p style="font-size: 2.5rem; margin: 1rem 0; color: var(--primary-color);">
                <?= htmlspecialchars($latest_weight) ?> <?= $latest_weight !== '--' ? '<span style="font-size: 1rem;">kg</span>' : '' ?>
            </p>
            <a href="weight-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">View History</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Water Today</h3>
            <p style="font-size: 2.5rem; margin: 1rem 0; color: #3498db;">
                <?= htmlspecialchars($today_water) ?> <span style="font-size: 1rem;">ml</span>
            </p>
            <a href="water-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Drink More</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Calories Today</h3>
            <p style="font-size: 2.5rem; margin: 1rem 0; color: #e67e22;">
                <?= htmlspecialchars($today_calories) ?> <span style="font-size: 1rem;">kcal</span>
            </p>
            <a href="calories-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Log Meal</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Latest Exercise</h3>
            <p style="font-size: 1.5rem; margin: 1.5rem 0; color: #2ecc71;">
                <?php if ($latest_exercise): ?>
                    <?= htmlspecialchars($latest_exercise['activity']) ?><br>
                    <span style="font-size: 1rem; color: #666;"><?= htmlspecialchars($latest_exercise['duration_mins']) ?> mins</span>
                <?php else: ?>
                    <span style="font-size: 1.5rem; color: #aaa;">--</span>
                <?php endif; ?>
            </p>
            <a href="exercise-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Add Activity</a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
