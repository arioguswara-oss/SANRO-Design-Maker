<?php
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = array(); $GLOBALS['shortcodes'] = array();
function add_action($name, $callback) { $GLOBALS['hooks'][$name][] = $callback; }
function add_shortcode($name, $callback) { $GLOBALS['shortcodes'][$name] = $callback; }
function plugin_dir_url($file) { return 'https://example.test/plugins/sanro-design-maker/'; }
function wp_style_is($handle, $list) { return false; }
function wp_register_style(...$args) { $GLOBALS['styles'][] = $args; }
function wp_register_script(...$args) { $GLOBALS['scripts'][] = $args; }
function wp_enqueue_style($handle) { $GLOBALS['enqueued'][] = $handle; }
function wp_enqueue_script($handle) { $GLOBALS['enqueued'][] = $handle; }
function wp_localize_script(...$args) { $GLOBALS['localized'][] = $args; }
function register_activation_hook(...$args) {} function register_deactivation_hook(...$args) {}
function rest_url($path = '') { return 'https://example.test/wp-json/' . $path; }
function esc_url_raw($value) { return $value; } function is_user_logged_in() { return false; }
function wp_create_nonce($action) { return ''; } function wp_login_url($url) { return 'https://example.test/wp-login.php?redirect_to=' . rawurlencode($url); }
function wp_unslash($value) { return $value; }
function is_singular() { return true; }
function get_post() { return (object) array('post_content' => '[sanro_design_maker]'); }
function has_shortcode($content, $tag) { return strpos($content, '[' . $tag) !== false; }
function esc_attr__($value) { return $value; } function esc_html__($value) { return $value; }
function esc_url($value) { return $value; } function home_url($path) { return 'https://example.test' . $path; }
function current_user_can($cap) { return $cap === 'manage_options'; } function esc_html($value) { return $value; }
require __DIR__ . '/../sanro-design-maker/sanro-design-maker.php';
sanro_dm_register_editor(); sanro_dm_enqueue_editor_assets(); $html = sanro_dm_render_editor();
if (strpos($html, 'data-design-canvas') === false || strpos($html, 'CETAK DESAIN INI') === false || strpos($html, 'data-template-list') === false || strpos($html, 'data-shape-type="circle"') === false) throw new RuntimeException('Editor markup, templates, shapes or print CTA missing');
if (!in_array('sanro-dm-editor', $GLOBALS['enqueued'], true)) throw new RuntimeException('Editor assets not enqueued');
if (strpos($GLOBALS['scripts'][0][1], 'assets/vendor/fabric/index.min.js') === false) throw new RuntimeException('Fabric must be locally bundled');
if (strpos($html, 'https://example.test/custom/') === false) throw new RuntimeException('Print CTA target missing');
echo "PHP smoke PASS: shortcode, local assets, canvas mount and print CTA\n";
