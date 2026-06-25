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

// 单条标记已读
if(isset($_GET['read_id'])){
    $mid = intval($_GET['read_id']);
    $conn->query("UPDATE user_message SET is_read=1 WHERE msg_id=$mid AND recv_uid=$uid");
    header("Location: message.php");
    exit;
}
// 全部标为已读
if(isset($_GET['read_all'])){
    $conn->query("UPDATE user_message SET is_read=1 WHERE recv_uid=$uid");
    header("Location: message.php");
    exit;
}

// 查询当前用户所有消息，未读排在前面
$msg_res = $conn->query("SELECT * FROM user_message WHERE recv_uid=$uid ORDER BY is_read ASC, create_time DESC");
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fa fa-bell"></i> 我的系统消息</h4>
                    <div>
                        <a href="?read_all=1" class="btn btn-sm btn-light me-2">全部设为已读</a>
                        <a href="shop.php" class="btn btn-sm btn-dark">返回商城首页</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($msg_res->num_rows == 0): ?>
                        <div class="alert alert-secondary text-center">暂无任何系统消息</div>
                    <?php else: ?>
                        <?php while($msg = $msg_res->fetch_assoc()): ?>
                            <div class="border p-3 mb-3 rounded <?= $msg['is_read']==0 ? 'border-primary border-2 bg-light' : '' ?>">
                                <div class="d-flex justify-content-between">
                                    <h5>
                                        <?=$msg['msg_title']?>
                                        <?php if($msg['is_read']==0): ?>
                                            <span class="badge bg-primary">未读</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted"><?=$msg['create_time']?></small>
                                </div>
                                <p><?=$msg['msg_content']?></p>
                                <?php if($msg['is_read']==0): ?>
                                    <a href="?read_id=<?=$msg['msg_id']?>" class="btn btn-sm btn-outline-primary">标为已读</a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $conn->close(); ?>