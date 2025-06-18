<?php
include 'config.php';
session_start();

// 只允许管理员访问
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'header.php';

// 统计数据
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSellers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// 查询所有用户信息
$stmt = $pdo->query("SELECT id, username, phone, role, created_at FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查询所有分类
$cat_stmt = $pdo->query("SELECT id, name FROM categories");
$categories = [];
foreach ($cat_stmt as $cat) {
    $categories[$cat['id']] = $cat['name'];
}

// 获取所有用户的真实姓名和地址（可作为分类/标签展示）
$profile_stmt = $pdo->query("SELECT user_id, real_name, address FROM user_profiles");
$user_profiles = [];
foreach ($profile_stmt as $row) {
    $user_profiles[$row['user_id']] = [
        'real_name' => $row['real_name'],
        'address' => $row['address']
    ];
}

// 查询所有店铺信息
$shops_stmt = $pdo->query("SELECT s.*, u.username FROM shops s LEFT JOIN users u ON s.seller_id = u.id ORDER BY s.id ASC");
$shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 管理员视图内容 -->
<div class="col-12">
    <!-- 统计卡片 -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold text-primary">用户总数</div>
                    <div class="h4 mb-0 fw-bold text-secondary"><?= $totalUsers ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold text-primary">商家总数</div>
                    <div class="h4 mb-0 fw-bold text-warning"><?= $totalSellers ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold text-primary">商品总数</div>
                    <div class="h4 mb-0 fw-bold text-success"><?= $totalProducts ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold text-primary">商品分类数</div>
                    <div class="h4 mb-0 fw-bold text-info"><?= $totalCategories ?></div>
                </div>
            </div>
        </div>
    </div>


    <!-- 用户管理 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">用户管理（按类型分类）</div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="userTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">全部</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button" role="tab">普通用户</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="seller-tab" data-bs-toggle="tab" data-bs-target="#seller" type="button" role="tab">商家</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin" type="button" role="tab">管理员</button>
                </li>
            </ul>
            <div class="tab-content" id="userTabContent">
                <?php
                $roleMap = [
                    'all' => '全部',
                    'user' => '普通用户',
                    'seller' => '商家',
                    'admin' => '管理员'
                ];
                foreach ($roleMap as $roleKey => $roleName):
                ?>
                <div class="tab-pane fade<?= $roleKey === 'all' ? ' show active' : '' ?>" id="<?= $roleKey ?>" role="tabpanel">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>手机号</th>
                                <th>用户类型</th>
                                <th>注册时间</th>
                                <th>真实姓名</th>
                                <th>地址</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php if ($roleKey === 'all' || $user['role'] === $roleKey): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['phone']) ?></td>
                                    <td>
                                        <?php
                                        if ($user['role'] === 'admin') echo '<span class="badge bg-primary">管理员</span>';
                                        elseif ($user['role'] === 'seller') echo '<span class="badge bg-warning text-dark">商家</span>';
                                        else echo '<span class="badge bg-success">普通用户</span>';
                                        ?>
                                    </td>
                                    <td><?= $user['created_at'] ?></td>
                                    <td>
                                        <?= isset($user_profiles[$user['id']]['real_name']) && $user_profiles[$user['id']]['real_name'] ? htmlspecialchars($user_profiles[$user['id']]['real_name']) : '—' ?>
                                    </td>
                                    <td>
                                        <?= isset($user_profiles[$user['id']]['address']) && $user_profiles[$user['id']]['address'] ? htmlspecialchars($user_profiles[$user['id']]['address']) : '—' ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>