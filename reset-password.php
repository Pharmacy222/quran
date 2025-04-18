<?php
session_start();
require_once 'db_connect.php';

// إذا كان المستخدم مسجل دخوله بالفعل
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';
$success = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

// التحقق من صحة الرمز
if (!empty($token)) {
    try {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $admin = $stmt->fetch();

        if ($admin) {
            $valid_token = true;
            $admin_id = $admin['id'];
            
            // معالجة إعادة تعيين كلمة المرور
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $new_password = trim($_POST['password']);
                $confirm_password = trim($_POST['confirm_password']);
                
                if (strlen($new_password) < 8) {
                    $error = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
                } elseif ($new_password !== $confirm_password) {
                    $error = "كلمتا المرور غير متطابقتين";
                } else {
                    // تحديث كلمة المرور (في الواقع يجب تشفيرها)
                    $stmt = $conn->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                    $stmt->execute([$new_password, $admin_id]);
                    
                    $success = "تم تحديث كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.";
                    $valid_token = false; // لعدم السماح باستخدام الرمز مرة أخرى
                }
            }
        } else {
            $error = "رابط إعادة التعيين غير صالح أو منتهي الصلاحية";
        }
    } catch (PDOException $e) {
        $error = "حدث خطأ في النظام. الرجاء المحاولة لاحقًا.";
        error_log("Reset Password Error: " . $e->getMessage());
    }
} else {
    $error = "رابط غير صالح";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
        }

        body {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.9), rgba(58, 86, 212, 0.95)), 
                        url('https://images.unsplash.com/photo-1518655048521-f130df041f66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            font-family: 'Tajawal', sans-serif;
        }

        .reset-container {
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out forwards;
        }

        .reset-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 30px;
            text-align: center;
        }

        .reset-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }

        input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .success-message {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .error-message {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
            font-size: 0.85rem;
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1><i class="fas fa-lock"></i> تعيين كلمة مرور جديدة</h1>
            <p>اختر كلمة مرور قوية وآمنة</p>
        </div>
        
        <div class="reset-body">
            <?php if ($error && !$valid_token): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <div class="footer-links">
                    <a href="forgot-password.php"><i class="fas fa-key"></i> طلب رابط استعادة جديد</a>
                </div>
            <?php elseif ($success): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="footer-links">
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> الانتقال إلى صفحة تسجيل الدخول</a>
                </div>
            <?php elseif ($valid_token): ?>
                <?php if ($error): ?>
                    <div class="message error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="كلمة المرور الجديدة" required minlength="8">
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter" id="strengthMeter"></div>
                        </div>
                        <small>يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" placeholder="تأكيد كلمة المرور الجديدة" required>
                        </div>
                    </div>
                    
                    <button type="submit">
                        <i class="fas fa-save"></i> حفظ كلمة المرور الجديدة
                    </button>
                </form>
                
                <div class="footer-links">
                    <a href="admin_login.php"><i class="fas fa-arrow-right"></i> العودة لتسجيل الدخول</a>
                </div>
                
                <script>
                    
                    document.getElementById('password').addEventListener('input', function() {
                        const password = this.value;
                        const meter = document.getElementById('strengthMeter');
                        let strength = 0;
                        
                        if (password.length >= 8) strength += 1;
                        if (password.match(/[a-z]/)) strength += 1;
                        if (password.match(/[A-Z]/)) strength += 1;
                        if (password.match(/[0-9]/)) strength += 1;
                        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
                        
                        const width = (strength / 5) * 100;
                        meter.style.width = width + '%';
                        
                        if (strength <= 2) {
                            meter.style.backgroundColor = '#f72585'; 
                        } else if (strength <= 4) {
                            meter.style.backgroundColor = '#f8961e'; 
                        } else {
                            meter.style.backgroundColor = '#4cc9f0';
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>