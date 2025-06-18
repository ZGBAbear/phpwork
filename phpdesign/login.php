<?php
// 引入数据库配置文件
include 'config.php';
// 启动Session会话
session_start();

// 用于存储错误信息
$error = '';

// 处理登录请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 登录方式：手机号或用户名
    $login_type = $_POST['login_type'] ?? 'phone'; // phone 或 username
    $password = $_POST['password'] ?? '';

    // 根据登录方式查询条件
    if ($login_type === 'phone') {
        $phone = $_POST['phone'] ?? '';
        $stmt = $pdo->prepare("SELECT id, username, phone, role, password FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
    } else {
        $username = $_POST['username'] ?? '';
        $stmt = $pdo->prepare("SELECT id, username, phone, role, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // 验证密码（本项目为明文存储）
    if ($user && $user['password'] === $password) {
        // 登录成功，保存用户信息到Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['role'] = $user['role'];
        // 登录后根据角色跳转到不同首页
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } elseif ($user['role'] === 'seller') {
            header("Location: seller.php");
        } else {
            header("Location: user.php");
        }
        exit;
    } else {
        $error = "账号或密码错误";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>用户登录</title>
    <!-- 使用Bootstrap 5.3.0 CDN，方便快速美化页面 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fc; }
        #logindiv {
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
        body#loginbody {
            background: url('img/bkjpg.jpg') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
    <script>
        // 切换登录方式（手机号/用户名）
        function switchTab(type) {
            document.getElementById('login_type').value = type;
            if (type === 'phone') {
                document.getElementById('phone-group').style.display = '';
                document.getElementById('username-group').style.display = 'none';
                document.getElementById('tab-phone').classList.add('active');
                document.getElementById('tab-username').classList.remove('active');
            } else {
                document.getElementById('phone-group').style.display = 'none';
                document.getElementById('username-group').style.display = '';
                document.getElementById('tab-phone').classList.remove('active');
                document.getElementById('tab-username').classList.add('active');
            }
        }
    </script>
</head>
<body id="loginbody">
    <div id="logindiv">
        <h3 class="mb-4">用户登录</h3>
        <!-- 登录错误提示 -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
         <!-- 登录方式切换按钮 -->
        <div class="btn-group w-100 mb-3">
            <button type="button" id="tab-phone" class="btn btn-outline-primary tab-btn active" onclick="switchTab('phone')">手机号登录</button>
            <button type="button" id="tab-username" class="btn btn-outline-primary tab-btn" onclick="switchTab('username')">用户名登录</button>
        </div>
        <!-- 登录表单 -->
        <form method="post" action="login.php">
            <input type="hidden" name="login_type" id="login_type" value="phone">
            <div id="phone-group">
                <input type="text" name="phone" class="form-control mb-3" placeholder="手机号" pattern="\d{11}" maxlength="11">
            </div>
            <div id="username-group" style="display:none;">
                <input type="text" name="username" class="form-control mb-3" placeholder="用户名">
            </div>
            <input type="password" name="password" class="form-control mb-3" placeholder="密码" required>
            <input type="submit" class="btn btn-primary" value="登录">
        </form>
        <!--注册链接-->
        <p class="mt-3">还没有账户？<a href="register.php">立即注册</a></p>
    </div>
    <script>
        // 默认手机号登录
        switchTab('phone');
    </script>
</body>
</html>

