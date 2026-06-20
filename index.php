<?php
session_start();

// === GIỎ HÀNG RIÊNG BIỆT THEO TÀI KHOẢN ===
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['cart_user_id']) && $_SESSION['cart_user_id'] != $_SESSION['user_id']) {
        unset($_SESSION['cart']);
        if (isset($_COOKIE['shopping_cart'])) setcookie('shopping_cart', '', time() - 3600, '/');
    }
    $_SESSION['cart_user_id'] = $_SESSION['user_id'];
    if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart']))
        $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
} else {
    unset($_SESSION['cart'], $_SESSION['cart_user_id']);
}

require_once "database.php";
$db = new Database();

// ── Kiểm tra các cột tùy chọn ──────────────────────────────────
$has_featured_col  = (bool)$db->select("SHOW COLUMNS FROM product LIKE 'is_featured'")->num_rows;
$has_flash_col     = (bool)$db->select("SHOW COLUMNS FROM product LIKE 'GiaKhuyenMai'")->num_rows;
$has_stock_col     = (bool)$db->select("SHOW COLUMNS FROM product LIKE 'SoLuongTon'")->num_rows;

// ── Wishlist của user đang đăng nhập (1 lần duy nhất) ───────────
$user_liked_products = [];
if (isset($_SESSION['user_id'])) {
    $uid    = intval($_SESSION['user_id']);
    $res_wl = $db->select("SELECT MaSanPham FROM wishlist WHERE IdNguoiDung = $uid");
    if ($res_wl) while ($r = $res_wl->fetch_assoc()) $user_liked_products[] = $r['MaSanPham'];
}

// ── Danh mục ────────────────────────────────────────────────────
$all_categories = [];
$res_cat = $db->select("SELECT * FROM categories");
if ($res_cat) while ($r = $res_cat->fetch_assoc()) $all_categories[] = $r;

// ── Flash Sale ──────────────────────────────────────────────────
$flash_products = [];
if ($has_flash_col) {
    $now_str  = date('Y-m-d H:i:s');
    $res_flash = $db->select(
        "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
         FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
         WHERE p.GiaKhuyenMai IS NOT NULL AND p.GiaKhuyenMai > 0
           AND (p.NgayKetThucSale IS NULL OR p.NgayKetThucSale > '$now_str')
         GROUP BY p.MaSanPham ORDER BY p.GiaSanPham DESC LIMIT 8"
    );
    if ($res_flash) while ($r = $res_flash->fetch_assoc()) $flash_products[] = $r;
}

// ── Sản phẩm NỔI BẬT ────────────────────────────────────────────
$featured_products = [];
if ($has_featured_col) {
    $res_feat = $db->select(
        "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
         FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
         WHERE p.is_featured = 1
         GROUP BY p.MaSanPham ORDER BY p.SaoTrungBinh DESC LIMIT 8"
    );
    if ($res_feat) while ($r = $res_feat->fetch_assoc()) $featured_products[] = $r;
}

// ── Sản phẩm BÁN CHẠY (ORDER BY DaBan) ───────────────────────────
$bestseller_products = [];
$res_bs = $db->select(
    "SELECT p.*, COALESCE(p.DaBan, 0) as da_ban,
            COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
     FROM product p
     LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
     GROUP BY p.MaSanPham
     ORDER BY da_ban DESC, p.MaSanPham DESC
     LIMIT 8"
);
if ($res_bs) {
    while ($r = $res_bs->fetch_assoc()) {
        $bestseller_products[] = $r;
    }
}


// ── Sản phẩm GỢI Ý (is_suggested) ───────────────────────────────
$recommended_products = [];
// ưu tiên còn hàng nếu có cột tồn kho
if ($has_stock_col) {
    $res_rec = $db->select(
        "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
         FROM product p
         LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
         WHERE p.is_suggested = 1 AND p.SoLuongTon > 0
         GROUP BY p.MaSanPham
         ORDER BY p.MaSanPham DESC
         LIMIT 8"
    );
} else {
    $res_rec = $db->select(
        "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
         FROM product p
         LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
         WHERE p.is_suggested = 1
         GROUP BY p.MaSanPham
         ORDER BY p.MaSanPham DESC
         LIMIT 8"
    );
}
if ($res_rec) {
    while ($r = $res_rec->fetch_assoc()) {
        $recommended_products[] = $r;
    }
}


// ── Đã xem gần đây ──────────────────────────────────────────────
$recently_viewed_products = [];
if (!empty($_SESSION['recently_viewed'])) {
    $rv_ids = implode(',', array_map('intval', $_SESSION['recently_viewed']));
    $res_rv = $db->select(
        "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
         FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
         WHERE p.MaSanPham IN ($rv_ids) GROUP BY p.MaSanPham"
    );
    if ($res_rv) {
        $rv_map = [];
        while ($r = $res_rv->fetch_assoc()) $rv_map[$r['MaSanPham']] = $r;
        foreach ($_SESSION['recently_viewed'] as $rv_id)
            if (isset($rv_map[$rv_id])) $recently_viewed_products[] = $rv_map[$rv_id];
    }
}

// ── Phân trang "Gợi ý hôm nay" ──────────────────────────────────
$page      = max(1, intval($_GET['page'] ?? 1));
$per_page  = 12;
$res_total = $db->select("SELECT COUNT(*) as total FROM product");
$total_products = $res_total ? intval($res_total->fetch_assoc()['total']) : 0;
$total_pages    = max(1, ceil($total_products / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

$suggest_products = [];
$res_suggest = $db->select(
    "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
     FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
     GROUP BY p.MaSanPham
     ORDER BY p.MaSanPham DESC
     LIMIT $per_page OFFSET $offset"
);
if ($res_suggest) while ($r = $res_suggest->fetch_assoc()) $suggest_products[] = $r;

// ── Sản phẩm theo danh mục (dùng riêng, lấy tất cả) ────────────
$all_products = [];
$res_all = $db->select(
    "SELECT p.*, COUNT(DISTINCT w.id_yeuthich) as TongYeuThich
     FROM product p LEFT JOIN wishlist w ON p.MaSanPham = w.MaSanPham
     GROUP BY p.MaSanPham ORDER BY p.MaSanPham DESC"
);
if ($res_all) while ($r = $res_all->fetch_assoc()) $all_products[] = $r;

// ── Giỏ hàng badge ──────────────────────────────────────────────
$total_items = 0;
if (!empty($_SESSION['cart']))
    foreach ($_SESSION['cart'] as $item)
        $total_items += intval($item['soluong'] ?? 1);

include 'header.php';

// ── Helper: render stock badge HTML ─────────────────────────────
function stock_badge(int $ton, bool $has_col): string {
    if (!$has_col) return '';
    if ($ton <= 0)  return '<span class="absolute top-2 left-2 z-10 bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết hàng</span>';
    if ($ton <= 5)  return '<span class="absolute top-2 left-2 z-10 bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sắp hết</span>';
    return '';
}

// ── Helper: add-to-cart button (disable nếu hết hàng) ──────────
function cart_btn(int $sp_id, int $ton, bool $has_col): string {
    $disabled = $has_col && $ton <= 0;
    if ($disabled)
        return '<span class="bg-gray-300 text-gray-500 text-[11px] md:text-xs font-bold py-2 px-3 rounded-full flex items-center gap-1.5 cursor-not-allowed"><i class="fas fa-ban"></i> <span class="hidden md:inline">Hết hàng</span></span>';
    return '<button onclick="addToCartAjax(event,'.$sp_id.')" type="button" class="bg-blue-600 text-white text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-blue-700 transition shadow-lg flex items-center gap-1.5"><i class="fas fa-cart-plus"></i><span class="hidden md:inline">Thêm</span></button>';
}
?>
<title>TTP Shop - Đẳng Cấp Thời Trang</title>

<main id="main-content"
    class="container mx-auto px-4 max-w-7xl py-6 min-h-screen bg-white border border-gray-200 rounded-2xl shadow-sm">



    <!-- ===== BANNER SLIDER ===== -->
    <section class="mb-10 w-full h-[300px] md:h-[450px]">
        <div id="banner-slider" class="w-full h-full rounded-2xl overflow-hidden relative group shadow-sm bg-gray-900">

            <?php
        $banners = [

            [
                'img'=>'./public/images/nang_tam_phong_cach_cua_ban.jpg',
                'label'=>'New Collection 2026',
                'border'=>'border-blue-500',
                'title'=>'Nâng Tầm <br><span class="text-blue-400">Phong Cách Của Bạn</span>',
                'btn_cls'=>'bg-blue-600 hover:bg-white hover:text-blue-600',
                'href'=>'#goi-y-hom-nay',
                'btn_txt'=>'Mua Sắm Ngay'
            ],

            [
                'img'=>'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=2070&auto=format&fit=crop',
                'label'=>'Summer Sale',
                'border'=>'border-red-500',
                'title'=>'Khuyến Mãi <br><span class="text-red-400">Lên Đến 50%</span>',
                'btn_cls'=>'bg-red-500 hover:bg-white hover:text-red-600',
                'href'=>'#flash-sale',
                'btn_txt'=>'Khám Phá Ngay'
            ],

            [
                'img'=>'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=2070&auto=format&fit=crop',
                'label'=>'Trending Now',
                'border'=>'border-yellow-400',
                'title'=>'Bộ Sưu Tập <br><span class="text-yellow-400">Thu Đông Độc Quyền</span>',
                'btn_cls'=>'bg-yellow-500 text-gray-900 hover:bg-white',
                'href'=>'#san-pham-noi-bat',
                'btn_txt'=>'Xem Chi Tiết'
            ],

            [
                'img'=>'https://images.unsplash.com/photo-1445205170230-053b83016050?q=80&w=2071&auto=format&fit=crop',
                'label'=>'Thành Viên Mới',
                'border'=>'border-green-400',
                'title'=>'Ưu Đãi <br><span class="text-green-400">Dành Riêng Cho Bạn</span>',
                'btn_cls'=>'bg-green-500 hover:bg-white hover:text-green-600',
                'href'=>'register.php',
                'btn_txt'=>'Đăng Ký Ngay'
            ],

            // BANNER THÔNG BÁO
            [
                'img'=>'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?q=80&w=2070&auto=format&fit=crop',
                'label'=>'Thông Báo',
                'border'=>'border-red-400',
                'title'=>'ĐÂY LÀ SẢN PHẨM BÀI TẬP PHỤC VỤ CHẤM ĐIỂM MÔN HỌC,<br><span class="text-red-300">KHÔNG NHẰM MỤC ĐÍCH THƯƠNG MẠI</span>',
                'btn_cls'=>'bg-red-600 hover:bg-white hover:text-red-600',
                'href'=>'#goi-y-hom-nay',
                'btn_txt'=>'Đã Hiểu'
            ],

        ];

        foreach ($banners as $i => $b):
        ?>

            <div
                class="slide absolute inset-0 transition-opacity duration-700 ease-in-out <?php echo $i===0?'opacity-100 z-10':'opacity-0 z-0'; ?>">

                <img src="<?php echo $b['img']; ?>" class="w-full h-full object-cover" alt="Banner">

                <div
                    class="absolute inset-0 bg-gradient-to-r from-black/70 to-transparent flex flex-col justify-center p-8 md:p-16">

                    <span
                        class="text-white/90 text-sm md:text-lg font-bold uppercase tracking-widest mb-3 border-l-4 <?php echo $b['border']; ?> pl-3">
                        <?php echo $b['label']; ?>
                    </span>

                    <h2 class="text-3xl md:text-6xl font-black text-white mb-6 leading-tight drop-shadow-lg max-w-4xl">
                        <?php echo $b['title']; ?>
                    </h2>

                    <a href="<?php echo $b['href']; ?>"
                        class="inline-block <?php echo $b['btn_cls']; ?> text-white font-bold px-8 py-3 rounded-full w-max transition-all shadow-lg transform hover:-translate-y-1">
                        <?php echo $b['btn_txt']; ?>
                    </a>

                </div>
            </div>

            <?php endforeach; ?>

            <!-- BUTTON PREV -->
            <button id="prev-slide"
                class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center shadow-lg z-20 opacity-0 group-hover:opacity-100 transition-all">
                <i class="fas fa-chevron-left text-lg"></i>
            </button>

            <!-- BUTTON NEXT -->
            <button id="next-slide"
                class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center shadow-lg z-20 opacity-0 group-hover:opacity-100 transition-all">
                <i class="fas fa-chevron-right text-lg"></i>
            </button>

            <!-- DOTS -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex space-x-3 z-20">

                <?php for($i=0;$i<count($banners);$i++): ?>

                <button
                    class="dot <?php echo $i===0?'w-8 h-2 bg-white':'w-2.5 h-2.5 bg-white/50 hover:bg-white/90'; ?> md:h-2.5 rounded-full shadow-sm transition-all duration-300">
                </button>

                <?php endfor; ?>

            </div>

        </div>
    </section>
    <script>
    (function() {
        const sl = document.getElementById('banner-slider');
        if (!sl) return;
        const slides = sl.querySelectorAll('.slide'),
            dots = sl.querySelectorAll('.dot');
        const prev = document.getElementById('prev-slide'),
            next = document.getElementById('next-slide');
        let cur = 0;

        function show(i) {
            slides.forEach(s => {
                s.classList.remove('opacity-100', 'z-10');
                s.classList.add('opacity-0', 'z-0');
            });
            dots.forEach(d => {
                d.classList.remove('bg-white', 'w-8');
                d.classList.add('bg-white/50', 'w-2.5');
            });
            slides[i].classList.remove('opacity-0', 'z-0');
            slides[i].classList.add('opacity-100', 'z-10');
            dots[i].classList.remove('bg-white/50', 'w-2.5');
            dots[i].classList.add('bg-white', 'w-8');
            cur = i;
        }
        let timer = setInterval(() => show((cur + 1) % slides.length), 5000);

        function reset() {
            clearInterval(timer);
            timer = setInterval(() => show((cur + 1) % slides.length), 5000);
        }
        if (next) next.addEventListener('click', () => {
            show((cur + 1) % slides.length);
            reset();
        });
        if (prev) prev.addEventListener('click', () => {
            show((cur - 1 + slides.length) % slides.length);
            reset();
        });
        dots.forEach((d, i) => d.addEventListener('click', () => {
            show(i);
            reset();
        }));
    })();
    </script>

    <!-- ===== DANH MỤC ===== -->
    <section class="mb-12">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6">
            <h2 class="text-lg md:text-xl font-bold text-gray-800 uppercase mb-5 border-b pb-3">Danh Mục</h2>
            <div class="grid grid-cols-4 gap-2 md:gap-6">
                <?php
                $anh_dm = [1=>'public/images/quan.webp',2=>'public/images/ao.webp',3=>'public/images/giay.webp',4=>'public/images/phukien.webp'];
                foreach ($all_categories as $cat):
                    $cid = $cat['MaDanhMuc'];
                    $img = !empty($cat['HinhAnh']) ? 'public/images/'.$cat['HinhAnh'] : ($anh_dm[$cid] ?? 'https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=400&auto=format&fit=crop');
                ?>
                <a href="#danhmuc-<?php echo $cid; ?>" class="flex flex-col items-center group">
                    <div
                        class="w-16 h-16 md:w-24 md:h-24 rounded-full overflow-hidden bg-gray-50 border-2 border-transparent group-hover:border-blue-400 group-hover:shadow-md transition-all mb-2 md:mb-3">
                        <img src="<?php echo htmlspecialchars($img); ?>"
                            alt="<?php echo htmlspecialchars($cat['TenDanhMuc']); ?>" loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <span
                        class="text-[11px] md:text-sm text-gray-700 font-medium text-center group-hover:text-blue-600 transition uppercase"><?php echo htmlspecialchars($cat['TenDanhMuc']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== FLASH SALE ===== -->
    <?php include 'index_coupon_section.php'; ?>
    <?php if (!empty($flash_products)): ?>
    <section class="mb-12 scroll-mt-24" id="flash-sale">
        <div class="bg-gradient-to-r from-red-600 to-rose-500 rounded-2xl shadow-lg overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-4 gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚡</span>
                    <h2 class="text-xl md:text-2xl font-black text-white uppercase">Flash Sale</h2>
                    <span class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full animate-pulse">Đang
                        diễn ra</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-white/80 text-sm font-medium">Kết thúc sau:</span>
                    <div class="flex gap-1.5 items-center">
                        <?php foreach(['fs-h'=>'Giờ','fs-m'=>'Phút','fs-s'=>'Giây'] as $fid=>$flbl): ?>
                        <div class="bg-white/20 rounded-lg px-2.5 py-1.5 text-center min-w-[42px]">
                            <span id="<?php echo $fid; ?>"
                                class="text-white font-black text-lg block leading-none">00</span>
                            <span class="text-white/70 text-[9px]"><?php echo $flbl; ?></span>
                        </div>
                        <?php if($fid!=='fs-s'): ?><span class="text-white font-black text-lg">:</span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="bg-white/5 px-4 pb-5 pt-1">
                <div class="overflow-x-auto hide-scroll">
                    <div class="flex gap-4 pb-2" style="min-width:max-content">
                        <?php foreach($flash_products as $fp):
                        $fp_id=$fp['MaSanPham'];$fp_gia=intval($fp['GiaSanPham']);$fp_sale=intval($fp['GiaKhuyenMai']);
                        $fp_pct=$fp_gia>0?round((1-$fp_sale/$fp_gia)*100):0;
                        $fp_liked=in_array($fp_id,$user_liked_products);
                        $ton=intval($fp['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;
                    ?>
                        <div class="group bg-white rounded-xl overflow-hidden flex flex-col relative shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>"
                            style="width:180px;flex-shrink:0">
                            <?php if($fp_pct>0&&!$het): ?><div
                                class="absolute top-2 left-2 z-10 bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow">
                                -<?php echo $fp_pct; ?>%</div><?php endif; ?>
                            <?php if($het): ?><div
                                class="absolute top-2 left-2 z-10 bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">
                                Hết hàng</div><?php endif; ?>
                            <button type="button" onclick="toggleLike(<?php echo $fp_id; ?>,this)"
                                class="absolute top-2 right-2 z-10 w-7 h-7 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                                <i
                                    class="<?php echo $fp_liked?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-xs heart-icon-<?php echo $fp_id; ?>"></i>
                            </button>
                            <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:4/5">
                                <a href="chitiet.php?id=<?php echo $fp_id; ?>" class="block w-full h-full">
                                    <img src="public/images/<?php echo htmlspecialchars($fp['hinh']); ?>"
                                        alt="<?php echo htmlspecialchars($fp['TenSanPham']); ?>" loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                                </a>
                            </div>
                            <div class="p-3 flex flex-col flex-1">
                                <a href="chitiet.php?id=<?php echo $fp_id; ?>">
                                    <h3 class="text-gray-800 text-xs font-medium line-clamp-2 mb-2 group-hover:text-blue-600 transition"
                                        style="height:32px"><?php echo htmlspecialchars($fp['TenSanPham']); ?></h3>
                                </a>
                                <div class="mt-auto">
                                    <div class="text-red-600 font-black text-base">
                                        ₫<?php echo number_format($fp_sale); ?></div>
                                    <div class="text-gray-400 text-xs line-through">
                                        ₫<?php echo number_format($fp_gia); ?></div>
                                    <?php if($has_stock_col&&!$het): ?>
                                    <div class="mt-2">
                                        <div class="flex justify-between text-[10px] text-gray-500 mb-1"><span>Còn
                                                lại</span><span
                                                class="font-semibold <?php echo $ton<=5?'text-orange-500':''; ?>"><?php echo $ton; ?></span>
                                        </div>
                                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full <?php echo $ton<=5?'bg-red-500':($ton<=20?'bg-orange-400':'bg-green-500'); ?>"
                                                style="width:<?php echo min(100,round($ton/max($ton,100)*100)); ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php echo $het?'<button disabled class="mt-2 w-full bg-gray-300 text-gray-500 text-xs font-bold py-1.5 rounded-lg cursor-not-allowed">Hết hàng</button>':'<button onclick="addToCartAjax(event,'.$fp_id.')" class="mt-2 w-full bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1.5 rounded-lg transition flex items-center justify-center gap-1"><i class=\"fas fa-cart-plus\"></i> Thêm vào giỏ</button>'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
    (function() {
        var cards = document.querySelectorAll('[data-sale-end]'),
            endTs = 0;
        cards.forEach(function(c) {
            var t = parseInt(c.dataset.saleEnd) * 1000;
            if (t > Date.now() && (endTs === 0 || t < endTs)) endTs = t;
        });
        if (endTs === 0) endTs = Date.now() + 86400000;

        function tick() {
            var d = Math.max(0, endTs - Date.now()),
                h = Math.floor(d / 3600000),
                m = Math.floor((d % 3600000) / 60000),
                s = Math.floor((d % 60000) / 1000);
            var fh = document.getElementById('fs-h'),
                fm = document.getElementById('fs-m'),
                fs = document.getElementById('fs-s');
            if (fh) fh.textContent = String(h).padStart(2, '0');
            if (fm) fm.textContent = String(m).padStart(2, '0');
            if (fs) fs.textContent = String(s).padStart(2, '0');
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>
    <?php endif; ?>

    <!-- ===== SẢN PHẨM NỔI BẬT ===== -->
    <?php if (!empty($featured_products)): ?>
    <section class="mb-12 scroll-mt-24" id="san-pham-noi-bat">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
            <div class="flex items-center justify-between mb-5 border-b pb-4">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-yellow-500 text-2xl">⭐</span> Sản Phẩm Nổi Bật
                </h2>
                <span
                    class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full"><?php echo count($featured_products); ?>
                    sản phẩm</span>
            </div>
            <div class="overflow-x-auto hide-scroll">
                <div class="flex gap-4 pb-2" style="min-width:max-content">
                    <?php foreach($featured_products as $row):
                    $sp_id=$row['MaSanPham'];$da_thich=in_array($sp_id,$user_liked_products);
                    $ton=intval($row['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;
                    $sap_het=$has_stock_col&&!$het&&$ton<=5;
                    $sao=round(floatval($row['SaoTrungBinh']??0));
                ?>
                    <div class="group bg-white rounded-xl border border-gray-100 overflow-hidden flex flex-col relative shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>"
                        style="width:190px;flex-shrink:0">
                        <div class="absolute top-2 left-2 z-10">
                            <?php if($het): ?><span
                                class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết hàng</span>
                            <?php elseif($sap_het): ?><span
                                class="bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sắp hết
                                (<?php echo $ton; ?>)</span>
                            <?php else: ?><span
                                class="bg-yellow-400 text-gray-900 text-[10px] font-bold px-2 py-1 rounded flex items-center gap-0.5">⭐
                                Nổi bật</span>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>,this)"
                            class="absolute top-2 right-2 z-10 w-7 h-7 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                            <i
                                class="<?php echo $da_thich?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-xs heart-icon-<?php echo $sp_id; ?>"></i>
                        </button>
                        <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:4/5">
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="block w-full h-full">
                                <img src="public/images/<?php echo htmlspecialchars($row['hinh']??'default.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                            </a>
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                                <h3 class="text-gray-800 text-xs font-medium line-clamp-2 mb-1.5 group-hover:text-blue-600 transition"
                                    style="height:30px"><?php echo htmlspecialchars($row['TenSanPham']); ?></h3>
                            </a>
                            <div class="flex text-yellow-400 text-[9px] mb-2">
                                <?php for($i=1;$i<=5;$i++) echo $i<=$sao?'<i class="fas fa-star"></i>':'<i class="far fa-star"></i>'; ?>
                            </div>
                            <div class="mt-auto flex items-center justify-between">
                                <div class="text-red-600 font-bold text-sm">
                                    ₫<?php echo number_format($row['GiaSanPham']); ?></div>
                                <?php if(!$het): ?><button onclick="addToCartAjax(event,<?php echo $sp_id; ?>)"
                                    class="bg-blue-600 text-white text-[10px] px-2.5 py-1.5 rounded-lg hover:bg-blue-700 transition"><i
                                        class="fas fa-cart-plus"></i></button><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== SẢN PHẨM BÁN CHẠY ===== -->
    <?php if (!empty($bestseller_products)): ?>
    <section class="mb-12 scroll-mt-24" id="ban-chay">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
            <div class="flex items-center justify-between mb-5 border-b pb-4">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-2xl">🔥</span> Sản Phẩm Bán Chạy
                </h2>
                <a href="#goi-y-hom-nay" class="text-sm text-blue-600 hover:underline">Xem tất cả</a>
            </div>
            <div class="overflow-x-auto hide-scroll">
                <div class="flex gap-4 pb-2" style="min-width:max-content">
                    <?php foreach($bestseller_products as $i=>$row):
                    $sp_id=$row['MaSanPham'];$da_thich=in_array($sp_id,$user_liked_products);
                    $ton=intval($row['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;
                    $da_ban=intval($row['da_ban']??0);
                    $rank_colors=['bg-yellow-400 text-gray-900','bg-gray-400 text-white','bg-orange-500 text-white'];
                ?>
                    <div class="group bg-white rounded-xl border border-gray-100 overflow-hidden flex flex-col relative shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>"
                        style="width:190px;flex-shrink:0">
                        <div class="absolute top-2 left-2 z-10 flex flex-col gap-1">
                            <span
                                class="<?php echo $rank_colors[$i]??'bg-gray-600 text-white'; ?> text-[10px] font-black w-6 h-6 rounded-full flex items-center justify-center shadow">#<?php echo $i+1; ?></span>
                        </div>
                        <?php if($het): ?><div class="absolute top-2 right-8 z-10"><span
                                class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết hàng</span>
                        </div><?php endif; ?>
                        <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>,this)"
                            class="absolute top-2 right-2 z-10 w-7 h-7 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                            <i
                                class="<?php echo $da_thich?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-xs heart-icon-<?php echo $sp_id; ?>"></i>
                        </button>
                        <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:4/5">
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="block w-full h-full">
                                <img src="public/images/<?php echo htmlspecialchars($row['hinh']??'default.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                            </a>
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                                <h3 class="text-gray-800 text-xs font-medium line-clamp-2 mb-1.5 group-hover:text-blue-600 transition"
                                    style="height:30px"><?php echo htmlspecialchars($row['TenSanPham']); ?></h3>
                            </a>
                            <div class="flex items-center gap-1 mb-2 text-[10px] text-gray-500"><i
                                    class="fas fa-fire text-orange-500"></i><span>Đã bán
                                    <strong><?php echo number_format($da_ban); ?></strong></span></div>
                            <div class="mt-auto flex items-center justify-between">
                                <div class="text-red-600 font-bold text-sm">
                                    ₫<?php echo number_format($row['GiaSanPham']); ?></div>
                                <?php if(!$het): ?>
                                <button onclick="addToCartAjax(event,<?php echo $sp_id; ?>)"
                                    class="bg-orange-500 text-white text-[10px] px-2.5 py-1.5 rounded-lg hover:bg-orange-600 transition"><i
                                        class="fas fa-cart-plus"></i></button>
                                <?php else: ?>
                                <span class="text-[11px] text-gray-400 font-bold">Hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== ĐÃ XEM GẦN ĐÂY ===== -->
    <?php if (!empty($recently_viewed_products)): ?>
    <section class="mb-12">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
            <h2 class="text-lg md:text-xl font-bold text-gray-800 flex items-center gap-2 mb-5 border-b pb-4"><i
                    class="fas fa-history text-blue-500"></i> Đã xem gần đây</h2>
            <div class="overflow-x-auto hide-scroll">
                <div class="flex gap-4 pb-1" style="min-width:max-content">
                    <?php foreach($recently_viewed_products as $rv):
                    $rv_id=$rv['MaSanPham'];$rv_liked=in_array($rv_id,$user_liked_products);
                    $ton=intval($rv['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;
                    $sao=round(floatval($rv['SaoTrungBinh']??0));
                ?>
                    <div class="group bg-white rounded-xl border border-gray-100 overflow-hidden flex flex-col relative shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                        style="width:160px;flex-shrink:0">
                        <?php echo $het?'<div class="absolute top-2 left-2 z-10"><span class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết hàng</span></div>':''; ?>
                        <button type="button" onclick="toggleLike(<?php echo $rv_id; ?>,this)"
                            class="absolute top-2 right-2 z-10 w-7 h-7 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                            <i
                                class="<?php echo $rv_liked?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-xs heart-icon-<?php echo $rv_id; ?>"></i>
                        </button>
                        <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:4/5">
                            <a href="chitiet.php?id=<?php echo $rv_id; ?>" class="block w-full h-full">
                                <img src="public/images/<?php echo htmlspecialchars($rv['hinh']??'default.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($rv['TenSanPham']); ?>" loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                            </a>
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <a href="chitiet.php?id=<?php echo $rv_id; ?>">
                                <h3 class="text-gray-800 text-xs font-medium line-clamp-2 mb-1.5 group-hover:text-blue-600 transition"
                                    style="height:30px"><?php echo htmlspecialchars($rv['TenSanPham']); ?></h3>
                            </a>
                            <div class="flex text-yellow-400 text-[9px] mb-1.5">
                                <?php for($i=1;$i<=5;$i++) echo $i<=$sao?'<i class="fas fa-star"></i>':'<i class="far fa-star"></i>'; ?>
                            </div>
                            <div class="text-red-600 font-bold text-sm">₫<?php echo number_format($rv['GiaSanPham']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== SẢN PHẨM GỢI Ý (cá nhân hóa) ===== -->
    <?php if (!empty($recommended_products)): ?>
    <section class="mb-12 scroll-mt-24" id="goi-y-ca-nhan">
        <div
            class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-sm border border-blue-100 p-5 md:p-6">
            <div class="flex items-center justify-between mb-5 border-b border-blue-200 pb-4">
                <h2 class="text-lg md:text-xl font-bold text-blue-800 flex items-center gap-2">
                    <i class="fas fa-magic text-blue-500"></i> Dành Riêng Cho Bạn
                </h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach($recommended_products as $row):
                $sp_id=$row['MaSanPham'];$da_thich=in_array($sp_id,$user_liked_products);
                $ton=intval($row['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;
                $sap_het=$has_stock_col&&!$het&&$ton<=5;$sao=round(floatval($row['SaoTrungBinh']??0));
            ?>
                <div
                    class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-blue-100 flex flex-col overflow-hidden relative transform hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>">
                    <div class="absolute top-2 left-2 z-10">
                        <?php if($het): ?><span
                            class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết hàng</span>
                        <?php elseif($sap_het): ?><span
                            class="bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sắp hết</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>,this)"
                        class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                        <i
                            class="<?php echo $da_thich?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-sm heart-icon-<?php echo $sp_id; ?>"></i>
                    </button>
                    <div class="relative aspect-[4/5] overflow-hidden bg-gray-50">
                        <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="w-full h-full block">
                            <img src="public/images/<?php echo htmlspecialchars($row['hinh']??'default.jpg'); ?>"
                                alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                        </a>
                        <div
                            class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center gap-2 pointer-events-none">
                            <?php echo cart_btn($sp_id,$ton,$has_stock_col); ?>
                            <a href="chitiet.php?id=<?php echo $sp_id; ?>"
                                class="bg-white text-gray-900 text-[11px] font-bold py-2 px-3 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center gap-1.5 pointer-events-auto"><i
                                    class="fas fa-eye"></i><span class="hidden md:inline">Xem</span></a>
                        </div>
                    </div>
                    <div class="p-3 md:p-4 flex flex-col flex-1">
                        <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                            <h3
                                class="text-gray-800 text-sm font-medium line-clamp-2 mb-1 group-hover:text-blue-600 transition h-10 leading-tight">
                                <?php echo htmlspecialchars($row['TenSanPham']); ?></h3>
                        </a>
                        <div class="flex text-yellow-400 text-[10px] mb-2">
                            <?php for($i=1;$i<=5;$i++) echo $i<=$sao?'<i class="fas fa-star"></i>':'<i class="far fa-star"></i>'; ?><span
                                class="text-gray-400 ml-1">(<?php echo $row['TongDanhGia']??0; ?>)</span></div>
                        <div class="mt-auto text-red-600 font-bold text-base">
                            ₫<?php echo number_format($row['GiaSanPham']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== GỢI Ý HÔM NAY + PHÂN TRANG ===== -->
    <section id="goi-y-hom-nay" class="mb-12 scroll-mt-24 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-6 border-b-2 border-blue-600 pb-2">
            <h2 class="text-2xl font-bold text-white uppercase bg-blue-600 px-6 py-2 rounded-t-lg inline-block">
                Gợi ý hôm nay
            </h2>
            <span class="text-sm text-gray-400">Trang <?php echo $page; ?>/<?php echo $total_pages; ?> ·
                <?php echo $total_products; ?> sản phẩm</span>
        </div>

        <div
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5 mb-8 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <?php foreach($suggest_products as $row):
            $sp_id=$row['MaSanPham'];$da_thich=in_array($sp_id,$user_liked_products);
            $tong_tim=$row['TongYeuThich']??0;
            $ton=intval($row['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;$sap_het=$has_stock_col&&!$het&&$ton<=5;
        ?>
            <div
                class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden relative transform hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>">
                <div class="absolute top-2 left-2 z-10">
                    <?php if($het): ?><span class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết
                        hàng</span>
                    <?php elseif($sap_het): ?><span
                        class="bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sắp hết</span>
                    <?php else: ?><span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded">HOT</span>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>,this)"
                    class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                    <i
                        class="<?php echo $da_thich?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-sm heart-icon-<?php echo $sp_id; ?>"></i>
                </button>
                <div class="relative aspect-[4/5] overflow-hidden bg-gray-50 border-b border-gray-50">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="w-full h-full block">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh']??'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                    </a>
                    <div
                        class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center items-center gap-2 pointer-events-none">
                        <?php echo cart_btn($sp_id,$ton,$has_stock_col); ?>
                        <a href="chitiet.php?id=<?php echo $sp_id; ?>"
                            class="bg-white text-gray-900 text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center gap-1.5 pointer-events-auto"><i
                                class="fas fa-eye"></i><span class="hidden md:inline">Xem</span></a>
                    </div>
                </div>
                <div class="p-3 md:p-4 flex flex-col flex-1">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                        <h3
                            class="text-gray-800 text-sm font-medium line-clamp-2 mb-2 group-hover:text-blue-600 transition h-10 leading-tight">
                            <?php echo htmlspecialchars($row['TenSanPham']); ?></h3>
                    </a>
                    <?php if($has_stock_col&&!$het): ?>
                    <div class="text-[10px] text-gray-400 mb-2 flex items-center gap-1">
                        <i class="fas fa-warehouse text-[9px]"></i>
                        <span class="<?php echo $sap_het?'text-orange-500 font-semibold':''; ?>">Còn <?php echo $ton; ?>
                            sản phẩm</span>
                    </div>
                    <?php endif; ?>
                    <div class="mt-auto flex items-end justify-between">
                        <div class="text-red-600 font-bold text-base md:text-lg">
                            ₫<?php echo number_format($row['GiaSanPham']); ?></div>
                        <div class="text-xs text-gray-500 flex items-center gap-1"><i
                                class="fas fa-heart text-red-400 text-[10px]"></i><span><?php echo $tong_tim; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PHÂN TRANG -->
        <?php if($total_pages > 1): ?>
        <nav class="flex justify-center items-center gap-2 flex-wrap pt-4 border-t border-gray-100">
            <?php
            $prev_page = $page - 1;
            $next_page = $page + 1;
            $query_prefix = '';
            // Giữ các query param khác nếu có
            $other_params = $_GET;
            unset($other_params['page']);
            if (!empty($other_params)) $query_prefix = http_build_query($other_params) . '&';
            ?>
            <a href="?<?php echo $query_prefix; ?>page=<?php echo max(1,$prev_page); ?>#goi-y-hom-nay"
                class="w-9 h-9 rounded-xl border flex items-center justify-center text-sm font-medium transition
                      <?php echo $page<=1?'text-gray-300 border-gray-200 pointer-events-none':'text-gray-600 border-gray-300 hover:bg-gray-100'; ?>">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <?php
            // Hiện tối đa 7 nút trang
            $range = 2;
            $start = max(1, $page - $range);
            $end   = min($total_pages, $page + $range);
            if($start > 1): ?><a href="?<?php echo $query_prefix; ?>page=1#goi-y-hom-nay"
                class="w-9 h-9 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center justify-center hover:bg-gray-100 transition">1</a><?php if($start>2): ?><span
                class="text-gray-400 text-sm px-1">…</span><?php endif; endif; ?>

            <?php for($p=$start;$p<=$end;$p++): ?>
            <a href="?<?php echo $query_prefix; ?>page=<?php echo $p; ?>#goi-y-hom-nay"
                class="w-9 h-9 rounded-xl border text-sm font-medium flex items-center justify-center transition
                      <?php echo $p==$page?'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200':'text-gray-600 border-gray-300 hover:bg-gray-100'; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>

            <?php if($end<$total_pages): ?><?php if($end<$total_pages-1): ?><span
                class="text-gray-400 text-sm px-1">…</span><?php endif; ?><a
                href="?<?php echo $query_prefix; ?>page=<?php echo $total_pages; ?>#goi-y-hom-nay"
                class="w-9 h-9 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center justify-center hover:bg-gray-100 transition"><?php echo $total_pages; ?></a><?php endif; ?>

            <a href="?<?php echo $query_prefix; ?>page=<?php echo min($total_pages,$next_page); ?>#goi-y-hom-nay"
                class="w-9 h-9 rounded-xl border flex items-center justify-center text-sm font-medium transition
                      <?php echo $page>=$total_pages?'text-gray-300 border-gray-200 pointer-events-none':'text-gray-600 border-gray-300 hover:bg-gray-100'; ?>">
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
        </nav>
        <p class="text-center text-xs text-gray-400 mt-3">Đang xem trang <?php echo $page; ?> trong
            <?php echo $total_pages; ?> trang</p>
        <?php endif; ?>
    </section>

    <!-- ===== SẢN PHẨM THEO DANH MỤC ===== -->
    <?php foreach($all_categories as $cat):
        $cat_id=$cat['MaDanhMuc'];$cat_name=$cat['TenDanhMuc'];
        $products_in_cat=array_filter($all_products,function($p) use($cat_id){return isset($p['MaDanhMuc'])&&$p['MaDanhMuc']==$cat_id;});
        if(empty($products_in_cat)) continue;
    ?>
    <section id="danhmuc-<?php echo $cat_id; ?>"
        class="mb-12 scroll-mt-24 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-6 border-b-2 border-gray-800 pb-2">
            <h2 class="text-2xl font-bold text-white uppercase bg-gray-800 px-6 py-2 rounded-t-lg inline-block">
                <?php echo htmlspecialchars($cat_name); ?></h2>
            <a href="categories.php?MaDanhMuc=<?php echo $cat_id; ?>"
                class="text-gray-600 hover:text-blue-600 font-medium text-sm flex items-center gap-1 transition">Xem tất
                cả <i class="fas fa-chevron-right text-[10px]"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5">
            <?php foreach($products_in_cat as $row):
            $sp_id=$row['MaSanPham'];$da_thich=in_array($sp_id,$user_liked_products);
            $tong_tim=$row['TongYeuThich']??0;
            $ton=intval($row['SoLuongTon']??99);$het=$has_stock_col&&$ton<=0;$sap_het=$has_stock_col&&!$het&&$ton<=5;
        ?>
            <div
                class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden relative transform hover:-translate-y-1 <?php echo $het?'opacity-70':''; ?>">
                <div class="absolute top-2 left-2 z-10">
                    <?php if($het): ?><span class="bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded">Hết
                        hàng</span>
                    <?php elseif($sap_het): ?><span
                        class="bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded">Sắp hết</span>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="toggleLike(<?php echo $sp_id; ?>,this)"
                    class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center hover:bg-white shadow-sm transition group/btn">
                    <i
                        class="<?php echo $da_thich?'fas text-red-500':'far text-gray-400 group-hover/btn:text-red-500'; ?> fa-heart text-sm heart-icon-<?php echo $sp_id; ?>"></i>
                </button>
                <div class="relative aspect-[4/5] overflow-hidden bg-gray-50 border-b border-gray-50">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>" class="w-full h-full block">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh']??'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>" loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?php echo $het?'grayscale':''; ?>">
                    </a>
                    <div
                        class="absolute inset-x-0 bottom-0 p-3 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center gap-2 pointer-events-none">
                        <?php echo cart_btn($sp_id,$ton,$has_stock_col); ?>
                        <a href="chitiet.php?id=<?php echo $sp_id; ?>"
                            class="bg-white text-gray-900 text-[11px] md:text-xs font-bold py-2 px-3 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center gap-1.5 pointer-events-auto"><i
                                class="fas fa-eye"></i><span class="hidden md:inline">Xem</span></a>
                    </div>
                </div>
                <div class="p-3 md:p-4 flex flex-col flex-1">
                    <a href="chitiet.php?id=<?php echo $sp_id; ?>">
                        <h3
                            class="text-gray-800 text-sm font-medium line-clamp-2 mb-2 group-hover:text-blue-600 transition h-10 leading-tight">
                            <?php echo htmlspecialchars($row['TenSanPham']); ?></h3>
                    </a>
                    <?php if($has_stock_col): ?>
                    <div
                        class="text-[10px] mb-2 flex items-center gap-1 <?php echo $het?'text-gray-400':($sap_het?'text-orange-500 font-semibold':'text-gray-400'); ?>">
                        <i class="fas fa-warehouse text-[9px]"></i>
                        <span><?php echo $het?'Hết hàng':('Còn '.$ton.' sản phẩm'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="mt-auto flex items-end justify-between">
                        <div class="text-red-600 font-bold text-base md:text-lg">
                            ₫<?php echo number_format($row['GiaSanPham']); ?></div>
                        <div class="text-xs text-gray-500 flex items-center gap-1"><i
                                class="fas fa-heart text-red-400 text-[10px]"></i><span><?php echo $tong_tim; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

</main>

<style>
.hide-scroll::-webkit-scrollbar {
    display: none
}

.hide-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none
}
</style>

<script src="public/js/effects.js"></script>
<?php include 'footer.php'; ?>