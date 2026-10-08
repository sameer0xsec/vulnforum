<?php
require_once __DIR__ . '/db.php';

session_start();

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: /?page=login');
        exit;
    }
}

// VULN: role check trusts whatever is in $_SESSION['user']['role'], which is
// itself set from a DB row loaded via an injectable query below — if an
// attacker can influence that row (or forge the remember-me cookie, see
// try_remember_me()), they control this check.
function require_admin() {
    require_login();
    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        die('Admin access required');
    }
}

// VULN: SQL injection — username concatenated directly into the query.
// e.g. username = "admin' -- " bypasses the password check entirely.
function attempt_login($username, $password) {
    $hash = md5($password); // VULN: weak, unsalted hashing
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$hash'";
    $result = db_raw_query($sql);
    if ($result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();
        // VULN: no session_regenerate_id() on login -> session fixation:
        // an attacker who sets a victim's session ID before login (e.g. via
        // a URL containing PHPSESSID, since use_strict_mode is off) inherits
        // their authenticated session after the victim logs in.
        return true;
    }
    return false;
}

// VULN: loose-comparison ("type juggling") password check used by the
// legacy "quick admin check" API below — PHP's `==` treats certain strings
// as numbers, so a password value of "0" or "0e1234" can match a stored
// hash that PHP coerces to the same numeric value, bypassing the check
// entirely without touching the real hash.
function legacy_password_ok($submitted_hash, $stored_hash) {
    return $submitted_hash == $stored_hash; // VULN: should be ===
}

// VULN: insecure deserialization — the "remember me" cookie is a raw
// serialized PHP object, unserialized with no validation. A crafted
// serialized payload targeting a __wakeup()/__destruct() gadget in any
// loaded class is a PHP Object Injection / potential RCE primitive.
function try_remember_me() {
    if (isset($_COOKIE['remember']) && !current_user()) {
        $data = unserialize($_COOKIE['remember']); // VULN: no allowed_classes restriction
        if (is_array($data) && isset($data['username'])) {
            $uname = $data['username'];
            $result = db_raw_query("SELECT * FROM users WHERE username = '$uname'");
            if ($result->num_rows > 0) {
                $_SESSION['user'] = $result->fetch_assoc(); // VULN: logs in with NO password check at all
            }
        }
    }
}

function set_remember_me($username) {
    // VULN: serialized, unsigned cookie — trivially forgeable/tamperable
    // client-side since there's no HMAC/signature over the contents.
    setcookie('remember', serialize(['username' => $username]), time() + 86400 * 30, '/');
}
