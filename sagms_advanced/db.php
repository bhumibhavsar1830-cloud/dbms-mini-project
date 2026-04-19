<?phpC
// ============================================================
// includes/db.php — Railway-ready Database Connection
// Supports: Railway env vars OR local XAMPP fallback
// ============================================================

// Load .env file for local dev if exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

// Railway env vars take priority, fallback to localhost
define('DB_HOST', getenv('MYSQLHOST')     ?: ($_ENV['DB_HOST'] ?? 'localhost'));
define('DB_USER', getenv('MYSQLUSER')     ?: ($_ENV['DB_USER'] ?? 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: ($_ENV['DB_PASS'] ?? ''));
define('DB_NAME', getenv('MYSQLDATABASE') ?: ($_ENV['DB_NAME'] ?? 'student_mgmt'));
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: ($_ENV['DB_PORT'] ?? 3306)));

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    http_response_code(500);
    // Show friendly error instead of raw MySQL error
    die(json_encode([
        'error' => true,
        'message' => 'Database connection failed. Please check configuration.'
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');

// Helper: safe query with prepared statement
function db_query(mysqli $conn, string $sql, string $types = '', ...$params): mysqli_result|bool {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result ?: true;
}

// Helper: fetch all rows
function db_fetch_all(mysqli $conn, string $sql, string $types = '', ...$params): array {
    $result = db_query($conn, $sql, $types, ...$params);
    if (!$result || $result === true) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

// Helper: fetch one row
function db_fetch_one(mysqli $conn, string $sql, string $types = '', ...$params): ?array {
    $result = db_query($conn, $sql, $types, ...$params);
    if (!$result || $result === true) return null;
    return mysqli_fetch_assoc($result) ?: null;
}

// Helper: escape for legacy queries
function esc(mysqli $conn, $val): string {
    return mysqli_real_escape_string($conn, trim((string)$val));
}

