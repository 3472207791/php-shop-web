<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('请先登录！');location.href='login.php';</script>";
    exit;
}

$uid = $_SESSION['user_id'];
$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->set_charset("utf8mb4");

// 1. 设置默认地址
if(isset($_GET['set_default'])){
    $aid = intval($_GET['set_default']);
    // 先把该用户所有地址取消默认
    $conn->query("UPDATE user_address SET is_default=0 WHERE user_id=$uid");
    // 指定地址设为默认
    $conn->query("UPDATE user_address SET is_default=1 WHERE addr_id=$aid AND user_id=$uid");
    echo "<script>location.href='address.php';</script>";
    exit;
}

// 2. 删除地址
if(isset($_GET['del'])){
    $aid = intval($_GET['del']);
    $conn->query("DELETE FROM user_address WHERE addr_id=$aid AND user_id=$uid");
    echo "<script>location.href='address.php';</script>";
    exit;
}

// 3. 新增地址提交
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_addr'])){
    $name = trim($_POST['receiver_name']);
    $phone = trim($_POST['receiver_phone']);
    $addr = trim($_POST['address_detail']);
    $is_def = isset($_POST['is_default']) ? 1 : 0;

    // 如果勾选设为默认，先清空该用户所有默认
    if($is_def == 1){
        $conn->query("UPDATE user_address SET is_default=0 WHERE user_id=$uid");
    }
    $sql = "INSERT INTO user_address(user_id,receiver_name,receiver_phone,address_detail,is_default)
            VALUES($uid,'$name','$phone','$addr',$is_def)";
    $conn->query($sql);
    echo "<script>alert('地址新增成功');location.href='address.php';</script>";
    exit;
}

// 查询当前用户所有地址
$addr_res = $conn->query("SELECT * FROM user_address WHERE user_id=$uid ORDER BY is_default DESC,addr_id DESC");
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fa fa-map-marker text-primary"></i> 我的收货地址管理</h3>
        <a href="user_home.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> 返回个人主页</a>
    </div>

    <div class="row">
        <!-- 地址列表 -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">我的已有地址</div>
                <div class="card-body">
                    <?php if($addr_res->num_rows == 0): ?>
                        <p class="text-muted">你还没有保存任何收货地址，可在右侧添加新地址</p>
                    <?php else: ?>
                        <?php while($row = $addr_res->fetch_assoc()): ?>
                        <div class="border p-3 mb-3 rounded <?= $row['is_default']==1 ? 'border-primary border-3 bg-light' : '' ?>">
                            <?php if($row['is_default'] == 1): ?>
                                <span class="badge bg-primary mb-2">默认使用地址</span>
                            <?php endif; ?>
                            <p class="mb-1"><strong>收货人：</strong><?= $row['receiver_name'] ?></p>
                            <p class="mb-1"><strong>手机号：</strong><?= $row['receiver_phone'] ?></p>
                            <p class="mb-2"><strong>详细地址：</strong><?= $row['address_detail'] ?></p>
                            <div class="gap-2 d-flex">
                                <?php if($row['is_default'] != 1): ?>
                                    <a href="?set_default=<?= $row['addr_id'] ?>" class="btn btn-sm btn-success">设为默认地址</a>
                                <?php endif; ?>
                                <a href="?del=<?= $row['addr_id'] ?>" onclick="return confirm('确定删除该地址？')" class="btn btn-sm btn-danger">删除地址</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 新增地址表单 -->
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">添加新收货地址</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">收货人姓名</label>
                            <input type="text" name="receiver_name" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">手机号码</label>
                            <input type="tel" name="receiver_phone" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">详细收货地址</label>
                            <textarea name="address_detail" rows="3" required class="form-control"></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_default" class="form-check-input" id="def">
                            <label class="form-check-label" for="def">同时设为当前默认使用地址</label>
                        </div>
                        <button type="submit" name="add_addr" class="btn btn-primary w-100">保存新增地址</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $conn->close(); ?>