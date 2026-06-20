<?php
session_start();
require_once "database.php";

// === 1. KIỂM TRA ĐĂNG NHẬP ===
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// === TÍNH TỔNG SỐ LƯỢNG GIỎ HÀNG (Hiển thị badge đỏ trên icon) ===
$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}
// ==============================================================

$db = new Database();
$user_id = intval($_SESSION['user_id']); // Luôn ép kiểu int, không bao giờ dùng raw session value trong SQL

// === 2. API XÓA 1 SẢN PHẨM KHỎI YÊU THÍCH (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'remove') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = intval($data['id']);
    
    $sql = "DELETE FROM wishlist WHERE IdNguoiDung = $user_id AND MaSanPham = $product_id";
    if ($db->execute($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// === 3. API XÓA TẤT CẢ YÊU THÍCH (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'clear_all') {
    header('Content-Type: application/json');
    $sql = "DELETE FROM wishlist WHERE IdNguoiDung = $user_id";
    if ($db->execute($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// === 4. LẤY DANH SÁCH SẢN PHẨM YÊU THÍCH TỪ CSDL ===
$sql = "SELECT p.MaSanPham as id, p.TenSanPham as name, p.GiaSanPham as price, p.hinh as image, c.TenDanhMuc as category 
        FROM wishlist w 
        JOIN product p ON w.MaSanPham = p.MaSanPham 
        JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc 
        WHERE w.IdNguoiDung = $user_id
        ORDER BY w.NgayThem DESC";

$result = $db->select($sql);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id']; // Bắt buộc ép kiểu ID thành số nguyên ở đây để fix lỗi JS
        $row['image'] = 'public/images/' . $row['image'];
        $row['price'] = (int)$row['price'];
        $products[] = $row;
    }
}

// Mã hóa mảng PHP sang JSON để JS xử lý
$productsJSON = json_encode($products);
?>

<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm Yêu Thích - TTP Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    /* Tùy chỉnh thanh cuộn cho giỏ hàng mini */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</head>

<body class="bg-gray-50 flex flex-col min-h-screen text-gray-800">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="bg-blue-600 text-white text-xs py-1.5 px-4 text-center">
            Miễn phí vận chuyển cho đơn hàng từ 300K - Trải nghiệm mua sắm tuyệt vời cùng TTP Shop!
        </div>

        <div class="container mx-auto px-4 py-4 max-w-7xl flex items-center justify-between gap-6">
            <a href="index.php" class="flex items-center gap-2">
                <div class="text-2xl font-bold text-blue-600"><img src="public/images/favourite.jpg" alt="home"
                        class="w-30 h-20"></div>
            </a>

            <form action="search.php" method="GET" class="flex-1 max-w-2xl hidden md:block">
                <div
                    class="relative flex items-center w-full h-11 rounded-full bg-gray-100 border border-transparent focus-within:border-blue-500 focus-within:bg-white overflow-hidden transition-all">
                    <i class="fas fa-search text-gray-400 ml-4"></i>
                    <input type="text" name="keyword" placeholder="Tìm kiếm sản phẩm, danh mục..."
                        class="w-full bg-transparent border-none outline-none px-3 text-sm text-gray-700 placeholder-gray-500 h-full">
                    <button
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 h-full font-medium transition text-sm"><i
                            class="fas fa-search"></i></button>
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
                        <a href="chitietdonhang.php?id=<?php echo $latest_order_id; ?>"
                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition flex items-center group/item"><i
                                class="fas fa-shipping-fast w-5 text-gray-400 group-hover/item:text-green-500 transition mr-2"></i>Trạng
                            thái đơn hàng</a>
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
    </header>

    <main class="container mx-auto px-4 max-w-7xl py-6 flex-grow min-h-screen w-full">

        <div class="flex items-center justify-between mb-6 border-b-2 border-blue-600 pb-2 mt-4">
            <h2
                class="text-2xl font-bold text-gray-800 uppercase bg-blue-600 text-white px-6 py-2 rounded-t-lg inline-block flex items-center gap-2">
                <i class="fas fa-heart text-red-300"></i> Sản Phẩm Yêu Thích
            </h2>
        </div>

        <div id="stats"
            class="mb-8 bg-white rounded-xl border border-gray-100 p-6 shadow-sm flex flex-wrap justify-around items-center text-center gap-6 divide-x divide-gray-100">
            <div class="flex-1 px-4">
                <p class="text-3xl font-bold text-gray-900 mb-1" id="stat-count">0</p>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sản phẩm hiện tại</p>
            </div>
            <div class="flex-1 px-4">
                <p class="text-3xl font-bold text-blue-600 mb-1" id="stat-categories">0</p>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Danh mục</p>
            </div>
            <div class="flex-1 px-4">
                <p class="text-3xl font-bold text-red-500 mb-1" id="stat-value">0đ</p>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng giá trị</p>
            </div>
        </div>

        <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-8 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2 text-gray-500 pl-1">
                    <i class="fas fa-filter"></i>
                    <span class="text-sm font-medium">Bộ lọc:</span>
                </div>

                <select id="category-filter"
                    class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-sm font-medium text-gray-700 w-full sm:w-auto cursor-pointer transition">
                </select>

                <select id="sort-filter"
                    class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-sm font-medium text-gray-700 w-full sm:w-auto cursor-pointer transition">
                    <option value="default">Sắp xếp mặc định</option>
                    <option value="price-asc">Giá: Thấp đến cao</option>
                    <option value="price-desc">Giá: Cao đến thấp</option>
                    <option value="name">Tên: A-Z</option>
                </select>
            </div>

            <button id="clear-all"
                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white text-red-500 border border-red-200 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors text-sm font-bold w-full md:w-auto shrink-0 group shadow-sm">
                <i class="fas fa-trash-alt group-hover:scale-110 transition-transform"></i> Xóa tất cả
            </button>
        </div>

        <div id="products-grid"
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5 pb-10">
        </div>

    </main>

    <footer class="bg-gray-900 text-gray-300 pt-16 pb-8 border-t-4 border-blue-600 mt-10">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                <div>
                    <h3 class="text-2xl font-black text-white italic tracking-tighter mb-4">TTP<span
                            class="text-blue-500">SHOP</span></h3>
                    <p class="text-sm text-gray-400 leading-relaxed mb-6">Hệ thống mua sắm thời trang trực tuyến uy tín,
                        mang đến cho bạn những xu hướng mới nhất với chất lượng tuyệt vời.</p>
                    <div class="flex gap-4">
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-blue-600 text-white transition"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-pink-600 text-white transition"><i
                                class="fab fa-instagram"></i></a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-red-600 text-white transition"><i
                                class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Hỗ Trợ Khách Hàng</h4>
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
                    <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Thanh Toán</h4>
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
                <p>&copy; 2026 TTP Shop. Được thiết kế lại và tối ưu hóa trải nghiệm UI/UX.</p>
            </div>
        </div>
    </footer>

    <script>
    let products = <?php echo $productsJSON; ?>;

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function showToast(message, iconType = 'success') {
        Toast.fire({
            icon: iconType,
            title: message
        });
    }

    function formatPrice(price) {
        return price.toLocaleString('vi-VN') + 'đ';
    }

    function populateCategories() {
        const select = document.getElementById('category-filter');
        const categories = new Set(products.map(p => p.category));
        let html = '<option value="all">Tất cả danh mục</option>';
        categories.forEach(c => {
            html += `<option value="${c}">${c}</option>`;
        });
        select.innerHTML = html;
    }

    // --- TẠO THẺ SẢN PHẨM (Fix lỗi sự kiện click cho button xóa) ---
    function createProductCard(product) {
        return `
            <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden relative transform hover:-translate-y-1">
                
                <div class="absolute top-2 left-2 z-10 bg-blue-600/90 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                    ${product.category}
                </div>

                <button type="button" onclick="removeFavorite(event, ${product.id})" class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn text-gray-400">
                    <i class="fas fa-trash-alt text-sm transition-colors group-hover/btn:text-red-500"></i>
                </button>

                <div class="relative aspect-[4/5] overflow-hidden bg-gray-50 flex items-center justify-center border-b border-gray-50">
                    <a href="chitiet.php?id=${product.id}" class="w-full h-full block">
                        <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </a>

                    <div class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center items-center gap-2 pointer-events-none">
                        <button onclick="addToCartAjax(event, ${product.id})" type="button" class="bg-blue-600 text-white text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-blue-700 transition shadow-lg flex items-center gap-1.5 pointer-events-auto" title="Thêm vào giỏ">
                            <i class="fas fa-cart-plus"></i> <span class="hidden md:inline">Thêm</span>
                        </button>
                        <a href="chitiet.php?id=${product.id}" class="bg-white text-gray-900 text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center gap-1.5 pointer-events-auto" title="Xem chi tiết">
                            <i class="fas fa-eye"></i> <span class="hidden md:inline">Xem</span>
                        </a>
                    </div>
                </div>

                <div class="p-3 md:p-4 flex flex-col flex-1">
                    <a href="chitiet.php?id=${product.id}">
                        <h3 class="text-gray-800 text-sm font-medium line-clamp-2 mb-2 group-hover:text-blue-600 transition-colors h-10 leading-tight">
                            ${product.name}
                        </h3>
                    </a>

                    <div class="mt-auto flex items-center justify-between">
                        <div class="text-red-600 font-bold text-base md:text-lg">
                            ${formatPrice(product.price)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function updateStats(displayList) {
        const totalValue = displayList.reduce((sum, p) => sum + p.price, 0);
        const categories = new Set(displayList.map(p => p.category));

        document.getElementById('stat-count').textContent = displayList.length;
        document.getElementById('stat-categories').textContent = categories.size;
        document.getElementById('stat-value').textContent = formatPrice(totalValue);

        if (displayList.length === 0) {
            document.getElementById('stats').style.display = 'none';
        } else {
            document.getElementById('stats').style.display = 'flex';
        }
    }

    function renderProducts() {
        const categoryFilter = document.getElementById('category-filter').value;
        const sortFilter = document.getElementById('sort-filter').value;

        let filtered = [...products];

        if (categoryFilter !== 'all') {
            filtered = filtered.filter(p => p.category === categoryFilter);
        }

        if (sortFilter === 'price-asc') filtered.sort((a, b) => a.price - b.price);
        else if (sortFilter === 'price-desc') filtered.sort((a, b) => b.price - a.price);
        else if (sortFilter === 'name') filtered.sort((a, b) => a.name.localeCompare(b.name));

        const grid = document.getElementById('products-grid');

        if (filtered.length === 0) {
            grid.classList.remove('grid', 'grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-4', 'xl:grid-cols-5');
            grid.innerHTML = `
                <div class="w-full text-center py-20 bg-white rounded-2xl border border-dashed border-gray-200">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 rounded-full mb-4 shadow-sm">
                        <i class="far fa-heart text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                        ${products.length === 0 ? 'Danh sách yêu thích trống' : 'Không tìm thấy sản phẩm'}
                    </h3>
                    <p class="text-gray-500 mb-6">
                        ${products.length === 0 ? 'Hãy dạo quanh cửa hàng và thả tim cho sản phẩm bạn thích nhé.' : 'Thử thay đổi bộ lọc để xem các sản phẩm khác.'}
                    </p>
                    ${products.length === 0 ? '<a href="index.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-full font-bold hover:bg-blue-700 transition shadow-lg">Tiếp tục mua sắm</a>' : ''}
                </div>
            `;
        } else {
            grid.classList.add('grid', 'grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-4', 'xl:grid-cols-5');
            grid.innerHTML = filtered.map(createProductCard).join('');
        }

        updateStats(filtered);
    }

    // --- CẬP NHẬT LOGIC XÓA (Thêm event.stopPropagation() và ép kiểu Int) ---
    async function removeFavorite(event, id) {
        event.preventDefault();
        event.stopPropagation(); // Ngăn sự kiện click vô tình kích hoạt thẻ a

        const product = products.find(p => parseInt(p.id) === parseInt(id));
        const productName = product ? product.name : 'sản phẩm này';

        const result = await Swal.fire({
            title: 'Xóa khỏi yêu thích?',
            text: `Bạn có chắc muốn xóa ${productName} khỏi danh sách yêu thích không?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Có, xóa ngay!',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`?action=remove`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });
                const data = await response.json();

                if (data.success) {
                    // Ép kiểu đảm bảo đồng bộ định dạng int
                    const productId = parseInt(id);
                    products = products.filter(p => parseInt(p.id) !== productId);

                    showToast(`Đã xóa ${productName} khỏi yêu thích`);
                    populateCategories();
                    renderProducts();
                } else {
                    showToast('Có lỗi xảy ra khi xóa!', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi mạng, thử lại sau!', 'error');
            }
        }
    }

    document.getElementById('clear-all').addEventListener('click', async () => {
        if (products.length > 0) {
            Swal.fire({
                title: 'Xóa tất cả?',
                text: "Bạn có chắc chắn muốn làm trống danh sách yêu thích?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Có, xóa đi!',
                cancelButtonText: 'Hủy'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`?action=clear_all`, {
                            method: 'POST'
                        });
                        const data = await response.json();

                        if (data.success) {
                            products = [];
                            showToast('Đã làm trống danh sách yêu thích');
                            populateCategories();
                            renderProducts();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            })
        }
    });

    // Ajax Thêm giỏ hàng (Chỉ thêm và báo thành công, KHÔNG LOAD TRANG)

    function addToCartAjax(event, productId) {
        event.preventDefault(); // Chặn hành động load lại trang của Form/Trình duyệt

        fetch(`xuly_giohang.php?id=${productId}&action=ajax`, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(result => {
                // 1. Hiện thông báo
                Toast.fire({
                    icon: 'success',
                    title: 'Đã thêm sản phẩm vào giỏ hàng!'
                });

                // 2. Tự động cộng số trên icon Giỏ hàng đỏ góc phải trên cùng
                const badge = document.getElementById('cart-badge');
                if (badge) {
                    let currentCount = parseInt(badge.innerText) || 0;
                    badge.innerText = currentCount + 1;
                    badge.classList.remove('hidden');
                    badge.classList.add('flex');
                }

                // 3. CẬP NHẬT LẠI DANH SÁCH DROPDOWN MÀ KHÔNG LOAD TRANG
                fetch(window.location.href) // Tải ngầm lại trang hiện tại
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Trích xuất đúng cái khối html giỏ hàng mới vừa được PHP tạo ra
                        const newCartHTML = doc.getElementById('cart-dropdown-container').innerHTML;

                        // Ghi đè vào giao diện hiện tại của người dùng
                        document.getElementById('cart-dropdown-container').innerHTML = newCartHTML;
                    });
            })
            .catch(error => {
                console.error('Lỗi thêm giỏ hàng:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể thêm vào giỏ hàng. Vui lòng thử lại!'
                });
            });
    }

    document.getElementById('category-filter').addEventListener('change', renderProducts);
    document.getElementById('sort-filter').addEventListener('change', renderProducts);

    populateCategories();
    renderProducts();
    </script>
</body>

</html>