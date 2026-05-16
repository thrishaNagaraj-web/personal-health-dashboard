<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_goals'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }
    $water = filter_input(INPUT_POST, 'water_goal', FILTER_VALIDATE_INT) ?: 2000;
    $calorie = filter_input(INPUT_POST, 'calorie_goal', FILTER_VALIDATE_INT) ?: 2000;
    $exercise = filter_input(INPUT_POST, 'exercise_goal', FILTER_VALIDATE_INT) ?: 30;
    $sleep = filter_input(INPUT_POST, 'sleep_goal', FILTER_VALIDATE_FLOAT) ?: 8;
    $weight = filter_input(INPUT_POST, 'weight_goal', FILTER_VALIDATE_FLOAT);
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO goals (user_id, water_goal, calorie_goal, exercise_goal, sleep_goal, weight_goal, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$user_id, $water, $calorie, $exercise, $sleep, $weight]);
    
    $_SESSION['flash'] = 'Goals saved successfully!';
    header('Location: goals.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ?");
$stmt->execute([$user_id]);
$goals = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$goals) {
    $stmt = $pdo->prepare("INSERT INTO goals (user_id, water_goal, calorie_goal, exercise_goal, sleep_goal) VALUES (?, 2000, 2000, 30, 8)");
    $stmt->execute([$user_id]);
    
    $goals = [
        'water_goal' => 2000,
        'calorie_goal' => 2000,
        'exercise_goal' => 30,
        'sleep_goal' => 8,
        'weight_goal' => null
    ];
}

$water_goal = $goals['water_goal'];
$calorie_goal = $goals['calorie_goal'];
$exercise_goal = $goals['exercise_goal'];
$sleep_goal = $goals['sleep_goal'];
$weight_goal = $goals['weight_goal'];

// Fetch today's actual values
$stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_water = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(calories) FROM calories_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_calories = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(duration_mins) FROM exercise_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_exercise_mins = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT hours FROM sleep_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_sleep = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_weight = $stmt->fetchColumn();

// Calculate %
$water_pct    = $water_goal > 0 ? min(100, round(($today_water / $water_goal) * 100)) : 0;
$calorie_pct  = $calorie_goal > 0 ? min(100, round(($today_calories / $calorie_goal) * 100)) : 0;
$exercise_pct = $exercise_goal > 0 ? min(100, round(($today_exercise_mins / $exercise_goal) * 100)) : 0;
$sleep_pct    = $sleep_goal > 0 ? min(100, round(($last_sleep / $sleep_goal) * 100)) : 0;

$weight_pct = 0;
$weight_projection_msg = "Log more weight entries to see projection.";

if ($weight_goal !== null && $latest_weight !== false && $latest_weight !== null) {
    // Determine start weight
    $stmt = $pdo->prepare("SELECT weight, log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC, id ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $first_entry = $stmt->fetch();
    
    if ($first_entry) {
        $first_weight = $first_entry['weight'];
        $direction = $weight_goal < $first_weight ? 'lose' : 'gain';
        
        $total_change_needed = abs($first_weight - $weight_goal);
        $current_change = abs($first_weight - $latest_weight);
        
        // Ensure progress goes in the right direction
        if (($direction === 'lose' && $latest_weight <= $first_weight) || 
            ($direction === 'gain' && $latest_weight >= $first_weight)) {
            if ($total_change_needed > 0) {
                $weight_pct = min(100, round(($current_change / $total_change_needed) * 100));
            } else {
                $weight_pct = 100;
            }
        }
        
        // 7 days trending logic
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
        $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? AND log_date >= ? ORDER BY log_date ASC, id ASC");
        $stmt->execute([$user_id, $seven_days_ago]);
        $recent_weights = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($recent_weights) > 1) {
            $w_start = $recent_weights[0];
            $w_end = end($recent_weights);
            $change_7d = $w_end - $w_start;
            $avg_daily_change = $change_7d / 7;
            
            // Check if trending right direction
            $trending_right = ($direction === 'lose' && $avg_daily_change < 0) || ($direction === 'gain' && $avg_daily_change > 0);
            
            if ($trending_right && abs($avg_daily_change) > 0.01) {
                $days_remaining = abs($latest_weight - $weight_goal) / abs($avg_daily_change);
                if ($latest_weight == $weight_goal) {
                    $weight_projection_msg = "You've reached your weight goal! 🎉";
                } else if (($direction == 'lose' && $latest_weight < $weight_goal) || ($direction == 'gain' && $latest_weight > $weight_goal)) {
                    $weight_projection_msg = "You've surpassed your weight goal! 🎉";
                } else {
                    $projected_date = date('M j, Y', strtotime("+" . round($days_remaining) . " days"));
                    $weight_projection_msg = "At your current pace, you'll reach your goal by <strong>{$projected_date}</strong> 🚀";
                }
            } else {
                $weight_projection_msg = "Currently not trending toward goal. Keep pushing! 💪";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.progress-ring {
  width: 130px; height: 130px;
  border-radius: 50%;
  background: conic-gradient(var(--accent) calc(var(--pct) * 1%), transparent 0);
  display: flex; align-items: center; justify-content: center;
}
.ring-inner {
  width: 96px; height: 96px; border-radius: 50%;
  background: var(--surface);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 2px;
}
.ring-value { font-size: 1.4rem; font-weight: 700; color: var(--accent); }
.ring-label { font-size: 0.7rem; color: var(--text-muted); 
              text-transform: uppercase; letter-spacing: 0.05em; }
.ring-wrapper {
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
}
.ring-detail { margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
.flash-success {
    background: #ecfdf5; color: #22c55e;
    padding: 1rem; border-radius: 8px; border: 1px solid #a7f3d0;
    margin-bottom: 2rem; font-weight: 600; text-align: center;
}
</style>

<div class="main-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">My Health Goals</h2>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-success"><?= htmlspecialchars($_SESSION['flash']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin-top: 0; margin-bottom: 2rem;">Today's Progress</h3>
        
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center;">
            <div class="ring-wrapper">
                <div class="progress-ring" style="--pct: <?= $water_pct ?>; --accent: #3498db;">
                    <div class="ring-inner">
                        <span class="ring-value"><?= $water_pct ?>%</span>
                        <span class="ring-label">Water</span>
                    </div>
                </div>
                <p class="ring-detail"><?= $today_water ?>ml / <?= $water_goal ?>ml</p>
            </div>

            <div class="ring-wrapper">
                <div class="progress-ring" style="--pct: <?= $calorie_pct ?>; --accent: #e67e22;">
                    <div class="ring-inner">
                        <span class="ring-value"><?= $calorie_pct ?>%</span>
                        <span class="ring-label">Calories</span>
                    </div>
                </div>
                <p class="ring-detail"><?= $today_calories ?>kcal / <?= $calorie_goal ?>kcal</p>
            </div>

            <div class="ring-wrapper">
                <div class="progress-ring" style="--pct: <?= $exercise_pct ?>; --accent: #2ecc71;">
                    <div class="ring-inner">
                        <span class="ring-value"><?= $exercise_pct ?>%</span>
                        <span class="ring-label">Exercise</span>
                    </div>
                </div>
                <p class="ring-detail"><?= $today_exercise_mins ?>m / <?= $exercise_goal ?>m</p>
            </div>

            <div class="ring-wrapper">
                <div class="progress-ring" style="--pct: <?= $sleep_pct ?>; --accent: #8b5cf6;">
                    <div class="ring-inner">
                        <span class="ring-value"><?= $sleep_pct ?>%</span>
                        <span class="ring-label">Sleep</span>
                    </div>
                </div>
                <p class="ring-detail"><?= $last_sleep ?>h / <?= $sleep_goal ?>h</p>
            </div>
            
            <?php if ($weight_goal !== null): ?>
            <div class="ring-wrapper">
                <div class="progress-ring" style="--pct: <?= $weight_pct ?>; --accent: #14b8a6;">
                    <div class="ring-inner">
                        <span class="ring-value"><?= $weight_pct ?>%</span>
                        <span class="ring-label">Weight</span>
                    </div>
                </div>
                <p class="ring-detail"><?= $latest_weight ?>kg / <?= $weight_goal ?>kg</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($weight_goal !== null && $latest_weight !== null && $latest_weight !== false): ?>
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem;">🎯 Weight Goal Projection</h3>
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem;">
            Current: <strong><?= $latest_weight ?>kg</strong> &rarr; Goal: <strong><?= $weight_goal ?>kg</strong>
        </p>

        <div style="width: 100%; height: 12px; background: var(--border); border-radius: 999px; overflow: hidden; margin-bottom: 1.5rem;">
            <div style="height: 100%; width: <?= $weight_pct ?>%; background: #14b8a6; border-radius: 999px; transition: width 1s;"></div>
        </div>

        <div style="font-size: 1.5rem; color: var(--primary);">
            <?= $weight_projection_msg ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Set Your Goals</h3>
        <form method="POST" action="goals.php">
            <?= csrfField() ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label>💧 Daily Water Goal (ml)</label>
                    <input type="number" name="water_goal" class="form-control" value="<?= $water_goal ?>" required>
                </div>
                <div class="form-group">
                    <label>🔥 Daily Calorie Goal (kcal)</label>
                    <input type="number" name="calorie_goal" class="form-control" value="<?= $calorie_goal ?>" required>
                </div>
                <div class="form-group">
                    <label>🏃 Exercise Goal (mins/day)</label>
                    <input type="number" name="exercise_goal" class="form-control" value="<?= $exercise_goal ?>" required>
                </div>
                <div class="form-group">
                    <label>😴 Sleep Goal (hours/night)</label>
                    <input type="number" name="sleep_goal" class="form-control" step="0.5" value="<?= $sleep_goal ?>" required>
                </div>
                <div class="form-group">
                    <label>⚖️ Target Weight (kg) [Optional]</label>
                    <input type="number" name="weight_goal" class="form-control" step="0.1" value="<?= $weight_goal ?>">
                </div>
            </div>
            
            <button type="submit" name="save_goals" class="btn btn-primary btn-block" style="margin-top: 1rem;">Save Goals</button>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 1rem;">Goals are used to calculate your Health Score and daily progress bars</p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
