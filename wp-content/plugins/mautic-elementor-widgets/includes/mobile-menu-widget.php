<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register widgets
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once plugin_dir_path(__FILE__) . 'widgets/mobile-menu-widget.php';
    require_once plugin_dir_path(__FILE__) . 'widgets/services-widget.php';

    $widgets_manager->register(new \Elementor\Mobile_Menu_Widget());
    $widgets_manager->register(new \Elementor\Services_Widget());
});

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'mautic-widgets-style',
        plugin_dir_url(__DIR__) . 'assets/style.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'mautic-widgets-script',
        plugin_dir_url(__DIR__) . 'assets/mobile-menu.js',
        ['jquery'],
        '1.0',
        true
    );
});
