<?php $user = current_user(); ?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>VulnForum</title><link rel="stylesheet" href="/css/style.css"></head>
<body>
<nav>
  <a href="/?page=pages/home">VulnForum</a>
  <a href="/?page=pages/search">Search</a>
  <?php if ($user): ?>
    <a href="/?page=pages/new_thread">New Thread</a>
    <a href="/?page=pages/profile">Profile</a>
    <?php if ($user['role'] === 'admin'): ?><a href="/admin/users.php">Admin</a><?php endif; ?>
    <a href="/?page=pages/logout">Logout (<?= htmlspecialchars($user['username']) ?>)</a>
  <?php else: ?>
    <a href="/?page=pages/login">Login</a>
    <a href="/?page=pages/register">Register</a>
  <?php endif; ?>
</nav>
<main>
