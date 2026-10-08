<?php
require_once __DIR__ . '/includes/db.php';

// VULN: path traversal — "name" is concatenated straight into a filesystem
// path with no sanitization or containment check.
// e.g. /file.php?name=../../../../etc/passwd
$name = $_GET['name'] ?? '';
$path = __DIR__ . '/uploads/' . $name;
if (file_exists($path)) {
    header('Content-Type: text/plain');
    readfile($path);
} else {
    http_response_code(404);
    echo "Not found: $path"; // VULN: leaks server path
}
