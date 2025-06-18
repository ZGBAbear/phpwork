<?php
// 支付记录管理页面，支持多角色视图
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// 删除支付记录（同步订单状态为待支付）
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = intval($_GET['del']);
    // 查找对应订单
    $stmt = $pdo->prepare("SELECT order_id FROM payments WHERE id=?");
    $stmt->execute([$del_id]);
    $order_id = $stmt->fetchColumn();
    if ($order_id) {
        // 删除支付记录
        $pdo->prepare("DELETE FROM payments WHERE id=?")->execute([$del_id]);
        // 同步订单状态为待支付
        $pdo->prepare("UPDATE orders SET status='pending' WHERE id=?")->execute([$order_id]);
    }
    header("Location: payments.php");
    exit();
}

include 'header.php';

// 查询支付记录
if ($role === 'admin') {
    // 管理员：所有支付记录，带用户名
    $sql = "SELECT p.*, u.username FROM payments p
            JOIN orders o ON p.order_id = o.id
            JOIN users u ON o.user_id = u.id
            ORDER BY p.created_at DESC";
    $stmt = $pdo->query($sql);
} elseif ($role === 'seller') {
    // 商家：只看与自己商品相关的支付记录
    $sql = "SELECT DISTINCT p.*, u.username FROM payments p
            JOIN orders o ON p.order_id = o.id
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON od.order_id = o.id
            JOIN products pr ON od.product_id = pr.id
            WHERE pr.seller_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
} else {
    // 普通用户：只看自己的支付记录
    $sql = "SELECT p.* FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE o.user_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="col-12">
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">支付记录</div>
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>支付ID</th>
                        <th>订单号</th>
                        <?php if ($role === 'admin' || $role === 'seller'): ?>
                        <th>用户</th>
                        <?php endif; ?>
                        <th>金额</th>
                        <th>支付方式</th>
                        <th>状态</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments): foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= $pay['id'] ?></td>
                        <td><?= $pay['order_id'] ?></td>
                        <?php if ($role === 'admin' || $role === 'seller'): ?>
                        <td><?= htmlspecialchars($pay['username'] ?? '') ?></td>
                        <?php endif; ?>
                        <td>¥<?= $pay['amount'] ?></td>
                        <td><?= htmlspecialchars($pay['method']) ?></td>
                        <td>
                            <?php
                            // 状态badge与orders.php一致
                            $status = $pay['status'];
                            if ($status === 'success') echo '<span class="badge bg-success">已支付</span>';
                            elseif ($status === 'pending') echo '<span class="badge bg-warning text-dark">待支付</span>';
                            elseif ($status === 'failed') echo '<span class="badge bg-danger">支付失败</span>';
                            else echo '<span class="badge bg-secondary">未知</span>';
                            ?>
                        </td>
                        <td><?= $pay['created_at'] ?></td>
                        <td>
                            <a href="payments.php?del=<?= $pay['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除该支付记录吗？')">删除</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="<?= ($role === 'admin' || $role === 'seller') ? 8 : 7 ?>" class="text-center">暂无支付记录</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>