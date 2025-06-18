<?php
// 引入数据库配置文件
include 'config.php';
// 启动Session会话
session_start();

// 用于存储错误和成功信息
$error = '';
$success = '';

// 处理注册请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_type = $_POST['reg_type'] ?? 'user'; // 注册类型：user 或 seller
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // 基本校验
    if (!$username || !$phone || !$password || !$password2) {
        $error = "请填写完整信息";
    } elseif ($password !== $password2) {
        $error = "两次输入的密码不一致";
    } elseif (strlen($password) < 6) {
        // 只要求密码不少于6位，不要求必须包含字母和数字
        $error = "密码必须不少于6位";
    } else {
        // 检查用户名或手机号是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR phone = ?");
        $stmt->execute([$username, $phone]);
        if ($stmt->fetch()) {
            $error = "用户名或手机号已被注册";
        } else {
            // 只允许注册普通用户或商家
            $role = $reg_type === 'seller' ? 'seller' : 'user';
            $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $phone, $password, $role])) {
                $success = "注册成功，请<a href='login.php'>登录</a>！";
            } else {
                $error = "注册失败，请重试";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>用户注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body#registerbody {
            background: url('img/bkpng.png') no-repeat center center fixed;
            background-size: cover;
        }
        #registerdiv {
            margin: 100px auto;
            width: 350px;
            padding: 30px 30px 20px 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            text-align: center;
        }
        .form-control { border-radius: 20px; }
        .btn-primary { border-radius: 20px; width: 100%; }
        .tab-btn { width: 50%; border-radius: 0; }
        .tab-btn.active { background: #4e73df; color: #fff; }
    </style>
    <script>
        // 切换注册类型（客户/商家）
        function switchRegTab(type) {
            document.getElementById('reg_type').value = type;
            if (type === 'user') {
                document.getElementById('tab-user').classList.add('active');
                document.getElementById('tab-seller').classList.remove('active');
            } else {
                document.getElementById('tab-user').classList.remove('active');
                document.getElementById('tab-seller').classList.add('active');
            }
        }
    </script>
</head>
<body id="registerbody">
    <div id="registerdiv">
        <h3 class="mb-4">用户注册</h3>
        <!-- 错误提示 -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <!-- 成功提示 -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <!-- 注册类型切换按钮 -->
        <div class="btn-group w-100 mb-3">
            <button type="button" id="tab-user" class="btn btn-outline-primary tab-btn active" onclick="switchRegTab('user')">客户注册</button>
            <button type="button" id="tab-seller" class="btn btn-outline-primary tab-btn" onclick="switchRegTab('seller')">商家注册</button>
        </div>
        <!-- 注册表单 -->
        <form method="post" action="register.php">
            <input type="hidden" name="reg_type" id="reg_type" value="user">
            <input type="text" name="username" class="form-control mb-3" placeholder="用户名" required>
            <input type="text" name="phone" class="form-control mb-3" placeholder="手机号" pattern="\d{11}" maxlength="11" required>
            <!-- 密码输入两次，校验规则：不少于6位 -->
            <input type="password" name="password" class="form-control mb-3" placeholder="密码（不少于6位）" required>
            <input type="password" name="password2" class="form-control mb-3" placeholder="请再次输入密码" required>
            <input type="submit" class="btn btn-primary" value="注册">
        </form>
        <p class="mt-3">已有账号？<a href="login.php">去登录</a></p>
        <!-- 说明区域 -->
        <div class="alert alert-info mt-3" style="font-size:14px;">
            密码必须不少于6位，两次输入需一致。
        </div>
    </div>
    <script>
        // 默认客户注册
        switchRegTab('user');
    </script>
</body>
</html>
