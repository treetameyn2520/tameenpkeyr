<?php
// get_data.php

// رؤوس HTTP لمنع التخزين المؤقت
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// تعطيل حد وقت تنفيذ السكريبت لـ PHP (مهم لـ Long Polling)
set_time_limit(0); 

// تحديد مهلة للانتظار (بالثواني)
$max_wait_time = 29; // أقل بقليل من 30 ثانية لتجنب مهلة المتصفح الافتراضية

$data_file = 'form_submissions.json';
// الطابع الزمني لآخر تعديل من العميل
$last_modified_client = isset($_GET['last_modified']) ? intval($_GET['last_modified']) : 0; 
$start_time = time();

while (time() - $start_time < $max_wait_time) {
    if (file_exists($data_file)) {
        $current_modified_time = filemtime($data_file); // الحصول على طابع وقت آخر تعديل للملف

        // إذا كان الملف قد تم تعديله منذ آخر مرة طلبها العميل، أو إذا لم يكن لدى العميل طابع زمني (طلب أولي)
        if ($current_modified_time > $last_modified_client || $last_modified_client === 0) {
            $json_data = file_get_contents($data_file);
            $submissions = json_decode($json_data, true) ?? [];

            // في هذه الحالة، سنرسل جميع البيانات الموجودة
            $reversed_submissions = array_reverse($submissions);
            
            echo json_encode([
                'data' => $reversed_submissions,
                'last_modified' => $current_modified_time // إرسال الطابع الزمني الحالي للملف
            ]);
            exit(); // إرسال البيانات والخروج
        }
    } else {
        // إذا كان الملف غير موجود، أرسل استجابة فارغة.
        // لا تخرج فوراً، استمر في الانتظار، فقد يتم إنشاء الملف لاحقاً.
    }

    // لا توجد بيانات جديدة، انتظر قليلاً ثم تحقق مرة أخرى
    usleep(500000); // انتظر 500 مللي ثانية (نصف ثانية)
}

// إذا انتهت المهلة ولم يتم العثور على بيانات جديدة، أرسل استجابة فارغة
// مع الطابع الزمني لآخر تعديل (حتى لا يطلب العميل نفس البيانات مرة أخرى بشكل غير ضروري)
echo json_encode([
    'data' => [], 
    'last_modified' => $last_modified_client > 0 ? $last_modified_client : (file_exists($data_file) ? filemtime($data_file) : time())
]);
?>