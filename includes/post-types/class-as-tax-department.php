<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Tax_Department {

    public function __construct() {
        add_action( 'init', array( $this, 'register_taxonomy_department' ) );
    }

    public function register_taxonomy_department() {
        $labels = array(
            'name'              => __( 'دپارتمان‌ها', 'appointment-system' ),
            'singular_name'     => __( 'دپارتمان',  'appointment-system' ),
            'search_items'      => __( 'جستجوی دپارتمان', 'appointment-system' ),
            'all_items'         => __( 'همه دپارتمان‌ها', 'appointment-system' ),
            'edit_item'         => __( 'ویرایش دپارتمان', 'appointment-system' ),
            'add_new_item'      => __( 'افزودن دپارتمان جديد', 'appointment-system' ),
            'menu_name'         => __( 'دپارتمان‌ها', 'appointment-system' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => array( 'slug' => 'department' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'department', array( 'advisor' ), $args );
    }
}
