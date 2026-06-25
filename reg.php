<?php
// 数据库配置（和你的项目完全一致，不做任何修改）
$host = 'localhost';
$user = 'root';
$pwd = '123456';
$dbname = 'shop_db';
$conn = new mysqli($host, $user, $pwd, $dbname);
$conn->set_charset("utf8mb4");

// 处理注册提交
if($_POST){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $real_name = $_POST['real_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // 插入数据库
    $sql = "INSERT INTO users (username,password,real_name,phone,address) 
            VALUES ('$username','$password','$real_name','$phone','$address')";

    if($conn->query($sql)){
        echo "<script>alert('注册成功！');location.href='shop.php'</script>";
    }else{
        echo "<script>alert('注册失败！用户名/手机号已存在');</script>";
    }
}
?>

<!-- 界面样式和商城统一 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-user-plus"></i> 用户注册</h4>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">登录密码</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">真实姓名</label>
                            <input type="text" name="real_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">手机号</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">收货地址</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-check"></i> 完成注册
                        </button>
			<a href="shop.php" class="btn btn-secondary w-100 mt-2">
    			<i class="fa fa-arrow-left"></i> 返回商城首页
			</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>