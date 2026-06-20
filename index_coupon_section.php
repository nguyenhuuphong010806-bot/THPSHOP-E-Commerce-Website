<?php
/* =====================================================================
 * SNIPPET NÀY CHÈN VÀO index.php
 * Vị trí: SAU </section> kết thúc phần Danh Mục (~line 293)
 *         và TRƯỚC <!-- ===== FLASH SALE ===== -->
 *
 * Cách dùng: thêm <?php include 'index_coupon_section.php'; ?>
 * hoặc copy-paste khối HTML bên dưới trực tiếp vào index.php
 * ===================================================================== */

// Lấy 3 coupon nổi bật (giảm nhiều nhất, còn hạn, đang bật)
$now_idx = date('Y-m-d H:i:s');
$res_idx_cp = $db->select(
    "SELECT * FROM coupon
     WHERE TrangThai = 1
       AND NgayBatDau <= '$now_idx'
       AND NgayHetHan  > '$now_idx'
       AND (SoLanToiDa IS NULL OR DaDung < SoLanToiDa)
     ORDER BY GiaTri DESC
     LIMIT 3"
);
$index_coupons = [];
if ($res_idx_cp) while ($r = $res_idx_cp->fetch_assoc()) $index_coupons[] = $r;
$saved_idx = isset($_SESSION['saved_coupons']) ? $_SESSION['saved_coupons'] : [];
?>

<!-- ===== MÃ GIẢM GIÁ NỔI BẬT ===== -->
<?php if (!empty($index_coupons)): ?>
<section class="mb-12">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-white flex items-center gap-2">
                    <i class="fas fa-ticket-alt text-yellow-300"></i> Mã Giảm Giá Hôm Nay
                </h2>
                <p class="text-blue-200 text-sm mt-0.5">Lưu mã — hệ thống tự áp khi thanh toán!</p>
            </div>
            <a href="coupons.php"
               class="shrink-0 bg-white text-blue-700 font-bold px-5 py-2 rounded-xl hover:bg-yellow-300 hover:text-blue-900 transition text-sm flex items-center gap-2 shadow">
                <i class="fas fa-list"></i> Xem tất cả
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 border-t border-blue-500/40">
            <?php foreach ($index_coupons as $cp):
                $code = $cp['MaCoupon'];
                $is_sv = in_array($code, $saved_idx);
                $discount_label = ($cp['LoaiGiam'] === 'percent')
                    ? "Giảm {$cp['GiaTri']}%"
                    : "Giảm " . number_format($cp['GiaTri']) . "₫";
                $days_left = ceil((strtotime($cp['NgayHetHan']) - time()) / 86400);
            ?>
            <div class="border-b md:border-b-0 md:border-r border-blue-500/40 px-5 py-4 flex items-center gap-4 group">
                <!-- Icon -->
                <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas <?php echo $cp['LoaiGiam']==='percent'?'fa-percent':'fa-tag'; ?> text-yellow-300 text-xl"></i>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-black text-white tracking-widest text-base"><?php echo htmlspecialchars($code); ?></span>
                        <button onclick="copyCodeIdx('<?php echo htmlspecialchars($code); ?>')"
                                class="text-blue-200 hover:text-yellow-300 text-xs transition" title="Sao chép">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-yellow-200 text-xs font-semibold"><?php echo htmlspecialchars($discount_label); ?></p>
                    <?php if ($cp['GiaTriToiThieu'] > 0): ?>
                    <p class="text-blue-300 text-[10px] mt-0.5">Đơn từ <?php echo number_format($cp['GiaTriToiThieu']); ?>₫</p>
                    <?php endif; ?>
                    <p class="text-blue-300 text-[10px]">HSD: <?php echo date('d/m/Y', strtotime($cp['NgayHetHan'])); ?>
                        <?php if ($days_left <= 3): ?><span class="text-red-300">(còn <?php echo $days_left; ?> ngày)</span><?php endif; ?>
                    </p>
                </div>
                <!-- Save btn -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <button id="idx-btn-<?php echo htmlspecialchars($code); ?>"
                        onclick="toggleSaveIdx('<?php echo htmlspecialchars($code); ?>', this)"
                        class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-lg transition
                               <?php echo $is_sv ? 'bg-blue-300 text-blue-900' : 'bg-white/20 text-white hover:bg-white hover:text-blue-700'; ?>">
                    <i class="fas <?php echo $is_sv?'fa-bookmark':'fa-plus'; ?>"></i>
                </button>
                <?php else: ?>
                <a href="login.php" class="flex-shrink-0 text-xs bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white hover:text-blue-700 transition font-bold">
                    <i class="fas fa-lock"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Toast copy (index) -->
<div id="idx-copy-toast"
     class="fixed bottom-24 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-sm px-5 py-2.5 rounded-full shadow-xl opacity-0 pointer-events-none transition-opacity duration-300 z-[200] flex items-center gap-2">
    <i class="fas fa-check-circle text-green-400"></i>
    <span id="idx-toast-msg">Đã sao chép!</span>
</div>

<script>
function showIdxToast(msg) {
    const t = document.getElementById('idx-copy-toast');
    document.getElementById('idx-toast-msg').textContent = msg;
    t.style.opacity = '1'; t.style.pointerEvents = 'auto';
    setTimeout(() => { t.style.opacity='0'; t.style.pointerEvents='none'; }, 2000);
}
function copyCodeIdx(code) {
    navigator.clipboard.writeText(code)
        .then(() => showIdxToast('Đã sao chép mã ' + code))
        .catch(() => showIdxToast('Không thể sao chép!'));
}
async function toggleSaveIdx(code, btn) {
    btn.disabled = true;
    const fd = new FormData(); fd.append('code', code);
    try {
        const res  = await fetch('xuly_coupon_save.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.redirect) { window.location.href = data.redirect; return; }
        if (!data.ok) { showIdxToast(data.msg || 'Lỗi!'); btn.disabled=false; return; }
        if (data.saved) {
            btn.className = btn.className.replace('bg-white/20 text-white hover:bg-white hover:text-blue-700','bg-blue-300 text-blue-900');
            btn.innerHTML = '<i class="fas fa-bookmark"></i>';
            showIdxToast('Đã lưu mã ' + code + '!');
        } else {
            btn.className = btn.className.replace('bg-blue-300 text-blue-900','bg-white/20 text-white hover:bg-white hover:text-blue-700');
            btn.innerHTML = '<i class="fas fa-plus"></i>';
            showIdxToast('Đã bỏ lưu mã ' + code);
        }
    } catch(e) { showIdxToast('Lỗi kết nối!'); }
    btn.disabled = false;
}
</script>
