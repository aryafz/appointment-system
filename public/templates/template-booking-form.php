<?php
use Morilog\Jalali\Jalalian;

$id = intval($id);
$price = get_post_meta($id, '_as_price', true);
$work_days = get_post_meta($id, '_as_work_days', true);
$exceptions = get_post_meta($id, '_as_exceptions', true);
$session_len = get_post_meta($id, '_as_session_length', true) ?: 45;
$break_len = get_post_meta($id, '_as_break_length', true) ?: 15;

// روزهای هفته به انگلیسی و اندیس عددی برای mapping هوشمند
$wp_days = [
    0 => 'sunday',    // یکشنبه
    1 => 'monday',    // دوشنبه
    2 => 'tuesday',   // سه‌شنبه
    3 => 'wednesday', // چهارشنبه
    4 => 'thursday',  // پنج‌شنبه
    5 => 'friday',    // جمعه
    6 => 'saturday',  // شنبه
];
$fa_days = [
    0 => 'یکشنبه',
    1 => 'دوشنبه',
    2 => 'سه‌شنبه',
    3 => 'چهارشنبه',
    4 => 'پنجشنبه',
    5 => 'جمعه',
    6 => 'شنبه'
];

// تبدیل روزهای کاری به اندیس عددی معتبر
$work_days_index = [];
if (is_array($work_days)) {
    foreach ($work_days as $d) {
        // اعداد مستقیم
        if (is_numeric($d)) {
            $n = intval($d);
            if ($n >= 0 && $n <= 6) $work_days_index[] = $n;
        }
        // انگلیسی مثل 'mon' یا 'monday'
        elseif (is_string($d)) {
            $d = strtolower($d);
            if ($d == 'sat' || $d == 'شنبه') $work_days_index[] = 6;
            elseif ($d == 'sun' || $d == 'یکشنبه') $work_days_index[] = 0;
            elseif ($d == 'mon' || $d == 'دوشنبه') $work_days_index[] = 1;
            elseif ($d == 'tue' || $d == 'سه‌شنبه') $work_days_index[] = 2;
            elseif ($d == 'wed' || $d == 'چهارشنبه') $work_days_index[] = 3;
            elseif ($d == 'thu' || $d == 'پنجشنبه') $work_days_index[] = 4;
            elseif ($d == 'fri' || $d == 'جمعه') $work_days_index[] = 5;
            elseif (in_array($d, $wp_days)) {
                $key = array_search($d, $wp_days);
                if ($key !== false) $work_days_index[] = $key;
            }
        }
    }
}
$work_days_index = array_unique($work_days_index);

// استثناها آرایه
if (!$exceptions || !is_array($exceptions)) $exceptions = [];

$now = new DateTime('now');
$show_days = [];
$max_days = 24;
$i = 0;
while (count($show_days) < 10 && $i < $max_days) {
    $tmp = clone $now;
    $tmp->modify("+$i day");
    $gy = $tmp->format('Y');
    $gm = $tmp->format('m');
    $gd = $tmp->format('d');
    $weekday = (int)$tmp->format('w');
    if (in_array($weekday, $work_days_index)) {
        if (function_exists('wp_jdate')) {
            $fa_monthday = wp_jdate('j F', $tmp->getTimestamp());
        } else if (class_exists('\Morilog\Jalali\Jalalian')) {
            $jalali = Jalalian::fromDateTime($tmp);
            $fa_monthday = $jalali->format('j F');
        } else {
            $fa_monthday = $tmp->format('d/m');
        }
        $fa_day = $fa_days[$weekday];
        $show_days[] = [
            'gy' => $gy,
            'gm' => $gm,
            'gd' => $gd,
            'fa_label' => $fa_day,
            'fa_date' => $fa_monthday,
            'date_en' => $tmp->format('Y-m-d'),
            'date_fa' => $fa_day . ' ' . $fa_monthday,
        ];
    }
    $i++;
}

$avatar_url = get_the_post_thumbnail_url($id, 'thumbnail');
if (!$avatar_url) {
    $avatar_html = '<i class="bi bi-person-bounding-box as-booking-avatar"></i>';
} else {
    $avatar_html = '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr(get_the_title($id)) . '" class="as-booking-avatar" width="56" height="56" loading="lazy">';
}
?>
<div class="as-booking-form-modern"
     data-advisor="<?php echo esc_attr($id); ?>"
     data-session="<?php echo esc_attr($session_len); ?>"
     data-break="<?php echo esc_attr($break_len); ?>"
     data-work-days="<?php echo esc_attr(implode(',', $work_days_index)); ?>"
     data-exceptions="<?php echo esc_attr(implode(',', $exceptions)); ?>">
    <div class="as-row as-profile-row mb-2">
        <?php echo $avatar_html; ?>
        <h3 class="as-advisor-name"><?php echo get_the_title($id); ?></h3>
    </div>
    <div class="as-booking-price mb-2">
        <span>هزینه هر جلسه:</span>
        <span class="price"><?php echo number_format($price); ?> <span>تومان</span></span>
    </div>
    <div class="as-date-row">
        <?php foreach ($show_days as $ix => $day): ?>
            <button class="as-date-btn<?php if($ix==0) echo ' selected'; ?>"
                    data-date-en="<?php echo esc_attr($day['date_en']); ?>"
                    data-date-fa="<?php echo esc_attr($day['date_fa']); ?>">
                <div class="as-date-label"><?php echo $day['fa_label']; ?></div>
                <div class="as-date-value"><?php echo $day['fa_date']; ?></div>
            </button>
        <?php endforeach; ?>
    </div>
    <input type="hidden" id="as-date-selected" value="<?php echo esc_attr($show_days[0]['date_en']); ?>">
    <div class="as-time-row" id="as-time-row">
        <div class="as-loading">در حال بارگذاری ساعت‌ها...</div>
    </div>
    <button id="as-book-btn" class="btn btn-success w-100 mt-4" disabled>رزرو نوبت</button>
    <div id="as-book-result" class="mt-3"></div>
</div>
