<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit();
}
require_once "database.php";
$db = new Database();
function getRealtimeStatus($status_db, $time_confirm) {
    // Chỉ tự động tiến từ "Đang giao" (2) → "Hoàn thành" (3) sau 8 phút
    if ($status_db !== 2 || !$time_confirm) return $status_db;
    $minutes_passed = (time() - strtotime($time_confirm)) / 60;
    if ($minutes_passed >= 8) return 3;
    return 2;
}

/* ── Cập nhật trạng thái ── */
if (isset($_GET['update_status'], $_GET['order_id'])) {
    $oid = intval($_GET['order_id']);
    $st  = intval($_GET['update_status']);
    // Cho phép 0–4 (bao gồm Đã hủy = 4 từ phía admin)
    if ($st >= 0 && $st <= 4 && $oid > 0) {
        // --- ĐOẠN MỚI THAY THẾ ---
        date_default_timezone_set('Asia/Ho_Chi_Minh'); // Đảm bảo lưu đúng giờ Việt Nam
        if ($st == 2) {
    $now = date('Y-m-d H:i:s');
    $db->execute("UPDATE donhang SET trangThai = 2, ThoiGianXacNhan = '$now' WHERE MaDonHang = $oid");
} else {
    $db->execute("UPDATE donhang SET trangThai = $st WHERE MaDonHang = $oid");
}
        // --- KẾT THÚC ĐOẠN THAY THẾ ---
        // Nếu admin khôi phục đơn từ hủy → trừ lại tồn kho
        if ($st === 0 && intval($_GET['prev_status'] ?? -1) === 4) {
            $res_items = $db->select("SELECT MaSanPham, SoLuong FROM chitietdonhang WHERE MaDonHang = $oid");
            if ($res_items) while ($item = $res_items->fetch_assoc()) {
                $db->execute("UPDATE product SET SoLuongTon = SoLuongTon - ".intval($item['SoLuong'])." WHERE MaSanPham = ".intval($item['MaSanPham'])." AND SoLuongTon >= ".intval($item['SoLuong']));
            }
        }
    }
    $redir = "admin_orders.php";
    $qs = [];
    if (isset($_GET['status_filter'])) $qs[] = "status_filter=".intval($_GET['status_filter']);
    if (isset($_GET['search']))        $qs[] = "search=".urlencode($_GET['search']);
    header("Location: $redir" . (!empty($qs) ? "?".implode("&",$qs) : ""));
    exit();
}

/* ── Xóa đơn hàng ── */
if (isset($_GET['delete_order'])) {
    $oid = intval($_GET['delete_order']);
    if ($oid > 0) {
        $db->execute("DELETE FROM chitietdonhang WHERE MaDonHang = $oid");
        $db->execute("DELETE FROM donhang WHERE MaDonHang = $oid");
    }
    header("Location: admin_orders.php"); exit();
}

/* ── Bộ lọc ── */
$filter_status = isset($_GET['status_filter']) ? intval($_GET['status_filter']) : -1;
$search_user   = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_like   = $search_user !== '' ? '%' . $db->conn->real_escape_string($search_user) . '%' : '';

$where = "WHERE 1=1";
if ($filter_status >= 0 && $filter_status <= 4) $where .= " AND d.trangThai = $filter_status";
if (!empty($search_user)) {
    $search_like = '%' . $db->conn->real_escape_string($search_user) . '%';
    $where .= " AND (u.TenNguoiDung LIKE '$search_like' OR u.email LIKE '$search_like' OR d.TenNguoiNhan LIKE '$search_like' OR d.SoDienThoai LIKE '$search_like')";
}

$sql = "SELECT d.*, u.TenNguoiDung, u.email,
    (SELECT COUNT(*) FROM chitietdonhang c WHERE c.MaDonHang = d.MaDonHang) as so_sp
    FROM donhang d
    JOIN user u ON d.IdNguoiDung = u.IdNguoiDung
    $where
    ORDER BY d.trangThai ASC, d.NgayDat DESC";
$res = $db->select($sql);
$orders = [];
if ($res) while ($r = $res->fetch_assoc()) $orders[] = $r;

/* ── Thống kê ── */
$stats = [-1=>0, 0=>0, 1=>0, 2=>0, 3=>0, 4=>0];
$res_s = $db->select("SELECT trangThai, COUNT(*) as cnt FROM donhang GROUP BY trangThai");
if ($res_s) while ($r = $res_s->fetch_assoc()) $stats[intval($r['trangThai'])] = intval($r['cnt']);
$stats[-1] = array_sum(array_filter($stats, fn($k) => $k >= 0, ARRAY_FILTER_USE_KEY));

$total_revenue_res = $db->select("SELECT SUM(TongTien) as rev FROM donhang WHERE trangThai = 3");
$total_revenue = 0;
if ($total_revenue_res) { $rv=$total_revenue_res->fetch_assoc(); $total_revenue=intval($rv['rev']); }

// Số đơn mới (trangThai=0) trong 24h qua
$new_orders_res = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE trangThai = 0 AND NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$new_orders_count = 0;
if ($new_orders_res) { $nr=$new_orders_res->fetch_assoc(); $new_orders_count=intval($nr['cnt']); }

// Số đơn vừa hủy trong 24h qua
$cancel_res = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE trangThai = 4 AND NgayDat >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
$cancel_count = 0;
if ($cancel_res) { $cr=$cancel_res->fetch_assoc(); $cancel_count=intval($cr['cnt']); }

$status_labels = ['Đã đặt','Đóng gói','Đang giao','Hoàn thành','Đã hủy'];
$status_colors = [
    0 => ['bg'=>'bg-blue-100',   'text'=>'text-blue-700',   'btn'=>'bg-blue-600',   'icon'=>'fa-receipt'],
    1 => ['bg'=>'bg-yellow-100', 'text'=>'text-yellow-700', 'btn'=>'bg-yellow-500', 'icon'=>'fa-box'],
    2 => ['bg'=>'bg-purple-100', 'text'=>'text-purple-700', 'btn'=>'bg-purple-600', 'icon'=>'fa-truck'],
    3 => ['bg'=>'bg-green-100',  'text'=>'text-green-700',  'btn'=>'bg-green-600',  'icon'=>'fa-check-circle'],
    4 => ['bg'=>'bg-red-100',    'text'=>'text-red-700',    'btn'=>'bg-red-500',    'icon'=>'fa-times-circle'],
];
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - TTP Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="./public/images/icon_web.png" type="image/png">
</head>

<body class="bg-gray-100 font-sans">
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

        <?php include 'admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header
                class="bg-white border-b border-gray-200 px-4 md:px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="adminSidebarOpen()" class="md:hidden text-gray-500 hover:text-blue-600 transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-blue-600"></i> Quản lý đơn hàng
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Badge đơn mới -->
                    <?php if($new_orders_count > 0): ?>
                    <a href="?status_filter=0"
                        class="relative flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-100 transition">
                        <i class="fas fa-bell animate-bounce"></i> <?php echo $new_orders_count; ?> đơn mới
                    </a>
                    <?php endif; ?>
                    <!-- Badge đơn hủy -->
                    <?php if($cancel_count > 0): ?>
                    <a href="?status_filter=4"
                        class="relative flex items-center gap-2 bg-red-50 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-100 transition">
                        <i class="fas fa-ban"></i> <?php echo $cancel_count; ?> đơn hủy
                    </a>
                    <?php endif; ?>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">

                <!-- Thống kê nhanh -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    <?php
                $stat_items = [
                    ['label'=>'Tổng đơn',    'count'=>$stats[-1], 'icon'=>'fa-list-alt',     'cls'=>'bg-indigo-50 text-indigo-600'],
                    ['label'=>'Đã đặt',      'count'=>$stats[0],  'icon'=>'fa-receipt',      'cls'=>'bg-blue-50 text-blue-600'],
                    ['label'=>'Đóng gói',    'count'=>$stats[1],  'icon'=>'fa-box',          'cls'=>'bg-yellow-50 text-yellow-600'],
                    ['label'=>'Đang giao',   'count'=>$stats[2],  'icon'=>'fa-truck',        'cls'=>'bg-purple-50 text-purple-600'],
                    ['label'=>'Hoàn thành',  'count'=>$stats[3],  'icon'=>'fa-check-circle', 'cls'=>'bg-green-50 text-green-600'],
                    ['label'=>'Đã hủy',      'count'=>$stats[4],  'icon'=>'fa-times-circle', 'cls'=>'bg-red-50 text-red-600'],
                ];
                foreach($stat_items as $s): ?>
                    <div
                        class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3 hover:shadow-md transition">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo $s['cls']; ?> flex-shrink-0">
                            <i class="fas <?php echo $s['icon']; ?> text-lg"></i>
                        </div>
                        <div>
                            <div class="text-xl font-black text-gray-800"><?php echo number_format($s['count']); ?>
                            </div>
                            <div class="text-[11px] text-gray-500 whitespace-nowrap"><?php echo $s['label']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Doanh thu -->
                <div
                    class="bg-white rounded-xl border border-gray-100 p-5 mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-coins text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-red-600"><?php echo number_format($total_revenue); ?>₫
                            </div>
                            <div class="text-xs text-gray-500">Tổng doanh thu (đơn hoàn thành)</div>
                        </div>
                    </div>
                </div>

                <!-- Bộ lọc -->
                <div class="bg-white rounded-xl border border-gray-100 mb-5 overflow-hidden">
                    <!-- Tabs trạng thái -->
                    <div class="flex overflow-x-auto border-b border-gray-100">
                        <?php
                    $filter_tabs = [
                        -1 => 'Tất cả',
                        0  => 'Đã đặt',
                        1  => 'Đóng gói',
                        2  => 'Đang giao',
                        3  => 'Hoàn thành',
                        4  => 'Đã hủy',
                    ];
                    $tab_icons = [-1=>'fa-list',0=>'fa-receipt',1=>'fa-box',2=>'fa-truck',3=>'fa-check-circle',4=>'fa-ban'];
                    foreach($filter_tabs as $val => $tlbl):
                        $is_active = ($filter_status == $val);
                        $href_qs   = $val == -1 ? 'admin_orders.php' : 'admin_orders.php?status_filter='.$val;
                        if(!empty($search_user)) $href_qs .= ($val==-1?'?':'&').'search='.urlencode($search_user);
                        $cnt = $val == -1 ? $stats[-1] : ($stats[$val]??0);
                        $active_cls = $is_active
                            ? ($val==4?'border-b-2 border-red-500 text-red-600 font-bold':'border-b-2 border-blue-600 text-blue-600 font-bold')
                            : 'text-gray-500 hover:text-gray-800';
                    ?>
                        <a href="<?php echo $href_qs; ?>"
                            class="flex items-center gap-1.5 px-4 py-3 text-xs whitespace-nowrap transition <?php echo $active_cls; ?>">
                            <i class="fas <?php echo $tab_icons[$val]; ?> text-[10px]"></i>
                            <?php echo $tlbl; ?>
                            <?php if($cnt > 0): ?>
                            <span
                                class="<?php echo $is_active?($val==4?'bg-red-500':'bg-blue-600').' text-white':'bg-gray-200 text-gray-600'; ?> text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                                <?php echo $cnt; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <!-- Search -->
                    <form method="GET" class="flex flex-wrap gap-3 items-center p-4">
                        <?php if($filter_status >= 0): ?>
                        <input type="hidden" name="status_filter" value="<?php echo $filter_status; ?>">
                        <?php endif; ?>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_user); ?>"
                            placeholder="Tìm tên, SĐT, email..."
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 flex-1 min-w-[200px]">
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-2">
                            <i class="fas fa-search"></i> Tìm
                        </button>
                        <a href="admin_orders.php" class="text-sm text-gray-500 hover:text-gray-800 px-3 py-2">Xóa
                            lọc</a>
                        <span class="text-sm text-gray-500 ml-auto">Hiển thị
                            <strong><?php echo count($orders); ?></strong> đơn</span>
                    </form>
                </div>

                <!-- Bảng đơn hàng -->
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <?php if(empty($orders)): ?>
                    <div class="py-20 text-center text-gray-400">
                        <i class="fas fa-inbox text-5xl mb-3 block"></i>
                        Không có đơn hàng nào phù hợp
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Mã đơn</th>
                                    <th class="px-4 py-3 text-left">Khách hàng</th>
                                    <th class="px-4 py-3 text-left hidden md:table-cell">Ngày đặt</th>
                                    <th class="px-4 py-3 text-right">Tổng tiền</th>
                                    <th class="px-4 py-3 text-center">Trạng thái</th>
                                    <th class="px-4 py-3 text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($orders as $order):
    // 1. GỌI HÀM TÍNH TRẠNG THÁI THỰC TẾ (CHÈN VÀO ĐÂY)
    $current_st = getRealtimeStatus(intval($order['trangThai']), $order['ThoiGianXacNhan']);
    
    // 2. CẬP NHẬT CÁC BIẾN HIỂN THỊ THEO TRẠNG THÁI MỚI
    $clr = $status_colors[$current_st] ?? $status_colors[0];
    $lbl = $status_labels[$current_st] ?? 'Không rõ';
    
    $st = intval($order['trangThai']); // Đây vẫn giữ trạng thái gốc trong DB để xử lý nút bấm
    $oid = str_pad($order['MaDonHang'], 6, '0', STR_PAD_LEFT);
    $is_cancelled = ($st === 4);
    $is_new = ($st === 0 && strtotime($order['NgayDat']) >= strtotime('-24 hours'));
?>
                                <tr class="hover:bg-gray-50 transition <?php echo $is_cancelled?'bg-red-50/40':''; ?>"
                                    id="row-<?php echo $order['MaDonHang']; ?>">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-700">#<?php echo $oid; ?></span>
                                            <?php if($is_new): ?><span
                                                class="bg-blue-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full animate-pulse">MỚI</span><?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5"><?php echo $order['so_sp']; ?> sản
                                            phẩm</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-800">
                                            <?php echo htmlspecialchars($order['TenNguoiNhan']); ?></div>
                                        <div class="text-xs text-gray-400">
                                            <?php echo htmlspecialchars($order['SoDienThoai']); ?></div>
                                        <div class="text-xs text-gray-400 hidden md:block truncate max-w-[200px]">
                                            <?php echo htmlspecialchars($order['diachi']); ?></div>
                                    </td>
                                    <td class="px-4 py-4 hidden md:table-cell text-gray-500 text-xs">
                                        <?php echo date('d/m/Y', strtotime($order['NgayDat'])); ?><br>
                                        <?php echo date('H:i', strtotime($order['NgayDat'])); ?>
                                    </td>
                                    <td
                                        class="px-4 py-4 text-right font-bold <?php echo $is_cancelled?'text-gray-400 line-through':'text-red-600'; ?>">
                                        <?php echo number_format($order['TongTien']); ?>₫
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full <?php echo $clr['bg'].' '.$clr['text']; ?>">
                                            <i class="fas <?php echo $clr['icon']; ?> text-[10px]"></i>
                                            <?php echo $lbl; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-2 flex-wrap">

                                            <?php if(!$is_cancelled && $st < 2): ?>
                                            <!-- Nút tiến trạng thái (chỉ cho 0→1 và 1→2, admin bấm tay) -->
                                            <a href="admin_orders.php?update_status=<?php echo $st+1; ?>&order_id=<?php echo $order['MaDonHang']; ?><?php echo $filter_status>=0?'&status_filter='.$filter_status:''; ?>"
                                                class="text-xs <?php echo $status_colors[$st+1]['btn']; ?> text-white px-3 py-1.5 rounded-lg font-medium hover:opacity-90 transition flex items-center gap-1">
                                                <i class="fas fa-arrow-right text-[10px]"></i>
                                                <?php echo $status_labels[$st+1]; ?>
                                            </a>
                                            <?php endif; ?>

                                            <?php if($st === 2): ?>
                                            <!-- Đang giao: hiển thị countdown tự động, không có nút bấm tay -->
                                            <div
                                                class="text-xs text-purple-700 bg-purple-50 border border-purple-200 px-3 py-1.5 rounded-lg font-medium flex items-center gap-1.5">
                                                <i class="fas fa-truck text-[10px] animate-bounce"></i>
                                                <?php if($order['ThoiGianXacNhan']): ?>
                                                <span class="delivery-countdown"
                                                    data-start="<?php echo strtotime($order['ThoiGianXacNhan']); ?>"
                                                    data-order="<?php echo $order['MaDonHang']; ?>" data-duration="480">
                                                    Đang tính...
                                                </span>
                                                <?php else: ?>
                                                <span>Đang giao hàng</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>

                                            <?php if($is_cancelled): ?>
                                            <!-- Khôi phục đơn hủy -->
                                            <button onclick="confirmRestore(<?php echo $order['MaDonHang']; ?>)"
                                                class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg font-medium hover:bg-blue-700 transition flex items-center gap-1">
                                                <i class="fas fa-undo text-[10px]"></i> Khôi phục
                                            </button>
                                            <?php endif; ?>

                                            <!-- Menu thêm -->
                                            <div class="relative group/menu">
                                                <button
                                                    class="text-xs border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1">
                                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                                </button>
                                                <div
                                                    class="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-20 opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all">
                                                    <a href="chitietdonhang.php?id=<?php echo $order['MaDonHang']; ?>"
                                                        target="_blank"
                                                        class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                                        <i class="fas fa-eye w-4"></i> Xem chi tiết
                                                    </a>
                                                    <?php if(!$is_cancelled): ?>
                                                    <hr class="border-gray-100 my-1">
                                                    <?php foreach($status_labels as $k=>$slbl): if($k==$st||$k==4) continue; ?>
                                                    <a href="admin_orders.php?update_status=<?php echo $k; ?>&order_id=<?php echo $order['MaDonHang']; ?>"
                                                        class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                                        <span
                                                            class="w-2 h-2 rounded-full <?php echo $status_colors[$k]['btn']; ?> inline-block"></span>
                                                        <?php echo $slbl; ?>
                                                    </a>
                                                    <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <hr class="border-gray-100 my-1">
                                                    <button onclick="confirmDelete(<?php echo $order['MaDonHang']; ?>)"
                                                        class="flex items-center gap-2 px-4 py-2 text-xs text-red-500 hover:bg-red-50 w-full text-left">
                                                        <i class="fas fa-trash w-4"></i> Xóa đơn
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script>
    // ── Countdown tự động cho đơn "Đang giao" ──
    (function() {
        function initCountdowns() {
            document.querySelectorAll('.delivery-countdown').forEach(function(el) {
                if (el.dataset.init) return; // tránh khởi động 2 lần
                el.dataset.init = '1';

                var startTime = parseInt(el.dataset.start); // unix timestamp
                var duration = parseInt(el.dataset.duration); // 480 giây = 8 phút
                var orderId = el.dataset.order;

                function tick() {
                    var elapsed = Math.floor(Date.now() / 1000) - startTime;
                    var remaining = duration - elapsed;

                    if (remaining <= 0) {
                        el.textContent = 'Hoàn thành!';
                        el.closest('.delivery-countdown')?.parentElement
                            ?.classList.replace('text-purple-700', 'text-green-700');
                        // Gọi AJAX cập nhật DB rồi reload trang
                        fetch('admin_orders.php?update_status=3&order_id=' + orderId)
                            .then(function() {
                                location.reload();
                            });
                        return;
                    }

                    var mins = Math.floor(remaining / 60);
                    var secs = remaining % 60;
                    el.textContent = 'Còn ' + mins + ':' + String(secs).padStart(2, '0') + ' phút';
                    setTimeout(tick, 1000);
                }
                tick();
            });
        }

        document.addEventListener('DOMContentLoaded', initCountdowns);
    })();

    function confirmDelete(id) {
        Swal.fire({
                title: 'Xóa đơn hàng?',
                text: 'Hành động này không thể hoàn tác!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            })
            .then(r => {
                if (r.isConfirmed) window.location.href = 'admin_orders.php?delete_order=' + id;
            });
    }

    function confirmRestore(id) {
        Swal.fire({
                title: 'Khôi phục đơn hàng?',
                text: 'Đơn sẽ được chuyển về trạng thái "Đã đặt" và tồn kho sẽ được trừ lại.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Khôi phục',
                cancelButtonText: 'Hủy'
            })
            .then(r => {
                if (r.isConfirmed) window.location.href =
                    'admin_orders.php?update_status=0&prev_status=4&order_id=' + id;
            });
    }
    </script>
</body>

</html>