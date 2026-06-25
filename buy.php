<?php
session_start();
// 未登录拦截
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('请先登录！');location.href='login.php';</script>";
    exit;
}
$buyer_uid = $_SESSION['user_id'];

$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->set_charset("utf8mb4");

// 获取商品ID
$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pid == 0) {
    echo "<script>alert('商品不存在');location.href='shop.php';</script>";
    exit;
}

// 查询商品信息
$prod = $conn->query("SELECT * FROM products WHERE product_id=$pid AND status='on'")->fetch_assoc();
if (!$prod) {
    echo "<script>alert('商品已下架或不存在');location.href='shop.php';</script>";
    exit;
}
if ($prod['stock'] <= 0) {
    echo "<script>alert('商品缺货，无法购买');location.href='shop.php';</script>";
    exit;
}

// 提交订单处理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $buy_num = intval($_POST['buy_num']);
    if ($buy_num <= 0 || $buy_num > $prod['stock']) {
        echo "<script>alert('购买数量非法');history.back();</script>";
        exit;
    }
    $total_money = $buy_num * $prod['price'];

    // 1. 插入主订单，字段改为 buyer_id 适配你的数据表
    $order_sql = "INSERT INTO orders(buyer_id,total_amount,order_status,create_time) VALUES($buyer_uid,$total_money,'unpay',NOW())";
    $conn->query($order_sql);
    $order_id = $conn->insert_id;

    // 2. 插入订单项
    $item_sql = "INSERT INTO order_items(order_id,product_id,buy_num,item_price) VALUES($order_id,$pid,$buy_num,{$prod['price']})";
    $conn->query($item_sql);

    // 3. 扣减商品库存
    $conn->query("UPDATE products SET stock = stock - $buy_num WHERE product_id=$pid");

    // ========== 下单推送卖家消息核心代码 ==========
    $seller_uid = $prod['user_id'];
    $p_name = $prod['product_name'];
    $msg_title = "新订单提醒";
    $msg_content = "您有一条【{$p_name}】的订单，请及时处理发货";
    $conn->query("INSERT INTO user_message(recv_uid,msg_title,msg_content) VALUES($seller_uid,'$msg_title','$msg_content')");
    // =============================================

    echo "<script>alert('下单成功！');location.href='my_order.php';</script>";
    exit;
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fa fa-shopping-cart"></i> 商品下单结算</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="productIcons/<?=$prod['pic']?>" style="height:220px;object-fit:cover;">
                    </div>
                    <h4 class="text-center"><?=$prod['product_name']?></h4>
                    <p class="text-center text-danger fs-4 fw-bold">单价：¥<?=$prod['price']?></p>
                    <p class="text-center text-muted">当前库存：<?=$prod['stock']?> 件</p>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">购买数量</label>
                            <input type="number" min="1" max="<?=$prod['stock']?>" value="1" name="buy_num" class="form-control" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">提交订单</button>
                            <a href="shop.php" class="btn btn-secondary">返回商城首页</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>