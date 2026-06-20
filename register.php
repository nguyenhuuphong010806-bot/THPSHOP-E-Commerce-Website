<?php
require_once "database.php";
$db = new Database();

$swal_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']          ?? '');
    $email    = trim($_POST['email']             ?? '');
    $phone    = trim($_POST['phone']             ?? '');
    $gender   = trim($_POST['gender']            ?? 'male');
    $dob      = trim($_POST['dob']               ?? '');
    $address  = trim($_POST['address']           ?? '');
    $password = $_POST['password']               ?? '';
    $confirm  = $_POST['confirm-password']       ?? '';

    // ---- Kiểm tra đầu vào ----
    if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $swal_script = "Swal.fire('Thất bại', 'Vui lòng nhập đầy đủ thông tin!', 'warning');";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $swal_script = "Swal.fire('Thất bại', 'Email không đúng định dạng!', 'warning');";

    } elseif (strlen($password) < 6) {
        $swal_script = "Swal.fire('Thất bại', 'Mật khẩu phải có ít nhất 6 ký tự!', 'warning');";

    } elseif ($password !== $confirm) {
        $swal_script = "Swal.fire('Thất bại', 'Mật khẩu xác nhận không khớp!', 'error');";

    } else {
        // Escape dữ liệu trước khi đưa vào SQL
        $fullname_e = $db->conn->real_escape_string($fullname);
        $email_e    = $db->conn->real_escape_string($email);
        $phone_e    = $db->conn->real_escape_string($phone);
        $gender_e   = in_array($gender, ['male', 'female']) ? $gender : 'male';
        $dob_e      = $db->conn->real_escape_string($dob);
        $address_e  = $db->conn->real_escape_string($address);

        // Kiểm tra email đã tồn tại
        $checkEmail = $db->select("SELECT IdNguoiDung FROM user WHERE email = '$email_e' LIMIT 1");
        if ($checkEmail && $checkEmail->num_rows > 0) {
            $swal_script = "Swal.fire('Lỗi', 'Email này đã được sử dụng!', 'warning');";
        } else {
            // Hash mật khẩu trước khi lưu
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $hashed_e = $db->conn->real_escape_string($hashed);

            $dob_val = !empty($dob_e) ? "'$dob_e'" : "NULL";

            $sql = "INSERT INTO user (TenNguoiDung, email, matkhau, quyen, diachi, SoDienThoai, GioiTinh, NgaySinh)
                    VALUES ('$fullname_e', '$email_e', '$hashed_e', 'user', '$address_e', '$phone_e', '$gender_e', $dob_val)";

            if ($db->execute($sql)) {
                $swal_script = "
                    Swal.fire({
                        title: 'Thành công!',
                        text: 'Đăng ký hoàn tất. Bạn sẽ được chuyển đến trang đăng nhập.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => { window.location.href = 'login.php'; });
                ";
            } else {
                $swal_script = "Swal.fire('Lỗi', 'Có lỗi xảy ra trong quá trình lưu dữ liệu.', 'error');";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - TTP Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0056b3, #f39c12);
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex text-gray-800">

    <div
        class="hidden lg:flex lg:fixed lg:inset-y-0 lg:left-0 lg:w-1/2 items-center justify-center relative overflow-hidden z-0 ">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-30">
        </div>

        <div class="z-10 flex flex-col items-center justify-center p-12 text-center">
            <img src="public/images/icon_web.png" alt="Shopping Illustration"
                class="w-4/5 max-w-md drop-shadow-2xl mb-8 transform hover:scale-105 transition-transform duration-500">
            <h1 class="text-4xl font-black text-blue-900 tracking-tight mb-3">THÀNH VIÊN TTP<span
                    class="text-4xl font-black text-yellow-500 tracking-tight mb-3">SHOP</span></h1>
            <p class="text-white font-medium text-lg max-w-sm">
                Tạo tài khoản ngay hôm nay để nhận voucher giảm giá và theo dõi đơn hàng dễ dàng!
            </p>
        </div>
    </div>

    <div
        class="w-full lg:w-1/2 lg:ml-[50%] flex flex-col justify-center p-6 sm:p-12 bg-white relative min-h-screen z-10">

        <a href="index.php"
            class="absolute top-6 left-6 text-gray-500 hover:text-blue-600 transition flex items-center gap-2 font-medium z-20">
            <i class="fas fa-arrow-left"></i> Trang chủ
        </a>

        <div class="w-full max-w-lg mx-auto mt-10 lg:mt-0">
            <div class="text-center mb-8">
                <a href="index.php">
                    <img src="public/images/icon_web.png" alt="TTP Shop" class="h-16 mx-auto mb-4 object-contain">
                </a>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Tạo tài khoản mới <i class="fas fa-user-plus"></i>
                </h2>
                <p class="text-gray-500">Vui lòng điền đầy đủ thông tin bên dưới</p>
            </div>

            <form action="register.php" method="POST" class="space-y-4">

                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1.5">Họ và tên *</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Nhập họ và tên của bạn" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                        <input type="email" id="email" name="email" placeholder="Nhập email" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Số điện thoại
                            *</label>
                        <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1.5">Giới tính *</label>
                        <select id="gender" name="gender" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none cursor-pointer appearance-none">
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                        </select>
                    </div>
                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700 mb-1.5">Ngày sinh *</label>
                        <input type="date" id="dob" name="dob" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Địa chỉ *</label>
                    <input type="text" id="address" name="address" placeholder="Nhập địa chỉ giao hàng" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu *</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Tạo mật khẩu" required
                                class="w-full pl-4 pr-12 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                            <span
                                class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer text-gray-400 hover:text-blue-600 transition-colors toggle-password"
                                data-target="password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1.5">Xác nhận
                            mật khẩu *</label>
                        <div class="relative">
                            <input type="password" id="confirm-password" name="confirm-password"
                                placeholder="Nhập lại mật khẩu" required
                                class="w-full pl-4 pr-12 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                            <span
                                class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer text-gray-400 hover:text-blue-600 transition-colors toggle-password"
                                data-target="confirm-password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center pt-2 pb-4 text-sm text-gray-600">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="agree"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-2" required>
                        <span>Tôi đồng ý với các <a href="#" class="text-blue-600 font-medium hover:underline">Điều
                                khoản dịch vụ</a></span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-gray-900/20">
                    ĐĂNG KÝ TÀI KHOẢN
                </button>
            </form>

            <p class="text-center mt-8 text-gray-600">
                Đã có tài khoản?
                <a href="login.php" class="text-blue-600 font-bold hover:underline">Đăng nhập ngay</a>
            </p>
        </div>
    </div>

    <script>
    // Logic tắt/mở mật khẩu cho cả 2 ô
    const togglePasswords = document.querySelectorAll('.toggle-password');

    togglePasswords.forEach(toggle => {
        toggle.addEventListener('click', function() {
            // Lấy ID của ô input đang cần ẩn/hiện
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);

            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            // Đổi icon tương ứng
            this.innerHTML = type === 'text' ?
                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' :
                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        });
    });
    </script>

    <?php if ($swal_script != ""): ?>
    <script>
    <?php echo $swal_script; ?>
    </script>
    <?php endif; ?>

</body>

</html>