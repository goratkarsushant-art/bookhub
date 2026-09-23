<?php
require_once 'config.php';
require_once 'mail_config.php';
if (isset($_SESSION['user_id'])) go_home();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']??''); $password=$_POST['password']??'';
    $q=mysqli_prepare($conn,'SELECT id,name,email,password,role FROM users WHERE email=? LIMIT 1');
    mysqli_stmt_bind_param($q,'s',$email); mysqli_stmt_execute($q);
    $user=mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if ($user && password_verify($password,$user['password'])) {
        $_SESSION['user_id']=$user['id']; $_SESSION['user_name']=$user['name']; $_SESSION['role']=$user['role'];
        $mailSent = send_login_alert($user['email'], $user['name']);
        $_SESSION['login_mail_status'] = $mailSent ? 'Login successful. A confirmation email has been sent to your registered email address.' : 'Login successful. Email could not be sent; check Gmail SMTP/App Password settings.';
        go_home();
    } else $error='Invalid email or password.';
}
?>
<!doctype html><html><head><title>Library Login</title><link rel="stylesheet" href="assets/css/root/index.css"></head><body><div class="box"><h1>📚 BookHub</h1><?php if($error): ?><div class="error"><?=e($error)?></div><?php endif; ?><form method="post"><input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Password" required><button>Login</button></form><p><a href="forgot_password.php">Forgot Password?</a> | <a href="register.php">Register</a></p></div></body></html>
