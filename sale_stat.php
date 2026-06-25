<?php
session_start();
// 未登录拦截（可选，如果想让所有人都能看就注释掉）
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

// ==================== 数据1：柱状图 - 商品销量TOP10 ====================
$product_sales = [];
$sql = "SELECT p.product_name, SUM(oi.buy_num) AS total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.order_status = 'finish'
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 10";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
    $product_sales[] = $row;
}
$product_names = json_encode(array_column($product_sales, 'product_name'));
$product_sold_nums = json_encode(array_column($product_sales, 'total_sold'));

// ==================== 数据2：饼状图 - 商家销售额占比 ====================
$seller_sales = [];
$sql = "SELECT u.username, SUM(oi.buy_num * oi.item_price) AS total_money
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN users u ON p.user_id = u.user_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.order_status = 'finish'
        GROUP BY u.user_id
        ORDER BY total_money DESC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
    $seller_sales[] = $row;
}
$seller_names = json_encode(array_column($seller_sales, 'username'));
$seller_moneys = json_encode(array_column($seller_sales, 'total_money'));
$has_seller_data = count($seller_sales) > 0;

// ==================== 数据3：折线图 - 近7天日销量趋势 ====================
$daily_sales = [];
for($i = 6; $i >= 0; $i--){
    $date = date('Y-m-d', strtotime("-$i day"));
    $daily_sales[$date] = 0;
}
$sql = "SELECT DATE(o.create_time) AS sale_date, SUM(oi.buy_num) AS daily_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.order_status = 'finish'
        AND o.create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(o.create_time)
        ORDER BY sale_date";
$result = $conn->query($sql);
if($result){
    while($row = $result->fetch_assoc()){
        $daily_sales[$row['sale_date']] = (int)$row['daily_count'];
    }
}
$daily_dates = json_encode(array_keys($daily_sales));
$daily_counts = json_encode(array_values($daily_sales));

// 总销售额
$total_result = $conn->query("SELECT SUM(total_amount) AS all_sale FROM orders WHERE order_status='finish'")->fetch_assoc();
$total_sale = $total_result['all_sale'] ?: 0;

// 总订单数
$order_count = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE order_status='finish'")->fetch_assoc()['cnt'];

// 总销量
$total_sold_result = $conn->query("SELECT SUM(buy_num) AS total FROM order_items oi JOIN orders o ON oi.order_id=o.order_id WHERE o.order_status='finish'")->fetch_assoc();
$total_sold = $total_sold_result['total'] ?: 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>销售统计 - 优品线上商城</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 350px;
            margin: 20px 0;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .empty-tip {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: #999;
        }
    </style>
</head>
<body class="bg-light">
    <!-- 顶部导航 -->
    <div class="bg-light p-2 text-center">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>欢迎回来，<?php echo $_SESSION['username']; ?></span>
            <a href="user_home.php?exit=1" class="btn btn-sm btn-outline-danger ms-2">退出登录</a>
        <?php else: ?>
            <span>您当前未登录</span>
            <a href="login.php" class="btn btn-sm btn-primary ms-2">登录</a>
            <a href="reg.php" class="btn btn-sm btn-outline-secondary ms-1">注册</a>
        <?php endif; ?>
    </div>
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand fs-3" href="shop.php"><i class="fa fa-shopping-bag"></i> 优品线上商城</a>
            <div class="d-flex gap-3">
                <a href="shop.php" class="btn btn-light"><i class="fa fa-home"></i> 返回商城</a>
                <a href="sale_stat.php" class="btn btn-warning"><i class="fa fa-line-chart"></i> 销售统计</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- 顶部数据概览 -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card shadow bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-yen fs-1"></i>
                        <h3 class="mt-2">¥<?php echo number_format($total_sale, 2); ?></h3>
                        <p class="mb-0">总销售额</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-shopping-cart fs-1"></i>
                        <h3 class="mt-2"><?php echo $order_count; ?></h3>
                        <p class="mb-0">总订单数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-cubes fs-1"></i>
                        <h3 class="mt-2"><?php echo $total_sold; ?></h3>
                        <p class="mb-0">总销量（件）</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 第一行：柱状图 + 饼状图 -->
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fa fa-bar-chart"></i> 商品销量排行榜 TOP10</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <?php if(count($product_sales) > 0): ?>
                                <canvas id="barChart"></canvas>
                            <?php else: ?>
                                <div class="empty-tip">
                                    <i class="fa fa-bar-chart fa-3x mb-3"></i>
                                    <p>暂无销售数据</p>
                                    <p class="small">有订单后这里会显示商品销量排行</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fa fa-pie-chart"></i> 商家销售额占比</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <?php if($has_seller_data): ?>
                                <canvas id="pieChart"></canvas>
                            <?php else: ?>
                                <div class="empty-tip">
                                    <i class="fa fa-pie-chart fa-3x mb-3"></i>
                                    <p>暂无销售数据</p>
                                    <p class="small">有订单后这里会显示商家占比</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 第二行：折线图 -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fa fa-line-chart"></i> 近7天日销量趋势</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 详细数据表格 -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fa fa-table"></i> 商家销售明细</h5>
                    </div>
                    <div class="card-body">
                        <?php if($has_seller_data): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>排名</th>
                                    <th>卖家</th>
                                    <th>销售额</th>
                                    <th>占比</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                $all_money = array_sum(array_column($seller_sales, 'total_money'));
                                foreach($seller_sales as $seller): 
                                    $percent = $all_money > 0 ? round($seller['total_money'] / $all_money * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <?php if($rank == 1): ?>
                                            <span class="badge bg-warning">🥇 第1名</span>
                                        <?php elseif($rank == 2): ?>
                                            <span class="badge bg-secondary">🥈 第2名</span>
                                        <?php elseif($rank == 3): ?>
                                            <span class="badge bg-danger">🥉 第3名</span>
                                        <?php else: ?>
                                            第<?php echo $rank; ?>名
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $seller['username']; ?></td>
                                    <td class="text-danger fw-bold">¥<?php echo number_format($seller['total_money'], 2); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $percent; ?>%">
                                                <?php echo $percent; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-info-circle"></i> 暂无销售数据
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-dark text-white text-center">
        优品商城 ©2026 实训项目 | 基于WAMP+MariaDB开发
    </footer>

    <script>
        // ==================== 柱状图 ====================
        <?php if(count($product_sales) > 0): ?>
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $product_names; ?>,
                datasets: [{
                    label: '销量（件）',
                    data: <?php echo $product_sold_nums; ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)',
                        'rgba(83, 102, 255, 0.7)',
                        'rgba(40, 159, 64, 0.7)',
                        'rgba(210, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                        'rgba(83, 102, 255, 1)',
                        'rgba(40, 159, 64, 1)',
                        'rgba(210, 99, 132, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        <?php endif; ?>

        // ==================== 饼状图 ====================
        <?php if($has_seller_data): ?>
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $seller_names; ?>,
                datasets: [{
                    data: <?php echo $seller_moneys; ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#C9CBCF',
                        '#7BC225'
                    ],
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ¥' + Number(value).toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // ==================== 折线图 ====================
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?php echo $daily_dates; ?>,
                datasets: [{
                    label: '日销量（件）',
                    data: <?php echo $daily_counts; ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(75, 192, 192)',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>