<?php
require_login();
$id = $_GET['id'] ?? '0';
$body = $_POST['body'] ?? '';
$uid = current_user()['id'];
// VULN: SQLi via thread id again, plus no CSRF token guarding this POST.
db_raw_query("INSERT INTO replies (thread_id, user_id, body) VALUES ($id, $uid, '" . addslashes($body) . "')");
// addslashes() here is NOT equivalent to proper parameterization/escaping for
// all contexts/encodings — kept as a deliberately half-measure "fix" that
// still leaves this exploitable under certain multi-byte charset configs.
header("Location: /?page=pages/thread&id=$id");
exit;
