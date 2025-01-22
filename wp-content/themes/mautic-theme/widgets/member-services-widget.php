<?php
/**
 * Custom Elementor Widget to Display Services for the "member" Post Type
 */

namespace Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_Services_Widget extends Widget_Base {

    public function get_name() {
        return 'member_services_widget';
    }

    public function get_title() {
        return __('Member Services', 'mautic-theme');
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return ['mautic-widgets'];
    }

    protected function render() {
        $post_id = get_the_ID();

        if ( ! $post_id || 'member' !== get_post_type($post_id) ) {
            echo '<p>' . __('This widget only applies to the Member post type.', 'mautic-theme') . '</p>';
            return;
        }

        $services = wp_get_post_terms($post_id, 'service');

        if (empty($services)) {
            echo '<p>' . __('No services found for this member.', 'mautic-theme') . '</p>';
            return;
        }

        // Output wrapper for grid layout
        echo '<div class="services-grid">';

        foreach ($services as $service) {
            echo '<div class="service-card">';
            echo '<h3>' . esc_html($service->name) . '</h3>';

            if (!empty($service->description)) {
                // If we want to allow HTML in the descriptions, we will use wp_kses_post()
                echo '<p>' . wp_kses_post($service->description) . '</p>';
            }
            echo '</div>';
        }

        // End grid wrapper
        echo '</div>';
    }
}
