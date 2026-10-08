<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$output = '';
$code = $_POST['expr'] ?? '';
if ($code !== '') {
    // VULN: eval() on user-controlled input — this "template preview" tool
    // was meant to let admins test small PHP snippets for thread signatures,
    // but it hands anyone who reaches this page (and remember, require_admin()
    // trusts a session role that can be forged via several bugs elsewhere in
    // this app — see VULNERABILITIES.md) direct remote code execution.
    ob_start();
    eval($code);
    $output = ob_get_clean();
}
?>
<!DOCTYPE html><html><head><title>Admin - Template Preview</title></head><body>
<h2>Template Preview Tool</h2>
<form method="post">
  <textarea name="expr" rows="4" cols="60" placeholder="echo 'Hello ' . current_user()['username'];"><?= htmlspecialchars($code) ?></textarea>
  <button type="submit">Run</button>
</form>
<pre><?= htmlspecialchars($output) ?></pre>
<p style="font-size:12px;color:#888;">Try: system('id');</p>
</body></html>
