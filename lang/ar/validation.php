<?php

return [

    /*
    |--------------------------------------------------------------------------
    | سطور لغة التحقق
    |--------------------------------------------------------------------------
    |
    | تحتوي سطور اللغة التالية على رسائل الخطأ الافتراضية المستخدمة من قبل
    | فئة المحقق. بعض هذه القواعد لها إصدارات متعددة مثل
    | قواعد الحجم. لا تتردد في تعديل كل من هذه الرسائل هنا.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url' => ':attribute ليس عنوان رابط صالح.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على بين :min و :max عناصر.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'string' => 'يجب أن يكون :attribute بين :min و :max أحرف.',
    ],
    'boolean' => 'يجب أن يكون حقل :attribute صحيحًا أو خطأ.',
    'confirmed' => 'تأكيد :attribute لا يتطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا يساوي :date.',
    'date_format' => ':attribute لا يتطابق مع التنسيق :format.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يكون :attribute :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'dimensions' => 'للصورة :attribute أبعاد غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'ends_with' => 'يجب أن ينتهي :attribute بواحد من التالي: :values.',
    'enum' => ':attribute المحدد غير صالح.',
    'exists' => ':attribute المحدد غير صالح.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
        'string' => 'يجب أن يكون :attribute أكبر من :value أحرف.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عناصر أو أكثر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أكبر من أو يساوي :value أحرف.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان بروتوكول الإنترنت صالح.',
    'ipv4' => 'يجب أن يكون :attribute عنوان بروتوكول الإنترنت الإصدار 4 صالح.',
    'ipv6' => 'يجب أن يكون :attribute عنوان بروتوكول الإنترنت الإصدار 6 صالح.',
    'json' => 'يجب أن يكون :attribute سلسلة جيسون صالحة.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من :value كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
        'string' => 'يجب أن يكون :attribute أقل من :value أحرف.',
    ],
    'lte' => [
        'array' => 'يجب أن يحتوي :attribute على أقصى :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أقل من أو يساوي :value أحرف.',
    ],
    'mac_address' => 'يجب أن يكون :attribute عنوان ماك صالح.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يكون حجم :attribute أكبر من :max كيلو بايت.',
        'numeric' => 'يجب ألا يكون :attribute أكبر من :max.',
        'string' => 'يجب ألا يكون :attribute أكبر من :max أحرف.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على الأقل :min عناصر.',
        'file' => 'يجب أن يكون حجم :attribute على الأقل :min كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
        'string' => 'يجب أن يكون :attribute على الأقل :min أحرف.',
    ],
    'multiple_of' => 'يجب أن يكون :attribute مضاعفًا لـ :value.',
    'not_in' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'not_regex' => 'تنسيق :attribute غير صالح.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'present' => 'يجب أن يكون حقل :attribute موجودًا.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless' => 'حقل :attribute محظور ما لم يكن :other موجودًا في :values.',
    'prohibits' => 'حقل :attribute يحظر وجود :other.',
    'regex' => 'تنسيق :attribute غير صالح.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على إدخالات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_unless' => 'حقل :attribute مطلوب ما لم يكن :other موجودًا في :values.',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجودًا.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا تكون :values موجودة.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلو بايت.',
        'numeric' => 'يجب أن يكون :attribute :size.',
        'string' => 'يجب أن يكون :attribute :size أحرف.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بواحد من التالي: :values.',
    'string' => 'يجب أن يكون :attribute سلسلة نصية.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صالحة.',
    'unique' => 'تم أخذ :attribute بالفعل.',
    'uploaded' => 'فشل تحميل :attribute.',
    'url' => 'يجب أن يكون :attribute عنوان رابط صالح.',
    'uuid' => 'يجب أن يكون :attribute معرف فريد عالمي صالح.',


    /*
    |--------------------------------------------------------------------------
    | سطور لغة التحقق المخصصة
    |--------------------------------------------------------------------------
    |
    | هنا يمكنك تحديد رسائل التحقق المخصصة للسمات باستخدام
    | الاتفاقية "السمة.القاعدة" لتسمية السطور. هذا يجعل من السهل
    | تحديد سطر لغة مخصص محدد لقاعدة سمة معينة.
    |
    */

    'custom' => [
        'اسم-السمة' => [
            'اسم-القاعدة' => 'رسالة-مخصصة',
        ],
        'firebase_key' => [
            'required_if' => __('messages.firebase_key_required'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | سمات التحقق المخصصة
    |--------------------------------------------------------------------------
    |
    | تُستخدم سطور اللغة التالية لاستبدال العنصر النائب للسمة
    | بشيء أكثر وضوحًا للقارئ مثل "عنوان البريد الإلكتروني" بدلاً
    | من "email". يساعدنا هذا ببساطة في جعل رسالتنا أكثر تعبيرًا.
    |
    */

    'attributes' => [
        'attribute' => 'السمة',
        'other' => 'الآخر',
        'value' => 'القيمة',
        'date' => 'التاريخ',
        'min' => 'الحد الأدنى',
        'max' => 'الحد الأقصى',
        'size' => 'الحجم',
        'firebase_key' => __('messages.lbl_firebase_key'),
        'description' => 'الوصف',
        'plan_id' => 'الخطة',
        'video_upload_type' => 'نوع تحميل الفيديو',
        'content_rating' => 'تقييم المحتوى',
        'title' => 'العنوان',
        'duration' => 'المدة',
        'release_date' => 'تاريخ الإصدار',
        'download_video_type' => 'نوع رابط تنزيل الفيديو',
        'video_download_type' => 'نوع رابط تنزيل الفيديو',
        'user_id' => 'المستخدم',
        'payment_date' => 'تاريخ الدفع',
        'code' => 'الرمز',
        'expire_date' => 'تاريخ الانتهاء',
        'start_date' => 'تاريخ البدء',
        'discount' => 'الخصم',
        'subscription_plan_ids' => 'خطة الاشتراك',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'value' => 'القيمة',
        'expiry_plan' => 'خطة الانتهاء',
        'upcoming' => 'القادم',
        'continue_watch' => 'متابعة المشاهدة',
        'meta_keywords' => 'الكلمات الرئيسية للموقع',
        'meta_title' => 'عنوان الميتا',
        'canonical_url' => 'رابط كانوني عالمي',
        'google_site_verification' => 'التحقق من موقع جوجل',
        'seo_image' => 'صورة تحسين محركات البحث',
        'short_description' => 'وصف الميتا للموقع',
    ],

];
