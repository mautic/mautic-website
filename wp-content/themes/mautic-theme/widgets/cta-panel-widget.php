<?php
namespace Elementor;

if (!defined('ABSPATH')) exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class CTA_Panel_Widget extends Widget_Base
{
    private const D = [
        'panel_bg'   => '#425090',
        'panel_text' => '#ffffff',

        'title_font'   => 'Titillium Web',
        'title_size'   => 52,
        'title_weight' => 600,
        'title_lh'     => 1.15,

        'desc_font'    => 'Roboto',
        'desc_size'    => 18,
        'desc_lh'      => 1.6,

        'btn_bg'       => '#fdb933',
        'btn_color'    => '#000000',
        'btn_radius'   => 50,
        'btn_pad'      => ['top'=>12,'right'=>24,'bottom'=>12,'left'=>24,'unit'=>'px'],

        'pad'          => ['top'=>26,'right'=>40,'bottom'=>50,'left'=>40,'unit'=>'px'],
        'gap_px'       => 16,
        'radius_px'    => 12,
    ];

    public function get_name(){ return 'mautic_cta_panel'; }
    public function get_title(){ return 'CTA Panel'; }
    public function get_icon(){ return 'eicon-call-to-action'; }
    public function get_categories(){ return ['mautic-widgets']; }
    public function get_style_depends(){ return ['mautic-cta-panel']; }

    protected function register_controls()
    {
        $this->start_controls_section('content', ['label' => 'Content']);

        $this->add_control('title', [
            'label' => 'Title',
            'type' => Controls_Manager::TEXT,
            'default' => 'Ready to see what Mautic can do?',
            'label_block' => true,
        ]);

        $this->add_control('description', [
            'label' => 'Description',
            'type' => Controls_Manager::WYSIWYG,
            'default' => 'Start your 14-day free trial and see how Mautic helps you create automated campaigns, personalize experiences, and stay compliant with GDPR/CCPA and other international laws',
        ]);

        $this->add_control('button_text', [
            'label' => 'Button text',
            'type' => Controls_Manager::TEXT,
            'default' => 'Try Mautic',
        ]);

        $this->add_control('button_url', [
            'label' => 'Button URL',
            'type' => Controls_Manager::URL,
            'dynamic' => ['active' => true],
            'default' => ['url' => '#', 'is_external' => false, 'nofollow' => false],
        ]);

        $this->add_control('new_tab', [
            'label' => 'Open in new tab',
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('layout', ['label' => 'Layout', 'tab' => Controls_Manager::TAB_STYLE]);

        $this->add_responsive_control('align', [
            'label' => 'Alignment',
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'left' => ['title' => 'Left', 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Center', 'icon' => 'eicon-text-align-center'],
                'right' => ['title' => 'Right', 'icon' => 'eicon-text-align-right'],
            ],
            'default' => 'center',
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'text-align: {{VALUE}};'],
        ]);

        $this->add_control('min_height', [
            'label' => 'Min height',
            'type' => Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 800]],
            'default'    => [ 'size' => 430, 'unit' => 'px' ],
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'min-height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('padding', [
            'label' => 'Padding',
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px','em','rem'],
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'default' => self::D['pad'],
        ]);

        $this->add_control('gap', [
            'label' => 'Gap',
            'type' => Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 64]],
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'row-gap: {{SIZE}}{{UNIT}};'],
            'default' => ['size' => self::D['gap_px'], 'unit' => 'px'],
        ]);

        $this->add_control('border_radius', [
            'label' => 'Border radius',
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px','%'],
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'default' => ['top'=>self::D['radius_px'],'right'=>self::D['radius_px'],'bottom'=>self::D['radius_px'],'left'=>self::D['radius_px'],'unit'=>'px'],
        ]);

        $this->add_control('bg_color', [
            'label' => 'Background color',
            'type' => Controls_Manager::COLOR,
            'default' => self::D['panel_bg'],
            'selectors' => ['{{WRAPPER}} .mautic-cta' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('text_color', [
            'label' => 'Text color',
            'type' => Controls_Manager::COLOR,
            'default' => self::D['panel_text'],
            'selectors' => [
                '{{WRAPPER}} .mautic-cta' => 'color: {{VALUE}};',
                '{{WRAPPER}} .mautic-cta__desc a:not(.mautic-cta__btn)' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('typography', ['label' => 'Typography', 'tab' => Controls_Manager::TAB_STYLE]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name' => 'title_typo',
            'label' => 'Title',
            'selector' => '{{WRAPPER}} .mautic-cta__title',
            'fields_options' => [
                'typography'  => ['default' => 'custom'],
                'font_family' => ['default' => self::D['title_font']],
                'font_weight' => ['default' => self::D['title_weight']],
                'font_size'   => ['default' => ['size' => self::D['title_size'], 'unit' => 'px']],
                'line_height' => ['default' => ['size' => self::D['title_lh'],   'unit' => 'em']],
            ],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name' => 'desc_typo',
            'label' => 'Text',
            'selector' => '{{WRAPPER}} .mautic-cta__desc',
            'fields_options' => [
                'typography'  => ['default' => 'custom'],
                'font_family' => ['default' => self::D['desc_font']],
                'font_size'   => ['default' => ['size' => self::D['desc_size'], 'unit' => 'px']],
                'line_height' => ['default' => ['size' => self::D['desc_lh'],   'unit' => 'em']],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('button_style', ['label' => 'Button', 'tab' => Controls_Manager::TAB_STYLE]);

        $this->add_control('btn_bg', [
            'label' => 'Background',
            'type' => Controls_Manager::COLOR,
            'default' => self::D['btn_bg'],
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('btn_padding', [
            'label' => 'Padding',
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px','em','rem'],
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'default' => self::D['btn_pad'],
        ]);

        $this->add_control('btn_radius', [
            'label' => 'Border radius',
            'type' => Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 100]],
            'default' => ['size' => self::D['btn_radius'], 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('tabs_btn_styles');

        $this->start_controls_tab('tab_btn_normal', ['label' => 'Normal']);

        $this->add_control('btn_bg_normal', [
            'label' => 'Background',
            'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('btn_color_normal', [
            'label' => 'Text color',
            'type' => Controls_Manager::COLOR,
            'default' => self::D['btn_color'],
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('btn_underline_normal', [
            'label' => 'Underline',
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn' => 'text-decoration: {{VALUE}};'],
            'selectors_dictionary' => ['yes' => 'underline', '' => 'none'],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_btn_hover', ['label' => 'Hover']);

        $this->add_control('btn_bg_hover', [
            'label' => 'Background',
            'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn:hover' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('btn_color_hover', [
            'label' => 'Text color',
            'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('btn_underline_hover', [
            'label' => 'Underline',
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => '',
            'selectors' => ['{{WRAPPER}} .mautic-cta__btn:hover' => 'text-decoration: {{VALUE}};'],
            'selectors_dictionary' => ['yes' => 'underline', '' => 'none'],
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        $title = isset($s['title']) ? esc_html($s['title']) : '';
        $desc = !empty($s['description']) ? $this->parse_text_editor($s['description']) : '';
        $btn_text = isset($s['button_text']) ? esc_html($s['button_text']) : '';
        $btn_url = !empty($s['button_url']['url']) ? esc_url($s['button_url']['url']) : '';

        $openNew = (!empty($s['new_tab']) && $s['new_tab'] === 'yes');
        $rel_parts = [];
        if ($openNew) { $rel_parts[] = 'noopener'; }
        if (!empty($s['button_url']['nofollow'])) { $rel_parts[] = 'nofollow'; }
        $rel_attr = $rel_parts ? ' rel="' . esc_attr(implode(' ', array_unique($rel_parts))) . '"' : '';
        $target_attr = $openNew ? ' target="_blank"' : ' target="_self"';

        echo '<div class="mautic-cta">';
        if ($title) echo '<h2 class="mautic-cta__title">' . $title . '</h2>';
        if ($desc) echo '<div class="mautic-cta__desc">' . $desc . '</div>';
        if ($btn_text && $btn_url) {
            echo '<div class="mautic-cta__actions">';
            echo '<a class="mautic-cta__btn" href="' . $btn_url . '"' . $target_attr . $rel_attr . '>' . $btn_text . '</a>';
            echo '</div>';
        }
        echo '</div>';
    }
}
