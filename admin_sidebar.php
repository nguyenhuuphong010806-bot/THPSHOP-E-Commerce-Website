<?php
/**
 * admin_sidebar.php — Sidebar dùng chung cho tất cả trang admin
 * 
 * CÁCH DÙNG: Thay toàn bộ khối <aside>...</aside> trong mỗi trang admin bằng:
 *   <?php include 'admin_sidebar.php'; ?>
*/

$_admin_nav = [
['href' => 'admin_dashboard.php', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
['href' => 'admin_product.php', 'icon' => 'fa-box-open', 'label' => 'Sản phẩm'],
['href' => 'admin_flashsale.php', 'icon' => 'fa-bolt', 'label' => 'Flash Sale'],
['href' => 'admin_coupons.php', 'icon' => 'fa-ticket-alt', 'label' => 'Coupons'],
['href' => 'admin_categories.php', 'icon' => 'fa-tags', 'label' => 'Danh mục'],
['href' => 'admin_orders.php', 'icon' => 'fa-clipboard-list', 'label' => 'Đơn hàng'],
['href' => 'admin_users.php', 'icon' => 'fa-users', 'label' => 'Người dùng'],
];
$_cur_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Overlay mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden md:hidden transition-opacity"
    onclick="adminSidebarClose()"></div>

<!-- Sidebar -->
<aside id="adminSidebar" class="fixed inset-y-0 left-0 z-40 md:relative md:z-auto
           w-64 md:w-56
           bg-gray-900 flex flex-col flex-shrink-0
           -translate-x-full md:translate-x-0
           transition-transform duration-300 ease-in-out
           shadow-2xl md:shadow-none">

    <!-- Logo -->
    <div class="px-5 py-5 border-b border-gray-800/80 flex items-center gap-3">
        <a href="admin_dashboard.php" class="flex items-center gap-3 group">
            <div
                class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-500 transition">
                <img src="public/images/icon_web.png" alt="TTP" class="w-6 h-6 object-contain">
            </div>
            <div class="leading-tight">
                <span class="text-white font-black text-base tracking-tight">TTP<span
                        class="text-yellow-400">ADMIN</span></span>
                <p class="text-gray-500 text-[10px] font-medium">Quản trị hệ thống</p>
            </div>
        </a>
        <!-- Nút đóng trên mobile -->
        <button onclick="adminSidebarClose()" class="ml-auto md:hidden text-gray-500 hover:text-white transition">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-3 overflow-y-auto">
        <p class="px-5 pt-2 pb-1 text-[10px] font-bold text-gray-600 uppercase tracking-widest">Menu chính</p>
        <?php foreach ($_admin_nav as $n):
            $is_active = ($_cur_page === basename($n['href']));
            $cls = $is_active
                ? 'bg-blue-600 text-white shadow-md shadow-blue-900/40'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white';
        ?>
        <a href="<?php echo $n['href']; ?>"
            class="flex items-center gap-3 mx-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 my-0.5 <?php echo $cls; ?>">
            <i class="fas <?php echo $n['icon']; ?> w-5 text-center flex-shrink-0
                <?php echo $is_active ? 'text-white' : 'text-gray-500'; ?>"></i>
            <span><?php echo $n['label']; ?></span>
            <?php if ($is_active): ?>
            <span class="ml-auto w-1.5 h-1.5 bg-white rounded-full opacity-80"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer sidebar -->
    <div class="px-5 py-4 border-t border-gray-800/80 space-y-1">
        <a href="index.php" target="_blank"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-all">
            <i class="fas fa-globe w-5 text-center text-gray-500"></i>
            <span>Xem website</span>
            <i class="fas fa-external-link-alt text-[10px] ml-auto opacity-50"></i>
        </a>
        <a href="logout.php"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-900/30 hover:text-red-300 transition-all">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>

<script>
/* ── Admin Sidebar Helpers ─────────────────────────────── */
function adminSidebarOpen() {
    const sb = document.getElementById('adminSidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb || !ov) return;
    sb.classList.remove('-translate-x-full');
    ov.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function adminSidebarClose() {
    const sb = document.getElementById('adminSidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb || !ov) return;
    sb.classList.add('-translate-x-full');
    ov.classList.add('hidden');
    document.body.style.overflow = '';
}
/* Nếu trang cũ dùng toggleSidebar(), alias lại để không bị lỗi */
function toggleSidebar() {
    adminSidebarOpen();
}
</script>