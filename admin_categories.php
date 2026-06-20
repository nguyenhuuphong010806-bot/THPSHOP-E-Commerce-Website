<?php
// 1. kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// 2. xử lý xóa danh mục
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // lưu ý: nếu có sản phẩm thuộc danh mục này, câu lệnh xóa có thể bị lỗi do ràng buộc khóa ngoại (fk_sp_dm)
    $db->execute("DELETE FROM categories WHERE MaDanhMuc = $id");
    header("Location: admin_categories.php");
    exit();
}

// 3. xử lý thêm/sửa danh mục
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['catId']) ? intval($_POST['catId']) : 0;
    $ten = $db->conn->real_escape_string($_POST['catName']);
    $mota = $db->conn->real_escape_string($_POST['catDescription']);

    if ($id > 0) {
        // cập nhật danh mục đã có
        $sql = "UPDATE categories SET TenDanhMuc='$ten', MoTa='$mota' WHERE MaDanhMuc=$id";
    } else {
        // thêm danh mục mới
        $sql = "INSERT INTO categories (TenDanhMuc, MoTa) VALUES ('$ten', '$mota')";
    }
    
    $db->execute($sql);
    header("Location: admin_categories.php");
    exit();
}

// 4. lấy danh sách danh mục
$categories = $db->select("SELECT * FROM categories ORDER BY MaDanhMuc DESC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - TTP Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 flex flex-col min-h-screen">
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

    <div class="flex flex-1 relative overflow-hidden">
        <?php include 'admin_sidebar.php'; ?>

        <main class="flex-1 p-4 md:p-8 w-full overflow-hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list-alt text-blue-600"></i> Danh sách danh mục
                    </h2>
                    <button onclick="openAddModal()"
                        class="w-full sm:w-auto bg-green-600 text-white px-5 md:px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold shadow-lg shadow-green-200 flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Thêm danh mục
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full min-w-[600px]">
                        <thead
                            class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] md:text-xs font-bold tracking-wider">
                            <tr>
                                <th class="p-3 md:p-4 text-left w-16 md:w-20">Mã</th>
                                <th class="p-3 md:p-4 text-left w-40 md:w-64">Tên danh mục</th>
                                <th class="p-3 md:p-4 text-left">Mô tả</th>
                                <th class="p-3 md:p-4 text-center w-28 md:w-32">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while($row = $categories->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-3 md:p-4 font-mono text-gray-500 font-bold text-xs md:text-sm">
                                    #<?php echo $row['MaDanhMuc']; ?></td>
                                <td class="p-3 md:p-4 font-bold text-blue-600 text-sm md:text-base">
                                    <?php echo htmlspecialchars($row['TenDanhMuc']); ?></td>
                                <td class="p-3 md:p-4 text-gray-600 italic leading-relaxed text-xs md:text-sm">
                                    <?php echo htmlspecialchars($row['MoTa'] ?? 'Chưa có mô tả'); ?>
                                </td>
                                <td class="p-3 md:p-4">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)'
                                            class="w-7 h-7 md:w-8 md:h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition flex items-center justify-center"
                                            title="Sửa">
                                            <i class="fas fa-edit text-xs md:text-sm"></i>
                                        </button>
                                        <a href="admin_categories.php?delete=<?php echo $row['MaDanhMuc']; ?>"
                                            onclick="return confirm('Lưu ý: Nếu xóa danh mục có chứa sản phẩm, bạn phải xóa sản phẩm trước. Bạn vẫn muốn tiếp tục xóa?')"
                                            class="w-7 h-7 md:w-8 md:h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition flex items-center justify-center"
                                            title="Xóa">
                                            <i class="fas fa-trash text-xs md:text-sm"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-500">Chưa có danh mục nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="catModal"
        class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
        <div
            class="bg-white rounded-2xl max-w-lg w-full p-5 md:p-8 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h2 id="modalTitle" class="text-xl md:text-2xl font-black mb-4 md:mb-6 text-gray-800 border-b pb-3 md:pb-4">
                Thêm danh mục mới</h2>
            <form action="admin_categories.php" method="POST" class="space-y-4 md:space-y-5">
                <input type="hidden" name="catId" id="catId">

                <div>
                    <label class="block font-bold text-gray-700 mb-1.5 text-sm md:text-base">Tên danh mục <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="catName" id="catName" required
                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-sm md:text-base">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1.5 text-sm md:text-base">Mô tả danh mục</label>
                    <textarea name="catDescription" id="catDescription" rows="4" placeholder="Viết mô tả ngắn gọn..."
                        class="w-full px-3 md:px-4 py-2 md:py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition leading-relaxed text-sm md:text-base"></textarea>
                </div>

                <div class="flex gap-3 md:gap-4 pt-4 md:pt-6 border-t border-gray-100">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-2.5 md:py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 text-sm md:text-base">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" onclick="closeModal()"
                        class="w-1/3 bg-gray-100 py-2.5 md:py-3 rounded-xl font-bold hover:bg-gray-200 transition text-gray-700 flex items-center justify-center gap-2 text-sm md:text-base">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    /* Tùy chỉnh thanh cuộn cho Modal gọn gàng trên mobile */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>

    <script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Thêm danh mục mới';
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catDescription').value = '';
        document.getElementById('catModal').classList.remove('hidden');
    }

    function openEditModal(cat) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa danh mục';
        document.getElementById('catId').value = cat.MaDanhMuc;
        document.getElementById('catName').value = cat.TenDanhMuc;
        document.getElementById('catDescription').value = cat.MoTa;
        document.getElementById('catModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('catModal').classList.add('hidden');
    }
    </script>
</body>

</html>