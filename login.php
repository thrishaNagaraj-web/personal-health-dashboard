<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
redirectIfLoggedIn();

$error = '';
$success = isset($_GET['registered']) ? "Registration successful! Please log in." : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?");
    $stmt->execute([$ip, $window]);
    $attempts = $stmt->fetchColumn();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($attempts >= 5) {
        $error = "Too many login attempts. Please wait 15 minutes and try again.";
    } elseif (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
        $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header('Location: dashboard.php');
            exit;
        } else {
            $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
            $error = "Invalid username or password.";
        }
    }
    
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-1 hour')");
}
require_once 'includes/header.php';
?>

<div style="max-width: 400px; margin: 0 auto; padding: 2rem 0;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Welcome Back</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;">
            Don't have an account? <a href="registration.php">Sign Up</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
