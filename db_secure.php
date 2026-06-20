<?php
/**
 * db_secure.php — Prepared Statement helper cho TTP Shop
 * ============================================================
 * Include file này THAY THẾ hoặc SAU require_once "database.php"
 * trong các file PHP có nhận input từ user.
 *
 * Cách dùng:
 *   require_once "database.php";
 *   require_once "db_secure.php";
 *
 *   // SELECT trả về array các row:
 *   $rows = db_select($db, "SELECT * FROM product WHERE MaSanPham = ?", "i", $id);
 *
 *   // SELECT trả về 1 row:
 *   $row = db_select_one($db, "SELECT * FROM user WHERE email = ?", "s", $email);
 *
 *   // INSERT / UPDATE / DELETE trả về bool:
 *   $ok = db_execute($db, "UPDATE user SET matkhau = ? WHERE IdNguoiDung = ?", "si", $hash, $uid);
 *
 * Type codes (chuẩn MySQLi bind_param):
 *   i = integer, s = string, d = double, b = blob
 * ============================================================
 */

if (!function_exists('db_select')) {

    /**
     * SELECT nhiều row → trả về array kết quả (rỗng nếu không có)
     */
    function db_select($db, $sql, $types = '', ...$params) {
        if (empty($types) || empty($params)) {
            // Không có tham số — query tĩnh an toàn
            $res = $db->select($sql);
            $rows = [];
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) $rows[] = $row;
            }
            return $rows;
        }
        $stmt = $db->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * SELECT 1 row → trả về assoc array hoặc null
     */
    function db_select_one($db, $sql, $types = '', ...$params) {
        $rows = db_select($db, $sql, $types, ...$params);
        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * INSERT / UPDATE / DELETE → trả về bool
     */
    function db_execute($db, $sql, $types = '', ...$params) {
        if (empty($types) || empty($params)) {
            return $db->execute($sql);
        }
        $stmt = $db->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * INSERT → trả về insert_id
     */
    function db_insert($db, $sql, $types = '', ...$params) {
        if (db_execute($db, $sql, $types, ...$params)) {
            return $db->conn->insert_id;
        }
        return 0;
    }

    /**
     * Escape đầu vào LIKE để tránh wildcard injection
     * Dùng khi cần LIKE: $safe = db_escape_like($keyword);
     * Rồi dùng trong query: "WHERE ten LIKE ?"  với param = "%{$safe}%"
     */
    function db_escape_like($str) {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $str);
    }

    /**
     * Lấy user_id an toàn từ session (luôn integer)
     */
    function session_uid() {
        return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    }
}
