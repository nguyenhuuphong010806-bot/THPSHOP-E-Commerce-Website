<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once "database.php";
$db = new Database();

$user_id    = intval($_SESSION['user_id']);
$page_title = 'Đơn hàng của tôi - TTP Shop';

// --- Bộ lọc theo trạng thái (0-4) ---
$filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
$where_status = ($filter >= 0 && $filter <= 4) ? "AND trangThai = $filter" : "";

$sql_orders = "SELECT d.*,
    (SELECT COUNT(*) FROM chitietdonhang c WHERE c.MaDonHang = d.MaDonHang) as so_sp
    FROM donhang d
    WHERE d.IdNguoiDung = $user_id $where_status
    ORDER BY d.NgayDat DESC";
$res_orders = $db->select($sql_orders);
$orders = [];
if ($res_orders) while ($row = $res_orders->fetch_assoc()) $orders[] = $row;

// --- Đếm theo tab ---
$counts = [-1=>0, 0=>0, 1=>0, 2=>0, 3=>0, 4=>0];
$res_count = $db->select("SELECT trangThai, COUNT(*) as cnt FROM donhang WHERE IdNguoiDung = $user_id GROUP BY trangThai");
if ($res_count) while ($r = $res_count->fetch_assoc()) $counts[intval($r['trangThai'])] = intval($r['cnt']);
$counts[-1] = array_sum(array_filter($counts, fn($k) => $k >= 0, ARRAY_FILTER_USE_KEY));

$status_labels = ['Đã đặt','Đóng gói','Đang giao','Hoàn thành','Đã hủy'];
$status_colors = [
    0 => 'bg-blue-100 text-blue-700',
    1 => 'bg-yellow-100 text-yellow-700',
    2 => 'bg-purple-100 text-purple-700',
    3 => 'bg-green-100 text-green-700',
    4 => 'bg-red-100 text-red-600',
];
$status_icons = ['fa-receipt','fa-box','fa-truck','fa-check-circle','fa-ban'];

// Giỏ hàng badge
$total_items = 0;
if (isset($_SESSION['cart']))
    foreach ($_SESSION['cart'] as $item)
        $total_items += intval($item['soluong'] ?? 1);

include 'header.php';
?>

<main id="main-content" class="container mx-auto px-4 max-w-5xl py-8 min-h-screen">

    <!-- Breadcrumb -->
    <div class="text-sm text-gray-500 mb-6 flex items-center gap-1">
        <a href="index.php" class="hover:text-blue-600">Trang chủ</a>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 mx-1"></i>
        <span class="text-gray-800 font-medium">Đơn hàng của tôi</span>
    </div>

    <!-- Toast thông báo hủy thành công -->
    <?php if (isset($_GET['cancelled'])): ?>
    <div id="cancel-toast" class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-3 flex items-center gap-3 shadow-sm">
        <i class="fas fa-check-circle text-green-500 text-xl"></i>
        <div><p class="font-bold text-sm">Hủy đơn hàng thành công!</p><p class="text-xs text-green-600">Tồn kho đã được hoàn trả tự động.</p></div>
        <button onclick="document.getElementById('cancel-toast').remove()" class="ml-auto text-green-400 hover:text-green-600"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_cancel'): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 flex items-center gap-3 shadow-sm">
        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
        <p class="text-sm">Không thể hủy đơn hàng này. Đơn đã được xử lý hoặc không tồn tại.</p>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fas fa-shopping-bag text-blue-600"></i> Đơn hàng của tôi
        </h1>
        <a href="index.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Tiếp tục mua sắm
        </a>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="flex overflow-x-auto hide-scroll">
            <?php
            $tabs = [
                -1 => ['label'=>'Tất cả',     'icon'=>'fa-list'],
                0  => ['label'=>'Đã đặt',      'icon'=>'fa-receipt'],
                1  => ['label'=>'Đóng gói',    'icon'=>'fa-box'],
                2  => ['label'=>'Đang giao',   'icon'=>'fa-truck'],
                3  => ['label'=>'Hoàn thành',  'icon'=>'fa-check-circle'],
                4  => ['label'=>'Đã hủy',      'icon'=>'fa-ban'],
            ];
            foreach($tabs as $val=>$tab):
                $is_active = ($filter == $val);
                $href = $val==-1?'orders.php':'orders.php?status='.$val;
                $active_class = $is_active
                    ? ($val==4?'border-b-2 border-red-500 text-red-600 font-bold':'border-b-2 border-blue-600 text-blue-600 font-bold')
                    : 'text-gray-500 hover:text-gray-800';
            ?>
            <a href="<?php echo $href; ?>" class="flex items-center gap-2 px-5 py-4 text-sm whitespace-nowrap transition <?php echo $active_class; ?>">
                <i class="fas <?php echo $tab['icon']; ?> text-xs"></i>
                <?php echo $tab['label']; ?>
                <?php if($counts[$val]>0): ?>
                <span class="<?php echo $is_active?($val==4?'bg-red-500':'bg-blue-600').' text-white':'bg-gray-200 text-gray-600'; ?> text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <?php echo $counts[$val]; ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Danh sách đơn hàng -->
    <?php if(empty($orders)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
        <i class="fas fa-box-open text-6xl text-gray-200 mb-4 block"></i>
        <p class="text-gray-500 text-lg mb-2"><?php echo $filter==4?'Chưa có đơn hàng nào bị hủy':'Chưa có đơn hàng nào'; ?></p>
        <?php if($filter!=4): ?><a href="index.php" class="inline-block bg-blue-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-blue-700 transition mt-4">Mua sắm ngay</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="space-y-4">
    <?php foreach($orders as $order):
        $st  = intval($order['trangThai']);
        $clr = $status_colors[$st] ?? 'bg-gray-100 text-gray-700';
        $ico = $status_icons[$st] ?? 'fa-question';
        $lbl = $status_labels[$st] ?? 'Không rõ';
        $oid = str_pad($order['MaDonHang'],6,'0',STR_PAD_LEFT);
        $date = date('d/m/Y H:i',strtotime($order['NgayDat']));
        $is_cancelled = ($st === 4);
        $can_cancel   = ($st === 0); // chỉ hủy khi "Đã đặt"
    ?>
    <div class="bg-white rounded-2xl shadow-sm border <?php echo $is_cancelled?'border-red-100':'border-gray-100'; ?> overflow-hidden hover:shadow-md transition">
        <!-- Header đơn -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-4 border-b <?php echo $is_cancelled?'border-red-100 bg-red-50/40':'border-gray-100 bg-gray-50'; ?> gap-2">
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-gray-700">Đơn #<?php echo $oid; ?></span>
                <span class="text-xs text-gray-400"><?php echo $date; ?></span>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full <?php echo $clr; ?>">
                <i class="fas <?php echo $ico; ?> text-[10px]"></i> <?php echo $lbl; ?>
            </span>
        </div>

        <!-- Sản phẩm preview -->
        <div class="px-6 py-4 <?php echo $is_cancelled?'opacity-60':''; ?>">
            <?php
            $sql_items = "SELECT c.SoLuong,c.Gia,p.TenSanPham,p.hinh,p.MaSanPham
                FROM chitietdonhang c JOIN product p ON c.MaSanPham=p.MaSanPham
                WHERE c.MaDonHang={$order['MaDonHang']} LIMIT 3";
            $res_items = $db->select($sql_items);
            $items_preview = [];
            if($res_items) while($r=$res_items->fetch_assoc()) $items_preview[]=$r;
            ?>
            <div class="flex flex-col gap-3">
            <?php foreach($items_preview as $it): ?>
            <div class="flex items-center gap-4">
                <img src="public/images/<?php echo htmlspecialchars($it['hinh']); ?>" alt="<?php echo htmlspecialchars($it['TenSanPham']); ?>" loading="lazy" class="w-14 h-14 object-cover rounded-lg border border-gray-100 flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($it['TenSanPham']); ?></p>
                    <p class="text-xs text-gray-400 mt-0.5">x<?php echo $it['SoLuong']; ?> &nbsp;|&nbsp; <?php echo number_format($it['Gia']); ?>₫</p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if($order['so_sp']>3): ?><p class="text-xs text-gray-400 pl-[72px]">...và <?php echo $order['so_sp']-3; ?> sản phẩm khác</p><?php endif; ?>
            </div>
        </div>

        <!-- Footer đơn -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-4 border-t <?php echo $is_cancelled?'border-red-100':'border-gray-100'; ?> gap-3">
            <div>
                <span class="text-sm text-gray-500"><?php echo $order['so_sp']; ?> sản phẩm &nbsp;·&nbsp; Tổng:</span>
                <span class="text-lg font-black ml-1 <?php echo $is_cancelled?'text-gray-400 line-through':'text-red-600'; ?>"><?php echo number_format($order['TongTien']); ?>₫</span>
                <?php if($is_cancelled): ?><span class="text-xs text-red-400 ml-2">(Đã hủy)</span><?php endif; ?>
            </div>

            <div class="flex gap-2 flex-wrap">
                <?php if($can_cancel): ?>
                <!-- Nút hủy đơn -->
                <button onclick="confirmCancel(<?php echo $order['MaDonHang']; ?>)"
                    class="text-xs border border-red-200 text-red-500 hover:bg-red-50 px-4 py-2 rounded-xl font-medium transition flex items-center gap-1">
                    <i class="fas fa-times text-[10px]"></i> Hủy đơn
                </button>
                <?php elseif($st===1||$st===2): ?>
                <span class="text-xs text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full font-medium flex items-center gap-1">
                    <i class="fas fa-sync-alt fa-spin text-[10px]"></i> Đang xử lý
                </span>
                <?php elseif($st===3): ?>
                <a href="chitiet.php?id=<?php echo intval($items_preview[0]['MaSanPham']??0); ?>"
                    class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-xl font-medium transition">
                    Mua lại
                </a>
                <?php endif; ?>

                <?php if(!$is_cancelled): ?>
                <a href="chitietdonhang.php?id=<?php echo $order['MaDonHang']; ?>"
                    class="text-xs bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-xl font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-eye text-[10px]"></i> Xem chi tiết
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<script>
function confirmCancel(orderId) {
    Swal.fire({
        title: 'Hủy đơn hàng?',
        html: 'Bạn có chắc muốn hủy đơn này không?<br><span class="text-sm text-gray-500">Tồn kho sẽ được hoàn trả tự động.</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-times mr-1"></i> Đồng ý hủy',
        cancelButtonText: 'Quay lại'
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = 'xuly_huy_don.php?id=' + orderId;
        }
    });
}
</script>

<style>
.hide-scroll::-webkit-scrollbar { display: none; }
.hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php include 'footer.php'; ?>
