<?php
// 订单管理页面，支持分页和多角色视图
// 注意：所有跳转页面必须实际存在，避免404
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// 订单状态操作（管理员、商家、用户）
if (isset($_GET['action'], $_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];
    // 管理员和商家发货
    if ($action === 'shipped' && ($role === 'admin' || $role === 'seller')) {
        $stmt = $pdo->prepare("UPDATE orders SET status='shipped' WHERE id=?");
        $stmt->execute([$order_id]);
        header("Location: orders.php");
        exit();
    }
    // 商家同意退款
    if ($action === 'refund_agree' && $role === 'seller') {
        $stmt = $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?");
        $stmt->execute([$order_id]);
        header("Location: orders.php");
        exit();
    }
    // 用户申请退款
    if ($action === 'refund_apply' && $role === 'user') {
        $stmt = $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?");
        $stmt->execute([$order_id]);
        header("Location: orders.php");
        exit();
    }
    // 管理员订单状态操作（已支付、已完成、已取消）
    $allowed = ['paid', 'completed', 'cancelled'];
    if ($role === 'admin' && in_array($action, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$action, $order_id]);
        header("Location: orders.php");
        exit();
    }
}

include 'header.php';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

// 统计订单总数（用于分页）
if ($role === 'admin') {
    $total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} elseif ($role === 'seller') {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o
        JOIN order_details od ON od.order_id = o.id
        JOIN products pr ON od.product_id = pr.id
        WHERE pr.seller_id = ?");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();
}

// 查询订单及支付状态
if ($role === 'admin') {
    $sql = "SELECT o.*, u.username, p.status AS pay_status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN payments p ON p.order_id = o.id
            ORDER BY o.created_at DESC LIMIT $offset, $pageSize";
    $stmt = $pdo->query($sql);
} elseif ($role === 'seller') {
    $sql = "SELECT DISTINCT o.*, u.username, p.status AS pay_status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON od.order_id = o.id
            JOIN products pr ON od.product_id = pr.id
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE pr.seller_id = ?
            ORDER BY o.created_at DESC LIMIT $offset, $pageSize";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
} else {
    $sql = "SELECT o.*, p.status AS pay_status
            FROM orders o
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC LIMIT $offset, $pageSize";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查询订单详情（商品名、数量）
$orderIds = array_column($orders, 'id');
$orderDetails = [];
if ($orderIds) {
    $in = implode(',', array_map('intval', $orderIds));
    $detailsStmt = $pdo->query("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id IN ($in)");
    foreach ($detailsStmt as $row) {
        $orderDetails[$row['order_id']][] = $row;
    }
}
?>

<div class="col-12">
    <div class="card">
        <div class="card-header bg-primary text-white">订单管理</div>
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>订单号</th>
                        <?php if ($role === 'admin' || $role === 'seller'): ?>
                        <th>买家</th>
                        <?php endif; ?>
                        <th>商品名称</th>
                        <th>数量</th>
                        <th>金额</th>
                        <th>日期</th>
                        <th>支付情况</th>
                        <th>订单状态</th> <!-- 新增订单状态列 -->
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <?php
                        $details = $orderDetails[$order['id']] ?? [];
                        $first = true;
                        foreach ($details as $d):
                        ?>
                        <tr>
                            <?php if ($first): ?>
                            <td rowspan="<?= count($details) ?>"><?= $order['id'] ?></td>
                            <?php if ($role === 'admin' || $role === 'seller'): ?>
                            <td rowspan="<?= count($details) ?>"><?= htmlspecialchars($order['username'] ?? '') ?></td>
                            <?php endif; ?>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($d['name']) ?></td>
                            <td><?= $d['quantity'] ?></td>
                            <?php if ($first): ?>
                            <td rowspan="<?= count($details) ?>">¥<?= $order['total'] ?></td>
                            <td rowspan="<?= count($details) ?>"><?= $order['created_at'] ?></td>
                            <td rowspan="<?= count($details) ?>">
                                <?php
                                // 用支付状态（pay_status）来显示支付情况
                                if ($order['pay_status'] === 'success') {
                                    echo '<span class="badge bg-success">已支付</span>';
                                } elseif ($order['pay_status'] === 'pending' || $order['pay_status'] === null) {
                                    echo '<span class="badge bg-warning text-dark">待支付</span>';
                                } elseif ($order['pay_status'] === 'failed') {
                                    echo '<span class="badge bg-danger">支付失败</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">未知</span>';
                                }
                                ?>
                            </td>
                            <td rowspan="<?= count($details) ?>">
                                <!-- 新增订单状态显示 -->
                                <?php
                                $statusMap = [
                                    'pending' => ['text' => '待支付', 'class' => 'bg-warning text-dark'],
                                    'paid' => ['text' => '已支付', 'class' => 'bg-success'],
                                    'shipped' => ['text' => '已发货', 'class' => 'bg-primary'],
                                    'completed' => ['text' => '已完成', 'class' => 'bg-info'],
                                    'cancelled' => ['text' => '已取消', 'class' => 'bg-secondary'],
                                ];
                                $s = $order['status'];
                                $map = $statusMap[$s] ?? ['text' => $s, 'class' => 'bg-light'];
                                echo '<span class="badge ' . $map['class'] . '">' . $map['text'] . '</span>';
                                ?>
                            </td>
                            <td rowspan="<?= count($details) ?>">
                                <?php if ($role === 'user'): ?>
                                    <?php if ($order['pay_status'] === 'success'): ?>
                                        <a href="customer_service.php?type=refund&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger">申请退款</a>
                                    <?php elseif ($order['pay_status'] === 'pending' || $order['pay_status'] === null): ?>
                                        <a href="payments.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">去支付</a>
                                    <?php endif; ?>
                                <?php elseif ($role === 'seller'): ?>
                                    <?php if ($order['pay_status'] === 'pending' || $order['pay_status'] === 'shipped'): ?>
                                        <span class="text-muted">待买家支付</span>
                                    <?php elseif ($order['pay_status'] === 'success'): ?>
                                        <!-- 卖家发货 -->
                                        <a href="orders.php?action=shipped&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('确认发货？')">发货</a>
                                        <!-- 卖家同意退款 -->
                                        <a href="orders.php?action=refund_agree&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确认同意退款？')">同意退款</a>
                                    <?php endif; ?>
                                <?php elseif ($role === 'admin'): ?>
                                    <!-- 管理员可直接在本页管理订单状态 -->
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="orders.php?action=shipped&order_id=<?= $order['id'] ?>" class="btn btn-outline-primary" onclick="return confirm('确认设为已发货？')">设为已发货</a>
                                        <a href="orders.php?action=completed&order_id=<?= $order['id'] ?>" class="btn btn-outline-info" onclick="return confirm('确认设为已完成？')">设为已完成</a>
                                        <a href="orders.php?action=cancelled&order_id=<?= $order['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('确认取消订单？')">取消订单</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php $first = false; endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= ($role === 'admin' || $role === 'seller') ? 9 : 8 ?>" class="text-center">暂无订单</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= ceil($total / $pageSize); $i++): ?>
                        <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>