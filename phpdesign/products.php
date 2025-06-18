<?php
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// 确保uploads目录存在
$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 商家上传商品（审核状态为pending）
if ($role === 'seller' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = 'uploads/' . $filename;
        }
    }
    $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, description, price, stock, image_url, audit_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $name, $desc, $price, $stock, $image_url]);
    header("Location: products.php");
    exit();
}

// 商家编辑商品处理
if ($role === 'seller' && isset($_POST['edit_product'])) {
    $pid = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image_url = $_POST['old_image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = 'uploads/' . $filename;
        }
    }
    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image_url=?, audit_status='pending' WHERE id=? AND seller_id=?");
    $stmt->execute([$name, $desc, $price, $stock, $image_url, $pid, $user_id]);
    header("Location: products.php");
    exit();
}

// 管理员审核商品
if ($role === 'admin' && isset($_GET['audit'], $_GET['id'])) {
    $pid = intval($_GET['id']);
    $audit = $_GET['audit'];
    if (in_array($audit, ['approved', 'rejected'])) {
        $pdo->prepare("UPDATE products SET audit_status=? WHERE id=?")->execute([$audit, $pid]);
    }
    header("Location: products.php");
    exit();
}

// 下架/删除商品
if (isset($_GET['action'], $_GET['id'])) {
    $pid = intval($_GET['id']);
    if ($_GET['action'] === 'delete') {
        if ($role === 'admin') {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$pid]);
        } elseif ($role === 'seller') {
            $pdo->prepare("DELETE FROM products WHERE id=? AND seller_id=?")->execute([$pid, $user_id]);
        }
        header("Location: products.php");
        exit();
    }
}

include 'header.php';

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 8;
$offset = ($page - 1) * $pageSize;

// 商品查询逻辑
$where = [];
$params = [];
if ($role === 'seller') {
    // 商家只能管理自己的商品
    $where[] = "seller_id = ?";
    $params[] = $user_id;
} elseif ($role === 'admin') {
    // 管理员可筛选审核状态
    if (isset($_GET['audit_status'])) {
        $where[] = "audit_status = ?";
        $params[] = $_GET['audit_status'];
    }
} else {
    // 普通用户：只看审核通过商品
    $where[] = "audit_status = 'approved'";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// 查询总数
$total = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
$total->execute($params);
$totalCount = $total->fetchColumn();

// 查询商品
$sql = "SELECT * FROM products $whereSql ORDER BY id DESC LIMIT $offset, $pageSize";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 商家商品管理界面（简洁风格，适合学生项目） -->
<?php if ($role === 'seller'): ?>
<div class="container my-4">
    <!-- 上传商品 -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            上传新商品
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">商品名称</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">价格</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">库存</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">商品图片</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required onchange="previewImg(event)">
                        <img id="imgPreview" style="width:40px;height:30px;object-fit:cover;display:none;" class="rounded mt-2 border" onerror="this.style.display='none';">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_product" class="btn btn-success w-100">上传</button>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">商品描述</label>
                    <textarea name="description" class="form-control" required></textarea>
                </div>
            </form>
        </div>
    </div>
    <script>
    function previewImg(e) {
        // 选择图片后也不显示预览
        document.getElementById('imgPreview').style.display = 'none';
    }
    </script>
    <!-- 商品管理表格 -->
    <div class="card">
        <div class="card-header bg-light">
            我的商品管理
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">图片</th>
                        <th>商品名称</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>审核状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <?php
                        $img_path = (!empty($product['image_url']) && file_exists(__DIR__ . '/' . $product['image_url']))
                            ? htmlspecialchars($product['image_url'])
                            : 'https://via.placeholder.com/60x40?text=No+Image';
                        ?>
                        <img src="<?= $img_path ?>" class="rounded border" style="width:60px;height:40px;object-fit:cover;">
                    </td>
                    <td>
                        <?= htmlspecialchars($product['name']) ?>
                        <div class="text-muted small"><?= mb_strimwidth(htmlspecialchars($product['description']), 0, 32, '...') ?></div>
                    </td>
                    <td>¥<?= htmlspecialchars($product['price']) ?></td>
                    <td><?= htmlspecialchars($product['stock']) ?></td>
                    <td>
                        <?php
                        $auditMap = [
                            'pending' => ['text' => '待审核', 'class' => 'badge bg-warning text-dark'],
                            'approved' => ['text' => '已通过', 'class' => 'badge bg-success'],
                            'rejected' => ['text' => '未通过', 'class' => 'badge bg-danger'],
                        ];
                        $a = $product['audit_status'] ?? 'pending';
                        $amap = $auditMap[$a] ?? ['text' => $a, 'class' => 'badge bg-secondary'];
                        echo '<span class="' . $amap['class'] . '">' . $amap['text'] . '</span>';
                        ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                        <a href="?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定下架该商品？')">下架</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">暂无商品</td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            <!-- 分页 -->
            <nav>
              <ul class="pagination justify-content-center my-3">
                <?php for ($i = 1; $i <= ceil($totalCount / $pageSize); $i++): ?>
                  <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
              </ul>
            </nav>
        </div>
    </div>
    <?php
        // 编辑弹窗逻辑
        if (isset($_GET['edit'])):
            $edit_id = intval($_GET['edit']);
            $edit_stmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND seller_id=?");
            $edit_stmt->execute([$edit_id, $user_id]);
            $edit_product = $edit_stmt->fetch(PDO::FETCH_ASSOC);
            if ($edit_product):
    ?>
    <!-- 编辑商品模态框 -->
    <div class="modal fade show" style="display:block; background:rgba(0,0,0,0.3);" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">编辑商品</h5>
              <a href="products.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
              <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
              <input type="hidden" name="old_image_url" value="<?= htmlspecialchars($edit_product['image_url']) ?>">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">商品名称</label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_product['name']) ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">价格</label>
                  <input type="number" name="price" class="form-control" value="<?= $edit_product['price'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">库存</label>
                  <input type="number" name="stock" class="form-control" value="<?= $edit_product['stock'] ?>" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">商品描述</label>
                <textarea name="description" class="form-control" rows="2" required><?= htmlspecialchars($edit_product['description']) ?></textarea>
              </div>
              <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                  <label class="form-label">商品图片</label>
                  <input type="file" name="image" class="form-control" accept="image/*" onchange="editPreviewImg(event)">
                </div>
                <div class="col-md-6">
                  <?php
                  $editImg = (!empty($edit_product['image_url']) && file_exists(__DIR__ . '/' . $edit_product['image_url']))
                      ? htmlspecialchars($edit_product['image_url'])
                      : 'https://via.placeholder.com/120x80?text=No+Image';
                  ?>
                  <img id="editImgPreview" src="<?= $editImg ?>" class="rounded border" style="width:120px;height:80px;object-fit:cover;">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" name="edit_product" class="btn btn-primary">保存修改</button>
              <a href="products.php" class="btn btn-secondary">取消</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
    function editPreviewImg(e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('editImgPreview').src = URL.createObjectURL(file);
        }
    }
    </script>
    <style>
    body { overflow: hidden; }
    .modal-content { border-radius: 10px; }
    </style>
    <?php
            endif;
        endif;
    ?>
</div>
<?php endif; ?>

<?php
// 管理员和普通用户界面
if ($role === 'admin' || $role === 'user'):
?>
<div class="container my-4">
    <?php if ($role === 'admin'): ?>
    <!-- 管理员管理商品，显示管理表格和审核按钮 -->
    <div class="mb-3">
        <a href="products.php" class="btn btn-outline-primary btn-sm<?= !isset($_GET['audit_status']) ? ' active' : '' ?>">全部</a>
        <a href="products.php?audit_status=pending" class="btn btn-outline-warning btn-sm<?= ($_GET['audit_status'] ?? '') === 'pending' ? ' active' : '' ?>">待审核</a>
        <a href="products.php?audit_status=approved" class="btn btn-outline-success btn-sm<?= ($_GET['audit_status'] ?? '') === 'approved' ? ' active' : '' ?>">已通过</a>
        <a href="products.php?audit_status=rejected" class="btn btn-outline-danger btn-sm<?= ($_GET['audit_status'] ?? '') === 'rejected' ? ' active' : '' ?>">未通过</a>
    </div>
    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold">商品管理</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>图片</th>
                            <th>商品ID</th>
                            <th>商品名称</th>
                            <th>价格</th>
                            <th>库存</th>
                            <th>审核状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/60') ?>" style="width:60px;height:40px;object-fit:cover;"></td>
                            <td><?= $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>¥<?= htmlspecialchars($product['price']) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td>
                                <?php
                                $auditMap = [
                                    'pending' => ['text' => '待审核', 'class' => 'badge bg-warning text-dark'],
                                    'approved' => ['text' => '已通过', 'class' => 'badge bg-success'],
                                    'rejected' => ['text' => '未通过', 'class' => 'badge bg-danger'],
                                ];
                                $a = $product['audit_status'] ?? 'pending';
                                $amap = $auditMap[$a] ?? ['text' => $a, 'class' => 'badge bg-secondary'];
                                echo '<span class="' . $amap['class'] . '">' . $amap['text'] . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($product['audit_status'] === 'pending'): ?>
                                    <a href="?audit=approved&id=<?= $product['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('确认审核通过？')">通过</a>
                                    <a href="?audit=rejected&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认驳回？')">驳回</a>
                                <?php elseif ($product['audit_status'] === 'rejected'): ?>
                                    <a href="?audit=approved&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('确认重新上架？')">重新上架</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除该商品？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- 分页 -->
                <nav>
                  <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= ceil($totalCount / $pageSize); $i++): ?>
                      <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>
                  </ul>
                </nav>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- 普通用户购物卡片风格，显示商品所属店铺 -->
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
                <img src="<?= htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200/22b573/ffffff?text=' . urlencode($product['name'])) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
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
                        <a href="cart.php?product_id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-cart-plus"></i> 加入购物车</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <div class="col-12 text-center text-muted py-5">暂无商品</div>
        <?php endif; ?>
    </div>
    <!-- 分页 -->
    <nav>
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= ceil($totalCount / $pageSize); $i++): ?>
          <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
</div>
<?php  endif; ?>
<?php include 'footer.php'; ?>
