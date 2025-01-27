<?php
namespace Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Mobile_Menu_Widget extends Widget_Base {

    public function get_name() {
        return 'mobile_menu_widget';
    }

    public function get_title() {
        return __('Mobile Menu', 'mautic-elementor-widgets');
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
            <button class="mobile-menu-toggle" aria-label="<?php esc_attr_e('Open menu', 'mautic-elementor-widgets'); ?>" aria-expanded="false" aria-controls="mobile-menu">
                <span class="hamburger"></span>
            </button>

            <nav id="mobile-menu" class="mobile-menu" aria-hidden="true">
                <button class="menu-close" aria-label="<?php esc_attr_e('Close menu', 'mautic-elementor-widgets'); ?>">×</button>
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

    protected function _register_controls() {
        // Register controls here (menu_slug, button styling, etc.)
    }
}
