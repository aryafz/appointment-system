<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Meta_Faq {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_all_meta_boxes' ) );
        add_action( 'save_post_advisor',   array( $this, 'save_all_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function register_all_meta_boxes() {
        // متاباکس FAQ (بدون تغییر)
        add_meta_box(
            'as_advisor_faq',
            __( 'سوالات متداول', 'appointment-system' ),
            array( $this, 'render_faq_meta_box' ),
            'advisor',
            'normal',
            'default'
        );

        // متاباکس سوابق کاری (ساده شده)
        add_meta_box(
            'as_advisor_work_experience',
            __( 'سوابق کاری مشاور', 'appointment-system' ),
            array( $this, 'render_single_text_repeater_meta_box' ),
            'advisor',
            'normal',
            'default',
            [ 'meta_key' => '_as_work_experience', 'title_label' => 'نام سابقه کاری', 'button_label' => 'افزودن سابقه کاری' ]
        );

        // متاباکس مجوزها و گواهینامه‌ها (ساده شده)
        add_meta_box(
            'as_advisor_licenses',
            __( 'مجوزها و گواهینامه‌های حرفه‌ای', 'appointment-system' ),
            array( $this, 'render_single_text_repeater_meta_box' ),
            'advisor',
            'normal',
            'default',
            [ 'meta_key' => '_as_licenses', 'title_label' => 'نام مجوز/گواهینامه', 'button_label' => 'افزودن مجوز/گواهینامه' ]
        );

        // متاباکس رویکردهای درمانی (بدون تغییر)
        add_meta_box(
            'as_advisor_approaches',
            __( 'رویکردهای درمانی', 'appointment-system' ),
            array( $this, 'render_single_text_repeater_meta_box' ), // استفاده از تابع رندرینگ عمومی
            'advisor',
            'normal',
            'default',
            [ 'meta_key' => '_as_approaches', 'title_label' => 'نام رویکرد', 'button_label' => 'افزودن رویکرد' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            wp_enqueue_script( 'as-repeater', AS_PLUGIN_URL . 'admin/js/repeater.js', array( 'jquery' ), AS_PLUGIN_VERSION, true );
            wp_enqueue_style(  'as-repeater-style', AS_PLUGIN_URL . 'admin/css/repeater.css', array(), AS_PLUGIN_VERSION );
        }
    }

    // --- توابع رندرینگ ---

    public function render_faq_meta_box( $post ) {
        wp_nonce_field( 'as_save_advisor_faq', 'as_advisor_faq_nonce' );
        $faqs = get_post_meta( $post->ID, '_as_faq', true );
        if ( ! is_array( $faqs ) ) {
            $faqs = array();
        }
        echo '<div id="as-faq-repeater" class="as-repeater-container">';
        foreach ( $faqs as $i => $faq ) {
            $q = esc_textarea( $faq['question'] ?? '' );
            $a = esc_textarea( $faq['answer'] ?? '' );
            echo $this->get_faq_row_html( $i, $q, $a );
        }
        echo $this->get_faq_row_html( count( $faqs ), '', '' ); // یک ردیف خالی
        echo '</div>';
        echo '<p><button type="button" class="button as-add-repeater-row" data-repeater-target="#as-faq-repeater" data-row-template-id="as-faq-row-template">' . __( 'سوال جدید', 'appointment-system' ) . '</button></p>';
        echo '<script type="text/html" id="as-faq-row-template">';
        echo $this->get_faq_row_html( '{repeater_index}', '', '' );
        echo '</script>';
    }

    private function get_faq_row_html( $index, $question, $answer ) {
        return '
        <div class="as-repeater-row">
            <p><label>' . __( 'سوال', 'appointment-system' ) . '</label><br>
            <textarea name="as_faq[' . $index . '][question]" rows="2" style="width:100%;">' . $question . '</textarea></p>
            <p><label>' . __( 'پاسخ', 'appointment-system' ) . '</label><br>
            <textarea name="as_faq[' . $index . '][answer]" rows="3" style="width:100%;">' . $answer . '</textarea></p>
            <p><button type="button" class="button as-remove-repeater-row">' . __( 'حذف', 'appointment-system' ) . '</button></p>
            <hr>
        </div>';
    }

    /**
     * رندرینگ یک فیلد تکرارشونده با یک فیلد متنی واحد
     * این تابع برای سوابق کاری، مجوزها و رویکردهای درمانی استفاده می‌شود.
     * @param WP_Post $post
     * @param array $metabox The metabox array, including 'args'.
     */
    public function render_single_text_repeater_meta_box( $post, $metabox ) {
        $meta_key     = $metabox['args']['meta_key']; // مثلاً '_as_work_experience'
        $title_label  = $metabox['args']['title_label']; // مثلاً 'نام سابقه کاری'
        $button_label = $metabox['args']['button_label']; // مثلاً 'افزودن سابقه کاری'
        $nonce_name   = str_replace('_as_', 'as_save_advisor_', $meta_key) . '_nonce'; // as_save_advisor_work_experience_nonce
        $nonce_value  = str_replace('_as_', 'as_save_advisor_', $meta_key); // as_save_advisor_work_experience

        wp_nonce_field( $nonce_value, $nonce_name );

        $items = get_post_meta( $post->ID, $meta_key, true );
        if ( ! is_array( $items ) ) {
            $items = array();
        }

        $container_id = str_replace('_as_', 'as-', $meta_key) . '-repeater'; // as-work-experience-repeater
        $template_id  = str_replace('_as_', 'as-', $meta_key) . '-row-template'; // as-work-experience-row-template
        $field_name   = str_replace('_as_', 'as_', $meta_key); // as_work_experience

        echo '<div id="' . esc_attr($container_id) . '" class="as-repeater-container">';
        foreach ( $items as $i => $item ) {
            // item['name'] for approaches, but for work_experience and licenses, it will be the only field
            $name = esc_textarea( $item['name'] ?? '' );
            echo $this->get_single_text_repeater_row_html( $index = $i, $field_name, $name, $title_label );
        }
        // یک ردیف خالی
        echo $this->get_single_text_repeater_row_html( $index = count($items), $field_name, '', $title_label );
        echo '</div>';
        echo '<p><button type="button" class="button as-add-repeater-row" data-repeater-target="#' . esc_attr($container_id) . '" data-row-template-id="' . esc_attr($template_id) . '">' . esc_html($button_label) . '</button></p>';
        echo '<script type="text/html" id="' . esc_attr($template_id) . '">';
        echo $this->get_single_text_repeater_row_html( '{repeater_index}', $field_name, '', $title_label );
        echo '</script>';
    }

    private function get_single_text_repeater_row_html( $index, $field_base_name, $value, $label ) {
        return '
        <div class="as-repeater-row">
            <p><label>' . esc_html( $label ) . '</label><br>
            <input type="text" name="' . esc_attr($field_base_name) . '[' . $index . '][name]" value="' . esc_attr( $value ) . '" style="width:100%;"></p>
            <p><button type="button" class="button as-remove-repeater-row">' . __( 'حذف', 'appointment-system' ) . '</button></p>
            <hr>
        </div>';
    }

    // --- توابع ذخیره سازی ---

    public function save_all_meta( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( $post->post_type !== 'advisor' ) {
            return;
        }

        // ذخیره FAQ
        if ( isset( $_POST['as_advisor_faq_nonce'] ) && wp_verify_nonce( $_POST['as_advisor_faq_nonce'], 'as_save_advisor_faq' ) ) {
            $faqs = array();
            if ( ! empty( $_POST['as_faq'] ) && is_array( $_POST['as_faq'] ) ) {
                foreach ( $_POST['as_faq'] as $row ) {
                    $q = sanitize_text_field( $row['question'] ?? '' );
                    $a = sanitize_textarea_field( $row['answer'] ?? '' );
                    if ( $q !== '' && $a !== '' ) {
                        $faqs[] = array( 'question' => $q, 'answer' => $a );
                    }
                }
            }
            update_post_meta( $post_id, '_as_faq', $faqs );
        }

        // ذخیره سوابق کاری (با استفاده از تابع ذخیره‌سازی عمومی)
        $this->save_single_text_repeater_meta( $post_id, '_as_work_experience', 'as_save_advisor_work_experience', 'as_work_experience' );

        // ذخیره مجوزها و گواهینامه‌ها (با استفاده از تابع ذخیره‌سازی عمومی)
        $this->save_single_text_repeater_meta( $post_id, '_as_licenses', 'as_save_advisor_licenses', 'as_licenses' );

        // ذخیره رویکردهای درمانی (با استفاده از تابع ذخیره‌سازی عمومی)
        $this->save_single_text_repeater_meta( $post_id, '_as_approaches', 'as_save_advisor_approaches', 'as_approaches' );
    }

    /**
     * ذخیره یک فیلد تکرارشونده با یک فیلد متنی واحد
     * @param int $post_id
     * @param string $meta_key کلید متای ذخیره‌سازی (مثلاً '_as_work_experience')
     * @param string $nonce_action اکشن Nonce (مثلاً 'as_save_advisor_work_experience')
     * @param string $post_data_key کلید در $_POST (مثلاً 'as_work_experience')
     */
    private function save_single_text_repeater_meta( $post_id, $meta_key, $nonce_action, $post_data_key ) {
        $nonce_name = $nonce_action . '_nonce';
        if ( isset( $_POST[$nonce_name] ) && wp_verify_nonce( $_POST[$nonce_name], $nonce_action ) ) {
            $items = array();
            if ( ! empty( $_POST[$post_data_key] ) && is_array( $_POST[$post_data_key] ) ) {
                foreach ( $_POST[$post_data_key] as $row ) {
                    $name = sanitize_text_field( $row['name'] ?? '' );
                    if ( $name !== '' ) {
                        $items[] = array( 'name' => $name );
                    }
                }
            }
            update_post_meta( $post_id, $meta_key, $items );
        }
    }
}