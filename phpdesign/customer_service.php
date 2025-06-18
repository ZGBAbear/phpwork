<?php
// 客户服务请求页面：支持管理员、商家、普通用户不同操作
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// 用户通过订单“申请退款”入口自动生成退款服务请求
if ($role === 'user' && isset($_GET['type'], $_GET['order_id']) && $_GET['type'] === 'refund') {
    $order_id = intval($_GET['order_id']);
    // 检查该订单是否已存在退款请求，避免重复
    $stmt = $pdo->prepare("SELECT id FROM customer_service WHERE user_id=? AND order_id=? AND type='退款'");
    $stmt->execute([$user_id, $order_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO customer_service (user_id, order_id, type, content, status, created_at) VALUES (?, ?, '退款', '用户申请退款', 'pending', NOW())");
        $stmt->execute([$user_id, $order_id]);
    }
    header("Location: customer_service.php?msg=refund_applied");
    exit();
}

// 处理客户服务状态
if (($role === 'admin' || $role === 'seller') && isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $allowed = ['processing', 'resolved'];
    if (in_array($action, $allowed)) {
        $stmt = $pdo->prepare("UPDATE customer_service SET status=? WHERE id=?");
        $stmt->execute([$action, $id]);
        $redirect = "customer_service.php";
        if (isset($_GET['type'])) $redirect .= "?type=" . urlencode($_GET['type']);
        header("Location: $redirect");
        exit();
    }
}

// 处理商家服务状态
if ($role === 'admin' && isset($_GET['ss_action'], $_GET['ss_id'])) {
    $ss_id = intval($_GET['ss_id']);
    $ss_action = $_GET['ss_action'];
    $allowed = ['processing', 'resolved'];
    if (in_array($ss_action, $allowed)) {
        $stmt = $pdo->prepare("UPDATE seller_services SET status=? WHERE id=?");
        $stmt->execute([$ss_action, $ss_id]);
        header("Location: customer_service.php");
        exit();
    }
}



include 'header.php';

// 提示退款申请已提交
if (isset($_GET['msg']) && $_GET['msg'] === 'refund_applied') {
    echo '<div class="alert alert-success">退款申请已提交，客服会尽快处理！</div>';
}

$typeList = ['咨询','退货','退款','物流','投诉'];
$typeFilter = isset($_GET['type']) && in_array($_GET['type'], $typeList) ? $_GET['type'] : '';

// 查询客户服务请求（支持类型筛选）
if ($role === 'admin') {
    $sql = "SELECT cs.*, u.username FROM customer_service cs
            JOIN users u ON cs.user_id = u.id";
    $params = [];
    if ($typeFilter) {
        $sql .= " WHERE cs.type = ?";
        $params[] = $typeFilter;
    }
    $sql .= " ORDER BY cs.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} elseif ($role === 'seller') {
    $sql = "SELECT DISTINCT cs.*, u.username FROM customer_service cs
            JOIN users u ON cs.user_id = u.id
            JOIN orders o ON cs.order_id = o.id
            JOIN order_details od ON od.order_id = o.id
            JOIN products p ON od.product_id = p.id
            WHERE p.seller_id = ?";
    $params = [$user_id];
    if ($typeFilter) {
        $sql .= " AND cs.type = ?";
        $params[] = $typeFilter;
    }
    $sql .= " ORDER BY cs.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $sql = "SELECT cs.*, u.username FROM customer_service cs
            JOIN users u ON cs.user_id = u.id
            WHERE cs.user_id = ?";
    $params = [$user_id];
    if ($typeFilter) {
        $sql .= " AND cs.type = ?";
        $params[] = $typeFilter;
    }
    $sql .= " ORDER BY cs.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 商家服务模块（仅管理员和商家可见） ---
if ($role === 'admin') {
    // 管理员可查看所有商家服务
    $sql_seller = "SELECT ss.*, u.username FROM seller_services ss JOIN users u ON ss.seller_id = u.id ORDER BY ss.created_at DESC";
    $stmt_seller = $pdo->query($sql_seller);
    $seller_services = $stmt_seller->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'seller') {
    // 商家只看自己提交的
    $sql_seller = "SELECT ss.*, u.username FROM seller_services ss JOIN users u ON ss.seller_id = u.id WHERE ss.seller_id = ? ORDER BY ss.created_at DESC";
    $stmt_seller = $pdo->prepare($sql_seller);
    $stmt_seller->execute([$user_id]);
    $seller_services = $stmt_seller->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="col-12">
    <?php if ($role === 'user'): ?>
    <!-- 普通用户：提交新服务请求表单 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">提交服务请求</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">订单号（可选）</label>
                    <input type="number" name="order_id" class="form-control" placeholder="关联订单号">
                </div>
                <div class="col-md-3">
                    <label class="form-label">类型</label>
                    <select name="type" class="form-select" required>
                        <option value="">请选择</option>
                        <?php foreach ($typeList as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">内容</label>
                    <input type="text" name="content" class="form-control" required maxlength="100">
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">提交请求</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- 商家：提交商家服务请求表单 -->
    <?php if ($role === 'seller'): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">提交商家服务请求（如平台咨询、功能建议、费用问题等）</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">类型</label>
                    <select name="service_type" class="form-select" required>
                        <option value="">请选择</option>
                        <option value="平台咨询">平台咨询</option>
                        <option value="功能请求">功能请求</option>
                        <option value="费用问题">费用问题</option>
                        <option value="其它">其它</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">内容</label>
                    <input type="text" name="content" class="form-control" required maxlength="100">
                </div>
                <div class="col-12">
                    <button class="btn btn-warning" type="submit">提交商家服务</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- 管理员/商家：类型筛选 -->
    <?php if ($role === 'admin' || $role === 'seller'): ?>
    <form method="get" class="mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">按类型筛选：</label>
            </div>
            <div class="col-auto">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">全部类型</option>
                    <?php foreach ($typeList as $t): ?>
                    <option value="<?= $t ?>" <?= $typeFilter===$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <!-- 服务请求列表 -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">服务请求列表</div>
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>用户</th>
                        <th>订单号</th>
                        <th>类型</th>
                        <th>内容</th>
                        <th>状态</th>
                        <th>时间</th>
                        <?php if ($role === 'admin' || $role === 'seller'): ?>
                        <th>操作</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $srv): ?>
                    <tr>
                        <td><?= $srv['id'] ?></td>
                        <td><?= htmlspecialchars($srv['username']) ?></td>
                        <td><?= $srv['order_id'] ?: '-' ?></td>
                        <td><?= htmlspecialchars($srv['type']) ?></td>
                        <td><?= htmlspecialchars(mb_substr($srv['content'],0,30)) ?></td>
                        <td>
                            <?php
                            if ($srv['status'] === 'pending') echo '<span class="badge bg-warning text-dark">待处理</span>';
                            elseif ($srv['status'] === 'processing') echo '<span class="badge bg-primary">处理中</span>';
                            else echo '<span class="badge bg-success">已解决</span>';
                            ?>
                        </td>
                        <td><?= $srv['created_at'] ?></td>
                        <?php if (($role === 'admin' || $role === 'seller')): ?>
                        <td>
                            <?php if ($srv['status'] === 'pending'): ?>
                                <a href="?action=processing&id=<?= $srv['id'] ?><?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="btn btn-sm btn-primary">设为处理中</a>
                                <a href="?action=resolved&id=<?= $srv['id'] ?><?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="btn btn-sm btn-success">设为已解决</a>
                            <?php elseif ($srv['status'] === 'processing'): ?>
                                <a href="?action=resolved&id=<?= $srv['id'] ?><?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="btn btn-sm btn-success">设为已解决</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($services)): ?>
                    <tr><td colspan="<?= ($role === 'admin' || $role === 'seller') ? 8 : 7 ?>" class="text-center">暂无服务请求</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($role === 'admin' || $role === 'seller'): ?>
    <!-- 商家服务模块 -->
    <div class="card mt-5">
        <div class="card-header bg-warning text-dark">商家服务请求（平台与商家沟通/功能建议/费用问题等）</div>
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>商家</th>
                        <th>类型</th>
                        <th>内容</th>
                        <th>状态</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seller_services as $ss): ?>
                    <tr>
                        <td><?= $ss['id'] ?></td>
                        <td><?= htmlspecialchars($ss['username']) ?></td>
                        <td><?= htmlspecialchars($ss['service_type']) ?></td>
                        <td><?= htmlspecialchars(mb_substr($ss['content'],0,30)) ?></td>
                        <td>
                            <?php
                            if ($ss['status'] === 'pending') echo '<span class="badge bg-warning text-dark">待处理</span>';
                            elseif ($ss['status'] === 'processing') echo '<span class="badge bg-primary">处理中</span>';
                            else echo '<span class="badge bg-success">已解决</span>';
                            ?>
                        </td>
                        <td><?= $ss['created_at'] ?></td>
                        <td>
                            <?php if ($role === 'admin'): ?>
                                <?php if ($ss['status'] === 'pending'): ?>
                                    <a href="?ss_action=processing&ss_id=<?= $ss['id'] ?>" class="btn btn-sm btn-primary">设为处理中</a>
                                    <a href="?ss_action=resolved&ss_id=<?= $ss['id'] ?>" class="btn btn-sm btn-success">设为已解决</a>
                                <?php elseif ($ss['status'] === 'processing'): ?>
                                    <a href="?ss_action=resolved&ss_id=<?= $ss['id'] ?>" class="btn btn-sm btn-success">设为已解决</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($seller_services)): ?>
                    <tr><td colspan="7" class="text-center">暂无商家服务请求</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>