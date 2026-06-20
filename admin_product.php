<?php
// 1. kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// 2. xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->execute("DELETE FROM product WHERE MaSanPham = $id");
    header("Location: admin_product.php");
    exit();
}

// 3. xử lý thêm/sửa sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
    
    // Sử dụng real_escape_string để tránh lỗi khi tên/mô tả có chứa dấu nháy đơn (')
    $ten = $db->conn->real_escape_string($_POST['productName']);
    $gia = intval($_POST['productPrice']);
    $mota = $db->conn->real_escape_string($_POST['productDescription']);
    $danhmuc = intval($_POST['productCategory']);
    
    // Lấy tên các màu sắc và size
    $mau1 = $db->conn->real_escape_string($_POST['mau1']);
    $mau2 = $db->conn->real_escape_string($_POST['mau2']);
    $mau3 = $db->conn->real_escape_string($_POST['mau3']);
    $size = isset($_POST['productSize']) ? $db->conn->real_escape_string($_POST['productSize']) : '';

    // Flags nổi bật / gợi ý
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
    $is_suggested = isset($_POST['is_suggested']) ? 1 : 0;

    // Tồn kho
    $so_luong_ton = isset($_POST['productStock']) ? intval($_POST['productStock']) : 99;

    // DaBan mặc định 0 khi thêm mới (không cho admin sửa)
    $da_ban = 0;



    // --- XỬ LÝ HÌNH ẢNH 1 (Ảnh chính & Màu 1) ---
    $hinh = $_POST['currentImage']; 
    if (isset($_FILES['productImage']) && !empty($_FILES['productImage']['name'])) {
        $hinh = time() . '_1_' . $_FILES['productImage']['name'];
        move_uploaded_file($_FILES['productImage']['tmp_name'], "public/images/" . $hinh);
    } elseif ($id == 0 && empty($hinh)) {
        $hinh = 'default.png'; 
    }

    // --- XỬ LÝ HÌNH ẢNH 2 (Màu 2) ---
    $hinh2 = $_POST['currentImage2']; 
    if (isset($_FILES['productImage2']) && !empty($_FILES['productImage2']['name'])) {
        $hinh2 = time() . '_2_' . $_FILES['productImage2']['name'];
        move_uploaded_file($_FILES['productImage2']['tmp_name'], "public/images/" . $hinh2);
    }

    // --- XỬ LÝ HÌNH ẢNH 3 (Màu 3) ---
    $hinh3 = $_POST['currentImage3']; 
    if (isset($_FILES['productImage3']) && !empty($_FILES['productImage3']['name'])) {
        $hinh3 = time() . '_3_' . $_FILES['productImage3']['name'];
        move_uploaded_file($_FILES['productImage3']['tmp_name'], "public/images/" . $hinh3);
    }

    if ($id > 0) {
        // cập nhật
        $sql = "UPDATE product SET 
                TenSanPham='$ten', GiaSanPham='$gia', MoTa='$mota', MaDanhMuc='$danhmuc',
                hinh='$hinh', hinh2='$hinh2', hinh3='$hinh3',
                mau1='$mau1', mau2='$mau2', mau3='$mau3', size='$size',
                SoLuongTon='$so_luong_ton',
                is_featured='$is_featured',
                is_suggested='$is_suggested'
                WHERE MaSanPham=$id";
    } else {
        // thêm mới
        $sql = "INSERT INTO product (TenSanPham, GiaSanPham, MoTa, MaDanhMuc, hinh, hinh2, hinh3, mau1, mau2, mau3, size,
                                        SoLuongTon, is_featured, is_suggested, DaBan)
                VALUES ('$ten', '$gia', '$mota', '$danhmuc', '$hinh', '$hinh2', '$hinh3', '$mau1', '$mau2', '$mau3', '$size',
                        '$so_luong_ton', '$is_featured', '$is_suggested', '$da_ban')";
    }

    
    $db->execute($sql);
    header("Location: admin_product.php");
    exit();
}

// 4. lấy danh sách sản phẩm và danh mục
$products = $db->select("SELECT p.*, c.TenDanhMuc FROM product p JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc ORDER BY MaSanPham DESC");
$categories = $db->select("SELECT * FROM categories");
$categories_list = [];
if ($categories) {
    while($cat = $categories->fetch_assoc()) { 
        $categories_list[] = $cat; 
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - TTP Shop</title>
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
                        <i class="fas fa-box text-blue-600"></i> Danh sách sản phẩm
                    </h2>
                    <button onclick="openAddModal()"
                        class="w-full sm:w-auto bg-green-600 text-white px-5 md:px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold shadow-lg shadow-green-200 flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full min-w-[750px]">
                        <thead
                            class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] md:text-xs font-bold tracking-wider">
                            <tr>
                                <th class="p-3 md:p-4 text-left w-12 md:w-16">ID</th>
                                <th class="p-3 md:p-4 text-left w-20 md:w-24">Hình</th>
                                <th class="p-3 md:p-4 text-left">Tên sản phẩm</th>
                                <th class="p-3 md:p-4 text-left w-32">Danh mục</th>
                                <th class="p-3 md:p-4 text-left w-32">Giá</th>
                                <th class="p-3 md:p-4 text-center w-24 md:w-28">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs md:text-sm">
                            <?php if($products): while($row = $products->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-3 md:p-4 font-bold text-gray-500">#<?php echo $row['MaSanPham']; ?></td>
                                <td class="p-3 md:p-4">
                                    <img src="public/images/<?php echo $row['hinh']; ?>"
                                        class="w-10 h-10 md:w-14 md:h-14 object-cover rounded-lg border border-gray-200 shadow-sm">
                                </td>
                                <td class="p-3 md:p-4 font-bold text-gray-800"><?php echo $row['TenSanPham']; ?></td>
                                <td class="p-3 md:p-4">
                                    <span
                                        class="inline-block px-2 md:px-3 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-full text-[10px] md:text-xs font-bold whitespace-nowrap">
                                        <?php echo $row['TenDanhMuc']; ?>
                                    </span>
                                </td>
                                <td class="p-3 md:p-4 font-bold text-red-600 text-sm md:text-base whitespace-nowrap">
                                    <?php echo number_format($row['GiaSanPham']); ?>đ
                                </td>
                                <td class="p-3 md:p-4">
                                    <div class="flex justify-center gap-1.5 md:gap-2">
                                        <button
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                            class="w-7 h-7 md:w-8 md:h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition flex items-center justify-center"
                                            title="Sửa">
                                            <i class="fas fa-edit text-xs md:text-sm"></i>
                                        </button>
                                        <a href="admin_product.php?delete=<?php echo $row['MaSanPham']; ?>"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')"
                                            class="w-7 h-7 md:w-8 md:h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition flex items-center justify-center"
                                            title="Xóa">
                                            <i class="fas fa-trash text-xs md:text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-500">Chưa có sản phẩm nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="productModal"
        class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-2 md:p-4 backdrop-blur-sm transition-opacity">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-4 md:p-8 max-h-[95vh] md:max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h2 id="modalTitle" class="text-xl md:text-2xl font-black mb-4 md:mb-6 text-gray-800 border-b pb-3 md:pb-4">
                Thêm sản phẩm mới</h2>
            <form action="admin_product.php" method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-5">
                <input type="hidden" name="productId" id="productId" value="0">
                <input type="hidden" name="currentImage" id="currentImage" value="">
                <input type="hidden" name="currentImage2" id="currentImage2" value="">
                <input type="hidden" name="currentImage3" id="currentImage3" value="">

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Tên sản phẩm <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="productName" id="productName" required
                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-sm md:text-base">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Danh mục <span
                                class="text-red-500">*</span></label>
                        <select name="productCategory" id="productCategory" required
                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer text-sm md:text-base">
                            <?php foreach($categories_list as $cat): ?>
                            <option value="<?php echo $cat['MaDanhMuc']; ?>"><?php echo $cat['TenDanhMuc']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Giá (VNĐ) <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="productPrice" id="productPrice" required
                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-bold text-red-600 text-sm md:text-base">
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Kích cỡ (Size)</label>
                    <input type="text" name="productSize" id="productSize"
                        placeholder="VD: S, M, L hoặc 36, 37, 38. Phân cách bằng dấu phẩy (,)"
                        class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-sm md:text-base">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Tồn kho (Số
                            lượng)</label>
                        <input type="number" min="0" name="productStock" id="productStock" value="99"
                            class="w-full px-3 md:px-4 py-2 md:py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-bold text-gray-900 text-sm md:text-base">
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Trạng thái hiển
                            thị</label>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <label class="flex items-center gap-2 text-sm md:text-base mb-2">
                                <input type="checkbox" name="is_featured" id="is_featured" class="w-4 h-4" value="1">
                                <span class="font-semibold">Nổi bật</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm md:text-base">
                                <input type="checkbox" name="is_suggested" id="is_suggested" class="w-4 h-4" value="1">
                                <span class="font-semibold">Gợi ý</span>
                            </label>
                        </div>
                    </div>
                </div>


                <div class="bg-blue-50/50 p-3 md:p-5 rounded-xl border border-blue-100">
                    <h3 class="font-bold text-blue-800 mb-1.5 md:mb-2 text-sm md:text-base"><i
                            class="fas fa-palette mr-2"></i>Tùy chọn Màu sắc & Hình ảnh</h3>
                    <p class="text-[11px] md:text-xs text-blue-600 mb-3 md:mb-4 opacity-80">* Để trống màu 2 và 3 nếu
                        sản phẩm chỉ có 1 màu.</p>

                    <div class="space-y-3 md:space-y-4">
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Tên Màu 1 (Mặc
                                    định)</label>
                                <input type="text" name="mau1" id="mau1" placeholder="VD: Trắng"
                                    class="w-full px-3 py-1.5 md:py-2 border rounded-lg focus:outline-none focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Ảnh Màu 1 (Ảnh
                                    chính) <span class="text-red-500">*</span></label>
                                <input type="file" name="productImage" id="productImage"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-[11px] md:text-sm focus:outline-none focus:border-blue-500 file:mr-2 md:file:mr-4 file:py-1 file:px-2 md:file:px-3 file:rounded-full file:border-0 file:text-[11px] md:file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Tên Màu
                                    2</label>
                                <input type="text" name="mau2" id="mau2" placeholder="VD: Đen"
                                    class="w-full px-3 py-1.5 md:py-2 border rounded-lg focus:outline-none focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Ảnh Màu
                                    2</label>
                                <input type="file" name="productImage2" id="productImage2"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-[11px] md:text-sm focus:outline-none focus:border-blue-500 file:mr-2 md:file:mr-4 file:py-1 file:px-2 md:file:px-3 file:rounded-full file:border-0 file:text-[11px] md:file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Tên Màu
                                    3</label>
                                <input type="text" name="mau3" id="mau3" placeholder="VD: Xanh"
                                    class="w-full px-3 py-1.5 md:py-2 border rounded-lg focus:outline-none focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-[13px] md:text-sm font-bold mb-1 text-gray-700">Ảnh Màu
                                    3</label>
                                <input type="file" name="productImage3" id="productImage3"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-[11px] md:text-sm focus:outline-none focus:border-blue-500 file:mr-2 md:file:mr-4 file:py-1 file:px-2 md:file:px-3 file:rounded-full file:border-0 file:text-[11px] md:file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>
                        <p id="imageHelper"
                            class="text-[11px] md:text-xs text-orange-500 mt-2 hidden text-center font-semibold bg-orange-50 py-1.5 rounded">
                            <i class="fas fa-info-circle mr-1"></i> Đang chỉnh sửa: Bỏ trống ô tải ảnh nếu muốn giữ
                            nguyên ảnh cũ.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700 text-sm md:text-base">Mô tả sản phẩm</label>
                    <textarea name="productDescription" id="productDescription" rows="4"
                        placeholder="Nhập mô tả chi tiết sản phẩm..."
                        class="w-full px-3 md:px-4 py-2 md:py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition leading-relaxed text-sm md:text-base"></textarea>
                </div>

                <div class="flex gap-3 md:gap-4 pt-4 md:pt-6 border-t border-gray-100">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-2.5 md:py-3.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 text-sm md:text-base">
                        <i class="fas fa-save"></i> Lưu thông tin
                    </button>
                    <button type="button" onclick="closeModal()"
                        class="w-1/3 bg-gray-100 py-2.5 md:py-3.5 rounded-xl font-bold hover:bg-gray-200 transition text-gray-700 flex items-center justify-center gap-2 text-sm md:text-base">
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
        document.getElementById('modalTitle').innerText = 'Thêm sản phẩm mới';
        document.getElementById('productId').value = '0';

        // mặc định
        var cb1 = document.getElementById('is_featured');
        var cb2 = document.getElementById('is_suggested');
        if (cb1) cb1.checked = false;
        if (cb2) cb2.checked = false;
        var stock = document.getElementById('productStock');
        if (stock) stock.value = '99';


        document.getElementById('currentImage').value = '';
        document.getElementById('currentImage2').value = '';
        document.getElementById('currentImage3').value = '';

        document.getElementById('productName').value = '';
        document.getElementById('productPrice').value = '';
        document.getElementById('productDescription').value = '';
        document.getElementById('productSize').value = '';
        document.getElementById('mau1').value = '';
        document.getElementById('mau2').value = '';
        document.getElementById('mau3').value = '';

        document.getElementById('productImage').value = '';
        document.getElementById('productImage2').value = '';
        document.getElementById('productImage3').value = '';
        document.getElementById('imageHelper').classList.add('hidden');

        if (document.getElementById('productCategory').options.length > 0) {
            document.getElementById('productCategory').selectedIndex = 0;
        }

        document.getElementById('productModal').classList.remove('hidden');
    }

    function openEditModal(product) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa sản phẩm';
        document.getElementById('productId').value = product.MaSanPham;

        var stock = document.getElementById('productStock');
        if (stock) stock.value = product.SoLuongTon ?? '99';
        var cb1 = document.getElementById('is_featured');
        var cb2 = document.getElementById('is_suggested');
        if (cb1) cb1.checked = (product.is_featured == 1 || product.is_featured === true);
        if (cb2) cb2.checked = (product.is_suggested == 1 || product.is_suggested === true);


        document.getElementById('currentImage').value = product.hinh || '';
        document.getElementById('currentImage2').value = product.hinh2 || '';
        document.getElementById('currentImage3').value = product.hinh3 || '';

        document.getElementById('productName').value = product.TenSanPham;
        document.getElementById('productPrice').value = product.GiaSanPham;
        document.getElementById('productCategory').value = product.MaDanhMuc;
        document.getElementById('productDescription').value = product.MoTa;

        document.getElementById('productSize').value = product.size || '';

        document.getElementById('mau1').value = product.mau1 || '';
        document.getElementById('mau2').value = product.mau2 || '';
        document.getElementById('mau3').value = product.mau3 || '';

        document.getElementById('productImage').value = '';
        document.getElementById('productImage2').value = '';
        document.getElementById('productImage3').value = '';
        document.getElementById('imageHelper').classList.remove('hidden');

        document.getElementById('productModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('productModal').classList.add('hidden');
    }
    </script>
</body>

</html>