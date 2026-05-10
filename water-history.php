<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }
    $amount_ml = (int)$_POST['amount_ml'];
    $log_date = $_POST['log_date'];

    if ($amount_ml > 0 && !empty($log_date)) {
        $stmt = $pdo->prepare("INSERT INTO water_logs (user_id, amount_ml, log_date) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $amount_ml, $log_date])) {
            $success = "Water intake logged successfully!";
        }
    }
}

// Fetch history
$stmt = $pdo->prepare("SELECT id, amount_ml, log_date FROM water_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto; padding: 2rem 0;">
    <h2>Water Tracking</h2>
    
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Log Water Intake</h3>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="water-history.php" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="amount_ml">Amount (ml)</label>
                <input type="number" step="50" id="amount_ml" name="amount_ml" class="form-control" placeholder="e.g. 250" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="log_date">Date</label>
                <input type="date" id="log_date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Add Log</button>
        </form>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Water Intake History</h3>
            <?php if (count($logs) > 0): ?>
                <button onclick="exportToCSV()" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.9rem;">📥 Export CSV</button>
            <?php endif; ?>
        </div>
        
        <?php if (count($logs) > 0): ?>
            <!-- Chart Container -->
            <div style="margin: 1.5rem 0;">
                <canvas id="waterChart" height="100"></canvas>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #ddd; text-align: left;">
                        <th style="padding: 0.5rem;">Date</th>
                        <th style="padding: 0.5rem;">Amount (ml)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['log_date']) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['amount_ml']) ?> ml</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No water logs found. Start hydrating!</p>
        <?php endif; ?>
    </div>
</div>

<script>
<?php if (count($logs) > 0): ?>
    // Setup Data
    const rawLogs = <?= json_encode($logs) ?>;
    const chartLogs = [...rawLogs].reverse(); // oldest first

    // Chart Setup
    const ctx = document.getElementById('waterChart').getContext('2d');
    const dates = chartLogs.map(l => l.log_date);
    const amounts = chartLogs.map(l => l.amount_ml);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [{
                label: 'Water Intake (ml)',
                data: amounts,
                backgroundColor: '#3498db',
                borderRadius: 4
            }]
        },
        options: {
            scales: {
                y: { 
                    beginAtZero: true
                }
            }
        }
    });

    // CSV Export
    function exportToCSV() {
        let csvContent = "data:text/csv;charset=utf-8,Date,Amount (ml)\n";
        rawLogs.forEach(function(row) {
            csvContent += row.log_date + "," + row.amount_ml + "\n";
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "water_history.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>
