<?php
session_start();
// 禁用浏览器缓存，保证登录状态实时更新
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 数据库配置
$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->connect_error && die("数据库连接失败：" . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 缺货提醒提交处理
if(isset($_GET['remind_pid'])){
    if(!isset($_SESSION['user_id'])){
        echo "<script>alert('请先登录！');location.href='login.php';</script>";
        exit;
    }
    $pid = intval($_GET['remind_pid']);
    $uid = $_SESSION['user_id'];
    // 插入提醒，唯一键防止重复提交
    $ins_sql = "INSERT IGNORE INTO stock_remind(user_id,product_id) VALUES($uid,$pid)";
    if($conn->query($ins_sql)){
        echo "<script>alert('已设置到货提醒，商品补货后会通知您！');location.href='shop.php';</script>";
    }else{
        echo "<script>alert('您已经设置过该商品提醒了！');location.href='shop.php';</script>";
    }
    exit;
}

// 统计当前登录用户 未读消息数量
$unread_count = 0;
if(isset($_SESSION['user_id'])){
    $res_msg = $conn->query("SELECT COUNT(*) cnt FROM user_message WHERE recv_uid={$_SESSION['user_id']} AND is_read=0");
    $unread_count = $res_msg->fetch_assoc()['cnt'];
}

// 查询上架商品
$prod_sql = "SELECT p.*,u.username FROM products p LEFT JOIN users u ON p.user_id=u.user_id WHERE p.status='on'";
$prod_res = $conn->query($prod_sql);

// 全站总销售额
$total_sql = "SELECT SUM(total_amount) AS all_sale FROM orders WHERE order_status='finish'";
$total_row = $conn->query($total_sql)->fetch_assoc();

// 卖家销售额排行
$user_sale_sql = "SELECT u.username,SUM(oi.buy_num*oi.item_price) AS sale_money
FROM users u
JOIN products p ON u.user_id=p.user_id
JOIN order_items oi ON p.product_id=oi.product_id
JOIN orders o ON oi.order_id=o.order_id
WHERE o.order_status='finish'
GROUP BY u.user_id ORDER BY sale_money DESC";
$sale_res = $conn->query($user_sale_sql);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
<style>
.badge-notice{
    position:relative;
    top:-12px;
    left:-8px;
    font-size:12px;
}
</style>

<div class="bg-light p-2 text-center">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span>欢迎回来，<?php echo $_SESSION['username']; ?></span>
        <a href="user_home.php?exit=1" class="btn btn-sm btn-outline-danger ms-2">退出登录</a>
    <?php else: ?>
        <span>您当前未登录</span>
        <a href="login.php" class="btn btn-sm btn-primary ms-2">登录</a>
        <a href="reg.php" class="btn btn-sm btn-outline-secondary ms-1">注册</a>
    <?php endif; ?>
</div>

<nav class="navbar navbar-expand-lg bg-primary navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fs-3" href="#"><i class="fa fa-shopping-bag"></i> 优品线上商城</a>
        <div class="d-flex gap-3 align-items-center">
            <a href="reg.php" class="btn btn-light"><i class="fa fa-user-plus"></i> 用户注册</a>
            <a href="javascript:checkLogin()" class="btn btn-light"><i class="fa fa-cubes"></i> 商品上架</a>
            <a href="javascript:checkLogin()" class="btn btn-light"><i class="fa fa-file-text"></i> 用户主页</a>
            <a href="sale_stat.php" class="btn btn-warning"><i class="fa fa-line-chart"></i> 销售统计</a>
            <!-- 新增系统信息按钮 + 未读角标 -->
            <div class="position-relative d-inline-block">
                <a href="message.php" class="btn btn-info text-white"><i class="fa fa-bell"></i> 系统信息</a>
                <?php if($unread_count > 0): ?>
                    <span class="badge bg-danger badge-notice"><?=$unread_count?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fa fa-list-ul"></i> 功能菜单栏</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-outline-primary"><i class="fa fa-exchange"></i> 切换账号</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary"><i class="fa fa-user"></i> 账号登录</a>
                        <?php endif; ?>
                        <a href="javascript:checkLogin()" class="btn btn-outline-success"><i class="fa fa-cubes"></i> 我的商品</a>
                        <a href="javascript:checkLogin()" class="btn btn-outline-info"><i class="fa fa-shopping-cart"></i> 我的购物车</a>
                        <a href="javascript:checkLogin()" class="btn btn-outline-dark"><i class="fa fa-bar-chart"></i> 个人营收</a>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <p class="mb-0 text-success">🏆 全站总销售额：<?php echo $total_row['all_sale'] ?: 0; ?> 元</p>
                </div>
            </div>

            <div class="card mt-4 shadow" id="stat">
                <div class="card-header bg-success text-white">
                    <h5><i class="fa fa-trophy"></i> 卖家销售额排行榜</h5>
                </div>
                <div class="card-body">
                    <?php while ($s = $sale_res->fetch_assoc()): ?>
                        <p class="border-bottom pb-2 mb-2">
                            <i class="fa fa-user-circle text-primary"></i>
                            <?php echo $s['username']; ?>：<span class="text-danger fw-bold"><?php echo $s['sale_money'] ?: 0; ?>元</span>
                        </p>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <h3 class="border-start border-4 border-primary ps-3 mb-4">在售商品 <i class="fa fa-gift text-danger"></i></h3>
            <div class="row g-4">
                <?php while ($goods = $prod_res->fetch_assoc()): ?>
                    <?php
                    // 判断是否缺货
                    $is_out = ($goods['stock'] <= 0);
                    $card_class = $is_out ? 'card h-100 shadow-sm bg-secondary-subtle text-muted' : 'card h-100 shadow-sm';
                    ?>
                    <div class="col-md-4">
                        <div class="<?= $card_class ?>">
                            <img src="productIcons/<?= $goods['pic'] ?>" class="card-img-top <?= $is_out ? 'opacity-50' : '' ?>" alt="<?= $goods['product_name'] ?>" style="height:200px;object-fit:cover">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= $goods['product_name'] ?>
                                    <?php if($is_out): ?>
                                        <span class="badge bg-danger ms-2">缺货</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="text-danger fs-5 fw-bold <?= $is_out ? 'text-muted' : '' ?>">￥<?php echo $goods['price']; ?></p>
                                <p class="card-text small text-muted">库存：<?php echo $goods['stock']; ?>件 | 卖家：<?php echo $goods['username']; ?></p>
                                <p class="card-text small"><?php echo mb_substr($goods['product_desc'], 0, 25); ?>...</p>
                            </div>
                            <div class="card-footer bg-white">
                                <?php if(!$is_out): ?>
                                    <button onclick="buyProduct(<?php echo $goods['product_id']; ?>)" class="btn btn-danger w-100"><i class="fa fa-cart-plus"></i> 立即购买</button>
                                <?php else: ?>
                                    <button onclick="remindMe(<?= $goods['product_id'] ?>)" class="btn btn-outline-warning w-100">
                                        <i class="fa fa-bell"></i> 缺货物品上架提醒我
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-3 bg-dark text-white text-center">
    优品商城 ©2026 实训项目 | 基于WAMP+MariaDB开发
</footer>

<script>
// 登录校验：已登录跳转 我的商品页面 my_goods.php
function checkLogin() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('请先登录，再进行此操作！');
        window.location.href = 'login.php';
    <?php else: ?>
        window.location.href = 'my_goods.php';
    <?php endif; ?>
}

// 带有商品ID传参的购买逻辑
function buyProduct(productId) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('请先登录，再进行购买！');
        window.location.href = 'login.php';
    <?php else: ?>
        window.location.href = 'buy.php?id=' + productId;
    <?php endif; ?>
}

// 缺货提醒跳转
function remindMe(pid){
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('请先登录后设置到货提醒！');
        window.location.href = 'login.php';
    <?php else: ?>
        location.href = 'shop.php?remind_pid=' + pid;
    <?php endif; ?>
}
</script>

<?php $conn->close(); ?>