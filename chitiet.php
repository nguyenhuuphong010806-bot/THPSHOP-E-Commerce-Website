<?php
session_start();
require_once "database.php";
$db = new Database();
// 1. Kiểm tra xem có ID sản phẩm trên URL không
if (isset($_GET['id'])) {
    $sp_id = (int)$_GET['id'];
    
    // 2. Truy vấn lấy thông tin sản phẩm (đặc biệt là Tên sản phẩm)
    $sql_product = "SELECT TenSanPham FROM product WHERE MaSanPham = $sp_id LIMIT 1";
    $result_product = $db->select($sql_product);
    
    if ($result_product && $result_product->num_rows > 0) {
        $row_product = $result_product->fetch_assoc();
        
        // 3. GÁN TÊN SẢN PHẨM CHO BIẾN $page_title
        // Khuyên dùng: Ghép thêm tên shop phía sau để tốt cho SEO
        $page_title = $row_product['TenSanPham'] . ' - TTP Shop'; 
    } else {
        // Nếu không tìm thấy sản phẩm
        $page_title = 'Không tìm thấy sản phẩm - TTP Shop';
    }
} else {
    $page_title = 'Chi tiết sản phẩm - TTP Shop';
}

// Xử lý thả tim (like/unlike) bằng AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để thả tim.']);
        exit;
    }
    $productId = intval($_POST['product_id']);
    $userId = $_SESSION['user_id']; 
    
    $check_res = $db->select("SELECT * FROM wishlist WHERE IdNguoiDung = $userId AND MaSanPham = $productId");
    if ($check_res && $check_res->num_rows > 0) {
        $db->execute("DELETE FROM wishlist WHERE IdNguoiDung = $userId AND MaSanPham = $productId");
        $is_liked = false;
    } else {
        $db->execute("INSERT INTO wishlist (IdNguoiDung, MaSanPham) VALUES ($userId, $productId)");
        $is_liked = true;
    }
    
    $count_res = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE MaSanPham = $productId");
    echo json_encode(['status' => 'success', 'is_liked' => $is_liked, 'total_likes' => $count_res ? $count_res->fetch_assoc()['total'] : 0]);
    exit;
}
// =========================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) { echo "ID sản phẩm không hợp lệ."; exit; }

// Lưu lịch sử sản phẩm đã xem vào session
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}
// Loại bỏ nếu đã tồn tại để tránh trùng và đưa lên đầu
if (($key = array_search($id, $_SESSION['recently_viewed'])) !== false) {
    unset($_SESSION['recently_viewed'][$key]);
}
// Thêm vào đầu mảng
array_unshift($_SESSION['recently_viewed'], $id);
// Giữ tối đa 10 sản phẩm
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 10);

// 1. Lấy thông tin sản phẩm
$res_sp = $db->select("SELECT p.*, c.TenDanhMuc FROM product p JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc WHERE p.MaSanPham = $id");
$row = ($res_sp && $res_sp->num_rows > 0) ? $res_sp->fetch_assoc() : die("Sản phẩm không tồn tại.");

// 2. Kiểm tra trạng thái thả tim
$da_thich = false;
if (isset($_SESSION['user_id'])) {
    $uid_ct  = intval($_SESSION['user_id']);
    $check_like = $db->select("SELECT * FROM wishlist WHERE IdNguoiDung = $uid_ct AND MaSanPham = $id");
    if ($check_like && $check_like->num_rows > 0) $da_thich = true;
}
$count_like_db = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE MaSanPham = $id");
$tong_tim = $count_like_db ? $count_like_db->fetch_assoc()['total'] : 0;

// 3. Lấy thông tin đánh giá
$rating_avg = isset($row['SaoTrungBinh']) && $row['SaoTrungBinh'] > 0 ? (float) $row['SaoTrungBinh'] : 0;
$total_reviews = isset($row['TongDanhGia']) ? (int) $row['TongDanhGia'] : 0;

$res_reviews = $db->select("SELECT r.*, u.TenNguoiDung, u.AnhDaiDien FROM review r JOIN user u ON r.IdNguoiDung = u.IdNguoiDung WHERE r.MaSanPham = $id ORDER BY r.NgayBinhLuan DESC");

$reviews_data = [];
$star_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]; 
$total_comment_reviews = 0;

if ($res_reviews && $res_reviews->num_rows > 0) {
    while ($rv = $res_reviews->fetch_assoc()) {
        $star_counts[$rv['SoSao']]++; 
        $reviews_data[] = $rv; 
        if (!empty(trim($rv['NoiDung']))) $total_comment_reviews++;
    }
}

// Active nav cho header
$nav_map = [1 => 'Quần', 2 => 'Áo', 3 => 'Giày', 4 => 'Phụ kiện'];
$active_nav = $nav_map[(int)$row['MaDanhMuc']] ?? 'index.php';
// --- 4. KIỂM TRA TỒN KHO ---
// Giả sử bảng product của bạn đã có cột SoLuongTon (nếu chưa có thì mặc định là 100 để không bị lỗi)
$ton_kho = isset($row['SoLuongTon']) ? intval($row['SoLuongTon']) : 100;
$het_hang = ($ton_kho <= 0);

// --- 5. SẢN PHẨM GỢI Ý (Cùng danh mục) ---
$maDM = intval($row['MaDanhMuc']);
$sp_goiy = [];
// Lấy 4 sản phẩm cùng danh mục, loại trừ sản phẩm đang xem
$sql_goiy = "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich 
             FROM product p 
             LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham 
             WHERE p.MaDanhMuc = $maDM AND p.MaSanPham != $id 
             GROUP BY p.MaSanPham 
             ORDER BY p.MaSanPham DESC LIMIT 4";
$res_goiy = $db->select($sql_goiy);
if ($res_goiy) {
    while ($r = $res_goiy->fetch_assoc()) {
        $sp_goiy[] = $r;
    }
}
include 'header.php';
?>
<title><?php echo isset($page_title) ? $page_title : 'TTP Shop '; ?></title>
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

.size-active {
    border-color: #1f2937 !important;
    background-color: #ffffff !important;
    color: #111827 !important;
    border-width: 2px !important;
}

.color-active {
    border-color: #2563eb !important;
}

.thumb-active {
    border-color: #1f2937 !important;
    opacity: 1 !important;
}
</style>

<main class="container mx-auto px-4 max-w-7xl py-6 min-h-screen">
    <div class="text-sm text-gray-500 mb-6 mt-2 font-medium">
        <a href="index.php" class="hover:text-blue-600">Trang chủ</a> >
        <a href="categories.php?MaDanhMuc=<?php echo $row['MaDanhMuc']; ?>"
            class="hover:text-blue-600"><?php echo htmlspecialchars($row['TenDanhMuc']); ?></a> >
        <span class="text-gray-800"><?php echo htmlspecialchars($row['TenSanPham']); ?></span>
    </div>

    <div
        class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col lg:flex-row mb-12 p-4 lg:p-8 gap-6 lg:gap-10">
        <div class="md:w-5/12 flex flex-col items-center">
            <img id="main-image" src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                class="w-full aspect-[3/4] object-cover rounded-2xl mb-2 shadow-sm transition duration-300"
                alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>">
            <div class="flex items-center gap-2 sm:gap-3 w-full justify-center px-2 mt-2">
                <button type="button" id="btn-prev-img"
                    class="w-10 h-10 bg-white border rounded-lg flex items-center justify-center font-bold hover:bg-gray-50 text-gray-500">&lt;</button>
                <div id="thumbnail-container" class="flex gap-1 sm:gap-2 flex-nowrap overflow-x-auto hide-scroll pb-1">
                    <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                        class="thumb-img w-14 h-18 sm:w-16 sm:h-20 flex-shrink-0 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-100 hover:opacity-100 transition thumb-active"
                        data-index="0">
                    <?php if(!empty($row['hinh2'])): ?>
                    <img src="public/images/<?php echo htmlspecialchars($row['hinh2']); ?>"
                        class="thumb-img w-14 h-18 sm:w-16 sm:h-20 flex-shrink-0 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-60 hover:opacity-100 transition"
                        data-index="1">
                    <?php endif; ?>
                    <?php if(!empty($row['hinh3'])): ?>
                    <img src="public/images/<?php echo htmlspecialchars($row['hinh3']); ?>"
                        class="thumb-img w-16 h-20 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-60 hover:opacity-100 transition"
                        data-index="2">
                    <?php endif; ?>
                </div>
                <button type="button" id="btn-next-img"
                    class="w-10 h-10 bg-white border rounded-lg flex items-center justify-center font-bold hover:bg-gray-50 text-gray-500">&gt;</button>
            </div>
        </div>

        <div class="md:w-7/12 flex flex-col">
            <p class="text-xs sm:text-sm font-bold text-gray-800 mb-3 sm:mb-2">Loại sản phẩm:
                <a href="categories.php?MaDanhMuc=<?php echo $row['MaDanhMuc']; ?>"
                    class="text-blue-600 font-semibold hover:underline">
                    <?php echo htmlspecialchars($row['TenDanhMuc']); ?>
                </a>
            </p>
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-gray-900 mb-3 sm:mb-4 uppercase leading-tight">
                <?php echo htmlspecialchars($row['TenSanPham']); ?>
            </h1>

            <div class="flex items-center gap-2 mb-6">
                <div class="flex text-yellow-400 text-sm sm:text-lg">
                    <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating_avg) echo '<i class="fas fa-star"></i>';
                            elseif ($i - 0.5 <= $rating_avg) echo '<i class="fas fa-star-half-alt"></i>';
                            else echo '<i class="far fa-star"></i>';
                        }
                    ?>
                </div>
                <span class="text-gray-900 font-bold ml-1 text-lg"><?php echo number_format($rating_avg, 1); ?>/5</span>
                <span class="text-gray-500 text-sm ml-2">(<?php echo $total_reviews; ?> lượt đánh giá)</span>
            </div>

            <div class="flex items-end gap-6 mb-8">
                <div class="text-3xl md:text-4xl font-black text-red-600">
                    <?php echo number_format($row['GiaSanPham']); ?>Đ</div>
            </div>

            <?php 
                $has_sizes = !empty($row['size']); 
                $size_array = $has_sizes ? array_map('trim', explode(',', $row['size'])) : [];
            ?>
            <?php if ($has_sizes && count($size_array) > 0): ?>
            <div class="flex items-center gap-4 mb-6" id="size-section">
                <span class="font-bold text-gray-900 w-24">Size:</span>
                <div class="flex gap-2 flex-wrap">
                    <?php foreach ($size_array as $index => $sz): ?>
                    <button
                        class="btn-size min-w-[3rem] px-2 h-10 rounded-lg bg-gray-100 border text-gray-600 font-semibold hover:bg-gray-200 transition <?php echo $index === 0 ? 'size-active' : ''; ?>"
                        data-size="<?php echo htmlspecialchars($sz); ?>">
                        <?php echo htmlspecialchars($sz); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-4 mb-8">
                <span class="font-bold text-gray-900 w-24">Màu sắc:</span>
                <div class="flex gap-3 flex-wrap">
                    <label
                        class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition color-active"
                        data-color="<?php echo !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'MacDinh'; ?>">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                            class="w-8 h-8 object-cover rounded">
                        <span
                            class="text-sm font-bold text-gray-900"><?php echo !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'Mặc định'; ?></span>
                    </label>
                    <?php if(!empty($row['mau2']) && !empty($row['hinh2'])): ?>
                    <label
                        class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition"
                        data-color="<?php echo htmlspecialchars($row['mau2']); ?>">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh2']); ?>"
                            class="w-8 h-8 object-cover rounded">
                        <span
                            class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['mau2']); ?></span>
                    </label>
                    <?php endif; ?>
                    <?php if(!empty($row['mau3']) && !empty($row['hinh3'])): ?>
                    <label
                        class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition"
                        data-color="<?php echo htmlspecialchars($row['mau3']); ?>">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh3']); ?>"
                            class="w-8 h-8 object-cover rounded">
                        <span
                            class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['mau3']); ?></span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 sm:gap-6 mb-10">
                <div class="flex items-center gap-2 sm:gap-4">
                    <span class="font-bold text-gray-900 w-20 sm:w-24">Số Lượng:</span>
                    <div class="flex items-center border rounded-lg overflow-hidden h-11">
                        <button type="button" id="btn-minus"
                            class="px-3 sm:px-4 bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold outline-none border-r h-full transition">-</button>
                        <input type="text" id="input-qty" value="1" readonly
                            class="w-10 sm:w-14 text-center font-bold outline-none bg-white text-gray-800">
                        <button type="button" id="btn-plus"
                            class="px-3 sm:px-4 bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold outline-none border-l h-full transition">+</button>
                    </div>
                </div>
                <button type="button" id="btn-like" data-id="<?php echo $id; ?>"
                    class="flex items-center gap-2 transition <?php echo $da_thich ? 'text-red-500' : 'text-gray-500 hover:text-red-500'; ?>">
                    <i id="icon-like"
                        class="<?php echo $da_thich ? 'fas' : 'far'; ?> fa-heart text-2xl transition-transform active:scale-75"></i>
                    <span class="text-sm font-medium whitespace-nowrap">Đã thích (<span
                            id="like-count"><?php echo $tong_tim; ?></span>)</span>
                </button>
            </div>
            <div class="mt-4 text-sm <?= $het_hang ? 'text-red-500 font-bold' : 'text-green-600' ?>">
                <i class="fas fa-warehouse mr-1"></i>
                <?= $het_hang ? 'Sản phẩm đã hết hàng' : 'Còn lại ' . $ton_kho . ' sản phẩm' ?>
            </div>

            <div class="flex gap-4 mt-6">
                <?php if ($het_hang): ?>
                <button disabled
                    class="w-full bg-gray-400 text-white py-3.5 rounded-xl font-bold cursor-not-allowed flex items-center justify-center gap-2">
                    <i class="fas fa-ban"></i> Đã hết hàng
                </button>
                <?php else: ?>
                <button type="button" onclick="addToCartAjax(event, <?= $id ?>)"
                    class="flex-1 bg-blue-50 text-blue-600 border border-blue-200 py-3.5 rounded-xl font-bold hover:bg-blue-100 transition flex items-center justify-center gap-2">
                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                </button>
                <button type="button"
                    class="flex-1 bg-blue-600 text-white py-3.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center justify-center gap-2">
                    Mua ngay
                </button>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-12 border border-gray-100">
        <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Mô tả sản phẩm</h3>
        </div>
        <div class="p-8 text-gray-700 leading-relaxed text-[15px]">
            <?php 
                $mota = htmlspecialchars($row['MoTa'] ?? '');
                echo nl2br(preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $mota)); 
            ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-10 border border-gray-100">
        <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">ĐÁNH GIÁ SẢN PHẨM</h3>
        </div>
        <div class="p-8">
            <div
                class="flex flex-col md:flex-row gap-8 items-center border border-gray-200 rounded-xl p-6 bg-red-50/20">
                <div
                    class="flex flex-col items-center gap-2 w-full md:w-auto text-center border-r md:border-r-0 md:border-b-0 pr-0 md:pr-10 border-gray-200">
                    <p class="text-5xl font-black text-red-600"><?php echo number_format($rating_avg, 1); ?> <span
                            class="text-xl font-medium text-red-600">trên 5</span></p>
                    <div class="flex text-yellow-400 text-2xl">
                        <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating_avg) echo '<i class="fas fa-star"></i>';
                                elseif ($i - 0.5 <= $rating_avg) echo '<i class="fas fa-star-half-alt"></i>';
                                else echo '<i class="far fa-star text-gray-300"></i>';
                            }
                        ?>
                    </div>
                </div>

                <div class="flex-1 flex gap-2 flex-wrap justify-center md:justify-start" id="review-filters">
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium transition border-blue-500 text-blue-600 shadow-sm shadow-blue-100"
                        data-filter="all">Tất Cả (<?php echo $total_reviews; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="comment">Có Bình Luận (<?php echo $total_comment_reviews; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="5">5 Sao (<?php echo $star_counts[5]; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="4">4 Sao (<?php echo $star_counts[4]; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="3">3 Sao (<?php echo $star_counts[3]; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="2">2 Sao (<?php echo $star_counts[2]; ?>)</button>
                    <button
                        class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                        data-filter="1">1 Sao (<?php echo $star_counts[1]; ?>)</button>
                </div>
            </div>
        </div>

        <div class="review-list">
            <?php if (count($reviews_data) > 0): ?>
            <?php foreach ($reviews_data as $rv): 
                    $so_sao = (int)$rv['SoSao'];
                    $noi_dung = trim($rv['NoiDung']);
                    $ten_user = htmlspecialchars($rv['TenNguoiDung'] ?? 'Khách');
                    $avatar_db = trim(str_replace(["'", '"'], "", $rv['AnhDaiDien']));
                    $avatar = ($avatar_db !== "default.png" && !empty($avatar_db)) ? "public/images/" . $avatar_db : "https://ui-avatars.com/api/?name=" . urlencode($ten_user) . "&background=random&color=fff&size=128";
                ?>
            <div class="review-item p-8 border-t border-gray-100 flex gap-5" data-star="<?php echo $so_sao; ?>"
                data-comment="<?php echo !empty($noi_dung) ? 'yes' : 'no'; ?>">
                <img src="<?php echo htmlspecialchars($avatar); ?>"
                    class="w-12 h-12 object-cover rounded-full border flex-shrink-0 shadow-sm" alt="Avatar">
                <div class="flex-1 flex flex-col gap-2.5">
                    <p class="font-bold text-gray-900 text-[15px]"><?php echo $ten_user; ?></p>
                    <div class="flex text-yellow-400 text-sm gap-0.5">
                        <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $so_sao) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-300"></i>';
                                }
                            ?>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        <?php echo date('d/m/Y H:i', strtotime($rv['NgayBinhLuan'])); ?></div>
                    <p class="text-gray-900 text-[15px] mt-2 mb-3 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($noi_dung)); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="p-8 text-center text-gray-500 font-medium">Chưa có đánh giá nào cho sản phẩm này. Hãy là người
                đầu tiên đánh giá!</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-12 border border-gray-100">
        <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">VIẾT ĐÁNH GIÁ CỦA BẠN</h3>
        </div>
        <div class="p-8">
            <?php if(isset($_SESSION['user_id'])): ?>
            <form id="review_form" action="xuly_danhgia.php" method="POST" onsubmit="return validateReviewForm();"
                class="space-y-6">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="rating" id="rating_value" value="0">

                <div>
                    <label class="block font-semibold mb-2.5 text-gray-800">Chọn số sao của bạn *:</label>
                    <div class="flex text-gray-300 text-3xl gap-1.5 cursor-pointer" id="star_rating_form">
                        <?php for($i=1; $i<=5; $i++): ?>
                        <button type="button" class="transition hover:text-yellow-400" data-star="<?php echo $i; ?>"><i
                                class="far fa-star"></i></button>
                        <?php endfor; ?>
                    </div>
                    <p class="text-sm font-semibold text-red-500 mt-2 hidden" id="star_error_msg"><i
                            class="fas fa-exclamation-circle"></i> Vui lòng chọn số sao đánh giá!</p>
                </div>

                <div>
                    <label for="review_comment" class="block font-semibold mb-2.5 text-gray-800">Nội dung đánh giá
                        (không bắt buộc):</label>
                    <textarea name="comment" id="review_comment" rows="6"
                        placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."
                        class="w-full px-5 py-3.5 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50"></textarea>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" name="submit_review"
                        class="bg-blue-600 text-white font-bold py-3.5 px-10 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition text-lg flex items-center justify-center gap-2">
                        Gửi đánh giá <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                <p class="text-gray-700 mb-3">Bạn cần đăng nhập để có thể gửi bình luận.</p>
                <a href="login.php"
                    class="inline-block bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition">Đến
                    trang đăng nhập</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($sp_goiy)): ?>
    <div class="mt-16 mb-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-tags text-blue-600"></i> Sản phẩm tương tự
        </h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($sp_goiy as $item): ?>
            <div
                class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition duration-300 overflow-hidden flex flex-col border border-gray-100">
                <div class="relative overflow-hidden aspect-square">
                    <a href="chitiet.php?id=<?= $item['MaSanPham'] ?>">
                        <img src="<?= htmlspecialchars($item['hinh']) ?>"
                            alt="<?= htmlspecialchars($item['TenSanPham']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </a>
                </div>
                <div class="p-4 flex flex-col flex-1">
                    <a href="chitiet.php?id=<?= $item['MaSanPham'] ?>">
                        <h4
                            class="text-gray-800 text-sm font-medium line-clamp-2 mb-2 group-hover:text-blue-600 transition">
                            <?= htmlspecialchars($item['TenSanPham']) ?></h4>
                    </a>
                    <div class="mt-auto flex items-end justify-between">
                        <div class="text-red-600 font-bold text-base">₫<?= number_format($item['GiaSanPham']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>