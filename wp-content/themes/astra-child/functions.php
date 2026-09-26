<?php
/**
 * AstraChild Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package AstraChild
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRACHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function force_contact_template($template) {
    if (is_page('contact')) {
        error_log("FORCED TEMPLATE: Contact Page Loaded");
        return get_stylesheet_directory() . '/page-contact.php';
    }
    return $template;
}
add_filter('template_include', 'force_contact_template');

function child_enqueue_styles() {

	wp_enqueue_style( 'astrachild-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRACHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );