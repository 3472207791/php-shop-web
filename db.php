<?php
// 自动判断当前运行环境
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // ---------------- 如果在你的本地 XAMPP 运行 ----------------
    $host = 'localhost';
    $user = 'root';
    $pwd = '123456';
    $dbname = 'shop_db';
} else {
    // ---------------- 如果在 InfinityFree 云端运行 ----------------
    $host = 'sql310.infinityfree.com';
    $user = 'if0_42268672';
    $pwd = 'UznkG1OPTncvwkA';
    $dbname = 'if0_42268672_shop';
}

// 建立连接
$conn = new mysqli($host, $user, $pwd, $dbname);

// 检查连接是否成功（可选，方便排错）
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");
?>
