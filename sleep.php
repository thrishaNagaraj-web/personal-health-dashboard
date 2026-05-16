<?php
session_save_path('/tmp');
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$messages = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_sleep'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }
    $hours = filter_input(INPUT_POST, 'hours', FILTER_VALIDATE_FLOAT);
    $quality = filter_input(INPUT_POST, 'quality', FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');
    
    // Allow users to specify the date or default to today
    $log_date = $_POST['log_date'] ?? $today;

    if ($hours !== false && $quality >= 1 && $quality <= 5) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sleep_logs (user_id, hours, quality, log_date, note) 
                                   VALUES (?, ?, ?, ?, ?)
                                   ON CONFLICT(user_id, log_date) DO UPDATE SET 
                                   hours = excluded.hours, quality = excluded.quality, note = excluded.note");
            $stmt->execute([$user_id, $hours, $quality, $log_date, $note]);
            header('Location: sleep.php');
            exit;
        } catch (PDOException $e) {
            $messages[] = ['type' => 'error', 'text' => 'Database error. Please try again.'];
        }
    } else {
        $messages[] = ['type' => 'error', 'text' => 'Invalid input. Ensure hours and quality are correctly selected.'];
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM sleep_logs WHERE id = ? AND user_id = ?");
        $stmt->execute([$del_id, $user_id]);
    } catch (PDOException $e) {}
    header("Location: sleep.php");
    exit;
}

// Calculate logic for KPI (Last 30 days)
try {
    $stmt = $pdo->prepare("SELECT * FROM sleep_logs WHERE user_id = ? AND log_date >= date('now', '-30 days') ORDER BY log_date ASC");
    $stmt->execute([$user_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
}

$this_week_logs = array_filter($logs, fn($l) => strtotime($l['log_date']) >= strtotime('-7 days'));
$avg_hours_week = count($this_week_logs) > 0 ? array_sum(array_column($this_week_logs, 'hours')) / count($this_week_logs) : 0;

$best_night = null;
$worst_night = null;

if (count($logs) > 0) {
    // Sort by quality
    $sorted_by_quality = $logs;
    usort($sorted_by_quality, function($a, $b) {
        if ($b['quality'] == $a['quality']) return $b['hours'] <=> $a['hours'];
        return $b['quality'] <=> $a['quality'];
    });
    $best_night = $sorted_by_quality[0];
    $worst_night = end($sorted_by_quality);
}

// Prepare data for Chart.js (Last 14 days)
$last_14_days = [];
for ($i = 13; $i >= 0; $i--) {
    $last_14_days[date('Y-m-d', strtotime("-$i days"))] = ['hours' => 0, 'quality' => null];
}

foreach ($logs as $log) {
    if (isset($last_14_days[$log['log_date']])) {
        $last_14_days[$log['log_date']] = ['hours' => $log['hours'], 'quality' => $log['quality']];
    }
}

$chart_labels = array_keys($last_14_days);
$chart_hours = array_column($last_14_days, 'hours');
$chart_quality = array_column($last_14_days, 'quality');

$chart_bg_colors = array_map(function($h) {
    if ($h == 0) return 'rgba(200, 200, 200, 0.2)';
    if ($h < 6) return 'rgba(239, 68, 68, 0.7)'; // Red
    if ($h <= 7) return 'rgba(234, 179, 8, 0.7)'; // Yellow
    return 'rgba(34, 197, 94, 0.7)'; // Green
}, $chart_hours);

require_once 'includes/header.php';
$quality_emojis = [1 => '😴 Terrible', 2 => '😐 Poor', 3 => '🙂 Okay', 4 => '😊 Good', 5 => '🤩 Amazing'];
$quality_emojis_short = [1 => '😴', 2 => '😐', 3 => '🙂', 4 => '😊', 5 => '🤩'];
?>

<div class="main-container">
    <h2>Sleep Tracker</h2>
    
    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endforeach; ?>

    <div class="dashboard-grid">
        <div class="card" style="grid-column: 1 / -1;">
            <h3>Log Sleep</h3>
            <form method="POST" action="sleep.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Date</label>
                        <input type="date" name="log_date" class="form-control" value="<?= $today ?>" max="<?= $today ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Hours Slept</label>
                        <input type="number" name="hours" class="form-control" step="0.5" min="0" max="24" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Quality</label>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                        <?php foreach($quality_emojis as $val => $label): ?>
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
                            <input type="radio" name="quality" value="<?= $val ?>" required>
                            <span><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Note (optional)</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
                
                <button type="submit" name="log_sleep" class="btn btn-primary">Save Sleep Log</button>
            </form>
        </div>

        <div class="card">
            <h3>Avg Hours (This Week)</h3>
            <div class="widget-value" style="color: #6366f1;"><?= number_format($avg_hours_week, 1) ?>h</div>
        </div>
        <div class="card">
            <h3>Best Night</h3>
            <div class="widget-value" style="font-size: 1.5rem; color: #22c55e;">
                <?= $best_night ? date('M d', strtotime($best_night['log_date'])) . ' - ' . $best_night['hours'] . 'h ' . $quality_emojis_short[$best_night['quality']] : '--' ?>
            </div>
        </div>
        <div class="card">
            <h3>Worst Night</h3>
            <div class="widget-value" style="font-size: 1.5rem; color: #ef4444;">
                <?= $worst_night ? date('M d', strtotime($worst_night['log_date'])) . ' - ' . $worst_night['hours'] . 'h ' . $quality_emojis_short[$worst_night['quality']] : '--' ?>
            </div>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <h3>Last 14 Days Trend</h3>
            <canvas id="sleepChart" style="max-height: 300px;"></canvas>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <h3>Sleep History</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Hours</th>
                        <th>Quality</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    try {
                        $history_stmt = $pdo->prepare("SELECT * FROM sleep_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 30");
                        $history_stmt->execute([$user_id]);
                        while ($row = $history_stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['log_date'])) ?></td>
                        <td><?= $row['hours'] ?>h</td>
                        <td><?= $quality_emojis_short[$row['quality']] ?? '' ?></td>
                        <td><?= htmlspecialchars($row['note']) ?></td>
                        <td>
                            <a href="sleep.php?delete=<?= $row['id'] ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; border-color: #ef4444; color: #ef4444;" onclick="return confirm('Delete this log?');">Delete</a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } catch (PDOException $e) {} 
                    ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('sleepChart').getContext('2d');
    const sleepChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                type: 'line',
                label: 'Quality (1-5)',
                data: <?= json_encode($chart_quality) ?>,
                borderColor: '#6366f1',
                pointBackgroundColor: '#6366f1',
                pointRadius: 5,
                yAxisID: 'y1',
                spanGaps: true
            }, {
                type: 'bar',
                label: 'Hours Slept',
                data: <?= json_encode($chart_hours) ?>,
                backgroundColor: <?= json_encode($chart_bg_colors) ?>,
                yAxisID: 'y'
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, max: 14, title: { display: true, text: 'Hours' } },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    min: 0,
                    max: 5,
                    title: { display: true, text: 'Quality' }
                }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
