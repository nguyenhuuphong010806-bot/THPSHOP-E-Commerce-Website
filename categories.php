<?php
session_start();
// === 1. XỬ LÝ GIỎ HÀNG RIÊNG BIỆT CHO TỪNG TÀI KHOẢN (Tương tự index) ===
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['cart_user_id']) && $_SESSION['cart_user_id'] != $_SESSION['user_id']) {
        unset($_SESSION['cart']);
        if (isset($_COOKIE['shopping_cart'])) {
            setcookie('shopping_cart', '', time() - 3600, '/');
        }
    }
    $_SESSION['cart_user_id'] = $_SESSION['user_id'];
    
    if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
        $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
    }
} else {
    unset($_SESSION['cart']);
    if (isset($_SESSION['cart_user_id'])) {
        unset($_SESSION['cart_user_id']);
    }
}
// =========================================================

require_once "database.php";
$db = new Database();

// 1. lấy mã danh mục từ url
$MaDanhMuc = isset($_GET['MaDanhMuc']) ? intval($_GET['MaDanhMuc']) : 0;

// 2. Truy vấn lấy TÊN và HÌNH ẢNH danh mục
$sql_cate = "SELECT * FROM categories WHERE MaDanhMuc = $MaDanhMuc";
$res_cate = $db->select($sql_cate);

$TenDanhMuc = "Tất cả sản phẩm";
// Ảnh nền mặc định nếu không thuộc danh mục nào
$bg_image = 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=2070&auto=format&fit=crop'; 

if ($res_cate && $res_cate->num_rows > 0) {
    $row_cate = $res_cate->fetch_assoc();
    $TenDanhMuc = $row_cate['TenDanhMuc'];
    
    // Đồng bộ logic lấy ảnh danh mục với trang index.php
    $anh_danh_muc_thu_cong = [
        1 => 'public/images/quan.webp',      
        2 => 'public/images/ao.webp',    
        3 => 'public/images/giay.webp',    
        4 => 'public/images/phukien.webp',  
    ];
    
    // Nếu trong Database có lưu tên file ảnh thì dùng, không thì dùng ảnh thủ công
    if (!empty($row_cate['HinhAnh'])) {
        $bg_image = 'public/images/' . $row_cate['HinhAnh'];
    } elseif (isset($anh_danh_muc_thu_cong[$MaDanhMuc])) {
        $bg_image = $anh_danh_muc_thu_cong[$MaDanhMuc];
    }
}

// 3. Bộ lọc & sắp xếp từ URL
$sort_by   = isset($_GET['sort'])   ? $_GET['sort']            : 'newest';
$gia_min   = isset($_GET['gia_min']) ? intval($_GET['gia_min']) : 0;
$gia_max   = isset($_GET['gia_max']) ? intval($_GET['gia_max']) : 0;

$order_sql = "p.MaSanPham DESC"; // mặc định: mới nhất
if ($sort_by === 'price_asc')  $order_sql = "p.GiaSanPham ASC";
if ($sort_by === 'price_desc') $order_sql = "p.GiaSanPham DESC";
if ($sort_by === 'rating')     $order_sql = "p.SaoTrungBinh DESC, p.TongDanhGia DESC";
if ($sort_by === 'popular')    $order_sql = "TongYeuThich DESC";

$where_cate  = ($MaDanhMuc > 0) ? "AND p.MaDanhMuc = $MaDanhMuc" : "";
$where_price = "";
if ($gia_min > 0)               $where_price .= " AND p.GiaSanPham >= $gia_min";
if ($gia_max > 0 && $gia_max > $gia_min) $where_price .= " AND p.GiaSanPham <= $gia_max";

// ===== PHÂN TRANG =====
$page      = max(1, intval($_GET['page'] ?? 1));
$per_page  = 12;
$offset    = ($page - 1) * $per_page;

// Tổng số sản phẩm (để tính page)
$sql_count = "SELECT COUNT(DISTINCT p.MaSanPham) as total
        FROM product p
        LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
        WHERE 1=1 $where_cate $where_price";
$res_count = $db->select($sql_count);
$total_products = $res_count ? intval($res_count->fetch_assoc()['total']) : 0;

// Dữ liệu trang hiện tại
$sql = "SELECT p.*, COUNT(w.MaSanPham) as TongYeuThich
        FROM product p
        LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
        WHERE 1=1 $where_cate $where_price
        GROUP BY p.MaSanPham
        ORDER BY $order_sql
        LIMIT $per_page OFFSET $offset";
$result = $db->select($sql);


// 4. Tính tổng số lượng giỏ hàng (Chỉ tính khi ĐÃ ĐĂNG NHẬP)
$total_items = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}
include 'header.php';
?>
<div class="relative w-full bg-cover bg-center bg-no-repeat mt-4 rounded-2xl overflow-hidden shadow-lg mx-4 xl:mx-auto">
    <section class="mb-12 max-w-7xl mx-auto bg-white p-3 md:p-4 rounded-3xl shadow-sm border border-gray-100">
        <title><?php echo isset($page_title) ? $page_title : 'TTP Shop - ' . htmlspecialchars($TenDanhMuc); ?></title>
        <div class="relative w-full bg-cover bg-center bg-no-repeat rounded-2xl overflow-hidden shadow-lg"
            style="background-image: url('<?php echo $bg_image; ?>');">

            <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"></div>

            <div
                class="container mx-auto px-6 py-20 md:py-28 flex flex-col items-center justify-center relative z-10 text-center">
                <span
                    class="inline-block py-1.5 px-4 rounded-full bg-white/10 border border-white/20 text-white text-xs md:text-sm font-semibold uppercase tracking-widest mb-6 backdrop-blur-md shadow-sm">
                    TTP SHOP 2026
                </span>

                <h1
                    class="text-5xl md:text-7xl font-black text-white leading-tight mb-4 tracking-wider uppercase drop-shadow-2xl">
                    <?php echo htmlspecialchars($TenDanhMuc); ?>
                </h1>

                <p class="text-lg md:text-xl text-gray-300 max-w-xl font-light leading-relaxed italic drop-shadow-md">
                    Khẳng định phong cách thời trang của riêng bạn
                </p>
            </div>
        </div>
    </section>
    <main id="main-content"
        class="container mx-auto px-4 max-w-7xl py-12 flex-grow bg-white rounded-3xl shadow-sm border border-gray-100">

        <div class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4 flex-wrap gap-3">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                <span class="w-2 h-8 bg-blue-600 rounded-full block"></span>
                Tất Cả Sản Phẩm
                <span class="text-sm font-normal text-gray-400">(<?php echo $total_products; ?>)</span>
            </h2>
        </div>

        <!-- Bộ lọc & sắp xếp -->
        <form method="GET" id="filter-form" class="mb-6">
            <?php if ($MaDanhMuc > 0): ?>
            <input type="hidden" name="MaDanhMuc" value="<?php echo $MaDanhMuc; ?>">
            <?php endif; ?>
            <div class="bg-gray-50 rounded-xl p-4 flex flex-wrap gap-3 items-end">
                <!-- Sắp xếp -->
                <div class="flex flex-col gap-1 min-w-[160px]">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Sắp xếp</label>
                    <select name="sort" onchange="this.form.submit()"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-blue-500">
                        <option value="newest" <?php if ($sort_by=='newest')     echo 'selected'; ?>>Mới nhất</option>
                        <option value="price_asc" <?php if ($sort_by=='price_asc')  echo 'selected'; ?>>Giá: Thấp đến
                            cao</option>
                        <option value="price_desc" <?php if ($sort_by=='price_desc') echo 'selected'; ?>>Giá: Cao đến
                            thấp</option>
                        <option value="rating" <?php if ($sort_by=='rating')     echo 'selected'; ?>>Đánh giá cao nhất
                        </option>
                        <option value="popular" <?php if ($sort_by=='popular')    echo 'selected'; ?>>Yêu thích nhiều
                            nhất</option>
                    </select>
                </div>
                <!-- Lọc giá -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Giá từ (₫)</label>
                    <input type="number" name="gia_min" value="<?php echo $gia_min > 0 ? $gia_min : ''; ?>"
                        placeholder="0" min="0" step="10000"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">đến (₫)</label>
                    <input type="number" name="gia_max" value="<?php echo $gia_max > 0 ? $gia_max : ''; ?>"
                        placeholder="Không giới hạn" min="0" step="10000"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-36 focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2 self-end">
                    <i class="fas fa-filter"></i> Lọc
                </button>
                <?php if ($sort_by !== 'newest' || $gia_min > 0 || $gia_max > 0): ?>
                <a href="categories.php<?php echo $MaDanhMuc > 0 ? '?MaDanhMuc='.$MaDanhMuc : ''; ?>"
                    class="text-sm text-gray-500 hover:text-red-600 self-end py-2 flex items-center gap-1 transition">
                    <i class="fas fa-times text-xs"></i> Xóa lọc
                </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5 lg:gap-6">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sp_id = $row['MaSanPham'];
                    $da_thich = false;
                    if (isset($_SESSION['user_id'])) {
                        $uid = $_SESSION['user_id'];
                        $check_like = $db->select("SELECT 1 FROM wishlist WHERE IdNguoiDung = $uid AND MaSanPham = $sp_id");
                        if ($check_like && $check_like->num_rows > 0) $da_thich = true;
                    }
                    $tong_tim = $row['TongYeuThich'] ?? 0;
            ?>
            <div
                class="group bg-white rounded-xl shadow-sm hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden relative transform hover:-translate-y-1">

                <button type="button" id="like-btn-<?php echo $sp_id; ?>"
                    onclick="toggleLike(this, <?php echo $sp_id; ?>)"
                    class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn text-gray-400 pointer-events-auto"
                    style="touch-action: manipulation;">
                    <i id="heart-icon-<?php echo $sp_id; ?>"
                        class="<?php echo $da_thich ? 'fas text-red-500' : 'far group-hover/btn:text-red-500'; ?> fa-heart text-sm transition-colors"></i>
                </button>

                <div
                    class="relative aspect-[4/5] overflow-hidden bg-gray-50 flex items-center justify-center border-b border-gray-50">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="w-full h-full block">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh'] ?? 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </a>

                    <div
                        class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/50 to-transparent flex justify-center items-center gap-2 pointer-events-none">
                        <form action="cart.php" method="POST" class="m-0 pointer-events-auto flex gap-2">
                            <input type="hidden" name="MaSanPham" value="<?php echo $sp_id; ?>">
                            <input type="hidden" name="soluong" value="1">
                            <button onclick="addToCartAjax(event, <?php echo $sp_id; ?>)" type="button"
                                name="add_to_cart"
                                class="bg-blue-600 text-white text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-blue-700 transition shadow-lg flex items-center gap-1.5 pointer-events-auto"
                                title="Thêm vào giỏ">
                                <i class="fas fa-cart-plus"></i> <span class="hidden md:inline">Thêm</span>
                            </button>
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>"
                                class="bg-white text-gray-900 text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center gap-1.5"
                                title="Xem chi tiết">
                                <i class="fas fa-eye"></i> <span class="hidden md:inline">Xem</span>
                            </a>
                        </form>
                    </div>
                </div>

                <div class="p-3 md:p-4 flex flex-col flex-1">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                        <h3
                            class="text-gray-800 text-sm md:text-[15px] font-medium line-clamp-2 mb-1 group-hover:text-blue-600 transition-colors h-10 md:h-11 leading-tight">
                            <?php echo htmlspecialchars($row['TenSanPham']); ?>
                        </h3>
                    </a>

                    <div class="flex items-center gap-1 mb-2 mt-1">
                        <div class="flex text-yellow-400 text-[10px]">
                            <?php 
                            $s = round($row['SaoTrungBinh'] ?? 0);
                            for($i=1; $i<=5; $i++) echo ($i<=$s)?'<i class="fas fa-star"></i>':'<i class="far fa-star"></i>';
                        ?>
                        </div>
                        <span class="text-[10px] text-gray-400">(<?php echo $row['TongDanhGia'] ?? 0; ?>)</span>
                    </div>

                    <div class="mt-auto flex items-end justify-between">
                        <div class="text-red-600 font-bold text-base md:text-lg tracking-tight">
                            <?php echo number_format($row['GiaSanPham']); ?>₫
                        </div>

                        <div class="text-xs text-gray-500 flex items-center gap-1">
                            <i class="fas fa-heart text-red-400 text-[10px]"></i>
                            <span id="like-count-<?php echo $sp_id; ?>"><?php echo $tong_tim; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="col-span-full py-20 text-center text-gray-500">
                <i class="fas fa-box-open text-6xl text-gray-200 mb-4"></i>
                <p class="text-lg">Danh mục này hiện chưa có sản phẩm nào.</p>
                <a href="index.php"
                    class="mt-4 inline-block px-6 py-2 bg-blue-600 text-white font-semibold rounded-full hover:bg-blue-700 transition shadow-md">Về
                    trang chủ</a>
            </div>
            <?php } ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_products > $per_page):
            $total_pages = (int)ceil($total_products / $per_page);
            $query_base = '';
            if ($MaDanhMuc > 0) $query_base .= 'MaDanhMuc=' . $MaDanhMuc;
            if ($MaDanhMuc > 0) $query_base .= '&';
            $query_params = [];
            if (!empty($sort_by) && $sort_by !== 'newest') $query_params[] = 'sort=' . urlencode($sort_by);
            if ($gia_min > 0) $query_params[] = 'gia_min=' . intval($gia_min);
            if ($gia_max > 0) $query_params[] = 'gia_max=' . intval($gia_max);
            if (!empty($query_params)) {
                if ($MaDanhMuc > 0) $query_base .= implode('&', $query_params);
                else $query_base = implode('&', $query_params);
            }
        ?>
        <div class="mt-8 flex items-center justify-center">
            <nav class="inline-flex items-center gap-2 text-sm" aria-label="Phân trang">
                <?php
                    $base = ($query_base !== '') ? '&' . $query_base . '&page=' : 'page=';
                ?>
                <?php if ($page > 1): ?>
                <a class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50"
                    href="categories.php<?php echo ($query_base!==''?('?'.$query_base.'&page='.($page-1)):'?page='.($page-1)); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    for ($p = $start; $p <= $end; $p++):
                        $active = ($p === $page);
                        $url = 'categories.php';
                        $qs = [];
                        if ($MaDanhMuc > 0) $qs[] = 'MaDanhMuc=' . $MaDanhMuc;
                        if ($sort_by !== 'newest') $qs[] = 'sort=' . urlencode($sort_by);
                        if ($gia_min > 0) $qs[] = 'gia_min=' . intval($gia_min);
                        if ($gia_max > 0) $qs[] = 'gia_max=' . intval($gia_max);
                        $qs[] = 'page=' . $p;
                        $url .= '?' . implode('&', $qs);
                ?>
                <a href="<?php echo $url; ?>"
                    class="px-3 py-2 rounded-lg border text-gray-700 <?php echo $active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200 hover:bg-gray-50'; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50"
                    href="categories.php<?php echo ($query_base!==''?('?'.$query_base.'&page='.($page+1)):'?page='.($page+1)); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>