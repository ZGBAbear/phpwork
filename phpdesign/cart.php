<?php
// 购物车页面，整合风格，避免重复，动态展示购物车内容
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 处理加入购物车
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cartItem) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$cartItem['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $product_id]);
    }
    header("Location: cart.php");
    exit();
}

// 处理数量修改
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $cart_id => $qty) {
        $qty = max(1, intval($qty));
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$qty, $cart_id, $user_id]);
    }
    header("Location: cart.php");
    exit();
}

// 处理删除
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    header("Location: cart.php");
    exit();
}

// 处理结算
if (isset($_POST['checkout']) && !empty($_POST['select_cart']) && !empty($_POST['pay_method'])) {
    $pay_method = $_POST['pay_method'];
    $cart_ids = array_map('intval', $_POST['select_cart']);
    $in = str_repeat('?,', count($cart_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND c.id IN ($in)");
    $stmt->execute(array_merge([$user_id], $cart_ids));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'paid')");
        $stmt->execute([$user_id, $total]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // 直接支付成功
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, ?, 'success')");
        $stmt->execute([$order_id, $total, $pay_method]);
        $payment_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND id IN ($in)");
        $stmt->execute(array_merge([$user_id], $cart_ids));

        $pdo->commit();

        // 跳转到支付记录页面并提示
        header("Location: payments.php?payid=$payment_id&success=1");
        exit();
    }
}

include 'header.php';


// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

// 查询购物车商品总数
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$totalStmt->execute([$user_id]);
$totalCount = $totalStmt->fetchColumn();

// 查询购物车商品
$stmt = $pdo->prepare("SELECT c.id, c.quantity, p.name, p.price 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?
                        LIMIT $offset, $pageSize");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 计算总价
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="col-12">
    <div class="card">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold">我的购物车</h6>
        </div>
        <div class="card-body">
            <?php if (count($cartItems) > 0): ?>
            <form method="post">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="select_all"></th>
                            <th>商品名称</th>
                            <th>数量</th>
                            <th>单价</th>
                            <th>小计</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="select_cart[]" value="<?= $row['id'] ?>"></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td style="width:120px;">
                                <input type="number" name="qty[<?= $row['id']; ?>]" value="<?= $row['quantity']; ?>" min="1" class="form-control form-control-sm" style="width:70px;display:inline-block;">
                            </td>
                            <td>¥<?= $row['price']; ?></td>
                            <td>¥<?= $row['price'] * $row['quantity']; ?></td>
                            <td>
                                <a href="cart.php?remove=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除吗？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">总计:</td>
                            <td class="fw-bold">¥<?= $total; ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                <script>
                document.getElementById('select_all').onclick = function() {
                    var cbs = document.querySelectorAll('input[name="select_cart[]"]');
                    for (var i = 0; i < cbs.length; i++) cbs[i].checked = this.checked;
                };
                </script>
                <!-- 分页 -->
                <nav>
                  <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= ceil($totalCount / $pageSize); $i++): ?>
                      <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                      </li>
                    <?php endfor; ?>
                  </ul>
                </nav>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">支付方式：</label>
                <div>
                    <label class="me-3"><input type="radio" name="pay_method" value="微信" required> 微信</label>
                    <label class="me-3"><input type="radio" name="pay_method" value="支付宝"> 支付宝</label>
                    <label class="me-3"><input type="radio" name="pay_method" value="QQ"> QQ</label>
                    <label class="me-3"><input type="radio" name="pay_method" value="银联"> 银联</label>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" name="update_cart" class="btn btn-success">更新数量</button>
                <button type="submit" name="checkout" class="btn btn-primary btn-lg">结算所选</button>
            </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">购物车为空，<a href="products.php">去选购商品</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>