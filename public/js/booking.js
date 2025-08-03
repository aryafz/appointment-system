jQuery(function ($) {
    let selectedDate = $('#as-date-selected').val();
    let selectedTime = '';
    const $form = $('.as-booking-form-modern');
    const advisorId = $form.data('advisor');
    const workDays = ('' + $form.data('work-days')).split(',').map(Number).filter(n => n >= 0 && n <= 6);
    const exceptions = ('' + $form.data('exceptions')).split(',').map(e => e.trim()).filter(Boolean);

    $('.as-date-btn').on('click', function() {
        $('.as-date-btn').removeClass('selected');
        $(this).addClass('selected');
        selectedDate = $(this).data('date-en');
        $('#as-date-selected').val(selectedDate);
        selectedTime = '';
        $('#as-book-btn').prop('disabled', true);
        $('#as-book-result').empty();
        loadTimes(selectedDate);
    });

    loadTimes(selectedDate);

    function loadTimes(date) {
        $('#as-time-row').html('<div class="as-loading">در حال بارگذاری ساعت‌ها...</div>');
        $.post(AS_BOOKING_DATA.ajax_url, {
            action: 'as_get_slots',
            nonce: AS_BOOKING_DATA.booking_nonce,
            advisor_id: advisorId,
            date: date
        }).done(res => {
            $('#as-time-row').empty();
            if (res.success && Array.isArray(res.data) && res.data.length) {
                res.data.forEach(time => {
                    const $btn = $('<button type="button" class="as-time-btn"></button>');
                    $btn.text(time);
                    $btn.on('click', function() {
                        $('.as-time-btn').removeClass('selected');
                        $(this).addClass('selected');
                        selectedTime = time;
                        $('#as-book-btn').prop('disabled', false);
                    });
                    $('#as-time-row').append($btn);
                });
            } else {
                $('#as-time-row').html('<div class="as-loading">ساعتی موجود نیست</div>');
            }
        }).fail(() => {
            $('#as-time-row').html('<div class="as-loading">خطا در دریافت ساعت‌ها</div>');
        });
    }

    $('#as-book-btn').on('click', function() {
        if (!selectedDate || !selectedTime) return;
        if (!AS_BOOKING_DATA?.add_to_cart_nonce) {
            $('#as-book-result').html('<span class="text-danger">خطا: امکان ثبت نیست.</span>');
            return;
        }
        const url = `${location.origin}${location.pathname}?as_action=add_to_cart`
            + `&advisor_id=${advisorId}`
            + `&date=${encodeURIComponent(selectedDate)}`
            + `&time=${encodeURIComponent(selectedTime)}`
            + `&nonce=${AS_BOOKING_DATA.add_to_cart_nonce}`;
        location.href = url;
    });
});
