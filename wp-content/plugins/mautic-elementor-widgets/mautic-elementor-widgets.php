<?php
/**
 * Plugin Name: Mautic Elementor Widgets
 * Description: Custom Elementor widgets for Mautic.
 * Version: 1.0.0
 * Author: Achilles Poloynis
 * Text Domain: mautic-elementor-widgets
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('MAUTIC_WIDGETS_PATH', plugin_dir_path(__FILE__));
define('MAUTIC_WIDGETS_URL', plugin_dir_url(__FILE__));

require_once MAUTIC_WIDGETS_PATH . 'includes/mobile-menu-widget.php';
require_once MAUTIC_WIDGETS_PATH . 'includes/member-services-widget.php';

function mautic_enqueue_widget_assets() {
    wp_enqueue_style('mautic-widgets-style', MAUTIC_WIDGETS_URL . 'includes/assets/style.css', [], '1.0.0');
    wp_enqueue_script('mautic-mobile-menu', MAUTIC_WIDGETS_URL . 'includes/assets/mobile-menu.js', [], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'mautic_enqueue_widget_assets');

function mautic_register_widgets($widgets_manager) {
    require_once MAUTIC_WIDGETS_PATH . 'includes/mobile-menu-widget.php';
    require_once MAUTIC_WIDGETS_PATH . 'includes/member-services-widget.php';

    $widgets_manager->register(new \Elementor\Mobile_Menu_Widget());
    $widgets_manager->register(new \Elementor\Member_Services_Widget());
}
add_action('elementor/widgets/register', 'mautic_register_widgets');
