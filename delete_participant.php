<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$participant_id = $_GET['id'];

try {
    // حذف النتائج المرتبطة أولاً إذا كان هناك foreign key constraint
    $conn->beginTransaction();
    
    $delete_results = $conn->prepare("DELETE FROM results WHERE participant_id = ?");
    $delete_results->execute([$participant_id]);
    
    $delete_participant = $conn->prepare("DELETE FROM participants WHERE id = ?");
    $delete_participant->execute([$participant_id]);
    
    $conn->commit();
    
    $_SESSION['success_message'] = "تم حذف المشارك بنجاح";
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error_message'] = "حدث خطأ أثناء حذف المشارك: " . $e->getMessage();
}

header("Location: admin_dashboard.php");
exit();
?>