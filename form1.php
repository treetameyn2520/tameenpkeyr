<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'الرسالة فارغة']);
        exit;
    }

    $token = '7647127310:AAEL_VzCr1wTh26Exczu6IPuFgEsH4HHHVE';
    $chat_id = '6454807559';

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
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $error]);
    } else {
        echo json_encode(['status' => 'success']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
}
?>
