<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسموح بالوصول']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    if (isset($_GET['id'])) {
        $result_id = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
        $stmt->execute([$result_id]);
        $response = ['success' => true, 'message' => 'تم حذف النتيجة بنجاح'];
    }
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);