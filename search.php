<?php
session_start();

// === 1. XỬ LÝ GIỎ HÀNG RIÊNG BIỆT CHO TỪNG TÀI KHOẢN ===
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

// Khởi tạo $MaDanhMuc = 0 để tránh lỗi ở Header Navigation
$MaDanhMuc = 0;

// Lấy keyword, giới hạn độ dài 100 ký tự, escape cho LIKE query
$keyword_raw = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$keyword_raw = mb_substr($keyword_raw, 0, 100, 'UTF-8'); // giới hạn 100 ký tự

// Escape ký tự đặc biệt trong LIKE (%, _, \) để chặn slow query / wildcard injection
$keyword_like = $db->conn->real_escape_string(
    str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $keyword_raw)
);
// Keyword để hiển thị an toàn trên UI
$keyword = $db->conn->real_escape_string($keyword_raw);

// THÊM BIẾN NÀY ĐỂ LƯU DANH MỤC DỰ ĐOÁN
$inferred_category = 0; 

if (!empty($keyword_raw)) {
    // Dùng $keyword_like (đã escape wildcard) trong LIKE query
    $sql = "SELECT p.*, COUNT(w.MaSanPham) as TongYeuThich 
            FROM product p 
            LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham 
            WHERE p.TenSanPham LIKE '%$keyword_like%' 
            GROUP BY p.MaSanPham 
            ORDER BY p.MaSanPham DESC";
// ===== PHÂN TRANG =====
$page      = max(1, intval($_GET['page'] ?? 1));
$per_page  = 12;
$offset    = ($page - 1) * $per_page;

$result_total = $db->select("SELECT COUNT(DISTINCT p.MaSanPham) as total FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham WHERE p.TenSanPham LIKE '%$keyword_like%'");
$total_products = $result_total ? intval($result_total->fetch_assoc()['total']) : 0;

$sql .= " LIMIT $per_page OFFSET $offset";
$result = $db->select($sql);


    // THÊM ĐOẠN NÀY: Lấy mã danh mục của sản phẩm đầu tiên tìm được làm danh mục mặc định
    if ($result && $result->num_rows > 0) {
        $first_product = $result->fetch_assoc();
        if (isset($first_product['MaDanhMuc'])) {
            $inferred_category = $first_product['MaDanhMuc'];
        }
        // Trả con trỏ dữ liệu về vị trí 0 để vòng lặp hiển thị sản phẩm bên dưới không bị mất món đầu tiên
        $result->data_seek(0); 
    }

} else {
    $result = null;
}

// 3. Tính tổng số lượng giỏ hàng để hiển thị trên icon
$total_items = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}
include 'header.php';
?>

<style>

</style>
<title>
    <?php 
        if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
            echo ' TTP Shop - Tìm kiếm: ' . htmlspecialchars(trim($_GET['keyword'])) ;
        } else {
            echo isset($page_title) ? $page_title : 'TTP Shop - Tìm kiếm';
        }
    ?>
</title>

<main class="container mx-auto px-4 max-w-7xl py-8 md:py-12 flex-grow bg-white">

    <div
        class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 border-b border-gray-200 pb-4 gap-4 w-full">

        <h2
            class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3 min-w-0 flex-1 w-full">

            <span class="w-2 h-6 md:h-8 bg-blue-600 rounded-full block shrink-0"></span>

            <span class="truncate block">
                Kết quả tìm kiếm cho: "<span class="text-blue-600"><?php echo htmlspecialchars($keyword); ?></span>"
            </span>

        </h2>

        <?php if ($result && $result->num_rows > 0): ?>
        <span class="shrink-0 whitespace-nowrap text-sm font-medium bg-blue-50 text-blue-600 px-3 py-1 rounded-full">
            Tìm thấy <?php echo $result->num_rows; ?> sản phẩm
        </span>
        <?php endif; ?>

    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5 lg:gap-6">
        <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sp_id = $row['MaSanPham'];
                    $da_thich = false;
                    
                    // Kiểm tra xem user hiện tại đã thích sản phẩm này chưa
                    if (isset($_SESSION['user_id'])) {
                        $uid = $_SESSION['user_id'];
                        $check_like = $db->select("SELECT 1 FROM wishlist WHERE IdNguoiDung = $uid AND MaSanPham = $sp_id");
                        if ($check_like && $check_like->num_rows > 0) {
                            $da_thich = true;
                        }
                    }
                    $tong_tim = $row['TongYeuThich'] ?? 0;
            ?>
        <div
            class="group bg-white rounded-xl shadow-sm hover:shadow-2xl transition-all duration-300 border border-gray-200 flex flex-col overflow-hidden relative transform hover:-translate-y-1">

            <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>, this)"
                class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                <i
                    class="<?php echo $da_thich ? 'fas text-red-500' : 'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-sm transition-colors"></i>
            </button>

            <div
                class="relative aspect-[4/5] overflow-hidden bg-gray-50 flex items-center justify-center border-b border-gray-100">
                <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="w-full h-full block">
                    <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                        alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                </a>

                <div
                    class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/50 to-transparent flex justify-center items-center gap-2 pointer-events-none">
                    <form action="cart.php" method="POST" class="m-0 pointer-events-auto flex gap-2">
                        <input type="hidden" name="MaSanPham" value="<?php echo $sp_id; ?>">
                        <input type="hidden" name="soluong" value="1">
                        <button onclick="addToCartAjax(event, <?php echo $sp_id; ?>)" type="button" name="add_to_cart"
                            class="bg-blue-600 text-white text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-blue-700 transition shadow-lg flex items-center gap-1.5"
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
                            for($i=1; $i<=5; $i++){
                                if($i <= $s) echo '<i class="fas fa-star"></i>';
                                else echo '<i class="far fa-star"></i>';
                            }
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
        <div
            class="col-span-full relative flex flex-col items-center justify-center py-28 md:py-32 text-center bg-white overflow-hidden z-0 px-4">
            <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-6 relative z-10 tracking-wide">
                404 <span class="text-green-500">PAGE NOT FOUND</span>
            </h2>

            <img src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                alt="Not Found Animation" class="w-80 md:w-[450px] h-auto rounded-2xl mb-10 relative z-10 object-cover">

            <h2 class="text-3xl md:text-4xl font-semibold text-gray-800 mb-4 relative z-10">
                Trông có vẻ như bạn đang đi lạc...
            </h2>

            <p class="text-lg md:text-xl text-gray-500 mb-10 max-w-2xl mx-auto relative z-10 leading-relaxed w-full">
                Rất tiếc, chúng tôi không tìm thấy sản phẩm nào khớp với từ khóa <br class="hidden md:block">
                "<strong
                    class="text-gray-800 inline-block align-bottom truncate max-w-[150px] sm:max-w-[250px] md:max-w-[400px]"><?php echo htmlspecialchars($keyword); ?></strong>".
                <br class="hidden md:block">
                Vui lòng thử lại với một từ khóa khác nhé!
            </p>

            <a href="index.php"
                class="relative z-10 inline-block px-10 py-4 text-lg bg-[#39ac31] text-white font-bold rounded-md hover:bg-[#2e8a27] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-1">
                Quay về trang chủ
            </a>
        </div>



        <?php } ?>
    </div>

    <?php if (isset($total_products) && $total_products > $per_page):
    $total_pages = (int)ceil($total_products / $per_page);
    $qs = [];
    if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') $qs[] = 'keyword=' . urlencode(trim($_GET['keyword']));
?>
    <div class="mt-10 mb-6 flex items-center justify-center">
        <nav class="inline-flex items-center gap-2 text-sm" aria-label="Phân trang">
            <?php if ($page > 1): ?>
            <a class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50"
                href="search.php?<?php echo implode('&', array_merge($qs, ['page=' . ($page-1)])); ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
                $active = ($p === $page);
        ?>
            <a href="search.php?<?php echo implode('&', array_merge($qs, ['page=' . $p])); ?>"
                class="px-3 py-2 rounded-lg border text-gray-700 <?php echo $active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200 hover:bg-gray-50'; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a class="px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50"
                href="search.php?<?php echo implode('&', array_merge($qs, ['page=' . ($page+1)])); ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>

</main>

<?php 
// 5. GỌI FOOTER CHUNG
include 'footer.php'; 
?>