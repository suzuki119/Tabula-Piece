<?php
// ローカル設定ファイルがあれば優先読み込み（本番環境用）
// db_local.php で DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS を定義する
$localConfig = __DIR__ . '/db_local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// デフォルト値（MAMP ローカル開発環境）
defined('DB_HOST')    || define('DB_HOST',    'localhost');
defined('DB_PORT')    || define('DB_PORT',    8889);
defined('DB_NAME')    || define('DB_NAME',    'tabula_piece');
defined('DB_USER')    || define('DB_USER',    'root');
defined('DB_PASS')    || define('DB_PASS',    'root');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = DB_PORT
        ? sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET)
        : sprintf('mysql:host=%s;dbname=%s;charset=%s',          DB_HOST,          DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
