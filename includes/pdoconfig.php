<?php
$DB_host = getenv('DB_HOST') ?: 'localhost';
$DB_user = getenv('DB_USER') ?: 'root';
$DB_pass = getenv('DB_PASSWORD') ?: '';
$DB_name = getenv('DB_NAME') ?: 'hostelmsphp';

try {
    $DB_con = new PDO(
        "mysql:host={$DB_host};dbname={$DB_name};charset=utf8mb4",
        $DB_user,
        $DB_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('PDO connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('تعذر الاتصال بقاعدة البيانات. تحقق من إعدادات الاتصال.');
}
