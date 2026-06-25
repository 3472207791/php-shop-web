<?php
session_start();
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

// 提交表单 → 写入数据库
if($_POST){
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $desc = $_POST['desc'];
    $status = $_POST['status'];
    
    // ✅ 修复：直接获取当前登录用户的 ID
    $user_id = $_SESSION['user_id']; 
    
    // 图片上传
    $pic = 'default.jpg';
    if($_FILES['pic']['name']){
        $pic = time().$_FILES['pic']['name'];
        move_uploaded_file($_FILES['pic']['tmp_name'], 'productIcons/'.$pic);
    }

    // 插入商品
    $sql = "INSERT INTO products(user_id,product_name,price,stock,product_desc,status,pic) 
            VALUES('$user_id','$name','$price','$stock','$desc','$status','$pic')";
    if($conn->query($sql)){
        echo "<script>alert('上架成功！');location.href='shop.php'</script>";
    }else{
        echo "<script>alert('上架失败');</script>";
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-4">
    <h3 class="mb-4"><i class="fa fa-upload"></i> 商品上架</h3>
    <div class="card shadow p-4">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>商品名称</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>商品价格</label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>库存数量</label>
                <input type="number" name="stock" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>商品图片</label>
                <input type="file" name="pic" class="form-control">
            </div>
            <div class="mb-3">
                <label>商品描述</label>
                <textarea name="desc" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label>上架状态</label>
                <select name="status" class="form-control">
                    <option value="on">上架</option>
                    <option value="off">下架</option>
                </select>
            </div>
            <button class="btn btn-primary w-100"><i class="fa fa-save"></i> 确认上架</button>
            <a href="shop.php" class="btn btn-secondary w-100 mt-2">
                <i class="fa fa-arrow-left"></i> 返回商城首页
            </a>
        </form>
    </div>
</div>