jQuery(function($){
    var $picker = $('#as-exceptions-picker'),
        $hidden = $('#as-exceptions-hidden'),
        $tags   = $('#as-exceptions-tags'),
        $clear  = $('#as-clear-exceptions');

    // آرایه برای نگهداری تاریخ‌های انتخاب شده
    var selectedDates = [];

    // بارگذاری مقادیر اولیه از PHP
    if (typeof asInitialExceptions !== 'undefined' && Array.isArray(asInitialExceptions)) {
        selectedDates = asInitialExceptions.slice(); // کپی آرایه
    }

    // تابع به‌روزرسانی نمایش و hidden input
    function updateDisplay() {
        // پاک کردن تگ‌های قبلی
        $tags.empty();

        // ایجاد تگ‌های جدید
        selectedDates.forEach(function(date) {
            if (date && date.trim()) {
                var $tag = $('<span class="as-exception-tag"></span>')
                    .text(date)
                    .append('<span class="as-tag-remove" style="margin-right:5px; cursor:pointer; color:red;">×</span>')
                    .data('date', date);
                $tags.append($tag);
            }
        });

        // به‌روزرسانی hidden input برای ارسال به سرور
        $hidden.val(selectedDates.join(','));

        console.log('Updated exceptions:', selectedDates); // برای دیباگ
    }

    // راه‌اندازی JalaliDatePicker
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch();

        // رویداد انتخاب تاریخ
        $(document).on('jdp:change', '#as-exceptions-picker', function(e) {
            var newDate = this.value;
            console.log('Date selected:', newDate); // برای دیباگ

            if (newDate && newDate.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
                // بررسی اینکه آیا این تاریخ قبلاً انتخاب شده یا نه
                var index = selectedDates.indexOf(newDate);
                if (index === -1) {
                    // اضافه کردن تاریخ جدید
                    selectedDates.push(newDate);
                } else {
                    // اگر قبلاً انتخاب شده، حذف کن
                    selectedDates.splice(index, 1);
                }
                updateDisplay();
            }

            // پاک کردن مقدار picker تا آماده انتخاب بعدی باشد
            setTimeout(function() {
                $picker.val('');
            }, 100);
        });
    }

    // حذف تگ تکی
    $tags.on('click', '.as-tag-remove', function(e) {
        e.preventDefault();
        var dateToRemove = $(this).parent().data('date');
        selectedDates = selectedDates.filter(function(date) {
            return date !== dateToRemove;
        });
        updateDisplay();
    });

    // پاک کردن همه
    $clear.on('click', function(e) {
        e.preventDefault();
        selectedDates = [];
        updateDisplay();
    });

    // نمایش اولیه
    updateDisplay();

    // اطمینان از ذخیره‌سازی قبل از submit
    $('form').on('submit', function() {
        $hidden.val(selectedDates.join(','));
        console.log('Form submitted with exceptions:', selectedDates); // برای دیباگ
    });
});





