<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// البحث والتصفية
$search = isset($_GET['search']) ? $_GET['search'] : '';
$age_filter = isset($_GET['age_filter']) ? $_GET['age_filter'] : '';
$parts_filter = isset($_GET['parts_filter']) ? $_GET['parts_filter'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;

// بناء استعلام SQL مع عوامل التصفية
$sql = "SELECT * FROM participants WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM participants WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR sheikh_name LIKE ?)";
    $count_sql .= " AND (full_name LIKE ? OR phone LIKE ? OR sheikh_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($age_filter)) {
    $sql .= " AND age = ?";
    $count_sql .= " AND age = ?";
    $params[] = $age_filter;
}

if (!empty($parts_filter)) {
    $sql .= " AND parts_count = ?";
    $count_sql .= " AND parts_count = ?";
    $params[] = $parts_filter;
}

$sql .= " ORDER BY registration_date DESC";

// التقسيم إلى صفحات
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$sql .= " LIMIT $offset, $per_page";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();

// الحصول على الخيارات المميزة للتصفية
$age_options = $conn->query("SELECT DISTINCT age FROM participants ORDER BY age")->fetchAll();
$parts_options = $conn->query("SELECT DISTINCT parts_count FROM participants ORDER BY parts_count")->fetchAll();

// إحصائيات
$total_participants = $conn->query("SELECT COUNT(*) FROM participants")->fetchColumn();
$today_participants = $conn->query("SELECT COUNT(*) FROM participants WHERE DATE(registration_date) = CURDATE()")->fetchColumn();
$new_this_week = $conn->query("SELECT COUNT(*) FROM participants WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المشرف</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="images (1)-modified.png">
    <meta name="theme-color" content="#6b8e23">
    <style>
        /* جميع أنماط CSS السابقة تبقى كما هي مع حذف .highlight */
        
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --secondary-dark: #1a252f;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* أنماط التنبيهات المحدثة */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out forwards;
            transform: translateX(120%);
            opacity: 0;
            position: relative;
            overflow: hidden;
            border-right: 5px solid;
        }

        .alert.success {
            background-color: #f0fdf4;
            color: #166534;
            border-color: #16a34a;
        }

        .alert.error {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #dc2626;
        }

        .alert.warning {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #f59e0b;
        }

        .alert.info {
            background-color: #eff6ff;
            color: #1e40af;
            border-color: #3b82f6;
        }

        .alert .icon {
            font-size: 1.5rem;
            margin-left: 10px;
        }

        .alert .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }

        .alert .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background-color: rgba(0,0,0,0.1);
            width: 100%;
        }

        .alert .progress-bar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 100%;
            width: 100%;
            animation: progress 5s linear forwards;
        }

        .alert.success .progress-bar::after {
            background-color: #16a34a;
        }

        .alert.error .progress-bar::after {
            background-color: #dc2626;
        }

        .alert.warning .progress-bar::after {
            background-color: #f59e0b;
        }

        .alert.info .progress-bar::after {
            background-color: #3b82f6;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* شريط التنقل المحسن */
        .admin-navbar {
            background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .logo i {
            margin-left: 10px;
            font-size: 1.8rem;
            color: var(--gold);
        }

        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background-color: var(--gold);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 80%;
        }

        .nav-links .active {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-links .active::after {
            width: 80%;
            background-color: var(--gold);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--info));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s;
            z-index: 100;
        }

        .user-avatar:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            color: var(--dark);
            padding: 0.8rem 1.5rem;
            display: block;
            font-weight: 500;
            transition: all 0.3s;
        }

        .dropdown-menu a:hover {
            background: var(--light-gray);
            color: var(--primary);
        }

        .dropdown-menu a i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }

        .notification-bell {
            color: white;
            font-size: 1.3rem;
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* المحتوى الرئيسي */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-header h1 {
            color: var(--secondary);
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            text-shadow: 0 2px 10px rgba(67, 97, 238, 0.2);
        }

        .dashboard-header h2 {
            color: var(--gray);
            font-size: 1.4rem;
            font-weight: 400;
            margin-top: 0.5rem;
        }

        /* بطاقات الإحصائيات المتميزة */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(255, 255, 255, 0));
            z-index: -1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .stat-card h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .stat-card p {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 1rem 0;
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            opacity: 0.2;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            transition: all 0.4s;
        }

        .stat-card:hover .stat-icon {
            opacity: 0.4;
            transform: scale(1.2);
        }

        .stat-card .stat-change {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .stat-card .stat-change.up {
            color: var(--success);
        }

        .stat-card .stat-change.down {
            color: var(--danger);
        }

        .stat-card .stat-change i {
            margin-left: 5px;
        }

        /* شريط الإجراءات المميز */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .action-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            transition: all 0.4s;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn i {
            margin-left: 0.8rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #d11450);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray), #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #3bb2d8);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 201, 240, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #e68a19);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(248, 150, 30, 0.4);
        }

        /* فلتر البحث المتميز */
        .filters-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            animation: slideUp 0.5s ease-out;
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filters-header h3 {
            color: var(--secondary);
            font-size: 1.4rem;
            font-weight: 700;
        }

        .filters-header .toggle-filters {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .filters-header .toggle-filters i {
            margin-left: 5px;
            transition: transform 0.3s;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            align-items: flex-end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .form-group input:focus,
        .form-group select:focus {
            background: rgba(0, 0, 0, 0.41);
            box-shadow: 0 0 0 3px rgba(67, 98, 238, 0.22),
                        inset 0 2px 5px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
            outline: none;
        }

        /* جدول البيانات المتميز */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 3rem;
            animation: fadeIn 0.6s ease-out;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            padding: 1.2rem;
            position: sticky;
            top: 0;
        }

        th:first-child {
            border-top-right-radius: 16px;
        }

        th:last-child {
            border-top-left-radius: 16px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover td {
            background: rgba(67, 97, 238, 0.03);
        }

        /* أزرار الإجراءات في الجدول */
        .action-btns {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s;
            min-width: 80px;
        }

        .btn-sm i {
            margin: 0;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--info), #3d7ec9);
            color: white;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(72, 149, 239, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger), #d11450);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(247, 37, 133, 0.3);
        }

        /* زر التصدير المتميز */
        .export-container {
            text-align: center;
            margin-top: 3rem;
            margin-bottom: 2rem;
        }

        .export-btn {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            border-radius: 50px;
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .export-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0)
            );
            transform: rotate(30deg);
            transition: all 0.5s;
        }

        .export-btn:hover::after {
            left: 100%;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(76, 175, 80, 0.4);
        }

        .export-btn i {
            margin-left: 0.8rem;
            font-size: 1.3rem;
        }

        /* التقسيم إلى صفحات */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            color: var(--dark);
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .page-link:hover {
            background: var(--light-gray);
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }

        .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* حالة عدم وجود نتائج */
        .no-results {
            padding: 3rem;
            text-align: center;
            color: var(--gray);
            font-size: 1.1rem;
        }

        .no-results i {
            font-size: 3rem;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
            display: block;
        }

        .no-results .btn {
            margin-top: 1.5rem;
        }

        /* تأثيرات الحركة */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* شريط التمرير المخصص */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* تحسينات للعرض على الأجهزة الصغيرة */
        @media (max-width: 1200px) {
            .nav-links a {
                padding: 1.2rem 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 992px) {
            .nav-links a {
                padding: 1.2rem 1rem;
                font-size: 0.95rem;
            }
            
            .filters-form {
                grid-template-columns: 1fr 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0.5rem 1rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-sm {
                width: 100%;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .dashboard-header h2 {
                font-size: 1.1rem;
            }

            .alert-container {
                left: 20px;
                right: 20px;
                max-width: none;
            }
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #e0e0e0;
            }
            
            .stat-card,
            .filters-container,
            .table-container,
            .action-bar {
                background: #1e1e1e;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                color: #e0e0e0;
            }
            
            .form-group input,
            .form-group select {
                background: #2d2d2d;
                color: #e0e0e0;
                border-color: #444;
            }
            .form-group label{
               color:rgba(255, 251, 235, 0.35);
            }
            
            tr:nth-child(even) {
                background-color: #252525;
            }
            
            tr:hover td {
                background-color: #333;
            }
            
            .page-link {
                background: #2d2d2d;
                color: #e0e0e0;
            }

            .alert.success {
                background-color: #052e16;
                color: #bbf7d0;
                border-color: #16a34a;
            }

            .alert.error {
                background-color: #450a0a;
                color: #fecaca;
                border-color: #dc2626;
            }

            .alert.warning {
                background-color: #431407;
                color: #fed7aa;
                border-color: #f59e0b;
            }

            .alert.info {
                background-color: #172554;
                color: #bfdbfe;
                border-color: #3b82f6;
            }
        }
    </style>
</head>
<body>
    <div class="alert-container"></div>

    <nav class="admin-navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-quran"></i>
                <span>مسابقة القرآن الكريم</span>
            </div>
            
            <ul class="nav-links">
                <li>
                    <a href="admin_dashboard.php" class="active">
                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                    </a>
                </li>
                <li>
                    <a href="participants.php">
                        <i class="fas fa-users"></i> المشاركون
                    </a>
                </li>
                <li>
                    <a href="results.php">
                        <i class="fas fa-chart-bar"></i> النتائج
                    </a>
                </li>
                <li>
                    <a href="export.php">
                        <i class="fas fa-file-export"></i> تصدير البيانات
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                </li>
            </ul>
            
            <div class="user-menu">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-avatar">
                    <?php echo substr($_SESSION['admin_username'], 0, 1); ?>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user-cog"></i> الملف الشخصي</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>لوحة تحكم مسابقة القرآن الكريم</h1>
            <h2>مرحباً بك <?php echo $_SESSION['admin_username']; ?></h2>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>إجمالي المسجلين</h3>
                <p><?php echo $total_participants; ?></p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i>
                    <span>12% زيادة عن الأسبوع الماضي</span>
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            <div class="stat-card">
                <h3>المسجلين اليوم</h3>
                <p><?php echo $today_participants; ?></p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i>
                    <span>5% زيادة عن البارحة</span>
                </div>
                <i class="fas fa-calendar-day stat-icon"></i>
            </div>
            <div class="stat-card">
                <h3>المسجلين هذا الأسبوع</h3>
                <p><?php echo $new_this_week; ?></p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i>
                    <span>18% زيادة عن الأسبوع الماضي</span>
                </div>
                <i class="fas fa-chart-line stat-icon"></i>
            </div>
            <div class="stat-card">
                <h3>آخر تحديث</h3>
                <p><?php echo date('H:i'); ?></p>
                <div class="stat-change">
                    <span>اليوم <?php echo date('Y/m/d'); ?></span>
                </div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>

        <div class="action-bar">
            <div class="action-group">
                <a href="import.php" class="btn btn-success">
                    <i class="fas fa-file-import"></i> استيراد بيانات
                </a>
            </div>
            
            <form method="post" action="delete_all_data.php" onsubmit="return confirm('هل أنت متأكد أنك تريد حذف جميع بيانات المشاركين؟ هذا الإجراء لا يمكن التراجع عنه!');">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> حذف جميع البيانات
                </button>
            </form>
        </div>

        <div class="filters-container">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> تصفية المشاركين</h3>
                <button class="toggle-filters">
                    إظهار/إخفاء الفلاتر
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <form method="GET" action="admin_dashboard.php">
                <div class="filters-form">
                    <div class="form-group">
                        <label>بحث بالمشاركين:</label>
                        <input type="text" name="search" placeholder="ابحث بالاسم أو الهاتف أو اسم الشيخ..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>تصفية حسب العمر:</label>
                        <select name="age_filter">
                            <option value="">كل الأعمار</option>
                            <?php foreach ($age_options as $option): ?>
                                <option value="<?php echo $option['age']; ?>" <?php echo $age_filter == $option['age'] ? 'selected' : ''; ?>>
                                    <?php echo $option['age']; ?> سنة
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>تصفية حسب الأجزاء:</label>
                        <select name="parts_filter">
                            <option value="">كل المستويات</option>
                            <?php foreach ($parts_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['parts_count']); ?>" <?php echo $parts_filter == $option['parts_count'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option['parts_count']); ?> جزء
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-filter"></i> تطبيق الفلتر
                        </button>
                        <a href="admin_dashboard.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                            <i class="fas fa-redo"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-responsive">
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
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($participants)): ?>
                            <tr>
                                <td colspan="8" class="no-results">
                                    <i class="fas fa-info-circle"></i>
                                    لا توجد نتائج مطابقة لبحثك
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($participants as $index => $participant): ?>
                                <tr>
                                    <td><?php echo $index + 1 + $offset; ?></td>
                                    <td><?php echo htmlspecialchars($participant['full_name']); ?></td>
                                    <td><?php echo $participant['age']; ?></td>
                                    <td><?php echo $participant['sheikh_name'] ? htmlspecialchars($participant['sheikh_name']) : '---'; ?></td>
                                    <td><?php echo htmlspecialchars($participant['parts_count']); ?></td>
                                    <td><?php echo htmlspecialchars($participant['phone']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($participant['registration_date'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                          
                                            </a>
                                            <a href="edit_participant.php?id=<?= $participant['id'] ?>" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <a href="delete_participant.php?id=<?= $participant['id'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('هل أنت متأكد من حذف المشارك <?php echo htmlspecialchars(addslashes($participant['full_name'])); ?>؟ هذا الإجراء لا يمكن التراجع عنه.');">
                                                <i class="fas fa-trash-alt"></i> حذف
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&age_filter=<?= $age_filter ?>&parts_filter=<?= $parts_filter ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                </li>
            <?php endif; ?>

            <?php 
            $start = max(1, $page - 2);
            $end = min($pages, $page + 2);
            
            if ($start > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&age_filter=<?= $age_filter ?>&parts_filter=<?= $parts_filter ?>">1</a>
                </li>
                <?php if ($start > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&age_filter=<?= $age_filter ?>&parts_filter=<?= $parts_filter ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pages ?>&search=<?= urlencode($search) ?>&age_filter=<?= $age_filter ?>&parts_filter=<?= $parts_filter ?>"><?= $pages ?></a>
                </li>
            <?php endif; ?>

            <?php if ($page < $pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&age_filter=<?= $age_filter ?>&parts_filter=<?= $parts_filter ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                </li>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="export-container">
            <form method="post" action="export.php">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="age_filter" value="<?= htmlspecialchars($age_filter) ?>">
                <input type="hidden" name="parts_filter" value="<?= htmlspecialchars($parts_filter) ?>">
                <button type="submit" class="export-btn">
                    <i class="fas fa-file-excel"></i> تصدير البيانات إلى Excel
                </button>
            </form>
        </div>
    </div>

    <script>
        function showAlert(type, message, duration = 5000) {
            const alertContainer = document.querySelector('.alert-container') || createAlertContainer();
            
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            
            const icons = {
                success: 'fa-circle-check',
                error: 'fa-circle-xmark',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info'
            };
            
            alert.innerHTML = `
                <i class="fa-solid ${icons[type]} icon"></i>
                <span>${message}</span>
                <button class="close-btn"><i class="fa-solid fa-xmark"></i></button>
                <div class="progress-bar"></div>
            `;
            
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.style.transform = 'translateX(0)';
                alert.style.opacity = '1';
            }, 10);
            
            const closeBtn = alert.querySelector('.close-btn');
            closeBtn.addEventListener('click', () => {
                closeAlert(alert);
            });
            
            if (duration) {
                setTimeout(() => {
                    closeAlert(alert);
                }, duration);
            }
        }

        function closeAlert(alert) {
            alert.style.transform = 'translateX(120%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }

        function createAlertContainer() {
            const container = document.createElement('div');
            container.className = 'alert-container';
            document.body.appendChild(container);
            return container;
        }

        // عرض التنبيهات من الجلسة إذا وجدت
        <?php if (isset($_SESSION['alert'])): ?>
            showAlert('<?= $_SESSION['alert']['type'] ?>', '<?= $_SESSION['alert']['message'] ?>');
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        // تأثيرات العائمة لبطاقات الإحصائيات
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.animation = 'float 3s ease-in-out infinite';
            });
            card.addEventListener('mouseleave', () => {
                card.style.animation = 'none';
            });
        });

        // تأثير النبض لزر التصدير
        const exportBtn = document.querySelector('.export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('mouseenter', () => {
                exportBtn.style.animation = 'pulse 1.5s infinite';
            });
            exportBtn.addEventListener('mouseleave', () => {
                exportBtn.style.animation = 'none';
            });
        }

        // تبديل عرض الفلاتر
        const toggleFilters = document.querySelector('.toggle-filters');
        const filtersForm = document.querySelector('.filters-form');
        
        if (toggleFilters && filtersForm) {
            toggleFilters.addEventListener('click', function(e) {
                e.preventDefault();
                filtersForm.style.display = filtersForm.style.display === 'none' ? 'grid' : 'none';
                const icon = this.querySelector('i');
                icon.style.transform = filtersForm.style.display === 'none' ? 'rotate(0deg)' : 'rotate(180deg)';
            });
        }

        // تأثير التمرير لتغيير لون شريط التنقل
        window.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY;
            const navbar = document.querySelector('.admin-navbar');
            if (scrollPosition > 10) {
                navbar.style.background = 'linear-gradient(135deg, var(--secondary-dark), var(--secondary))';
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            } else {
                navbar.style.background = 'linear-gradient(135deg, var(--secondary), var(--primary-dark))';
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.2)';
            }
        });

        // تنبيه عند محاولة مغادرة الصفحة مع وجود تغييرات غير محفوظة
        let formChanged = false;
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'لديك تغييرات غير محفوظة. هل أنت متأكد أنك تريد المغادرة؟';
            }
        });
    </script>
</body>
</html>