<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// معالجة البحث والتصفية
$search = $_GET['search'] ?? '';
$position_filter = $_GET['position'] ?? [];

// جلب المراكز المتاحة للتصفية
$positions_query = $conn->query("SELECT DISTINCT position FROM results WHERE position IS NOT NULL ORDER BY position");
$positions = $positions_query->fetchAll(PDO::FETCH_ASSOC);

// بناء الاستعلام الأساسي المعدل
$sql = "SELECT p.id as p_id, p.full_name, p.age, p.phone, 
               r.id as r_id, r.score, r.level, r.position, r.notes
        FROM participants p
        JOIN results r ON p.id = r.participant_id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.full_name LIKE ? OR p.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($position_filter)) {
    $placeholders = implode(',', array_fill(0, count($position_filter), '?'));
    $sql .= " AND r.position IN ($placeholders)";
    $params = array_merge($params, $position_filter);
}

$sql .= " ORDER BY 
          CASE WHEN r.position IS NULL THEN 9999 ELSE r.position END ASC, 
          r.score DESC, 
          p.full_name ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "خطأ في جلب البيانات: " . $e->getMessage();
    header("Location: results.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة النتائج</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image-modified.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>

        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --black: #000000;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
            --transition: all 0.3s ease-in-out;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

       
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 0.8rem 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--light-gray);
        }

        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .card-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            background-color: var(--light);
            border-radius: 0 0 8px 8px;
        }

        .filter-group {
            margin-bottom: 0;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark);
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .multi-select {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 48px;
            padding: 4px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary);
            border: none;
            border-radius: 4px;
            color: white;
            padding: 2px 8px;
            margin: 3px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 4px;
        }

        .filter-btn, .reset-btn {
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
        }

        .reset-btn {
            background-color: var(--gray);
        }

        .reset-btn:hover {
            background-color: #5a6268;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            font-size: 1rem;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .btn-secondary {
            background-color: var(--gray);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 0 0 8px 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 1.25rem;
            text-align: center;
            border-bottom: 1px solid var(--light-gray);
        }

        th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: rgba(248, 249, 250, 0.5);
        }

        tr:hover {
            background-color: rgba(233, 236, 239, 0.7);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50px;
            transition: var(--transition);
        }

        .badge-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .badge-success {
            background-color: var(--success);
            color: var(--white);
        }

        .badge-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .medal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .medal:hover {
            transform: scale(1.1);
        }

        .gold {
            background: linear-gradient(135deg, var(--gold), #daa520);
            color: var(--black);
        }

        .silver {
            background: linear-gradient(135deg, var(--silver), #a8a8a8);
            color: var(--white);
        }

        .bronze {
            background: linear-gradient(135deg, var(--bronze), #b87333);
            color: var(--white);
        }

        .action-btns {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            transform: translateY(-2px) scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .edit-btn {
            background-color: var(--info);
        }

        .delete-btn {
            background-color: var(--danger);
        }

        .sms-btn {
            background-color: var(--success);
        }

        .certificate-btn {
            background-color: var(--warning);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--light-gray);
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        @media print {
            .navbar, .card-header, .filters, .action-btn {
                display: none !important;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            th, td {
                padding: 0.5rem;
                border: 1px solid #ddd;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: space-around;
                flex-wrap: wrap;
            }

            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
        }

        /* تأثيرات إضافية */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* شريط التحميل */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary);
            transform-origin: 0%;
            z-index: 1100;
            display: none;
        }

        .select2-container--default .select2-selection--single {
            height: auto;
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-right: 0;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary);
        }
    </style>

 
<meta name="description" content="نتائج خاصه للتسجيل فى مسابقه القرآن الكريم لمسجد الشهيد (أهل الله).">
</head>
<body>
    <div class="loading-bar" id="loadingBar"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="nav-brand">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <ul class="nav-links">
                <li>
                    <a href="results.php" class="active">
                        <i class="fas fa-chart-bar"></i> النتائج
                    </a>
                </li>
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-users"></i> المشاركون
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-filter"></i> تصفية النتائج</h2>
            </div>
            <div class="filters">
                <div class="filter-group">
                    <label>البحث بالمشارك:</label>
                    <input type="text" name="search" placeholder="اسم المشارك أو رقم الهاتف" 
                           value="<?= htmlspecialchars($search) ?>" id="searchInput" form="filter-form">
                </div>
                <div class="filter-group">
                    <label>المركز:</label>
                    <select name="position[]" id="positionSelect" multiple="multiple" form="filter-form" class="multi-select">
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?= $pos['position'] ?>" 
                                <?= in_array($pos['position'], (array)$position_filter) ? 'selected' : '' ?>>
                                المركز <?= $pos['position'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="align-self: flex-end;">
                    <button type="submit" form="filter-form" class="btn btn-primary filter-btn">
                        <i class="fas fa-filter"></i> تطبيق التصفية
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn btn-secondary reset-btn">
                        <i class="fas fa-undo"></i> إعادة تعيين
                    </button>
                </div>
            </div>
            <form id="filter-form" method="GET" action="results.php"></form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> إضافة/تعديل نتيجة</h2>
            </div>
            <div class="card-body">
                <form id="resultForm" method="POST" action="save_result.php">
                    <input type="hidden" id="resultId" name="result_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>اسم المشارك:</label>
                            <select name="participant_id" id="participantSelect" required class="form-control">
                                <option value="">اختر المشارك</option>
                                <?php
                                $participants = $conn->query("SELECT id, full_name FROM participants ORDER BY full_name");
                                while ($p = $participants->fetch()):
                                ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>النقاط:</label>
                            <input type="number" name="score" id="scoreInput" step="0.01" min="0" max="100" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>المركز:</label>
                            <input type="number" name="position" id="positionInput" min="1" max="20" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ملاحظات:</label>
                            <textarea name="notes" id="notesTextarea" placeholder="أي ملاحظات إضافية عن المشارك..." class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="button" id="resetBtn" class="btn btn-secondary mr-2">
                            <i class="fas fa-undo"></i> إعادة تعيين
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ النتيجة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-trophy"></i> قائمة النتائج</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-secondary print-btn">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> تصدير لإكسل
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="resultsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المشارك</th>
                            <th>المعلومات</th>
                            <th>النتيجة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="5" class="no-results">
                                    <i class="fas fa-info-circle fa-2x"></i>
                                    <h3>لا توجد نتائج متاحة</h3>
                                    <p>استخدم أدوات التصفية للعثور على النتائج</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                    <small>العمر: <?= $row['age'] ?></small>
                                </td>
                                <td>
                                    <div><i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['score']): ?>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <?php if ($row['position']): ?>
                                                <span class="medal <?= 
                                                    $row['position'] == 1 ? 'gold' : 
                                                    ($row['position'] == 2 ? 'silver' : 'bronze') 
                                                ?>">
                                                    <?= $row['position'] ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge badge-primary ml-2">
                                                <?= $row['score'] ?> نقاط
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-warning">لا توجد نتيجة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="#" class="action-btn edit-btn" title="تعديل" 
                                           data-id="<?= $row['r_id'] ?>" 
                                           data-participant="<?= $row['p_id'] ?>"
                                           data-score="<?= $row['score'] ?>"
                                           data-position="<?= $row['position'] ?>"
                                           data-notes="<?= htmlspecialchars($row['notes']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_result.php?id=<?= $row['r_id'] ?>" 
                                           class="action-btn delete-btn" title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <?php if ($row['position'] && $row['position'] <= 3): ?>
                                            <a href="send_sms.php?phone=<?= urlencode($row['phone']) ?>&name=<?= urlencode($row['full_name']) ?>&position=<?= $row['position'] ?>" 
                                               class="action-btn sms-btn" title="إرسال تهنئة">
                                                <i class="fas fa-sms"></i>
                                            </a>
                                            <a href="generate_certificates.php?id=<?= $row['p_id'] ?>" 
                                               class="action-btn certificate-btn" title="إنشاء شهادة">
                                                <i class="fas fa-certificate"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- إضافة المكتبات المطلوبة -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#participantSelect').select2({
                placeholder: "اختر المشارك",
                allowClear: true,
                language: {
                    noResults: function() {
                        return "لا توجد نتائج";
                    }
                }
            });

            $('.multi-select').select2({
                placeholder: "اختر الخيارات",
                allowClear: true,
                closeOnSelect: false,
                language: {
                    noResults: function() {
                        return "لا توجد نتائج";
                    }
                }
            });

            $('#searchInput, #positionSelect').on('input change', function() {
                $('#filter-form').submit();
            });

            $('#resetBtn').click(function() {
                $('#resultForm')[0].reset();
                $('#participantSelect').val(null).trigger('change');
                $('#resultId').val('');
                $('#submitBtn').html('<i class="fas fa-save"></i> حفظ النتيجة');
            });

            $(document).on('click', '.edit-btn', function(e) {
                e.preventDefault();
                
                const resultId = $(this).data('id');
                const participantId = $(this).data('participant');
                const score = $(this).data('score');
                const position = $(this).data('position');
                const notes = $(this).data('notes');
                
                $('#resultId').val(resultId);
                $('#participantSelect').val(participantId).trigger('change');
                $('#scoreInput').val(score);
                $('#positionInput').val(position);
                $('#notesTextarea').val(notes);
                
                $('#submitBtn').html('<i class="fas fa-save"></i> تحديث النتيجة');
                
                $('html, body').animate({
                    scrollTop: $('#resultForm').offset().top - 100
                }, 500);
            });

            $('#resultForm').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                const submitBtn = $('#submitBtn');
                const originalBtnText = submitBtn.html();
                
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
                showLoading();
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: $(this).attr('method'),
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ غير معروف'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'حدث خطأ أثناء الاتصال بالخادم';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            if (xhr.status === 0) {
                                errorMsg = 'تعذر الاتصال بالخادم. يرجى التحقق من اتصال الشبكة.';
                            } else if (xhr.status === 500) {
                                errorMsg = 'خطأ في الخادم الداخلي. يرجى المحاولة لاحقًا.';
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMsg
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        submitBtn.html(originalBtnText);
                        hideLoading();
                    }
                });
            });

                $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const deleteUrl = $(this).attr('href');
                
                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    text: "لن تتمكن من التراجع عن هذا الإجراء!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، احذف!',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoading();
                        $.ajax({
                            url: deleteUrl,
                            type: 'GET',
                            success: function(response) {
                                if(response.success) {
                                    Swal.fire(
                                        'تم الحذف!',
                                        response.message,
                                        'success'
                                    ).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'خطأ!',
                                        response.message,
                                        'error'
                                    );
                                }
                                hideLoading();
                            },
                            error: function() {
                                Swal.fire(
                                    'خطأ!',
                                    'حدث خطأ أثناء الاتصال بالخادم',
                                    'error'
                                );
                                hideLoading();
                            }
                        });
                    }
                });
            });
        });

        // إعادة تعيين الفلاتر
        function resetFilters() {
            $('#filter-form')[0].reset();
            $('.multi-select').val(null).trigger('change');
            $('#filter-form').submit();
        }

        // عرض شريط التحميل
        function showLoading() {
            $('#loadingBar').css({
                'display': 'block',
                'width': '70%',
                'transition': 'width 0.5s ease'
            });
        }

        // إخفاء شريط التحميل
        function hideLoading() {
            $('#loadingBar').css('width', '100%');
            setTimeout(() => {
                $('#loadingBar').css({
                    'display': 'none',
                    'width': '0'
                });
            }, 300);
        }

        // تصدير الجدول إلى Excel
        function exportToExcel() {
            showLoading();
            
            setTimeout(() => {
                const table = document.getElementById('resultsTable');
                const workbook = XLSX.utils.table_to_book(table);
                XLSX.writeFile(workbook, 'نتائج_المشاركين.xlsx');
                
                hideLoading();
            }, 500);
        }
    </script>
</body>
</html>


