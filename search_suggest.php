<?php
// search_suggest.php — Trả về JSON sản phẩm gợi ý cho autocomplete
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once 'database.php';
$db = new Database();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$kw = $db->conn->real_escape_string($q);

$sql = "SELECT p.MaSanPham, p.TenSanPham, p.GiaSanPham, p.hinh,
               p.SaoTrungBinh, p.TongDanhGia, c.TenDanhMuc
        FROM product p
        LEFT JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc
        WHERE p.TenSanPham LIKE '%$kw%'
        ORDER BY p.TenSanPham ASC
        LIMIT 6";

$res = $db->select($sql);
$results = [];

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'id'       => (int)$row['MaSanPham'],
            'ten'      => $row['TenSanPham'],
            'gia'      => (int)$row['GiaSanPham'],
            'gia_fmt'  => number_format($row['GiaSanPham']) . '₫',
            'hinh'     => $row['hinh'],
            'sao'      => round((float)$row['SaoTrungBinh'], 1),
            'danh_muc' => $row['TenDanhMuc'],
        ];
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;
