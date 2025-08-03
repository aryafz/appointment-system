<?php
namespace AS_Elementor;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// کلاس موجود: Tag_Specialties
// این کلاس تخصص‌ها را به صورت یک لیست HTML نمایش می‌دهد.
class Tag_Specialties extends Tag {
    public function get_name() {
        return 'as-specialties';
    }

    public function get_title() {
        return __( 'تخصص‌های مشاور (سیستم نوبت‌دهی)', 'appointment-system' );
    }

    public function get_group() {
        return 'post';
    }

    public function get_categories() {
        return [ TagsModule::TEXT_CATEGORY, TagsModule::POST_META_CATEGORY ];
    }

    public function render() {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'advisor' ) {
            return;
        }

        $specs = get_post_meta( $post_id, 'as_specialties', true );
        // برای سازگاری با داده‌های قدیمی
        if ( empty( $specs ) ) {
            $specs = get_post_meta( $post_id, '_as_specialties', true );
        }

        if ( ! is_array( $specs ) || empty( $specs ) ) {
            return;
        }

        $html = '<ul class="as-specialties-list">';
        foreach ( $specs as $s ) {
            $html .= '<li>' . esc_html( $s ) . '</li>'; // اینجا دیگر نیازی به ایندکس نیست
        }
        $html .= '</ul>';

        echo $html;
    }
}


// کلاس موجود: Tag_FAQ
// این کلاس سوالات متداول را به صورت جزئیات/خلاصه (details/summary) نمایش می‌دهد.
class Tag_FAQ extends Tag {
    public function get_name() {
        return 'as-faq';
    }

    public function get_title() {
        return __( 'سوالات متداول (سیستم نوبت‌دهی)', 'appointment-system' );
    }

    public function get_group() {
        return 'post';
    }

    public function get_categories() {
        return [ TagsModule::TEXT_CATEGORY, TagsModule::POST_META_CATEGORY ];
    }

    public function render() {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'advisor' ) {
            return;
        }

        $faq = get_post_meta( $post_id, 'as_faq', true );
        // برای سازگاری با داده‌های قدیمی
        if ( empty( $faq ) ) {
            $faq = get_post_meta( $post_id, '_as_faq', true );
        }

        if ( ! is_array( $faq ) || empty( $faq ) ) {
            return;
        }

        $html = '<div class="as-faq-container">';
        foreach ( $faq as $k => $item ) {
            $q = $item['question'] ?? '';
            $a = $item['answer']   ?? '';
            // ایندکس k را به k+1 تغییر دادم تا از 1 شروع شود
            $html .= '<details open><summary>' . esc_html( ($k + 1) . ' ) ' . $q ) .
                '</summary><div>' . wp_kses_post( $a ) . '</div></details>';
        }
        $html .= '</div>';

        echo $html;
    }
}


/**
 * کلاس پایه انتزاعی برای Dynamic Tagهایی که یک لیست از آیتم‌های متنی ساده (فقط نام) را نمایش می‌دهند.
 */
abstract class AS_Single_Text_Repeater_Tag extends Tag {
    // هر کلاس فرزند باید این متدها را پیاده‌سازی کند تا کلیدهای متای مربوط به خود را برگرداند.
    abstract protected function get_meta_key_new();
    abstract protected function get_meta_key_old();

    public function get_group() {
        return 'post';
    }

    public function get_categories() {
        return [ TagsModule::TEXT_CATEGORY, TagsModule::POST_META_CATEGORY ];
    }

    public function render() {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'advisor' ) {
            return;
        }

        $items = get_post_meta( $post_id, $this->get_meta_key_new(), true );
        // برای سازگاری با داده‌های قدیمی
        if ( empty( $items ) ) {
            $items = get_post_meta( $post_id, $this->get_meta_key_old(), true );
        }

        if ( ! is_array( $items ) || empty( $items ) ) {
            return;
        }

        $html = '<ul class="as-single-text-list">';
        foreach ( $items as $item ) {
            $name = esc_html( $item['name'] ?? '' ); // فرض بر این است که هر آیتم یک کلید 'name' دارد
            if ( $name ) {
                $html .= '<li>' . $name . '</li>';
            }
        }
        $html .= '</ul>';

        echo $html;
    }
}


// کلاس جدید: Tag_Work_Experience (ساده‌شده)
// از AS_Single_Text_Repeater_Tag ارث‌بری می‌کند.
class Tag_Work_Experience extends AS_Single_Text_Repeater_Tag {
    public function get_name() {
        return 'as-work-experience';
    }

    public function get_title() {
        return __( 'سوابق کاری مشاور (سیستم نوبت‌دهی)', 'appointment-system' );
    }

    protected function get_meta_key_new() {
        return 'as_work_experience';
    }

    protected function get_meta_key_old() {
        return '_as_work_experience';
    }
}


// کلاس جدید: Tag_Licenses (ساده‌شده)
// از AS_Single_Text_Repeater_Tag ارث‌بری می‌کند.
class Tag_Licenses extends AS_Single_Text_Repeater_Tag {
    public function get_name() {
        return 'as-licenses';
    }

    public function get_title() {
        return __( 'مجوزها و گواهینامه‌ها (سیستم نوبت‌دهی)', 'appointment-system' );
    }

    protected function get_meta_key_new() {
        return 'as_licenses';
    }

    protected function get_meta_key_old() {
        return '_as_licenses';
    }
}


// کلاس جدید: Tag_Approaches
// از AS_Single_Text_Repeater_Tag ارث‌بری می‌کند.
class Tag_Approaches extends AS_Single_Text_Repeater_Tag {
    public function get_name() {
        return 'as-approaches';
    }

    public function get_title() {
        return __( 'رویکردهای درمانی (سیستم نوبت‌دهی)', 'appointment-system' );
    }

    protected function get_meta_key_new() {
        return 'as_approaches';
    }

    protected function get_meta_key_old() {
        return '_as_approaches';
    }
}