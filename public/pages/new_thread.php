<?php
require_login();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    $uid = current_user()['id'];
    db_raw_query("INSERT INTO threads (user_id, title, body) VALUES ($uid, '" . addslashes($title) . "', '" . addslashes($body) . "')");
    header('Location: /?page=pages/home');
    exit;
}
?>
<h2>New Thread</h2>
<form method="post">
  <input name="title" placeholder="title">
  <textarea name="body" placeholder="body (HTML allowed!)"></textarea>
  <button type="submit">Post</button>
</form>
