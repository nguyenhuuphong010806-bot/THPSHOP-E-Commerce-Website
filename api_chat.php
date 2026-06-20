<?php
// Tên file: api_chat.php
header('Content-Type: application/json');
require_once 'database.php'; 

$danh_sach_tu_dong = "";

try {
    $db = new Database();
    $sql = "SELECT TenSanPham, GiaSanPham FROM product";
    $result = $db->select($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $gia_formated = number_format($row['GiaSanPham'], 0, ',', '.');
            $danh_sach_tu_dong .= "- " . $row['TenSanPham'] . ": " . $gia_formated . "đ\n";
        }
    } else {
        $danh_sach_tu_dong = "Hiện tại shop đang cập nhật sản phẩm mới.";
    }
    $db->close();
} catch (Exception $e) {
    $danh_sach_tu_dong = "Lỗi lấy dữ liệu DB: " . $e->getMessage();
}

// Chỉ trả về đúng danh sách sản phẩm
echo json_encode(['danh_sach' => $danh_sach_tu_dong]);
?>