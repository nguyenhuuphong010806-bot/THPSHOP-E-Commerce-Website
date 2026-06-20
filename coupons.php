<?php
/**
 * coupons.php — Trang xem & lưu mã giảm giá
 * Mã đã lưu được giữ trong $_SESSION['saved_coupons'] (array of MaCoupon strings)
 */
session_start();

// Giỏ hàng badge (giống các trang khác)
$total_items = 0;
if (!empty($_SESSION['cart']))
    foreach ($_SESSION['cart'] as $item)
        $total_items += intval($item['soluong'] ?? 1);

require_once "database.php";
$db = new Database();

// Lấy tất cả coupon đang hoạt động & còn hạn
$now = date('Y-m-d H:i:s');
$res = $db->select(
    "SELECT * FROM coupon
     WHERE TrangThai = 1
       AND NgayBatDau <= '$now'
       AND NgayHetHan  > '$now'
       AND (SoLanToiDa IS NULL OR DaDung < SoLanToiDa)
     ORDER BY GiaTriToiThieu ASC"
);
$coupons = [];
if ($res) while ($r = $res->fetch_assoc()) $coupons[] = $r;

$saved = isset($_SESSION['saved_coupons']) ? $_SESSION['saved_coupons'] : [];

// ── Tính giỏ hàng hiện tại để highlight coupon áp dụng được ──
$cart_subtotal = 0;
if (!empty($_SESSION['cart']))
    foreach ($_SESSION['cart'] as $item)
        $cart_subtotal += intval($item['gia']) * intval($item['soluong']);

include 'header.php';
?>

<title>Mã Giảm Giá - TTP Shop</title>

<main class="container mx-auto px-4 max-w-5xl py-8 min-h-screen bg-white border border-gray-200 rounded-2xl shadow-sm">

    <!-- Breadcrumb -->
    <div class="text-sm text-gray-500 mb-6 flex items-center gap-1">
        <a href="index.php" class="hover:text-blue-600">Trang chủ</a>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 mx-1"></i>
        <span class="text-gray-800 font-medium">Mã giảm giá</span>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <span class="w-2 h-7 bg-blue-600 rounded-full block"></span>
                <i class="fas fa-ticket-alt text-blue-600"></i> Kho Mã Giảm Giá
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-10">Lưu mã yêu thích — hệ thống sẽ tự áp khi thanh toán!</p>
        </div>
        <?php if (!empty($saved)): ?>
        <span class="bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-full">
            <i class="fas fa-bookmark mr-1"></i><?php echo count($saved); ?> mã đã lưu
        </span>
        <?php endif; ?>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Chưa đăng nhập -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl px-6 py-5 mb-8 flex items-center gap-4">
        <i class="fas fa-info-circle text-yellow-500 text-2xl flex-shrink-0"></i>
        <div>
            <p class="font-bold text-yellow-800">Đăng nhập để lưu mã giảm giá!</p>
            <p class="text-sm text-yellow-700 mt-0.5">Mã đã lưu sẽ được tự động áp dụng khi bạn thanh toán.</p>
        </div>
        <a href="login.php"
            class="ml-auto bg-yellow-500 text-white font-bold px-5 py-2 rounded-xl hover:bg-yellow-600 transition text-sm whitespace-nowrap">
            Đăng nhập
        </a>
    </div>
    <?php endif; ?>

    <?php if (empty($coupons)): ?>
    <div class="text-center py-24 text-gray-400">
        <i class="fas fa-ticket-alt text-6xl mb-4 block opacity-30"></i>
        <p class="text-lg font-medium">Hiện chưa có mã giảm giá nào.</p>
        <p class="text-sm mt-1">Hãy quay lại sau nhé!</p>
    </div>
    <?php else: ?>

    <!-- Grid coupon -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="coupon-list">
        <?php foreach ($coupons as $cp):
            $code      = $cp['MaCoupon'];
            $is_saved  = in_array($code, $saved);
            $applicable = ($cart_subtotal >= intval($cp['GiaTriToiThieu']));
            $discount_label = ($cp['LoaiGiam'] === 'percent')
                ? "Giảm {$cp['GiaTri']}%"
                : "Giảm " . number_format($cp['GiaTri']) . "₫";
            $days_left = ceil((strtotime($cp['NgayHetHan']) - time()) / 86400);
            $urgent    = ($days_left <= 3);

            // Màu card theo loại
            $accent = ($cp['LoaiGiam'] === 'percent') ? ['from-blue-600','to-indigo-700','bg-blue-500'] : ['from-orange-500','to-red-600','bg-orange-400'];
        ?>
        <div id="card-<?php echo htmlspecialchars($code); ?>"
            class="coupon-card relative rounded-2xl overflow-hidden shadow-md border <?php echo $is_saved ? 'border-blue-400 ring-2 ring-blue-300' : 'border-gray-200'; ?> flex transition-all duration-300">

            <!-- Dải màu trái -->
            <div
                class="bg-gradient-to-b <?php echo $accent[0]; ?> <?php echo $accent[1]; ?> text-white flex flex-col items-center justify-center px-4 py-5 min-w-[90px] gap-1 relative">
                <i class="fas <?php echo $cp['LoaiGiam']==='percent'?'fa-percent':'fa-tag'; ?> text-2xl opacity-80"></i>
                <span class="text-lg font-black leading-tight text-center">
                    <?php echo $cp['LoaiGiam']==='percent' ? $cp['GiaTri'].'%' : number_format($cp['GiaTri']); ?>
                </span>
                <?php if ($cp['LoaiGiam'] === 'fixed'): ?>
                <span class="text-[10px] opacity-70">VNĐ</span>
                <?php endif; ?>

                <!-- Hiệu ứng khoét tròn -->
                <div
                    class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full border border-gray-200 z-10">
                </div>
            </div>

            <!-- Nội dung -->
            <div class="flex-1 p-4 bg-white relative">
                <!-- Badge đã lưu -->
                <?php if ($is_saved): ?>
                <span
                    class="absolute top-3 right-3 bg-blue-100 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                    <i class="fas fa-bookmark text-[9px]"></i> Đã lưu
                </span>
                <?php endif; ?>

                <?php if ($urgent): ?>
                <span
                    class="absolute top-3 <?php echo $is_saved ? 'right-20' : 'right-3'; ?> bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                    <i class="fas fa-fire text-[9px]"></i> Sắp hết hạn
                </span>
                <?php endif; ?>

                <div class="flex items-center gap-2 mb-1 mt-0.5 pr-16">
                    <span
                        class="font-black text-gray-800 text-base tracking-widest"><?php echo htmlspecialchars($code); ?></span>
                    <button onclick="copyCode('<?php echo htmlspecialchars($code); ?>')" title="Sao chép mã"
                        class="text-gray-400 hover:text-blue-600 transition text-xs">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <p class="text-sm font-semibold text-blue-700 mb-1"><?php echo htmlspecialchars($discount_label); ?></p>

                <?php if (!empty($cp['MoTa'])): ?>
                <p class="text-xs text-gray-500 mb-2 line-clamp-1"><?php echo htmlspecialchars($cp['MoTa']); ?></p>
                <?php endif; ?>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500 mb-3">
                    <?php if ($cp['GiaTriToiThieu'] > 0): ?>
                    <span><i class="fas fa-shopping-bag text-gray-400 mr-1"></i>Đơn tối thiểu
                        <?php echo number_format($cp['GiaTriToiThieu']); ?>₫</span>
                    <?php else: ?>
                    <span><i class="fas fa-check-circle text-green-500 mr-1"></i>Không giới hạn đơn tối thiểu</span>
                    <?php endif; ?>
                    <span class="<?php echo $urgent?'text-red-500 font-medium':''; ?>">
                        <i class="far fa-clock mr-1"></i>HSD: <?php echo date('d/m/Y', strtotime($cp['NgayHetHan'])); ?>
                        <?php if($urgent): ?>(còn <?php echo $days_left; ?> ngày)<?php endif; ?>
                    </span>
                    <?php if ($cp['SoLanToiDa']): ?>
                    <span><i class="fas fa-users mr-1 text-gray-400"></i>Còn
                        <?php echo $cp['SoLanToiDa'] - $cp['DaDung']; ?> lượt</span>
                    <?php endif; ?>
                </div>

                <!-- Trạng thái áp dụng được cho giỏ hiện tại -->
                <?php if ($cart_subtotal > 0): ?>
                <div
                    class="mb-3 text-[11px] <?php echo $applicable?'text-green-600':'text-orange-500'; ?> flex items-center gap-1">
                    <i class="fas <?php echo $applicable?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
                    <?php echo $applicable ? 'Áp dụng được cho giỏ hàng của bạn!' : 'Giỏ hàng chưa đủ điều kiện'; ?>
                </div>
                <?php endif; ?>

                <!-- Nút lưu / bỏ lưu -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="toggleSave('<?php echo htmlspecialchars($code); ?>', this)"
                    data-saved="<?php echo $is_saved ? '1' : '0'; ?>"
                    class="save-btn text-sm font-bold px-4 py-1.5 rounded-xl transition w-full <?php echo $is_saved ? 'bg-blue-600 text-white hover:bg-red-500' : 'bg-gray-100 text-gray-700 hover:bg-blue-600 hover:text-white'; ?>">
                    <i class="fas <?php echo $is_saved?'fa-bookmark':'fa-plus'; ?> mr-1"></i>
                    <span><?php echo $is_saved ? 'Bỏ lưu' : 'Lưu mã này'; ?></span>
                </button>
                <?php else: ?>
                <a href="login.php"
                    class="block text-center text-sm font-bold px-4 py-1.5 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition w-full">
                    <i class="fas fa-lock mr-1"></i> Đăng nhập để lưu
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <!-- Phần mã đã lưu -->
    <?php if (!empty($saved) && isset($_SESSION['user_id'])): ?>
    <div class="mt-12 bg-blue-50 border border-blue-200 rounded-2xl p-6">
        <h2 class="font-bold text-blue-800 text-lg mb-4 flex items-center gap-2">
            <i class="fas fa-bookmark text-blue-600"></i> Mã bạn đã lưu (<?php echo count($saved); ?>)
        </h2>
        <p class="text-sm text-blue-600 mb-4">
            <i class="fas fa-magic mr-1"></i>Khi thanh toán, hệ thống sẽ tự động áp mã giảm nhiều nhất phù hợp với đơn
            hàng.
        </p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($saved as $sc): ?>
            <span
                class="bg-white border border-blue-300 text-blue-700 font-bold px-4 py-2 rounded-full text-sm flex items-center gap-2 shadow-sm">
                <i class="fas fa-tag text-blue-400 text-xs"></i>
                <?php echo htmlspecialchars($sc); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <a href="cart.php"
            class="mt-5 inline-flex items-center gap-2 bg-blue-600 text-white font-bold px-6 py-2.5 rounded-xl hover:bg-blue-700 transition shadow-sm text-sm">
            <i class="fas fa-shopping-cart"></i> Đi đến giỏ hàng
        </a>
    </div>
    <?php endif; ?>

</main>

<!-- Toast copy -->
<div id="copy-toast"
    class="fixed bottom-24 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-sm px-5 py-2.5 rounded-full shadow-xl opacity-0 pointer-events-none transition-opacity duration-300 z-[200] flex items-center gap-2">
    <i class="fas fa-check-circle text-green-400"></i> <span id="copy-toast-msg">Đã sao chép mã!</span>
</div>

<script>
/* ── Toggle Save Coupon ─────────────────────────── */
async function toggleSave(code, btn) {
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('code', code);
        const res = await fetch('xuly_coupon_save.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();

        if (!data.ok) {
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            showToast(data.msg || 'Lỗi!', 'error');
            btn.disabled = false;
            return;
        }

        const card = document.getElementById('card-' + code);
        if (data.saved) {
            btn.dataset.saved = '1';
            btn.className =
                'save-btn text-sm font-bold px-4 py-1.5 rounded-xl transition w-full bg-blue-600 text-white hover:bg-red-500';
            btn.innerHTML = '<i class="fas fa-bookmark mr-1"></i><span>Bỏ lưu</span>';
            if (card) card.classList.add('border-blue-400', 'ring-2', 'ring-blue-300');

            // Thêm badge "đã lưu" nếu chưa có
            const existing = card && card.querySelector('.saved-badge');
            if (card && !existing) {
                const badge = document.createElement('span');
                badge.className =
                    'saved-badge absolute top-3 right-3 bg-blue-100 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1';
                badge.innerHTML = '<i class="fas fa-bookmark text-[9px]"></i> Đã lưu';
                card.querySelector('.flex-1').appendChild(badge);
            }
            showToast('Đã lưu mã ' + code + '!');
        } else {
            btn.dataset.saved = '0';
            btn.className =
                'save-btn text-sm font-bold px-4 py-1.5 rounded-xl transition w-full bg-gray-100 text-gray-700 hover:bg-blue-600 hover:text-white';
            btn.innerHTML = '<i class="fas fa-plus mr-1"></i><span>Lưu mã này</span>';
            if (card) {
                card.classList.remove('border-blue-400', 'ring-2', 'ring-blue-300');
            }
            const badge = card && card.querySelector('.saved-badge');
            if (badge) badge.remove();
            showToast('Đã bỏ lưu mã ' + code);
        }
    } catch (e) {
        showToast('Lỗi kết nối!', 'error');
    }
    btn.disabled = false;
}

/* ── Copy Code ───────────────────────────────────── */
function copyCode(code) {
    navigator.clipboard.writeText(code)
        .then(() => showToast('Đã sao chép mã ' + code + '!'))
        .catch(() => showToast('Không thể sao chép!', 'error'));
}

/* ── Toast helper ────────────────────────────────── */
function showToast(msg, type = 'success') {
    const toast = document.getElementById('copy-toast');
    const msgEl = document.getElementById('copy-toast-msg');
    msgEl.textContent = msg;
    toast.style.opacity = '1';
    toast.style.pointerEvents = 'auto';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.pointerEvents = 'none';
    }, 2200);
}
</script>

<?php include 'footer.php'; ?>