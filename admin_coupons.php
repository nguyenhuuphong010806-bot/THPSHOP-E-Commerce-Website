<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once "database.php";
$db = new Database();

// Xóa mã
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->execute("DELETE FROM coupon WHERE id = $id");
    header("Location: admin_coupons.php");
    exit();
}

// Thêm / Sửa mã
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['couponId']);
    $ma = strtoupper($db->conn->real_escape_string($_POST['maCoupon']));
    $loai = $_POST['loaiGiam']; // 'percent' hoặc 'fixed'
    $giaTri = intval($_POST['giaTri']);
    $giaTriToiThieu = intval($_POST['giaTriToiThieu']);
    $soLan = empty($_POST['soLanToiDa']) ? "NULL" : intval($_POST['soLanToiDa']);
    $mota = $db->conn->real_escape_string($_POST['mota']);
    $batDau = date('Y-m-d H:i:s', strtotime($_POST['ngayBatDau']));
    $hetHan = date('Y-m-d H:i:s', strtotime($_POST['ngayHetHan']));
    $trangThai = isset($_POST['trangThai']) ? 1 : 0;

    if ($id > 0) {
        $sql = "UPDATE coupon SET MaCoupon='$ma', LoaiGiam='$loai', GiaTri=$giaTri, GiaTriToiThieu=$giaTriToiThieu, SoLanToiDa=$soLan, MoTa='$mota', NgayBatDau='$batDau', NgayHetHan='$hetHan', TrangThai=$trangThai WHERE id=$id";
    } else {
        $sql = "INSERT INTO coupon (MaCoupon, LoaiGiam, GiaTri, GiaTriToiThieu, SoLanToiDa, MoTa, NgayBatDau, NgayHetHan, TrangThai) 
                VALUES ('$ma', '$loai', $giaTri, $giaTriToiThieu, $soLan, '$mota', '$batDau', '$hetHan', $trangThai)";
    }
    $db->execute($sql);
    header("Location: admin_coupons.php");
    exit();
}

$res_coupons = $db->select("SELECT * FROM coupon ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Coupon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="flex-1 flex flex-col min-h-screen">


            <header class="bg-white shadow-sm border-b px-6 py-4">
                <h1 class="text-xl font-bold">Mã Giảm Giá</h1>
            </header>
            <main class="p-6 flex-1">
                <button onclick="openModal()" class="mb-4 bg-blue-600 text-white px-4 py-2 rounded font-bold"><i
                        class="fas fa-plus"></i> Thêm Mã Mới</button>
                <div class="bg-white border rounded overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3">Mã Code</th>
                                <th class="p-3">Giảm</th>
                                <th class="p-3">Đơn tối thiểu</th>
                                <th class="p-3">Lượt dùng</th>
                                <th class="p-3">Hạn sử dụng</th>
                                <th class="p-3">Trạng thái</th>
                                <th class="p-3">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($res_coupons && $res_coupons->num_rows>0): while($row = $res_coupons->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="p-3 font-bold text-blue-600"><?= $row['MaCoupon'] ?></td>
                                <td class="p-3">
                                    <?= $row['LoaiGiam'] == 'fixed' ? number_format($row['GiaTri']).'đ' : $row['GiaTri'].'%' ?>
                                </td>
                                <td class="p-3"><?= number_format($row['GiaTriToiThieu']) ?>đ</td>
                                <td class="p-3"><?= $row['DaDung'] ?> / <?= $row['SoLanToiDa'] ?? '∞' ?></td>
                                <td class="p-3 text-sm"><?= date('d/m/Y', strtotime($row['NgayHetHan'])) ?></td>
                                <td class="p-3">
                                    <?= $row['TrangThai'] ? '<span class="text-green-600">Bật</span>' : '<span class="text-red-600">Tắt</span>' ?>
                                </td>
                                <td class="p-3 space-x-2">
                                    <button onclick='openEdit(<?= json_encode($row) ?>)' class="text-blue-500"><i
                                            class="fas fa-edit"></i></button>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Xóa mã này?')"
                                        class="text-red-500"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <div id="couponModal" class="fixed inset-0 bg-black/50 hidden flex justify-center items-center p-4">
        <form method="POST" class="bg-white rounded-xl p-6 w-full max-w-lg grid grid-cols-2 gap-4">
            <h3 class="col-span-2 text-lg font-bold">Thông tin mã giảm giá</h3>
            <input type="hidden" name="couponId" id="couponId">

            <div class="col-span-2">
                <label class="text-sm font-medium">Mã Coupon</label>
                <input type="text" name="maCoupon" id="maCoupon" required class="w-full border p-2 rounded uppercase">
            </div>

            <div>
                <label class="text-sm font-medium">Loại giảm</label>
                <select name="loaiGiam" id="loaiGiam" class="w-full border p-2 rounded">
                    <option value="fixed">Giảm tiền (VNĐ)</option>
                    <option value="percent">Giảm phần trăm (%)</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Giá trị giảm</label>
                <input type="number" name="giaTri" id="giaTri" required class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="text-sm font-medium">Đơn tối thiểu</label>
                <input type="number" name="giaTriToiThieu" id="giaTriToiThieu" value="0"
                    class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="text-sm font-medium">Giới hạn lượt dùng (để trống = vô hạn)</label>
                <input type="number" name="soLanToiDa" id="soLanToiDa" class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="text-sm font-medium">Bắt đầu</label>
                <input type="datetime-local" name="ngayBatDau" id="ngayBatDau" required
                    class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="text-sm font-medium">Hết hạn</label>
                <input type="datetime-local" name="ngayHetHan" id="ngayHetHan" required
                    class="w-full border p-2 rounded">
            </div>

            <div class="col-span-2">
                <label class="text-sm font-medium">Mô tả mã</label>
                <input type="text" name="mota" id="mota" class="w-full border p-2 rounded">
            </div>

            <div class="col-span-2 flex items-center gap-2">
                <input type="checkbox" name="trangThai" id="trangThai" checked> <label>Kích hoạt mã</label>
            </div>

            <div class="col-span-2 flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('couponModal').classList.add('hidden')"
                    class="px-4 py-2 border rounded">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Lưu</button>
            </div>
        </form>
    </div>

    <script>
    function openModal() {
        document.getElementById('couponId').value = '';
        document.getElementById('maCoupon').value = '';
        document.getElementById('giaTri').value = '';
        document.getElementById('soLanToiDa').value = '';
        document.getElementById('mota').value = '';
        document.getElementById('couponModal').classList.remove('hidden');
    }

    function openEdit(c) {
        document.getElementById('couponId').value = c.id;
        document.getElementById('maCoupon').value = c.MaCoupon;
        document.getElementById('loaiGiam').value = c.LoaiGiam;
        document.getElementById('giaTri').value = c.GiaTri;
        document.getElementById('giaTriToiThieu').value = c.GiaTriToiThieu;
        document.getElementById('soLanToiDa').value = c.SoLanToiDa || '';
        document.getElementById('ngayBatDau').value = c.NgayBatDau.replace(' ', 'T').slice(0, 16);
        document.getElementById('ngayHetHan').value = c.NgayHetHan.replace(' ', 'T').slice(0, 16);
        document.getElementById('mota').value = c.MoTa;
        document.getElementById('trangThai').checked = c.TrangThai == 1;
        document.getElementById('couponModal').classList.remove('hidden');
    }
    </script>
</body>

</html>