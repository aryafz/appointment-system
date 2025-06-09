<?php
/**
 * قالب فرم رزرو (template-booking-form.php)
 * آخرین ویرایش: ۱۴۰۴/۰۳/۱۰
 * - نمایش ساعت‌ها به‌صورت دکمه‌های قابل انتخاب
 * - استفاده از Bootstrap 5 و Flexbox برای UI/UX مدرن
 */

$id = intval( $id );
$price = get_post_meta( $id, '_as_price', true );

$work_days   = get_post_meta( $id, '_as_work_days', true )       ?: [];
$exceptions  = get_post_meta( $id, '_as_exceptions', true )     ?: [];
$session_len = get_post_meta( $id, '_as_session_length', true ) ?: 45;
$break_len   = get_post_meta( $id, '_as_break_length', true )   ?: 15;

$work_days_attr  = esc_attr( implode( ',', $work_days ) );
$exceptions_attr = esc_attr( implode( ',', $exceptions ) );
?>

<div class="as-booking-form p-4 border rounded shadow-sm"
     data-advisor="<?php echo esc_attr( $id ); ?>"
     data-session="<?php echo esc_attr( $session_len ); ?>"
     data-break="<?php echo esc_attr( $break_len ); ?>"
     data-work-days="<?php echo $work_days_attr; ?>"
     data-exceptions="<?php echo $exceptions_attr; ?>">

    <h3 class="mb-3"><?php echo get_the_title( $id ); ?></h3>
    <p class="mb-4">قیمت هر جلسه: <strong><?php echo number_format( $price ); ?> تومان</strong></p>

    <div class="mb-3">
        <label for="as-date" class="form-label">انتخاب تاریخ:</label>
        <input type="text"
               id="as-date"
               data-jdp
               data-jdp-format="YYYY/MM/DD"
               readonly
               class="form-control"/>
        <input type="hidden" id="as-date-miladi" value=""/>
    </div>

    <div class="mb-4">
        <label class="form-label d-block">ساعت‌های آزاد:</label>
        <div id="as-time-buttons" class="d-flex flex-wrap gap-2">
            <span class="text-muted">ابتدا تاریخ را انتخاب کنید</span>
        </div>
    </div>

    <button id="as-book-btn" class="btn btn-primary w-100" disabled>
        رزرو نوبت
    </button>
    <div id="as-book-result" class="mt-3"></div>
</div>

<style>
    .time-slot {
        min-width: 80px;
        padding: 0.5rem 1rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
        transition: background-color 0.2s, border-color 0.2s;
        cursor: pointer;
        text-align: center;
    }
    .time-slot:hover {
        background-color: #e2e6ea;
    }
    .time-slot.active {
        background-color: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }
    .as-exception-day .jdp-day {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }
    .as-weekend-day .jdp-day {
        background-color: #e2e3e5 !important;
        color: #6c757d !important;
    }
</style>
