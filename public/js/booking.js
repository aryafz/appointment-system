/**
 * Appointment-System — booking.js
 * آماده‌سازی فرم رزرو مشاور (فرانت‌اند)
 * آخرین ویرایش: ۱۴۰۴/۰۳/۱۰
 * - نمایش ساعت‌ها به‌صورت دکمه‌های انتخابی
 * - حفظ تمام بخش‌های اصلی کد
 */
jQuery(function ($) {
    /*───────────────── ۱) متادیتا ─────────────────*/
    const $form = $('.as-booking-form');
    if (!$form.length) return;

    const advisorId  = +$form.data('advisor');
    const sessionLen = +$form.data('session');  // دقیقه
    const breakLen   = +$form.data('break');    // دقیقه

    /* روزهای کاری (شکل «sat,mon» یا «0,2») → [عدد] */
    const dayKeyToNum = {
        sun: 0,  // یکشنبه
        mon: 1,  // دوشنبه
        tue: 2,  // سه‌شنبه
        wed: 3,  // چهارشنبه
        thu: 4,  // پنج‌شنبه
        fri: 5,  // جمعه
        sat: 6   // شنبه
    };

    const workWeekdays = ('' + $form.data('work-days'))
        .split(',').filter(Boolean)
        .map(k => /^[0-6]$/.test(k) ? +k : dayKeyToNum[k.trim().toLowerCase()])
        .filter(n => typeof n === 'number');

    const activeWeekdays = workWeekdays.length ? workWeekdays : [0, 1, 2, 3, 4, 5, 6];

    console.log('Active weekdays:', activeWeekdays);
    console.log('Work days from form:', $form.data('work-days'));

    /* تاریخ‌های استثناء (همگی به فرم YYYY/MM/DD) */
    const normalize = s => {
        const [y, m, d] = (s || '').split('/');
        return y ? `${y}/${('0' + (+m)).slice(-2)}/${('0' + (+d)).slice(-2)}` : '';
    };
    const exceptions = ('' + $form.data('exceptions'))
        .split(',').filter(Boolean).map(normalize);

    console.log('Exceptions:', exceptions);

    /*───────────────── ۲) تبدیل جلالی ← میلادی ─────────────────*/
    function j2g(jy, jm, jd) {
        jy = +jy; jm = +jm; jd = +jd;
        let gy = jy <= 979 ? 621 : 1600;
        jy -= jy <= 979 ? 0 : 979;
        let days = 365 * jy + ~~(jy / 33) * 8 + ~~((jy % 33 + 3) / 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30 + 186));
        gy += 400 * ~~(days / 146097);
        days %= 146097;
        if (days > 36524) {
            gy += 100 * ~~(--days / 36524);
            days %= 36524;
            if (days >= 365) days++;
        }
        gy += 4 * ~~(days / 1461);
        days %= 1461;
        if (days > 365) {
            gy += ~~((days - 1) / 365);
            days = (days - 1) % 365;
        }
        const sal = [0, 31, (gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        let gm, gd = days + 1;
        for (gm = 0; gm < 13; gm++) {
            if (gd <= sal[gm]) break;
            gd -= sal[gm];
        }
        return [gy, gm, gd];
    }

    /*───────────────── ۳) گزینه‌های DatePicker ─────────────────*/
    const dpOptions = {
        minDate: 'today',
        format: 'YYYY/MM/DD',
        dayRendering: function (info) {
            console.log('dayRendering invoked', info);

            if (!info || !info.year || !info.month || !info.day) {
                console.warn('dayRendering: اطلاعات تاریخ موجود نیست.', info);
                return { isValid: true };
            }

            const date = `${info.year}/${('0' + info.month).slice(-2)}/${('0' + info.day).slice(-2)}`;
            console.log('Processing date:', date);

            try {
                const [jy, jm, jd] = date.split('/').map(Number);
                if (!jy || !jm || !jd) {
                    console.warn('Invalid date format:', date);
                    return { isValid: true };
                }

                const [gy, gm, gd] = j2g(jy, jm, jd);
                const gregorianDate = new Date(gy, gm - 1, gd);
                const dayOfWeek = gregorianDate.getDay();

                console.log(`Date: ${date}, Weekday: ${dayOfWeek}, Active: ${activeWeekdays.includes(dayOfWeek)}`);

                if (exceptions.includes(normalize(date))) {
                    console.log('Exception date:', date);
                    return { isValid: false, className: 'as-exception-day' };
                }

                if (!activeWeekdays.includes(dayOfWeek)) {
                    console.log('Weekend day:', date, 'dayOfWeek:', dayOfWeek);
                    return { isValid: false, className: 'as-weekend-day' };
                }

                return { isValid: true };
            } catch (error) {
                console.error('Error in dayRendering:', error, 'for date:', date);
                return { isValid: true };
            }
        }
    };

    /*─ ۳-الف) شناسایی و راه‌اندازی DatePicker ─*/
    if (window.jalaliDatepicker?.startWatch) {
        jalaliDatepicker.startWatch();
        jalaliDatepicker.updateOptions(dpOptions);
        document.getElementById('as-date')
            ?.addEventListener('jdp:change', e => chooseDate(e.target.value));
    } else if ($.fn.jalaliDatepicker) {
        $('#as-date').jalaliDatepicker({
            ...dpOptions,
            closeOnDateSelect: true,
            onSelect: chooseDate
        });
    } else {
        console.error('JalaliDatePicker library not loaded.');
    }

    /*───────────────── ۴) واکنش به انتخاب تاریخ ─────────────────*/
    let selectedTime = '';

    function chooseDate(jVal) {
        let g = '';
        const $btnContainer = $('#as-time-buttons').empty();
        selectedTime = '';
        $('#as-book-btn').prop('disabled', true);
        $('#as-book-result').empty();

        if (!jVal) {
            $btnContainer.append('<span class="text-muted">ابتدا تاریخ را انتخاب کنید</span>');
            return;
        }
        const [jy, jm, jd] = jVal.split('/');
        if (jd) {
            const [gy, gm, gd] = j2g(jy, jm, jd);
            g = `${gy}-${('0' + gm).slice(-2)}-${('0' + gd).slice(-2)}`;
        }
        $('#as-date-miladi').val(g);
        $btnContainer.append('<span class="text-muted">در حال بارگذاری...</span>');

        if (!AS_BOOKING_DATA?.ajax_url || !AS_BOOKING_DATA?.booking_nonce) {
            console.error('AS_BOOKING_DATA ناقص است');
            $btnContainer.html('<span class="text-danger">خطا: تنظیمات ناقص</span>');
            return;
        }

        $.post(AS_BOOKING_DATA.ajax_url, {
            action: 'as_get_slots',
            nonce: AS_BOOKING_DATA.booking_nonce,
            advisor_id: advisorId,
            date: g
        }).done(res => {
            $btnContainer.empty();
            const def = 'یک ساعت انتخاب کنید';
            if (res.success && Array.isArray(res.data) && res.data.length) {
                res.data.forEach(time => {
                    const $btn = $(`<div role="button" tabindex="0" class="time-slot">${time}</div>`);
                    $btn.on('click keypress', e => {
                        if (e.type === 'click' || e.key === 'Enter') {
                            selectedTime = time;
                            $('.time-slot').removeClass('active');
                            $btn.addClass('active');
                            $('#as-book-btn').prop('disabled', false);
                        }
                    });
                    $btnContainer.append($btn);
                });
            } else {
                $btnContainer.html('<span class="text-muted">ساعت آزادی وجود ندارد</span>');
            }
        }).fail(() => {
            $btnContainer.html('<span class="text-danger">خطا در دریافت اطلاعات</span>');
        });
    }

    /*───────────────── ۵) تعاملات فرم ─────────────────*/
    // در این نسخه دیگر از select استفاده نمی‌شود،
    // بنابراین فقط دکمه رزرو را مدیریت می‌کنیم.
    $('#as-book-btn').on('click', () => {
        const gDate = $('#as-date-miladi').val();
        if (!gDate || !selectedTime) return;
        if (!AS_BOOKING_DATA?.add_to_cart_nonce) {
            $('#as-book-result')
                .text('خطا: امکان افزودن به سبد نیست.')
                .css('color', 'red');
            return;
        }
        const url = `${location.origin}${location.pathname}?as_action=add_to_cart`
            + `&advisor_id=${advisorId}`
            + `&date=${encodeURIComponent(gDate)}`
            + `&time=${encodeURIComponent(selectedTime)}`
            + `&nonce=${AS_BOOKING_DATA.add_to_cart_nonce}`;
        location.href = url;
    });
});
