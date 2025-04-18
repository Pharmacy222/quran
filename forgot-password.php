<?php
session_start();
require_once 'db_connect.php';

// إذا كان المستخدم مسجل دخوله بالفعل
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    try {
        // التحقق من وجود البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id, username FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin) {
            // إنشاء رمز استعادة
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // صلاحية ساعة واحدة

            // حفظ الرمز في قاعدة البيانات
            $stmt = $conn->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $admin['id']]);

            // إرسال البريد الإلكتروني (هنا مثال تجريبي)
            $reset_link = "http://yourdomain.com/reset-password.php?token=$token";
            $subject = "إعادة تعيين كلمة المرور";
            $message = "مرحبًا {$admin['username']},\n\n";
            $message .= "لقد تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بك.\n";
            $message .= "الرجاء النقر على الرابط التالي لإعادة التعيين:\n";
            $message .= "$reset_link\n\n";
            $message .= "إذا لم تطلب هذا، يرجى تجاهل هذه الرسالة.\n";
            $message .= "الرابط صالح لمدة ساعة واحدة فقط.\n\n";
            $message .= "مع تحيات,\nفريق الإدارة";

            // في الواقع يجب استخدام مكتبة مثل PHPMailer لإرسال البريد
            // mail($email, $subject, $message);

            $message = "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. الرجاء التحقق من صندوق الوارد.";
        } else {
            $error = "البريد الإلكتروني غير مسجل في النظام";
        }
    } catch (PDOException $e) {
        $error = "حدث خطأ في النظام. الرجاء المحاولة لاحقًا.";
        error_log("Forgot Password Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* نفس تنسيق صفحة تسجيل الدخول مع تعديلات بسيطة */
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
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1><i class="fas fa-key"></i> استعادة كلمة المرور</h1>
            <p>أدخل بريدك الإلكتروني لإعادة تعيين كلمة المرور</p>
        </div>
        
        <div class="reset-body">
            <?php if ($message): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="البريد الإلكتروني المسجل" required>
                    </div>
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> إرسال رابط الإستعادة
                </button>
            </form>
            
            <div class="footer-links">
                <a href="admin_login.php"><i class="fas fa-arrow-right"></i> العودة لتسجيل الدخول</a>
            </div>
        </div>
    </div>
</body>
</html>