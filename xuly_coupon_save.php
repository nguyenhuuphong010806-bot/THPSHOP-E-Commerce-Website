<?php
/**
 * xuly_coupon_save.php — AJAX toggle lưu/bỏ lưu mã giảm giá vào session
 * POST: code = MaCoupon
 * Response JSON: { ok, saved, msg }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'redirect'=>'login.php','msg'=>'Vui lòng đăng nhập.']);
    exit;
}

require_once 'database.php';
$db = new Database();

$code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
if (empty($code)) {
    echo json_encode(['ok'=>false,'msg'=>'Mã không hợp lệ.']);
    exit;
}

// Kiểm tra mã tồn tại & còn hiệu lực
$now = date('Y-m-d H:i:s');
$code_e = $db->conn->real_escape_string($code);
$res = $db->select(
    "SELECT MaCoupon FROM coupon
     WHERE MaCoupon = '$code_e'
       AND TrangThai = 1
       AND NgayBatDau <= '$now'
       AND NgayHetHan  > '$now'
       AND (SoLanToiDa IS NULL OR DaDung < SoLanToiDa)
     LIMIT 1"
);
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok'=>false,'msg'=>'Mã không hợp lệ hoặc đã hết hạn.']);
    exit;
}

// Toggle trong session
if (!isset($_SESSION['saved_coupons'])) $_SESSION['saved_coupons'] = [];

$saved = &$_SESSION['saved_coupons'];
$key   = array_search($code, $saved);

if ($key !== false) {
    // Đang lưu → bỏ lưu
    array_splice($saved, $key, 1);
    $saved = array_values($saved);
    echo json_encode(['ok'=>true,'saved'=>false,'msg'=>'Đã bỏ lưu mã.']);
} else {
    // Chưa lưu → lưu (tối đa 20 mã)
    if (count($saved) >= 20) {
        echo json_encode(['ok'=>false,'msg'=>'Bạn đã lưu tối đa 20 mã.']);
        exit;
    }
    $saved[] = $code;
    echo json_encode(['ok'=>true,'saved'=>true,'msg'=>'Đã lưu mã thành công.']);
}
exit;
