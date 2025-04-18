<?php
session_start();
require_once 'db_connect.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// جلب جميع المشاركين
$stmt = $conn->query("SELECT * FROM participants ORDER BY registration_date DESC");
$participants = $stmt->fetchAll();

// عدد المشاركين
$count_stmt = $conn->query("SELECT COUNT(*) as total FROM participants");
$total_participants = $count_stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - مسابقة القرآن الكريم</title>
    <style>
        /* أنماط لوحة التحكم */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            width: 90%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        h1, h2 {
            color: #2c3e50;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .stat-box {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 5px;
            width: 22%;
            text-align: center;
            margin: 10px 0;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .logout-btn {
            display: block;
            width: 100px;
            margin: 20px auto;
            padding: 10px;
            background: #e74c3c;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .stat-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>لوحة تحكم مسابقة القرآن الكريم</h1>
        <h2>إحصاءات المشاركين</h2>
        
        <div class="stats">
            <div class="stat-box">
                <h3>إجمالي المسجلين</h3>
                <p><?php echo $total_participants; ?></p>
            </div>
            <!-- يمكن إضافة المزيد من الإحصاءات هنا -->
        </div>
        
        <h2>قائمة المشاركين</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم الكامل</th>
                    <th>العمر</th>
                    <th>اسم الشيخ</th>
                    <th>عدد الأجزاء</th>
                    <th>رقم الهاتف</th>
                    <th>تاريخ التسجيل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $index => $participant): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($participant['full_name']); ?></td>
                    <td><?php echo $participant['age']; ?></td>
                    <td><?php echo $participant['sheikh_name'] ? htmlspecialchars($participant['sheikh_name']) : '---'; ?></td>
                    <td><?php echo htmlspecialchars($participant['parts_count']); ?></td>
                    <td><?php echo htmlspecialchars($participant['phone']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($participant['registration_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
    </div>
</body>
</html>