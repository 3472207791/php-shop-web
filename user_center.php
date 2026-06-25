<?php
session_start();
// 未登录直接跳转到登录页
if(!isset($_SESSION['user_id'])){
    echo "<script>alert('请先登录！');location.href='login.php';</script>";
    exit;
}

// 数据库配置（和你项目完全一致）
$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->set_charset("utf8mb4");

// 获取【当前登录用户】的专属信息
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$user = $conn->query($sql)->fetch_assoc();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-user-circle"></i> 个人中心</h4>
                </div>
                <div class="card-body p-4">
                    <!-- 专属账户信息展示 -->
                    <div class="mb-3">
                        <label class="fw-bold">用户名：</label>
                        <p class="form-control-plaintext border-bottom"><?php echo $user['username']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">真实姓名：</label>
                        <p class="form-control-plaintext border-bottom"><?php echo $user['real_name']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">手机号：</label>
                        <p class="form-control-plaintext border-bottom"><?php echo $user['phone']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">收货地址：</label>
                        <p class="form-control-plaintext border-bottom"><?php echo $user['address']; ?></p>
                    </div>

                    <!-- 操作按钮 -->
		    <a href="edit_user.php" class="btn btn-info w-100 mb-2">
    		    <i class="fa fa-pencil"></i> 修改个人资料
		    </a>
                    <a href="shop.php" class="btn btn-secondary w-100 mt-2">
                        <i class="fa fa-arrow-left"></i> 返回商城首页
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>