<?php

include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';

$user_id = $_SESSION['user_id'];

// 查询用户基本信息
$stmt = $pdo->prepare("SELECT u.username, u.phone, u.role, u.created_at, p.real_name, p.address FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger mt-4'>用户不存在</div>";
    include 'footer.php';
    exit();
}

// 处理表单提交
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $real_name = trim($_POST['real_name']);
    $address = trim($_POST['address']);

    // 更新 users 表
    $stmt1 = $pdo->prepare("UPDATE users SET username=?, phone=? WHERE id=?");
    $stmt1->execute([$username, $phone, $user_id]);

    // 更新 user_profiles 表
    $stmt2 = $pdo->prepare("INSERT INTO user_profiles (user_id, real_name, address) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), address=VALUES(address)");
    $stmt2->execute([$user_id, $real_name, $address]);

    // 刷新SESSION
    $_SESSION['username'] = $username;

    $success = "资料已更新！";
    // 重新查询最新数据
    $stmt = $pdo->prepare("SELECT u.username, u.phone, u.role, u.created_at, p.real_name, p.address FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 角色首页跳转
$home = 'index.php';
if ($user['role'] === 'admin') $home = 'admin.php';
elseif ($user['role'] === 'seller') $home = 'seller.php';
?>

<div class="col-12">
    <div class="card mt-3">
        <div class="card-header py-3 d-flex align-items-center">
            <a href="<?= $home ?>" class="btn btn-link me-2"><i class="fas fa-arrow-left"></i></a>
            <h6 class="m-0 fw-bold">个人资料</h6>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">用户名</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">手机号</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">真实姓名</label>
                        <input type="text" name="real_name" class="form-control" value="<?= htmlspecialchars($user['real_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">注册日期</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['created_at']) ?>" disabled>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">收货地址</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">保存更改</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>