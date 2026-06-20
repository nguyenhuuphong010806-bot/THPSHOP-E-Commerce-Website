<?php
session_start();
require_once "database.php";

$db = new Database();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Đọc số lượng từ tham số 'qty' (do main.js gửi lên qua updateLinks)
    // Fallback sang 'soluong' nếu có nơi nào gọi cách khác
    $soluong_them = 1;
    if (isset($_GET['qty']) && intval($_GET['qty']) > 0) {
        $soluong_them = intval($_GET['qty']);
    } elseif (isset($_GET['soluong']) && intval($_GET['soluong']) > 0) {
        $soluong_them = intval($_GET['soluong']);
    }

    // lấy thông tin sản phẩm từ db
    $sql = "SELECT * FROM product WHERE MaSanPham = $id";
    $res = $db->select($sql);

    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();

        // tạo cấu trúc sản phẩm trong giỏ
        $item = [
            'id'      => $product['MaSanPham'],
            'ten'     => $product['TenSanPham'],
            'gia'     => $product['GiaSanPham'],
            'hinh'    => $product['hinh'],
            'soluong' => $soluong_them
        ];

        // nếu giỏ hàng đã có sản phẩm này thì cộng thêm số lượng được chọn
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['soluong'] += $soluong_them;
        } else {
            // nếu chưa có thì thêm mới vào giỏ với đúng số lượng
            $_SESSION['cart'][$id] = $item;
        }
    }
}

// sau khi xử lý xong, quay lại trang giỏ hàng
if (isset($_GET['action']) && $_GET['action'] === 'ajax') {
    echo 'success';
    exit;
}

setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
header("Location: cart.php");
exit();
?>