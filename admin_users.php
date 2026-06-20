<?php
session_start();
// Kiểm tra quyền admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit();
}
require_once "database.php";
$db = new Database();

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$like = '%' . $db->conn->real_escape_string($search) . '%';
$where = $search !== '' ? "WHERE (TenNguoiDung LIKE '$like' OR email LIKE '$like')" : "";

// Lấy danh sách người dùng
$sql = "SELECT * FROM user $where ORDER BY IdNguoiDung DESC";
$r_users = $db->select($sql);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
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

        <main class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-800">Danh sách thành viên</h1>
                <div class="text-sm text-gray-500">Tổng cộng: <?php echo $r_users ? $r_users->num_rows : 0; ?> tài khoản
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
                <form action="" method="GET" class="flex gap-3">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Tìm theo tên, email hoặc số điện thoại..."
                        class="flex-1 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Tìm kiếm
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 uppercase text-xs font-bold">
                            <th class="p-4">Thành viên</th>
                            <th class="p-4 text-center">Vai trò</th>
                            <th class="p-4 text-center">Trạng thái</th>
                            <th class="p-4 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($r_users): while ($u = $r_users->fetch_assoc()): ?>
                        <tr class="hover:bg-blue-50/30 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img src="uploads/<?php echo $u['AnhDaiDien']; ?>"
                                        onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($u['TenNguoiDung']); ?>'"
                                        class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                    <div>
                                        <div class="font-bold text-gray-800"><?php echo $u['TenNguoiDung']; ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $u['email']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <a href="admin_user_handle.php?action=change_role&id=<?php echo $u['IdNguoiDung']; ?>&current=<?php echo $u['quyen']; ?>"
                                    class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $u['quyen']=='admin' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'; ?>">
                                    <?php echo $u['quyen']; ?>
                                </a>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($u['trangThai'] == 1): ?>
                                <span
                                    class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Hoạt động
                                </span>
                                <?php else: ?>
                                <span
                                    class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Bị khóa
                                </span>

                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="flex justify-center gap-2">
                                    <a href="admin_user_handle.php?action=toggle_status&id=<?php echo $u['IdNguoiDung']; ?>&status=<?php echo $u['trangThai']; ?>"
                                        title="<?php echo $u['trangThai'] == 1 ? 'Khóa tài khoản' : 'Mở khóa'; ?>"
                                        class="p-2 <?php echo $u['trangThai'] == 1 ? 'text-amber-500 hover:bg-amber-50' : 'text-green-500 hover:bg-green-50'; ?> rounded-lg transition">
                                        <i
                                            class="fas <?php echo $u['trangThai'] == 1 ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    </a>

                                    <button onclick="confirmReset(<?php echo $u['IdNguoiDung']; ?>)"
                                        title="Reset mật khẩu về 123456"
                                        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    <button onclick="confirmDelete(<?php echo $u['IdNguoiDung']; ?>)"
                                        title="Xóa tài khoản"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: "Dữ liệu người dùng và lịch sử đơn hàng sẽ bị ảnh hưởng!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = "admin_user_handle.php?action=delete&id=" + id;
        })
    }

    function confirmReset(id) {
        Swal.fire({
            title: 'Reset mật khẩu?',
            text: "Mật khẩu sẽ được đưa về mặc định: 123456",
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Thực hiện',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = "admin_user_handle.php?action=reset_pw&id=" + id;
        })
    }
    </script>
</body>

</html>