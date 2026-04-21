<?php
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'student_mgmt');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$conn) die('DB Error: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');

function db_query($conn, $sql, $types = '', ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result ?: true;
}

function db_fetch_all($conn, $sql, $types = '', ...$params) {
    $result = db_query($conn, $sql, $types, ...$params);
    if (!$result || $result === true) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function db_fetch_one($conn, $sql, $types = '', ...$params) {
    $result = db_query($conn, $sql, $types, ...$params);
    if (!$result || $result === true) return null;
    return mysqli_fetch_assoc($result) ?: null;
}

function esc($conn, $val) {
    return mysqli_real_escape_string($conn, trim((string)$val));
}
