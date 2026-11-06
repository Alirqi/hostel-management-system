<?php
session_start();
require_once __DIR__ . '/../includes/dbconn.php';
require_once __DIR__ . '/../includes/check-login.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changepwd'])) {
    $oldPassword = (string)($_POST['oldpassword'] ?? '');
    $newPassword = (string)($_POST['newpassword'] ?? '');
    $adminId = (int)$_SESSION['id'];
    $stmt = $mysqli->prepare('SELECT password FROM admin WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $valid = false;
    if ($record) {
        $stored = (string)$record['password'];
        $legacy = strlen($stored) === 32 && ctype_xdigit($stored);
        $valid = $legacy ? hash_equals(strtolower($stored), md5($oldPassword)) : password_verify($oldPassword, $stored);
    }

    if (!$valid) {
        $_SESSION['msg'] = 'كلمة المرور الحالية غير صحيحة.';
    } elseif (strlen($newPassword) < 10) {
        $_SESSION['msg'] = 'يجب أن تتكون كلمة المرور الجديدة من 10 أحرف على الأقل.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $date = date('Y-m-d');
        $update = $mysqli->prepare('UPDATE admin SET password = ?, updation_date = ? WHERE id = ?');
        $update->bind_param('ssi', $newHash, $date, $adminId);
        $update->execute();
        $update->close();
        $_SESSION['msg'] = 'تم تحديث كلمة المرور بنجاح.';
    }
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png">
    <title>تغير كلمة المرور</title>
    <!-- Custom CSS -->
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
     <!-- This page plugin CSS -->
     <link href="../assets/extra-libs/datatables.net-bs4/css/dataTables.bootstrap4.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../dist/css/style.min.css" rel="stylesheet">

    <link href="../assets/css/modern-hostel.css" rel="stylesheet">
</head>

<body>
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
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin6">
            <?php include 'includes/navigation.php'?>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <?php include 'includes/sidebar.php'?>
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <div class="page-breadcrumb">
                <div class="row">
                    <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">تغير كلمة المرور</h4>
                        
                        
                        <?php if(isset($_POST['changepwd']))
                            { ?>
                                <div class="alert alert-secondary alert-dismissible bg-secondary text-white border-0 fade show"
                                    role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <strong>رسالة - </strong> <?php echo htmlentities($_SESSION['msg']); ?> <?php echo htmlentities($_SESSION['msg']=""); ?>
                                </div>
						<?php } ?>

                            
                        
                        
                    </div>
                    
                </div>

            </div>
            <!-- ============================================================== -->
            <!-- End Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">

                <form method="POST">

                    <div class="row">


                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">كلمة المرور الحالية</h4>
                                        <div class="form-group">
                                        <input type="password" value="" name="oldpassword" id="oldpassword" class="form-control" onBlur="checkpass()" required="required">
                                        <span id="password-availability-status" style="font-size:12px;"></span>
                                        </div>
                                    
                                </div>
                            </div>
                        </div>



                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">كلمة المرور الجديدة</h4>
                                        <div class="form-group">
                                        <input type="password" class="form-control" name="newpassword" id="newpassword" value="" required="required">
                                        </div>
                                    
                                </div>
                            </div>
                        </div>


                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">تاكيد كلمة المرور</h4>
                                        <div class="form-group">
                                        <input type="password" class="form-control" value="" required="required" id="cpassword" name="cpassword" >
                                        </div>
                                </div>
                            </div>
                        </div>

                        <?php	
                        $aid=$_SESSION['id'];
                        $ret="SELECT * from admin where id=?";
                            $stmt= $mysqli->prepare($ret) ;
                        $stmt->bind_param('i',$aid);
                        $stmt->execute() ;//ok
                        $res=$stmt->get_result();
                        //$cnt=1;
                        while($row=$res->fetch_object())
                        {
                            ?>

                        <h6 class="card-subtitle"><code>اخر تحديث: </code> <?php echo $row->updation_date; }?> </h6>



                    </div>


                        <div class="form-actions">
                            <div class="text-center">
                                <button type="submit" name="changepwd" class="btn btn-success">تغير</button>
                                <button type="reset" class="btn btn-danger">حذف</button>
                            </div>
                        </div>

                 
                 </form>
                 
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <?php include '../includes/footer.php' ?>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- apps -->
    <!-- apps -->
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <script src="../assets/extra-libs/c3/d3.min.js"></script>
    <script src="../assets/extra-libs/c3/c3.min.js"></script>
    <script src="../assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="../assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="../dist/js/pages/dashboards/dashboard1.min.js"></script>
    <script src="../assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../dist/js/pages/datatable/datatable-basic.init.js"></script>

    <script>
    function checkAvailability() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check-availability-admin.php",
            data:'emailid='+$("#emailid").val(),
            type: "POST",
        success:function(data){
            $("#user-availability-status").html(data);
            $("#loaderIcon").hide();
        },
        error:function (){}
        });
    }
    </script>

    <script>
    function checkpass() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check-availability-admin.php",
            data:'oldpassword='+$("#oldpassword").val(),
            type: "POST",
        success:function(data){
            $("#password-availability-status").html(data);
            $("#loaderIcon").hide();
        },
        error:function (){}
        });
    }
    </script>

</body>

</html>