<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Meta_Advisor_Info {

    public function __construct() {
        add_action( 'add_meta_boxes',      [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_advisor',   [ $this, 'save_meta' ], 10, 2 );
    }

    public function register_meta_boxes() {
        add_meta_box(
            'as_advisor_info',
            __( 'اطلاعات مشاور', 'appointment-system' ),
            [ $this, 'render_meta_box' ],
            'advisor',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'as_save_advisor_info', 'as_advisor_info_nonce' );

        // بارگذاری مقادیر قبلی
        $about       = get_post_meta( $post->ID, '_as_about', true );
        $specialties = get_post_meta( $post->ID, '_as_specialties', true );
        $address     = get_post_meta( $post->ID, '_as_address', true );
        $phone       = get_post_meta( $post->ID, '_as_phone', true );
        $map         = get_post_meta( $post->ID, '_as_map', true );
        $psych_code  = get_post_meta( $post->ID, '_as_psych_code', true );
        $price       = get_post_meta( $post->ID, '_as_price', true );

        // مثال تخصص‌ها (قابل تغییر)
        $all_specs = [ 'مشاوره فردی', 'زوج درمانی', 'مشاوره خانواده', 'تست روانشناسی' ];

        // فیلد درباره مشاور
        echo '<p><label for="as_about">' . __( 'درباره مشاور', 'appointment-system' ) . '</label><br>';
        echo '<textarea id="as_about" name="as_about" rows="5" style="width:100%;">' . esc_textarea( $about ) . '</textarea></p>';

        // فیلد تخصص‌ها
        echo '<p><strong>' . __( 'تخصص‌ها', 'appointment-system' ) . ':</strong><br>';
        foreach ( $all_specs as $spec ) {
            printf(
                '<label><input type="checkbox" name="as_specialties[]" value="%1$s" %2$s> %1$s</label><br>',
                esc_attr( $spec ),
                ( is_array( $specialties ) && in_array( $spec, $specialties, true ) ) ? 'checked' : ''
            );
        }
        echo '</p><hr>';

        // اطلاعات تماس
        printf(
            '<p><label>%1$s<br><input type="text" name="as_address" value="%2$s" style="width:100%%;"></label></p>',
            __( 'آدرس', 'appointment-system' ),
            esc_attr( $address )
        );
        printf(
            '<p><label>%1$s<br><input type="text" name="as_phone" value="%2$s"></label></p>',
            __( 'تلفن', 'appointment-system' ),
            esc_attr( $phone )
        );
        printf(
            '<p><label>%1$s<br><input type="text" name="as_map" value="%2$s" style="width:100%%;"></label></p>',
            __( 'لینک نقشه', 'appointment-system' ),
            esc_attr( $map )
        );
        echo '<hr>';

        // کد نظام روانشناسی
        printf(
            '<p><label>%1$s<br><input type="text" name="as_psych_code" value="%2$s"></label></p>',
            __( 'کد نظام روانشناسی', 'appointment-system' ),
            esc_attr( $psych_code )
        );

        // فیلد قیمت مشاوره
        printf(
            '<p><label>%1$s<br><input type="number" name="as_price" id="as_price" value="%2$s" step="0.01" style="width:100%%;"></label></p>',
            __( 'قیمت هر جلسه (تومان)', 'appointment-system' ),
            esc_attr( $price )
        );
    }

    public function save_meta( $post_id, $post ) {
        // امنیت: nonce و autosave و نوع پست
        if (
            ! isset( $_POST['as_advisor_info_nonce'] )
            || ! wp_verify_nonce( $_POST['as_advisor_info_nonce'], 'as_save_advisor_info' )
            || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
            || $post->post_type !== 'advisor'
        ) {
            return;
        }

        // فیلدها و نام آن‌ها در فرم
        $fields = [
            'about'       => 'as_about',
            'specialties' => 'as_specialties',
            'address'     => 'as_address',
            'phone'       => 'as_phone',
            'map'         => 'as_map',
            'psych_code'  => 'as_psych_code',
            'price'       => 'as_price',
        ];

        foreach ( $fields as $key => $field_name ) {
            if ( 'specialties' === $key ) {
                // چک‌باکس‌ها: آرایه
                $value = ! empty( $_POST[ $field_name ] ) && is_array( $_POST[ $field_name ] )
                    ? array_map( 'sanitize_text_field', $_POST[ $field_name ] )
                    : [];
            } else {
                $value = isset( $_POST[ $field_name ] )
                    ? sanitize_text_field( $_POST[ $field_name ] )
                    : '';
            }
            update_post_meta( $post_id, "_as_{$key}", $value );
        }
    }
}

new AS_Meta_Advisor_Info();
