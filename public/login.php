<?php
require_once __DIR__ . '/../app/config.php';
if (!empty($_SESSION['user_id'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 csrf_check();
 $email = trim((string)($_POST['email'] ?? '')); $password = (string)($_POST['password'] ?? '');
 $stmt = db()->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=? AND active=1 LIMIT 1');
 $stmt->execute([$email]); $user = $stmt->fetch();
 if ($user && password_verify($password, $user['password_hash'])) {
  session_regenerate_id(true);
  $_SESSION['user_id']=(int)$user['id']; $_SESSION['name']=$user['name']; $_SESSION['role']=$user['role'];
  redirect('index.php');
 }
 $error = 'Email or password is incorrect.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · LibraryHub</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-page"><section class="auth-card"><div class="auth-logo"><span class="brand-icon">▣</span> LibraryHub</div><h1>Welcome back</h1><p>Sign in to manage your library records.</p><?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label>Email address</label><input type="email" name="email" autocomplete="username" required></div>
<div class="password-field">
    <label>Password</label>
    <input
        type="password"
        id="password"
        name="password"
        required
    >

    <button
        type="button"
        class="password-toggle"
        onclick="togglePasswordField('password', this)"
        aria-label="Show password"
    >
        👁
    </button>
</div>
<button class="btn btn-primary">Sign in</button></form><p class="muted" style="margin-top:18px">First time here? Complete the one-time setup first.</p></section>
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
