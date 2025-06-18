<?php
// 商家首页，只允许商家访问，展示统计和商品管理
include 'config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}
include 'header.php';
$seller_id = $_SESSION['user_id'];

// 查询商家商品数量
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=?");
$stmt->execute([$seller_id]);
$product_count = $stmt->fetchColumn();

// 查询商家订单数量和总销售额
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) as order_count, IFNULL(SUM(o.total),0) as total_sales
    FROM orders o
    JOIN order_details od ON od.order_id = o.id
    JOIN products p ON od.product_id = p.id
    WHERE p.seller_id = ?
");
$stmt->execute([$seller_id]);
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 查询商家商品列表
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id=? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="col-12">
    <!-- 商家统计卡片 -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fw-bold fs-5">商品数量</div>
                    <div class="display-6"><?= $product_count ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fw-bold fs-5">订单总数</div>
                    <div class="display-6"><?= $order_stats['order_count'] ?? 0 ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fw-bold fs-5">总销售额</div>
                    <div class="display-6">¥<?= $order_stats['total_sales'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 商品管理列表 -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            我的商品
            <a href="products.php" class="btn btn-sm btn-light float-end">管理商品</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>商品名称</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products): ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>¥<?= $p['price'] ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td><?= $p['created_at'] ?></td>
                            <td>
                                <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-primary">编辑</a>
                                <a href="products.php?del=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除该商品吗？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">暂无商品</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>