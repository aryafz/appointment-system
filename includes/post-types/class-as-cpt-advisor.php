<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_CPT_Advisor {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt_advisor' ) );
    }

    public function register_cpt_advisor() {
        $labels = array(
            'name'               => __( 'مشاوران', 'appointment-system' ),
            'singular_name'      => __( 'مشاور',  'appointment-system' ),
            'add_new'            => __( 'افزودن مشاور', 'appointment-system' ),
            'add_new_item'       => __( 'افزودن مشاور جدید', 'appointment-system' ),
            'edit_item'          => __( 'ویرایش مشاور', 'appointment-system' ),
            'new_item'           => __( 'مشاور جدید', 'appointment-system' ),
            'all_items'          => __( 'همه مشاوران', 'appointment-system' ),
            'view_item'          => __( 'نمایش مشاور', 'appointment-system' ),
            'search_items'       => __( 'جستجوی مشاور', 'appointment-system' ),
            'not_found'          => __( 'مشاوری یافت نشد', 'appointment-system' ),
            'menu_name'          => __( 'مشاوران', 'appointment-system' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => true,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-businessman',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'comments' ),
            'has_archive'        => false,
            'rewrite'            => array( 'slug' => 'advisor' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'advisor', $args );
    }
}
