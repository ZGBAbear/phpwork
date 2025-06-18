<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // 登录后根据角色跳转
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit();
    } elseif ($_SESSION['role'] === 'seller') {
        header("Location: seller.php");
        exit();
    } else {
        header("Location: user.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>一云购物平台入口</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>  
<body>
<div class="container py-5">
    <h1 class="mb-4 text-center">一云购物平台</h1>
    <div class="row justify-content-center">
        <div class="col-md-4 text-center">
            <a href="login.php" class="btn btn-primary btn-lg w-100 mb-3">登录</a>
            <a href="register.php" class="btn btn-outline-secondary btn-lg w-100">注册</a>
        </div>
    </div>
</div>+
</body>
</html>
