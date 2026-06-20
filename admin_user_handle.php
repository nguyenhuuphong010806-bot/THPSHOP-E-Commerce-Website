<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') exit("Access Denied");
require_once "database.php";
$db = new Database();

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) { header("Location: admin_users.php"); exit(); }

switch ($action) {
    case 'delete':
        // Xóa người dùng
        $db->select("DELETE FROM user WHERE IdNguoiDung = $id");
        break;

    case 'toggle_status':
        // Đảo ngược trạng thái: 1 -> 0, 0 -> 1
        $current_status = intval($_GET['status']);
        $new_status = ($current_status == 1) ? 0 : 1;
        $db->select("UPDATE user SET trangThai = $new_status WHERE IdNguoiDung = $id");
        break;

    case 'change_role':
        // Đảo quyền: admin -> user, user -> admin
        $current_role = $_GET['current'];
        $new_role = ($current_role == 'admin') ? 'user' : 'admin';
        $db->select("UPDATE user SET quyen = '$new_role' WHERE IdNguoiDung = $id");
        break;

    case 'reset_pw':
        // Đặt lại mật khẩu mặc định (giả sử là '123456')
        // Lưu ý: Nếu web bạn có mã hóa mật khẩu, hãy dùng password_hash ở đây
        $db->select("UPDATE user SET matkhau = '123456' WHERE IdNguoiDung = $id");
        break;
}

header("Location: admin_users.php?msg=success");
exit();