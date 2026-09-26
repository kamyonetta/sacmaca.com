<?php
/**
 * Plugin Name: KRMF Private Companion
 * Description: Isolated /krmf/ notebook and authenticated, revision-checked sync. No changes to existing pages.
 * Version: 0.1.0
 * Requires PHP: 8.1
 */
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/protocol.php';

register_activation_hook(__FILE__, function () {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'krmf_notebooks';
    dbDelta("CREATE TABLE $table (
        user_id bigint(20) unsigned NOT NULL,
        body longtext NOT NULL,
        PRIMARY KEY  (user_id)
    ) ENGINE=InnoDB " . $wpdb->get_charset_collate() . ';');
    // No account is granted access automatically. Owner must be explicitly selected.
    add_role('krmf_companion', 'KRMF companion only', ['read' => true, 'krmf_use' => true]);
});
function krmf_allowed() {
    return true;
}
function krmf_user_id() { return max(1, get_current_user_id()); }
function krmf_private_headers() {
    nocache_headers();
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    do_action('litespeed_control_set_nocache', 'KRMF private companion');
}
add_action('admin_init', function () {
    register_setting('krmf', 'krmf_owner', ['type'=>'integer', 'sanitize_callback'=>'absint', 'default'=>0]);
});
add_action('admin_menu', function () {
    add_options_page('KRMF Companion', 'KRMF Companion', 'manage_options', 'krmf', function () {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>KRMF private companion</h1><p>Select the WordPress user ID allowed to open the notebook. Zero disables owner access. A dedicated KRMF companion-only user can also sign in. Each user has a separate notebook.</p><form method="post" action="options.php">';
        settings_fields('krmf');
        echo '<label>Owner user ID <input type="number" min="0" name="krmf_owner" value="' . esc_attr(get_option('krmf_owner', 0)) . '"></label>';
        submit_button();
        echo '</form><p>Private link: <a href="' . esc_url(home_url('/krmf/')) . '">Open KRMF</a>. Do not activate on production until staging tests pass.</p></div>';
    });
});
add_action('rest_api_init', function () {
    register_rest_route('krmf/v1', '/sync', [
        'methods'=>'POST',
        'permission_callback'=>function () {
            if (!is_ssl()) return new WP_Error('krmf_https', 'HTTPS required', ['status'=>403]);
            return krmf_allowed() ? true : new WP_Error('krmf_private', 'Private notebook', ['status'=>403]);
        },
        'callback'=>function ($request) {
            global $wpdb;
            krmf_private_headers();
            if (strlen($request->get_body()) > 1048576) return new WP_Error('krmf_size', 'Batch too large', ['status'=>413]);
            $payload = $request->get_json_params();
            try { krmf_validate_request($payload); }
            catch (Throwable $e) { return new WP_Error('krmf_invalid', $e->getMessage(), ['status'=>400]); }
            $table = $wpdb->prefix . 'krmf_notebooks'; $user = krmf_user_id();
            // A locked InnoDB row serializes all mutations for one notebook. A retry
            // receives its original receipt even if another device edited later.
            try {
                if ($wpdb->query('START TRANSACTION') === false) throw new RuntimeException();
                if ($wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (user_id,body) VALUES (%d,%s)", $user, '{"records":{},"receipts":{}}')) === false) throw new RuntimeException();
                $body = $wpdb->get_var($wpdb->prepare("SELECT body FROM $table WHERE user_id=%d FOR UPDATE", $user));
                if (!is_string($body)) throw new RuntimeException();
                $state = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $reply = krmf_exchange($state, $payload);
                $encoded = wp_json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($encoded === false || strlen($encoded) > 16 * 1024 * 1024) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('krmf_capacity', 'Notebook history needs an archival backup. Local edits are retained.', ['status'=>507]);
                }
                if ($wpdb->update($table, ['body'=>$encoded], ['user_id'=>$user], ['%s'], ['%d']) === false) throw new RuntimeException();
                if ($wpdb->query('COMMIT') === false) throw new RuntimeException();
                $response = new WP_REST_Response($reply, 200);
                $response->header('Cache-Control', 'private, no-store, max-age=0');
                return $response;
            } catch (Throwable $e) {
                $wpdb->query('ROLLBACK');
                // Never log rows, credentials or raw database errors.
                return new WP_Error('krmf_storage', 'Sync could not commit. Retry safely.', ['status'=>503]);
            }
        }
    ]);
});
add_filter('rest_pre_serve_request', function ($served) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, ['tauri://localhost', 'http://tauri.localhost'], true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: Content-Type, X-KRMF-Password');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
    }
    return $served;
}, 100);
// No rewrite flush, theme replacement, page creation or existing-route interception.
add_action('template_redirect', function () {
    $root = rtrim((string)parse_url(home_url('/krmf/'), PHP_URL_PATH), '/');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($path !== $root && $path !== $root . '/' && $path !== $root . '/sw.js') return;
    if ($path === $root) { wp_safe_redirect(home_url('/krmf/')); exit; }
    if (!is_ssl()) { wp_safe_redirect(set_url_scheme(home_url('/krmf/'), 'https')); exit; }
    krmf_private_headers();
    if ($path === $root . '/sw.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: ' . $root . '/');
        readfile(__DIR__ . '/web/sw.js'); exit;
    }
    if (!krmf_allowed()) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals('123', (string)($_POST['krmf_password'] ?? ''))) {
            setcookie('krmf_access', hash_hmac('sha256', 'krmf-access', wp_salt('auth')), ['expires'=>time()+2592000, 'path'=>$root.'/', 'secure'=>true, 'httponly'=>true, 'samesite'=>'Strict']);
            wp_safe_redirect(home_url('/krmf/')); exit;
        }
        status_header(401);
        echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>KRMF sign in</title><style>body{font-family:monospace;background:#171522;color:#fff;display:grid;place-items:center;min-height:100vh}form{border:3px solid #fff;padding:24px;box-shadow:8px 8px #7b61ff}input,button{font:inherit;padding:12px;margin-top:12px}button{cursor:pointer}</style><form method="post"><h1>KRMF</h1><label>Password<br><input name="krmf_password" type="password" autofocus></label><br><button>Enter</button></form>'; exit;
    }
    $file = __DIR__ . '/web/index.html';
    if (!is_file($file)) { status_header(503); exit('Companion assets have not been built.'); }
    status_header(200);
    header('X-KRMF-Shell: 1');
    $base = plugins_url('web/', __FILE__);
    $session = ['user'=>krmf_user_id(), 'endpoint'=>rest_url('krmf/v1/sync'), 'nonce'=>wp_create_nonce('wp_rest'), 'logout'=>home_url('/krmf/'), 'site'=>home_url('/')];
    $html = file_get_contents($file);
    $html = str_replace(['src="./', 'href="./'], ['src="'.esc_url($base), 'href="'.esc_url($base)], $html);
    $bootstrap = '<script id="krmf-session">window.KRMF_SESSION=' . wp_json_encode($session, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';</script>';
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'sha256-" . base64_encode(hash('sha256', 'window.KRMF_SESSION=' . wp_json_encode($session, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';', true)) . "'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'");
    echo str_replace('</head>', $bootstrap . '</head>', $html); exit;
}, 0);
