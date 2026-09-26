<?php
// WordPress calls this file only from the plugin uninstall action.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'krmf_notebooks') . '`');
delete_option('krmf_owner');
remove_role('krmf_companion');
