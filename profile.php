<?php
session_start();
require_once 'db_connect.php';

// تفعيل عرض الأخطاء لأغراض التطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// جلب بيانات المشرف الحالي مع التحقق من وجودها
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'لم يتم العثور على بيانات المشرف'];
    header("Location: admin_dashboard.php");
    exit();
}

// تعيين قيم افتراضية إذا كانت غير موجودة
$admin['username'] = $admin['username'] ?? '';
$admin['email'] = $admin['email'] ?? '';
$admin['full_name'] = $admin['full_name'] ?? '';

// معالجة تحديث البيانات الشخصية
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    
    // التحقق من صحة البيانات
    if (empty($username) || empty($email) || empty($full_name)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'جميع الحقول مطلوبة'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'البريد الإلكتروني غير صحيح'];
    } else {
        try {
            // التحقق من عدم تكرار اسم المستخدم أو البريد الإلكتروني
            $stmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $admin_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'اسم المستخدم أو البريد الإلكتروني موجود بالفعل'];
            } else {
                // تحديث البيانات
                $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, full_name = ? WHERE id = ?");
                $stmt->execute([$username, $email, $full_name, $admin_id]);
                
                $_SESSION['admin_username'] = $username;
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'تم تحديث البيانات بنجاح'];
                
                // إعادة تحميل البيانات
                header("Location: profile.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }
}

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من صحة البيانات
    if (empty($current_password)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'كلمة المرور الحالية مطلوبة'];
    } elseif (!password_verify($current_password, $admin['password'])) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'كلمة المرور الحالية غير صحيحة'];
    } elseif (empty($new_password)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'كلمة المرور الجديدة مطلوبة'];
    } elseif (strlen($new_password) < 6) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'];
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'كلمة المرور الجديدة غير متطابقة'];
    } else {
        try {
            // تحديث كلمة المرور
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $admin_id]);
            
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'تم تغيير كلمة المرور بنجاح'];
            
            // إعادة تحميل الصفحة
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        .admin-navbar {
            background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .logo i {
            margin-left: 10px;
            font-size: 1.8rem;
            color: gold;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--info));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            color: var(--secondary);
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .profile-card h2 {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert.success {
            background-color: #f0fdf4;
            color: #166534;
            border-right: 5px solid #16a34a;
        }

        .alert.error {
            background-color: #fef2f2;
            color: #991b1b;
            border-right: 5px solid #dc2626;
        }

        .alert .close-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .profile-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <nav class="admin-navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-quran"></i>
                <span>مسابقة القرآن الكريم</span>
            </div>
            
            <div class="user-menu">
                <div class="user-avatar">
                    <?php echo substr($admin['username'], 0, 1); ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-header">
            <h1>الملف الشخصي</h1>
            <p>إدارة بيانات حسابك وتغيير كلمة المرور</p>
        </div>

        <!-- عرض التنبيهات -->
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert <?= $_SESSION['alert']['type'] ?>">
                <span><?= $_SESSION['alert']['message'] ?></span>
                <button class="close-btn" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <!-- بطاقة البيانات الشخصية -->
        <div class="profile-card">
            <h2><i class="fas fa-user-cog"></i> البيانات الشخصية</h2>
            
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($admin['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">الاسم الكامل</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </form>
        </div>

        <!-- بطاقة تغيير كلمة المرور -->
        <div class="profile-card">
            <h2><i class="fas fa-key"></i> تغيير كلمة المرور</h2>
            
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">كلمة المرور الجديدة</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>

    <script>
        // إغلاق التنبيهات عند النقر على زر الإغلاق
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });
    </script>
</body>
</html>