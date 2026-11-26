<?php
defined('ABSPATH') || exit;
function sanro_dm_register_editor() {
    add_shortcode('sanro_design_maker', 'sanro_dm_editor_placeholder');
}
function sanro_dm_editor_placeholder() {
    return '<section class="sanro-dm" aria-label="SANRO Design Maker"><p>' . esc_html__('Editor under development.', 'sanro-design-maker') . '</p></section>';
}
function sanro_dm_admin_menu() {
    add_menu_page('SANRO Design Maker', 'SANRO Design Maker', 'manage_options', 'sanro-design-maker', 'sanro_dm_admin_page', 'dashicons-art');
}
function sanro_dm_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>SANRO Design Maker</h1><p>' . esc_html__('Development foundation only.', 'sanro-design-maker') . '</p></div>';
}
