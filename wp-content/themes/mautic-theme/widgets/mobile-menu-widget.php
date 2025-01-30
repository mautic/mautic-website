<?php
namespace Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class Mobile_Menu_Widget extends Widget_Base {

    public function get_name() {
        return 'mobile_menu_widget';
    }

    public function get_title() {
        return __('Mobile Menu', 'mautic-theme');
    }

    public function get_icon() {
        return 'eicon-menu-bar';
    }

    public function get_categories() {
        return ['mautic-widgets'];
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $menu_slug = $settings['menu_slug'];

        ?>
        <div class="mobile-menu-container">
            <!-- Hamburger Icon -->
            <button class="mobile-menu-toggle" aria-label="<?php esc_attr_e('Open menu', 'mautic-theme'); ?>" aria-expanded="false" aria-controls="mobile-menu">
                <span class="hamburger"></span>
            </button>

            <!-- Slide-Out Menu -->
            <nav id="mobile-menu" class="mobile-menu" aria-hidden="true">
                <button class="menu-close" aria-label="<?php esc_attr_e('Close menu', 'mautic-theme'); ?>">×</button>
                <?php
                wp_nav_menu([
                    'menu' => $menu_slug,
                    'menu_class' => 'mobile-menu-list',
                    'container' => false,
                ]);
                ?>
            </nav>
        </div>
        <?php
    }

    public function _register_controls() {

        $this->start_controls_section(
            'general_settings',
            [
                'label' => __('General', 'mautic-theme'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );


        $this->add_control(
            'menu_slug',
            [
                'label' => __('Select Menu', 'mautic-theme'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_available_menus(),
                'default' => '',
                'description' => __('Choose the WordPress menu to display.', 'mautic-theme'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'toggle_button_section',
            [
                'label' => __('Toggle Button', 'mautic-theme'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'toggle_button_alignment',
            [
                'label' => __('Alignment', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'mautic-theme'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'mautic-theme'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'mautic-theme'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'right',
                'toggle' => true,
            ]
        );

        $this->add_control(
            'toggle_button_color',
            [
                'label' => __('Button Color', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-toggle .hamburger' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .mobile-menu-toggle .hamburger::before' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .mobile-menu-toggle .hamburger::after' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'toggle_button_background',
            [
                'label' => __('Button Background', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-toggle' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'toggle_button_size',
            [
                'label' => __('Button Size', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                ],
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-toggle' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'toggle_button_padding',
            [
                'label' => __('Button Padding', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '10',
                    'right' => '10',
                    'bottom' => '10',
                    'left' => '10',
                ],
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'toggle_button_border_radius',
            [
                'label' => __('Border Radius', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-toggle' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'menu_link_styles',
            [
                'label' => __('Menu Links', 'mautic-theme'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'link_color_normal',
            [
                'label' => __('Normal Color', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-list a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_color_hover',
            [
                'label' => __('Hover Color', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-list a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_color_active',
            [
                'label' => __('Active Color', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ff0000',
                'selectors' => [
                    '{{WRAPPER}} .mobile-menu-list .current-menu-item > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'menu_background_styles',
            [
                'label' => __('Menu Background', 'mautic-theme'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'menu_background_color',
            [
                'label' => __('Background Color', 'mautic-theme'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} #mobile-menu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Fetch all available menus in WordPress.
     *
     * @return array Associative array of menu slugs and names.
     */
    private function get_available_menus() {
        $menus = wp_get_nav_menus(); // Fetch all menus
        $menu_options = [];

        foreach ($menus as $menu) {
            $menu_options[$menu->slug] = $menu->name;
        }

        return $menu_options;
    }
}
