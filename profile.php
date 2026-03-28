<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $birthdate = $_POST['birthdate'] ?: null;
    $height = $_POST['height'] ?: null;
    $gender = $_POST['gender'] ?: null;

    $stmt = $pdo->prepare("UPDATE users SET birthdate = ?, height = ?, gender = ? WHERE id = ?");
    if ($stmt->execute([$birthdate, $height, $gender, $user_id])) {
        $success = "Profile updated successfully!";
    }
}

$stmt = $pdo->prepare("SELECT username, birthdate, height, gender FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

function calculateAge($birthdate) {
    if (!$birthdate) return 'N/A';
    $dob = new DateTime($birthdate);
    $now = new DateTime();
    $diff = $now->diff($dob);
    return $diff->y;
}

require_once 'includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto; padding: 2rem 0;">
    <h2>My Profile</h2>
    <div class="card">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Age:</strong> <?= calculateAge($user['birthdate']) ?> years</p>
        
        <form method="POST" action="profile.php" style="margin-top: 1.5rem;">
            <div class="form-group">
                <label for="birthdate">Birthdate</label>
                <input type="date" id="birthdate" name="birthdate" class="form-control" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="height">Height (cm)</label>
                <input type="number" step="0.1" id="height" name="height" class="form-control" value="<?= htmlspecialchars($user['height'] ?? '') ?>" placeholder="e.g. 175">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">Select...</option>
                    <option value="Male" <?= ($user['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($user['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($user['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
