<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit();
}
require_once "database.php";
$db = new Database();

// ---- Thống kê tổng quan ----
$today = date('Y-m-d');
$month = date('Y-m');

$r_order_today  = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE DATE(NgayDat) = '$today'");
$r_revenue_month = $db->select("SELECT SUM(TongTien) as rev FROM donhang WHERE DATE_FORMAT(NgayDat,'%Y-%m') = '$month' AND trangThai = 3");
$r_total_user   = $db->select("SELECT COUNT(*) as cnt FROM user WHERE quyen = 'user'");
$r_total_product = $db->select("SELECT COUNT(*) as cnt FROM product");
$r_pending      = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE trangThai = 0");

$order_today    = $r_order_today    ? intval($r_order_today->fetch_assoc()['cnt'])    : 0;
$revenue_month  = $r_revenue_month  ? intval($r_revenue_month->fetch_assoc()['rev'])  : 0;
$total_user     = $r_total_user     ? intval($r_total_user->fetch_assoc()['cnt'])     : 0;
$total_product  = $r_total_product  ? intval($r_total_product->fetch_assoc()['cnt'])  : 0;
$pending_orders = $r_pending        ? intval($r_pending->fetch_assoc()['cnt'])         : 0;

// ---- Doanh thu 14 ngày gần nhất ----
$r_chart = $db->select("SELECT DATE(NgayDat) as ngay, SUM(TongTien) as doanhthu, COUNT(*) as so_don
    FROM donhang
    WHERE NgayDat >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND trangThai = 3
    GROUP BY DATE(NgayDat) ORDER BY ngay ASC");

$chart_labels = [];
$chart_revenue = [];
$chart_orders  = [];
// Điền đủ 14 ngày kể cả ngày không có doanh thu
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[]  = date('d/m', strtotime($d));
    $chart_revenue[$d] = 0;
    $chart_orders[$d]  = 0;
}
if ($r_chart) {
    while ($row = $r_chart->fetch_assoc()) {
        if (isset($chart_revenue[$row['ngay']])) {
            $chart_revenue[$row['ngay']] = intval($row['doanhthu']);
            $chart_orders[$row['ngay']]  = intval($row['so_don']);
        }
    }
}
$chart_revenue_vals = array_values($chart_revenue);
$chart_orders_vals  = array_values($chart_orders);

// ---- Top 5 sản phẩm bán chạy ----
$r_top = $db->select("SELECT p.TenSanPham, p.hinh, SUM(c.SoLuong) as da_ban,
    SUM(c.SoLuong * c.Gia) as doanhthu
    FROM chitietdonhang c
    JOIN product p ON c.MaSanPham = p.MaSanPham
    JOIN donhang d ON c.MaDonHang = d.MaDonHang
    WHERE d.trangThai = 3
    GROUP BY c.MaSanPham ORDER BY da_ban DESC LIMIT 5");
$top_products = [];
if ($r_top) while ($row = $r_top->fetch_assoc()) $top_products[] = $row;
$max_ban = !empty($top_products) ? intval($top_products[0]['da_ban']) : 1;

// ---- Đơn hàng mới nhất ----
$r_recent = $db->select("SELECT d.MaDonHang, d.TenNguoiNhan, d.TongTien, d.trangThai, d.NgayDat, u.email
    FROM donhang d JOIN user u ON d.IdNguoiDung = u.IdNguoiDung
    ORDER BY d.NgayDat DESC LIMIT 8");
$recent_orders = [];
if ($r_recent) while ($row = $r_recent->fetch_assoc()) $recent_orders[] = $row;

// ---- Phân bổ doanh thu theo danh mục ----
$r_cat_rev = $db->select("SELECT cat.TenDanhMuc, SUM(c.SoLuong * c.Gia) as rev
    FROM chitietdonhang c
    JOIN product p ON c.MaSanPham = p.MaSanPham
    JOIN categories cat ON p.MaDanhMuc = cat.MaDanhMuc
    JOIN donhang d ON c.MaDonHang = d.MaDonHang
    WHERE d.trangThai = 3
    GROUP BY p.MaDanhMuc ORDER BY rev DESC");
$cat_labels = []; $cat_rev = [];
if ($r_cat_rev) {
    while ($row = $r_cat_rev->fetch_assoc()) {
        $cat_labels[] = $row['TenDanhMuc'];
        $cat_rev[]    = intval($row['rev']);
    }
}

// Thay đoạn cũ bằng đoạn này:
$status_labels = [
    0 => 'Đã đặt', 
    1 => 'Đóng gói', 
    2 => 'Đang giao', 
    3 => 'Hoàn thành', 
    4 => 'Đã hủy' // Thêm cái này
];
$status_colors_cls = [
    0 => 'bg-blue-100 text-blue-700',
    1 => 'bg-yellow-100 text-yellow-700',
    2 => 'bg-purple-100 text-purple-700',
    3 => 'bg-green-100 text-green-700',
    4 => 'bg-red-100 text-red-700', // Màu đỏ cho đơn hủy
];
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TTP Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <link rel="icon" href="./public/images/icon_web.png" type="image/png">
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    </style>
</head>

<body class="bg-gray-100">
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="adminSidebarOpen()"
                    class="md:hidden text-gray-600 hover:text-blue-600 focus:outline-none transition">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl md:text-2xl font-bold text-blue-600 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> <span class="hidden sm:inline">TTP Admin</span>
                </h1>
            </div>

            <div class="flex items-center gap-3 md:gap-6">
                <a href="index.php"
                    class="bg-blue-50 text-blue-600 px-3 md:px-4 py-2 rounded-lg font-bold hover:bg-blue-100 transition flex items-center gap-2 text-sm">
                    <i class="fas fa-globe"></i> <span class="hidden sm:inline">Xem website</span>
                </a>

                <?php 
                    // Tạo link avatar động
                    $ten_user = $_SESSION['user_name'];
                    $avatar_link = 'https://ui-avatars.com/api/?name=' . urlencode($ten_user) . '&background=0D8ABC&color=fff&size=128';
                    
                    if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar'] != 'default.png' && $_SESSION['user_avatar'] != '') {
                        $avatar_link = 'public/images/' . $_SESSION['user_avatar'];
                    }
                ?>
                <div class="relative group inline-block z-50 text-sm">
                    <div class="flex items-center gap-2 cursor-pointer whitespace-nowrap py-2"
                        title="Tài khoản của tôi">
                        <span class="text-gray-500 group-hover:text-gray-800 transition hidden md:inline">Xin
                            chào,</span>
                        <img src="<?php echo htmlspecialchars($avatar_link); ?>" alt="Avatar"
                            class="w-8 h-8 rounded-full object-cover border border-gray-300 group-hover:border-blue-400 transition">
                        <strong class="text-gray-800 group-hover:text-blue-600 hidden md:flex items-center gap-1">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            <i class="fas fa-chevron-down text-[10px] transition-transform group-hover:rotate-180"></i>
                        </strong>
                    </div>

                    <div
                        class="absolute right-0 top-full mt-0 w-48 md:w-56 bg-white rounded-lg shadow-xl py-2 hidden group-hover:block border border-gray-100 transform opacity-0 group-hover:opacity-100 transition duration-300 text-left">
                        <div
                            class="absolute -top-2 right-4 w-4 h-4 bg-white border-l border-t border-gray-100 transform rotate-45">
                        </div>

                        <div class="px-4 py-2 border-b border-gray-100 md:hidden font-bold text-gray-800 truncate">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </div>

                        <div class="relative z-10 flex flex-col text-gray-700">
                            <a href="user_profile.php"
                                class="px-4 py-2 hover:bg-blue-50 hover:text-blue-600 transition flex items-center gap-3">
                                <i class="fas fa-user-circle w-4 text-center"></i> Hồ sơ cá nhân
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="logout.php"
                                class="px-4 py-2 hover:bg-red-50 hover:text-red-600 transition flex items-center gap-3 text-red-500 font-semibold">
                                <i class="fas fa-sign-out-alt w-4 text-center"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-4 md:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button onclick="adminSidebarOpen()" class="md:hidden text-gray-500 hover:text-blue-600 transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Hôm nay: <?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <?php if ($pending_orders > 0): ?>
                    <a href="admin_orders.php?status_filter=0"
                        class="flex items-center gap-2 bg-red-50 text-red-600 border border-red-200 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition animate-pulse">
                        <i class="fas fa-bell text-xs"></i>
                        <?php echo $pending_orders; ?> đơn chờ xử lý
                    </a>
                    <?php endif; ?>
                    <a href="index.php" target="_blank"
                        class="text-sm text-gray-400 hover:text-blue-600 flex items-center gap-1 transition">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        <span class="hidden md:inline">Xem trang chủ</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php
                $kpis = [
                    ['icon'=>'fa-shopping-bag',    'cls'=>'blue',   'label'=>'Đơn hôm nay',       'value'=>$order_today,                             'link'=>'admin_orders.php', 'suffix'=>'đơn'],
                    ['icon'=>'fa-coins',            'cls'=>'green',  'label'=>'Doanh thu tháng',    'value'=>number_format($revenue_month).'₫',       'link'=>'admin_orders.php', 'suffix'=>''],
                    ['icon'=>'fa-users',            'cls'=>'purple', 'label'=>'Khách hàng',         'value'=>$total_user,                             'link'=>'#',                'suffix'=>'người'],
                    ['icon'=>'fa-box',              'cls'=>'amber',  'label'=>'Sản phẩm',           'value'=>$total_product,                          'link'=>'admin_product.php','suffix'=>'SP'],
                ];
                $cls_map = [
                    'blue'  => ['bg'=>'bg-blue-50',  'text'=>'text-blue-600',  'icon_bg'=>'bg-blue-100'],
                    'green' => ['bg'=>'bg-green-50', 'text'=>'text-green-600', 'icon_bg'=>'bg-green-100'],
                    'purple'=> ['bg'=>'bg-purple-50','text'=>'text-purple-600','icon_bg'=>'bg-purple-100'],
                    'amber' => ['bg'=>'bg-amber-50', 'text'=>'text-amber-600', 'icon_bg'=>'bg-amber-100'],
                ];
                foreach ($kpis as $kpi):
                    $c = $cls_map[$kpi['cls']];
                ?>
                    <a href="<?php echo $kpi['link']; ?>"
                        class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition group">
                        <div
                            class="w-12 h-12 rounded-xl <?php echo $c['icon_bg'] . ' ' . $c['text']; ?> flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                            <i class="fas <?php echo $kpi['icon']; ?> text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 mb-0.5"><?php echo $kpi['label']; ?></p>
                            <p class="text-xl font-black text-gray-800 truncate"><?php echo $kpi['value']; ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Charts row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Doanh thu 14 ngày -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-800">Doanh thu 14 ngày (đơn hoàn thành)</h3>
                            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">triệu ₫</span>
                        </div>
                        <div style="position: relative; height: 240px;">
                            <canvas id="revenueChart" role="img"
                                aria-label="Biểu đồ doanh thu 14 ngày gần nhất"></canvas>
                        </div>
                    </div>

                    <!-- Doanh thu theo danh mục -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 mb-4">Doanh thu theo danh mục</h3>
                        <?php if (!empty($cat_rev)): ?>
                        <div style="position: relative; height: 200px;">
                            <canvas id="catChart" role="img"
                                aria-label="Biểu đồ doanh thu theo danh mục sản phẩm"></canvas>
                        </div>
                        <!-- Custom legend -->
                        <div class="mt-4 space-y-2" id="cat-legend"></div>
                        <?php else: ?>
                        <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                            <i class="fas fa-chart-pie text-4xl mb-2"></i>
                            <p class="text-sm">Chưa có dữ liệu</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top sản phẩm -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="font-bold text-gray-800">Top sản phẩm bán chạy</h3>
                            <a href="admin_product.php" class="text-xs text-blue-600 hover:underline">Quản lý SP</a>
                        </div>
                        <?php if (empty($top_products)): ?>
                        <div class="text-center text-gray-400 py-10">
                            <i class="fas fa-box-open text-3xl mb-2 block"></i>Chưa có dữ liệu
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($top_products as $i => $tp):
                            $pct = $max_ban > 0 ? round(intval($tp['da_ban']) / $max_ban * 100) : 0;
                            // Tránh Warning: Undefined array key khi $i vượt quá index mảng
                            // Tìm đoạn này (khoảng dòng 347-348):
                            $bar_cls_list = ['bg-blue-500','bg-purple-500','bg-green-500','bg-amber-500','bg-red-400'];
                            // $i chạy 0..4 trong mảng top_products (LIMIT 5). Vẫn dùng ternary để tránh Notice nếu mảng rỗng/khác độ dài.
                            $bar_cls = $bar_cls_list[$i] ?? 'bg-gray-400';
                            

                        ?>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black text-gray-400 w-4"><?php echo $i+1; ?></span>
                                <img src="public/images/<?php echo htmlspecialchars($tp['hinh']); ?>" loading="lazy"
                                    class="w-10 h-10 object-cover rounded-lg border border-gray-100 flex-shrink-0"
                                    alt="<?php echo htmlspecialchars($tp['TenSanPham']); ?>">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        <?php echo htmlspecialchars($tp['TenSanPham']); ?></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full <?php echo $bar_cls; ?>"
                                                style="width:<?php echo $pct; ?>%"></div>
                                        </div>
                                        <span
                                            class="text-xs text-gray-500 whitespace-nowrap"><?php echo number_format($tp['da_ban']); ?>
                                            bán</span>
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-bold text-red-600 whitespace-nowrap"><?php echo number_format($tp['doanhthu']); ?>₫</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Đơn hàng gần đây -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="font-bold text-gray-800">Đơn hàng gần đây</h3>
                            <a href="admin_orders.php" class="text-xs text-blue-600 hover:underline">Xem tất cả</a>
                        </div>
                        <?php if (empty($recent_orders)): ?>
                        <div class="text-center text-gray-400 py-10">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>Chưa có đơn hàng
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_orders as $ro):
                            $st = intval($ro['trangThai']);
                        ?>
                            <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-sm font-bold text-gray-700">#<?php echo str_pad($ro['MaDonHang'],6,'0',STR_PAD_LEFT); ?></span>
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full <?php echo $status_colors_cls[$st] ?? 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo $status_labels[$st] ?? 'Không xác định'; ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">
                                        <?php echo htmlspecialchars($ro['TenNguoiNhan']); ?> ·
                                        <?php echo date('d/m H:i', strtotime($ro['NgayDat'])); ?></p>
                                </div>
                                <div class="text-sm font-bold text-red-600 whitespace-nowrap">
                                    <?php echo number_format($ro['TongTien']); ?>₫</div>
                                <a href="admin_orders.php?update_status=<?php echo $st; ?>&order_id=<?php echo $ro['MaDonHang']; ?>"
                                    class="text-gray-400 hover:text-blue-600 transition text-xs">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
    // ---- Biểu đồ doanh thu 14 ngày ----
    (function() {
        var labels = <?php echo json_encode($chart_labels); ?>;
        var revenue =
            <?php echo json_encode(array_map(function($v){ return round($v/1000000, 2); }, $chart_revenue_vals)); ?>;
        var orders = <?php echo json_encode($chart_orders_vals); ?>;

        var ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        type: 'line',
                        label: 'Số đơn',
                        data: orders,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: '#f59e0b',
                        pointRadius: 3,
                        tension: 0.4,
                        yAxisID: 'y2'
                    },
                    {
                        type: 'bar',
                        label: 'Doanh thu (triệu ₫)',
                        data: revenue,
                        backgroundColor: 'rgba(37,99,235,0.7)',
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y1: {
                        position: 'left',
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: function(v) {
                                return v + 'M';
                            }
                        }
                    },
                    y2: {
                        position: 'right',
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    })();

    // ---- Biểu đồ danh mục ----
    (function() {
        var ctx2 = document.getElementById('catChart');
        if (!ctx2) return;

        var labels = <?php echo json_encode($cat_labels ?: ['Chưa có']); ?>;
        var values =
            <?php echo json_encode(!empty($cat_rev) ? array_map(function($v){ return round($v/1000000,2); }, $cat_rev) : [0]); ?>;
        var colors = ['#2563eb', '#7c3aed', '#16a34a', '#d97706', '#dc2626'];

        var chart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '65%'
            }
        });

        // Custom legend
        var legend = document.getElementById('cat-legend');
        if (legend) {
            labels.forEach(function(lbl, i) {
                var total = values.reduce(function(a, b) {
                    return a + b;
                }, 0);
                var pct = total > 0 ? Math.round(values[i] / total * 100) : 0;
                legend.innerHTML += '<div class="flex items-center justify-between text-xs">' +
                    '<div class="flex items-center gap-2">' +
                    '<span style="width:10px;height:10px;border-radius:2px;background:' + (colors[i] ||
                        '#ccc') + ';display:inline-block;"></span>' +
                    '<span class="text-gray-700">' + lbl + '</span>' +
                    '</div>' +
                    '<span class="font-semibold text-gray-600">' + values[i].toFixed(1) + 'M (' + pct +
                    '%)</span>' +
                    '</div>';
            });
        }
    })();
    </script>
</body>

</html>