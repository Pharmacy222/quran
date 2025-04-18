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
$stmt = $conn->prepare("SELECT * FROM participants WHERE id = ?");
$stmt->execute([$participant_id]);
$participant = $stmt->fetch();

if (!$participant) {
    $_SESSION['error_message'] = "المشارك غير موجود";
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $age = intval($_POST['age']);
    $sheikh_name = !empty($_POST['sheikh_name']) ? htmlspecialchars(trim($_POST['sheikh_name'])) : null;
    $parts_count = htmlspecialchars(trim($_POST['parts_count']));
    $phone = htmlspecialchars(trim($_POST['phone']));

    try {
        if (empty($full_name) || empty($age) || empty($parts_count) || empty($phone)) {
            throw new Exception("جميع الحقول المطلوبة يجب ملؤها");
        }

        if ($age < 4 || $age > 18) {
            throw new Exception("العمر يجب أن يكون بين 4 و 18 سنة");
        }

        $update_stmt = $conn->prepare("UPDATE participants SET full_name = ?, age = ?, sheikh_name = ?, parts_count = ?, phone = ? WHERE id = ?");
        $update_stmt->execute([$full_name, $age, $sheikh_name, $parts_count, $phone, $participant_id]);

        $_SESSION['success_message'] = "تم تحديث بيانات المشارك بنجاح";
        header("Location: admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "خطأ: " . $e->getMessage();
        header("Location: edit_participant.php?id=" . $participant_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات المشارك</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #f8f9fa;
            --dark: #343a40;
            --border: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #212529;
            line-height: 1.6;
        }

        .admin-navbar {
            background: linear-gradient(135deg, var(--secondary), var(--dark));
            padding: 0 2rem;
            box-shadow: var(--shadow);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 1.2rem 1.5rem;
            display: block;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a i {
            margin-left: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            color: white;
            padding: 0 1rem;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .edit-card {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), #5dade2);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .card-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        .participant-info {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-right: 4px solid var(--primary);
        }

        .participant-info h3 {
            color: var(--secondary);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .participant-info p {
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn i {
            margin-left: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .action-btns {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                padding: 1rem 0;
            }

            .nav-links {
                width: 100%;
                flex-direction: column;
            }

            .nav-links a {
                padding: 0.8rem 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .user-info {
                padding: 0.8rem 1rem;
                width: 100%;
                justify-content: center;
            }

            .container {
                padding: 0 0.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .action-btns {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <nav class="admin-navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                    </a>
                </li>
                <li>
                    <a href="results.php">
                        <i class="fas fa-chart-bar"></i> النتائج
                    </a>
                </li>
            </ul>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo $_SESSION['admin_username']; ?></span>
                <a href="logout.php" style="color: white; margin-right: 15px;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="edit-card">
            <div class="card-header">
                <h1><i class="fas fa-user-edit"></i> تعديل بيانات المشارك</h1>
            </div>

            <div class="card-body">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error_message'] ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="participant-info">
                    <h3>معلومات المشارك الحالية</h3>
                    <p><strong>الاسم:</strong> <?= htmlspecialchars($participant['full_name']) ?></p>
                    <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($participant['phone']) ?></p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="full_name">الاسم الكامل:</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?= htmlspecialchars($participant['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="age">العمر (بين 4 و 18 سنة):</label>
                        <input type="number" id="age" name="age" min="4" max="18" 
                               value="<?= $participant['age'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="sheikh_name">اسم شيخ المحفظ (اختياري):</label>
                        <input type="text" id="sheikh_name" name="sheikh_name" 
                               value="<?= $participant['sheikh_name'] ? htmlspecialchars($participant['sheikh_name']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="parts_count">عدد الأجزاء:</label>
                        <select id="parts_count" name="parts_count" required>
                            <option value="القرآن كاملاً (مجوداً)" <?= $participant['parts_count'] == 'القرآن كاملاً (مجوداً)' ? 'selected' : '' ?>>القرآن كاملاً (مجوداً)</option>
                            <option value="القرآن كاملاً (غير مجود)" <?= $participant['parts_count'] == 'القرآن كاملاً (غير مجود)' ? 'selected' : '' ?>>القرآن كاملاً (غير مجود)</option>
                            <option value="20 جزءًا" <?= $participant['parts_count'] == '20 جزءًا' ? 'selected' : '' ?>>20 جزءًا</option>
                            <option value="نص القرآن" <?= $participant['parts_count'] == 'نص القرآن' ? 'selected' : '' ?>>15 جزءًا (نص القرآن)</option>
                            <option value="اجزاء 10" <?= $participant['parts_count'] == 'اجزاء 10' ? 'selected' : '' ?>>10 أجزاء</option>
                            <option value="1-3 أجزاء" <?= $participant['parts_count'] == '1-3 أجزاء' ? 'selected' : '' ?>>1-3 أجزاء</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="phone">رقم الهاتف:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?= htmlspecialchars($participant['phone']) ?>" required>
                    </div>

                    <div class="action-btns">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                        <a href="admin_dashboard.php" class="btn btn-danger">
                            <i class="fas fa-times"></i> إلغاء والعودة
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>