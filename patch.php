<?php
$files = [
    'water-history.php', 'weight-history.php', 'calories-history.php', 
    'exercise-history.php', 'profile.php', 'sleep.php', 'mood.php', 'goals.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // 1. Add require_once 'includes/csrf.php'; after auth.php
    if (strpos($content, "require_once 'includes/csrf.php';") === false) {
        $content = str_replace("require_once 'includes/auth.php';", "require_once 'includes/auth.php';\nrequire_once 'includes/csrf.php';", $content);
    }
    
    // 2. Add validation block cleanly tracing Regex captures recursively mapping inside conditionals
    if (strpos($content, "validateCsrfToken") === false) {
        $content = preg_replace('/(if\s*\(\$_SERVER\[\'REQUEST_METHOD\'\]\s*===\s*\'POST\'.*?\{)/', "$1\n    if (!validateCsrfToken(\$_POST['csrf_token'] ?? '')) {\n        die('Invalid request. Please go back and try again.');\n    }\n", $content);
    }
    
    // 3. Add <?= csrfField() ?> inside forms implicitly
    if (strpos($content, "csrfField()") === false) {
        $content = preg_replace('/(<form[^>]*>)/i', "$1\n            <?= csrfField() ?>", $content);
    }
    
    file_put_contents($file, $content);
    echo "Patched $file\n";
}
unlink(__FILE__);
?>
