<?php
session_start();

// 退出登录（无条件执行，彻底清理会话）
if (isset($_GET['exit'])) {
    // 1. 清空当前会话所有变量
    $_SESSION = [];
    
    // 2. 销毁服务器端会话文件
    session_destroy();
    
    // 3. 删除客户端Session Cookie（关键！解决重新登录异常）
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // 提示并跳转
    echo "<script>alert('退出成功！');location.href='shop.php';</script>";
    exit;
}

// 未登录拦截（原有逻辑保留）
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('请先登录！');location.href='login.php';</script>";
    exit;
}

// 数据库配置
$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->set_charset("utf8mb4");

// 获取当前用户信息
$user = $conn->query("SELECT * FROM users WHERE user_id={$_SESSION['user_id']}")->fetch_assoc();

// 查询当前用户默认收货地址
$default_addr = $conn->query("SELECT * FROM user_address WHERE user_id={$_SESSION['user_id']} AND is_default=1 LIMIT 1")->fetch_assoc();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fa fa-user-circle"></i> 欢迎你，<?php echo $user['username']; ?></h3>
                </div>
                <div class="card-body p-4">
                    <h5 class="border-bottom pb-2 mb-3">📋 我的账户信息</h5>
                    <p><strong>真实姓名：</strong><?php echo $user['real_name']; ?></p>
                    <p><strong>手机号：</strong><?php echo $user['phone']; ?></p>

                    <!-- 默认地址+同行管理按钮布局 -->
                    <div class="d-flex align-items-center justify-content-between my-3 flex-wrap gap-2">
                        <div>
                            <strong>默认收货地址：</strong>
                            <?php if ($default_addr): ?>
                                <?php echo $default_addr['receiver_name'] . '，' . $default_addr['receiver_phone'] . '，' . $default_addr['address_detail']; ?>
                            <?php else: ?>
                                <span class="text-muted">暂未设置默认收货地址</span>
                            <?php endif; ?>
                        </div>
                        <a href="address.php" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-map-marker"></i> 管理我的收货地址
                        </a>
                    </div>

                    <h5 class="border-bottom pb-2 mb-3 mt-4">🚀 快捷操作</h5>
                    <div class="d-grid gap-2">
                        <a href="shop.php" class="btn btn-success btn-lg"><i class="fa fa-shopping-bag"></i> 进入商城购物</a>
                        <a href="user_center.php" class="btn btn-info btn-lg"><i class="fa fa-id-card"></i> 个人中心详情</a>
			<a href="my_goods.php" class="btn btn-dark btn-lg"><i class="fa fa-cubes"></i> 我的商品</a>
			<a href="my_order.php" class="btn btn-primary btn-lg"><i class="fa fa-list-alt"></i> 我的订单</a>
                        <a href="add_product.php" class="btn btn-warning btn-lg"><i class="fa fa-upload"></i> 上架我的商品</a>
                        <a href="?exit=1" class="btn btn-secondary btn-lg"><i class="fa fa-sign-out"></i> 退出登录</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $conn->close(); ?>