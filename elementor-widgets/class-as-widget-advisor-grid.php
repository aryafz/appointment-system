<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class AS_Widget_Advisor_Grid extends Widget_Base {

    public function get_name() { return 'as-advisor-grid'; }
    public function get_title() { return 'لیست مشاوران'; }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories() { return ['general']; }
    public function get_style_depends() {
        return array( 'as-advisors-grid' );
    }


    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [ 'label' => 'تنظیمات لیست' ]
        );
        $this->add_control(
            'posts_per_page',
            [
                'label' => 'تعداد مشاور در هر صفحه',
                'type' => Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 100,
            ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // کوئری تخصص (فیلتر)
        $tax_query = [];
        if (!empty($_GET['specialty'])) {
            $tax_query[] = [
                'taxonomy' => 'department',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['specialty']),
            ];
        }

        // صفحه جاری برای صفحه‌بندی
        $paged = get_query_var('paged') ?: (get_query_var('page') ?: 1);

        $args = [
            'post_type'      => 'advisor',
            'posts_per_page' => $settings['posts_per_page'],
            'paged'          => $paged,
            'tax_query'      => $tax_query,
        ];

        $query = new WP_Query($args);
        if($query->have_posts()) {
            echo '<div class="as-advisor-grid">';
            while($query->have_posts()) {
                $query->the_post();
                $img = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://placehold.co/280x180?text=No+Image';
                $title = get_the_title();
                // اگر specialty متا نیست و taxonomy است از get_the_terms بگیر
                $terms = get_the_terms(get_the_ID(), 'department');
                $specialty = $terms && !is_wp_error($terms) ? $terms[0]->name : '';

                $booking_page = get_page_by_path('booking');
                $booking_url = $booking_page ? get_permalink($booking_page->ID) : site_url('/booking/');
                $booking_url = add_query_arg('advisor_id', get_the_ID(), $booking_url);

                ?>
                <div class="as-advisor-card">
                    <div class="as-card-image">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" />
                    </div>
                    <div class="as-card-body">
                        <div class="as-card-title"><?php echo esc_html($title); ?></div>
                        <div class="as-card-specialty"><?php echo esc_html($specialty); ?></div>
                        <a href="<?php echo esc_url($booking_url); ?>" class="as-booking-btn">رزرو وقت مشاوره</a>
                    </div>
                </div>
                <?php
            }
            echo '</div>';

            // صفحه‌بندی (اگر لازم داشتی سفارشی کن)
            $big = 999999999; // need an unlikely integer
            $paginate_links = paginate_links( [
                'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'format'    => '?paged=%#%',
                'current'   => max( 1, $paged ),
                'total'     => $query->max_num_pages,
                'type'      => 'list',
            ] );
            if ($paginate_links) {
                echo '<div class="as-pagination">' . $paginate_links . '</div>';
            }
        } else {
            echo '<div class="as-no-advisors">مشاوری یافت نشد.</div>';
        }
        wp_reset_postdata();
    }
}
add_action('admin_head', function() {
    error_log('admin_head called from: ' . debug_backtrace()[0]['file']);
});
