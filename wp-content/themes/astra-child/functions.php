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

/** The public salon: shared presentation, existing WordPress content/routes. */
function sacmaca_salon_assets() {
    $base = get_stylesheet_directory();
    wp_enqueue_style('sacmaca-salon', get_stylesheet_directory_uri() . '/salon.css', array('astrachild-theme-css'), filemtime($base . '/salon.css'));
    if (is_front_page()) {
        wp_enqueue_script('sacmaca-salon', get_stylesheet_directory_uri() . '/salon.js', array(), filemtime($base . '/salon.js'), true);
    }
}
add_action('wp_enqueue_scripts', 'sacmaca_salon_assets', 30);

function sacmaca_salon_setup() {
    // The old Customizer CSS hides all headers/cursors and breaks mobile layouts.
    // Keep it saved in WordPress, but replace its presentation with salon.css.
    remove_action('wp_head', 'wp_custom_css_cb', 101);
}
add_action('wp', 'sacmaca_salon_setup');

function sacmaca_salon_body_class($classes) {
    $classes[] = 'sacmaca-salon';
    return $classes;
}
add_filter('body_class', 'sacmaca_salon_body_class');

function sacmaca_salon_refresh_page_cache() {
    $release = 'salon-20260926';
    // front-page.php is deployed last. Purge old HTML only once the release is complete.
    if (file_exists(get_stylesheet_directory() . '/front-page.php')
        && has_action('litespeed_purge_all')
        && get_option('sacmaca_salon_release') !== $release) {
        do_action('litespeed_purge_all');
        update_option('sacmaca_salon_release', $release, false);
    }
}
add_action('wp', 'sacmaca_salon_refresh_page_cache', 20);
