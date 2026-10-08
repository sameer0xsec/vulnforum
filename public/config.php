<?php
// VULN: DB credentials and app secrets hardcoded directly in a file inside
// the webroot. If Apache ever misconfigures the PHP handler (or this file
// is renamed .php.bak during an edit), its contents are served as plaintext.
define('DB_HOST', 'db');
define('DB_USER', 'vulnforum');
define('DB_PASS', 'forum123');
define('DB_NAME', 'vulnforum');
define('APP_SECRET', 'supersecret'); // VULN: weak, hardcoded, used nowhere securely anyway

// VULN: errors displayed to the browser in "production" too — stack traces,
// file paths, and query fragments all leak on any uncaught error/warning.
ini_set('display_errors', 1);
error_reporting(E_ALL);
