<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// جلب الفائزين (المراكز الثلاثة الأولى)
$winners = $conn->query("
    SELECT p.full_name, p.parts_count, r.position, r.level 
    FROM participants p
    JOIN results r ON p.id = r.participant_id
    WHERE r.position <= 3
    ORDER BY r.position
")->fetchAll();

// إنشاء الشهادات (هذا مثال بسيط - يمكن تطويره لإنشاء PDF)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="شهادات_المسابقة_' . date('Y-m-d') . '.xls"');
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .certificate {
            border: 2px solid #000;
            padding: 20px;
            margin: 10px;
            text-align: center;
            page-break-after: always;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .winner-name {
            font-size: 20px;
            margin: 15px 0;
        }
        .details {
            font-size: 16px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php foreach ($winners as $winner): ?>
        <div class="certificate">
            <div class="title">شهادة تقدير</div>
            <div>يُمنح الطالب/ة</div>
            <div class="winner-name"><?= htmlspecialchars($winner['full_name']) ?></div>
            <div>الذي حقق المركز <?= $winner['position'] ?></div>
            <div class="details">في مسابقة القرآن الكريم - مستوى <?= htmlspecialchars($winner['level']) ?></div>
            <div class="details">بتسميع <?= htmlspecialchars($winner['parts_count']) ?> من القرآن الكريم</div>
            <div>وذلك تقديراً لجهوده المتميزة</div>
            <div style="margin-top: 50px;">التوقيع: _______________</div>
        </div>
    <?php endforeach; ?>
</body>
</html>