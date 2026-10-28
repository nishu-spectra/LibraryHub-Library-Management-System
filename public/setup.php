<?php
require_once __DIR__ . '/../app/config.php';
$count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        $message = 'An account already exists. Remove or disable setup.php now.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');
        if (!$name || !$email || strlen($password) < 10) {
            $message = 'Enter a name, valid email and password of at least 10 characters.';
        } else {
            $stmt = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')");
            $stmt->execute([$name,$email,password_hash($password, PASSWORD_DEFAULT)]);
            $message = 'Administrator created. Delete public/setup.php, then sign in.';
            $count = 1;
        }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>First-time setup · LibraryHub</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-page"><section class="auth-card"><div class="auth-logo"><span class="brand-icon">▣</span> LibraryHub</div><h1>First-time setup</h1><p>Create the initial administrator account for your library.</p><?php if($message): ?><div class="alert <?= $count ? 'success' : 'error' ?>"><?= e($message) ?></div><?php endif; ?><?php if($count === 0): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label>Administrator name</label><input name="name" required maxlength="120"></div><div class="field"><label>Email</label><input type="email" name="email" required maxlength="190"></div>
<div class="password-field">
    <label>Admin Password</label>
    <input
        type="password"
        id="admin_password"
        name="password"
        required
    >

    <button
        type="button"
        class="password-toggle"
        onclick="togglePasswordField('admin_password', this)"
        aria-label="Show password"
    >
        👁
    </button>
</div>
<button class="btn btn-primary">Create administrator</button></form><?php else: ?><a class="btn btn-primary" href="login.php">Go to sign in</a><?php endif; ?></section>
<script>
function togglePasswordField(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
        button.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        button.textContent = '👁';
        button.setAttribute('aria-label', 'Show password');
    }
}
</script></body></html>
