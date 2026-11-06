<?php
/**
 * Run from a trusted terminal only: php scripts/create-admin.php
 * Prompts for credentials without placing passwords in source control.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/dbconn.php';

$username = trim((string)readline('Admin username: '));
$email = filter_var(trim((string)readline('Admin email: ')), FILTER_VALIDATE_EMAIL);
$password = (string)readline('New password (minimum 12 characters): ');

if ($username === '' || !$email || strlen($password) < 12) {
    fwrite(STDERR, "Invalid input. Use a username, valid email, and password of at least 12 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$date = date('Y-m-d');
$stmt = $mysqli->prepare('INSERT INTO admin (username,email,password,updation_date) VALUES (?,?,?,?)');
$stmt->bind_param('ssss', $username, $email, $hash, $date);
$stmt->execute();
$stmt->close();

fwrite(STDOUT, "Administrator account created successfully.\n");
