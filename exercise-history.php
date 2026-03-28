<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $activity = trim($_POST['activity'] ?? '');
    $duration_mins = (int)$_POST['duration_mins'];
    $calories_burned = (int)($_POST['calories_burned'] ?? 0);
    $log_date = $_POST['log_date'];

    if (!empty($activity) && $duration_mins > 0 && !empty($log_date)) {
        $stmt = $pdo->prepare("INSERT INTO exercise_logs (user_id, activity, duration_mins, calories_burned, log_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $activity, $duration_mins, $calories_burned > 0 ? $calories_burned : null, $log_date])) {
            $success = "Exercise logged successfully!";
        }
    }
}

// Fetch history
$stmt = $pdo->prepare("SELECT id, activity, duration_mins, calories_burned, log_date FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto; padding: 2rem 0;">
    <h2>Exercise Tracking</h2>
    
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Log Exercise</h3>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="exercise-history.php" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="activity">Activity</label>
                <input type="text" id="activity" name="activity" class="form-control" placeholder="e.g. Running" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="duration_mins">Duration (mins)</label>
                <input type="number" id="duration_mins" name="duration_mins" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="calories_burned">Cals Burned (opt)</label>
                <input type="number" id="calories_burned" name="calories_burned" class="form-control" placeholder="e.g. 300">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="log_date">Date</label>
                <input type="date" id="log_date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Add Log</button>
        </form>
    </div>

    <div class="card">
        <h3>Exercise History</h3>
        <?php if (count($logs) > 0): ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #ddd; text-align: left;">
                        <th style="padding: 0.5rem;">Date</th>
                        <th style="padding: 0.5rem;">Activity</th>
                        <th style="padding: 0.5rem;">Duration (mins)</th>
                        <th style="padding: 0.5rem;">Calories Burned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['log_date']) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['activity']) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['duration_mins']) ?> mins</td>
                            <td style="padding: 0.5rem;"><?= $log['calories_burned'] ? htmlspecialchars($log['calories_burned']) . ' kcal' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No exercise logs found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
