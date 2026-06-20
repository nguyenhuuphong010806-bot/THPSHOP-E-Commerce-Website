-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th5 18, 2026 lúc 12:48 AM
-- Phiên bản máy phục vụ: 9.1.0
-- Phiên bản PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `web`
--
CREATE DATABASE IF NOT EXISTS `web` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `web`;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `idCart` int NOT NULL AUTO_INCREMENT,
  `MaSanPham` int NOT NULL,
  `ngayTao` date NOT NULL,
  `trangThai` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `TongTien` decimal(10,0) NOT NULL,
  `IdNguoiDung` int NOT NULL,
  PRIMARY KEY (`idCart`),
  KEY `fk_giohang` (`IdNguoiDung`),
  KEY `fk_card_sp` (`MaSanPham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `MaDanhMuc` int NOT NULL AUTO_INCREMENT,
  `TenDanhMuc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MoTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`MaDanhMuc`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`MaDanhMuc`, `TenDanhMuc`, `MoTa`) VALUES
(1, 'Quần', 'Quần là trang phục mặc ở phần dưới cơ thể, dùng để che và bảo vệ chân, đồng thời tạo phong cách thời trang.'),
(2, 'Áo', 'Áo là trang phục mặc ở phần trên cơ thể, giúp bảo vệ cơ thể và thể hiện phong cách cá nhân.'),
(3, 'Giày', 'Giày là sản phẩm dùng để bảo vệ bàn chân và hỗ trợ di chuyển.'),
(4, 'Phụ Kiện', 'Phụ kiện là các sản phẩm đi kèm với trang phục để làm nổi bật phong cách và tăng tính thẩm mỹ.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

DROP TABLE IF EXISTS `chitietdonhang`;
CREATE TABLE IF NOT EXISTS `chitietdonhang` (
  `MaChiTiet` int NOT NULL AUTO_INCREMENT,
  `MaDonHang` int NOT NULL,
  `MaSanPham` int NOT NULL,
  `PhanLoai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SoLuong` int NOT NULL,
  `Gia` decimal(10,0) NOT NULL,
  PRIMARY KEY (`MaChiTiet`),
  KEY `fk_ctdh_sanpham` (`MaSanPham`),
  KEY `fk_ctdh_donhang` (`MaDonHang`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietdonhang`
--

INSERT INTO `chitietdonhang` (`MaChiTiet`, `MaDonHang`, `MaSanPham`, `PhanLoai`, `SoLuong`, `Gia`) VALUES
(1, 1, 2, 'Mặc định', 1, 50000),
(2, 2, 6, 'Mặc định', 2, 100000),
(3, 2, 2, 'Mặc định', 2, 50000),
(4, 2, 3, 'Mặc định', 1, 150000),
(5, 3, 2, 'Mặc định', 1, 50000),
(6, 3, 1, 'Mặc định', 1, 100000),
(7, 4, 4, 'Mặc định', 1, 40000),
(8, 4, 3, 'Mặc định', 1, 150000),
(9, 4, 2, 'Mặc định', 1, 50000),
(10, 4, 1, 'Mặc định', 1, 100000),
(11, 4, 6, 'Mặc định', 1, 100000),
(12, 5, 3, 'Mặc định', 1, 150000),
(13, 6, 2, 'Mặc định', 1, 50000),
(14, 7, 4, 'Mặc định', 2, 40000),
(15, 8, 2, 'Mặc định', 1, 50000),
(16, 8, 3, 'Mặc định', 36, 150000),
(17, 9, 6, 'Mặc định', 1, 100000),
(18, 10, 6, 'Mặc định', 1, 100000),
(19, 11, 2, 'Mặc định', 3, 50000),
(20, 11, 3, 'Mặc định', 1, 150000),
(21, 11, 4, 'Mặc định', 1, 40000),
(22, 11, 1, 'Mặc định', 1, 100000),
(23, 12, 4, 'Mặc định', 2, 40000),
(24, 12, 2, 'Mặc định', 18, 50000),
(26, 14, 12, 'Mặc định', 1, 420000),
(27, 15, 4, 'Mặc định', 1, 40000),
(28, 16, 9, 'Mặc định', 1, 550000),
(29, 17, 10, 'Mặc định', 1, 850000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupon`
--

DROP TABLE IF EXISTS `coupon`;
CREATE TABLE IF NOT EXISTS `coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `MaCoupon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `LoaiGiam` enum('percent','fixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent' COMMENT 'percent = % giảm, fixed = giảm tiền mặt',
  `GiaTri` decimal(10,0) NOT NULL COMMENT 'Giá trị giảm (% hoặc VNĐ)',
  `GiaTriToiThieu` decimal(10,0) NOT NULL DEFAULT '0' COMMENT 'Đơn tối thiểu để áp dụng',
  `NgayBatDau` datetime DEFAULT CURRENT_TIMESTAMP,
  `NgayHetHan` datetime NOT NULL,
  `SoLanToiDa` int DEFAULT NULL COMMENT 'NULL = không giới hạn',
  `DaDung` int NOT NULL DEFAULT '0',
  `MoTa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT '1' COMMENT '1: Kích hoạt, 0: Khóa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `MaCoupon` (`MaCoupon`),
  KEY `idx_coupon_code` (`MaCoupon`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `coupon`
--

INSERT INTO `coupon` (`id`, `MaCoupon`, `LoaiGiam`, `GiaTri`, `GiaTriToiThieu`, `NgayBatDau`, `NgayHetHan`, `SoLanToiDa`, `DaDung`, `MoTa`, `TrangThai`) VALUES
(1, 'TTPWELCOME', 'percent', 10, 0, '2026-05-11 09:34:37', '2027-12-31 23:59:59', NULL, 0, 'Giảm 10% cho mọi đơn hàng', 1),
(2, 'FREESHIP', 'fixed', 30000, 0, '2026-05-11 09:34:37', '2027-12-31 23:59:59', NULL, 0, 'Miễn phí vận chuyển', 1),
(3, 'GIAM50K', 'fixed', 50000, 300000, '2026-05-11 09:34:37', '2027-12-31 23:59:59', 500, 0, 'Giảm 50.000₫ cho đơn từ 300.000₫', 1),
(4, 'SALE20', 'percent', 20, 200000, '2026-05-11 09:34:37', '2027-06-30 23:59:59', 100, 0, 'Giảm 20% cho đơn từ 200.000₫', 1),
(5, 'DOMIXI', 'fixed', 120000, 1, '2026-05-05 14:42:00', '2027-01-12 14:42:00', 1, 0, 'Nà ná na na Anh Phùng Thanh Độ', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

DROP TABLE IF EXISTS `donhang`;
CREATE TABLE IF NOT EXISTS `donhang` (
  `MaDonHang` int NOT NULL AUTO_INCREMENT,
  `IdNguoiDung` int NOT NULL,
  `TenNguoiNhan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SoDienThoai` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `diachi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `TongTien` decimal(10,0) NOT NULL,
  `trangThai` int NOT NULL DEFAULT '0' COMMENT '0:Đã đặt | 1:Đóng gói | 2:Đang giao | 3:Hoàn thành | 4:Đã hủy',
  `NgayDat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `MaPhuongThuc` int NOT NULL DEFAULT '1',
  `TrangThaiThanhToan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Chưa thanh toán',
  `CouponCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SoTienGiam` decimal(10,0) NOT NULL DEFAULT '0',
  `ThoiGianXacNhan` datetime DEFAULT NULL,
  PRIMARY KEY (`MaDonHang`),
  KEY `fk_donhang_user` (`IdNguoiDung`),
  KEY `fk_donhang_pttt` (`MaPhuongThuc`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`MaDonHang`, `IdNguoiDung`, `TenNguoiNhan`, `SoDienThoai`, `diachi`, `TongTien`, `trangThai`, `NgayDat`, `MaPhuongThuc`, `TrangThaiThanhToan`, `CouponCode`, `SoTienGiam`, `ThoiGianXacNhan`) VALUES
(1, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 80000, 3, '2026-03-19 00:33:00', 1, 'Chưa thanh toán', NULL, 0, NULL),
(2, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 480000, 3, '2026-04-07 12:24:29', 1, 'Chưa thanh toán', NULL, 0, '2026-05-11 19:48:05'),
(3, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 180000, 3, '2026-04-07 12:32:08', 1, 'Chưa thanh toán', NULL, 0, NULL),
(4, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 470000, 3, '2026-04-07 12:37:07', 1, 'Chưa thanh toán', NULL, 0, NULL),
(5, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 180000, 3, '2026-04-09 02:02:33', 2, 'Chưa thanh toán', NULL, 0, NULL),
(6, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 80000, 3, '2026-04-09 02:36:03', 1, 'Chưa thanh toán', NULL, 0, NULL),
(7, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 110000, 3, '2026-04-09 03:15:56', 3, 'Chưa thanh toán', NULL, 0, '2026-05-11 23:50:35'),
(8, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 5480000, 3, '2026-04-11 03:12:44', 1, 'Chưa thanh toán', NULL, 0, '2026-05-11 19:28:11'),
(9, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 130000, 3, '2026-04-11 08:24:39', 1, 'Chưa thanh toán', NULL, 0, NULL),
(10, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 130000, 3, '2026-04-11 09:07:39', 3, 'Chưa thanh toán', NULL, 0, '2026-05-11 21:24:33'),
(11, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 470000, 3, '2026-04-13 00:07:45', 3, 'Chưa thanh toán', NULL, 0, NULL),
(12, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng - cao Bằng', 1010000, 3, '2026-04-20 03:41:37', 3, 'Chưa thanh toán', NULL, 0, NULL),
(14, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng - cao Bằng', 450000, 4, '2026-05-11 11:22:18', 3, 'Chưa thanh toán', NULL, 0, NULL),
(15, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng - cao Bằng', 70000, 3, '2026-05-11 11:24:27', 3, 'Chưa thanh toán', NULL, 0, NULL),
(16, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng - cao Bằng', 580000, 3, '2026-05-11 20:47:01', 3, 'Chưa thanh toán', NULL, 0, '2026-05-11 21:24:40'),
(17, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng - cao Bằng', 880000, 3, '2026-05-11 21:25:15', 3, 'Chưa thanh toán', NULL, 0, '2026-05-11 21:25:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phuong_thuc_thanh_toan`
--

DROP TABLE IF EXISTS `phuong_thuc_thanh_toan`;
CREATE TABLE IF NOT EXISTS `phuong_thuc_thanh_toan` (
  `MaPhuongThuc` int NOT NULL AUTO_INCREMENT,
  `TenPhuongThuc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `trangThai` int DEFAULT '1',
  PRIMARY KEY (`MaPhuongThuc`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phuong_thuc_thanh_toan`
--

INSERT INTO `phuong_thuc_thanh_toan` (`MaPhuongThuc`, `TenPhuongThuc`, `mota`, `trangThai`) VALUES
(1, 'Thanh toán khi nhận hàng (COD)', 'Trả tiền mặt cho shipper', 1),
(2, 'Thanh toán qua Ví MoMo', 'Quét mã QR an toàn', 1),
(3, 'Thanh toán bằng Thẻ Ngân hàng / VNPay', 'Thanh toán online tiện lợi', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `MaSanPham` int NOT NULL AUTO_INCREMENT,
  `TenSanPham` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `GiaSanPham` decimal(10,0) NOT NULL,
  `hinh` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MoTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MaDanhMuc` int NOT NULL,
  `SaoTrungBinh` float NOT NULL,
  `TongDanhGia` int NOT NULL,
  `hinh2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `hinh3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `SoLuongTon` int NOT NULL DEFAULT '99',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = Sản phẩm nổi bật',
  `GiaKhuyenMai` decimal(10,0) DEFAULT NULL,
  `NgayBatDauSale` datetime DEFAULT NULL,
  `NgayKetThucSale` datetime DEFAULT NULL,
  `is_suggested` tinyint(1) DEFAULT '0',
  `DaBan` int DEFAULT '0',
  PRIMARY KEY (`MaSanPham`),
  KEY `fk_sp_dm` (`MaDanhMuc`),
  KEY `idx_ten_sp` (`TenSanPham`(50))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `product`
--

INSERT INTO `product` (`MaSanPham`, `TenSanPham`, `GiaSanPham`, `hinh`, `MoTa`, `MaDanhMuc`, `SaoTrungBinh`, `TongDanhGia`, `hinh2`, `hinh3`, `mau1`, `mau2`, `mau3`, `size`, `SoLuongTon`, `is_featured`, `GiaKhuyenMai`, `NgayBatDauSale`, `NgayKetThucSale`, `is_suggested`, `DaBan`) VALUES
(1, 'Quần Short Jeans Denim Rách', 100000, 'quan.jpg', 'Quần Short Jeans Denim - Thiết Kế Rách Cá Tính - Chất Denim Co Giãn - Bền Màu & Bụi Bặm\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Quần Short Jeans nam nữ rách phong cách Streetwear\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Denim cotton 100% dày dặn, thấm hút mồ hôi tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size S: 40-50kg\r\n???? Size M: 50-60kg\r\n???? Size L: 60-70kg\r\n???? Size XL: 70-80kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Chi tiết rách (distressed) được làm thủ công, tạo vẻ ngoài bụi bặm và phá cách\r\n✅ Màu xanh Denim sáng trẻ trung, cực kỳ dễ phối cùng các loại giày Sneaker\r\n✅ Sợi vải được xử lý chống co rút, không bị biến dạng sau nhiều lần giặt\r\n✅ Gấu quần cắt lai tua rua tạo điểm nhấn thời trang cực chất cho mùa hè\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Nên giặt bằng nước lạnh trong vài lần đầu để giữ màu Indigo đặc trưng\r\n✅ Lộn trái sản phẩm khi giặt máy để bảo vệ các chi tiết rách\r\n✅ Không phơi trực tiếp dưới nắng gắt để tránh làm cứng vải\r\n\r\n???? Sản phẩm \"Must-have\" cho những chuyến du lịch và dạo phố! ????', 1, 2, 3, NULL, NULL, NULL, NULL, NULL, NULL, 99, 0, NULL, NULL, NULL, 0, 0),
(2, 'Áo thun tay lỡ form rộng phong cách Hàn Quốc', 50000, '1773689314_1_ao.jpg', 'Áo Thun Tay Lỡ Unisex - Form Rộng Oversize - Chất Cotton Co Giãn 4 Chiều - Phong Cách Hàn Quốc Trẻ Trung\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Áo thun tay lỡ form rộng nam nữ, áo phông Oversize phong cách Ulzzang\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Cotton 100% cao cấp, mềm mịn, thấm hút mồ hôi cực tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size M: 1m50 - 1m65; 45-55kg\r\n???? Size L: 1m60 - 1m75; 55-65kg\r\n???? Size XL: 1m70 - 1m80; 65-75kg\r\n???? Size XXL: 1m75 - 1m85; 75-85kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Thiết kế form rộng thoải mái, tay lỡ năng động, phù hợp cho cả nam và nữ (Unisex)\r\n✅ Chất vải cotton dày dặn, không xù lông, không nhão sau khi giặt\r\n✅ Kiểu dáng basic dễ phối đồ, có thể kết hợp cùng quần jean, kaki hay quần short đều cực đẹp\r\n✅ Đường may móc xích tỉ mỉ, cổ áo bo thun dày dặn không bị giãn\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Giặt tay hoặc giặt máy đều được, nên lộn trái áo khi giặt\r\n✅ Không đổ trực tiếp thuốc tẩy lên hình in hoặc bề mặt vải\r\n✅ Phơi ở nơi thoáng mát, tránh ánh nắng gắt để giữ màu áo bền lâu\r\n\r\n???? Mua ngay để sở hữu chiếc áo thun \"quốc dân\" chuẩn style Hàn Quốc này! ????', 2, 4.2, 6, '1773689314_2_aotrang.jpg', '1773689314_3_aoxanh.jpg', 'Đen', 'Trắng', 'Xanh', 'S,M,L,XL,XXL', 100, 1, NULL, NULL, NULL, 0, 0),
(3, 'Giày Sneaker Retro Multi-Color', 150000, 'giay.jpg', 'Giày Sneaker Nam Nữ Retro Chunky - Phối Màu Đa Sắc Ấn Tượng - Đế Cao Hack Dáng - Phong Cách Streetwear\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Giày Sneaker Chunky phối màu Retro (Cam/Xanh/Tím)\r\n???? Xuất xứ: Hàng nhập khẩu/Việt Nam\r\n???? Chất liệu: Da PU phối lưới thoáng khí, đế cao su đúc nguyên khối\r\n\r\n???? Bảng size: 36 - 37 - 38 - 39 - 40 - 41 - 42 - 43 - 44\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Thiết kế phối màu đa sắc cực chất, tạo điểm nhấn cá tính cho mọi outfit\r\n✅ Đế cao 4-5cm, giúp hack chiều cao một cách tự nhiên\r\n✅ Lớp lót trong êm ái, thoáng khí, không gây bí chân khi vận động cả ngày\r\n✅ Đế cao su có rãnh chống trượt, độ bền cao và bám dính tốt\r\n\r\n???? HƯỚNG DẪN BẢO QUẢN ????\r\n✅ Hạn chế tiếp xúc trực tiếp với nước trong thời gian dài\r\n✅ Vệ sinh bằng khăn ẩm hoặc dung dịch vệ sinh giày chuyên dụng\r\n✅ Tránh phơi trực tiếp dưới ánh nắng gắt\r\n\r\n???? Sắm ngay đôi Sneaker \"quốc dân\" để nâng tầm phong cách của bạn! ????', 3, 5, 1, '', '', '', '', '', '36,37,38,39,40,41,42,43,44', 99, 1, NULL, NULL, NULL, 0, 0),
(4, 'Túi Đeo Chéo Nữ Họa Tiết Wonder', 40000, 'phukien.jpg', 'Túi Xách Nữ Đeo Chéo Dây Xích - Họa Tiết Chữ Wonder Cá Tính - Da Cao Cấp - Sang Trọng & Năng Động\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Túi đeo chéo Wonder Box Bag\r\n???? Chất liệu: Da PU cao cấp chống thấm nước nhẹ\r\n⛓️ Phụ kiện: Dây xích kim loại mạ bạc sáng bóng\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Họa tiết chữ Wonder dập nổi hiện đại, mang đậm hơi thở thời trang đường phố\r\n✅ Form túi cứng cáp, chuẩn dáng, không bị mất form sau thời gian dài sử dụng\r\n✅ Ngăn chứa rộng rãi, thoải mái đựng điện thoại, ví tiền và mỹ phẩm\r\n✅ Dây xích linh hoạt, có thể đeo chéo hoặc đeo vai tùy ý\r\n\r\n???? HƯỚNG DẪN BẢO QUẢN ????\r\n✅ Dùng khăn mềm lau sạch khi bám bẩn\r\n✅ Tránh để túi ở nơi ẩm ướt hoặc nhiệt độ quá cao\r\n✅ Không dùng chất tẩy rửa mạnh để vệ sinh bề mặt da\r\n\r\n???? Phụ kiện không thể thiếu cho các nàng sành điệu - Chốt đơn ngay! ????', 4, 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, 99, 0, NULL, NULL, NULL, 0, 1),
(6, 'Quần Kaki Ống Đứng', 100000, 'quan3.jpg', 'Quần Kaki Ống Đứng - Thiết kế Tối Giản Thời Thượng - Chất Vải Bền Đẹp - Form Suông Thanh Lịch\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Quần kaki ống đứng, màu xanh Olive basic phong cách Casual\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Kaki Cotton cao cấp, giữ form tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size M: 1m55 - 1m65; 45-55kg\r\n???? Size L: 1m60 - 1m70; 55-65kg\r\n???? Size XL: 1m65 - 1m75; 65-75kg\r\n???? Size XXL: 1m70 - 1m80; 75-85kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Quần kaki vải dày dặn, bề mặt vải mịn, không xù lông, ít nhăn\r\n✅ Thiết kế ống đứng che khuyết điểm, tạo cảm giác chân dài và thon gọn hơn\r\n✅ Màu xanh Olive dễ phối đồ, phù hợp đi học, đi làm hay đi chơi\r\n✅ Đường may tỉ mỉ, chắc chắn, túi quần sâu tiện lợi\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Giặt lần đầu với nước lạnh để giữ màu vải\r\n✅ Lộn trái khi giặt và phơi để tránh phai màu trực tiếp dưới ánh nắng\r\n✅ Phù hợp cả giặt máy & giặt tay\r\n\r\n???? Mua ngay để sở hữu chiếc quần kaki thanh lịch cực HOT này! ????', 1, 5, 1, '', '', '', '', '', '', 10, 1, NULL, NULL, NULL, 0, 0),
(8, 'Áo Thun Polo Classic Cotton', 350000, '1778470411_1_shopping (3).webp', 'Áo Polo chất liệu cotton cá sấu co giãn 4 chiều, thấm hút mồ hôi tốt, phù hợp đi làm và đi chơi.', 2, 4.8, 150, NULL, NULL, 'Trắng', NULL, NULL, 'M, L, XL, XXL', 120, 1, 290000, '2026-05-10 00:00:00', '2026-05-20 23:59:59', 1, 450),
(9, 'Quần Jean Slim Fit Co Giãn', 550000, '1778470479_1_shopping (4).webp', 'Quần Jean nam dáng Slim Fit hiện đại, chất denim dày dặn nhưng vẫn đảm bảo sự thoải mái khi vận động.', 1, 4.5, 85, NULL, NULL, 'Xanh Đậm', NULL, NULL, '29, 30, 31, 32, 34', 90, 1, 499000, '2026-05-11 08:00:00', '2026-05-12 23:59:59', 0, 211),
(10, 'Giày Sneaker Streetwear Trắng', 850000, '1778470623_1_shopping (5).webp', 'Giày Sneaker trắng basic, dễ phối đồ, đế cao su đúc nguyên khối chống trơn trượt hiệu quả.', 3, 5, 42, NULL, NULL, 'Trắng', NULL, NULL, '38, 39, 40, 41, 42, 43', 50, 1, 650000, '2026-05-11 00:00:00', '2026-05-15 00:00:00', 1, 121),
(11, 'Thắt Lưng Da Bò Khóa Tự Động', 450000, '1778470607_1_tải xuống (2).jfif', 'Thắt lưng da bò thật 100%, mặt khóa hợp kim không gỉ, thiết kế khóa tự động tiện lợi.', 4, 4.7, 110, NULL, NULL, 'Nâu', NULL, NULL, 'Freesize', 200, 0, NULL, NULL, NULL, 0, 340),
(12, 'Áo Sơ Mi Trắng Công Sở', 420000, '1778470534_1_tải xuống.webp', 'Áo sơ mi vải sợi tre (bamboo) chống nhăn, mát lịm, form dáng chuẩn cho dân văn phòng.', 2, 4.6, 65, NULL, NULL, 'Trắng', NULL, NULL, 'S, M, L, XL', 81, 0, 380000, '2026-05-01 00:00:00', '2026-05-31 23:59:59', 1, 185),
(13, 'Quần Kaki Ống Suông Korea', 380000, '1778470330_1_8453b68a68de0186bce7dd2bc4d87aef.jpeg', 'Quần kaki ống suông phong cách Hàn Quốc, vải mềm không xù lông, phù hợp cho cả nam và nữ.', 1, 4.4, 30, NULL, NULL, 'Be', NULL, NULL, 'M, L, XL', 150, 1, NULL, NULL, NULL, 0, 55),
(14, 'Giày Tây Da Bóng Derby', 1250000, '1778470272_1_shopping (2).webp', 'Giày tây Derby lịch lãm, da bò bóng cao cấp, phù hợp cho các buổi tiệc và sự kiện quan trọng.', 3, 4.9, 15, NULL, NULL, 'Đen', NULL, NULL, '39, 40, 41, 42', 30, 1, 990000, '2026-05-11 10:00:00', '2026-05-11 22:00:00', 0, 12),
(15, 'Mũ Lưỡi Trai Kaki Thêu Chữ', 150000, '1778470230_1_shopping (1).webp', 'Mũ lưỡi trai vải kaki dày dặn, thêu họa tiết nổi bật, khóa kim loại điều chỉnh kích thước dễ dàng.', 4, 4.2, 200, NULL, NULL, 'Đen', NULL, NULL, 'Freesize', 500, 0, 99000, '2026-05-11 00:00:00', '2026-05-12 00:00:00', 1, 1200),
(16, 'Áo Hoodie Oversize Nỉ Bông', 480000, '1778470164_1_ao.webp', 'Áo hoodie chất nỉ bông dày dặn, form rộng chuẩn Streetwear, giữ ấm cực tốt cho mùa đông.', 2, 4.7, 95, NULL, NULL, 'Xám', NULL, NULL, 'L, XL, XXL', 60, 1, NULL, NULL, NULL, 1, 310),
(17, 'Quần Short Thể Thao Pro-Dry', 220000, '1778470136_1_shopping.webp', 'Quần short tập gym, chạy bộ với công nghệ thoát ẩm Pro-Dry, nhẹ và siêu thoáng mát.', 1, 4.5, 140, NULL, NULL, 'Đen', NULL, NULL, 'M, L, XL', 300, 0, 150000, '2026-05-11 00:00:00', '2026-05-25 00:00:00', 1, 850),
(18, 'Dép Sandal Quai Ngang Unisex', 280000, '1778468641_1_irene-kredenets-DDqxX0-7vKE-unsplash.jpg', 'Sandal quai ngang chắc chắn, đế cao su mềm mại, phù hợp đi mưa và dạo phố hàng ngày.', 3, 4.3, 50, NULL, NULL, 'Đen', NULL, NULL, '36, 37, 38, 39, 40, 41, 42, 43', 110, 0, NULL, NULL, NULL, 1, 230),
(19, 'Túi Tote Vải Canvas In Hình', 50000, '1778470062_1_tải xuống (1).jfif', 'Túi tote vải canvas bền đẹp, in hình sắc nét, ngăn chứa rộng rãi đựng vừa laptop 14 inch.', 4, 4.8, 320, NULL, NULL, 'Trắng', NULL, NULL, 'Freesize', 200, 0, 89000, '2026-05-11 00:00:00', '2026-05-12 00:00:00', 1, 1500),
(20, 'Quần Kaki ', 200000, '1779034264_1_ao3lo.webp', 'Áo Ba Lỗ \r\n\r\nÁo Ba Lỗ được sản xuất bằng vài cotton, thiết kế đặc biệt để cơ thể thoải mái nhất, Rất phù hợp cho các bạn nam khi mặc ở nhà hoặc mặc làm áo lót bên trong giúp thấm hút mồ hôi, với một mầu trắng đơn giản nên các bạn nam khi sử dụng rất dễ kết hợp với các áo khác bên ngoài.\r\n\r\n- Áo Ba Lỗ  kiểu dáng khỏe khoắn, nam tính, co giãn cực tốt, mang lại cảm giác thoải mái, Sản phẩm Áo Ba Lỗ  được may kỹ, bền và đẹp, giúp bạn sử dụng trong thời gian dài \r\n\r\n-Chất liệu: - Áo Ba Lỗ  Chất liệu 100 % cotton ( vải bông) đem đến sự mềm mại, thoáng khí, thấm hút mổ hôi phải chăng, giúp bạn lúc mặc sẽ luôn cảm thấy thỏa mái và tự tin.. Sờ vào mềm, mịn - Chất liệu Cotton thấm hút, co giãn tốt, mát. Chất đẹp, mát tạo cảm giác mát mẻ cho các quý ông. - Độ thấm hút cực tốt an toàn cho sức khoẻ -. Độ co giãn rất tốt - Đường kim mũi chỉ may cẩn thận \r\n\r\n', 2, 0, 0, NULL, NULL, 'Trắng', NULL, NULL, '', 99, 1, NULL, NULL, NULL, 0, 0),
(21, 'Áo Sweater The Moon nỉ bông Black Box kiểu Mỹ basic form rộng màu đen, xám tiêu, xanh than, đỏ đô un', 300000, '1779034402_1_áo sweated.webp', 'Áo sweater The Moon Black Box được sản xuất theo tiêu chuẩn quốc tế, sử dụng chất liệu nỉ bông cao cấp, dày dặn, mềm mại và ấm áp, mang lại cảm giác dễ chịu, thoải mái khi mặc. Phần bo chun ở tay áo và gấu áo chắc chắn giúp áo giữ phom lâu dài, ôm vừa vặn và hạn chế bai dão trong quá trình sử dụng.\r\n\r\n\r\n\r\nVới chất liệu cao cấp kết hợp cùng đường may tỉ mỉ, chiếc sweater này có phom dáng chuẩn, giữ đúng form như hình quảng cáo, đảm bảo bền đẹp và khác biệt khi mặc. Thiết kế mang phong cách basic kiểu Mỹ – tối giản, tinh tế và dễ phối đồ, phù hợp với nhiều phong cách thời trang khác nhau.\r\n\r\n\r\n\r\nSản phẩm chính hãng Black Box mang tinh thần local brand hiện đại, pha chút vibe retro – streetwear đậm chất Âu Mỹ. Là lựa chọn hoàn hảo cho cả nam và nữ yêu thích phong cách năng động, cá tính nhưng vẫn giữ sự gọn gàng và thanh lịch.\r\n\r\n\r\n\r\n???? Thông tin chi tiết:\r\n\r\nChất liệu: Nỉ bông cao cấp, mềm mại, dày, ấm áp\r\n\r\nForm: Rộng, unisex, basic kiểu Mỹ\r\n\r\nMàu sắc: Đen,  xám tiêu, xanh than, đỏ đô\r\n\r\nTay & thân áo: Bo chun chắc chắn, giữ form bền đẹp\r\n\r\n\r\n\r\n???? Hướng dẫn bảo quản:\r\n\r\nGiặt áo sau 3–4 ngày sử dụng để giữ độ mềm và màu sắc.\r\n\r\nKhông ngâm áo quá lâu, tránh dùng chất tẩy mạnh.\r\n\r\nLộn trái áo khi giặt, đặc biệt trong những lần đầu tiên.\r\n\r\nPhơi áo nơi thoáng mát, tránh nắng gắt để giữ form và hình in bền đẹp.\r\n\r\n\r\n\r\n???? Cam kết từ Black Box:\r\n\r\nSản phẩm thật 100%, đúng như hình và mô tả\r\n\r\nĐường may và chất liệu đạt tiêu chuẩn cao, đảm bảo bền đẹp lâu dài\r\n\r\nHỗ trợ đổi hàng trong 15 ngày nếu sản phẩm bị lỗi do vận chuyển hoặc nhà sản xuất', 2, 0, 0, NULL, NULL, 'Đen', NULL, NULL, 'S,M,L,XL,XXL', 99, 1, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review`
--

DROP TABLE IF EXISTS `review`;
CREATE TABLE IF NOT EXISTS `review` (
  `id` int NOT NULL AUTO_INCREMENT,
  `MaSanPham` int NOT NULL,
  `IdNguoiDung` int NOT NULL,
  `NoiDung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `SoSao` int NOT NULL,
  `NgayBinhLuan` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rv_sp` (`MaSanPham`),
  KEY `fk_rv_nd` (`IdNguoiDung`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `review`
--

INSERT INTO `review` (`id`, `MaSanPham`, `IdNguoiDung`, `NoiDung`, `SoSao`, `NgayBinhLuan`) VALUES
(1, 2, 6, 'quá đẹp', 5, '2026-03-12 15:20:15'),
(2, 2, 1, 'đẹp', 5, '2026-03-16 20:33:11'),
(3, 3, 1, 'đẹp điên luôn :))', 5, '2026-03-16 20:40:24'),
(4, 6, 1, 'đẹp', 5, '2026-03-17 06:03:11'),
(5, 2, 8, 'cũng cũng :v', 4, '2026-03-17 19:48:10'),
(6, 2, 1, '', 5, '2026-04-11 01:49:57'),
(7, 1, 1, '', 2, '2026-04-20 00:46:50'),
(8, 1, 1, '', 2, '2026-04-20 00:47:06'),
(9, 1, 1, '', 2, '2026-04-20 00:47:15'),
(10, 4, 1, 'đẹp', 3, '2026-04-20 20:42:06'),
(11, 4, 1, 'cũng tạm', 3, '2026-04-20 20:42:41'),
(12, 2, 1, '', 5, '2026-04-20 20:49:36'),
(13, 2, 1, '', 1, '2026-04-20 20:49:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `IdNguoiDung` int NOT NULL AUTO_INCREMENT,
  `TenNguoiDung` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `matkhau` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `quyen` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'user',
  `diachi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `SoDienThoai` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `GioiTinh` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'male',
  `NgaySinh` date DEFAULT NULL,
  `AnhDaiDien` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '''default.png''',
  `trangThai` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1: Hoạt động, 0: Bị khóa',
  PRIMARY KEY (`IdNguoiDung`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `user`
--

INSERT INTO `user` (`IdNguoiDung`, `TenNguoiDung`, `email`, `matkhau`, `quyen`, `diachi`, `SoDienThoai`, `GioiTinh`, `NgaySinh`, `AnhDaiDien`, `trangThai`) VALUES
(1, 'Ngô Bá Thắng', 'nguyenvana@gmail.com', '$2y$10$fqcRwWNfQ5ZM2cP41UXjpeRGPffR5h5n8ZOXkSy6HgFII1FHj.pUW', 'admin', '120 uyên lãng - cao Bằng', '0123456789', 'male', NULL, '1775869416_avatar_1.webp', 1),
(2, 'nguyễn văn b', 'nguyenvanb@gmail.com', '123', 'user', '123 Thủ đức', '', 'male', NULL, '\'default.png\'', 1),
(3, 'ngô bá thắng', 'ngobathang@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 1),
(4, 'độ mixi', 'mixi@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 1),
(5, 'Trần Cao Trọng', 'trancaotrong@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 1),
(6, 'ngobathang', 'trong@mail.com', '123', 'user', 'đường 123- thủ đức,tp.hcm', '', 'male', NULL, '\'default.png\'', 1),
(7, 'Huynhabc', 'abc@abc', '123', 'user', 'đường 123- thủ đức,tp.hcm', '', 'male', NULL, '\'default.png\'', 1),
(8, 'Trần Cao Trọng', 'trancaotrong040506@gmail.com', '$2y$10$UUAdr4bpWtKy6UY/cu1c5O.P/9b0KL2gTA1sIO.M6TUriZwNFX6EW', 'user', 'đường ABC- thủ đức,tp.hcm', '0123456789', 'male', '2006-05-04', '1773776859_avatar_8.png', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id_yeuthich` int NOT NULL AUTO_INCREMENT,
  `IdNguoiDung` int NOT NULL,
  `MaSanPham` int NOT NULL,
  `NgayThem` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_yeuthich`),
  UNIQUE KEY `IdNguoiDung` (`IdNguoiDung`,`MaSanPham`),
  KEY `fk_wishlist_products` (`MaSanPham`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wishlist`
--

INSERT INTO `wishlist` (`id_yeuthich`, `IdNguoiDung`, `MaSanPham`, `NgayThem`) VALUES
(8, 8, 2, '2026-03-16 16:41:46'),
(252, 1, 2, '2026-04-20 00:39:47'),
(255, 1, 4, '2026-04-20 20:37:32'),
(260, 1, 1, '2026-05-11 00:58:09'),
(264, 1, 3, '2026-05-11 02:15:03');

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD CONSTRAINT `fk_ctdh_donhang` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_ctdh_sanpham` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `fk_donhang_pttt` FOREIGN KEY (`MaPhuongThuc`) REFERENCES `phuong_thuc_thanh_toan` (`MaPhuongThuc`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_donhang_user` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_sp_dm` FOREIGN KEY (`MaDanhMuc`) REFERENCES `categories` (`MaDanhMuc`);

--
-- Các ràng buộc cho bảng `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_rv_nd` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_rv_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_products` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
