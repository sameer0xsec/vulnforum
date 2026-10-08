<?php
session_destroy();
setcookie('remember', '', time() - 3600, '/');
header('Location: /?page=pages/home');
exit;
