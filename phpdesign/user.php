<?php
include 'config.php';
session_start();
// 只允许普通用户访问，其他角色自动跳转
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    // 学生项目：不同角色跳转到各自首页，避免404
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') header("Location: admin.php");
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller') header("Location: seller.php");
    exit();
}
include 'header.php';

// 分类搜索处理
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search = trim($_GET['search'] ?? '');

// 获取所有一级分类（parent_id为NULL）
$cat_stmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY id");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有二级分类（parent_id不为NULL）
$subcat_stmt = $pdo->query("SELECT id, name, parent_id FROM categories WHERE parent_id IS NOT NULL ORDER BY parent_id, id");
$subcategories = $subcat_stmt->fetchAll(PDO::FETCH_ASSOC);

// 构建商品查询条件
$where = "WHERE audit_status='approved'";
$params = [];
if ($category_id > 0) {
    // 支持一级和二级分类筛选
    $where .= " AND category_id=?";
    $params[] = $category_id;
}
if ($search !== '') {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
}

// 查询所有商品（学生项目：不分页，全部显示）
$sql = "SELECT * FROM products $where ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-4">
    <!-- 分类和搜索框 -->
    <form class="row mb-3" method="get" action="user.php">
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <option value="0">全部分类</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"<?= $category_id == $cat['id'] ? ' selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php foreach ($subcategories as $sub): ?>
                        <?php if ($sub['parent_id'] == $cat['id']): ?>
                            <option value="<?= $sub['id'] ?>"<?= $category_id == $sub['id'] ? ' selected' : '' ?>>&nbsp;&nbsp;└ <?= htmlspecialchars($sub['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="搜索商品名称" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-success w-100" type="submit">搜索</button>
        </div>
    </form>

    <!-- 商品列表 -->
    <div class="row">
        <?php foreach ($products as $product): ?>
        <?php
            // 获取商品所属店铺信息
            $shop_stmt = $pdo->prepare("SELECT s.shop_name, s.contact_phone FROM shops s WHERE s.seller_id = ?");
            $shop_stmt->execute([$product['seller_id']]);
            $shop = $shop_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm">
                <img src="<?= htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200/22b573/ffffff?text=' . urlencode($product['name'])) ?>"
                     class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                    <?php if ($shop): ?>
                        <div class="mb-2 small text-muted">
                            店铺：<?= htmlspecialchars($shop['shop_name']) ?><br>
                            联系方式：<?= htmlspecialchars($shop['contact_phone']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="color:#22b573;">¥<?= $product['price'] ?></span>
                        <!-- 跳转到购物车页面，cart.php已存在 -->
                        <a href="cart.php?product_id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-success" title="加入购物车">
                            <i class="fas fa-cart-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <div class="col-12 text-center text-muted py-5">暂无商品</div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>


