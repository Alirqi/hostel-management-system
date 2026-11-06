<?php
function check_login()
{
    if (empty($_SESSION['id']) || !ctype_digit((string) $_SESSION['id'])) {
        $_SESSION = [];
        $target = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin'
            ? 'index.php'
            : '../index.php';
        header('Location: ' . $target, true, 302);
        exit;
    }
}
