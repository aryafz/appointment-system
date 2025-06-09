<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Meta_Faq {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post_advisor',   array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function register_meta_box() {
        add_meta_box(
            'as_advisor_faq',
            __( 'سوالات متداول', 'appointment-system' ),
            array( $this, 'render_meta_box' ),
            'advisor',
            'normal',
            'default'
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            wp_enqueue_script( 'as-faq-repeater', AS_PLUGIN_URL . 'admin/js/faq-repeater.js', array( 'jquery' ), AS_PLUGIN_VERSION, true );
            wp_enqueue_style(  'as-faq-style',   AS_PLUGIN_URL . 'admin/css/faq-style.css', array(), AS_PLUGIN_VERSION );
        }
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'as_save_advisor_faq', 'as_advisor_faq_nonce' );
        $faqs = get_post_meta( $post->ID, '_as_faq', true );
        if ( ! is_array( $faqs ) ) {
            $faqs = array();
        }
        echo '<div id="as-faq-repeater">';
        foreach ( $faqs as $i => $faq ) {
            $q = esc_textarea( $faq['question'] );
            $a = esc_textarea( $faq['answer'] );
            echo $this->get_faq_row_html( $i, $q, $a );
        }
        // یک ردیف خالی برای اضافه کردن
        echo $this->get_faq_row_html( count( $faqs ), '', '' );
        echo '</div>';
        echo '<p><button type="button" class="button" id="as-add-faq-row">' . __( 'سوال جدید', 'appointment-system' ) . '</button></p>';
    }

    private function get_faq_row_html( $index, $question, $answer ) {
        return '
        <div class="as-faq-row">
            <p><label>' . __( 'سوال', 'appointment-system' ) . '</label><br>
            <textarea name="as_faq[' . $index . '][question]" rows="2" style="width:100%;">' . $question . '</textarea></p>
            <p><label>' . __( 'پاسخ', 'appointment-system' ) . '</label><br>
            <textarea name="as_faq[' . $index . '][answer]" rows="3" style="width:100%;">' . $answer . '</textarea></p>
            <p><button type="button" class="button as-remove-faq-row">' . __( 'حذف', 'appointment-system' ) . '</button></p>
            <hr>
        </div>';
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['as_advisor_faq_nonce'] ) ||
            ! wp_verify_nonce( $_POST['as_advisor_faq_nonce'], 'as_save_advisor_faq' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( $post->post_type !== 'advisor' ) {
            return;
        }
        $faqs = array();
        if ( ! empty( $_POST['as_faq'] ) && is_array( $_POST['as_faq'] ) ) {
            foreach ( $_POST['as_faq'] as $row ) {
                $q = sanitize_text_field( $row['question'] );
                $a = sanitize_textarea_field( $row['answer'] );
                if ( $q !== '' && $a !== '' ) {
                    $faqs[] = array( 'question' => $q, 'answer' => $a );
                }
            }
        }
        update_post_meta( $post_id, '_as_faq', $faqs );
    }
}
