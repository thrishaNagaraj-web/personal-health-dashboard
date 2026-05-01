<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfLoggedIn();

$error = '';
$success = isset($_GET['registered']) ? "Registration successful! Please log in." : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
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
