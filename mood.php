<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$messages = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_mood'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }
    $mood = filter_input(INPUT_POST, 'mood', FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');
    $log_date = $_POST['log_date'] ?? $today;

    if ($mood >= 1 && $mood <= 5) {
        $stmt = $pdo->prepare("INSERT INTO mood_logs (user_id, mood, log_date, note) 
                               VALUES (?, ?, ?, ?)
                               ON CONFLICT(user_id, log_date) DO UPDATE SET 
                               mood = excluded.mood, note = excluded.note");
        $stmt->execute([$user_id, $mood, $log_date, $note]);
        $messages[] = ['type' => 'success', 'text' => 'Mood logged successfully!'];
    } else {
        $messages[] = ['type' => 'error', 'text' => 'Please select a valid mood.'];
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM mood_logs WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $user_id]);
    header("Location: mood.php");
    exit;
}

// Fetch 30 days of data
$stmt = $pdo->prepare("SELECT * FROM mood_logs WHERE user_id = ? AND log_date >= date('now', '-30 days') ORDER BY log_date ASC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

// Good Days Streak (mood >= 4)
$good_streak = 0;
// We check backwards from today
for ($i = 0; $i < 30; $i++) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($logs as $log) {
        if ($log['log_date'] === $d && $log['mood'] >= 4) {
            $good_streak++;
            $found = true;
            break;
        } else if ($log['log_date'] === $d) {
            // Logged but not good
            $found = true;
            break;
        }
    }
    if ($log['log_date'] === $d && $log['mood'] < 4) {
        break; // Streak broken structurally
    }
    if (!$found && $i > 0) {
        break; 
    }
}

// Prepare Heatmap Data (Current Month)
$current_month = date('Y-m');
$days_in_month = date('t');
$heatmap_data = [];
for ($i = 1; $i <= $days_in_month; $i++) {
    $d = $current_month . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $heatmap_data[$d] = null; // Default: no data
}
foreach ($logs as $log) {
    if (strpos($log['log_date'], $current_month) === 0) {
        $heatmap_data[$log['log_date']] = [
            'mood' => $log['mood'],
            'note' => $log['note']
        ];
    }
}

$mood_emojis = [1 => '😢', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😄'];
$mood_colors = [1 => '#ef4444', 2 => '#f97316', 3 => '#eab308', 4 => '#84cc16', 5 => '#22c55e'];

// Prepare Chart.js Data (Last 30 days exactly)
$chart_labels = [];
$chart_moods = [];
$last_30_days = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $last_30_days[$d] = null;
}
foreach ($logs as $log) {
    if (array_key_exists($log['log_date'], $last_30_days)) {
        $last_30_days[$log['log_date']] = $log['mood'];
    }
}
foreach ($last_30_days as $date => $mood) {
    $chart_labels[] = date('M d', strtotime($date));
    $chart_moods[] = $mood; // null will disconnect the line, which is fine, or we can use spanGaps
}

require_once 'includes/header.php';
?>

<style>
.mood-selector {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin: 1.5rem 0;
}
.mood-radio {
    display: none;
}
.mood-label {
    font-size: 3rem;
    cursor: pointer;
    transition: transform 0.2s, filter 0.2s;
    filter: grayscale(100%) opacity(0.5);
}
.mood-label:hover {
    transform: scale(1.1);
    filter: grayscale(0%) opacity(1);
}
.mood-radio:checked + .mood-label {
    filter: grayscale(0%) opacity(1);
    transform: scale(1.2);
}

.heatmap-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-top: 1rem;
}
.heatmap-cell {
    aspect-ratio: 1;
    border-radius: 4px;
    background: var(--border);
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.heatmap-cell:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 10;
    pointer-events: none;
}
</style>

<div class="main-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Mood Tracker</h2>
        <?php if ($good_streak > 0): ?>
            <div style="font-size: 1.1rem; font-weight: 600; background: var(--surface); padding: 0.5rem 1rem; border-radius: 999px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: #22c55e;">
                ✨ <?= $good_streak ?> Day Good Mood Streak!
            </div>
        <?php endif; ?>
    </div>

    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endforeach; ?>

    <div class="dashboard-grid">
        <div class="card" style="grid-column: 1 / -1; text-align: center;">
            <h3>How are you feeling?</h3>
            <form method="POST" action="mood.php">
                <?= csrfField() ?>
                <input type="hidden" name="log_date" value="<?= $today ?>">
                <div class="mood-selector">
                    <?php foreach($mood_emojis as $val => $emoji): ?>
                    <div>
                        <input type="radio" id="mood_<?= $val ?>" name="mood" value="<?= $val ?>" class="mood-radio" required>
                        <label for="mood_<?= $val ?>" class="mood-label" title="Level <?= $val ?>"><?= $emoji ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-group" style="max-width: 500px; margin: 0 auto; text-align: left;">
                    <label>Note (optional)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Why do you feel this way?"></textarea>
                </div>
                
                <button type="submit" name="log_mood" class="btn btn-primary" style="margin-top: 1rem;">Log Mood</button>
            </form>
        </div>

        <div class="card">
            <h3>This Month's Heatmap</h3>
            <div class="heatmap-grid">
                <?php 
                $first_day = date('w', strtotime($current_month . '-01'));
                for ($i = 0; $i < $first_day; $i++) {
                    echo '<div></div>'; // Padding
                }
                foreach ($heatmap_data as $date => $data) {
                    $color = $data ? $mood_colors[$data['mood']] : 'var(--border)';
                    $tooltip = date('M d', strtotime($date)) . ($data ? ': ' . $mood_emojis[$data['mood']] : ' (No entry)');
                    echo "<div class='heatmap-cell' style='background: {$color};' data-tooltip='{$tooltip}'></div>";
                }
                ?>
            </div>
        </div>

        <div class="card" style="grid-column: span 2;">
            <h3>30-Day Trend</h3>
            <canvas id="moodChart" style="max-height: 250px;"></canvas>
        </div>
        
        <div class="card" style="grid-column: 1 / -1;">
            <h3>Mood History</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Mood</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    $history_stmt = $pdo->prepare("SELECT * FROM mood_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 30");
                    $history_stmt->execute([$user_id]);
                    while ($row = $history_stmt->fetch()):
                    ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['log_date'])) ?></td>
                        <td style="font-size: 1.5rem;"><?= $mood_emojis[$row['mood']] ?></td>
                        <td><?= htmlspecialchars($row['note']) ?></td>
                        <td>
                            <a href="mood.php?delete=<?= $row['id'] ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; border-color: #ef4444; color: #ef4444;" onclick="return confirm('Delete this log?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('moodChart').getContext('2d');
    const moodEmojis = ['', '😢', '😕', '😐', '🙂', '😄'];
    const moodChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Mood',
                data: <?= json_encode($chart_moods) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                tension: 0.4,
                fill: true,
                spanGaps: true
            }]
        },
        options: {
            scales: {
                y: {
                    min: 1,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return moodEmojis[value] || '';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Mood: ' + moodEmojis[context.raw];
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
