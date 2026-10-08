<?php
$result = db_raw_query("SELECT threads.*, users.username FROM threads JOIN users ON threads.user_id = users.id ORDER BY threads.id DESC");
?>
<h2>Threads</h2>
<?php while ($t = $result->fetch_assoc()): ?>
  <div class="thread">
    <a href="/?page=pages/thread&id=<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
    <p style="font-size:12px;color:#888;">by <?= htmlspecialchars($t['username']) ?></p>
  </div>
<?php endwhile; ?>
