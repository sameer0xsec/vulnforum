<?php
require_once __DIR__ . '/../config.php';

function db_conn() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // VULN: connection error (including host/user detail) echoed straight out.
        die("DB connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// VULN: helper used by several pages to run raw, string-built SQL instead of
// prepared statements — kept separate so every raw-SQL call site greps easily.
function db_raw_query($sql) {
    $conn = db_conn();
    $result = $conn->query($sql);
    if ($result === false) {
        die("Query failed: " . $conn->error . " | SQL: " . $sql); // VULN: leaks query + DB error
    }
    return $result;
}
