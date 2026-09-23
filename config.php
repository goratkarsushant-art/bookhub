<?php
session_start();
$conn = mysqli_connect('localhost','root','','sushant');
if (!$conn) die('Database connection failed: '.mysqli_connect_error());
mysqli_set_charset($conn,'utf8mb4');

function login_required($role='') {
    if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
    if ($role && $_SESSION['role'] !== $role) { header('Location: ../index.php'); exit; }
}
function go_home() {
    if (!isset($_SESSION['role'])) return;
    $page = $_SESSION['role']==='superadmin' ? 'superadmin/dashboard.php' : ($_SESSION['role']==='admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
    header('Location: '.$page); exit;
}
function e($text) { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
?>
