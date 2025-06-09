<?php
namespace AS_Elementor;

use Elementor\Modules\DynamicTags\Module as TagsModule;   // ← مسیر درست
use Elementor\Core\DynamicTags\Tag;                       // در 3.29 همچنان وجود دارد

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* -------- لیست تخصص‌ها -------- */
class Tag_Specialties extends Tag {

    public function get_name()        { return 'as_specialties_list'; }
    public function get_title()       { return 'لیست تخصص‌ها'; }
    public function get_group()       { return 'post'; }
    public function get_categories()  { return [ TagsModule::TEXT_CATEGORY ]; }
    protected function register_controls() {}

    public function render() {
        $specs = get_post_meta( get_the_ID(), 'as_specialties', true )
            ?: get_post_meta( get_the_ID(), '_as_specialties', true );

        if ( ! is_array( $specs ) || empty( $specs ) ) return;

        echo '<ul class="as-specialties">';
        foreach ( $specs as $s ) echo '<li>'. esc_html( $s ) .'</li>';
        echo '</ul>';
    }
}

/* -------- سؤالات متداول -------- */
class Tag_FAQ extends Tag {

    public function get_name()        { return 'as_faq_list'; }
    public function get_title()       { return 'سؤالات متداول'; }
    public function get_group()       { return 'post'; }
    public function get_categories()  { return [ TagsModule::TEXT_CATEGORY ]; }
    protected function register_controls() {}

    public function render() {
        $faqs = get_post_meta( get_the_ID(), 'as_faq', true )
            ?: get_post_meta( get_the_ID(), '_as_faq', true );

        if ( ! is_array( $faqs ) || empty( $faqs ) ) return;

        echo '<div class="as-faq">';
        foreach ( $faqs as $f ) {
            $q = $f['question'] ?? '';
            $a = $f['answer']   ?? '';
            echo '<details open><summary>'. esc_html( $q ) .'</summary>';
            echo '<div>'. wp_kses_post( $a ) .'</div></details>';
        }
        echo '</div>';
    }
}
