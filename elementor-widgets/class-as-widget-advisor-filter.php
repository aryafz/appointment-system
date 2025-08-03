<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget – Advisor Specialty Filter (Ajax / Checkbox)
 */
class AS_Widget_Advisor_Filter extends Widget_Base {

    /** Widget slug */
    public function get_name() { return 'as-advisor-filter'; }

    /** Widget title */
    public function get_title() { return __( 'فیلتر تخصص مشاوران', 'appointment-system' ); }

    /** Widget icon */
    public function get_icon() { return 'eicon-filter'; }

    /** Widget category */
    public function get_categories() { return [ 'general' ]; }

    /*─────────────────────────── CONTROLS ───────────────────────────*/

    protected function register_controls() {

        $this->start_controls_section(
            'section_style',
            [
                'label' => __( 'استایل فیلتر', 'appointment-system' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'filter_bg',
            [
                'label'     => __( 'رنگ پس‌زمینه', 'appointment-system' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-advisor-filter' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_color',
            [
                'label'     => __( 'رنگ متن', 'appointment-system' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-advisor-filter label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_border',
            [
                'label'     => __( 'رنگ حاشیه', 'appointment-system' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-advisor-filter' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_radius',
            [
                'label'     => __( 'گردی گوشه', 'appointment-system' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
                'selectors' => [
                    '{{WRAPPER}} .as-advisor-filter' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /*─────────────────────────── RENDER ───────────────────────────*/

    protected function render() {

        /* 1) دریافت تمام تخصص‌ها (taxonomy = department) */
        $terms = get_terms( [
            'taxonomy'   => 'department',
            'hide_empty' => true,
        ] );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return;
        }

        /* 2) اصطلاحات انتخاب‌شده (روی بارگذاری صفحه) */
        $selected = isset( $_GET['specialty'] ) ? (array) $_GET['specialty'] : [];

        /* 3) Nonce برای امنیت تماس Ajax */
        $nonce = wp_create_nonce( 'as_filter_nonce' );

        /* 4) خروجی HTML */
        ?>
        <form class="as-advisor-filter" id="as-advisor-filter"
              style="display:flex;flex-wrap:wrap;gap:14px;padding:12px 16px;border:1.5px solid #ececec;margin-bottom:32px;align-items:center">

            <?php foreach ( $terms as $term ) : ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:1rem;cursor:pointer;border:1px solid #eee;padding:6px 10px;border-radius:7px;background:#fafaff">
                    <input type="checkbox"
                           name="specialty[]"
                           value="<?php echo esc_attr( $term->slug ); ?>"
                        <?php checked( in_array( $term->slug, $selected, true ) ); ?> />
                    <?php echo esc_html( $term->name ); ?>
                </label>
            <?php endforeach; ?>

            <input type="hidden" name="as_filter_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
        </form>

        <script>
            /* ــ Ajax submit on change ــــــــ */
            (function(){
                const $form = document.getElementById('as-advisor-filter');
                if(!$form) return;

                $form.addEventListener('change', function (){
                    const grid = document.querySelector('.as-advisor-grid');
                    if(grid) grid.innerHTML = '<div class="as-loading">در حال بارگذاری...</div>';

                    const fd = new FormData($form);

                    fetch('<?php echo admin_url( "admin-ajax.php" ); ?>?action=as_filter_advisors', {
                        method: 'POST',
                        body  : fd
                    })
                        .then(resp => resp.text())
                        .then(html => {
                            if(grid) grid.innerHTML = html;
                        });
                });
            })();
        </script>

        <style>
            .as-loading{padding:32px 0;font-size:1.15rem;color:#800020;text-align:center}
            .as-advisor-filter input[type="checkbox"]:checked{accent-color:#800020}
        </style>
        <?php
    }
}

/*──────────────────── Ajax Handler (یک‌بار درج شود) ────────────────────*/
if ( ! function_exists( 'as_filter_advisors_ajax' ) ) {

    function as_filter_advisors_ajax() {

        /* ۱- تأیید Nonce */
        if ( empty( $_POST['as_filter_nonce'] ) || ! wp_verify_nonce( $_POST['as_filter_nonce'], 'as_filter_nonce' ) ) {
            wp_die( 'BAD_NONCE' );
        }

        /* ۲- دریافت تخصص‌های انتخاب‌شده (ممکن است فارسی یا لاتین باشد) */
        $specialties_raw = isset( $_POST['specialty'] ) ? (array) $_POST['specialty'] : [];
        $specialties     = array_map( static function ( $s ) {
            $s = rawurldecode( $s );      // اگر %D9%… باشد
            return sanitize_title( $s );  // هم فارسی هم لاتین slug می‌سازد
        }, $specialties_raw );

        /* ۳- ساخت کوئری */
        $args = [
            'post_type'      => 'advisor',
            'posts_per_page' => 12,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( $specialties ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'department',
                    'field'    => 'slug',
                    'terms'    => $specialties,
                ],
            ];
        }

        $q = new WP_Query( $args );

        /* ۴- خروجی کارت‌ها */
        if ( $q->have_posts() ) {
            echo '<div class="as-advisor-grid">';
            while ( $q->have_posts() ) {
                $q->the_post();

                $img = get_the_post_thumbnail_url( null, 'medium' ) ?: AS_PLUGIN_URL . 'public/img/placeholder.png';
                $title = get_the_title();
                $tax   = get_the_terms( get_the_ID(), 'department' );
                $spec  = $tax && ! is_wp_error( $tax ) ? $tax[0]->name : '';

                $booking_page = get_page_by_path( 'booking' );
                $booking_url  = $booking_page ? get_permalink( $booking_page->ID ) : site_url( '/booking/' );
                $booking_url  = add_query_arg( 'advisor_id', get_the_ID(), $booking_url );
                ?>
                <div class="as-advisor-card">
                    <div class="as-card-image"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>"></div>
                    <div class="as-card-body">
                        <div class="as-card-title"><?php echo esc_html( $title ); ?></div>
                        <div class="as-card-specialty"><?php echo esc_html( $spec ); ?></div>
                        <a class="as-booking-btn" href="<?php echo esc_url( $booking_url ); ?>">رزرو وقت مشاوره</a>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        } else {
            echo '<div class="as-no-advisors">مشاوری یافت نشد.</div>';
        }

        wp_reset_postdata();
        wp_die();
    }

    add_action( 'wp_ajax_as_filter_advisors',        'as_filter_advisors_ajax' );
    add_action( 'wp_ajax_nopriv_as_filter_advisors', 'as_filter_advisors_ajax' );
}
