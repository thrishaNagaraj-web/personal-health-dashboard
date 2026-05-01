<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfLoggedIn();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$username, $hash])) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = "Failed to register. Please try again.";
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div style="max-width: 400px; margin: 0 auto; padding: 2rem 0;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Create an Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="registration.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;">
            Already have an account? <a href="login.php">Log In</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
