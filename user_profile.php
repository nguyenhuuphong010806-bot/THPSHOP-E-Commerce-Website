<?php
session_start();

// === 1. LOGIC TÍNH GIỎ HÀNG ===
$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += isset($item['soluong']) ? $item['soluong'] : 1;
    }
}

require_once "database.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// === 2. API ĐỔI MẬT KHẨU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'change_password') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    $current_pw = $data['current'] ?? '';
    $new_pw     = $data['new'] ?? '';

    // Giới hạn độ dài mật khẩu (tuỳ chỉnh theo yêu cầu)
    $min_pw = 6;
    $max_pw = 20;
    $new_pw_len = strlen($new_pw);

    if ($new_pw_len < $min_pw) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất '.$min_pw.' ký tự!']);
        exit();
    }
    if ($new_pw_len > $max_pw) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu mới không được vượt quá '.$max_pw.' ký tự!']);
        exit();
    }


    $check = $db->select("SELECT matkhau FROM user WHERE IdNguoiDung = $user_id");
    if ($check && $check->num_rows > 0) {
        $user      = $check->fetch_assoc();
        $stored_pw = $user['matkhau'];

        // Hỗ trợ cả hash lẫn plaintext cũ (tự nâng cấp nếu vẫn còn plaintext)
        $pw_ok = false;
        if (password_verify($current_pw, $stored_pw)) {
            $pw_ok = true;
        } elseif ($stored_pw === $current_pw) {
            $pw_ok = true; // tài khoản cũ chưa migrate
        }

        if (!$pw_ok) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng!']);
            exit();
        }

        // Hash mật khẩu mới trước khi lưu
        $hashed   = password_hash($new_pw, PASSWORD_DEFAULT);
        $hashed_e = $db->conn->real_escape_string($hashed);
        $update   = $db->execute("UPDATE user SET matkhau = '$hashed_e' WHERE IdNguoiDung = $user_id");

        if ($update) {
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống, không thể đổi mật khẩu.']);
        }
    }
    exit();
}

// === 3. API CẬP NHẬT HỒ SƠ & UPLOAD AVATAR ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'update_profile') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $gender = trim($data['gender'] ?? 'male');
    $dob = trim($data['dateOfBirth'] ?? '');

    // Giới hạn độ dài Họ & tên (để tránh quá ngắn/quá dài)
    $min_name = 3;
    $max_name = 50;
    $name_len = mb_strlen($name, 'UTF-8');
    if ($name_len < $min_name) {
        echo json_encode(['success' => false, 'message' => 'Tên quá ngắn! Tối thiểu '.$min_name.' ký tự.']);
        exit();
    }
    if ($name_len > $max_name) {
        echo json_encode(['success' => false, 'message' => 'Tên quá dài! Tối đa '.$max_name.' ký tự.']);
        exit();
    }

    // Giới hạn độ dài mật khẩu (áp dụng khi API đổi mật khẩu; phần update_profile không đổi mật khẩu)

    // Escape dữ liệu trước khi update DB
    $name = $db->conn->real_escape_string($name);
    $phone = $db->conn->real_escape_string($phone);
    $address = $db->conn->real_escape_string($address);
    $gender = $db->conn->real_escape_string($gender);
    $dob = $db->conn->real_escape_string($dob);



    
    $avatar_sql = "";
    if (isset($data['avatar']) && strpos($data['avatar'], 'data:image/') === 0) {
        $image_parts = explode(";base64,", $data['avatar']);
        if (count($image_parts) == 2) {
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1]; 
            $image_base64 = base64_decode($image_parts[1]);
            
            $file_name = time() . "_avatar_" . $user_id . '.' . $image_type;
            $file_path = "public/images/" . $file_name;
            
            if (file_put_contents($file_path, $image_base64)) {
                $avatar_sql = ", AnhDaiDien='$file_name'";
                $_SESSION['user_avatar'] = $file_name; 
            }
        }
    }

    $sql = "UPDATE user SET TenNguoiDung='$name', SoDienThoai='$phone', diachi='$address', GioiTinh='$gender', NgaySinh=" . (!empty($dob) ? "'$dob'" : "NULL") . " $avatar_sql WHERE IdNguoiDung = $user_id";
    
    if ($db->execute($sql)) {
        $_SESSION['user_name'] = $name;
        echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật hồ sơ.']);
    }
    exit();
}

// === 4. LẤY DỮ LIỆU HIỂN THỊ ===
$sql = "SELECT * FROM user WHERE IdNguoiDung = $user_id";
$result = $db->select($sql);
$user_data = $result->fetch_assoc();

$name = $user_data['TenNguoiDung'] ?? '';
$email = $user_data['email'] ?? '';
$phone = $user_data['SoDienThoai'] ?? '';
$gender = $user_data['GioiTinh'] ?? 'male'; 
$dob = $user_data['NgaySinh'] ?? '';
$address = $user_data['diachi'] ?? '';

// Fix lỗi avatar: Kiểm tra file tồn tại
$avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0D8ABC&color=fff&size=160';
if (!empty($user_data['AnhDaiDien']) && $user_data['AnhDaiDien'] != 'default.png') {
    $avatar_file = str_replace("'", "", $user_data['AnhDaiDien']);
    $avatar_path = 'public/images/' . $avatar_file;
    if (file_exists($avatar_path)) {
        $avatar = $avatar_path;
    }
}

$userDataJSON = json_encode([
    'name' => $name, 'email' => $email, 'phone' => $phone,
    'gender' => $gender, 'dateOfBirth' => $dob, 'address' => $address, 'avatar' => $avatar
]);

// === INCLUDE HEADER ===
require_once 'header.php';
?>


<title>TTPS - Thông Tin Người Dùng</title>

<div class="container mx-auto max-w-4xl px-4 mt-6">
    <a href="index.php"
        class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-medium transition text-sm">
        <i class="fas fa-arrow-left"></i> Về trang chủ
    </a>
</div>

<div id="root" class="flex-grow w-full"></div>

<script>
window.USER_DATA = <?php echo $userDataJSON; ?>;

window.showToast = (message, type = 'success') => {
    Toast.fire({
        icon: type === 'error' ? 'error' : 'success',
        title: message
    });
};
</script>

<script type="text/babel">
    const { useState } = React;
    const initialUserData = window.USER_DATA;

    function PasswordDialog({ isOpen, onClose, onSubmit }) {
        const [data, setData] = useState({ current: '', new: '', confirm: '' });
        if (!isOpen) return null;
        return (
            <>
                <div className="fixed inset-0 bg-black/50 z-50 anim-fade backdrop-blur-sm" onClick={onClose} />
                <div className="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white p-8 rounded-2xl w-[90%] max-w-md z-[60] anim-scale shadow-2xl">
                    <h2 className="text-xl font-bold mb-6 text-gray-900">Đổi mật khẩu</h2>
                    <div className="space-y-4">
                        <input type="password" placeholder="Mật khẩu hiện tại" className="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500" 
                            value={data.current} onChange={e => setData({...data, current: e.target.value})} />
                        <input type="password" placeholder="Mật khẩu mới" className="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500" 
                            value={data.new} onChange={e => setData({...data, new: e.target.value})} />
                        <input type="password" placeholder="Xác nhận mật khẩu mới" className="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500" 
                            value={data.confirm} onChange={e => setData({...data, confirm: e.target.value})} />
                    </div>
                    <div className="flex gap-3 mt-8 justify-end">
                        <button onClick={onClose} className="px-5 py-2 text-gray-600">Hủy</button>
                        <button onClick={() => onSubmit(data)} className="px-6 py-2 bg-blue-600 text-white rounded-full font-bold shadow-md hover:bg-blue-700 transition">Xác nhận</button>
                    </div>
                </div>
            </>
        );
    }

    function UserProfile() {
        const [isEditing, setIsEditing] = useState(false);
        const [isPwdOpen, setIsPwdOpen] = useState(false);
        const [profile, setProfile] = useState(initialUserData);
        const [edited, setEdited] = useState(initialUserData);

        const handlePwdSubmit = async (d) => {
            if (d.new !== d.confirm) return window.showToast('Mật khẩu không khớp!', 'error');
            const res = await fetch('user_profile.php?action=change_password', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(d)
            });
            const result = await res.json();
            window.showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) setIsPwdOpen(false);
        };

        const handleSaveProfile = async () => {
            const res = await fetch('user_profile.php?action=update_profile', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(edited)
            });
            const result = await res.json();
            if (result.success) {
                setProfile(edited);
                setIsEditing(false);
                window.showToast(result.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.showToast(result.message, 'error');
            }
        };

        const handleAvatar = (e) => {
            const file = e.target.files?.[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = () => setEdited({ ...edited, avatar: reader.result });
                reader.readAsDataURL(file);
            }
        };

        return (
            <div className="pb-16 pt-4 px-4">
                <PasswordDialog isOpen={isPwdOpen} onClose={() => setIsPwdOpen(false)} onSubmit={handlePwdSubmit} />
                
                <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-4">
                    <div className="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-800 h-40 relative">
                        <div className="absolute -bottom-16 left-8 sm:left-12">
                            <div className="relative group">
                                <div className="w-32 h-32 rounded-full ring-4 ring-white shadow-xl overflow-hidden bg-white">
                                    <img src={isEditing ? edited.avatar : profile.avatar} className="w-full h-full object-cover" />
                                </div>
                                {isEditing && (
                                    <label className="absolute bottom-2 right-2 bg-blue-600 text-white w-9 h-9 flex items-center justify-center rounded-full cursor-pointer hover:bg-blue-700 shadow-md">
                                        <i className="fas fa-camera"></i>
                                        <input type="file" accept="image/*" className="hidden" onChange={handleAvatar} />
                                    </label>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="pt-20 px-8 sm:px-12 pb-10">
                        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 border-b pb-6 gap-4">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{profile.name}</h1>
                                <p className="text-gray-500 mt-1">{profile.email}</p>
                            </div>
                            <div className="flex gap-3">
                                {!isEditing ? (
                                    <>
                                        <button onClick={() => setIsPwdOpen(true)} className="px-5 py-2.5 border rounded-full text-sm font-semibold text-gray-700 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition">Đổi mật khẩu</button>
                                        <button onClick={() => setIsEditing(true)} className="px-6 py-2.5 bg-blue-600 text-white rounded-full font-semibold shadow-md hover:bg-blue-700 transition">Sửa hồ sơ</button>
                                    </>
                                ) : (
                                    <>
                                        <button onClick={() => { setIsEditing(false); setEdited(profile); }} className="px-5 py-2.5 border rounded-full text-sm text-gray-600 hover:bg-gray-50 transition">Hủy</button>
                                        <button onClick={handleSaveProfile} className="px-6 py-2.5 bg-green-600 text-white rounded-full font-semibold shadow-md hover:bg-green-700 transition">Lưu thông tin</button>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="space-y-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Họ và Tên</label>
                                    {isEditing ? (
                                        <input className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" 
                                            value={edited.name} onChange={e => setEdited({...edited, name: e.target.value})} />
                                    ) : (
                                        <p className="font-medium text-gray-800 p-3">{profile.name}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Số điện thoại</label>
                                    {isEditing ? (
                                        <input type="tel" className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" 
                                            value={edited.phone} onChange={e => setEdited({...edited, phone: e.target.value})} placeholder="Chưa cập nhật" />
                                    ) : (
                                        <p className="font-medium text-gray-800 p-3">{profile.phone || <span className="text-gray-400 italic font-normal">Chưa cập nhật</span>}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Giới tính</label>
                                    {isEditing ? (
                                        <select className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer" 
                                            value={edited.gender} onChange={e => setEdited({...edited, gender: e.target.value})}>
                                            <option value="male">Nam</option>
                                            <option value="female">Nữ</option>
                                        </select>
                                    ) : (
                                        <p className="font-medium text-gray-800 p-3">{profile.gender === 'male' ? 'Nam' : 'Nữ'}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ngày sinh</label>
                                    {isEditing ? (
                                        <input type="date" className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer" 
                                            value={edited.dateOfBirth} onChange={e => setEdited({...edited, dateOfBirth: e.target.value})} />
                                    ) : (
                                        <p className="font-medium text-gray-800 p-3">{profile.dateOfBirth ? new Date(profile.dateOfBirth).toLocaleDateString('vi-VN') : <span className="text-gray-400 italic font-normal">Chưa cập nhật</span>}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Địa chỉ giao hàng</label>
                                    {isEditing ? (
                                        <textarea className="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition resize-none" 
                                            rows="5" value={edited.address} onChange={e => setEdited({...edited, address: e.target.value})} placeholder="Nhập địa chỉ nhận hàng của bạn..." />
                                    ) : (
                                        <p className="font-medium text-gray-800 p-3 leading-relaxed">{profile.address || <span className="text-gray-400 italic font-normal">Chưa cập nhật</span>}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<UserProfile />);
</script>

<?php include 'footer.php'; ?>