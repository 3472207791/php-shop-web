<?php
session_start();
// 未登录拦截
if(!isset($_SESSION['user_id'])){
    echo "<script>alert('请先登录');location.href='login.php'</script>";
    exit;
}
$uid = $_SESSION['user_id'];

$host='localhost';$user='root';$pwd='123456';$db='shop_db';
$conn=new mysqli($host,$user,$pwd,$db);
$conn->set_charset('utf8mb4');

// 提交修改
if($_POST){
    $real_name=$_POST['real_name'];
    $phone=$_POST['phone'];
    $address=$_POST['address'];
    $sql="UPDATE users SET real_name=?,phone=?,address=? WHERE user_id=?";
    $pre=$conn->prepare($sql);
    $pre->bind_param("sssi",$real_name,$phone,$address,$uid);
    if($pre->execute()){
        echo "<script>alert('资料修改成功');location.href='user_center.php'</script>";
    }else{
        echo "<script>alert('修改失败')</script>";
    }
}

// 读取本人原有信息
$row=$conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fa fa-edit"></i> 修改个人信息</h4>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">用户名(不可修改)</label>
                            <input type="text" class="form-control" value="<?=$row['username']?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">真实姓名</label>
                            <input type="text" name="real_name" class="form-control" value="<?=$row['real_name']?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">手机号</label>
                            <input type="text" name="phone" class="form-control" value="<?=$row['phone']?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">收货地址</label>
                            <textarea name="address" class="form-control" rows="2"><?=$row['address']?></textarea>
                        </div>
                        <button class="btn btn-success w-100">保存修改</button>
                    </form>
                    <a href="user_center.php" class="btn btn-secondary w-100 mt-2">
                        <i class="fa fa-arrow-left"></i> 返回个人中心
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>