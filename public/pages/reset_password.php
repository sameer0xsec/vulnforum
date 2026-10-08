<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $token = $_POST['token'] ?? '';
    $newpass = md5($_POST['new_password'] ?? '');
    // VULN: SQLi here too, same pattern as everywhere else in this app.
    $result = db_raw_query("SELECT * FROM users WHERE username = '$username' AND reset_token = '$token'");
    if ($result->num_rows > 0) {
        db_raw_query("UPDATE users SET password = '$newpass', reset_token = NULL WHERE username = '$username'");
        $msg = 'Password reset.';
    } else {
        $msg = 'Invalid token.';
    }
}
?>
<h2>Reset Password</h2>
<form method="post">
  <input name="username" placeholder="username">
  <input name="token" placeholder="4-digit token">
  <input name="new_password" type="password" placeholder="new password">
  <button type="submit">Reset</button>
</form>
<p><?= htmlspecialchars($msg) ?></p>
