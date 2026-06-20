<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once "database.php";
$db = new Database();

if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    die("<script>alert('Mã đơn hàng không hợp lệ!'); window.location.href='index.php';</script>");
}

// Xử lý cập nhật trạng thái
if (isset($_GET['ajax_update'])) {
    $st = intval($_GET['ajax_update']);
    $db->execute("UPDATE donhang SET trangThai = $st WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id");
    exit;
}

// Xử lý kiểm tra trạng thái realtime (cho JS)
if (isset($_GET['ajax_check_status'])) {
    $res = $db->select("SELECT trangThai FROM donhang WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id");
    $status = ($res && $res->num_rows > 0) ? intval($res->fetch_assoc()['trangThai']) : 0;
    header('Content-Type: application/json');
    echo json_encode(['status' => $status]);
    exit;
}

$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}

$sql_order = "SELECT * FROM donhang WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id";
$res_order = $db->select($sql_order);
if (!$res_order || $res_order->num_rows == 0) {
    die("<script>alert('Không tìm thấy đơn hàng hoặc bạn không có quyền xem!'); window.location.href='index.php';</script>");
}
$order = $res_order->fetch_assoc();

$date_col = '';
if (isset($order['NgayDat'])) $date_col = 'NgayDat';
elseif (isset($order['NgayTao'])) $date_col = 'NgayTao';
elseif (isset($order['ngayTao'])) $date_col = 'ngayTao';
elseif (isset($order['created_at'])) $date_col = 'created_at';

$so_giay_dang_giao = 0;
$db_status = intval($order['trangThai']);
$ngay_dat = $date_col && !empty($order[$date_col]) ? $order[$date_col] : ($order['NgayDat'] ?? null);

if ($db_status === 2 && !empty($order['ThoiGianXacNhan'])) {
    $res_del = $db->select("SELECT TIMESTAMPDIFF(SECOND, ThoiGianXacNhan, NOW()) as giay_giao FROM donhang WHERE MaDonHang = $order_id");
    if ($res_del) {
        $row_del = $res_del->fetch_assoc();
        $so_giay_dang_giao = max(0, intval($row_del['giay_giao']));
    }
}

$sql_details = "SELECT c.*, p.TenSanPham, p.hinh, p.GiaSanPham 
                FROM chitietdonhang c 
                JOIN product p ON c.MaSanPham = p.MaSanPham 
                WHERE c.MaDonHang = $order_id";
$order_items = $db->select($sql_details);

$payment_method_name = 'Thanh toán khi nhận hàng (COD)';
$sql_pt = "SELECT TenPhuongThuc FROM phuong_thuc_thanh_toan ptt 
           JOIN donhang dh ON dh.MaPhuongThuc = ptt.MaPhuongThuc 
           WHERE dh.MaDonHang = $order_id";
$res_pt = $db->select($sql_pt);
if ($res_pt && $row_pt = $res_pt->fetch_assoc()) {
    $payment_method_name = $row_pt['TenPhuongThuc'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id ?> - TTP Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .hide-scroll::-webkit-scrollbar {
        display: none;
    }

    .hide-scroll {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800 relative overflow-x-hidden">
    <div id="toast-success"
        class="fixed top-24 right-5 z-[100] transform transition-all duration-500 translate-x-[150%] opacity-0 bg-white border-l-4 border-green-500 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 min-w-[300px]">
        <i class="fas fa-check-circle text-green-500 text-3xl"></i>
        <div>
            <h4 class="text-gray-800 font-bold text-sm">Thành công!</h4>
            <p class="text-gray-500 text-xs">Thông báo thành công.</p>
        </div>
    </div>

    <header class="bg-white shadow-sm sticky top-0 z-50 transition-all duration-300" id="main-header">
        <div class="bg-blue-600 text-white text-xs py-1.5 px-4 text-center hidden md:block">Miễn phí vận chuyển cho đơn
            hàng từ 300K - Trải nghiệm mua sắm tuyệt vời cùng TTP Shop!</div>
        <div class="container mx-auto px-4 py-3 md:py-4 max-w-7xl flex items-center justify-between gap-4 md:gap-6">
            <div class="flex md:hidden items-center gap-4 flex-shrink-0">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-blue-600 text-xl focus:outline-none"><i
                        class="fas fa-bars"></i></button>
                <button id="mobile-search-btn" class="text-gray-600 hover:text-blue-600 text-xl focus:outline-none"><i
                        class="fas fa-search"></i></button>
            </div>
            <a href="index.php" class="flex items-center justify-center flex-1 md:flex-none">
                <div class="text-2xl font-bold text-blue-600 tracking-tighter"><img src="public/images/icon_web.png"
                        alt="TTP Shop" class="w-24 h-12 md:w-30 md:h-20 object-contain"></div>
            </a>
            <form action="search.php" method="GET" class="flex-1 max-w-2xl hidden md:block" id="search-form"
                autocomplete="off">
                <div class="relative">
                    <div
                        class="relative flex items-center w-full h-11 rounded-full bg-gray-100 border border-transparent focus-within:border-blue-500 focus-within:bg-white focus-within:shadow-sm overflow-hidden transition-all">
                        <i class="fas fa-search text-gray-400 ml-4"></i>
                        <input type="text" name="keyword" id="search-input" maxlength="100"
                            placeholder="Tìm kiếm sản phẩm, danh mục..."
                            class="w-full bg-transparent border-none outline-none px-3 text-sm text-gray-700 placeholder-gray-500 h-full">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 h-full font-medium transition text-sm flex items-center justify-center"><i
                                class="fas fa-search"></i></button>
                    </div>
                    <div id="search-suggest-box"
                        class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 z-[200] hidden overflow-hidden">
                        <div id="search-suggest-list"></div>
                    </div>
                </div>
            </form>
            <div class="flex items-center gap-4 md:gap-6 flex-shrink-0">
                <div class="relative group z-50">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php"
                        class="relative text-gray-700 hover:text-blue-600 transition flex items-center py-2 h-full cursor-pointer bg-transparent border-none">
                        <i class="fas fa-shopping-cart text-xl md:text-2xl"></i>
                        <span id="cart-badge"
                            class="<?php echo ($total_items > 0) ? 'flex' : 'hidden'; ?> absolute -top-1 -right-2 border-2 border-white bg-red-600 text-white text-[10px] font-bold w-4 h-4 md:w-5 md:h-5 items-center justify-center rounded-full shadow-sm"><?php echo $total_items; ?></span>
                    </a>
                    <div id="cart-dropdown-container"
                        class="absolute right-0 top-full mt-2 w-[350px] sm:w-[400px] bg-white rounded shadow-[0_1px_3.125rem_0_rgba(0,0,0,0.2)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-[100] before:content-[''] before:absolute before:-top-4 before:left-0 before:w-full before:h-4 before:bg-transparent cursor-default text-left">
                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                        <div class="text-gray-400 text-sm p-3 capitalize border-b border-gray-100">Sản phẩm mới thêm
                        </div>
                        <div class="max-h-[40vh] overflow-y-auto hide-scroll">
                            <?php 
                            $cart_preview = array_reverse($_SESSION['cart'], true); 
                            $count = 0;
                            foreach ($cart_preview as $id_sp => $item): 
                                if($count >= 5) break; 
                                $count++; 
                                $tenSP = $item['ten'] ?? $item['TenSanPham'] ?? 'Tên sản phẩm'; 
                                $giaSP = $item['gia'] ?? $item['GiaSanPham'] ?? 0;
                                $hinhSP = $item['hinh'] ?? $item['HinhAnh'] ?? 'default.png'; 
                                $slSP = $item['soluong'] ?? 1;
                            ?>
                            <a href="chitiet.php?id=<?php echo $id_sp; ?>"
                                class="flex items-center p-3 hover:bg-gray-50 transition cursor-pointer border-b border-gray-50 last:border-0">
                                <img src="public/images/<?php echo htmlspecialchars($hinhSP); ?>" alt="Product"
                                    class="w-10 h-10 object-cover border border-gray-200">
                                <div class="ml-3 flex-1 overflow-hidden">
                                    <div class="text-sm text-gray-800 truncate font-medium">
                                        <?php echo htmlspecialchars($tenSP); ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5">Số lượng: <span
                                            class="text-gray-900 font-semibold"><?php echo $slSP; ?></span></div>
                                </div>
                                <div class="text-red-500 text-sm font-bold ml-4">₫<?php echo number_format($giaSP); ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-3 bg-gray-50 flex justify-between items-center rounded-b">
                            <span class="text-xs text-gray-500 font-medium">Có <span
                                    id="cart-dropdown-count"><?php echo $total_items; ?></span> sản phẩm</span>
                            <a href="cart.php"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-sm text-sm capitalize transition shadow-sm">Xem
                                giỏ hàng</a>
                        </div>
                        <?php else: ?>
                        <div class="p-14 text-center flex flex-col items-center justify-center">
                            <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/9bdd8040b334d31946f49e36beaf32db.png"
                                alt="Empty Cart" class="w-24 h-24 mb-3">
                            <div class="text-sm text-gray-500 capitalize">Giỏ hàng trống</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <button onclick="requireLogin()"
                        class="relative text-gray-700 hover:text-blue-600 transition flex items-center py-2 h-full cursor-pointer bg-transparent border-none"><i
                            class="fas fa-shopping-cart text-xl md:text-2xl"></i></button>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_id'])): 
                    $ten_user = $_SESSION['user_name'] ?? 'Khách';
                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($ten_user) . '&background=0D8ABC&color=fff';
                    if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar'] != 'default.png') {
                        $avatar_path = 'public/images/' . $_SESSION['user_avatar'];
                        if (file_exists($avatar_path)) $avatar = $avatar_path;
                    }
                ?>
                <div class="relative group inline-block z-50">
                    <div
                        class="flex items-center gap-2 cursor-pointer py-2 px-1 rounded-full hover:bg-gray-50 transition">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                            class="w-8 h-8 md:w-9 md:h-9 rounded-full object-cover border border-gray-200">
                        <span
                            class="text-sm font-semibold text-gray-700 hidden md:block max-w-[100px] truncate"><?php echo htmlspecialchars($ten_user); ?></span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                    </div>
                    <div
                        class="absolute right-0 top-full mt-1 w-52 bg-white rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-100 origin-top-right transform scale-95 group-hover:scale-100 z-50 before:content-[''] before:absolute before:-top-4 before:left-0 before:w-full before:h-4 before:bg-transparent">
                        <div class="px-4 py-2 border-b border-gray-100 md:hidden block mb-1"><span
                                class="text-sm font-bold text-gray-800 block truncate"><?php echo htmlspecialchars($ten_user); ?></span>
                        </div>
                        <a href="user_profile.php"
                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition flex items-center group/item"><i
                                class="fas fa-user w-5 text-gray-400 group-hover/item:text-blue-500 transition mr-2"></i>Tài
                            khoản</a>
                        <a href="favourites_items.php"
                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-500 transition flex items-center group/item"><i
                                class="fas fa-heart w-5 text-gray-400 group-hover/item:text-red-500 transition mr-2"></i>Yêu
                            thích</a>
                        <?php 
                        $has_order = false;
                        $latest_order_id = 0;
                        if (isset($db) && isset($_SESSION['user_id'])) {
                            $uid_check = intval($_SESSION['user_id']);
                            $res_order_check = $db->select("SELECT MaDonHang FROM donhang WHERE IdNguoiDung = $uid_check ORDER BY MaDonHang DESC LIMIT 1");
                            if ($res_order_check && $res_order_check->num_rows > 0) {
                                $has_order = true;
                                $row_order = $res_order_check->fetch_assoc();
                                $latest_order_id = $row_order['MaDonHang'];
                            }
                        } ?>
                        <?php if ($has_order): ?>
                        <a href="orders.php"
                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition flex items-center group/item"><i
                                class="fas fa-clipboard-list w-5 text-gray-400 group-hover/item:text-green-500 transition mr-2"></i>Đơn
                            hàng của tôi</a>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="admin_product.php"
                            class="block px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-yellow-50 hover:text-yellow-600 transition flex items-center group/item"><i
                                class="fas fa-cog w-5 text-yellow-500 group-hover/item:text-yellow-600 transition mr-2"></i>Quản
                            trị Website</a>
                        <?php endif; ?>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="logout.php"
                            class="block px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 font-medium transition flex items-center group/item"><i
                                class="fas fa-sign-out-alt w-5 text-red-400 group-hover/item:text-red-500 transition mr-2"></i>Đăng
                            xuất</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex items-center gap-3">
                    <a href="login.php"
                        class="text-sm font-medium text-gray-600 hover:text-blue-600 transition hidden sm:block">Đăng
                        nhập</a>
                    <a href="register.php"
                        class="text-sm font-medium bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition whitespace-nowrap">Đăng
                        ký</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-100 hidden md:block bg-white overflow-hidden relative">
            <nav id="desktop-nav"
                class="container mx-auto px-4 max-w-7xl flex items-center justify-center gap-2 py-2 text-[13px] font-semibold text-gray-600 relative z-10">
                <a href="index.php"
                    class="nav-item hover:text-blue-600 flex items-center gap-2 px-5 py-2.5 rounded-full transition-colors relative z-10"><i
                        class="fas fa-home"></i> Trang chủ</a>
                <a href="categories.php?MaDanhMuc=2"
                    class="nav-item hover:text-blue-600 flex items-center gap-2 px-5 py-2.5 rounded-full transition-colors relative z-10"><i
                        class="fas fa-tshirt"></i> Áo</a>
                <a href="categories.php?MaDanhMuc=1"
                    class="nav-item hover:text-blue-600 flex items-center gap-2 px-5 py-2.5 rounded-full transition-colors relative z-10"><i
                        class="fas fa-socks"></i> Quần</a>
                <a href="categories.php?MaDanhMuc=3"
                    class="nav-item hover:text-blue-600 flex items-center gap-2 px-5 py-2.5 rounded-full transition-colors relative z-10"><i
                        class="fas fa-shoe-prints"></i> Giày</a>
            </nav>
        </div>

        <div id="mobile-search-panel"
            class="hidden md:hidden bg-white border-t border-gray-100 p-4 absolute w-full shadow-md z-40">
            <form action="search.php" method="GET" class="w-full">
                <div class="relative flex items-center w-full h-10 rounded-lg bg-gray-100 overflow-hidden">
                    <i class="fas fa-search text-gray-400 ml-3"></i>
                    <input type="text" name="keyword" placeholder="Tìm kiếm sản phẩm..."
                        class="w-full bg-transparent border-none outline-none px-3 text-sm h-full">
                </div>
            </form>
        </div>
        <div id="mobile-menu-panel"
            class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full shadow-xl z-40">
            <nav class="flex flex-col">
                <a href="index.php"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-home w-5 text-gray-400"></i> Trang chủ</a>
                <a href="categories.php?MaDanhMuc=2"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-tshirt w-5 text-gray-400"></i> Áo</a>
                <a href="categories.php?MaDanhMuc=1"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-socks w-5 text-gray-400"></i> Quần</a>
                <a href="categories.php?MaDanhMuc=3"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-shoe-prints w-5 text-gray-400"></i> Giày</a>
                <a href="categories.php?MaDanhMuc=4"
                    class="px-6 py-4 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-glasses w-5 text-gray-400"></i> Phụ kiện</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-4 max-w-7xl py-6 min-h-screen">
        <div class="text-sm text-gray-500 mb-6 mt-2 font-medium">
            <a href="index.php" class="hover:text-blue-600">Trang chủ</a> <i
                class="fas fa-chevron-right mx-1 text-xs text-gray-400"></i>
            <a href="cart.php" class="hover:text-blue-600">Giỏ hàng</a> <i
                class="fas fa-chevron-right mx-1 text-xs text-gray-400"></i>
            <span class="text-gray-800">Đơn hàng #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 text-white">
                        <h2 class="text-2xl font-bold flex items-center gap-3"><i class="fas fa-shipping-fast"></i> Theo
                            dõi đơn hàng #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h2>
                        <p class="opacity-90 mt-1">Trạng thái giao hàng</p>
                    </div>
                    <div class="p-8">
                        <div class="flex flex-col items-center text-center mb-8">
                            <p class="text-sm text-gray-500 mb-2">Ngày đặt hàng</p>
                            <p class="text-xl font-bold text-gray-800">
                                <?php echo date('d/m/Y H:i', strtotime($ngay_dat)); ?></p>
                        </div>

                        <!-- Các bước trạng thái -->
                        <div class="relative mb-8 sm:mb-12 mt-4">
                            <div
                                class="flex justify-between items-start w-full relative max-w-4xl mx-auto px-1 sm:px-4">
                                <div class="absolute top-5 sm:top-7 left-[12.5%] right-[12.5%] h-1.5 sm:h-2 z-0">
                                    <div class="absolute inset-0 bg-gray-200 rounded-full"></div>
                                </div>
                                <div id="progress-bar"
                                    class="absolute top-5 sm:top-7 left-[12.5%] h-1.5 sm:h-2 bg-gradient-to-r from-blue-500 to-green-500 rounded-full z-10 transition-all duration-[100ms] ease-linear"
                                    style="width: 0%;"></div>

                                <?php $status = intval($order['trangThai']); ?>
                                <?php $steps = [
                                    ['icon' => 'fa-receipt', 'title' => 'Đã đặt', 'time' => date('H:i', strtotime($ngay_dat))],
                                    ['icon' => 'fa-box', 'title' => 'Đóng gói', 'time' => date('H:i', strtotime($ngay_dat . ' +5 minutes'))],
                                    ['icon' => 'fa-truck', 'title' => 'Đang giao', 'time' => date('H:i', strtotime($ngay_dat . ' +6 minutes'))],
                                    ['icon' => 'fa-check-circle', 'title' => 'Giao thành công', 'time' => date('H:i', strtotime($ngay_dat . ' +15 minutes'))]
                                ]; ?>

                                <?php foreach ($steps as $i => $step): ?>
                                <div class="flex flex-col items-center text-center flex-1 px-1 z-10">
                                    <div id="icon-step-<?php echo $i ?>"
                                        class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center text-sm sm:text-lg font-bold transition-all duration-500 border-2 border-white z-10 mb-2 sm:mb-3 <?php echo $i < $status ? 'bg-blue-600 text-white shadow-md shadow-blue-300' : ($i == $status ? 'bg-blue-500 text-white shadow-lg shadow-blue-400 ring-4 ring-blue-100 animate-pulse scale-110' : 'bg-gray-200 text-gray-400 shadow-md'); ?>">
                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <p id="text-step-<?php echo $i ?>"
                                        class="text-[10px] sm:text-sm font-bold mb-1 <?php echo $i <= $status ? 'text-blue-600' : 'text-gray-400'; ?> leading-tight">
                                        <?php echo $step['title']; ?></p>
                                    <span
                                        class="text-[9px] sm:text-xs text-gray-500 leading-tight"><?php echo $step['time']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="status-message"
                            class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border-2 border-blue-200 mt-6">
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3 block"></i>
                            <h3 class="text-xl font-bold text-blue-800 mb-2">Đang theo dõi đơn hàng</h3>
                            <p class="text-blue-700">Vui lòng đợi đơn hàng của bạn - Chúng tôi sẽ cố gắng giao hàng
                                nhanh nhất có thể ;)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6 text-white">
                        <h2 class="text-2xl font-bold flex items-center gap-3"><i class="fas fa-shopping-bag"></i> Sản
                            phẩm trong đơn hàng (<?php echo $order_items ? $order_items->num_rows : 0 ?> sản phẩm)</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php 
                        $tam_tinh = 0;
                        if ($order_items && $order_items->num_rows > 0): 
                            $order_items->data_seek(0);
                            while ($item = $order_items->fetch_assoc()):
                                $thanh_tien = $item['SoLuong'] * $item['GiaSanPham'];
                                $tam_tinh += $thanh_tien;
                        ?>
                        <div class="p-6 hover:bg-gray-50 transition-all">
                            <div class="flex gap-6 items-start">
                                <a href="chitiet.php?id=<?= $item['MaSanPham'] ?>">
                                    <img src="public/images/<?= htmlspecialchars($item['hinh']) ?>"
                                        alt="<?= htmlspecialchars($item['TenSanPham']) ?>"
                                        class="w-24 h-28 object-cover rounded-xl shadow-sm flex-shrink-0">
                                </a>
                                <div class="flex-1 min-w-0">
                                    <a href="chitiet.php?id=<?= $item['MaSanPham'] ?>" class="block mb-3">
                                        <h3
                                            class="font-bold text-lg text-gray-800 line-clamp-2 hover:text-blue-600 transition-colors">
                                            <?= htmlspecialchars($item['TenSanPham']) ?></h3>
                                    </a>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-500">Phân loại:
                                                <?= htmlspecialchars($item['PhanLoai'] ?? 'Mặc định') ?></p>
                                            <p class="text-sm font-semibold text-gray-600 mt-1">x
                                                <?= $item['SoLuong'] ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-xl text-red-600">
                                                <?= number_format($item['GiaSanPham']) ?>₫</p>
                                            <small
                                                class="text-gray-500 block"><?= number_format($thanh_tien) ?>₫</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="p-12 text-center"><i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">Chưa có sản phẩm trong đơn hàng này</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-gray-800"><i
                            class="fas fa-map-marker-alt text-orange-500"></i> Địa chỉ giao hàng</h3>
                    <div class="space-y-2">
                        <p class="font-bold text-lg text-gray-800"><?= htmlspecialchars($order['TenNguoiNhan']) ?></p>
                        <p class="text-sm text-gray-600">📞 <?= htmlspecialchars($order['SoDienThoai']) ?></p>
                        <p class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($order['diachi'])) ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-gray-800"><i
                            class="fas fa-receipt text-green-500"></i> Tóm tắt thanh toán</h3>
                    <?php 
                    $shipping = 30000;
                    $total_discount = ($tam_tinh + $shipping) - $order['TongTien'];
                    $total_discount = $total_discount > 0 ? $total_discount : 0;
                    ?>
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between py-2"><span class="text-gray-700">Tạm tính:</span><span
                                class="font-bold text-lg"><?= number_format($tam_tinh) ?>₫</span></div>
                        <div class="flex justify-between py-2"><span class="text-gray-700">Phí vận chuyển:</span><span
                                class="font-bold text-green-600"><?= number_format($shipping) ?>₫</span></div>
                        <?php if ($total_discount > 0): ?>
                        <div class="flex justify-between py-2 text-green-600"><span>Giảm giá:</span><span
                                class="font-bold">-<?= number_format($total_discount) ?>₫</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-4"><span
                                class="text-2xl font-black text-gray-900">Tổng thanh toán:</span><span
                                class="text-2xl font-black text-red-600"><?= number_format($order['TongTien']) ?>₫</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4"><i
                                class="fas fa-<?= $order['MaPhuongThuc']==1 ? 'truck' : ($order['MaPhuongThuc']==2 ? 'wallet' : 'credit-card') ?> text-blue-500"></i>
                            <?= htmlspecialchars($payment_method_name) ?></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 text-center">
                    <button
                        onclick="Swal.fire('Hỗ trợ', 'Liên hệ CSKH để cập nhật trạng thái đơn hàng', 'info').then((result) => { if (result.isConfirmed) { document.getElementById('chatToggleBtn').click(); } })"
                        class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold py-4 px-6 rounded-xl shadow-xl hover:from-orange-600 hover:to-orange-700 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 text-lg flex items-center justify-center gap-3 mx-auto"><i
                            class="fas fa-headset"></i> Liên hệ hỗ trợ đơn hàng</button>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 pt-16 pb-8 border-t-4 border-blue-600 mt-auto">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                <div>
                    <h3 class="text-2xl font-black text-blue-500 italic tracking-tighter mb-4">TTP<span
                            class="text-yellow-500">SHOP</span></h3>
                    <p class="text-sm text-gray-400 leading-relaxed mb-6">Hệ thống mua sắm thời trang trực tuyến uy tín,
                        mang đến cho bạn những xu hướng mới nhất với chất lượng tuyệt vời.</p>
                    <div class="flex gap-4"><a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-blue-600 text-white transition"><i
                                class="fab fa-facebook-f"></i></a><a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-pink-600 text-white transition"><i
                                class="fab fa-instagram"></i></a><a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-red-600 text-white transition"><i
                                class="fab fa-youtube"></i></a></div>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Hỗ trợ khách hàng</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-blue-400 transition">Trung tâm trợ giúp</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Hướng dẫn mua hàng</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Chính sách vận chuyển</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Chính sách đổi trả</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Về TTP Shop</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-blue-400 transition">Giới thiệu</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Tuyển dụng</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Điều khoản bảo mật</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Liên hệ truyền thông</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Thanh toán</h4>
                    <div class="flex gap-2 flex-wrap">
                        <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                                class="fab fa-cc-visa text-blue-800 text-xl"></i></div>
                        <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                                class="fab fa-cc-mastercard text-red-600 text-xl"></i></div>
                        <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                                class="fab fa-cc-paypal text-blue-500 text-xl"></i></div>
                        <div
                            class="w-12 h-8 bg-gray-800 border border-gray-700 rounded flex items-center justify-center text-xs font-bold">
                            COD</div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                <p>© 2026 TTP Shop. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>

    <?php include 'chatbox.php'; ?>

    <script>
    (function() {
        var input = document.getElementById('search-input');
        var box = document.getElementById('search-suggest-box');
        var list = document.getElementById('search-suggest-list');
        if (input && box && list) {
            var timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                var q = this.value.trim();
                if (q.length < 1) {
                    hideSuggest();
                    return;
                }
                timer = setTimeout(function() {
                    fetchSuggest(q);
                }, 280);
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') hideSuggest();
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#search-form')) hideSuggest();
            });

            function fetchSuggest(q) {
                fetch('search_suggest.php?q=' + encodeURIComponent(q))
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (!data || data.length === 0) {
                            hideSuggest();
                            return;
                        }
                        renderSuggest(data, q);
                    })
                    .catch(function() {
                        hideSuggest();
                    });
            }

            function renderSuggest(data, q) {
                var html = '';
                data.forEach(function(item) {
                    var ten = item.ten.replace(new RegExp('(' + escapeRe(q) + ')', 'gi'),
                        '<span class="font-bold text-blue-600">$1</span>');
                    html += '<a href="chitiet.php?id=' + item.id +
                        '" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition border-b border-gray-50 last:border-0"><img src="public/images/' +
                        item.hinh +
                        '" class="w-10 h-10 object-cover rounded-lg border border-gray-100"><div class="flex-1 min-w-0"><div class="text-sm text-gray-800 truncate">' +
                        ten + '</div></div><span class="text-sm font-bold text-red-600">' + item.gia_fmt +
                        '</span></a>';
                });
                list.innerHTML = html;
                box.classList.remove('hidden');
            }

            function hideSuggest() {
                box.classList.add('hidden');
                list.innerHTML = '';
            }

            function escapeRe(s) {
                return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
        }
    })();

    document.addEventListener("DOMContentLoaded", function() {
        // --- Các biến trạng thái ---
        var DB_STATUS = <?= $db_status ?>; // 0,1,2,3,4
        var ELAPSED_GIAO = <?= $so_giay_dang_giao ?>; // giây đã trôi qua khi đang giao (nếu có)
        var DURATION_GIAO = 8 * 60; // 8 phút = 480 giây (có thể điều chỉnh)
        var progressBar = document.getElementById('progress-bar');
        var statusBox = document.getElementById('status-message');
        var tickInterval = null;
        var stopFlag = false;
        var isUpdatingToComplete = false; // chống gọi nhiều lần

        // Hàm dừng toàn bộ theo dõi
        function stopTracking() {
            if (tickInterval) {
                clearTimeout(tickInterval);
                tickInterval = null;
            }
            stopFlag = true;
        }

        // Lấy trạng thái mới nhất từ server (polling)
        function refreshOrderStatus() {
            fetch(window.location.href + '?ajax_check_status=1')
                .then(res => res.json())
                .then(data => {
                    if (data.status !== undefined && data.status !== DB_STATUS) {
                        DB_STATUS = data.status;
                        if (DB_STATUS === 3) {
                            updateDone(3);
                            stopTracking();
                        } else if (DB_STATUS === 4) {
                            updateDone(4);
                            stopTracking();
                        } else if (DB_STATUS === 2 && !stopFlag) {
                            // Nếu server vẫn đang giao, cập nhật giao diện (không reset timer)
                            updateUIForStatus(DB_STATUS, Math.max(0, Math.ceil(DURATION_GIAO -
                                ELAPSED_GIAO)));
                        }
                    }
                })
                .catch(err => console.warn("refreshOrderStatus error:", err));
        }

        // Cập nhật giao diện khi đã kết thúc (giao thành công hoặc hủy)
        function updateDone(finalStatus) {
            if (finalStatus === 3) {
                if (progressBar) progressBar.style.width = '75%';
                updateIcons(3);
                updateStatusMessage(3, 0);
            } else if (finalStatus === 4) {
                if (progressBar) progressBar.style.width = '75%';
                updateIcons(4);
                updateStatusMessage(4, 0);
            }
            stopTracking();
        }

        // Cập nhật giao diện cho trạng thái hiện tại (không dừng)
        function updateUIForStatus(activeStep, remainingSecs) {
            if (activeStep === 0) {
                updateIcons(0);
                updateStatusMessage(0, 0);
                if (progressBar) progressBar.style.width = '0%';
            } else if (activeStep === 1) {
                updateIcons(1);
                updateStatusMessage(1, 0);
                if (progressBar) progressBar.style.width = '25%';
            } else if (activeStep === 2) {
                updateIcons(2);
                updateStatusMessage(2, remainingSecs);
                if (progressBar) progressBar.style.width = '50%';
            } else if (activeStep === 3) {
                updateIcons(3);
                updateStatusMessage(3, 0);
                if (progressBar) progressBar.style.width = '75%';
            } else if (activeStep === 4) {
                updateIcons(4);
                updateStatusMessage(4, 0);
                if (progressBar) progressBar.style.width = '75%';
            }
        }

        // Cập nhật màu sắc icon các bước
        function updateIcons(activeStep) {
            var baseIcon =
                "w-10 h-10 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center text-sm sm:text-lg font-bold transition-all duration-500 border-2 border-white z-10 mb-2 sm:mb-3 ";
            var baseText = "text-[10px] sm:text-sm font-bold mb-1 leading-tight ";
            for (var i = 0; i <= 3; i++) {
                var icon = document.getElementById('icon-step-' + i);
                var text = document.getElementById('text-step-' + i);
                if (!icon || !text) continue;
                if (i < activeStep) {
                    icon.className = baseIcon + "bg-blue-600 text-white shadow-md shadow-blue-300";
                    text.className = baseText + "text-blue-600";
                } else if (i === activeStep) {
                    if (i === 3) {
                        icon.className = baseIcon +
                            "bg-green-500 text-white shadow-lg shadow-green-300 animate-bounce scale-110";
                        text.className = baseText + "text-green-600";
                    } else {
                        icon.className = baseIcon +
                            "bg-blue-500 text-white shadow-lg shadow-blue-400 ring-4 ring-blue-100 animate-pulse scale-110";
                        text.className = baseText + "text-blue-600";
                    }
                } else {
                    icon.className = baseIcon + "bg-gray-200 text-gray-400 shadow-md";
                    text.className = baseText + "text-gray-400";
                }
            }
        }

        // Cập nhật nội dung khung thông báo
        function updateStatusMessage(activeStep, remainingSecs) {
            if (!statusBox) return;
            if (activeStep === 0) {
                statusBox.innerHTML =
                    '<i class="fas fa-receipt text-3xl text-blue-500 mb-3 block"></i><h3 class="text-xl font-bold text-blue-800 mb-2">Đơn hàng đã được đặt</h3><p class="text-blue-700">Chờ shop xác nhận và đóng gói.</p>';
            } else if (activeStep === 1) {
                statusBox.innerHTML =
                    '<i class="fas fa-box text-3xl text-yellow-500 mb-3 block"></i><h3 class="text-xl font-bold text-yellow-800 mb-2">Đang đóng gói</h3><p class="text-yellow-700">Đơn hàng đang được chuẩn bị và đóng gói cẩn thận.</p>';
            } else if (activeStep === 2) {
                var mins = Math.floor(remainingSecs / 60);
                var secs = remainingSecs % 60;
                var timeStr = mins > 0 ? mins + ' phút ' + secs + ' giây' : secs + ' giây';
                statusBox.innerHTML =
                    '<i class="fas fa-truck text-3xl text-purple-500 mb-3 block animate-bounce"></i><h3 class="text-xl font-bold text-purple-800 mb-2">Đang giao hàng đến bạn!</h3><p class="text-purple-700">Dự kiến hoàn thành sau khoảng <strong>' +
                    timeStr + '</strong></p>';
            } else if (activeStep === 3) {
                statusBox.innerHTML =
                    '<i class="fas fa-check-circle text-3xl text-green-500 animate-bounce mb-3 block"></i><h3 class="text-xl font-bold text-green-800 mb-2">Giao thành công!</h3><p class="text-green-700">Cảm ơn bạn đã mua sắm tại TTP Shop ❤️</p>';
            } else if (activeStep === 4) {
                statusBox.innerHTML =
                    '<i class="fas fa-times-circle text-3xl text-red-400 mb-3 block"></i><h3 class="text-xl font-bold text-red-700 mb-2">Đơn hàng đã bị hủy</h3><p class="text-red-500">Vui lòng liên hệ hỗ trợ nếu cần thêm thông tin.</p>';
            }
        }

        // Gửi yêu cầu cập nhật trạng thái lên server (3 = giao thành công)
        function requestCompleteDelivery() {
            if (isUpdatingToComplete) return Promise.resolve(false);
            isUpdatingToComplete = true;
            return fetch(window.location.href + '?ajax_update=3')
                .then(res => {
                    if (res.ok) return res.text();
                    throw new Error('HTTP ' + res.status);
                })
                .then(() => {
                    DB_STATUS = 3;
                    updateDone(3);
                    return true;
                })
                .catch(err => {
                    console.warn("Không thể cập nhật trạng thái giao thành công:", err);
                    // Cho phép thử lại sau 2 giây
                    setTimeout(() => {
                        isUpdatingToComplete = false;
                    }, 2000);
                    return false;
                });
        }

        // Vòng lặp tick xử lý thời gian
        function tick() {
            if (stopFlag) return;

            // Xử lý các trạng thái không phải "đang giao"
            if (DB_STATUS !== 2) {
                if (DB_STATUS === 3 || DB_STATUS === 4) {
                    updateDone(DB_STATUS);
                    return;
                } else {
                    updateUIForStatus(DB_STATUS, 0);
                    tickInterval = setTimeout(tick, 200);
                    return;
                }
            }

            // DB_STATUS === 2: đang giao hàng
            var now = Date.now();
            if (!tick.lastNow) tick.lastNow = now;
            var dtSec = (now - tick.lastNow) / 1000;
            if (dtSec > 0) ELAPSED_GIAO += dtSec;
            tick.lastNow = now;

            var remainingSecs = Math.max(0, Math.ceil(DURATION_GIAO - ELAPSED_GIAO));
            updateUIForStatus(2, remainingSecs);

            // Kiểm tra nếu đã hết thời gian dự kiến và chưa yêu cầu chuyển trạng thái
            if (ELAPSED_GIAO >= DURATION_GIAO && !isUpdatingToComplete) {
                requestCompleteDelivery().then(success => {
                    if (!success && DB_STATUS === 2) {
                        // Nếu thất bại, thử lại sau 1 giây
                        if (tickInterval) clearTimeout(tickInterval);
                        tickInterval = setTimeout(tick, 1000);
                    }
                });
                return; // chờ kết quả, không gọi tick ngay
            }

            tickInterval = setTimeout(tick, 200);
        }

        // Xử lý ngay khi tải trang, nếu đơn hàng đã hoàn tất hoặc hủy
        if (DB_STATUS === 3) {
            updateDone(3);
        } else if (DB_STATUS === 4) {
            updateDone(4);
        } else {
            tick.lastNow = Date.now();
            tick();
            setInterval(refreshOrderStatus, 5000); // mỗi 5 giây kiểm tra lại
        }

        // Xử lý menu mobile
        var btnMenu = document.getElementById('mobile-menu-btn');
        var panelMenu = document.getElementById('mobile-menu-panel');
        var btnSearch = document.getElementById('mobile-search-btn');
        var panelSearch = document.getElementById('mobile-search-panel');
        if (btnMenu && panelMenu) btnMenu.addEventListener('click', function() {
            panelMenu.classList.toggle('hidden');
            if (panelSearch) panelSearch.classList.add('hidden');
        });
        if (btnSearch && panelSearch) btnSearch.addEventListener('click', function() {
            panelSearch.classList.toggle('hidden');
            if (panelMenu) panelMenu.classList.add('hidden');
        });
    });
    </script>
</body>

</html>