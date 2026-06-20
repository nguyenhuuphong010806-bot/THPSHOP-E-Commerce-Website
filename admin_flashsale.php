<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once "database.php";
$db = new Database();

// Xử lý Gỡ Flash Sale
if (isset($_GET['remove_flashsale'])) {
    $id = intval($_GET['remove_flashsale']);
    $db->execute("UPDATE product SET GiaKhuyenMai = NULL, NgayBatDauSale = NULL, NgayKetThucSale = NULL WHERE MaSanPham = $id");
    header("Location: admin_flashsale.php");
    exit();
}

// Xử lý Thêm / Sửa Flash Sale
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maSP = intval($_POST['productId']);
    $giaKM = intval($_POST['giaKhuyenMai']);
    $batDau = date('Y-m-d H:i:s', strtotime($_POST['ngayBatDauSale']));
    $ketThuc = date('Y-m-d H:i:s', strtotime($_POST['ngayKetThucSale']));

    if ($maSP > 0 && $giaKM > 0) {
        $sql_update = "UPDATE product SET GiaKhuyenMai = $giaKM, NgayBatDauSale = '$batDau', NgayKetThucSale = '$ketThuc' WHERE MaSanPham = $maSP";
        $db->execute($sql_update);
    }
    header("Location: admin_flashsale.php");
    exit();
}

// Lấy danh sách đang có Flash Sale
$sql_flashsale = "SELECT * FROM product WHERE GiaKhuyenMai > 0 ORDER BY NgayKetThucSale ASC";
$res_flashsale = $db->select($sql_flashsale);

// Lấy toàn bộ sản phẩm cho Dropdown
$res_all = $db->select("SELECT MaSanPham, TenSanPham, GiaSanPham FROM product ORDER BY TenSanPham ASC");
$all_products = [];
if ($res_all) while ($p = $res_all->fetch_assoc()) $all_products[] = $p;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Flash Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="adminSidebarOpen()"
                    class="md:hidden text-gray-600 hover:text-blue-600 focus:outline-none transition">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl md:text-2xl font-bold text-blue-600 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> <span class="hidden sm:inline">TTP Admin</span>
                </h1>
            </div>

            <div class="flex items-center gap-3 md:gap-6">
                <a href="index.php"
                    class="bg-blue-50 text-blue-600 px-3 md:px-4 py-2 rounded-lg font-bold hover:bg-blue-100 transition flex items-center gap-2 text-sm">
                    <i class="fas fa-globe"></i> <span class="hidden sm:inline">Xem website</span>
                </a>

                <?php 
                    // Tạo link avatar động
                    $ten_user = $_SESSION['user_name'];
                    $avatar_link = 'https://ui-avatars.com/api/?name=' . urlencode($ten_user) . '&background=0D8ABC&color=fff&size=128';
                    
                    if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar'] != 'default.png' && $_SESSION['user_avatar'] != '') {
                        $avatar_link = 'public/images/' . $_SESSION['user_avatar'];
                    }
                ?>
                <div class="relative group inline-block z-50 text-sm">
                    <div class="flex items-center gap-2 cursor-pointer whitespace-nowrap py-2"
                        title="Tài khoản của tôi">
                        <span class="text-gray-500 group-hover:text-gray-800 transition hidden md:inline">Xin
                            chào,</span>
                        <img src="<?php echo htmlspecialchars($avatar_link); ?>" alt="Avatar"
                            class="w-8 h-8 rounded-full object-cover border border-gray-300 group-hover:border-blue-400 transition">
                        <strong class="text-gray-800 group-hover:text-blue-600 hidden md:flex items-center gap-1">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            <i class="fas fa-chevron-down text-[10px] transition-transform group-hover:rotate-180"></i>
                        </strong>
                    </div>

                    <div
                        class="absolute right-0 top-full mt-0 w-48 md:w-56 bg-white rounded-lg shadow-xl py-2 hidden group-hover:block border border-gray-100 transform opacity-0 group-hover:opacity-100 transition duration-300 text-left">
                        <div
                            class="absolute -top-2 right-4 w-4 h-4 bg-white border-l border-t border-gray-100 transform rotate-45">
                        </div>

                        <div class="px-4 py-2 border-b border-gray-100 md:hidden font-bold text-gray-800 truncate">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </div>

                        <div class="relative z-10 flex flex-col text-gray-700">
                            <a href="user_profile.php"
                                class="px-4 py-2 hover:bg-blue-50 hover:text-blue-600 transition flex items-center gap-3">
                                <i class="fas fa-user-circle w-4 text-center"></i> Hồ sơ cá nhân
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="logout.php"
                                class="px-4 py-2 hover:bg-red-50 hover:text-red-600 transition flex items-center gap-3 text-red-500 font-semibold">
                                <i class="fas fa-sign-out-alt w-4 text-center"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="flex min-h-screen">
        <?php include 'admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-4 md:px-6 py-4 flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Quản lý Flash Sale</h1>
            </header>


            <main class="p-6 flex-1 overflow-y-auto">
                <button onclick="openModal()"
                    class="mb-4 bg-yellow-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-yellow-600 flex items-center gap-2">
                    <i class="fas fa-bolt"></i> Thêm Flash Sale
                </button>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="p-4">Sản phẩm</th>
                                <th class="p-4">Giá gốc</th>
                                <th class="p-4 text-red-600">Giá Flash Sale</th>
                                <th class="p-4">Thời gian</th>
                                <th class="p-4">Trạng thái</th>
                                <th class="p-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_flashsale && $res_flashsale->num_rows > 0): while ($row = $res_flashsale->fetch_assoc()): 
                            $now = time();
                            $start = strtotime($row['NgayBatDauSale']);
                            $end = strtotime($row['NgayKetThucSale']);
                            $statusText = $now < $start ? '<span class="text-yellow-600 bg-yellow-100 px-2 py-1 rounded text-xs">Sắp diễn ra</span>' : 
                                         ($now > $end ? '<span class="text-red-600 bg-red-100 px-2 py-1 rounded text-xs">Đã kết thúc</span>' : 
                                         '<span class="text-green-600 bg-green-100 px-2 py-1 rounded text-xs">Đang chạy</span>');
                        ?>
                            <tr class="border-b">
                                <td class="p-4 flex items-center gap-3">
                                    <img src="public/images/<?php echo $row['hinh']; ?>"
                                        class="w-10 h-10 object-cover rounded">
                                    <span class="font-medium line-clamp-1"><?= $row['TenSanPham'] ?></span>
                                </td>
                                <td class="p-4 line-through text-gray-400"><?= number_format($row['GiaSanPham']) ?>đ
                                </td>
                                <td class="p-4 font-bold text-red-600"><?= number_format($row['GiaKhuyenMai']) ?>đ</td>
                                <td class="p-4 text-sm">
                                    <div>Từ: <?= date('d/m H:i', $start) ?></div>
                                    <div>Đến: <?= date('d/m H:i', $end) ?></div>
                                </td>
                                <td class="p-4"><?= $statusText ?></td>
                                <td class="p-4 text-right space-x-2">
                                    <button onclick='openEdit(<?= json_encode($row) ?>)'
                                        class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmRemove(<?= $row['MaSanPham'] ?>)"
                                        class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">Chưa có sản phẩm Flash Sale</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <div id="flashModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
        <form action="admin_flashsale.php" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Cài đặt Flash Sale</h3>

            <label class="block text-sm font-medium mb-1">Sản phẩm</label>
            <select name="productId" id="productId" required class="w-full border p-2 rounded mb-3">
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($all_products as $p): ?>
                <option value="<?= $p['MaSanPham'] ?>"><?= $p['TenSanPham'] ?></option>
                <?php endforeach; ?>
            </select>

            <label class="block text-sm font-medium mb-1">Giá Flash Sale (VNĐ)</label>
            <input type="number" name="giaKhuyenMai" id="giaKhuyenMai" required
                class="w-full border p-2 rounded mb-3 text-red-600 font-bold">

            <label class="block text-sm font-medium mb-1">Bắt đầu</label>
            <input type="datetime-local" name="ngayBatDauSale" id="ngayBatDauSale" required
                class="w-full border p-2 rounded mb-3">

            <label class="block text-sm font-medium mb-1">Kết thúc</label>
            <input type="datetime-local" name="ngayKetThucSale" id="ngayKetThucSale" required
                class="w-full border p-2 rounded mb-4">

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded font-bold">Lưu</button>
            </div>
        </form>
    </div>

    <script>
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Thêm Flash Sale';
        document.getElementById('productId').value = '';
        document.getElementById('giaKhuyenMai').value = '';
        document.getElementById('ngayBatDauSale').value = '';
        document.getElementById('ngayKetThucSale').value = '';
        document.getElementById('productId').disabled = false;
        document.getElementById('flashModal').classList.remove('hidden');
    }

    function openEdit(p) {
        document.getElementById('modalTitle').innerText = 'Sửa Flash Sale';
        document.getElementById('productId').value = p.MaSanPham;
        document.getElementById('giaKhuyenMai').value = p.GiaKhuyenMai;
        document.getElementById('ngayBatDauSale').value = p.NgayBatDauSale.replace(' ', 'T').slice(0, 16);
        document.getElementById('ngayKetThucSale').value = p.NgayKetThucSale.replace(' ', 'T').slice(0, 16);
        document.getElementById('flashModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('flashModal').classList.add('hidden');
    }

    function confirmRemove(id) {
        if (confirm('Bạn có chắc muốn gỡ sản phẩm này khỏi Flash Sale?')) {
            window.location.href = '?remove_flashsale=' + id;
        }
    }
    </script>
</body>

</html>