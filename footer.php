<footer class="bg-gray-900 text-gray-300 pt-16 pb-8 border-t-4 border-blue-600 mt-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
            <div>
                <h3 class="text-2xl font-black text-blue-500 italic tracking-tighter mb-4">TTP<span
                        class="text-yellow-500">SHOP</span></h3>
                <p class="text-sm text-gray-400 leading-relaxed mb-6">Hệ thống mua sắm thời trang trực tuyến uy tín,
                    mang đến cho bạn những xu hướng mới nhất với chất lượng tuyệt vời.</p>
                <div class="flex gap-4">
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-blue-600 text-white transition"><i
                            class="fab fa-facebook-f"></i></a>
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-pink-600 text-white transition"><i
                            class="fab fa-instagram"></i></a>
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-red-600 text-white transition"><i
                            class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div>
                <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Hỗ Trợ Khách Hàng</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#" class="hover:text-blue-400 transition">Trung tâm trợ giúp</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Hướng dẫn mua hàng</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Chính sách vận chuyển</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Chính sách đổi trả</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Về TTP Shop</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#" class="hover:text-blue-400 transition">Giới thiệu</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Tuyển dụng</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Điều khoản bảo mật</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition">Liên hệ truyền thông</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-5 uppercase tracking-wide">Thanh Toán</h4>
                <div class="flex gap-2 flex-wrap">
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                            class="fab fa-cc-visa text-blue-800 text-xl"></i></div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                            class="fab fa-cc-mastercard text-red-600 text-xl"></i></div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center"><i
                            class="fab fa-cc-paypal text-blue-500 text-xl"></i></div>
                    <div
                        class="w-12 h-8 bg-gray-800 border border-gray-700 rounded flex items-center justify-center text-xs font-bold">
                        COD</div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
            <p>&copy; 2026 TTP Shop - Thắng, Trọng, Phong Shop - Đẳng cấp thời trang.</p>
        </div>
    </div>
</footer>
<script>
// Truyền trạng thái đăng nhập
const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

// Gom nhóm các dữ liệu của trang chi tiết sản phẩm thành 1 object
const productConfig = {
    id: <?php echo isset($id) ? $id : 'null'; ?>,
    defaultSize: '<?php echo (isset($has_sizes) && $has_sizes && count($size_array) > 0) ? htmlspecialchars($size_array[0]) : ""; ?>',
    defaultColor: '<?php echo isset($row['mau1']) && !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'MacDinh'; ?>'
};
</script>

<script src="public/js/main.js"></script>
<!-- <script src="public/js/effects.js"></script> thêm dòng này -->
<?php include 'chatbox.php'; ?>
<!-- include 'footer_scripts.php';  -->