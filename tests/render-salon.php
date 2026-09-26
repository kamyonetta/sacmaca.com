<?php
// Execute the templates without loading production WordPress/configuration/data.
define('ABSPATH', __DIR__);
function home_url($path) { return 'https://www.sacmaca.com' . $path; }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES); }
function language_attributes() { echo 'lang="en"'; }
function bloginfo($key) { echo 'UTF-8'; }
function wp_head() {}
function wp_footer() {}
function wp_body_open() {}
function body_class() { echo 'class="home sacmaca-salon"'; }
function is_page($slug) { return false; }
function is_home() { return false; }
function get_header() { require __DIR__ . '/../wp-content/themes/astra-child/header.php'; }
function get_footer() { require __DIR__ . '/../wp-content/themes/astra-child/footer.php'; }
function have_posts() { return false; }
foreach (array('front-page.php', 'home.php') as $template) {
    ob_start();
    require __DIR__ . '/../wp-content/themes/astra-child/' . $template;
    $html = ob_get_clean();
    if (substr_count($html, '<main ') !== 1 || substr_count($html, '<h1') !== 1
        || stripos($html, 'krmf') !== false || stripos($html, 'calendar') !== false
        || strpos($html, '/contact/') === false || strpos($html, '/mp3-player/index.html') === false) {
        throw new Exception('Template invariant failed: ' . $template);
    }
    echo 'Rendered and checked: ' . $template . PHP_EOL;
}
