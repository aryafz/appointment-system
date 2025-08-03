<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;

class AS_Widget_Booking_Button extends Widget_Base {

    public function get_name() {
        return 'as-booking-button';
    }

    public function get_title() {
        return 'دکمه رزرو نوبت مشاور';
    }

    public function get_icon() {
        return 'eicon-calendar';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        // Content Controls
        $this->start_controls_section(
            'section_content',
            [
                'label' => 'تنظیمات دکمه',
            ]
        );
        $this->add_control(
            'button_text',
            [
                'label' => 'متن دکمه',
                'type' => Controls_Manager::TEXT,
                'default' => 'رزرو نوبت',
            ]
        );
        $this->add_control(
            'button_icon',
            [
                'label' => 'آیکون',
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-calendar-check',
                    'library' => 'fa-solid',
                ],
            ]
        );
        $this->add_control(
            'icon_position',
            [
                'label' => 'جایگاه آیکون',
                'type' => Controls_Manager::SELECT,
                'default' => 'before',
                'options' => [
                    'before' => 'قبل از متن',
                    'after'  => 'بعد از متن',
                ],
                'condition' => [
                    'button_icon[value]!' => '',
                ],
            ]
        );
        $this->add_control(
            'button_align',
            [
                'label'   => 'چینش دکمه',
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [
                        'title' => 'چپ',
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => 'وسط',
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => 'راست',
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'toggle'  => true,
            ]
        );
        $this->add_control(
            'button_width',
            [
                'label'   => 'عرض دکمه',
                'type'    => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto'   => 'خودکار',
                    'full'   => 'تمام عرض',
                ],
            ]
        );
        $this->end_controls_section();

        // Style Controls
        $this->start_controls_section(
            'section_style',
            [
                'label' => 'استایل دکمه',
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'color',
            [
                'label'     => 'رنگ متن',
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-booking-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'background_color',
            [
                'label'     => 'رنگ پس‌زمینه',
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-booking-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'color_hover',
            [
                'label'     => 'رنگ متن (هاور)',
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-booking-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'background_hover',
            [
                'label'     => 'رنگ پس‌زمینه (هاور)',
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .as-booking-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'selector' => '{{WRAPPER}} .as-booking-btn',
            ]
        );
        $this->add_control(
            'border_radius',
            [
                'label' => 'گردی گوشه‌ها',
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 50 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .as-booking-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'selector' => '{{WRAPPER}} .as-booking-btn',
            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'box_shadow',
                'selector' => '{{WRAPPER}} .as-booking-btn',
            ]
        );
        $this->add_responsive_control(
            'padding',
            [
                'label'      => 'فاصله داخلی',
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .as-booking-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'margin',
            [
                'label'      => 'فاصله بیرونی',
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .as-booking-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        $advisor_id = get_the_ID();
        if (get_post_type($advisor_id) !== 'advisor') {
            echo '<div style="color: red">این ویجت فقط روی برگه مشاور فعال است.</div>';
            return;
        }
        $settings    = $this->get_settings_for_display();
        $button_text = !empty($settings['button_text']) ? $settings['button_text'] : 'رزرو نوبت';

        // پیدا کردن برگه رزرو
        $booking_page = get_page_by_path('booking'); // یا اسلاگ فارسی 'rezerv' یا...
        $booking_url = $booking_page ? get_permalink($booking_page->ID) : site_url('/booking/');
        $booking_url = add_query_arg('advisor_id', $advisor_id, $booking_url);

        $btn_classes = 'as-booking-btn elementor-button elementor-size-md';
        if ($settings['button_width'] === 'full') {
            $btn_classes .= ' as-btn-full-width';
        }

        // آیکون
        $icon_html = '';
        if (!empty($settings['button_icon']['value'])) {
            $icon_html = '<span class="as-btn-icon">';
            Icons_Manager::render_icon($settings['button_icon'], [ 'aria-hidden' => 'true' ]);
            $icon_html .= '</span>';
        }

        // آیکون قبل یا بعد از متن
        if ($icon_html && $settings['icon_position'] === 'after') {
            $output = '<a href="' . esc_url($booking_url) . '" class="' . esc_attr($btn_classes) . '">'
                . esc_html($button_text) . $icon_html
                . '</a>';
        } else {
            $output = '<a href="' . esc_url($booking_url) . '" class="' . esc_attr($btn_classes) . '">'
                . $icon_html . esc_html($button_text)
                . '</a>';
        }

        // رپ با تگ div و کلاس چینش
        $align = isset($settings['button_align']) ? $settings['button_align'] : 'center';
        echo '<div class="as-btn-wrap" style="text-align:' . esc_attr($align) . '">';
        echo $output;
        echo '</div>';
    }

    // استایل تمام عرض برای دکمه
    public function get_style_depends() {
        return [];
    }
}

// بعد از ساخت این کلاس، باید در اکشن elementor/widgets/widgets_registered آن را رجیستر کنید.
