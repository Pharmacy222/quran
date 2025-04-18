<?php
session_start();
require_once 'db_connect.php';

// التحقق من تسجيل دخول المدير
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// التحقق من وجود معرّف النتيجة
// في بداية edit_result.php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("معرّف النتيجة غير صالح!");
}
$result_id = (int)$_GET['id'];

$result_id = intval($_GET['id']);

// جلب بيانات النتيجة مع معلومات المشارك
try {
    $stmt = $conn->prepare("SELECT r.*, p.full_name, p.phone, p.age 
                           FROM results r 
                           JOIN participants p ON r.participant_id = p.id 
                           WHERE r.id = ?");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch();

    if (!$result) {
        $_SESSION['error_message'] = "النتيجة غير موجودة";
        header("Location: results.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "خطأ في جلب البيانات: " . $e->getMessage();
    header("Location: results.php");
    exit();
}

// معالجة بيانات النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تنظيف البيانات المدخلة
    $score = filter_input(INPUT_POST, 'score', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $level = isset($_POST['level']) ? htmlspecialchars($_POST['level'], ENT_QUOTES, 'UTF-8') : '';
    
    $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_NUMBER_INT);
    $notes = isset($_POST['notes']) ? htmlspecialchars($_POST['notes'], ENT_QUOTES, 'UTF-8') : '';

    // التحقق من صحة البيانات
    if ($score === false || $level === null || $position === false) {
        $_SESSION['error_message'] = "البيانات المدخلة غير صالحة";
    } else {
        try {
            $conn->beginTransaction();
            
            // التحقق من عدم وجود مركز مكرر
            $check_stmt = $conn->prepare("SELECT id FROM results WHERE position = ? AND id != ?");
            $check_stmt->execute([$position, $result_id]);
            $existing = $check_stmt->fetch();
            
            if ($existing) {
                $_SESSION['error_message'] = "المركز محجوز بالفعل لمشارك آخر";
            } else {
                // استعلام التحديث بدون حقل updated_at
                $update = $conn->prepare("UPDATE results SET score=?, level=?, position=?, notes=? WHERE id=?");
                $update->execute([$score, $level, $position, $notes, $result_id]);
                $conn->commit();
                
                $_SESSION['success_message'] = "تم تحديث النتيجة بنجاح";
                header("Location: results.php");
                exit();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "خطأ في تحديث النتيجة: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل النتيجة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --text-color: #333;
            --border-color: #ddd;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--secondary-color), var(--dark-color));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: var(--transition);
        }

        .header .back-btn:hover {
            color: var(--light-color);
            transform: translateY(-50%) translateX(-3px);
        }

        .card {
            padding: 30px;
        }

        .participant-info {
            background-color: var(--light-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-right: 4px solid var(--primary-color);
        }

        .participant-info h3 {
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .participant-info h3 i {
            color: var(--primary-color);
        }

        .participant-info p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .participant-info p i {
            width: 20px;
            color: var(--dark-color);
        }

        .participant-info .phone {
            direction: ltr;
            text-align: right;
            display: inline-block;
            font-family: monospace;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--primary-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
            font-family: 'Tajawal', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
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
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn i {
            margin-left: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .action-btns {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .medal-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .gold {
            background: linear-gradient(135deg, #ffd700, #daa520);
            color: black;
        }

        .silver {
            background: linear-gradient(135deg, #c0c0c0, #a8a8a8);
            color: white;
        }

        .bronze {
            background: linear-gradient(135deg, #cd7f32, #b87333);
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 20px;
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
            .container {
                margin: 15px;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 20px;
                padding-right: 30px;
            }
            
            .card {
                padding: 20px;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="results.php" class="back-btn" title="العودة إلى النتائج">
                <i class="fas fa-arrow-right"></i>
            </a>
            <h1><i class="fas fa-edit"></i> تعديل نتيجة المشارك</h1>
        </div>
        
        <div class="card">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error_message'] ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="participant-info">
                <h3><i class="fas fa-user"></i> <?= htmlspecialchars($result['full_name']) ?></h3>
                <p><i class="fas fa-phone"></i> <strong>رقم الهاتف:</strong> <span class="phone"><?= htmlspecialchars($result['phone']) ?></span></p>
                <p><i class="fas fa-birthday-cake"></i> <strong>العمر:</strong> <?= htmlspecialchars($result['age']) ?></p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="score"><i class="fas fa-star"></i> النقاط:</label>
                    <input type="number" id="score" name="score" step="0.01" min="0" max="100" 
                           value="<?= htmlspecialchars($result['score']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="level"><i class="fas fa-chart-line"></i> المستوى:</label>
                    <select id="level" name="level" required>
                        <option value="مبتدئ" <?= $result['level'] == 'مبتدئ' ? 'selected' : '' ?>>مبتدئ</option>
                        <option value="متوسط" <?= $result['level'] == 'متوسط' ? 'selected' : '' ?>>متوسط</option>
                        <option value="متقدم" <?= $result['level'] == 'متقدم' ? 'selected' : '' ?>>متقدم</option>
                        <option value="متميز" <?= $result['level'] == 'متميز' ? 'selected' : '' ?>>متميز</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="position"><i class="fas fa-trophy"></i> المركز:
                        <?php if ($result['position']): ?>
                            <span class="medal-badge <?= 
                                $result['position'] == 1 ? 'gold' : 
                                ($result['position'] == 2 ? 'silver' : 'bronze') 
                            ?>">
                                <?= $result['position'] ?>
                            </span>
                        <?php endif; ?>
                    </label>
                    <input type="number" id="position" name="position" min="1" 
                           value="<?= htmlspecialchars($result['position']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="notes"><i class="fas fa-notes"></i> ملاحظات:</label>
                    <textarea id="notes" name="notes" placeholder="أدخل أي ملاحظات إضافية..."><?= htmlspecialchars($result['notes']) ?></textarea>
                </div>
                
                <div class="action-btns">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التعديلات
                    </button>
                    <a href="results.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // إضافة تأثيرات عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // التحقق من صحة البيانات قبل الإرسال
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const score = parseFloat(document.getElementById('score').value);
                const position = parseInt(document.getElementById('position').value);
                
                if (isNaN(score) || score < 0 || score > 100) {
                    alert('يجب أن تكون النقاط بين 0 و 100');
                    e.preventDefault();
                    return;
                }
                
                if (isNaN(position) || position < 1) {
                    alert('يجب أن يكون المركز رقمًا صحيحًا موجبًا');
                    e.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>