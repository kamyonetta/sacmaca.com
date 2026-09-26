<?php
/** Public site shell. Content and form processing remain in their templates. */
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17131f">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="salon-skip" href="#content">Skip to content</a>
<div id="page" class="site">
    <header class="salon-header">
        <a class="salon-wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Saçmaca home">saçmaca<span aria-hidden="true">✳</span></a>
        <span class="salon-header-note">a very personal corner<br>of the internet</span>
        <nav class="salon-nav" aria-label="Main navigation">
            <a href="<?php echo esc_url(home_url('/bio-kiz/')); ?>"<?php if (is_page('bio-kiz')) { echo ' aria-current="page"'; } ?>>The lore <span>01</span></a>
            <a href="<?php echo esc_url(home_url('/blog/')); ?>"<?php if (is_home()) { echo ' aria-current="page"'; } ?>>The blog <span>02</span></a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>"<?php if (is_page('contact')) { echo ' aria-current="page"'; } ?>>Say hello <span aria-hidden="true">↗</span></a>
        </nav>
    </header>
    <div id="content" class="site-content" tabindex="-1">
    <div class="ast-container">
