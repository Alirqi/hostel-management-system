<?php
session_start();
require_once __DIR__ . '/../includes/dbconn.php';
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $identity = trim($_POST['username'] ?? '');
    $plainPassword = (string)($_POST['password'] ?? '');
    if ($identity !== '' && $plainPassword !== '') {
        $stmt = $mysqli->prepare('SELECT id,username,email,password FROM admin WHERE username=? OR email=? LIMIT 1');
        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $valid = false;
        if ($admin) {
            $stored = (string)$admin['password'];
            $legacy = strlen($stored) === 32 && ctype_xdigit($stored);
            $valid = $legacy ? hash_equals(strtolower($stored), md5($plainPassword)) : password_verify($plainPassword, $stored);
            if ($valid && $legacy) {
                $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                $upgrade = $mysqli->prepare('UPDATE admin SET password=? WHERE id=?');
                $upgrade->bind_param('si', $newHash, $admin['id']);
                $upgrade->execute(); $upgrade->close();
            }
        }
        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$admin['id'];
            $_SESSION['admin_login'] = $admin['username'];
            header('Location: dashboard.php'); exit;
        }
    }
    $loginError = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
}
?>

<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png">
    <title>نظام ادارة سكن الطلاب</title>
    <!-- Custom CSS -->
    <link href="../dist/css/style.min.css" rel="stylesheet">\n    <link href="../assets/css/modern-hostel.css" rel="stylesheet">

    <script type="text/javascript">
    function valid() {
    if(document.registration.password.value!= document.registration.cpassword.value){
        alert("كلمة المرور وحقل إعادة كتابة كلمة المرور غير متطابقين !!");
    document.registration.cpassword.focus();
    return false;
        }
    return true;
        }
    </script>

</head>

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

        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(../assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(../assets/images/adimg.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <div class="text-center">
                            <img src="../assets/images/big/icon.png" alt="wrapkit">
                        </div>
                        <h2 class="mt-3 text-center">تسجيل دخول المسؤول</h2>
                        
                        <form class="mt-4" method="POST">\n                            <?php if ($loginError !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="uname">البريد الالكتروني او اسم المستخدم</label>
                                        <input class="form-control" name="username" id="uname" type="text"
                                            placeholder="البريد الالكتروني او اسم المستخدم" required>
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
                                    <button type="submit" name="login" class="btn btn-block btn-danger">تسجيل</button>
                                </div>
                                <div class="col-lg-12 text-center mt-5">
                                   <a href="../index.php" class="text-danger">العودة</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- All Required js -->
    <!-- ============================================================== -->
    <script src="../assets/libs/jquery/dist/jquery.min.js "></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->
    <script>
        $(".preloader ").fadeOut();
    </script>
</body>

</html>