<?php
// VULN: SQL injection — thread id taken directly from $_GET and concatenated.
// e.g. ?page=pages/thread&id=1 UNION SELECT id,1,username,password,role,... FROM users
$id = $_GET['id'] ?? '0';
$t = db_raw_query("SELECT threads.*, users.username FROM threads JOIN users ON threads.user_id = users.id WHERE threads.id = $id")->fetch_assoc();
if (!$t) { echo "<p>Thread not found.</p>"; return; }
$replies = db_raw_query("SELECT replies.*, users.username FROM replies JOIN users ON replies.user_id = users.id WHERE thread_id = $id ORDER BY replies.id");
?>
<h2><?= htmlspecialchars($t['title']) ?></h2>
<!-- VULN: stored XSS — thread body rendered as raw HTML, no sanitization on
     write (register.php/db insert) or read (here). -->
<div class="thread"><?= $t['body'] ?></div>

<h3>Replies</h3>
<?php while ($r = $replies->fetch_assoc()): ?>
  <div class="reply"><strong><?= htmlspecialchars($r['username']) ?>:</strong> <?= $r['body'] ?></div>
<?php endwhile; ?>

<?php if (current_user()): ?>
<form method="post" action="/?page=pages/reply&id=<?= (int)$id ?>">
  <textarea name="body" placeholder="Write a reply (HTML allowed!)"></textarea>
  <button type="submit">Reply</button>
</form>
<?php endif; ?>
