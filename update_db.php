<?php
require_once 'db_connect.php';

try {
    $sql = "ALTER TABLE results ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    $conn->exec($sql);
    echo "تم تحديث جدول النتائج بنجاح بإضافة عمود updated_at";
} catch (PDOException $e) {
    echo "خطأ في تحديث الجدول: " . $e->getMessage();
}