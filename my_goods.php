<?php
session_start();

// 未登录拦截
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

// 获取当前登录用户ID
$user_id = $_SESSION['user_id'];

// 1. 增加库存处理
if (isset($_POST['add_stock'])) {
    $pid = intval($_POST['product_id']);
    $num = intval($_POST['num']);
    
    if ($num > 0) {
        // 先查询修改前库存
        $oldStockRow = $conn->query("SELECT stock, product_name FROM products WHERE product_id=$pid AND user_id=$user_id")->fetch_assoc();
        $oldStock = $oldStockRow['stock'];
        $prodName = $oldStockRow['product_name'];
        
        // 更新库存
        $conn->query("UPDATE products SET stock = stock + $num WHERE product_id=$pid AND user_id=$user_id");
        
        // 【核心逻辑】原库存=0，补货后推送订阅用户消息
        if ($oldStock == 0) {
            $remindUsers = $conn->query("SELECT DISTINCT user_id FROM stock_remind WHERE product_id=$pid");
            while ($u = $remindUsers->fetch_assoc()) {
                $uid = $u['user_id'];
                $conn->query("INSERT INTO user_message(recv_uid, msg_title, msg_content) 
                              VALUES($uid, '商品补货通知', '您关注的商品【{$prodName}】已补货，可以前往购买')");
            }
        }
    }
    
    echo "<script>location.href='my_goods.php';</script>";
    exit;
}

// 2. 减少库存处理
if (isset($_POST['sub_stock'])) {
    $pid = intval($_POST['product_id']);
    $num = intval($_POST['num']);
    
    if ($num > 0) {
        // 先查询当前库存，防止扣成负数
        $res = $conn->query("SELECT stock FROM products WHERE product_id=$pid AND user_id=$user_id");
        $row = $res->fetch_assoc();
        
        if ($row['stock'] >= $num) {
            $conn->query("UPDATE products SET stock = stock - $num WHERE product_id=$pid AND user_id=$user_id");
        } else {
            echo "<script>alert('库存不足，无法减少！');location.href='my_goods.php';</script>";
            exit;
        }
    }
    
    echo "<script>location.href='my_goods.php';</script>";
    exit;
}

// 3. 下架商品处理
if (isset($_POST['off_shelf'])) {
    $pid = intval($_POST['product_id']);
    $conn->query("UPDATE products SET status='off' WHERE product_id=$pid AND user_id=$user_id");
    echo "<script>location.href='my_goods.php';</script>";
    exit;
}

// 4. 重新上架处理逻辑
if (isset($_POST['on_shelf'])) {
    $pid = intval($_POST['product_id']);
    $conn->query("UPDATE products SET status='on' WHERE product_id=$pid AND user_id=$user_id");
    echo "<script>location.href='my_goods.php';</script>";
    exit;
}

// 查询【自己】上架的所有商品（含已售数量统计）
$sql = "SELECT p.*, COALESCE(SUM(oi.buy_num), 0) AS sold_num
        FROM products p 
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id AND o.order_status = 'finish'
        WHERE p.user_id = $user_id
        GROUP BY p.product_id";
$result = $conn->query($sql);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fa fa-cubes"></i> 我的商品管理</h4>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while ($goods = $result->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <img src="productIcons/<?php echo $goods['pic']; ?>" 
                                     class="card-img-top" 
                                     style="height: 150px; object-fit: cover">
                                
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $goods['product_name']; ?></h6>
                                    <p class="text-danger fw-bold mb-1">¥<?php echo $goods['price']; ?></p>
                                    <p class="small text-muted mb-2">
                                        库存：<?php echo $goods['stock']; ?>件 
                                        | 已售：<?php echo $goods['sold_num']; ?>件
                                    </p>
                                    
                                    <span class="badge bg-<?php echo $goods['status'] == 'on' ? 'success' : 'secondary'; ?>">
                                        <?php echo $goods['status'] == 'on' ? '在售' : '已下架'; ?>
                                    </span>
                                    
                                    <?php if ($goods['stock'] <= 0 && $goods['status'] == 'on'): ?>
                                        <span class="badge bg-warning text-dark">库存不足</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer d-grid gap-2">
                                    <!-- 增加库存按钮 -->
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="openAddStock(<?php echo $goods['product_id']; ?>)">
                                        <i class="fa fa-plus"></i> 添加库存量
                                    </button>
                                    
                                    <!-- 减少库存按钮 -->
                                    <button class="btn btn-sm btn-outline-warning" 
                                            onclick="openSubStock(<?php echo $goods['product_id']; ?>)">
                                        <i class="fa fa-minus"></i> 减少库存量
                                    </button>
                                    
                                    <!-- 在售商品：显示下架按钮 -->
                                    <?php if ($goods['status'] == 'on'): ?>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="openOffShelf(<?php echo $goods['product_id']; ?>)">
                                        <i class="fa fa-arrow-down"></i> 下架商品
                                    </button>
                                    <?php else: ?>
                                    <!-- 已下架商品：显示重新上架按钮 -->
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="openOnShelf(<?php echo $goods['product_id']; ?>)">
                                        <i class="fa fa-arrow-up"></i> 重新上架
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fa fa-info-circle"></i> 您还没有上架任何商品~
                    </div>
                    <?php endif; ?>
                    
                    <!-- 操作按钮 -->
                    <div class="d-grid gap-2 mt-4">
                        <a href="add_product.php" class="btn btn-warning">
                            <i class="fa fa-upload"></i> 上架新商品
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

<!-- 弹窗模态框：添加库存 -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">添加商品库存量</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="add_pid">
                    <label class="form-label">输入要添加的商品数量：</label>
                    <input type="number" min="1" class="form-control" name="num" required placeholder="请输入正数">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="add_stock" class="btn btn-primary">确认</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 弹窗模态框：减少库存 -->
<div class="modal fade" id="subStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">减少商品库存量</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="sub_pid">
                    <label class="form-label">输入要减少的商品数量：</label>
                    <input type="number" min="1" class="form-control" name="num" required placeholder="不能大于当前库存">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="sub_stock" class="btn btn-primary">确认</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 弹窗模态框：下架商品 -->
<div class="modal fade" id="offShelfModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">下架商品确认</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="off_pid">
                    <p class="text-danger">是否确认要下架该商品？下架后前台商城不再展示该商品</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="off_shelf" class="btn btn-danger">确认下架</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 弹窗模态框：重新上架确认 -->
<div class="modal fade" id="onShelfModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">商品重新上架确认</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="on_pid">
                    <p class="text-primary">是否确认将该商品重新上架？上架后商城首页可以正常展示并购买</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="on_shelf" class="btn btn-primary">确认上架</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const addModal = new bootstrap.Modal(document.getElementById('addStockModal'));
    const subModal = new bootstrap.Modal(document.getElementById('subStockModal'));
    const offModal = new bootstrap.Modal(document.getElementById('offShelfModal'));
    const onModal = new bootstrap.Modal(document.getElementById('onShelfModal'));

    // 打开添加库存弹窗
    function openAddStock(pid) {
        document.getElementById('add_pid').value = pid;
        addModal.show();
    }

    // 打开减少库存弹窗
    function openSubStock(pid) {
        document.getElementById('sub_pid').value = pid;
        subModal.show();
    }

    // 打开下架确认弹窗
    function openOffShelf(pid) {
        document.getElementById('off_pid').value = pid;
        offModal.show();
    }

    // 打开重新上架弹窗
    function openOnShelf(pid) {
        document.getElementById('on_pid').value = pid;
        onModal.show();
    }
</script>

<?php $conn->close(); ?>