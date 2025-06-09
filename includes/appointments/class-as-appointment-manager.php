<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Appointment_Manager {

    public function __construct() {
        // شورت‌کد فرم رزرو
        add_shortcode( 'as_booking_form', [ $this, 'render_booking_form' ] );

        // enqueue اسکریپت‌ها و استایل‌ها
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // AJAX: واکشی ساعات آزاد
        add_action( 'wp_ajax_as_get_slots',        [ $this, 'ajax_get_slots' ] );
        add_action( 'wp_ajax_nopriv_as_get_slots', [ $this, 'ajax_get_slots' ] );

        // AJAX: ثبت نوبت
        add_action( 'wp_ajax_as_book_slot',        [ $this, 'ajax_book_slot' ] );
        add_action( 'wp_ajax_nopriv_as_book_slot', [ $this, 'ajax_book_slot' ] );

        // افزودن به سبد ووکامرس
        add_action( 'init', [ $this, 'maybe_add_to_cart' ] );

        // هوک‌های قیمت
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_custom_price' ], 20 );
        add_filter( 'woocommerce_cart_item_price',        [ $this, 'filter_cart_item_price' ], 10, 3 );
    }

    /**
     * رندر فرم رزرو با شورت‌کد [as_booking_form advisor_id="ID"]
     */
    public function render_booking_form( $atts ) {
        $atts = shortcode_atts( [ 'advisor_id' => 0 ], $atts, 'as_booking_form' );
        if ( ! $atts['advisor_id'] ) {
            $atts['advisor_id'] = intval( $_GET['advisor_id'] ?? 0 );
        }
        if ( ! $atts['advisor_id'] ) {
            return '<p>' . esc_html__( 'مشاور نامعتبر است.', 'appointment-system' ) . '</p>';
        }
        $id = $atts['advisor_id'];
        ob_start();
        include AS_PLUGIN_DIR . 'public/templates/template-booking-form.php';
        return ob_get_clean();
    }

    /**
     * enqueue اسکریپت‌ها و استایل‌ها
     */
    public function enqueue_assets() {
        if ( ! is_singular() || ! has_shortcode( get_post()->post_content, 'as_booking_form' ) ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script(
            'jalali-datepicker',
            AS_PLUGIN_URL . 'public/js/jalalidatepicker.min.js',
            [ 'jquery' ],
            AS_PLUGIN_VERSION,
            true
        );
        wp_enqueue_style(
            'jalali-datepicker-css',
            AS_PLUGIN_URL . 'public/css/jalalidatepicker.min.css',
            [],
            AS_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'as-booking',
            AS_PLUGIN_URL . 'public/js/booking.js',
            [ 'jquery', 'jalali-datepicker' ],
            AS_PLUGIN_VERSION,
            true
        );
        wp_localize_script( 'as-booking', 'AS_BOOKING_DATA', [
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'booking_nonce'      => wp_create_nonce( 'as_booking_nonce' ),
            'add_to_cart_nonce'  => wp_create_nonce( 'as_add_to_cart' ),
        ] );
    }

    /**
     * AJAX: واکشی ساعات آزاد
     */
    public function ajax_get_slots() {
        check_ajax_referer( 'as_booking_nonce', 'nonce' );

        $advisor_id = intval( $_POST['advisor_id'] ?? 0 );
        $date       = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $advisor_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( esc_html__( 'پارامترها نادرست است.', 'appointment-system' ) );
        }

        $slots = $this->get_available_slots( $advisor_id, $date );
        wp_send_json_success( $slots );
    }

    /**
     * تولید بازه‌های زمانی آزاد برای یک تاریخ مشخص
     *
     * @param int    $advisor_id شناسه‌ی مشاور
     * @param string $date       تاریخ میلادی به فرمت YYYY-MM-DD
     * @return array لیست ساعت‌های آزاد
     */
    private function get_available_slots( $advisor_id, $date ) {
        // خواندن تنظیمات
        $day_keys   = get_post_meta( $advisor_id, '_as_work_days',        true ) ?: [];
        $exceptions = get_post_meta( $advisor_id, '_as_exceptions',       true ) ?: [];
        $start      = get_post_meta( $advisor_id, '_as_start_time',       true ) ?: '09:00';
        $end        = get_post_meta( $advisor_id, '_as_end_time',         true ) ?: '21:00';
        $session    = intval( get_post_meta( $advisor_id, '_as_session_length', true ) ) ?: 45;
        $break      = intval( get_post_meta( $advisor_id, '_as_break_length',   true ) ) ?: 15;

        // نگاشت نام روزها به اعداد 0–6
        $map  = [ 'sun'=>0, 'mon'=>1, 'tue'=>2, 'wed'=>3, 'thu'=>4, 'fri'=>5, 'sat'=>6 ];
        $days = [];
        foreach ( $day_keys as $key ) {
            if ( isset( $map[ $key ] ) ) {
                $days[] = $map[ $key ];
            }
        }
        if ( empty( $days ) ) {
            $days = range( 0, 6 );
        }

        // محاسبه روز هفته
        $w = intval( date( 'w', strtotime( $date ) ) );
        if ( in_array( $date, $exceptions, true ) || ! in_array( $w, $days, true ) ) {
            return [];
        }

        // تولید بازه‌ها
        $slots   = [];
        $current = strtotime( "{$date} {$start}" );
        $end_ts  = strtotime( "{$date} {$end}" );
        $count   = 0;
        while ( $current + $session * 60 <= $end_ts && $count < 100 ) {
            $slots[] = date( 'H:i', $current );
            $current += ( $session + $break ) * 60;
            $count++;
        }

        // حذف ساعات رزروشده
        $booked = get_posts( [
            'post_type'      => 'appointment',
            'meta_query'     => [
                [ 'key' => '_as_appointment_advisor', 'value' => $advisor_id ],
                [ 'key' => '_as_appointment_date',    'value' => $date       ],
            ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );
        $taken = [];
        foreach ( $booked as $pid ) {
            $taken[] = get_post_meta( $pid, '_as_appointment_time', true );
        }

        return array_values( array_diff( $slots, $taken ) );
    }

    /**
     * AJAX: ثبت نوبت جدید
     */
    public function ajax_book_slot() {
        check_ajax_referer( 'as_booking_nonce', 'nonce' );

        $advisor_id = intval( $_POST['advisor_id'] ?? 0 );
        $date       = sanitize_text_field( $_POST['date'] ?? '' );
        $time       = sanitize_text_field( $_POST['time'] ?? '' );

        if ( ! $advisor_id
            || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date )
            || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            wp_send_json_error( esc_html__( 'پارامترها نادرست است.', 'appointment-system' ) );
        }

        $slots = $this->get_available_slots( $advisor_id, $date );
        if ( ! in_array( $time, $slots, true ) ) {
            wp_send_json_error( esc_html__( 'این بازه در دسترس نیست.', 'appointment-system' ) );
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'appointment',
            'post_title'  => sprintf( 'نوبت %d – %s %s', $advisor_id, $date, $time ),
            'post_status' => 'publish',
            'meta_input'  => [
                '_as_appointment_advisor' => $advisor_id,
                '_as_appointment_date'    => $date,
                '_as_appointment_time'    => $time,
            ],
        ] );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( esc_html__( 'خطا در ثبت نوبت.', 'appointment-system' ) );
        }

        wp_send_json_success( [ 'appointment_id' => $post_id ] );
    }

    /**
     * افزودن آیتم به سبد ووکامرس با قیمت سفارشی
     */
    public function maybe_add_to_cart() {
        if (
            isset( $_GET['as_action'], $_GET['advisor_id'], $_GET['date'], $_GET['time'], $_GET['nonce'] )
            && $_GET['as_action'] === 'add_to_cart'
            && wp_verify_nonce( sanitize_text_field( $_GET['nonce'] ), 'as_add_to_cart' )
        ) {
            $advisor    = intval( $_GET['advisor_id'] );
            $date       = sanitize_text_field( $_GET['date'] );
            $time       = sanitize_text_field( $_GET['time'] );
            $product_id = 1454;

            // لاگ
            error_log("DEBUG maybe_add_to_cart called – advisor: {$advisor}, date: {$date}, time: {$time}, product: {$product_id}");

            $price_meta = get_post_meta( $advisor, '_as_price', true );
            error_log("DEBUG maybe_add_to_cart – price_meta for advisor {$advisor}: {$price_meta}");

            $cart_item_data = [
                'as_advisor_id'   => $advisor,
                'as_date'         => $date,
                'as_time'         => $time,
                'as_custom_price' => is_numeric($price_meta) ? floatval($price_meta) : 0,
            ];
            error_log("DEBUG maybe_add_to_cart – cart_item_data: " . print_r($cart_item_data, true));

            WC()->cart->add_to_cart( $product_id, 1, 0, [], $cart_item_data );
            wp_safe_redirect( wc_get_cart_url() );
            exit;
        }
    }



    /**
     * Override قیمت هر آیتم رزرو بر اساس دادهٔ as_custom_price
     */
    public function apply_custom_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        error_log("DEBUG apply_custom_price running – cart has " . count($cart->get_cart()) . " items");

        foreach ( $cart->get_cart() as $item_key => $item ) {
            error_log("DEBUG apply_custom_price – item {$item_key} data: " . print_r($item, true));
            if ( isset( $item['as_custom_price'] ) && is_numeric( $item['as_custom_price'] ) ) {
                $new_price = floatval( $item['as_custom_price'] );
                $item['data']->set_price( $new_price );
                error_log("DEBUG apply_custom_price – overridden item {$item_key} price to {$new_price}");
            }
        }
    }



    /**
     * اصلاح HTML نمایش قیمت در سبد
     */
    public function filter_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
        if ( empty( $cart_item['as_advisor_id'] ) ) {
            return $price_html;
        }
        $advisor_id = intval( $cart_item['as_advisor_id'] );
        $price_meta = get_post_meta( $advisor_id, '_as_price', true );
        if ( is_numeric( $price_meta ) ) {
            return wc_price( floatval( $price_meta ) );
        }
        return $price_html;
    }
}

// Instantiate the class (if not done elsewhere)
new AS_Appointment_Manager();