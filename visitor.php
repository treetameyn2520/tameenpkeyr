<?php
// بيانات التليجرام
$token = '7647127310:AAEL_VzCr1wTh26Exczu6IPuFgEsH4HHHVE';
$chat_id = '6454807559';

// استخراج بيانات الزائر
$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$time = date('Y-m-d H:i:s');

// رسالة التنبيه
$message = "
👀 <b>زيارة جديدة</b>
🌐 IP: <code>$ip</code>
📱 المتصفح: <i>$userAgent</i>
🕒 الوقت: <b>$time</b>
";

// إرسال إلى تليجرام
$url = "https://api.telegram.org/bot$token/sendMessage";

$postData = [
    'chat_id' => $chat_id,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);
curl_close($ch);

// يمكن تجاهل الإخراج أو طباعته للتصحيح
