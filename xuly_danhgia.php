<?php
session_start();
require_once "database.php";

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Vui lòng đăng nhập để đánh giá!'); window.location.href='login.php';</script>";
        exit;
    }

    $db = new Database();

    // 2. Nhận dữ liệu từ form an toàn
    $product_id = intval($_POST['product_id']);
    $user_id    = intval($_SESSION['user_id']);
    $rating     = intval($_POST['rating']);
    $comment    = isset($_POST['comment']) ? $db->conn->real_escape_string(trim($_POST['comment'])) : '';
    $ngay_binh_luan = date('Y-m-d H:i:s');

    // 3. Nếu product_id không hợp lệ từ POST, thử lấy từ HTTP_REFERER
    if ($product_id <= 0 && isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // Phân tích query string từ URL referer để lấy id
        $parsed = parse_url($referer, PHP_URL_QUERY);
        if ($parsed) {
            parse_str($parsed, $referer_params);
            if (isset($referer_params['id'])) {
                $product_id = intval($referer_params['id']);
            }
        }
    }

    // Kiểm tra lần cuối
    if ($product_id <= 0) {
        echo "<script>alert('Không xác định được sản phẩm. Vui lòng thử lại.'); history.back();</script>";
        exit;
    }

    // 4. Kiểm tra tính hợp lệ của số sao
    if ($rating < 1 || $rating > 5) {
        echo "<script>alert('Số sao không hợp lệ!'); history.back();</script>";
        exit;
    }

    // 5. Lưu vào Database (bảng `review`)
    $sql_insert = "INSERT INTO review (MaSanPham, IdNguoiDung, NoiDung, SoSao, NgayBinhLuan) 
                   VALUES ($product_id, $user_id, '$comment', $rating, '$ngay_binh_luan')";

    $result = $db->execute($sql_insert);

    if ($result) {
        // 6. Cập nhật lại SaoTrungBinh và TongDanhGia vào bảng `product`
        $sql_calc = "SELECT COUNT(id) as Tong, AVG(SoSao) as TrungBinh FROM review WHERE MaSanPham = $product_id";
        $res_calc = $db->select($sql_calc);

        if ($res_calc && $res_calc->num_rows > 0) {
            $row_calc = $res_calc->fetch_assoc();
            $tong_dg  = (int)$row_calc['Tong'];
            $sao_tb   = round((float)$row_calc['TrungBinh'], 1);

            $sql_update = "UPDATE product SET TongDanhGia = $tong_dg, SaoTrungBinh = $sao_tb WHERE MaSanPham = $product_id";
            $db->execute($sql_update);
        }

        // 7. Redirect về đúng trang chi tiết sản phẩm
        header("Location: chitiet.php?id=" . $product_id);
        exit;
    } else {
        echo "<script>alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.'); history.back();</script>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>