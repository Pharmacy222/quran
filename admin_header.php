<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success_message'] ?>
        <?php unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error_message'] ?>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<nav>
    <a href="admin_dashboard.php">لوحة التحكم</a>
    <a href="results.php">إدارة النتائج</a>
    <a href="export.php">تصدير البيانات</a>
    <a href="logout.php">تسجيل الخروج</a>
</nav>