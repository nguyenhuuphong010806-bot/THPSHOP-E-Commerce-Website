<?php
// Logic chung cho toàn bộ trang web, bao gồm xử lý giỏ hàng liên kết với tài khoản đăng nhập và nhận diện menu active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//1. LOGIC GIỎ HÀNG LIÊN KẾT VỚI TÀI KHOẢN ĐĂNG NHẬP
if (isset($_SESSION['user_id'])) {
    // Nếu người dùng đã đăng nhập nhưng trước đó có giỏ hàng của một user khác, xóa giỏ hàng cũ để tránh lộn xộn
    if (isset($_SESSION['cart_user_id']) && $_SESSION['cart_user_id'] != $_SESSION['user_id']) {
        unset($_SESSION['cart']);
        if (isset($_COOKIE['shopping_cart'])) {
            setcookie('shopping_cart', '', time() - 3600, '/');
        }
    }
    $_SESSION['cart_user_id'] = $_SESSION['user_id'];
    // Nếu giỏ hàng đang rỗng nhưng có cookie, đồng bộ từ cookie vào session
    if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
        $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
    }
    // Ngược lại, nếu giỏ hàng trong session có dữ liệu nhưng cookie không tồn tại hoặc khác, đồng bộ từ session ra cookie
} else {
    unset($_SESSION['cart']);
    // Nếu có cookie giỏ hàng cũ từ một user đã đăng xuất, xóa cookie đó để tránh nhầm lẫn cho người dùng mới
    if (isset($_SESSION['cart_user_id'])) {
        unset($_SESSION['cart_user_id']);
    }
}
require_once "database.php";
if (!isset($db)) {
    $db = new Database();
}

// Tính tổng số lượng giỏ hàng
$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}
// === 2. LOGIC NHẬN DIỆN MENU ACTIVE (SLIDING BACKGROUND) ===
$current_page = basename($_SERVER['PHP_SELF']);
$active_menu = 'none';

if ($current_page == 'index.php') {
    $active_menu = 'home';
} elseif ($current_page == 'categories.php' && isset($_GET['MaDanhMuc'])) {
    $active_menu = (string)$_GET['MaDanhMuc'];
} elseif ($current_page == 'chitiet.php' && isset($_GET['id'])) {
    // KHI Ở TRANG CHI TIẾT: Lấy ID sản phẩm từ URL và tìm danh mục tương ứng
    $sp_id = (int)$_GET['id']; // Ép kiểu int để bảo mật
    $sql_get_cat = "SELECT MaDanhMuc FROM product WHERE MaSanPham = $sp_id LIMIT 1";
    $res_cat = $db->select($sql_get_cat);
    
    if ($res_cat && $res_cat->num_rows > 0) {
        $row_cat = $res_cat->fetch_assoc();
        $active_menu = (string)$row_cat['MaDanhMuc']; // Gán menu active bằng ID danh mục của sản phẩm
    }
} elseif ($current_page == 'coupons.php') {
    $active_menu = 'coupons';
} elseif ($current_page == 'search.php') {
    $kw = isset($_GET['keyword']) ? mb_strtolower(trim($_GET['keyword']), 'UTF-8') : '';
    if (strpos($kw, 'áo') !== false) $active_menu = '2';
    elseif (strpos($kw, 'quần') !== false) $active_menu = '1';
    elseif (strpos($kw, 'giày') !== false) $active_menu = '3';
    elseif (strpos($kw, 'phụ kiện') !== false || strpos($kw, 'phụ') !== false) $active_menu = '4';
}
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./public/images/icon_web.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* HIỆU ỨNG CHUYỂN TRANG MƯỢT MÀ (FADE + SLIDE) */
    main#main-content {
        transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1;
        transform: translateY(0);
        will-change: opacity, transform;
        /* Bật tăng tốc phần cứng GPU */
    }

    main#main-content.is-loading {
        opacity: 0;
        transform: translateY(15px);
        /* Trượt nhẹ xuống 15px khi biến mất */
    }

    .anim-fade {
        animation: fadeIn 0.2s ease-out;
    }

    .anim-scale {
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }

    /* Tùy chỉnh thanh cuộn ẩn cho danh mục */
    .hide-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Smooth page transition */
    body.nav-transition {
        animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Xử lý background riêng cho từng trang */
    <?php if ($current_page=='chitiet.php'): ?>body {
        background: #ffffff;
        /* Màu trắng nguyên bản cho trang chi tiết */
        min-height: 100vh;
        display: flex;

        flex-direction: column;
    }

    <?php elseif ($current_page=='user_profile.php'): ?>body {
        background: white;
        /* Màu trắng nguyên bản cho trang chi tiết */
        min-height: 100vh;
        display: flex;

        flex-direction: column;
    }

    <?php elseif ($current_page=='search.php'): ?>body {
        background: #f3f4f6;
        /* Nền xám nhạt dành riêng cho trang tài khoản */

    }

    <?php else: ?>body {
        background: linear-gradient(135deg, #0056b3, #f39c12);
        background-attachment: fixed;
        /* Thêm dòng này để cố định gradient khi scroll */
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    <?php endif;
    ?>

    /* Ẩn thanh cuộn cho IE và Edge */
    .hide-scroll {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Giới hạn số dòng cho tên sản phẩm */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body>

    <header class="bg-white shadow-sm sticky top-0 z-50 transition-all duration-300" id="main-header">
        <div class="bg-blue-600 text-white text-xs py-1.5 px-4 text-center hidden md:block">
            Miễn phí vận chuyển cho đơn hàng từ 300K - Trải nghiệm mua sắm tuyệt vời cùng TTP Shop!
        </div>

        <div class="container mx-auto px-4 py-3 md:py-4 max-w-7xl flex items-center justify-between gap-4 md:gap-6">
            <div class="flex md:hidden items-center gap-4 flex-shrink-0">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-blue-600 text-xl focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
                <button id="mobile-search-btn" class="text-gray-600 hover:text-blue-600 text-xl focus:outline-none">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <a href="index.php" class="flex items-center justify-center flex-1 md:flex-none">
                <div class="text-2xl font-bold text-blue-600 tracking-tighter"><img src="public/images/icon_web.png"
                        alt="home" class="w-24 h-12 md:w-30 md:h-20 object-contain"></div>
            </a>

            <form action="search.php" method="GET" class="flex-1 max-w-2xl hidden md:block" id="search-form"
                autocomplete="off">
                <div class="relative">
                    <div
                        class="relative flex items-center w-full h-11 rounded-full bg-gray-100 border border-transparent focus-within:border-blue-500 focus-within:bg-white focus-within:shadow-sm overflow-hidden transition-all">
                        <i class="fas fa-search text-gray-400 ml-4"></i>
                        <input type="text" name="keyword" id="search-input" maxlength="100"
                            value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                            placeholder="Tìm kiếm sản phẩm, danh mục..."
                            class="w-full bg-transparent border-none outline-none px-3 text-sm text-gray-700 placeholder-gray-500 h-full">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 h-full font-medium transition text-sm flex items-center justify-center">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <!-- Autocomplete dropdown -->
                    <div id="search-suggest-box"
                        class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 z-[200] hidden overflow-hidden">
                        <div id="search-suggest-list"></div>
                    </div>
                </div>
            </form>

            <div class="flex items-center gap-4 md:gap-6 flex-shrink-0">

                <div class="flex items-center gap-4">
                    <div class="relative group z-50">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php"
                            class="relative text-gray-700 hover:text-blue-600 transition flex items-center py-2 h-full">
                            <i class="fas fa-shopping-cart text-xl md:text-2xl"></i>
                            <span id="cart-badge"
                                class="<?php echo ($total_items > 0) ? 'flex' : 'hidden'; ?> absolute -top-1 -right-2 border-2 border-white bg-red-600 text-white text-[10px] font-bold w-4 h-4 md:w-5 md:h-5 items-center justify-center rounded-full shadow-sm">
                                <?php echo $total_items; ?>
                            </span>
                        </a>

                        <div id="cart-dropdown-container"
                            class="absolute right-0 top-full mt-2 w-[350px] sm:w-[400px] bg-white rounded shadow-[0_1px_3.125rem_0_rgba(0,0,0,0.2)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-[100] before:content-[''] before:absolute before:-top-4 before:left-0 before:w-full before:h-4 before:bg-transparent cursor-default text-left">
                            <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                            <div class="text-gray-400 text-sm p-3 capitalize border-b border-gray-100">Sản phẩm mới thêm
                            </div>
                            <div class="max-h-[40vh] overflow-y-auto custom-scrollbar">
                                <?php 
                            $cart_preview = array_reverse($_SESSION['cart'], true);
                            $count = 0;
                            foreach ($cart_preview as $id => $item): 
                                if($count >= 5) break; 
                                $count++;
                                $tenSP = $item['ten'] ?? $item['TenSanPham'] ?? 'Tên sản phẩm';
                                $giaSP = $item['gia'] ?? $item['GiaSanPham'] ?? 0;
                                $hinhSP = $item['hinh'] ?? $item['HinhAnh'] ?? 'default.png';
                                $slSP = $item['soluong'] ?? 1;
                            ?>
                                <a href="chitiet.php?id=<?php echo $id; ?>"
                                    class="flex items-center p-3 hover:bg-gray-50 transition cursor-pointer border-b border-gray-50 last:border-0">
                                    <img src="./public/images/<?php echo htmlspecialchars($hinhSP); ?>" alt="Product"
                                        class="w-10 h-10 object-cover border border-gray-200">
                                    <div class="ml-3 flex-1 overflow-hidden">
                                        <div class="text-sm text-gray-800 truncate font-medium">
                                            <?php echo htmlspecialchars($tenSP); ?></div>
                                        <div class="text-xs text-gray-500 mt-0.5">Số lượng: <span
                                                class="text-gray-900 font-semibold"><?php echo $slSP; ?></span></div>
                                    </div>
                                    <div class="text-red-500 text-sm font-bold ml-4">
                                        ₫<?php echo number_format($giaSP); ?>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-3 bg-gray-50 flex justify-between items-center rounded-b">
                                <span class="text-xs text-gray-500 font-medium">Có <span
                                        id="cart-dropdown-count"><?php echo $total_items; ?></span> sản phẩm trong
                                    giỏ</span>
                                <a href="cart.php"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-sm text-sm capitalize transition shadow-sm">Xem
                                    giỏ hàng</a>
                            </div>
                            <?php else: ?>
                            <div class="p-14 text-center flex flex-col items-center justify-center">
                                <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/9bdd8040b334d31946f49e36beaf32db.png"
                                    alt="Empty Cart" class="w-24 h-24 mb-3">
                                <div class="text-sm text-gray-500 capitalize">Chưa có sản phẩm</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php else: ?>
                        <button onclick="requireLogin()"
                            class="relative text-gray-700 hover:text-blue-600 transition flex items-center py-2 h-full cursor-pointer bg-transparent border-none">
                            <i class="fas fa-shopping-cart text-xl md:text-2xl"></i>
                        </button>
                        <?php endif; ?>
                    </div>
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
                        <span class="text-black group-hover:text-black transition">Xin chào,</span>
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                            class="w-8 h-8 md:w-9 md:h-9 rounded-full object-cover border border-gray-200">
                        <span
                            class="text-sm font-semibold text-gray-700 hidden md:block max-w-[100px] truncate"><?php echo htmlspecialchars($ten_user); ?></span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                    </div>

                    <div
                        class="absolute right-0 top-full mt-1 w-52 bg-white rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-100 origin-top-right transform scale-95 group-hover:scale-100 z-50 before:content-[''] before:absolute before:-top-4 before:left-0 before:w-full before:h-4 before:bg-transparent">
                        <div class="px-4 py-2 border-b border-gray-100 md:hidden block mb-1">
                            <span
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
                        $res_order = $db->select("SELECT MaDonHang FROM donhang WHERE IdNguoiDung = $uid_check ORDER BY MaDonHang DESC LIMIT 1");
                        if ($res_order && $res_order->num_rows > 0) {
                            $has_order = true;
                            $row_order = $res_order->fetch_assoc();
                            $latest_order_id = $row_order['MaDonHang'];
                        }
                    }
                    ?>
                        <?php if ($has_order): ?>
                        <a href="orders.php"
                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition flex items-center group/item"><i
                                class="fas fa-clipboard-list w-5 text-gray-400 group-hover/item:text-green-500 transition mr-2"></i>Đơn
                            hàng của tôi</a>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="admin_dashboard.php"
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
                    <a href="login.php" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Đăng
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

                <a href="index.php" data-nav-id="home"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == 'home' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="categories.php?MaDanhMuc=2" data-nav-id="cat-2"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == '2' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-tshirt"></i> Áo
                </a>
                <a href="categories.php?MaDanhMuc=1" data-nav-id="cat-1"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == '1' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-socks"></i> Quần
                </a>
                <a href="categories.php?MaDanhMuc=3" data-nav-id="cat-3"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == '3' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-shoe-prints"></i> Giày
                </a>
                <a href="categories.php?MaDanhMuc=4" data-nav-id="cat-4"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == '4' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-glasses"></i> Phụ kiện
                </a>
                <a href="coupons.php" data-nav-id="coupons"
                    class="nav-item flex items-center gap-2 px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 hover:-translate-y-0.5 hover:shadow-md active:scale-95 relative z-10 <?php echo $active_menu == 'coupons' ? 'active-nav text-blue-600' : 'hover:text-blue-600'; ?>">
                    <i class="fas fa-ticket-alt"></i> Mã giảm giá
                </a>
                <div id="nav-indicator"
                    class="absolute bg-gradient-to-r from-blue-100 to-indigo-100 shadow-md transition-all duration-300 ease-out pointer-events-none rounded-full z-0 opacity-0"
                    style="width: 0; height: 0; left: 0; top: 0;"></div>

            </nav>
        </div>

        <div id="mobile-search-panel"
            class="hidden md:hidden bg-white border-t border-gray-100 p-4 absolute w-full shadow-2xl z-[60]">
            <form action="search.php" method="GET" class="w-full">
                <div class="relative flex items-center w-full h-10 rounded-lg bg-gray-100 overflow-hidden">
                    <i class="fas fa-search text-gray-400 ml-3"></i>
                    <input type="text" name="keyword" maxlength="100"
                        value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                        placeholder="Tìm kiếm sản phẩm..."
                        class="w-full bg-transparent border-none outline-none px-3 text-sm h-full">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 h-full font-medium transition text-sm flex items-center justify-center">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div id="mobile-menu-panel"
            class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full shadow-xl z-40">
            <nav class="flex flex-col">
                <a href="index.php"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-home w-5 text-gray-400 text-center"></i> Trang chủ</a>
                <a href="categories.php?MaDanhMuc=2"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-tshirt w-5 text-gray-400 text-center"></i> Áo</a>
                <a href="categories.php?MaDanhMuc=1"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-socks w-5 text-gray-400 text-center"></i> Quần</a>
                <a href="categories.php?MaDanhMuc=3"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-shoe-prints w-5 text-gray-400 text-center"></i> Giày</a>
                <a href="categories.php?MaDanhMuc=4"
                    class="px-6 py-4 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3"><i
                        class="fas fa-glasses w-5 text-gray-400 text-center"></i> Phụ kiện</a>
                <a href="coupons.php"
                    class="px-6 py-4 border-b border-gray-50 text-gray-700 font-medium hover:bg-gray-50 hover:text-blue-600 flex items-center gap-3 <?php echo $active_menu == 'coupons' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-ticket-alt w-5 text-gray-400 text-center"></i> Mã giảm giá
                </a>
            </nav>
        </div>
    </header>
    <script>
    // Hàm áp dụng màu nền dựa trên URL hiện tại
    function applyCorrectBackground() {
        const path = window.location.pathname;
        const body = document.body;

        // 1. Trả về nền trắng cho trang chi tiết & tài khoản
        if (path.includes('chitiet.php') || path.includes('user_profile.php')) {
            body.style.background = '#ffffff';
        }
        // 2. Trả về nền xám cho trang tìm kiếm
        else if (path.includes('search.php')) {
            body.style.background = '#f3f4f6';
        }
        // 3. Phủ lại gradient gốc cho trang chủ và danh mục
        else {
            body.style.background = 'linear-gradient(135deg, #0056b3, #f39c12)';
            body.style.backgroundAttachment = 'fixed';
        }
    }

    // Lắng nghe sự thay đổi của DOM để bám sát hiệu ứng "chuyển trang mượt mà"
    document.addEventListener('DOMContentLoaded', () => {
        let lastPath = window.location.pathname;

        const observer = new MutationObserver(() => {
            if (window.location.pathname !== lastPath) {
                lastPath = window.location.pathname;
                applyCorrectBackground();
            }
        });

        // Theo dõi toàn bộ sự kiện hoán đổi thẻ bên trong body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    // ============================================================
    // AUTOCOMPLETE TÌM KIẾM
    // ============================================================
    (function() {
        var input = document.getElementById('search-input');
        var box = document.getElementById('search-suggest-box');
        var list = document.getElementById('search-suggest-list');
        if (!input || !box || !list) return;

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
                var stars = '';
                var s = Math.round(item.sao);
                for (var i = 1; i <= 5; i++) {
                    stars += i <= s ?
                        '<i class="fas fa-star text-yellow-400 text-[9px]"></i>' :
                        '<i class="far fa-star text-gray-300 text-[9px]"></i>';
                }
                html += '<a href="chitiet.php?id=' + item.id + '"' +
                    ' class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition border-b border-gray-50 last:border-0"' +
                    ' onclick="hideSuggestGlobal()">' +
                    '<img src="public/images/' + item.hinh + '" loading="lazy"' +
                    '     class="w-10 h-10 object-cover rounded-lg border border-gray-100 flex-shrink-0">' +
                    '<div class="flex-1 min-w-0">' +
                    '  <div class="text-sm text-gray-800 truncate">' + ten + '</div>' +
                    '  <div class="flex items-center gap-1 mt-0.5">' + stars +
                    '    <span class="text-[10px] text-gray-400 ml-1">' + item.danh_muc + '</span>' +
                    '  </div>' +
                    '</div>' +
                    '<span class="text-sm font-bold text-red-600 flex-shrink-0">' + item.gia_fmt +
                    '</span>' +
                    '</a>';
            });
            html += '<a href="search.php?keyword=' + encodeURIComponent(q) +
                '" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 text-sm text-blue-600 font-semibold hover:bg-blue-50 transition">' +
                '<i class="fas fa-search text-xs"></i> Xem tất cả kết quả cho "' + escapeHtml(q) + '"</a>';
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

        function escapeHtml(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        window.hideSuggestGlobal = hideSuggest;
    })();
    </script>