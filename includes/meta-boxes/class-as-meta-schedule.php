<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Meta_Schedule {

    public function __construct() {
        add_action( 'add_meta_boxes',        [ $this, 'register_meta_box' ] );
        add_action( 'save_post_advisor',     [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_meta_box() {
        add_meta_box(
            'as_advisor_schedule',
            __( 'تنظیمات زمان‌بندی', 'appointment-system' ),
            [ $this, 'render_meta_box' ],
            'advisor',
            'normal',
            'default'
        );
    }

    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }
        if ( get_post_type() !== 'advisor' ) {
            return;
        }

        // JalaliDatePicker library
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

        // our schedule.js & CSS
        wp_enqueue_script(
            'as-schedule',
            AS_PLUGIN_URL . 'admin/js/schedule.js',
            [ 'jquery', 'jalali-datepicker' ],
            AS_PLUGIN_VERSION,
            true
        );
        wp_enqueue_style(
            'as-schedule-css',
            AS_PLUGIN_URL . 'admin/css/schedule.css',
            [],
            AS_PLUGIN_VERSION
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'as_save_advisor_schedule', 'as_advisor_schedule_nonce' );

        // load saved metas
        $days       = get_post_meta( $post->ID, '_as_work_days',       true ) ?: [];
        $start_time = get_post_meta( $post->ID, '_as_start_time',      true ) ?: '09:00';
        $end_time   = get_post_meta( $post->ID, '_as_end_time',        true ) ?: '21:00';
        $session    = get_post_meta( $post->ID, '_as_session_length', true ) ?: 45;
        $break      = get_post_meta( $post->ID, '_as_break_length',   true ) ?: 15;
        $exceptions = get_post_meta( $post->ID, '_as_exceptions',     true ) ?: [];

        $exceptions_string = is_array($exceptions) ? implode(',', $exceptions) : '';
        // — work days —
        echo '<p><strong>' . __( 'روزهای کاری', 'appointment-system' ) . ':</strong><br>';
        $weekdays = [
            'mon' => __( 'دوشنبه', 'appointment-system' ),
            'tue' => __( 'سه‌شنبه', 'appointment-system' ),
            'wed' => __( 'چهارشنبه', 'appointment-system' ),
            'thu' => __( 'پنج‌شنبه', 'appointment-system' ),
            'fri' => __( 'جمعه', 'appointment-system' ),
            'sat' => __( 'شنبه', 'appointment-system' ),
            'sun' => __( 'یک‌شنبه', 'appointment-system' ),
        ];
        foreach ( $weekdays as $key => $label ) {
            printf(
                '<label><input type="checkbox" name="as_work_days[]" value="%1$s" %2$s> %3$s</label><br>',
                esc_attr( $key ),
                in_array( $key, $days, true ) ? 'checked' : '',
                esc_html( $label )
            );
        }
        echo '</p><hr>';

        // — times —
        printf(
            '<p><label>%1$s <input type="time" name="as_start_time" value="%2$s"></label> — 
                  <label>%3$s <input type="time" name="as_end_time" value="%4$s"></label></p>',
            __( 'از', 'appointment-system' ),
            esc_attr( $start_time ),
            __( 'تا', 'appointment-system' ),
            esc_attr( $end_time )
        );
        printf(
            '<p><label>%1$s <input type="number" name="as_session_length" value="%2$s" min="1"> دقیقه</label></p>',
            __( 'مدت هر نوبت', 'appointment-system' ),
            esc_attr( $session )
        );
        printf(
            '<p><label>%1$s <input type="number" name="as_break_length" value="%2$s" min="0"> دقیقه</label></p>',
            __( 'مدت استراحت', 'appointment-system' ),
            esc_attr( $break )
        );
        echo '<hr>';

        // — exceptions: multi-select Persian calendar —
        $json_exc = esc_attr( wp_json_encode( $exceptions ) );
        $val_exc  = esc_attr( implode( ',', $exceptions ) );
        ?>
        <p><strong><?php esc_html_e( 'روزهای عدم کاری (استثنا)', 'appointment-system' ); ?></strong></p>
        <div id="as-exceptions-wrapper">
            <input
                    type="text"
                    id="as-exceptions-picker"
                    data-jdp
                    data-jdp-multiple="true"
                    data-jdp-format="YYYY/MM/DD"
                    style="width:100%; padding:6px; box-sizing:border-box;"
                    placeholder="<?php esc_attr_e( 'روی تقویم کلیک کنید تا تاریخ انتخاب کنید...', 'appointment-system' ); ?>"
                    readonly
            />

            <button type="button" id="as-clear-exceptions" class="button" style="margin-top:6px;">
                <?php esc_html_e( 'پاک کردن همه', 'appointment-system' ); ?>
            </button>
            <div id="as-exceptions-tags" style="margin-top:8px;"></div>
        </div>

        <!-- این input مهم است - همه تاریخ‌ها باید در اینجا ذخیره شوند -->
        <input
                type="hidden"
                id="as-exceptions-hidden"
                name="as_exceptions"
                value="<?php echo esc_attr(implode(',', $exceptions)); ?>"
        />

        <!-- اطلاعات اولیه برای JavaScript -->
        <script type="text/javascript">
            var asInitialExceptions = <?php echo wp_json_encode($exceptions); ?>;
        </script>

        <p class="description">
            <?php esc_html_e( 'روی تقویم کلیک کنید تا روزهای عدم کاری را انتخاب کنید.', 'appointment-system' ); ?>
        </p>
        <?php
    }

    public function save_meta( $post_id, $post ) {
        if (
            ! isset( $_POST['as_advisor_schedule_nonce'] )
            || ! wp_verify_nonce( $_POST['as_advisor_schedule_nonce'], 'as_save_advisor_schedule' )
            || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            || $post->post_type !== 'advisor'
        ) {
            return;
        }

        // work days
        $days = isset( $_POST['as_work_days'] ) && is_array( $_POST['as_work_days'] )
            ? array_map( 'sanitize_text_field', $_POST['as_work_days'] )
            : [];
        update_post_meta( $post_id, '_as_work_days', $days );

        // times
        update_post_meta( $post_id, '_as_start_time',     sanitize_text_field( $_POST['as_start_time'] ?? '' ) );
        update_post_meta( $post_id, '_as_end_time',       sanitize_text_field( $_POST['as_end_time']   ?? '' ) );
        update_post_meta( $post_id, '_as_session_length', intval( $_POST['as_session_length'] ?? 0 ) );
        update_post_meta( $post_id, '_as_break_length',   intval( $_POST['as_break_length']   ?? 0 ) );

        // exceptions - بررسی بیشتر
        $raw = sanitize_text_field( $_POST['as_exceptions'] ?? '' );
        error_log('Raw exceptions data: ' . $raw); // برای دیباگ

        if (empty($raw)) {
            update_post_meta( $post_id, '_as_exceptions', [] );
        } else {
            $list = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
            $final = [];
            foreach ( $list as $date ) {
                if ( preg_match( '/^\d{4}\/\d{2}\/\d{2}$/', $date ) ) {
                    $final[] = $date;
                }
            }
            error_log('Final exceptions: ' . print_r($final, true)); // برای دیباگ
            update_post_meta( $post_id, '_as_exceptions', $final );
        }
    }
}

new AS_Meta_Schedule();
