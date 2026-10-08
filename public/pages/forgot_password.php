<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    // VULN: predictable, low-entropy reset token generated with rand()
    // (not cryptographically secure), and returned directly in the page
    // response instead of only being emailed to the account owner.
    $token = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    db_raw_query("UPDATE users SET reset_token = '$token' WHERE username = '" . addslashes($username) . "'");
    $msg = "Reset token (would normally be emailed): $token";
}
?>
<h2>Forgot Password</h2>
<form method="post">
  <input name="username" placeholder="username">
  <button type="submit">Request reset</button>
</form>
<p><?= htmlspecialchars($msg) ?></p>
