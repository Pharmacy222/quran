<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="participants_' . date('Y-m-d') . '.xls"');

$stmt = $conn->query("SELECT * FROM participants ORDER BY registration_date DESC");
$participants = $stmt->fetchAll();
?>

<table border="1">
    <tr>
        <th>#</th>
        <th>الاسم الكامل</th>
        <th>العمر</th>
        <th>اسم الشيخ</th>
        <th>عدد الأجزاء</th>
        <th>رقم الهاتف</th>
        <th>تاريخ التسجيل</th>
    </tr>
    <?php foreach ($participants as $index => $participant): ?>
    <tr>
        <td><?php echo $index + 1; ?></td>
        <td><?php echo htmlspecialchars($participant['full_name']); ?></td>
        <td><?php echo $participant['age']; ?></td>
        <td><?php echo $participant['sheikh_name'] ? htmlspecialchars($participant['sheikh_name']) : '---'; ?></td>
        <td><?php echo htmlspecialchars($participant['parts_count']); ?></td>
        <td><?php echo htmlspecialchars($participant['phone']); ?></td>
        <td><?php echo date('Y/m/d H:i', strtotime($participant['registration_date'])); ?></td>
    </tr>
    <?php endforeach; ?>
</table>