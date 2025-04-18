<?php
require_once 'db_connect.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من البيانات
        $required_fields = ['name', 'age', 'parts_count', 'phon'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("جميع الحقول المطلوبة يجب ملؤها");
            }
        }

        // تنظيف البيانات
        $full_name = htmlspecialchars(trim($_POST['name']));
        $age = intval($_POST['age']);
        $sheikh_name = isset($_POST['sheikh_name']) ? htmlspecialchars(trim($_POST['sheikh_name'])) : null;
        $parts_count = htmlspecialchars(trim($_POST['parts_count']));
        $phone = htmlspecialchars(trim($_POST['phon']));

        // التحقق من العمر
        if ($age < 4 || $age > 18) {
            throw new Exception("العمر يجب أن يكون بين 4 و 18 سنة");
        }

        // إدخال البيانات في قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO participants (full_name, age, sheikh_name, parts_count, phone) 
                               VALUES (:full_name, :age, :sheikh_name, :parts_count, :phone)");
        
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':sheikh_name', $sheikh_name);
        $stmt->bindParam(':parts_count', $parts_count);
        $stmt->bindParam(':phone', $phone);

        if ($stmt->execute()) {
            echo "تم تسجيل المشارك بنجاح! شكراً لتسجيلك في مسابقة القرآن الكريم.";
        } else {
            throw new Exception("حدث خطأ أثناء حفظ البيانات");
        }
    } catch (Exception $e) {
        echo "خطأ: " . $e->getMessage();
    }
} else {
    header("Location: index.html");
    exit();
}
?>


