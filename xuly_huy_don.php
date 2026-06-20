<?php
/**
 * xuly_huy_don.php — Xử lý hủy đơn hàng từ phía khách hàng
 * Chỉ cho phép hủy khi trangThai = 0 (Đã đặt)
 */
session_start();
require_once 'database.php';

// Phải đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db      = new Database();
$user_id = intval($_SESSION['user_id']);
$oid     = intval($_GET['id'] ?? 0);

if ($oid <= 0) {
    header('Location: orders.php');
    exit();
}

// Kiểm tra đơn hàng thuộc về user này và đang ở trạng thái "Đã đặt" (0)
$res = $db->select(
    "SELECT * FROM donhang
     WHERE MaDonHang = $oid
       AND IdNguoiDung = $user_id
       AND trangThai = 0
     LIMIT 1"
);

if (!$res || $res->num_rows === 0) {
    // Không hợp lệ hoặc đã xử lý rồi
    header('Location: orders.php?error=cannot_cancel');
    exit();
}

// 1. Cập nhật trạng thái → 4 (Đã hủy)
$db->execute("UPDATE donhang SET trangThai = 4 WHERE MaDonHang = $oid");

// 2. Rollback: trừ lại DaBan + hoàn lại tồn kho cho từng sản phẩm trong đơn

$res_items = $db->select(
    "SELECT MaSanPham, SoLuong FROM chitietdonhang WHERE MaDonHang = $oid"
);
if ($res_items && $res_items->num_rows > 0) {
    while ($item = $res_items->fetch_assoc()) {
        $pid = intval($item['MaSanPham']);
        $qty = intval($item['SoLuong']);
        // 2.1 Rollback DaBan: trừ lại đúng số lượng đã mua trong đơn
        $db->execute("UPDATE product SET DaBan = GREATEST(DaBan - $qty, 0) WHERE MaSanPham = $pid");

        // 2.2 Hoàn lại tồn kho
        $db->execute(
            "UPDATE product SET SoLuongTon = SoLuongTon + $qty
             WHERE MaSanPham = $pid"
        );
    }
}

$db->close();
header('Location: orders.php?cancelled=1');
exit();