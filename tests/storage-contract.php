<?php
define('ABSPATH', '/tmp/wp/');
define('MB_IN_BYTES', 1048576);
class WP_Error { public $code; function __construct($code) { $this->code = $code; } }
class WP_REST_Request {}
class Fake_Dm_DB {
    public $last_args = array();
    public $prefix = 'wp_';
    function prepare($sql, ...$args) { $this->last_args = $args; return $sql; }
    function get_var($sql) { return isset($this->last_args[0], $this->last_args[1]) && $this->last_args[0] === 'owned-asset' && (int) $this->last_args[1] === 7 ? 'owned-asset' : null; }
}
function untrailingslashit($value) { return rtrim($value, '/'); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function rest_url($path = '') { return 'https://site.test/wp-json/' . $path; }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_json_encode($value) { return json_encode($value); }
require __DIR__ . '/../sanro-design-maker/includes/storage.php';
$wpdb = new Fake_Dm_DB();
function expect($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$valid = json_encode(array('objects' => array(array('type' => 'image', 'src' => rest_url('sanro-design-maker/v1/assets/owned-asset'), 'data' => array('assetId' => 'owned-asset')))));
expect(!is_wp_error(sanro_dm_validate_design($valid, 7)), 'owned private asset should be accepted');
$inline = json_encode(array('objects' => array(array('type' => 'image', 'src' => 'data:image/png;base64,AAAA', 'data' => array()))));
expect(is_wp_error(sanro_dm_validate_design($inline, 7)), 'inline image data should be rejected');
$remote = json_encode(array('objects' => array(array('type' => 'image', 'src' => 'https://attacker.test/pixel.png', 'data' => array('assetId' => 'owned-asset')))));
expect(is_wp_error(sanro_dm_validate_design($remote, 7)), 'non-private image URL should be rejected');
$foreign = json_encode(array('objects' => array(array('type' => 'image', 'src' => rest_url('sanro-design-maker/v1/assets/foreign-asset'), 'data' => array('assetId' => 'foreign-asset')))));
expect(is_wp_error(sanro_dm_validate_design($foreign, 7)), 'asset owned by another account should be rejected');
$tooMany = json_encode(array('objects' => array_fill(0, 251, array('type' => 'rect'))));
expect(is_wp_error(sanro_dm_validate_design($tooMany, 7)), 'object limit should be enforced');
echo "Storage contract PASS: private references, inline/remote images, ownership and object count\n";
