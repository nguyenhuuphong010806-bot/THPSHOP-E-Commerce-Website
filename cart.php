<?php
session_start();

// === 1. KHÔI PHỤC GIỎ HÀNG TỪ COOKIE NẾU SESSION TRỐNG (Do đăng xuất) ===
if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
}

require_once "database.php";
$db = new Database();

// Tính tổng số lượng sản phẩm trong giỏ hàng để hiển thị trên icon
$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}

// === COUPON AJAX === (phải đứng TRƯỚC block action chung để tránh bị redirect mất)
if (isset($_GET['action']) && $_GET['action'] === 'apply_coupon') {
    header('Content-Type: application/json');
    $code = isset($_POST['coupon']) ? strtoupper(trim($db->conn->real_escape_string($_POST['coupon']))) : '';
    if (empty($code)) { echo json_encode(['ok'=>false,'msg'=>'Vui lòng nhập mã giảm giá.']); exit; }

    $now = date('Y-m-d H:i:s');
    $res_cp = $db->select("SELECT * FROM coupon WHERE MaCoupon = '$code' AND TrangThai = 1 AND NgayBatDau <= '$now' AND NgayHetHan > '$now' AND (SoLanToiDa IS NULL OR DaDung < SoLanToiDa)");
    if (!$res_cp || $res_cp->num_rows === 0) {
        echo json_encode(['ok'=>false,'msg'=>'Mã không hợp lệ hoặc đã hết hạn.']); exit;
    }
    $cp = $res_cp->fetch_assoc();
    $sub = 0;
    if (isset($_SESSION['cart'])) foreach ($_SESSION['cart'] as $item) $sub += $item['gia'] * $item['soluong'];
    if ($sub < intval($cp['GiaTriToiThieu'])) {
        echo json_encode(['ok'=>false,'msg'=>'Đơn hàng chưa đạt tối thiểu ' . number_format($cp['GiaTriToiThieu']) . '₫.']); exit;
    }
    $giam = ($cp['LoaiGiam'] === 'percent') ? round($sub * $cp['GiaTri'] / 100) : intval($cp['GiaTri']);
    $giam = min($giam, $sub);
    $_SESSION['coupon'] = ['code'=>$cp['MaCoupon'],'giam'=>$giam,'mo_ta'=>$cp['MoTa']];
    echo json_encode(['ok'=>true,'code'=>$cp['MaCoupon'],'giam'=>number_format($giam),'mo_ta'=>$cp['MoTa']]); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'remove_coupon') {
    unset($_SESSION['coupon']); echo 'ok'; exit;
}

// === 2. XỬ LÝ CÁC THAO TÁC CẬP NHẬT GIỎ HÀNG ===
if (isset($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    switch ($_GET['action']) {
        case 'delete':
            unset($_SESSION['cart'][$id]);
            break;
            
        case 'update':
            $change = isset($_GET['change']) ? intval($_GET['change']) : 0;
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['soluong'] += $change;
                if ($_SESSION['cart'][$id]['soluong'] < 1) {
                    unset($_SESSION['cart'][$id]);
                }
            }
            break;
            
        case 'clear':
            unset($_SESSION['cart']);
            break;
    }

    // LƯU LẠI VÀO COOKIE (Sống 30 ngày) SAU KHI CÓ SỰ THAY ĐỔI
    if (!empty($_SESSION['cart'])) {
        setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
    } else {
        setcookie('shopping_cart', '', time() - 3600, "/"); // Xóa cookie nếu giỏ hàng trống
    }

    // Nếu đang có coupon trong session thì khi user thay đổi giỏ hàng
    // (update/delete/clear) cần tính lại vì subtotal thay đổi.
    // Nếu không, UI sẽ không phản ánh đúng coupon.
    if (isset($_SESSION['coupon'])) {
        unset($_SESSION['coupon']);
    }

    // NẾU LÀ YÊU CẦU TỪ AJAX (KHÔNG LOAD TRANG) -> TRẢ VỀ JSON
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $subtotal = 0;
        $item_qty = 0;
        $item_total = 0;

        if (isset($_SESSION['cart'][$id])) {
            $item_qty = $_SESSION['cart'][$id]['soluong'];
            $item_total = $_SESSION['cart'][$id]['gia'] * $item_qty;
        }

        foreach($_SESSION['cart'] as $item) {
            $subtotal += $item['gia'] * $item['soluong'];
        }
        $shipping = ($subtotal > 0) ? 30000 : 0;
        $total = $subtotal + $shipping;

        echo json_encode([
            'empty' => empty($_SESSION['cart']),
            'item_qty' => $item_qty,
            'item_total' => number_format($item_total) . '₫',
            'subtotal' => number_format($subtotal) . '₫',
            'shipping' => number_format($shipping) . '₫',
            'total' => number_format($total) . '₫'
        ]);
        exit();
    }

    header("Location: cart.php");
    exit();
}

// === 3. XỬ LÝ ĐẶT HÀNG (CHECKOUT) VÀO DATABASE ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // 3.1 Yêu cầu đăng nhập trước khi mua
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Vui lòng đăng nhập để đặt hàng!'); window.location.href='login.php';</script>";
        exit();
    }

    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Giỏ hàng trống!'); window.location.href='index.php';</script>";
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $ten = $db->conn->real_escape_string($_POST['name']);
    $sdt = $db->conn->real_escape_string($_POST['phone']);
$diachi = $db->conn->real_escape_string($_POST['address']);
    $phuongthuc = isset($_POST['phuongthuc']) ? intval($_POST['phuongthuc']) : 1;
    
    // Tính tổng tiền
    $subtotal = 0;
    foreach($_SESSION['cart'] as $item) {
        $subtotal += $item['gia'] * $item['soluong'];
    }
    $shipping = ($subtotal > 0) ? 30000 : 0;
    $total = $subtotal + $shipping;

    // 3.2 Insert vào bảng `donhang`
$sql_donhang = "INSERT INTO donhang (IdNguoiDung, TenNguoiNhan, SoDienThoai, diachi, TongTien, TrangThai, MaPhuongThuc) 
                    VALUES ($user_id, '$ten', '$sdt', '$diachi', $total, 0, $phuongthuc)";
    
    if ($db->execute($sql_donhang)) {
        // Lấy mã đơn hàng vừa tạo
        $order_id = $db->conn->insert_id;
        
        // 3.3 Lặp giỏ hàng để Insert vào bảng `chitietdonhang`
        foreach ($_SESSION['cart'] as $id_sp => $item) {
            $so_luong = (int)$item['soluong'];
            $gia = (int)$item['gia'];
            
            // Xử lý phân loại (nếu có, không thì mặc định)
            $phan_loai = isset($item['PhanLoai']) ? $db->conn->real_escape_string($item['PhanLoai']) : 'Mặc định';
            
            $sql_chitiet = "INSERT INTO chitietdonhang (MaDonHang, MaSanPham, PhanLoai, SoLuong, Gia) 
                            VALUES ($order_id, $id_sp, '$phan_loai', $so_luong, $gia)";
            $db->execute($sql_chitiet);

            // 3.3a Cập nhật DaBan (bán chạy) theo số lượng thực tế
            // Cộng theo tổng SoLuong thực tế của từng sản phẩm trong đơn
            $db->execute("UPDATE product SET DaBan = DaBan + $so_luong WHERE MaSanPham = $id_sp");
        }
        
        // 3.4 Đặt hàng thành công -> Xóa giỏ hàng
        unset($_SESSION['cart']);
        setcookie('shopping_cart', '', time() - 3600, "/"); 
        
        // 3.5 TỰ ĐỘNG CHUYỂN HƯỚNG SANG TRANG CHI TIẾT ĐƠN HÀNG
        header("Location: chitietdonhang.php?id=" . $order_id);
        exit();
    } else {
        echo "<script>alert('Lỗi hệ thống: Không thể tạo đơn hàng!');</script>";
    }
}

// ── AUTO-APPLY: tự động áp mã tốt nhất từ kho đã lưu ────────────
if (
    !isset($_SESSION['coupon'])
    && !empty($_SESSION['saved_coupons'])
    && isset($_SESSION['cart']) && !empty($_SESSION['cart'])
) {
    $auto_sub = 0;
    foreach ($_SESSION['cart'] as $itm) $auto_sub += intval($itm['gia']) * intval($itm['soluong']);

    $now_auto  = date('Y-m-d H:i:s');
    $best_giam = 0;
    $best_cp   = null;

    foreach ($_SESSION['saved_coupons'] as $saved_code) {
        $sc = $db->conn->real_escape_string($saved_code);
        $r  = $db->select(
            "SELECT * FROM coupon
             WHERE MaCoupon = '$sc'
               AND TrangThai = 1
               AND NgayBatDau <= '$now_auto'
               AND NgayHetHan  > '$now_auto'
               AND (SoLanToiDa IS NULL OR DaDung < SoLanToiDa)
             LIMIT 1"
        );
        if (!$r || $r->num_rows === 0) continue;
        $cp_row = $r->fetch_assoc();
        if ($auto_sub < intval($cp_row['GiaTriToiThieu'])) continue;
        $giam = ($cp_row['LoaiGiam'] === 'percent')
            ? round($auto_sub * $cp_row['GiaTri'] / 100)
            : intval($cp_row['GiaTri']);
        $giam = min($giam, $auto_sub);
        if ($giam > $best_giam) { $best_giam = $giam; $best_cp = $cp_row; }
    }
    if ($best_cp !== null) {
        $_SESSION['coupon'] = [
            'code'  => $best_cp['MaCoupon'],
            'giam'  => $best_giam,
            'mo_ta' => $best_cp['MoTa'],
            'auto'  => true,
        ];
    }
}
// ── KẾT THÚC AUTO-APPLY ─────────────────────────────────────────

// Áp dụng coupon đang lưu trong session (nếu có)
$coupon_info = isset($_SESSION['coupon']) ? $_SESSION['coupon'] : null;
$coupon_giam = $coupon_info ? intval($coupon_info['giam']) : 0;
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
foreach($cart as $item) {
    $subtotal += $item['gia'] * $item['soluong'];
}
$shipping = ($subtotal > 0) ? 30000 : 0;
$total = $subtotal + $shipping - $coupon_giam;
if ($total < 0) $total = 0;

// Lấy thông tin user có sẵn để điền tự động vào Form
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res_user = $db->select("SELECT TenNguoiDung, SoDienThoai, diachi FROM user WHERE IdNguoiDung = $uid");
if ($res_user) $user_info = $res_user->fetch_assoc();
}
$payment_methods = $db->select("SELECT * FROM phuong_thuc_thanh_toan WHERE trangThai = 1 ORDER BY MaPhuongThuc");
if (!$payment_methods) {
    die("Lỗi hệ thống: Không thể tải phương thức thanh toán!");
}
include 'header.php';
?>


<title>Giỏ hàng - TTP Shop</title>

<style>
/* From index */
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




<main class="container mx-auto px-4 max-w-7xl py-6 min-h-screen">



    <?php if (empty($cart)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-16 text-center border border-gray-100">
        <i class="fas fa-shopping-cart text-8xl text-gray-200 mb-6"></i>
        <h2 class="text-2xl md:text-3xl font-black text-gray-800 mb-4">Giỏ hàng trống</h2>
        <p class="text-gray-500 text-lg mb-8">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
        <a href="index.php"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all duration-300 text-lg">
            <i class="fas fa-arrow-left"></i>
            Tiếp tục mua sắm
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-shopping-cart"></i>
                        Giỏ hàng của bạn (<?= count($cart) ?> sản phẩm)
                    </h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($cart as $id => $item): ?>
                    <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-none"
                        id="product_row_<?= $id ?>">
                        <div class="flex flex-row gap-3 sm:gap-6 items-start">

                            <a href="chitiet.php?id=<?= $id ?>" class="flex-shrink-0">
                                <img src="public/images/<?= htmlspecialchars($item['hinh']) ?>"
                                    alt="<?= htmlspecialchars($item['ten']) ?>"
                                    class="w-20 h-24 sm:w-24 sm:h-28 object-cover rounded-xl shadow-sm">
                            </a>

                            <div class="flex-1 min-w-0 flex flex-col md:flex-row md:justify-between gap-3 md:gap-4">

                                <div class="flex flex-col">
                                    <a href="chitiet.php?id=<?= $id ?>" class="block min-w-0">
                                        <h3
                                            class="font-bold text-base sm:text-lg text-gray-800 line-clamp-2 mb-1 sm:mb-2 hover:text-blue-600 transition-colors">
                                            <?= htmlspecialchars($item['ten']) ?>
                                        </h3>
                                    </a>
                                    <p class="text-red-600 font-bold text-lg sm:text-xl mb-3 sm:mb-4">
                                        <?= number_format($item['gia']) ?>₫
                                    </p>

                                    <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                                        <div
                                            class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-gray-50 h-10 sm:h-12">
                                            <button type="button" onclick="updateCartAjax(<?= $id ?>, -1)"
                                                class="w-10 sm:w-12 h-full flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-200 transition">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span id="qty_<?= $id ?>"
                                                class="px-3 sm:px-6 h-full flex items-center font-bold text-base sm:text-lg bg-white">
                                                <?= $item['soluong'] ?>
                                            </span>
                                            <button type="button" onclick="updateCartAjax(<?= $id ?>, 1)"
                                                class="w-10 sm:w-12 h-full flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-200 transition">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        <button type="button" onclick="updateCartAjax(<?= $id ?>, 'delete')"
                                            class="flex items-center gap-1 text-red-500 hover:text-red-600 font-medium px-3 py-2 rounded-xl hover:bg-red-50 transition text-sm sm:text-base">
                                            <i class="fas fa-trash"></i>
                                            <span class="hidden sm:inline">Xóa</span>
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="text-left md:text-right flex flex-row md:flex-col items-center md:items-end justify-between md:justify-start mt-2 md:mt-0 pt-3 md:pt-0 border-t border-gray-100 md:border-none">
                                    <span class="md:hidden text-gray-500 font-medium text-sm">Thành tiền:</span>
                                    <div class="flex flex-col items-end">
                                        <div id="item_total_<?= $id ?>"
                                            class="font-black text-lg sm:text-2xl text-red-600">
                                            <?= number_format($item['gia'] * $item['soluong']) ?>₫
                                        </div>
                                        <small class="text-gray-500 hidden md:block">x<?= $item['soluong'] ?></small>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-6 bg-gray-50 border-t">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
                        <a href="index.php"
                            class="flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold">
                            <i class="fas fa-arrow-left"></i>
                            Tiếp tục mua sắm
                        </a>
                        <button onclick="clearCart()"
                            class="flex items-center gap-2 text-red-500 hover:text-red-600 font-semibold px-6 py-2 rounded-xl hover:bg-red-50 transition">
                            <i class="fas fa-trash-alt"></i>
                            Xóa tất cả (<?= count($cart) ?>)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 sticky top-24">
                <h2 class="text-xl font-bold text-gray-800 mb-6 uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-receipt text-blue-600"></i>
                    Tóm tắt đơn hàng
                </h2>
                <div class="space-y-4 mb-8 border-b pb-6">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-700 font-medium">Tạm tính:</span>
                        <span class="font-bold text-xl text-gray-900"
                            id="subtotal"><?= number_format($subtotal) ?>₫</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-700 font-medium">Phí vận chuyển:</span>
                        <span class="font-bold text-xl text-green-600"
                            id="shipping"><?= number_format($shipping) ?>₫</span>
                    </div>

                    <!-- COUPON BOX -->
                    <div class="mt-2">
                        <?php if ($coupon_info): ?>
                        <div
                            class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                            <div>
                                <span class="text-xs font-bold text-green-700 uppercase tracking-wide">
                                    <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($coupon_info['code']); ?>
                                </span>
                                <?php if (!empty($coupon_info['auto'])): ?>
                                <span
                                    class="ml-1 text-[10px] bg-blue-100 text-blue-600 font-bold px-1.5 py-0.5 rounded-full">
                                    <i class="fas fa-magic mr-0.5"></i>Tự động áp
                                </span>
                                <?php endif; ?>
                                <p class="text-xs text-green-600 mt-0.5">Giảm
                                    <?php echo number_format($coupon_info['giam']); ?>₫</p>
                                <?php if (!empty($coupon_info['mo_ta'])): ?>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    <?php echo htmlspecialchars($coupon_info['mo_ta']); ?></p>
                                <?php endif; ?>
                            </div>
                            <button onclick="removeCoupon()" class="text-red-400 hover:text-red-600 text-sm transition"
                                title="Bỏ mã">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="flex gap-2">
                            <input type="text" id="coupon-input" placeholder="Nhập mã giảm giá"
                                class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <button onclick="applyCoupon()"
                                class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-700 transition whitespace-nowrap">
                                Áp dụng
                            </button>
                        </div>
                        <p id="coupon-msg" class="text-xs mt-1.5 hidden"></p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="mt-2 text-center">
                            <a href="coupons.php"
                                class="text-xs text-blue-600 hover:underline font-medium flex items-center justify-center gap-1">
                                <i class="fas fa-ticket-alt text-[10px]"></i>
                                Xem kho mã giảm giá của bạn
                                <?php if (!empty($_SESSION['saved_coupons'])): ?>
                                <span
                                    class="bg-blue-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full ml-1">
                                    <?php echo count($_SESSION['saved_coupons']); ?> đã lưu
                                </span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($coupon_info): ?>
                    <div class="flex justify-between items-center py-2 text-green-600">
                        <span class="font-semibold">Giảm giá:</span>
                        <span class="font-bold text-xl" id="discount">-<?= number_format($coupon_giam) ?>₫</span>
                    </div>
                    <?php endif; ?>
                    <div
                        class="flex flex-col gap-2 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-l-4 border-blue-400 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-credit-card text-xl"></i> Phương thức thanh toán
                            </span>
                            <span id="selected-payment"
                                class="font-bold text-lg text-blue-700 bg-white px-3 py-1 rounded-lg shadow-sm border">Thanh
                                toán khi nhận hàng (COD)</span>
                        </div>
                    </div>
                    <hr>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-2xl font-black text-gray-900">Tổng cộng:</span>
                        <span class="text-2xl font-black text-red-600" id="total"><?= number_format($total) ?>₫</span>
                    </div>
                </div>

                <form action="cart.php" method="POST" class="space-y-4">
                    <h3 class="font-bold text-lg mb-4 uppercase tracking-wide flex items-center gap-2 text-gray-800">
                        <i class="fas fa-user"></i>
                        Thông tin nhận hàng
                    </h3>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Họ tên <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" placeholder="Nhập họ tên đầy đủ" required
                            value="<?= htmlspecialchars($user_info['TenNguoiDung'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại <span
                                class="text-red-500">*</span></label>
                        <input type="tel" name="phone" placeholder="0xxxxxxxxx" required
                            value="<?= htmlspecialchars($user_info['SoDienThoai'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ giao hàng <span
                                class="text-red-500">*</span></label>
                        <textarea name="address" rows="3"
                            placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition resize-vertical"><?= htmlspecialchars($user_info['diachi'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-credit-card text-blue-600"></i>
                            Phương thức thanh toán <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 gap-3" id="payment-methods">
                            <?php 
                                $payment_methods->data_seek(0); // Rewind result pointer
                                while($method = $payment_methods->fetch_assoc()): 
                                    $input_id = 'payment_' . $method['MaPhuongThuc'];
                                    $is_checked = ($method['MaPhuongThuc'] == 1) ? true : false;
                                    $icon_class = $method['MaPhuongThuc'] == 1 ? 'fa-truck' : ($method['MaPhuongThuc'] == 2 ? 'fa-wallet' : 'fa-credit-card');
                                ?>
                            <div
                                class="payment-option group flex p-3 bg-white border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md relative <?php if($is_checked): ?>ring-2 ring-blue-100 bg-blue-50 border-blue-500<?php endif; ?> hover:border-blue-400">
                                <input type="radio" id="<?= $input_id ?>" name="phuongthuc"
                                    value="<?= $method['MaPhuongThuc'] ?>"
                                    data-payment-name="<?= htmlspecialchars($method['TenPhuongThuc']) ?>"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer peer z-10" required
                                    <?= $is_checked ? 'checked' : '' ?> />
                                <label for="<?= $input_id ?>"
                                    class="w-full h-full p-4 cursor-pointer block relative z-0 peer-checked:bg-blue-50 peer-checked:border-blue-500">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-5 h-5 border-2 border-gray-400 rounded-full flex items-center justify-center flex-shrink-0 group-hover:border-blue-400 peer-checked:border-blue-600 transition-all">
                                            <div
                                                class="w-2.5 h-2.5 bg-blue-600 rounded-full scale-0 opacity-0 peer-checked:scale-100 peer-checked:opacity-100 transition-all duration-200 origin-center">
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm peer-checked:text-blue-700">
                                                <?= htmlspecialchars($method['TenPhuongThuc']) ?></div>
                                            <div class="text-xs text-gray-600 mt-0.5">
                                                <?= htmlspecialchars($method['mota']) ?></div>
                                        </div>
                                        <i
                                            class="fas <?= $icon_class ?> ml-auto text-lg opacity-60 flex-shrink-0 peer-checked:opacity-100 peer-checked:text-blue-600 transition-all text-gray-500"></i>
                                    </div>
                                </label>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <button type="submit" name="place_order"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-4 px-8 rounded-2xl shadow-xl hover:from-blue-700 hover:to-blue-800 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 text-lg flex items-center justify-center gap-3">
                        <i class="fas fa-credit-card"></i>
                        Đặt hàng ngay (<?= number_format($total) ?>₫)
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
function applyCoupon() {
    var code = document.getElementById('coupon-input').value.trim();
    var msg = document.getElementById('coupon-msg');
    if (!code) {
        showCouponMsg('Vui lòng nhập mã.', false);
        return;
    }
    var fd = new FormData();
    fd.append('coupon', code);
    fetch('cart.php?action=apply_coupon', {
            method: 'POST',
            body: fd
        })
        .then(function(r) {
            return r.json();
        })
        .then(function(d) {
            if (d.ok) {
                location.reload();
            } else {
                showCouponMsg(d.msg, false);
            }
        });
}

function removeCoupon() {
    fetch('cart.php?action=remove_coupon').then(function() {
        location.reload();
    });
}

function showCouponMsg(text, ok) {
    var el = document.getElementById('coupon-msg');
    if (!el) return;
    el.textContent = text;
    el.className = 'text-xs mt-1.5 ' + (ok ? 'text-green-600' : 'text-red-500');
    el.classList.remove('hidden');
}
var couponInput = document.getElementById('coupon-input');
if (couponInput) {
    couponInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyCoupon();
    });
}
</script>
<?php include 'footer.php'; ?>