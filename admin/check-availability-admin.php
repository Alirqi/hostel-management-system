<?php
session_start();
require_once __DIR__ . '/../includes/dbconn.php';

header('Content-Type: text/html; charset=utf-8');

if (isset($_POST['emailid'])) {
    $email = trim((string)$_POST['emailid']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<span class="text-danger">يرجى إدخال بريد إلكتروني صحيح.</span>';
    } else {
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM userregistration WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        echo $count > 0
            ? '<span class="text-danger">البريد الإلكتروني مستخدم مسبقاً.</span>'
            : '<span class="text-success">البريد الإلكتروني متاح.</span>';
    }
}

if (isset($_POST['oldpassword'])) {
    if (empty($_SESSION['id'])) {
        http_response_code(401);
        echo '<span class="text-danger">انتهت الجلسة، يرجى تسجيل الدخول مجدداً.</span>';
        exit;
    }
    $adminId = (int)$_SESSION['id'];
    $stmt = $mysqli->prepare('SELECT password FROM admin WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stored = (string)($record['password'] ?? '');
    $legacy = strlen($stored) === 32 && ctype_xdigit($stored);
    $valid = $legacy ? hash_equals(strtolower($stored), md5((string)$_POST['oldpassword'])) : password_verify((string)$_POST['oldpassword'], $stored);
    echo $valid
        ? '<span class="text-success">كلمة المرور الحالية صحيحة.</span>'
        : '<span class="text-danger">كلمة المرور الحالية غير صحيحة.</span>';
}
