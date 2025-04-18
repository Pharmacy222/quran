<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['phone'])) {
    header("Location: results.php");
    exit();
}

$phone = $_GET['phone'];
$name = $_GET['name'] ?? '';
$position = $_GET['position'] ?? '';

// رسالة التهنئة الجاهزة
$position_names = [
    1 => "الأول",
    2 => "الثاني", 
    3 => "الثالث"
];

$default_message = "مبروك {$name}! لقد حصلت على المركز {$position_names[$position]} في مسابقة القرآن الكريم. نعتز بمشاركتك ونتمنى لك المزيد من التوفيق.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    
    // هنا كود إرسال SMS الفعلي باستخدام API
    // هذا مثال افتراضي:
    $sms_sent = true; // يتم استبدال هذا بالكود الحقيقي
    
    if ($sms_sent) {
        $_SESSION['success_message'] = "تم إرسال الرسالة بنجاح إلى $phone";
        header("Location: results.php");
        exit();
    } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء إرسال الرسالة";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال رسالة تهنئة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        .recipient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #3498db;
        }
        .form-group {
            margin-bottom: 20px;
        }
        textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-height: 150px;
            font-family: 'Tajawal', sans-serif;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-sms"></i> إرسال رسالة تهنئة</h1>
        
        <div class="recipient-info">
            <h3>المستلم:</h3>
            <p><strong>الاسم:</strong> <?= htmlspecialchars($name) ?></p>
            <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($phone) ?></p>
            <p><strong>المركز:</strong> <?= $position_names[$position] ?? '' ?></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="message">نص الرسالة:</label>
                <textarea id="message" name="message" required><?= $default_message ?></textarea>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> إرسال الرسالة
            </button>
            <a href="results.php" class="btn" style="background: #95a5a6; margin-right: 10px;">
                <i class="fas fa-times"></i> إلغاء
            </a>
        </form>
    </div>
</body>
</html>