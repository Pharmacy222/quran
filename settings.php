<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// معالجة تحديث الإعدادات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_title = $_POST['site_title'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $records_per_page = (int)($_POST['records_per_page'] ?? 15);
    $enable_registration = isset($_POST['enable_registration']) ? 1 : 0;
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("UPDATE settings SET 
            site_title = ?, 
            admin_email = ?, 
            records_per_page = ?, 
            enable_registration = ?, 
            maintenance_mode = ? 
            WHERE id = 1");
        
        $stmt->execute([$site_title, $admin_email, $records_per_page, $enable_registration, $maintenance_mode]);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'تم تحديث الإعدادات بنجاح'
        ];
        
        header("Location: settings.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage()
        ];
    }
}

// جلب الإعدادات الحالية
$settings = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        /* أنماط الصفحة الرئيسية */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --secondary-dark: #1a252f;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
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
            min-height: 100vh;
        }

        /* أنماط التنبيهات */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out forwards;
            transform: translateX(120%);
            opacity: 0;
            position: relative;
            overflow: hidden;
            border-right: 5px solid;
        }

        .alert.success {
            background-color: #f0fdf4;
            color: #166534;
            border-color: #16a34a;
        }

        .alert.error {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #dc2626;
        }

        .alert.warning {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #f59e0b;
        }

        .alert.info {
            background-color: #eff6ff;
            color: #1e40af;
            border-color: #3b82f6;
        }

        .alert .icon {
            font-size: 1.5rem;
            margin-left: 10px;
        }

        .alert .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }

        .alert .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background-color: rgba(0,0,0,0.1);
            width: 100%;
        }

        .alert .progress-bar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 100%;
            width: 100%;
            animation: progress 5s linear forwards;
        }

        .alert.success .progress-bar::after {
            background-color: #16a34a;
        }

        .alert.error .progress-bar::after {
            background-color: #dc2626;
        }

        .alert.warning .progress-bar::after {
            background-color: #f59e0b;
        }

        .alert.info .progress-bar::after {
            background-color: #3b82f6;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* شريط التنقل */
        .admin-navbar {
            background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            color: var(--gold);
        }

        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background-color: var(--gold);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 80%;
        }

        .nav-links .active {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-links .active::after {
            width: 80%;
            background-color: var(--gold);
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
            position: relative;
            transition: all 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s;
            z-index: 100;
        }

        .user-avatar:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            color: var(--dark);
            padding: 0.8rem 1.5rem;
            display: block;
            font-weight: 500;
            transition: all 0.3s;
        }

        .dropdown-menu a:hover {
            background: var(--light-gray);
            color: var(--primary);
        }

        .dropdown-menu a i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }

        /* المحتوى الرئيسي */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-header h1 {
            color: var(--secondary);
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            text-shadow: 0 2px 10px rgba(67, 97, 238, 0.2);
        }

        .dashboard-header h2 {
            color: var(--gray);
            font-size: 1.4rem;
            font-weight: 400;
            margin-top: 0.5rem;
        }

        /* بطاقات الإعدادات */
        .settings-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }

        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .settings-header h3 {
            color: var(--secondary);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-header h3 i {
            color: var(--primary);
        }

        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .settings-tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .settings-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .settings-tab:hover {
            color: var(--primary-dark);
        }

        .settings-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .form-section {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--light-gray);
        }

        .form-section h4 {
            color: var(--secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--light-gray);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h4 i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            background: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2),
                        inset 0 2px 5px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
            outline: none;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .form-check-label {
            font-weight: 500;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--light-gray);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            transition: all 0.4s;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn i {
            margin-left: 0.8rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray), #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        /* تأثيرات الحركة */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* تحسينات للعرض على الأجهزة الصغيرة */
        @media (max-width: 768px) {
            .settings-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .settings-tab {
                padding: 0.8rem;
                border-bottom: none;
                border-right: 3px solid transparent;
            }
            
            .settings-tab.active {
                border-right-color: var(--primary);
                border-bottom: none;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* تحسينات للوضع الليلي */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #e0e0e0;
            }
            
            .settings-container,
            .form-section {
                background: #1e1e1e;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                color: #e0e0e0;
            }
            
            .form-section {
                background: #252525;
                border-color: #444;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                background: #2d2d2d;
                color: #e0e0e0;
                border-color: #444;
            }
            
            .form-group label {
                color: #e0e0e0;
            }
            
            .settings-header h3,
            .form-section h4 {
                color: #e0e0e0;
            }
            
            .settings-tabs {
                border-bottom-color: #444;
            }
            
            .form-section h4 {
                border-bottom-color: #444;
            }
            
            .form-actions {
                border-top-color: #444;
            }
        }
    </style>
</head>
<body>
    <!-- حاوية التنبيهات -->
    <div class="alert-container"></div>

    <!-- شريط التنقل العلوي -->
    <nav class="admin-navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-quran"></i>
                <span>مسابقة القرآن الكريم</span>
            </div>
            
            <ul class="nav-links">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                    </a>
                </li>
                <li>
                    <a href="participants.php">
                        <i class="fas fa-users"></i> المشاركون
                    </a>
                </li>
                <li>
                    <a href="results.php">
                        <i class="fas fa-chart-bar"></i> النتائج
                    </a>
                </li>
                <li>
                    <a href="export.php">
                        <i class="fas fa-file-export"></i> تصدير البيانات
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="active">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                </li>
            </ul>
            
            <div class="user-menu">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-avatar">
                    <?php echo substr($_SESSION['admin_username'], 0, 1); ?>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user-cog"></i> الملف الشخصي</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>إعدادات النظام</h1>
            <h2>إدارة إعدادات لوحة التحكم وتفضيلات النظام</h2>
        </div>

        <div class="settings-container">
            <div class="settings-header">
                <h3><i class="fas fa-cogs"></i> الإعدادات العامة</h3>
            </div>
            
            <div class="settings-tabs">
                <div class="settings-tab active">الإعدادات العامة</div>
                <div class="settings-tab">إعدادات الأمان</div>
                <div class="settings-tab">إعدادات البريد</div>
                <div class="settings-tab">إعدادات متقدمة</div>
            </div>
            
            <form method="POST" action="settings.php" class="settings-form">
                <div class="form-section">
                    <h4><i class="fas fa-globe"></i> إعدادات الموقع</h4>
                    
                    <div class="form-group">
                        <label for="site_title">عنوان الموقع</label>
                        <input type="text" id="site_title" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? 'مسابقة القرآن الكريم') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">البريد الإلكتروني للإدارة</label>
                        <input style="background-color: #1e1e1e; color: #fff;" type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="records_per_page">عدد السجلات في الصفحة</label>
                        <select id="records_per_page" name="records_per_page">
                            <option value="10" <?= ($settings['records_per_page'] ?? 15) == 10 ? 'selected' : '' ?>>10 سجلات</option>
                            <option value="15" <?= ($settings['records_per_page'] ?? 15) == 15 ? 'selected' : '' ?>>15 سجلات</option>
                            <option value="20" <?= ($settings['records_per_page'] ?? 15) == 20 ? 'selected' : '' ?>>20 سجلات</option>
                            <option value="25" <?= ($settings['records_per_page'] ?? 15) == 25 ? 'selected' : '' ?>>25 سجلات</option>
                            <option value="50" <?= ($settings['records_per_page'] ?? 15) == 50 ? 'selected' : '' ?>>50 سجلات</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4><i class="fas fa-user-shield"></i> إعدادات النظام</h4>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="enable_registration" name="enable_registration" <?= ($settings['enable_registration'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_registration">تفعيل التسجيل للمشاركين الجدد</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="maintenance_mode">وضع الصيانة (إيقاف الموقع مؤقتاً)</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> إعادة تعيين
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // دالة لعرض التنبيهات
        function showAlert(type, message, duration = 5000) {
            const alertContainer = document.querySelector('.alert-container') || createAlertContainer();
            
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            
            const icons = {
                success: 'fa-circle-check',
                error: 'fa-circle-xmark',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info'
            };
            
            alert.innerHTML = `
                <i class="fa-solid ${icons[type]} icon"></i>
                <span>${message}</span>
                <button class="close-btn"><i class="fa-solid fa-xmark"></i></button>
                <div class="progress-bar"></div>
            `;
            
            alertContainer.appendChild(alert);
            
            // تشغيل انيميشن الدخول
            setTimeout(() => {
                alert.style.transform = 'translateX(0)';
                alert.style.opacity = '1';
            }, 10);
            
            // إغلاق التنبيه عند النقر على زر الإغلاق
            const closeBtn = alert.querySelector('.close-btn');
            closeBtn.addEventListener('click', () => {
                closeAlert(alert);
            });
            
            // إغلاق التنبيه تلقائياً بعد المدة المحددة
            if (duration) {
                setTimeout(() => {
                    closeAlert(alert);
                }, duration);
            }
        }

        // دالة لإغلاق التنبيه
        function closeAlert(alert) {
            alert.style.transform = 'translateX(120%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }

        // إنشاء حاوية التنبيهات إذا لم تكن موجودة
        function createAlertContainer() {
            const container = document.createElement('div');
            container.className = 'alert-container';
            document.body.appendChild(container);
            return container;
        }

        // عرض التنبيهات من الجلسة إذا وجدت
        <?php if (isset($_SESSION['alert'])): ?>
            showAlert('<?= $_SESSION['alert']['type'] ?>', '<?= $_SESSION['alert']['message'] ?>');
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        // تبديل علامات التبويب
        const tabs = document.querySelectorAll('.settings-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // تنبيه عند محاولة مغادرة الصفحة مع وجود تغييرات غير محفوظة
        let formChanged = false;
        const form = document.querySelector('.settings-form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'لديك تغييرات غير محفوظة. هل أنت متأكد أنك تريد المغادرة؟';
            }
        });
    </script>
</body>
</html>