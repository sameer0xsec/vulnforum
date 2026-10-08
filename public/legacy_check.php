<?php
require_once __DIR__ . '/includes/auth.php';

// VULN: legacy "quick login check" API retained for an old mobile client.
// Compares an MD5 hash using loose `==` via legacy_password_ok(). PHP
// treats hex-digit-only strings that look like "0e12345..." (all digits
// after the leading 0e) as equal to the float 0, so an attacker who finds
// -or brute-forces- any password whose MD5 hash has that "magic hash" shape
// authenticates as ANY user without knowing their real password, as long as
// the stored hash also happens to be (or can be made to be) a magic hash.
// Separately, submitting literal hash="0" against a stored hash that PHP
// also coerces to 0 (e.g. an empty/malformed stored value) bypasses it too.
header('Content-Type: application/json');
$username = $_GET['username'] ?? '';
$hash = $_GET['hash'] ?? '';
$result = db_raw_query("SELECT * FROM users WHERE username = '" . addslashes($username) . "'");
if ($result->num_rows === 0) { echo json_encode(['error' => 'no such user']); exit; }
$u = $result->fetch_assoc();
if (legacy_password_ok($hash, $u['password'])) {
    echo json_encode(['status' => 'ok', 'role' => $u['role']]);
} else {
    echo json_encode(['status' => 'denied']);
}
