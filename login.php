<?php
session_start();
require_once "database.php";
$db = new Database();

$error = "";
$redirect_url = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($input) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        // Tìm user theo email HOẶC tên đăng nhập — chỉ dùng input để tìm, KHÔNG so khớp mật khẩu trong SQL
        $input_escaped = $db->conn->real_escape_string($input);
        $sql    = "SELECT * FROM user WHERE email = '$input_escaped' OR TenNguoiDung = '$input_escaped' LIMIT 1";
        $result = $db->select($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stored_pw = $user['matkhau'];

            // Hỗ trợ cả 2 trường hợp: mật khẩu đã hash (bcrypt) và chưa hash (cũ)
            // Sau khi chạy migrate_passwords.php thì chỉ còn bcrypt
            $pw_ok = false;
            if (password_verify($password, $stored_pw)) {
                // Mật khẩu đã được hash đúng chuẩn
                $pw_ok = true;
            } elseif ($stored_pw === $password) {
                // Mật khẩu cũ chưa hash — đăng nhập được nhưng tự động nâng cấp hash ngay
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $uid_upd  = intval($user['IdNguoiDung']);
                $db->execute("UPDATE user SET matkhau = '$new_hash' WHERE IdNguoiDung = $uid_upd");
                $pw_ok = true;
            }

            if ($pw_ok) {
                // Tái tạo session ID để chống Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id']     = $user['IdNguoiDung'];
                $_SESSION['user_name']   = $user['TenNguoiDung'];
                $_SESSION['user_role']   = $user['quyen'];
                $_SESSION['user_avatar'] = $user['AnhDaiDien'] ?? 'default.png';

                $redirect_url = ($user['quyen'] == 'admin') ? "admin_product.php" : "index.php";
            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
            }
        } else {
            // Dùng thông báo chung để không lộ email có tồn tại hay không
            $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
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
    <title>Đăng nhập - TTP Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0056b3, #f39c12);
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex text-gray-800">

    <div
        class="hidden lg:flex lg:fixed lg:inset-y-0 lg:right-0 lg:w-1/2 items-center justify-center relative overflow-hidden z-0 ">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-30">
        </div>

        <div class="z-10 flex flex-col items-center justify-center p-12 text-center">
            <img src="public/images/icon_web.png" alt="Shopping Illustration"
                class="w-4/5 max-w-md drop-shadow-2xl mb-8 transform hover:scale-105 transition-transform duration-500">
            <h1 class="text-4xl font-black text-blue-900 tracking-tight mb-3">HÀNG CHÍNH HÃNG 100%</h1>
            <p class="text-blue-700 font-medium text-lg max-w-sm">
                Trải nghiệm mua sắm tuyệt vời cùng TTPShop với hàng ngàn ưu đãi hấp dẫn mỗi ngày.
            </p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 bg-white relative">

        <a href="index.php"
            class="absolute top-6 left-6 text-gray-500 hover:text-blue-600 transition flex items-center gap-2 font-medium">
            <i class="fas fa-arrow-left"></i> Trang chủ
        </a>

        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="index.php">
                    <img src="public/images/icon_web.png" alt="TTP Shop" class="h-16 mx-auto mb-4 object-contain">
                </a>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Chào mừng trở lại! 👋</h2>
                <p class="text-gray-500">Vui lòng đăng nhập để tiếp tục</p>
            </div>

            <form action="login.php" method="POST" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Tên đăng nhập hoặc
                        Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="email" name="email" placeholder="Nhập email của bạn" required
                            class="w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required
                            class="w-full pl-11 pr-12 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none">
                        <span
                            class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer text-gray-400 hover:text-blue-600 transition-colors"
                            id="togglePassword">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span>Nhớ mật khẩu</span>
                    </label>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium hover:underline transition">Quên
                        mật khẩu?</a>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-blue-900 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-gray-900/20 mt-2">
                    ĐĂNG NHẬP
                </button>
            </form>



            <p class="text-center mt-8 text-gray-600">
                Chưa có tài khoản?
                <a href="register.php" class="text-blue-600 font-bold hover:underline">Đăng ký ngay</a>
            </p>
        </div>
    </div>

    <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'text' ?
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>' :
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    });
    </script>

    <?php if ($error != ""): ?>
    <script>
    Swal.fire({
        title: 'Lỗi!',
        text: '<?php echo $error; ?>',
        icon: 'error',
        confirmButtonText: 'Thử lại',
        confirmButtonColor: '#111827' // Đổi màu nút alert cho khớp theme
    });
    </script>
    <?php endif; ?>

    <?php if ($redirect_url != ""): ?>
    <script>
    Swal.fire({
        title: 'Thành công!',
        text: 'Đăng nhập thành công! Đang chuyển hướng...',
        icon: 'success',
        timer: 1500, // Đợi 1.5 giây
        showConfirmButton: false
    }).then(() => {
        window.location.href = '<?php echo $redirect_url; ?>'; // Chuyển trang bằng JS
    });
    </script>
    <?php endif; ?>

</body>

</html>