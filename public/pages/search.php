<?php
$q = $_GET['q'] ?? '';
$results = [];
if ($q !== '') {
    // VULN: SQL injection — raw string concatenation into a LIKE clause.
    // e.g. ?q=x' UNION SELECT id,1,username,password,role,NULL,reset_token,NULL FROM users --
    $sql = "SELECT * FROM threads WHERE title LIKE '%$q%' OR body LIKE '%$q%'";
    $results = db_raw_query($sql);
}
?>
<h2>Search</h2>
<form method="get" action="/index.php">
  <input type="hidden" name="page" value="pages/search">
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="search threads...">
  <button type="submit">Search</button>
</form>
<?php if ($q !== ''): ?>
  <!-- VULN: reflected XSS — raw query echoed into the page as HTML.
       Try q = <img src=x onerror=alert(document.cookie)> -->
  <p>Results for: <?= $q ?></p>
  <ul><?php while ($r = $results->fetch_assoc()): ?><li><?= htmlspecialchars($r['title']) ?></li><?php endwhile; ?></ul>
<?php endif; ?>
