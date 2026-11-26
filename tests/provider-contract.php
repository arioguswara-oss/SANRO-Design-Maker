<?php
define('ABSPATH','/tmp/');
class WP_Error { public $code; function __construct($code) { $this->code=$code; } }
class WP_REST_Request {}
function wp_parse_url($url,$component=-1) { return parse_url($url,$component); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value)); }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_json_encode($value) { return json_encode($value); }
function expect_provider($ok,$message) { if(!$ok)throw new RuntimeException($message); }
require __DIR__.'/../sanro-design-maker/includes/providers.php';
require __DIR__.'/../sanro-design-maker/includes/admin.php';
expect_provider(sanro_dm_allowed_license('cc0')&&sanro_dm_allowed_license('by')&&sanro_dm_allowed_license('by-sa'),'commercially compatible license allowlist missing');
expect_provider(!sanro_dm_allowed_license('by-nc')&&!sanro_dm_allowed_license('by-nd'),'non-commercial or no-derivatives license must be rejected');
expect_provider(sanro_dm_https_link('https://api.openverse.org/v1/images/'),'official HTTPS API link should pass');
expect_provider(!sanro_dm_https_link('http://api.openverse.org/'),'HTTP should be rejected');
expect_provider(!sanro_dm_https_link('https://user:pass@api.openverse.org/'),'credential-bearing URL should be rejected');
expect_provider(!sanro_dm_public_https_url('https://127.0.0.1/image.png'),'loopback SSRF target should be rejected');
expect_provider(!sanro_dm_public_https_url('https://10.0.0.1/image.png'),'private IPv4 SSRF target should be rejected');
expect_provider(!sanro_dm_public_https_url('https://[::1]/image.png'),'IPv6 loopback SSRF target should be rejected');
expect_provider(!sanro_dm_public_https_url('https://files.internal/image.png'),'internal hostname should be rejected');
$valid=sanro_dm_validate_template_catalog(array(array('id'=>'safe-template','title'=>'Safe','design'=>array('objects'=>array(array('type'=>'rect','fill'=>'#222222'))))));
expect_provider(is_array($valid)&&count($valid)===1,'safe vector template should pass');
$unsafe=sanro_dm_validate_template_catalog(array(array('id'=>'unsafe','title'=>'Unsafe','design'=>array('objects'=>array(array('type'=>'image','src'=>'https://attacker.test/a.png'))))));
expect_provider(is_wp_error($unsafe),'external image in public template must be rejected');
expect_provider(sanro_dm_font_source_matches('Poppins','https://fonts.google.com/specimen/Poppins'),'matching official font page should pass');
expect_provider(!sanro_dm_font_source_matches('Poppins','https://fonts.google.com/specimen/Anton'),'font provenance must match selected family');
echo "Provider contract PASS: license allowlist, URL policy, SSRF targets and template image safety\n";
