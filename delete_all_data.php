<?php
session_start();
require_once 'db_connect.php';

// التحقق من تسجيل الدخول وأن المستخدم لديه صلاحيات الإدارة
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// يمكنك إضافة تحقق إضافي للصلاحيات إذا كان لديك مستويات مختلفة للمشرفين
// if ($_SESSION['admin_role'] !== 'super_admin') {
//     $_SESSION['error_message'] = "ليس لديك صلاحيات لهذا الإجراء";
//     header("Location: admin_dashboard.php");
//     exit();
// }

// التحقق من أن الطريقة POST مستخدمة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // بدء transaction لضمان السلامة
        $conn->beginTransaction();

        // حذف جميع البيانات من الجدول (استخدم DELETE بدلاً من TRUNCATE للحفاظ على AI)
        $stmt = $conn->prepare("DELETE FROM participants");
        $stmt->execute();

        // إعادة تعيين AUTO_INCREMENT
        $resetAI = $conn->prepare("ALTER TABLE participants AUTO_INCREMENT = 1");
        $resetAI->execute();

        // تأكيد العملية
        $conn->commit();

        $_SESSION['success_message'] = "تم حذف جميع بيانات المشاركين بنجاح";
    } catch (PDOException $e) {
        // التراجع عن العملية في حالة حدوث خطأ
        $conn->rollBack();
        $_SESSION['error_message'] = "حدث خطأ أثناء محاولة حذف البيانات: " . $e->getMessage();
    }
    
    // إعادة التوجيه إلى لوحة التحكم
    header("Location: admin_dashboard.php");
    exit();
} else {
    // إذا لم تكن الطريقة POST، إعادة توجيه إلى لوحة التحكم
    $_SESSION['error_message'] = "طلب غير صحيح";
    header("Location: admin_dashboard.php");
    exit();
}
?>