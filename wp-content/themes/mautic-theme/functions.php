<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.3.9' );


/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

function my_query_by_different_order( $query ) {
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'meta_key', 'member_type' );
	$query->set( 'order', 'DESC' ); // Set ascending order

}
add_action( 'elementor/query/25', 'my_query_by_different_order' );
add_action( 'elementor/query/26', 'my_query_by_different_order' );

function linked_partner_info($atts) {
    global $post;

    // Haal de partner-ID(s) op uit het relatieveld  // Get the partner ID(s) from the relationship field
    $partner = get_field('link_member_or_partner', $post->ID);

    if ($partner) {
        // Controleer of het veld een array is (zoals bij ACF-relatievelden) // Check if the field is an array (like ACF relationship fields)
        if (is_array($partner)) {
            $partner_post = get_post($partner[0]); // Pak de eerste partner als er meerdere zijn // Get the first partner if there are multiple
        } else {
            $partner_post = get_post($partner); // Als het geen array is  // If it's not an array
        }

        // Bouw de HTML voor de output
        return '<div class="partner-info">' .
               '<h3>' . esc_html($partner_post->post_title) . '</h3>' .
               '<p>' . esc_html(get_the_excerpt($partner_post->ID)) . '</p>' .
               '</div>';
    } else {
        return 'No partner found.';
    }
}
add_shortcode('linked_partner', 'linked_partner_info');

function add_blog_slug_to_posts($post_link, $post) {
    if ($post->post_type === 'post') { // Only apply to blogs
        return home_url('blog/' . $post->post_name);
    }
    return $post_link;
}
add_filter('post_type_link', 'add_blog_slug_to_posts', 10, 2);

function add_blog_slug_rewrite_rules($rules) {
    $new_rules = array(
        'blog/([^/]+)?$' => 'index.php?name=$matches[1]', // Voor losse berichten // For single posts
    );
    return $new_rules + $rules;
}
add_filter('rewrite_rules_array', 'add_blog_slug_rewrite_rules');

function update_post_permalink($url, $post) {
    // Controleer of het een bericht is // Check if it's a post
    if ($post->post_type === 'post') {
        // Voeg de 'blog'-slug toe aan de URL // Add the 'blog' slug to the URL
        return home_url('blog/' . $post->post_name);
    }
    return $url;
}
add_filter('post_link', 'update_post_permalink', 10, 2);

/**
 * Load & Register the Member Services Widget.
 */
function mautic_add_widget_categories( $elements_manager ) {
    $elements_manager->add_category(
        'mautic-widgets',
        [
            'title' => esc_html__( 'Mautic Widgets', 'mautic-theme' ),
            'icon' => 'fa fa-plug',
        ]
    );
}
add_action( 'elementor/elements/categories_registered', 'mautic_add_widget_categories' );

function mautic_load_member_services_widget() {
    require_once get_stylesheet_directory() . '/widgets/member-services-widget.php';
}
add_action('elementor/widgets/register', 'mautic_load_member_services_widget', 5);

function mautic_register_member_services_widget($widgets_manager) {
    $widgets_manager->register(new \Elementor\Member_Services_Widget());
}
add_action('elementor/widgets/register', 'mautic_register_member_services_widget', 10);

/**
 * Load & Register the Mobile Menu Widget.
 */
function register_mobile_menu_widget($widgets_manager) {
    require_once get_stylesheet_directory() . '/widgets/mobile-menu-widget.php';
    $widgets_manager->register(new \Elementor\Mobile_Menu_Widget());
}
add_action('elementor/widgets/register', 'register_mobile_menu_widget');

function enqueue_mobile_menu_scripts() {
    wp_enqueue_script(
        'mobile-menu',
        get_stylesheet_directory_uri() . '/js/mobile-menu.js',
        [],
        HELLO_ELEMENTOR_CHILD_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_mobile_menu_scripts');



?>