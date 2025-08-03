<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_CPT_Appointment {

    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt_appointment' ] );

        // افزودن ستون‌های سفارشی به جدول مدیریت
        add_filter( 'manage_appointment_posts_columns', [ $this, 'custom_columns' ] );
        add_action( 'manage_appointment_posts_custom_column', [ $this, 'render_custom_columns' ], 10, 2 );
        add_filter( 'manage_edit-appointment_sortable_columns', [ $this, 'sortable_columns' ] );

        // افزودن فیلترهای سفارشی به بالای جدول
        add_action( 'restrict_manage_posts', [ $this, 'add_filters' ] );
        add_filter( 'parse_query', [ $this, 'filter_query' ] );

        // اکشن‌های سریع هر ردیف
        add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );
        add_action( 'admin_init', [ $this, 'handle_row_actions' ] );
    }

    public function register_cpt_appointment() {
        $labels = array(
            'name'               => __( 'نوبت‌ها', 'appointment-system' ),
            'singular_name'      => __( 'نوبت',  'appointment-system' ),
            'add_new'            => __( 'افزودن نوبت', 'appointment-system' ),
            'add_new_item'       => __( 'افزودن نوبت جدید', 'appointment-system' ),
            'edit_item'          => __( 'ویرایش نوبت', 'appointment-system' ),
            'new_item'           => __( 'نوبت جدید', 'appointment-system' ),
            'all_items'          => __( 'همه نوبت‌ها', 'appointment-system' ),
            'view_item'          => __( 'نمایش نوبت', 'appointment-system' ),
            'search_items'       => __( 'جستجوی نوبت', 'appointment-system' ),
            'not_found'          => __( 'نوبتی یافت نشد', 'appointment-system' ),
            'menu_name'          => __( 'نوبت‌ها', 'appointment-system' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=advisor',
            'supports'           => array( 'title' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'rewrite'            => false,
            'show_in_rest'       => false,
        );

        register_post_type( 'appointment', $args );
    }

    // -------------------- ستاپ ستون‌های سفارشی --------------------

    public function custom_columns( $columns ) {
        unset($columns['date']);
        $columns['as_customer']  = 'کاربر';
        $columns['as_advisor']   = 'مشاور';
        $columns['as_date']      = 'تاریخ';
        $columns['as_time']      = 'ساعت';
        $columns['as_status']    = 'وضعیت';
        $columns['as_order']     = 'سفارش ووکامرس';
        $columns['as_phone']     = 'موبایل کاربر';
        $columns['as_email']     = 'ایمیل کاربر';
        $columns['date']         = 'تاریخ ایجاد';
        return $columns;
    }

    public function render_custom_columns( $column, $post_id ) {
        switch ($column) {
            case 'as_customer':
                $user_id = get_post_meta($post_id, '_as_user_id', true);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    echo $user ? esc_html($user->display_name) : '—';
                } else {
                    echo '—';
                }
                break;
            case 'as_advisor':
                $advisor_id = get_post_meta($post_id, '_as_advisor_id', true);
                if ($advisor_id) {
                    $title = get_the_title($advisor_id);
                    echo $title ? esc_html($title) : '—';
                } else {
                    echo '—';
                }
                break;
            case 'as_date':
                $date = get_post_meta($post_id, '_as_date', true);
                echo $date ? esc_html($date) : '—';
                break;
            case 'as_time':
                $time = get_post_meta($post_id, '_as_time', true);
                echo $time ? esc_html($time) : '—';
                break;
            case 'as_status':
                $status = get_post_meta($post_id, '_as_status', true);
                $statuses = [
                    'pending'   => 'در انتظار پرداخت',
                    'paid'      => 'پرداخت شده',
                    'cancelled' => 'لغو شده',
                    'done'      => 'انجام شده',
                ];
                echo isset($statuses[$status]) ? esc_html($statuses[$status]) : 'نامشخص';
                break;
            case 'as_order':
                $order_id = get_post_meta($post_id, '_as_order_id', true);
                if ($order_id) {
                    echo '<a href="' . esc_url(admin_url('post.php?post='.$order_id.'&action=edit')) . '" target="_blank">#' . intval($order_id) . '</a>';
                } else {
                    echo '—';
                }
                break;
            case 'as_phone':
                $user_id = get_post_meta($post_id, '_as_user_id', true);
                $phone = get_post_meta($post_id, '_as_user_phone', true);
                if (!$phone && $user_id) {
                    $user = get_userdata($user_id);
                    $phone = $user ? get_user_meta($user_id, 'billing_phone', true) : '';
                }
                echo $phone ? esc_html($phone) : '—';
                break;
            case 'as_email':
                $user_id = get_post_meta($post_id, '_as_user_id', true);
                $email = '';
                if ($user_id) {
                    $user = get_userdata($user_id);
                    $email = $user ? $user->user_email : '';
                }
                echo $email ? esc_html($email) : '—';
                break;
        }
    }

    public function sortable_columns( $columns ) {
        $columns['as_date']   = 'as_date';
        $columns['as_status'] = 'as_status';
        return $columns;
    }

    // -------------------- فیلترهای بالای جدول --------------------

    public function add_filters( $post_type ) {
        if ( $post_type != 'appointment' ) return;

        // فیلتر مشاور
        $advisors = get_posts(['post_type' => 'advisor', 'numberposts' => -1, 'post_status' => 'publish']);
        echo '<select name="as_advisor" style="min-width:120px;">';
        echo '<option value="">همه مشاوران</option>';
        foreach ($advisors as $advisor) {
            $selected = (isset($_GET['as_advisor']) && $_GET['as_advisor'] == $advisor->ID) ? 'selected' : '';
            echo '<option value="'.$advisor->ID.'" '.$selected.'>'.esc_html($advisor->post_title).'</option>';
        }
        echo '</select>';

        // فیلتر وضعیت
        $statuses = [
            ''          => 'همه وضعیت‌ها',
            'pending'   => 'در انتظار پرداخت',
            'paid'      => 'پرداخت شده',
            'cancelled' => 'لغو شده',
            'done'      => 'انجام شده'
        ];
        echo '<select name="as_status" style="min-width:120px;">';
        foreach ($statuses as $key => $label) {
            $selected = (isset($_GET['as_status']) && $_GET['as_status'] === $key) ? 'selected' : '';
            echo '<option value="'.$key.'" '.$selected.'>'.$label.'</option>';
        }
        echo '</select>';
    }

    public function filter_query( $query ) {
        global $pagenow;
        if ( $pagenow != 'edit.php' || $query->get('post_type') != 'appointment' ) return;

        // فیلتر مشاور
        if ( !empty($_GET['as_advisor']) ) {
            $query->set( 'meta_key', '_as_advisor_id' );
            $query->set( 'meta_value', intval($_GET['as_advisor']) );
        }

        // فیلتر وضعیت
        if ( !empty($_GET['as_status']) ) {
            $meta_query = $query->get('meta_query', []);
            $meta_query[] = [
                'key'   => '_as_status',
                'value' => sanitize_text_field($_GET['as_status']),
            ];
            $query->set( 'meta_query', $meta_query );
        }
    }

    // -------------------- اکشن‌های سریع (row actions) --------------------

    public function row_actions( $actions, $post ) {
        if ( $post->post_type != 'appointment' ) return $actions;

        $status = get_post_meta( $post->ID, '_as_status', true );
        if ( $status !== 'done' ) {
            $actions['mark_done'] = '<a href="' . esc_url( add_query_arg( [ 'action' => 'as_mark_done', 'post' => $post->ID ] ) ) . '">انجام شد</a>';
        }
        if ( $status !== 'cancelled' ) {
            $actions['mark_cancelled'] = '<a style="color:red;" href="' . esc_url( add_query_arg( [ 'action' => 'as_mark_cancelled', 'post' => $post->ID ] ) ) . '">لغو</a>';
        }
        return $actions;
    }

    public function handle_row_actions() {
        if ( ! current_user_can('edit_posts') || !isset($_GET['action'], $_GET['post']) ) return;
        $post_id = intval($_GET['post']);
        if ( get_post_type($post_id) != 'appointment' ) return;

        if ( $_GET['action'] === 'as_mark_done' ) {
            update_post_meta( $post_id, '_as_status', 'done' );
            wp_safe_redirect( admin_url('edit.php?post_type=appointment') );
            exit;
        }
        if ( $_GET['action'] === 'as_mark_cancelled' ) {
            update_post_meta( $post_id, '_as_status', 'cancelled' );
            wp_safe_redirect( admin_url('edit.php?post_type=appointment') );
            exit;
        }
    }
}
