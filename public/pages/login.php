<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN: no CSRF token on this state-changing form.
    if (attempt_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        if (isset($_POST['remember'])) set_remember_me($_POST['username']);
        header('Location: /?page=pages/home');
        exit;
    }
    $msg = 'Invalid credentials';
}
?>
<h2>Login</h2>
<form method="post">
  <input name="username" placeholder="username">
  <input name="password" type="password" placeholder="password">
  <label><input type="checkbox" name="remember"> Remember me</label>
  <button type="submit">Login</button>
</form>
<p><?= htmlspecialchars($msg) ?></p>
<p style="font-size:12px;color:#888;">Try username <code>admin' -- </code> with any password.</p>
