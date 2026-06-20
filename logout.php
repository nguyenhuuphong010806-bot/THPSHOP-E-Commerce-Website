<?php
session_start();

// Xóa cookie giỏ hàng
if (isset($_COOKIE['shopping_cart'])) {
    setcookie('shopping_cart', '', time() - 3600, '/');
}

// Xóa toàn bộ session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Ngăn browser cache trang sau khi logout (nhấn Back không quay lại được)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>