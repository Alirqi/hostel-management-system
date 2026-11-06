<?php
session_start();
require_once __DIR__ . '/includes/dbconn.php';
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $plainPassword = (string)($_POST['password'] ?? '');
    if (!$email || $plainPassword === '') {
        $loginError = 'يرجى إدخال بريد إلكتروني صحيح وكلمة المرور.';
    } else {
        $stmt = $mysqli->prepare('SELECT id,email,password FROM userregistration WHERE email=? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $valid = false;
        if ($user) {
            $stored = (string)$user['password'];
            $legacy = strlen($stored) === 32 && ctype_xdigit($stored);
            $valid = $legacy ? hash_equals(strtolower($stored), md5($plainPassword)) : password_verify($plainPassword, $stored);
            if ($valid && $legacy) {
                $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                $upgrade = $mysqli->prepare('UPDATE userregistration SET password=? WHERE id=?');
                $upgrade->bind_param('si', $newHash, $user['id']);
                $upgrade->execute();
                $upgrade->close();
            }
        }
        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$user['id'];
            $_SESSION['login'] = $user['email'];
            $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            if ($ip === false) $ip = inet_pton('127.0.0.1');
            $city = ''; $country = '';
            $log = $mysqli->prepare('INSERT INTO userLog(userId,userEmail,userIp,city,country) VALUES(?,?,?,?,?)');
            $log->bind_param('issss', $_SESSION['id'], $_SESSION['login'], $ip, $city, $country);
            $log->execute(); $log->close();
            header('Location: student/dashboard.php'); exit;
        }
        $loginError = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
    }
}
?>
<!-- By CodeAstro - codeastro.com -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>الرئيسية</title>
    <!-- Custom CSS -->
    <link href="dist/css/style.min.css" rel="stylesheet">
    <link href="assets/css/modern-hostel.css" rel="stylesheet">

    <script type="text/javascript">
    function valid() {
    if(document.registration.password.value!= document.registration.cpassword.value){
        alert("Password and Re-Type Password Field do not match  !!");
    document.registration.cpassword.focus();
    return false;
        }
    return true;
        }
    </script>

</head>

<!-- By CodeAstro - codeastro.com -->

<body>
    
    <div class="main-wrapper">
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <div class="preloader">
            <div class="lds-ripple">
                <div class="lds-pos"></div>
                <div class="lds-pos"></div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <!-- By CodeAstro - codeastro.com -->
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(../assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(assets/images/hostel-img.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <div class="text-center">
                            <img src="assets/images/big/icon.png" alt="wrapkit">
                        </div>
                        <h2 class="mt-3 text-center">تسجيل دخول الطالب</h2>
                        
                        <form class="mt-4" method="POST">
                            <?php if ($loginError !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="uname">البريد الالكتروني</label>
                                        <input class="form-control" name="email" id="uname" type="email"
                                            placeholder="البريد الالكتروني" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="pwd">كلمة المرور</label>
                                        <input class="form-control" name="password" id="pwd" type="password"
                                            placeholder="كلمة المرور" required>
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button type="submit" name="login" class="btn btn-block btn-dark">تسجيل الدخول</button>
                                </div>
                                <div class="col-lg-12 text-center mt-5">
                                   <a href="admin/index.php" class="text-danger">تسجيل دخول المسؤول</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        

    
        <!-- By CodeAstro - codeastro.com -->
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- All Required js -->
    <!-- ============================================================== -->
    <script src="assets/libs/jquery/dist/jquery.min.js "></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->
    <script>
        $(".preloader ").fadeOut();
    </script>
    
</body>

</html>