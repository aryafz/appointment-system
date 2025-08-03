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

// بارگذاری کلاس‌های اصلی (این require_once ها ممکن است با Autoloader تداخل داشته باشند یا زائد باشند)
// اگر Autoloader به درستی کار می کند و این کلاس ها را پیدا می کند، می توانید این خطوط را حذف کنید.
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-cpt-advisor.php';
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-cpt-appointment.php';
require_once AS_PLUGIN_DIR . 'includes/post-types/class-as-tax-department.php';

require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-advisor-info.php';
require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-faq.php'; // این فایل شامل تمام متاباکس های تکرارشونده است
require_once AS_PLUGIN_DIR . 'includes/meta-boxes/class-as-meta-schedule.php';

require_once AS_PLUGIN_DIR . 'includes/appointments/class-as-appointment-manager.php';


/**
 * ثبت استایل گرید مشاوران فقط برای سمت کاربر (Front-end)
 */
if ( ! function_exists( 'as_register_advisors_grid_style' ) ) {
    function as_register_advisors_grid_style() {

        // اگر در پیشخوان هستیم، جلوتر نرو
        if ( is_admin() ) {
            return;
        }

        // مطمئن شو ثابت‌ها تعریف شده‌اند
        if ( ! defined( 'AS_PLUGIN_URL' ) || ! defined( 'AS_PLUGIN_VERSION' ) ) {
            return;
        }

        wp_register_style(
            'as-advisors-grid',
            AS_PLUGIN_URL . 'public/css/advisors-grid.css',
            array(),              // بدون وابستگی
            AS_PLUGIN_VERSION
        );
        wp_register_style(
            'pblic-css',
            AS_PLUGIN_URL . 'public/css/public.css',
            array(),              // بدون وابستگی
            AS_PLUGIN_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'as_register_advisors_grid_style', 11 );

add_action('wp_enqueue_scripts', function() {
    // مسیر فایل استایل افزونه
    $css_url = plugins_url('public/css/public.css', __FILE__);
    wp_enqueue_style('as-public-css', $css_url, [], '1.0.0');
});


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
    new AS_Meta_Faq(); // این کلاس اکنون مسئول تمام متاباکس های تکرارشونده است
    new AS_Meta_Schedule();

    new AS_Appointment_Manager();
}, 5 );





add_action('elementor/widgets/widgets_registered', function($widgets_manager) {
    require_once AS_PLUGIN_DIR . 'elementor-widgets/class-as-widget-booking-button.php';
    $widgets_manager->register( new \AS_Widget_Booking_Button() );
});
add_action('elementor/widgets/widgets_registered', function($widgets_manager) {
    require_once AS_PLUGIN_DIR . 'elementor-widgets/class-as-widget-advisor-grid.php';
    require_once AS_PLUGIN_DIR . 'elementor-widgets/class-as-widget-advisor-filter.php';
    $widgets_manager->register( new \AS_Widget_Advisor_Grid() );
    $widgets_manager->register( new \AS_Widget_Advisor_Filter() );
});

add_action('wp_ajax_as_filter_advisors', 'as_filter_advisors_ajax');
add_action('wp_ajax_nopriv_as_filter_advisors', 'as_filter_advisors_ajax');



/*──────────────────── فیلتر مشاوران بر اساس تخصص (Ajax) ───────────────────*/
if ( ! function_exists( 'as_filter_advisors_ajax' ) ) :

    function as_filter_advisors_ajax() {

        /* ➊ امنیت: تأیید نانس  */
        if ( empty( $_POST['as_filter_nonce'] ) ||
            ! wp_verify_nonce( $_POST['as_filter_nonce'], 'as_filter_nonce' ) ) {
            wp_die( 'BAD_NONCE', 403 );
        }

        /* ➋ آرایهٔ تخصص‌ها (ممکن است فارسی یا لاتین باشد) */
        $raw = isset( $_POST['specialty'] ) ? (array) $_POST['specialty'] : [];

        $specialties = array_map(
            static function ( $s ) {
                $s = rawurldecode( $s );   // "%D9..." ➜ UTF-8
                return sanitize_title( $s ); // نام فارسی ➜ اسلاگ، لاتین همان اسلاگ
            },
            $raw
        );

        /* ➌ کوئری وردپرس */
        $args = [
            'post_type'      => 'advisor',
            'posts_per_page' => 12,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( $specialties ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'department', // ← اگر taxonomy دیگری است، عوض کن
                    'field'    => 'slug',
                    'terms'    => $specialties,
                ],
            ];
        }

        $q = new WP_Query( $args );

        /* ➍ خروجی HTML کارت‌ها */
        if ( $q->have_posts() ) {
            echo '<div class="as-advisor-grid">';
            while ( $q->have_posts() ) {
                $q->the_post();

                $img  = get_the_post_thumbnail_url( null, 'medium' )
                    ?: 'https://placehold.co/280x180?text=No+Image';
                $title = get_the_title();
                $tax   = get_the_terms( get_the_ID(), 'department' );
                $spec  = $tax && ! is_wp_error( $tax ) ? $tax[0]->name : '';

                $booking_page = get_page_by_path( 'booking' );
                $booking_url  = $booking_page ? get_permalink( $booking_page->ID )
                    : site_url( '/booking/' );
                $booking_url  = add_query_arg( 'advisor_id', get_the_ID(), $booking_url );
                ?>
                <div class="as-advisor-card">
                    <div class="as-card-image">
                        <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                    </div>
                    <div class="as-card-body">
                        <div class="as-card-title"><?php echo esc_html( $title ); ?></div>
                        <div class="as-card-specialty"><?php echo esc_html( $spec ); ?></div>
                        <a class="as-booking-btn" href="<?php echo esc_url( $booking_url ); ?>">
                            <?php _e( 'رزرو وقت مشاوره', 'appointment-system' ); ?>
                        </a>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        } else {
            echo '<div class="as-no-advisors">' . __( 'مشاوری یافت نشد.', 'appointment-system' ) . '</div>';
        }

        wp_reset_postdata();
        wp_die();
    }

    /* ثبت هوک‌های Ajax (کاربر وارد / میهمان) */
    add_action( 'wp_ajax_as_filter_advisors',        'as_filter_advisors_ajax' );
    add_action( 'wp_ajax_nopriv_as_filter_advisors', 'as_filter_advisors_ajax' );

endif;




function as_register_and_migrate_meta_fields() {
    // تغییر ورژن گزینه برای اطمینان از اجرای مجدد مهاجرت در صورت نیاز در آینده.
    // اگر قبلاً از 'as_elementor_meta_migrated' استفاده کردید، آن را به 'as_elementor_meta_migrated_v2' تغییر دهید
    // تا مطمئن شوید که migration یک بار دیگر اجرا می‌شود و فیلدهای جدید را هم شامل می‌شود.
    $migration_option_key = 'as_elementor_meta_migrated_v2';
    $is_migrated = get_option( $migration_option_key, false );

    $fields_to_register = [
        'about'           => 'string',
        'specialties'     => 'string_array', // آرایه‌ای از رشته‌ها (تخصص‌ها)
        'address'         => 'string',
        'phone'           => 'string',
        'map'             => 'string',
        'psych_code'      => 'string',
        'price'           => 'number', // نوع عددی برای قیمت
        'work_days'       => 'string_array', // آرایه‌ای از رشته‌ها (روزهای کاری)
        'start_time'      => 'string',
        'end_time'        => 'string',
        'session_length'  => 'integer', // نوع عددی برای طول جلسه
        'break_length'    => 'integer', // نوع عددی برای طول استراحت
        'exceptions'      => 'string_array', // آرایه‌ای از رشته‌ها (تاریخ‌های استثنا)
        'faq'             => 'object_array_faq', // آرایه‌ای از آبجکت‌ها (سوال و پاسخ)
        'work_experience' => 'object_array_name_only', // آرایه‌ای از آبجکت‌ها (فقط نام)
        'licenses'        => 'object_array_name_only', // آرایه‌ای از آبجکت‌ها (فقط نام)
        'approaches'      => 'object_array_name_only', // آرایه‌ای از آبجکت‌ها (فقط نام)
    ];

    foreach ( $fields_to_register as $base => $type_def ) {
        $old_key = '_as_' . $base;
        $new_key = 'as_'  . $base;

        $meta_args = [
            'single'        => true,
            'show_in_rest'  => [ // لازم است برای REST API Schema تعریف شود
                'schema' => [],
            ],
            'auth_callback' => '__return_true',
        ];

        // تعیین نوع و Schema بر اساس فیلد
        switch ( $type_def ) {
            case 'string_array':
                $meta_args['type'] = 'array';
                $meta_args['show_in_rest']['schema'] = [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ];
                break;
            case 'object_array_faq':
                $meta_args['type'] = 'array';
                $meta_args['show_in_rest']['schema'] = [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'question' => [ 'type' => 'string' ],
                            'answer'   => [ 'type' => 'string' ],
                        ],
                        'required'   => ['question', 'answer'], // فیلدهای الزامی در آبجکت
                    ],
                ];
                break;
            case 'object_array_name_only':
                $meta_args['type'] = 'array';
                $meta_args['show_in_rest']['schema'] = [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'name' => [ 'type' => 'string' ],
                        ],
                        'required'   => ['name'], // فیلدهای الزامی در آبجکت
                    ],
                ];
                break;
            case 'number':
                $meta_args['type'] = 'number';
                $meta_args['show_in_rest']['schema'] = [ 'type' => 'number' ];
                break;
            case 'integer':
                $meta_args['type'] = 'integer';
                $meta_args['show_in_rest']['schema'] = [ 'type' => 'integer' ];
                break;
            default: // 'string' (پیش‌فرض)
                $meta_args['type'] = 'string';
                $meta_args['show_in_rest']['schema'] = [ 'type' => 'string' ];
                break;
        }

        // ثبت متافیلد قدیمی و جدید در REST
        register_post_meta( 'advisor', $old_key, $meta_args );
        register_post_meta( 'advisor', $new_key, $meta_args );

        // مهاجرت یک‌باره داده‌ها از قدیم به جدید فقط اگر قبلا انجام نشده باشد
        if ( ! $is_migrated ) {
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
    // پرچم مهاجرت را تنظیم کنید تا دیگر اجرا نشود
    if ( ! $is_migrated ) {
        update_option( $migration_option_key, true );
    }
}



/**
 * ثبت و مهاجرت متافیلدها برای Dynamic Tags المنتور
 * این تابع فقط یک بار در فعالسازی افزونه یا اولین بارگذاری پس از تغییرات اجرا می شود.
 */
add_action( 'init', 'as_register_and_migrate_meta_fields', 5 );
register_activation_hook( __FILE__, 'as_register_and_migrate_meta_fields' ); // مطمئن شوید که این هوک برای فعالسازی افزونه اجرا شود.




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
        $html .= '<li>' . esc_html( $s ) . '</li>'; // $i . ' ) ' را حذف کردم چون فقط نام میخواهید.
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
        $html .= '<details open><summary>' . esc_html( ($k+1) . ' ) ' . $q ) . // اصلاح شد: $k+1 برای نمایش شماره از 1
            '</summary><div>' . wp_kses_post( $a ) . '</div></details>';
    }
    $html .= '</div>';
    return $html;
} );

// ───── ثبت Dynamic Tagها در المنتور ─────
add_action( 'elementor/dynamic_tags/register', function( $dynamic_tags ) {
    // این تنها require_once باید باشد که تمام تگ‌های Elementor را بارگذاری می‌کند.
    // مطمئن شوید که هیچ require_once دیگری برای تگ‌های Elementor در این فایل وجود ندارد.
    require_once AS_PLUGIN_DIR . 'elementor-tags/class-as-tag-faq.php';

    // تگ‌ها را نمونه‌سازی و ثبت کنید. (استفاده از new ClassName() ضروری است)
    $dynamic_tags->register_tag( new AS_Elementor\Tag_Specialties() );
    $dynamic_tags->register_tag( new AS_Elementor\Tag_FAQ() );
    $dynamic_tags->register_tag( new AS_Elementor\Tag_Work_Experience() );
    $dynamic_tags->register_tag( new AS_Elementor\Tag_Licenses() );
    $dynamic_tags->register_tag( new AS_Elementor\Tag_Approaches() );
} );

add_action('admin_head', function() {
    error_log('admin_head called from: ' . debug_backtrace()[0]['file']);
});

