<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbhost = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'hostelmsphp';

try {
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('تعذر الاتصال بقاعدة البيانات. تحقق من إعدادات الاتصال.');
}
