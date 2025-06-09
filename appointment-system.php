<?php
/**
 * Plugin Name: سیستم نوبت دهی
 * Description: سیستم کامل نوبت‌دهی برای مشاوران با پشتیبانی از المنتور و فیلدهای داینامیک
 * Version:     1.0.1
 * Author:      همیار سایت
 * Text Domain: appointment-system
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// مسیرها و ورژن
define( 'AS_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'AS_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'AS_PLUGIN_VERSION', '1.0.1' );

// Autoloader
require_once AS_PLUGIN_DIR . 'includes/class-as-autoloader.php';
AS_Autoloader::run( AS_PLUGIN_DIR . 'includes/', 'AS_' );

// بارگذاری کلاس‌های اصلی
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-cpt-advisor.php';
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-cpt-appointment.php';
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-tax-department.php';

require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-advisor-info.php';
require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-faq.php';
require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-schedule.php';

require_once AS_PLUGIN_DIR . 'includes/appointments/class-as-appointment-manager.php';

// بارگذاری فایل زبان
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'appointment-system',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

// نمونه‌سازی کلاس‌ها
add_action( 'init', function() {
    new AS_CPT_Advisor();
    new AS_CPT_Appointment();
    new AS_Tax_Department();

    new AS_Meta_Advisor_Info();
    new AS_Meta_Faq();
    new AS_Meta_Schedule();

    new AS_Appointment_Manager();
}, 5 );

/**
 * ثبت و مهاجرت متافیلدها برای Dynamic Tags المنتور
 */
add_action( 'init', function() {
    static $migrated = false;
    $fields = [
        'about',
        'specialties',
        'address',
        'phone',
        'map',
        'psych_code',
        'price',
        'work_days',
        'start_time',
        'end_time',
        'session_length',
        'break_length',
        'exceptions',
        'faq',
    ];
    foreach ( $fields as $base ) {
        $old_key = '_as_' . $base;
        $new_key = 'as_'  . $base;
        $type    = in_array( $base, ['exceptions','faq'], true ) ? 'array' : 'string';

        // ثبت متافیلد قدیمی و جدید در REST
        register_post_meta( 'advisor', $old_key, [
            'single'        => true,
            'show_in_rest'  => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ] );
        register_post_meta( 'advisor', $new_key, [
            'single'        => true,
            'show_in_rest'  => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ] );

        // مهاجرت یک‌باره داده‌ها از قدیم به جدید
        if ( ! $migrated ) {
            $posts = get_posts( [
                'post_type'   => 'advisor',
                'numberposts' => -1,
                'post_status' => 'any',
                'fields'      => 'ids',
            ] );
            foreach ( $posts as $post_id ) {
                $old = get_post_meta( $post_id, $old_key, true );
                if ( '' !== $old && '' === get_post_meta( $post_id, $new_key, true ) ) {
                    update_post_meta( $post_id, $new_key, $old );
                }
            }
        }
    }
    $migrated = true;
}, 5 );

/**
 * نمایش متافیلدهای _as_… و as_… در المنتور
 */
add_filter( 'is_protected_meta', function( $protected, $meta_key ) {
    if ( 0 === strpos( $meta_key, '_as_' ) || 0 === strpos( $meta_key, 'as_' ) ) {
        return false;
    }
    return $protected;
}, 10, 2 );

// ---------------------------------------------------
// **توجه:** اطمینان حاصل کنید که هیچ فیلتر template_include
// برای پست‌تایپ advisor در این فایل یا includes وجود نداشته باشد!
// ---------------------------------------------------

// —————————————————————————————————
// بخش یکپارچگی با ووکامرس
// —————————————————————————————————

// افزودن اطلاعات نوبت به توضیحات آیتم سبد
add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item ) {
    if ( empty( $cart_item['as_date'] ) || empty( $cart_item['as_time'] ) ) {
        return $item_data;
    }
    // تابع تبدیل تاریخ میلادی به جلالی
    function as_gregorian_to_jalali( $gy, $gm, $gd ) {
        $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        $gy -= 1600; $gm -= 1; $gd -= 1;
        $day_no = 365*$gy + floor(($gy+3)/4) - floor(($gy+99)/100) + floor(($gy+399)/400)
            + $g_d_m[$gm] + $gd;
        $j_day_no = $day_no - 79;
        $j_np = floor($j_day_no / 12053);
        $j_day_no %= 12053;
        $jy = 979 + 33*$j_np + 4*floor($j_day_no/1461);
        $j_day_no %= 1461;
        if ( $j_day_no >= 366 ) {
            $jy += floor(($j_day_no-366)/365);
            $j_day_no = ($j_day_no-366) % 365;
        }
        $jm = 0;
        $j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
        while ( $j_day_no >= $j_days_in_month[$jm] ) {
            $j_day_no -= $j_days_in_month[$jm];
            $jm++;
        }
        $jd = $j_day_no + 1;
        return [ $jy, $jm+1, $jd ];
    }
    list( $y, $m, $d ) = explode( '-', $cart_item['as_date'] );
    $jalali = as_gregorian_to_jalali( (int)$y, (int)$m, (int)$d );
    $shamsi = sprintf( '%04d/%02d/%02d', $jalali[0], $jalali[1], $jalali[2] );

    $item_data[] = [
        'key'     => '<span style="color:#00897b;">تاریخ مشاوره</span>',
        'value'   => '<strong>' . esc_html( $shamsi ) . '</strong>',
        'display' => '',
    ];
    $item_data[] = [
        'key'     => '<span style="color:#00897b;">ساعت مشاوره</span>',
        'value'   => '<strong>' . esc_html( $cart_item['as_time'] ) . '</strong>',
        'display' => '',
    ];
    if ( ! empty( $cart_item['as_advisor_id'] ) ) {
        $name = get_the_title( $cart_item['as_advisor_id'] );
        $item_data[] = [
            'key'     => '<span style="color:#00897b;">مشاور</span>',
            'value'   => '<strong>' . esc_html( $name ) . '</strong>',
            'display' => '',
        ];
    }
    return $item_data;
}, 10, 2 );

// ذخیره داده‌های نوبت هنگام افزودن به سبد
add_filter( 'woocommerce_add_cart_item_data', function( $cart_item_data, $product_id, $variation_id ) {
    if (
        isset( $_GET['as_action'], $_GET['advisor_id'], $_GET['as_date'], $_GET['as_time'] ) &&
        'add_to_cart' === $_GET['as_action'] &&
        $advisor = intval( $_GET['advisor_id'] )
    ) {
        $cart_item_data['as_advisor_id']   = $advisor;
        $cart_item_data['as_date']         = sanitize_text_field( $_GET['as_date'] );
        $cart_item_data['as_time']         = sanitize_text_field( $_GET['as_time'] );
        $price = get_post_meta( $advisor, '_as_price', true );
        if ( is_numeric( $price ) ) {
            $cart_item_data['as_custom_price'] = floatval( $price );
        }
    }
    return $cart_item_data;
}, 10, 3 );

// override قیمت قبل از محاسبه totals
add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    foreach ( $cart->get_cart() as &$item ) {
        if ( isset( $item['as_custom_price'] ) ) {
            $item['data']->set_price( $item['as_custom_price'] );
        }
    }
}, 20 );

// افزودن متادیتا به آیتم‌های سفارش
add_action( 'woocommerce_checkout_create_order_line_item', function( $item, $cart_item_key, $values ) {
    if ( ! empty( $values['as_advisor_id'] ) ) {
        $item->add_meta_data( 'as_advisor_id', $values['as_advisor_id'], true );
        $item->add_meta_data( 'as_date',       $values['as_date'],       true );
        $item->add_meta_data( 'as_time',       $values['as_time'],       true );
    }
}, 10, 3 );

// تغییر نام محصول در سبد
add_filter( 'woocommerce_cart_item_name', function( $name, $cart_item, $key ) {
    if ( ! empty( $cart_item['as_advisor_id'] ) ) {
        $advisor = get_the_title( $cart_item['as_advisor_id'] );
        $name = 'رزرو نوبت با مشاور: <strong>' . esc_html( $advisor ) . '</strong>';
    }
    return $name;
}, 10, 3 );

// تغییر نام آیتم در صفحه سفارش
add_filter( 'woocommerce_order_item_name', function( $name, $item ) {
    $advisor = $item->get_meta( 'as_advisor_id' );
    if ( $advisor ) {
        $name = 'رزرو نوبت با مشاور: <strong>' . esc_html( get_the_title( $advisor ) ) . '</strong>';
    }
    return $name;
}, 10, 2 );

// حذف فیلد کدپستی از فرم تسویه
add_filter( 'woocommerce_checkout_fields', function( $fields ) {
    unset( $fields['billing']['billing_postcode'], $fields['shipping']['shipping_postcode'] );
    return $fields;
} );

// ایجاد خودکار نوبت پس از تغییر وضعیت سفارش
add_action( 'woocommerce_order_status_processing', 'as_create_appointment_from_order' );
add_action( 'woocommerce_order_status_completed',  'as_create_appointment_from_order' );

function as_create_appointment_from_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    foreach ( $order->get_items() as $item ) {
        $advisor_id = $item->get_meta( 'as_advisor_id' );
        $date       = $item->get_meta( 'as_date' );
        $time       = $item->get_meta( 'as_time' );
        if ( $advisor_id && $date && $time ) {
            // جلوگیری از نوبت تکراری
            $exists = get_posts( [
                'post_type'   => 'appointment',
                'meta_query'  => [
                    [ 'key' => '_as_advisor_id', 'value' => $advisor_id ],
                    [ 'key' => '_as_date',       'value' => $date ],
                    [ 'key' => '_as_time',       'value' => $time ],
                ],
                'post_status' => 'any',
                'fields'      => 'ids',
            ] );
            if ( empty( $exists ) ) {
                $app_id = wp_insert_post( [
                    'post_type'   => 'appointment',
                    'post_title'  => sprintf( 'نوبت مشاور %s در %s %s', get_the_title( $advisor_id ), $date, $time ),
                    'post_status' => 'publish',
                ] );
                if ( $app_id ) {
                    update_post_meta( $app_id, '_as_advisor_id',   $advisor_id );
                    update_post_meta( $app_id, '_as_date',         $date );
                    update_post_meta( $app_id, '_as_time',         $time );
                    update_post_meta( $app_id, '_as_order_id',     $order_id );
                    update_post_meta( $app_id, '_as_user_id',      $order->get_user_id() );
                    update_post_meta( $app_id, '_as_user_phone',   $order->get_billing_phone() );
                    update_post_meta( $app_id, '_as_user_email',   $order->get_billing_email() );
                    $status = ( 'completed' === $order->get_status() ) ? 'paid' : 'pending';
                    update_post_meta( $app_id, '_as_status',       $status );
                }
            }
        }
    }
}





/* تخصص‌ها */
add_shortcode( 'advisor_specialties', function( $atts ) {
    $id = isset( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
    $specs = get_post_meta( $id, 'as_specialties', true );
    if ( empty( $specs ) )
        $specs = get_post_meta( $id, '_as_specialties', true );

    if ( ! is_array( $specs ) ) return 'NO ARRAY';

    $html = '<ul class="as-specialties">';
    foreach ( $specs as $i => $s ) {
        $html .= '<li>' . esc_html( $i . ' ) ' . $s ) . '</li>';
    }
    $html .= '</ul>';
    return $html;
} );

/* FAQ */
add_shortcode( 'advisor_faq', function( $atts ) {
    $id  = isset( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
    $faq = get_post_meta( $id, 'as_faq', true );
    if ( empty( $faq ) )
        $faq = get_post_meta( $id, '_as_faq', true );

    if ( ! is_array( $faq ) ) return 'NO ARRAY';

    $html = '<div class="as-faq">';
    foreach ( $faq as $k => $item ) {
        $q = $item['question'] ?? '';
        $a = $item['answer']   ?? '';
        $html .= '<details open><summary>' . esc_html( $k . ' ) ' . $q ) .
            '</summary><div>' . wp_kses_post( $a ) . '</div></details>';
    }
    $html .= '</div>';
    return $html;
} );
// ───── ثبت Dynamic Tagها در المنتور ─────
add_action( 'elementor/dynamic_tags/register', function( $dynamic_tags ) {
    require_once AS_PLUGIN_DIR . 'elementor-tags/class-as-dynamic-tags.php';

    $dynamic_tags->register_tag( 'AS_Elementor\Tag_Specialties' );
    $dynamic_tags->register_tag( 'AS_Elementor\Tag_FAQ' );
} );
