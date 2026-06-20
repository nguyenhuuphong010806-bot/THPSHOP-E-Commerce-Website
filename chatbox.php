<?php
// 1. Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    return; 
}

// 2. LẤY TRỰC TIẾP DANH SÁCH SẢN PHẨM TỪ DATABASE
require_once 'database.php'; // Đảm bảo đường dẫn tới file database.php của bạn là đúng
$danh_sach_tu_dong = "Hiện tại shop đang cập nhật sản phẩm.";

try {
    $db = new Database();
    
    // Đã đổi 'KichThuoc' thành 'size' cho khớp chuẩn 100% với database của bạn!
    $sql = "SELECT TenSanPham, GiaSanPham, size FROM product LIMIT 50"; 
    $result = $db->select($sql);
    
    if ($result === false) {
        // Lỗi database (ẩn đi không cho AI đọc nữa)
    } 
    else if ($result->num_rows > 0) {
        $danh_sach_tu_dong = "";
        while($row = $result->fetch_assoc()) {
            $gia_formated = number_format($row['GiaSanPham'], 0, ',', '.');
            
            // Xử lý lấy cột 'size', nếu trống thì báo Freesize
            $kich_co = !empty($row['size']) ? $row['size'] : 'Freesize'; 
            
            $danh_sach_tu_dong .= "- " . $row['TenSanPham'] . " (Size: " . $kich_co . "): " . $gia_formated . "đ\n";
        }
    }
    $db->close();
} catch (Exception $e) {
    // Ẩn lỗi hệ thống
}
?>

<button id="chatToggleBtn"
    class="fixed bottom-6 right-6 bg-blue-600 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center hover:bg-blue-700 hover:scale-105 transition-all z-[100] outline-none">
    <i class="fas fa-comment-dots text-2xl"></i>
</button>

<div id="chatBox"
    class="fixed bottom-24 right-6 bg-white rounded-xl shadow-2xl overflow-hidden hidden z-[100] flex-col border border-gray-200 transition-all duration-300 w-[min(20rem,calc(100vw-2.5rem))] max-w-[calc(100vw-2.5rem)]"
    style="height: min(450px, calc(100vh - 8rem));">

    <div class="bg-blue-600 text-white p-3 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                <img src="./public/images/icon_web.png" alt="logo" class="w-6 h-6 object-contain rounded-full">
            </div>
            <div>
                <h3 class="font-bold text-sm">Chat với TTPSHOP</h3>
                <p class="text-[10px] text-blue-100 flex items-center gap-1"><span
                        class="w-2 h-2 bg-green-400 rounded-full inline-block"></span> Xin chào,
                    <?php echo $_SESSION['user_name']; ?></p>
            </div>
        </div>
        <button id="closeChatBtn" class="text-white hover:text-gray-200 focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div id="chatMessages" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-3">
        <div class="flex items-start gap-2">
            <div
                class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-[10px] flex-shrink-0 border border-blue-200">
                Shop</div>
            <div
                class="bg-white border border-gray-200 p-2 px-3 rounded-xl rounded-tl-none text-sm text-gray-700 shadow-sm max-w-[80%] ">
                Chào <?php echo $_SESSION['user_name']; ?>! Bạn cần tư vấn về sản phẩm nào của shop ạ?</div>
        </div>
    </div>

    <div class="p-3 border-t bg-white flex items-center gap-2">
        <input type="text" id="chatInput" placeholder="Nhập tin nhắn..."
            class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-blue-500 bg-gray-50 focus:bg-white transition-colors">
        <button id="sendMessageBtn"
            class="bg-blue-600 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors flex-shrink-0 outline-none">
            <i class="fas fa-paper-plane text-sm"></i>
        </button>
    </div>
</div>

<script>
const chatToggleBtn = document.getElementById('chatToggleBtn');
const chatBox = document.getElementById('chatBox');
const closeChatBtn = document.getElementById('closeChatBtn');
const chatInput = document.getElementById('chatInput');
const sendMessageBtn = document.getElementById('sendMessageBtn');
const chatMessages = document.getElementById('chatMessages');

if (chatToggleBtn) {
    chatToggleBtn.addEventListener('click', () => {
        chatBox.classList.remove('hidden');
        chatBox.classList.add('flex');
        chatToggleBtn.classList.add('scale-0');
        setTimeout(() => chatToggleBtn.classList.add('hidden'), 300);
    });

    closeChatBtn.addEventListener('click', () => {
        chatBox.classList.add('hidden');
        chatBox.classList.remove('flex');
        chatToggleBtn.classList.remove('hidden');
        setTimeout(() => chatToggleBtn.classList.remove('scale-0'), 10);
    });

    async function sendMessage() {
        const text = chatInput.value.trim();
        if (text === '') return;

        // 1. Hiển thị tin nhắn user
        const userMsgHtml = `
            <div class="flex items-start gap-2 justify-end">
                <div class="bg-blue-600 text-white p-2 px-3 rounded-xl rounded-tr-none text-sm shadow-sm max-w-[80%] whitespace-pre-wrap">${text}</div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', userMsgHtml);
        chatInput.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // 2. Hiển thị "Shop đang nhập..."
        const typingId = 'typing-' + Date.now();
        const typingHtml = `
            <div id="${typingId}" class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-[10px] flex-shrink-0 border border-blue-200">Shop</div>
                <div class="bg-gray-200 animate-pulse p-2 px-3 rounded-xl rounded-tl-none text-sm text-gray-500 shadow-sm">
                    Shop đang nhập...
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', typingHtml);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        try {
            // 3. NHẬP THỂ DANH SÁCH SẢN PHẨM TỪ PHP VÀO JS
            const danh_sach = <?php echo json_encode($danh_sach_tu_dong); ?>;

            // 4. Gọi Google AI
            const systemInstructionText =
                `Bạn là nhân viên tư vấn nhiệt tình của TTPSHOP. Tên khách hàng là: <?php echo $_SESSION['user_name']; ?>.
Xưng hô là 'Shop' và 'Bạn' (hoặc gọi tên khách). 

SẢN PHẨM CỦA SHOP HIỆN CÓ BAO GỒM:
${danh_sach}

CHÍNH SÁCH: Ship đồng giá 30k, freeship đơn từ 300k. Đổi trả trong 7 ngày.

LƯU Ý QUAN TRỌNG (PHẢI TUÂN THỦ NGHIÊM NGẶT): 
- Trả lời ngắn gọn, thân thiện, dễ thương.
- Tuyệt đối không bịa ra sản phẩm không có trong danh sách trên.
- Nếu khách hỏi sản phẩm không có, hãy xin lỗi và gợi ý các sản phẩm đang có.
- CHỈ TẬP TRUNG tư vấn đúng sản phẩm mà khách đang hỏi. TUYỆT ĐỐI KHÔNG tự động liệt kê hay tư vấn thêm các sản phẩm khác nếu khách không yêu cầu.
- Về kích cỡ: Chỉ được phép dựa vào thông tin size có trong danh sách sản phẩm. KHÔNG ĐƯỢC TỰ BỊA ra các size như S, M, L, XL, XXL nếu sản phẩm đó là Freesize.`;

            const apiKey = 'AIzaSyCCKSbH6YsSH5M40_lMJhy2ZxumzZOkV1E';

            const url =
                `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`;
            const requestData = {
                "systemInstruction": {
                    "parts": [{
                        "text": systemInstructionText
                    }]
                },
                "contents": [{
                    "parts": [{
                        "text": text
                    }]
                }]
            };

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            const typingElement = document.getElementById(typingId);
            if (typingElement) typingElement.remove();

            let replyText = "Xin lỗi, shop đang gặp chút sự cố mạng. Bạn nhắn lại giúp shop nha!";

            // XỬ LÝ KẾT QUẢ TỪ GOOGLE
            if (data.candidates && data.candidates[0].content.parts[0].text) {
                replyText = data.candidates[0].content.parts[0].text;
            } else if (data.error) {
                replyText = "Lỗi từ API Google: " + data.error.message;
            }

            const shopReplyHtml = `
                <div class="flex items-start gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-[10px] flex-shrink-0 border border-blue-200">Shop</div>
                    <div class="bg-white border border-gray-200 p-2 px-3 rounded-xl rounded-tl-none text-sm text-gray-700 shadow-sm max-w-[80%] whitespace-pre-wrap">${replyText}</div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', shopReplyHtml);
            chatMessages.scrollTop = chatMessages.scrollHeight;

        } catch (error) {
            const typingElement = document.getElementById(typingId);
            if (typingElement) typingElement.remove();

            const errorHtml = `
                <div class="flex items-start gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-[10px] flex-shrink-0 border border-blue-200">Shop</div>
                    <div class="bg-white border border-red-200 p-2 px-3 rounded-xl rounded-tl-none text-sm text-red-600 shadow-sm max-w-[80%]">Lỗi mạng! Bạn vui lòng thử lại nhé.</div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', errorHtml);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    sendMessageBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
}
</script>