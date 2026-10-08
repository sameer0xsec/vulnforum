<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html><html><head><title>Admin - Users</title></head><body>
<h2>Admin: Users</h2>
<table border="1">
<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password hash</th><th>Reset token</th></tr>
<?php
// VULN: dumps password hashes and reset tokens straight into the page.
$result = db_raw_query("SELECT * FROM users");
while ($u = $result->fetch_assoc()):
?>
<tr>
  <td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['username']) ?></td>
  <td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td>
  <td><?= htmlspecialchars($u['password']) ?></td><td><?= htmlspecialchars($u['reset_token'] ?? '') ?></td>
</tr>
<?php endwhile; ?>
</table>
<p><a href="/admin/eval.php">Template preview tool</a></p>
</body></html>
