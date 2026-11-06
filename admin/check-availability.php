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
    $userId = (int)$_SESSION['id'];
    $stmt = $mysqli->prepare('SELECT password FROM userregistration WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
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

if (isset($_POST['roomno']) && $_POST['roomno'] !== '') {
    $roomNo = filter_var($_POST['roomno'], FILTER_VALIDATE_INT);
    if ($roomNo === false) {
        echo '<span class="text-danger">رقم الغرفة غير صحيح.</span>';
        exit;
    }
    $stmt = $mysqli->prepare('SELECT r.seater, COUNT(reg.id) AS occupied FROM rooms r LEFT JOIN registration reg ON reg.roomno = r.room_no WHERE r.room_no = ? GROUP BY r.id, r.seater');
    $stmt->bind_param('i', $roomNo);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$room) {
        echo '<span class="text-danger">الغرفة غير موجودة.</span>';
    } elseif ((int)$room['occupied'] >= (int)$room['seater']) {
        echo '<span class="text-danger">الغرفة مكتملة الإشغال.</span>';
    } else {
        echo '<span class="text-success">الغرفة متاحة، الأماكن المتبقية: ' . ((int)$room['seater'] - (int)$room['occupied']) . '.</span>';
    }
}
