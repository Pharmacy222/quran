<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسموح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صالحة']);
    exit();
}

$required_fields = ['participant_id', 'score', 'position'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
        exit();
    }
}

$participant_id = intval($_POST['participant_id']);
$score = floatval($_POST['score']);
$position = intval($_POST['position']);
$notes = $_POST['notes'] ?? '';
$result_id = $_POST['result_id'] ?? null;

try {
    $conn->beginTransaction();
    
    // التحقق من عدم وجود مركز مكرر
    if ($result_id) {
        $check_sql = "SELECT id FROM results WHERE position = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$position, $result_id]);
    } else {
        $check_sql = "SELECT id FROM results WHERE position = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$position]);
    }
    
    if ($check_stmt->fetch()) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'المركز محجوز بالفعل لمشارك آخر']);
        exit();
    }
    
    if ($result_id) {
        $stmt = $conn->prepare("UPDATE results SET score=?, level=?, position=?, notes=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$score, 'متوسط', $position, $notes, $result_id]);
        $message = 'تم تحديث النتيجة بنجاح';
    } else {
        $stmt = $conn->prepare("INSERT INTO results (participant_id, score, level, position, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$participant_id, $score, 'متوسط', $position, $notes]);
        $message = 'تم إضافة النتيجة بنجاح';
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}