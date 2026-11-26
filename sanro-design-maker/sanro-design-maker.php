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
add_action('init', 'sanro_dm_register_editor');
add_action('admin_menu', 'sanro_dm_admin_menu');
add_action('wp_enqueue_scripts', 'sanro_dm_enqueue_editor_assets');
