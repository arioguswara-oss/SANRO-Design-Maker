<?php
/**
 * Plugin Name: SANRO Design Maker
 * Description: Guest-first graphic design editor for kaoscetak.id.
 * Version: 0.1.0-dev
 * Requires PHP: 7.4
 * Text Domain: sanro-design-maker
 */
defined('ABSPATH') || exit;
define('SANRO_DM_VERSION', '0.1.0-dev');
define('SANRO_DM_FILE', __FILE__);
define('SANRO_DM_URL', plugin_dir_url(__FILE__));
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/storage.php';
add_action('init', 'sanro_dm_register_editor');
add_action('init', 'sanro_dm_storage_init', 1);
add_action('rest_api_init', 'sanro_dm_register_storage_routes');
add_action('sanro_dm_daily_gc', 'sanro_dm_gc');
register_activation_hook(__FILE__, 'sanro_dm_install_storage');
register_activation_hook(__FILE__, function () { if (!wp_next_scheduled('sanro_dm_daily_gc')) wp_schedule_event(time(), 'daily', 'sanro_dm_daily_gc'); });
register_deactivation_hook(__FILE__, function () { wp_clear_scheduled_hook('sanro_dm_daily_gc'); });
add_action('admin_menu', 'sanro_dm_admin_menu');
add_action('wp_enqueue_scripts', 'sanro_dm_enqueue_editor_assets');
