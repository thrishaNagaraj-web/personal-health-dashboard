<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Determine if sleep and mood tables exist
$has_sleep = false;
try {
    $pdo->query("SELECT 1 FROM sleep_logs LIMIT 1");
    $has_sleep = true;
} catch (Exception $e) {}

$has_mood = false;
try {
    $pdo->query("SELECT 1 FROM mood_logs LIMIT 1");
    $has_mood = true;
} catch (Exception $e) {}

// For the preview table (last 7 days)
$date_to = date('Y-m-d');
$date_from = date('Y-m-d', strtotime('-7 days'));

$dates = [];
$curr = strtotime($date_from);
$end = strtotime($date_to);
while ($curr <= $end) {
    $dates[] = date('Y-m-d', $curr);
    $curr = strtotime('+1 day', $curr);
}
// Sort DESC for preview
$dates = array_reverse($dates);

// Fetch preview data identical to CSV export
$preview_data = [];

$wt_stmt = $pdo->prepare("SELECT log_date, weight FROM weight_logs WHERE user_id = ? AND log_date BETWEEN ? AND ?");
$wt_stmt->execute([$user_id, $date_from, $date_to]);
$wt = []; while($r = $wt_stmt->fetch()) $wt[$r['log_date']] = $r['weight'];

$wa_stmt = $pdo->prepare("SELECT log_date, SUM(amount_ml) as t FROM water_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date");
$wa_stmt->execute([$user_id, $date_from, $date_to]);
$wa = []; while($r = $wa_stmt->fetch()) $wa[$r['log_date']] = $r['t'];

$ca_stmt = $pdo->prepare("SELECT log_date, SUM(calories) as t FROM calories_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date");
$ca_stmt->execute([$user_id, $date_from, $date_to]);
$ca = []; while($r = $ca_stmt->fetch()) $ca[$r['log_date']] = $r['t'];

$ex_stmt = $pdo->prepare("SELECT log_date, SUM(duration_mins) as t FROM exercise_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date");
$ex_stmt->execute([$user_id, $date_from, $date_to]);
$ex = []; while($r = $ex_stmt->fetch()) $ex[$r['log_date']] = $r['t'];

$sl = [];
if ($has_sleep) {
    $sl_stmt = $pdo->prepare("SELECT log_date, hours FROM sleep_logs WHERE user_id = ? AND log_date BETWEEN ? AND ?");
    $sl_stmt->execute([$user_id, $date_from, $date_to]);
    while($r = $sl_stmt->fetch()) $sl[$r['log_date']] = $r['hours'];
}

$md = [];
if ($has_mood) {
    $md_stmt = $pdo->prepare("SELECT log_date, mood FROM mood_logs WHERE user_id = ? AND log_date BETWEEN ? AND ?");
    $md_stmt->execute([$user_id, $date_from, $date_to]);
    while($r = $md_stmt->fetch()) $md[$r['log_date']] = $r['mood'];
}

foreach($dates as $d) {
    $preview_data[$d] = [
        'weight' => $wt[$d] ?? null,
        'water'  => $wa[$d] ?? null,
        'cal'    => $ca[$d] ?? null,
        'exc'    => $ex[$d] ?? null,
        'sleep'  => $sl[$d] ?? null,
        'mood'   => $md[$d] ?? null
    ];
}

require_once 'includes/header.php';
?>

<style>
@media print {
  .navbar, .export-form, .btn, footer, .landing-nav { display: none !important; }
  .preview-table { width: 100%; font-size: 12px; }
  body { background: white; color: black; }
  h1::after { content: " — Health Report"; }
}

.export-form {
    background: var(--surface); padding: 1.5rem; border-radius: 8px;
    margin-bottom: 2rem;
}
.checkbox-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin: 1rem 0;
}
.checkbox-grid label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.actions-row { display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap; }
</style>

<div class="main-container">
    <h1 style="margin-top: 0;">Export Your Health Data</h1>

    <div class="export-form card">
        <form action="export-csv.php" method="POST">
            <h3 style="margin-top: 0;">Configure Export</h3>
            
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Include Metrics</label>
                <div class="checkbox-grid">
                    <label><input type="checkbox" name="export_weight" checked> Weight</label>
                    <label><input type="checkbox" name="export_water" checked> Water</label>
                    <label><input type="checkbox" name="export_calories" checked> Calories</label>
                    <label><input type="checkbox" name="export_exercise" checked> Exercise</label>
                    <?php if ($has_sleep): ?>
                        <label><input type="checkbox" name="export_sleep" checked> Sleep</label>
                    <?php endif; ?>
                    <?php if ($has_mood): ?>
                        <label><input type="checkbox" name="export_mood" checked> Mood</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="actions-row">
                <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    📥 Download CSV
                </button>
                <button type="button" class="btn btn-outline" style="padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;" onclick="window.print()">
                    🖨️ Print / Save PDF
                </button>
            </div>
        </form>
    </div>

    <div class="card preview-table">
        <h3 style="margin-top: 0;">7-Day Preview</h3>
        <div class="table-container">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Weight (kg)</th>
                    <th>Water (ml)</th>
                    <th>Calories (kcal)</th>
                    <th>Exercise (mins)</th>
                    <?php if ($has_sleep): ?><th>Sleep (hrs)</th><?php endif; ?>
                    <?php if ($has_mood): ?><th>Mood</th><?php endif; ?>
                </tr>
                <?php foreach ($preview_data as $date => $metrics): 
                    $has_any = array_filter($metrics, function($v) { return $v !== null; });
                    if (empty($has_any)) continue;
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($date)) ?></td>
                    <td><?= $metrics['weight'] !== null ? htmlspecialchars($metrics['weight']) : '-' ?></td>
                    <td><?= $metrics['water'] !== null ? htmlspecialchars($metrics['water']) : '-' ?></td>
                    <td><?= $metrics['cal'] !== null ? htmlspecialchars($metrics['cal']) : '-' ?></td>
                    <td><?= $metrics['exc'] !== null ? htmlspecialchars($metrics['exc']) : '-' ?></td>
                    <?php if ($has_sleep): ?><td><?= $metrics['sleep'] !== null ? htmlspecialchars($metrics['sleep']) : '-' ?></td><?php endif; ?>
                    <?php if ($has_mood): ?><td><?= $metrics['mood'] !== null ? htmlspecialchars($metrics['mood']) : '-' ?></td><?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
