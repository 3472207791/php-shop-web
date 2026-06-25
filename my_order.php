<?php
session_start();
// 未登录拦截
if(!isset($_SESSION['user_id'])){
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

$user_id = $_SESSION['user_id'];
// 查询当前用户的所有订单 + 关联商品信息
$sql = "SELECT o.*, oi.buy_num, oi.item_price, p.product_name, p.pic 
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE o.buyer_id = $user_id 
        ORDER BY o.order_id DESC";
$result = $conn->query($sql);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fa fa-list-alt"></i> 我的订单</h4>
                </div>
                <div class="card-body">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($order = $result->fetch_assoc()): ?>
                        <div class="card mb-3 border">
                            <div class="card-header bg-light">
                                订单编号：<?=$order['order_id']?> 
                                <span class="badge bg-success float-end">已完成</span>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <img src="productIcons/<?=$order['pic']?>" width="80" height="80" class="me-3 object-fit-cover">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?=$order['product_name']?></h6>
                                    <p class="mb-0 small">单价：¥<?=$order['item_price']?> | 数量：<?=$order['buy_num']?> 件</p>
                                </div>
                                <div class="text-end">
                                    <h5 class="text-danger mb-0">¥<?=$order['total_amount']?></h5>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <i class="fa fa-shopping-cart"></i> 暂无订单记录，快去购物吧~
                        </div>
                    <?php endif; ?>

                    <!-- 快捷按钮 -->
                    <div class="d-grid gap-2 mt-3">
                        <a href="shop.php" class="btn btn-success">
                            <i class="fa fa-shopping-bag"></i> 继续购物
                        </a>
                        <a href="user_home.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> 返回个人主页
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>