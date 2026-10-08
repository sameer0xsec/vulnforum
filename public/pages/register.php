<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN: mass assignment — "role" taken straight from the POST body and
    // inserted as-is, so submitting role=admin via a raw HTTP request
    // (bypassing this form, which doesn't even show the field) self-promotes.
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = md5($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'user';
    db_raw_query("INSERT INTO users (username, email, password, role) VALUES ('$username','$email','$password','$role')");
    $msg = 'Registered. You can log in now.';
}
?>
<h2>Register</h2>
<form method="post">
  <input name="username" placeholder="username">
  <input name="email" placeholder="email">
  <input name="password" type="password" placeholder="password">
  <button type="submit">Register</button>
</form>
<p><?= htmlspecialchars($msg) ?></p>
<p style="font-size:12px;color:#888;">
  This form doesn't expose a "role" field — but the backend accepts one from
  any raw POST (try curl with role=admin).
</p>
