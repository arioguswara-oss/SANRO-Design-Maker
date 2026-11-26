<?php
/**
 * Plugin Name: SANRO Design Maker
 * Description: Guest-first graphic design editor foundation.
 * Version: 0.1.0-dev
 * Requires PHP: 7.4
 * Text Domain: sanro-design-maker
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/includes/bootstrap.php';
add_action('init', 'sanro_dm_register_editor');
add_action('admin_menu', 'sanro_dm_admin_menu');
