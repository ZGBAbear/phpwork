<?php
// 引入配置文件
include_once 'config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) session_start();

// 未登录用户跳转到登录页
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 获取当前用户信息（用户名和角色）
function getUserInfo() {
    return [
        'username' => $_SESSION['username'] ?? '未登录',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}
$userInfo = getUserInfo();

// 角色配色
$roleColor = [
    'admin' => '#2563eb',    // 蓝色
    'seller' => '#f59e42',   // 橙色
    'user' => '#22b573'      // 绿色
];
$mainColor = $roleColor[$userInfo['role']] ?? '#2563eb';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>一云购物平台</title>
    <!-- 引入Bootstrap和FontAwesome样式 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 页面整体背景色 */
        body { background: #f8f9fc; }
        /* 侧边栏样式 */
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            top: 0; left: 0;
            padding-top: 40px;
        }
        /* 侧边栏品牌样式 */
        .sidebar-brand {
            font-size: 1.3rem;
            font-weight: bold;
            color: <?= $mainColor ?>;
            text-align: center;
            margin-bottom: 2rem;
        }
        /* 侧边栏导航链接样式 */
        .nav-link {
            color: #444;
            font-size: 1rem;
            padding: 10px 20px;
        }
        .nav-link.active, .nav-link:hover {
            background: <?= $mainColor ?>22;
            color: <?= $mainColor ?>;
        }
        /* 主内容区样式 */
        .content-wrapper {
            margin-left: 220px;
            padding: 30px 20px 0 20px;
        }
        /* 顶部栏样式 */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        /* 隐藏下拉按钮箭头 */
        .dropdown-toggle::after { display: none; }
    </style>
</head>
<body>
    <!-- 侧边导航栏 -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-shopping-bag me-2"></i>一云购物
        </div>
        <ul class="nav flex-column">
            <?php if ($userInfo['role'] === 'admin'): ?>
                <!-- 管理员菜单 -->
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='admin.php'?' active':'' ?>" href="admin.php"><i class="fas fa-home me-2"></i>首页</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='products.php'?' active':'' ?>" href="products.php"><i class="fas fa-box me-2"></i>商品管理</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='orders.php'?' active':'' ?>" href="orders.php"><i class="fas fa-file-invoice me-2"></i>订单管理</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='payments.php'?' active':'' ?>" href="payments.php"><i class="fas fa-credit-card me-2"></i>支付记录</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='customer_service.php'?' active':'' ?>" href="customer_service.php"><i class="fas fa-headset me-2"></i>服务请求</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='profile.php'?' active':'' ?>" href="profile.php"><i class="fas fa-user me-2"></i>个人资料</a></li>
            <?php elseif ($userInfo['role'] === 'seller'): ?>
                <!-- 商家菜单 -->
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='seller.php'?' active':'' ?>" href="seller.php"><i class="fas fa-home me-2"></i>首页</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='products.php'?' active':'' ?>" href="products.php"><i class="fas fa-box me-2"></i>商品管理</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='orders.php'?' active':'' ?>" href="orders.php"><i class="fas fa-file-invoice me-2"></i>订单管理</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='payments.php'?' active':'' ?>" href="payments.php"><i class="fas fa-credit-card me-2"></i>支付记录</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='customer_service.php'?' active':'' ?>" href="customer_service.php"><i class="fas fa-headset me-2"></i>服务请求</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='profile.php'?' active':'' ?>" href="profile.php"><i class="fas fa-user me-2"></i>个人资料</a></li>
            <?php else: ?>
                <!-- 普通用户菜单 -->
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='user.php'?' active':'' ?>" href="user.php"><i class="fas fa-home me-2"></i>首页</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='products.php'?' active':'' ?>" href="products.php"><i class="fas fa-shopping-bag me-2"></i>商品浏览</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='cart.php'?' active':'' ?>" href="cart.php"><i class="fas fa-shopping-cart me-2"></i>购物车</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='orders.php'?' active':'' ?>" href="orders.php"><i class="fas fa-file-invoice me-2"></i>我的订单</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='payments.php'?' active':'' ?>" href="payments.php"><i class="fas fa-credit-card me-2"></i>支付记录</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='customer_service.php'?' active':'' ?>" href="customer_service.php"><i class="fas fa-headset me-2"></i>服务请求</a></li>
                <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='profile.php'?' active':'' ?>" href="profile.php"><i class="fas fa-user me-2"></i>个人资料</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <!-- 主内容区 -->
    <div class="content-wrapper">
        <!-- 顶部栏，显示当前身份和用户信息 -->
        <div class="topbar d-flex align-items-center justify-content-between">
            <div>
                <span class="fw-bold" style="font-size:1.2rem; color:<?= $mainColor ?>">
                    <?php 
                    // 根据角色显示不同的标题
                    if ($userInfo['role'] === 'admin') echo '管理员控制台';
                    elseif ($userInfo['role'] === 'seller') echo '商家中心';
                    else echo '用户中心';
                    ?>
                </span>
            </div>
            <div class="d-flex align-items-center">
                <!-- 用户信息下拉菜单 -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($userInfo['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>个人资料</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="loginout.php"><i class="fas fa-sign-out-alt me-2"></i>退出</a></li>
                    </ul>
                </div>
            </div>
        </div>
<!-- 页面内容从这里开始 -->