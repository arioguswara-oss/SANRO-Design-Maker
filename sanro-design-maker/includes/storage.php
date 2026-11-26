<?php
defined('ABSPATH') || exit;

/** Private persistent storage. Refuses to create anything inside either web root. */
function sanro_dm_private_root() {
    $root = dirname(untrailingslashit(ABSPATH)) . '/.sanro-design-maker-private';
    $web = realpath(ABSPATH);
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $candidate = realpath($root);
    if (!$web || !$doc || ($candidate && (strpos($candidate, $web . DIRECTORY_SEPARATOR) === 0 || $candidate === $web || strpos($candidate, $doc . DIRECTORY_SEPARATOR) === 0 || $candidate === $doc))) return new WP_Error('private_storage_unavailable', 'Private storage is unavailable.', array('status' => 503));
    if (!$candidate) {
        if (!wp_mkdir_p($root)) return new WP_Error('private_storage_unavailable', 'Private storage is unavailable.', array('status' => 503));
        @chmod($root, 0700);
        $candidate = realpath($root);
    }
    if (!$candidate || strpos($candidate, $web . DIRECTORY_SEPARATOR) === 0 || $candidate === $web || strpos($candidate, $doc . DIRECTORY_SEPARATOR) === 0 || $candidate === $doc) return new WP_Error('private_storage_unavailable', 'Private storage is unavailable.', array('status' => 503));
    return $candidate;
}

function sanro_dm_tables() {
    global $wpdb;
    return array('designs' => $wpdb->prefix . 'sanro_dm_designs', 'assets' => $wpdb->prefix . 'sanro_dm_assets', 'shares' => $wpdb->prefix . 'sanro_dm_shares', 'masters' => $wpdb->prefix . 'sanro_dm_masters');
}
function sanro_dm_install_storage() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t = sanro_dm_tables(); $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$t['designs']} (id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, title varchar(190) NOT NULL, design longtext NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_updated (user_id,updated_at)) $collate;");
    dbDelta("CREATE TABLE {$t['assets']} (id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, path varchar(255) NOT NULL, mime varchar(32) NOT NULL, bytes bigint(20) unsigned NOT NULL, source_url text NULL, attribution text NULL, license varchar(40) NULL, license_url text NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_id (user_id)) $collate;");
    dbDelta("CREATE TABLE {$t['shares']} (id char(36) NOT NULL, design_id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, token_hash char(64) NOT NULL, expires_at datetime NOT NULL, revoked_at datetime NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY token_hash (token_hash), KEY design_owner (design_id,user_id), KEY expires_at (expires_at)) $collate;");
    dbDelta("CREATE TABLE {$t['masters']} (id char(36) NOT NULL, design_id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, path varchar(255) NOT NULL, sha256 char(64) NOT NULL, bytes bigint(20) unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY design_owner (design_id,user_id), KEY user_id (user_id)) $collate;");
}
function sanro_dm_storage_init() {
    if (get_option('sanro_dm_storage_schema') !== '4') { sanro_dm_install_storage(); update_option('sanro_dm_storage_schema', '4', false); }
}
function sanro_dm_uuid() {
    return sprintf('%04x%04x-%04x-4%03x-%04x-%04x%04x%04x', random_int(0, 65535), random_int(0, 65535), random_int(0, 65535), random_int(0, 4095), random_int(0, 16383) | 0x8000, random_int(0, 65535), random_int(0, 65535), random_int(0, 65535));
}
function sanro_dm_storage_auth() { return is_user_logged_in() && wp_verify_nonce(isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '', 'wp_rest'); }
function sanro_dm_rate_limit($user_id, $action, $limit) {
    $key = 'sanro_dm_' . $action . '_' . (int) $user_id; $n = (int) get_transient($key);
    if ($n >= $limit) return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
    set_transient($key, $n + 1, HOUR_IN_SECONDS); return true;
}
function sanro_dm_upload_asset(WP_REST_Request $request) {
    global $wpdb;
    $uid = get_current_user_id(); $rate = sanro_dm_rate_limit($uid, 'upload', 30); if (is_wp_error($rate)) return $rate;
    $files = $request->get_file_params(); $file = isset($files['file']) ? $files['file'] : null;
    if (!$file || !isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name']) || $file['size'] < 1 || $file['size'] > 8 * MB_IN_BYTES) return new WP_Error('invalid_upload', 'Upload must be an image up to 8 MiB.', array('status' => 400));
    if (!class_exists('finfo') || !function_exists('getimagesize')) return new WP_Error('image_validation_unavailable', 'Image validation is unavailable.', array('status' => 503));
    $info = @getimagesize($file['tmp_name']); $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
    $allowed = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
    if (!$info || !isset($allowed[$mime]) || $info['mime'] !== $mime || $info[0] > 8000 || $info[1] > 8000 || ($info[0] * $info[1]) > 32000000) return new WP_Error('invalid_image', 'Only valid PNG, JPEG or WebP images within dimension limits are accepted.', array('status' => 400));
    $tables = sanro_dm_tables(); $used = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(bytes),0) FROM {$tables['assets']} WHERE user_id=%d", $uid));
    if ($used + (int) $file['size'] > 64 * MB_IN_BYTES || (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['assets']} WHERE user_id=%d", $uid)) >= 100) return new WP_Error('quota_exceeded', 'Private asset quota exceeded.', array('status' => 413));
    $root = sanro_dm_private_root(); if (is_wp_error($root)) return $root;
    $id = sanro_dm_uuid(); $relative = 'assets/' . $uid . '/' . $id . '.' . $allowed[$mime]; $path = $root . '/' . $relative;
    if (!wp_mkdir_p(dirname($path)) || !move_uploaded_file($file['tmp_name'], $path)) return new WP_Error('storage_failed', 'Private asset storage failed.', array('status' => 500));
    @chmod($path, 0600); $source=(string)$request->get_param('source_url');$attribution=sanitize_text_field($request->get_param('attribution'));$license=sanitize_text_field($request->get_param('license'));$licenseUrl=(string)$request->get_param('license_url');$providerId=sanitize_text_field($request->get_param('provider_id'));
    if ($source || $license || $licenseUrl || $attribution || $providerId) { $verified=$providerId?get_transient('sanro_dm_openverse_'.md5($providerId)):false;if(!$verified||!hash_equals((string)$verified['source_url'],$source)||!hash_equals((string)$verified['attribution'],$attribution)||!hash_equals((string)$verified['license'],$license)||!hash_equals((string)$verified['license_url'],$licenseUrl)||!sanro_dm_allowed_license($license)||!sanro_dm_public_https_url($source)||!sanro_dm_public_https_url($licenseUrl)){@unlink($path);return new WP_Error('invalid_provenance','Asset source and commercial-use license do not match a verified Openverse result.',array('status'=>400));} }
    $wpdb->insert($tables['assets'], array('id' => $id, 'user_id' => $uid, 'path' => $relative, 'mime' => $mime, 'bytes' => (int) $file['size'], 'source_url'=>$source, 'attribution'=>$attribution, 'license'=>$license, 'license_url'=>$licenseUrl, 'created_at' => current_time('mysql', true)), array('%s','%d','%s','%s','%d','%s','%s','%s','%s','%s'));
    return new WP_REST_Response(array('id' => $id, 'url' => rest_url('sanro-design-maker/v1/assets/' . $id), 'mime' => $mime), 201);
}
function sanro_dm_get_asset(WP_REST_Request $request) {
    global $wpdb; $id = sanitize_text_field($request['id']); $t = sanro_dm_tables();
    $asset = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['assets']} WHERE id=%s AND user_id=%d", $id, get_current_user_id()));
    if (!$asset) return new WP_Error('not_found', 'Asset not found.', array('status' => 404));
    $root = sanro_dm_private_root(); if (is_wp_error($root)) return $root;
    $path = realpath($root . '/' . $asset->path);
    if (!$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) return new WP_Error('not_found', 'Asset not found.', array('status' => 404));
    header('Content-Type: ' . $asset->mime); header('Content-Length: ' . filesize($path)); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff'); readfile($path); exit;
}
function sanro_dm_validate_design($raw, $uid) {
    if (!is_string($raw) || strlen($raw) > 2 * MB_IN_BYTES) return new WP_Error('invalid_design', 'Design is too large.', array('status' => 413));
    $design = json_decode($raw, true);
    if (!is_array($design) || !isset($design['objects']) || !is_array($design['objects']) || count($design['objects']) > 250) return new WP_Error('invalid_design', 'Design format is invalid.', array('status' => 400));
    $invalid = false;
    $check = function (&$value, $key = '') use (&$check, &$invalid, $uid) {
        if (is_array($value)) { foreach ($value as $k => &$v) $check($v, (string) $k); return; }
        if (is_string($value) && preg_match('/^data:/i', $value)) $invalid = true;
        if ($key === 'src' && is_string($value) && $value !== '' && strpos($value, rest_url('sanro-design-maker/v1/assets/')) !== 0) $invalid = true;
        if ($key === 'assetId' && $value) { global $wpdb; $t = sanro_dm_tables(); $owned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['assets']} WHERE id=%s AND user_id=%d", sanitize_text_field($value), $uid)); if (!$owned) $invalid = true; }
    };
    $check($design); if ($invalid) return new WP_Error('invalid_design_asset', 'Design contains an unowned or non-private image.', array('status' => 400)); return wp_json_encode($design);
}
function sanro_dm_designs(WP_REST_Request $request) {
    global $wpdb; $uid = get_current_user_id(); $rate = sanro_dm_rate_limit($uid, 'design', 120); if (is_wp_error($rate)) return $rate; $t = sanro_dm_tables();
    if ($request->get_method() === 'GET') {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id,title,created_at,updated_at FROM {$t['designs']} WHERE user_id=%d ORDER BY updated_at DESC LIMIT 100", $uid), ARRAY_A); return rest_ensure_response($rows);
    }
    $raw = $request->get_param('design'); $json = sanro_dm_validate_design($raw, $uid); if (is_wp_error($json)) return $json;
    $id = sanitize_text_field($request->get_param('id')); $title = sanitize_text_field($request->get_param('title')); if (!$title) $title = 'Desain tanpa judul'; $title = function_exists('mb_substr') ? mb_substr($title, 0, 190) : substr($title, 0, 190);
    if ($id) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['designs']} WHERE id=%s AND user_id=%d", $id, $uid));
        if (!$exists) return new WP_Error('not_found', 'Design not found.', array('status' => 404));
        $wpdb->update($t['designs'], array('title'=>$title,'design'=>$json,'updated_at'=>current_time('mysql', true)), array('id'=>$id,'user_id'=>$uid), array('%s','%s','%s'), array('%s','%d'));
    } else {
        if ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['designs']} WHERE user_id=%d", $uid)) >= 100) return new WP_Error('quota_exceeded', 'Design quota exceeded.', array('status'=>413));
        $id = sanro_dm_uuid(); $wpdb->insert($t['designs'], array('id'=>$id,'user_id'=>$uid,'title'=>$title,'design'=>$json,'created_at'=>current_time('mysql', true),'updated_at'=>current_time('mysql', true)), array('%s','%d','%s','%s','%s','%s'));
    }
    return new WP_REST_Response(array('id'=>$id,'title'=>$title), 200);
}
function sanro_dm_single_design(WP_REST_Request $request) {
    global $wpdb; $uid=get_current_user_id(); $id=sanitize_text_field($request['id']); $t=sanro_dm_tables(); $method=$request->get_method();
    $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['designs']} WHERE id=%s AND user_id=%d",$id,$uid)); if (!$row) return new WP_Error('not_found','Design not found.',array('status'=>404));
    if ($method==='DELETE') { if(function_exists('sanro_dm_cleanup_design_privates'))sanro_dm_cleanup_design_privates($id,$uid);$wpdb->delete($t['designs'],array('id'=>$id,'user_id'=>$uid),array('%s','%d')); return new WP_REST_Response(null,204); }
    if ($method==='PUT') { $request->set_param('id',$id); return sanro_dm_designs($request); }
    $design=json_decode($row->design,true); return rest_ensure_response(array('id'=>$id,'title'=>$row->title,'design'=>$design));
}
function sanro_dm_public_templates() {return rest_ensure_response((array)get_option('sanro_dm_templates',array()));}
function sanro_dm_create_share(WP_REST_Request $request) {
    global $wpdb;$uid=get_current_user_id();$rate=sanro_dm_rate_limit($uid,'share',15);if(is_wp_error($rate))return $rate;$id=sanitize_text_field($request['id']);$t=sanro_dm_tables();
    $owned=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['designs']} WHERE id=%s AND user_id=%d",$id,$uid));if(!$owned)return new WP_Error('not_found','Design not found.',array('status'=>404));
    $token=bin2hex(random_bytes(32));$shareId=sanro_dm_uuid();$expires=gmdate('Y-m-d H:i:s',time()+7*DAY_IN_SECONDS);
    $wpdb->update($t['shares'],array('revoked_at'=>current_time('mysql',true)),array('design_id'=>$id,'user_id'=>$uid,'revoked_at'=>null),array('%s'),array('%s','%d','%s'));
    $wpdb->insert($t['shares'],array('id'=>$shareId,'design_id'=>$id,'user_id'=>$uid,'token_hash'=>hash('sha256',$token),'expires_at'=>$expires,'revoked_at'=>null,'created_at'=>current_time('mysql',true)),array('%s','%s','%d','%s','%s','%s','%s'));
    return new WP_REST_Response(array('id'=>$shareId,'url'=>home_url('/design-maker/#share='.$token),'expires_at'=>$expires),201);
}
function sanro_dm_list_shares(WP_REST_Request $request) {
    global $wpdb;$id=sanitize_text_field($request['id']);$t=sanro_dm_tables();$owned=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['designs']} WHERE id=%s AND user_id=%d",$id,get_current_user_id()));if(!$owned)return new WP_Error('not_found','Design not found.',array('status'=>404));
    $rows=$wpdb->get_results($wpdb->prepare("SELECT id,expires_at FROM {$t['shares']} WHERE design_id=%s AND user_id=%d AND revoked_at IS NULL AND expires_at>%s ORDER BY created_at DESC",$id,get_current_user_id(),current_time('mysql',true)),ARRAY_A);return rest_ensure_response($rows);
}
function sanro_dm_revoke_share(WP_REST_Request $request) {
    global $wpdb;$id=sanitize_text_field($request['id']);$t=sanro_dm_tables();$changed=$wpdb->update($t['shares'],array('revoked_at'=>current_time('mysql',true)),array('id'=>$id,'user_id'=>get_current_user_id(),'revoked_at'=>null),array('%s'),array('%s','%d','%s'));if(!$changed)return new WP_Error('not_found','Share link not found.',array('status'=>404));return new WP_REST_Response(array('revoked'=>true),200);
}
function sanro_dm_public_share_row($token) {
    global $wpdb;$t=sanro_dm_tables();$hash=hash('sha256',(string)$token);$row=$wpdb->get_row($wpdb->prepare("SELECT s.*,d.title,d.design FROM {$t['shares']} s INNER JOIN {$t['designs']} d ON d.id=s.design_id AND d.user_id=s.user_id WHERE s.token_hash=%s AND s.revoked_at IS NULL AND s.expires_at>%s",$hash,current_time('mysql',true)));return $row;
}
function sanro_dm_share_references_asset($design,$assetId) {foreach((array)($design['objects']??array()) as $object)if(isset($object['data']['assetId'])&&hash_equals((string)$object['data']['assetId'],(string)$assetId))return true;return false;}
function sanro_dm_get_public_share(WP_REST_Request $request) {
    $row=sanro_dm_public_share_row($request['token']);if(!$row)return new WP_Error('not_found','Share link is expired or revoked.',array('status'=>404));return rest_ensure_response(array('title'=>$row->title,'design'=>json_decode($row->design,true),'read_only'=>true));
}
function sanro_dm_get_shared_asset(WP_REST_Request $request) {
    global $wpdb;$row=sanro_dm_public_share_row($request['token']);if(!$row)return new WP_Error('not_found','Share link is expired or revoked.',array('status'=>404));$assetId=sanitize_text_field($request['asset']);$design=json_decode($row->design,true);
    if(!sanro_dm_share_references_asset($design,$assetId))return new WP_Error('not_found','Shared asset not found.',array('status'=>404));$t=sanro_dm_tables();$asset=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['assets']} WHERE id=%s AND user_id=%d",$assetId,$row->user_id));if(!$asset)return new WP_Error('not_found','Shared asset not found.',array('status'=>404));
    $root=sanro_dm_private_root();if(is_wp_error($root))return $root;$path=realpath($root.'/'.$asset->path);if(!$path||strpos($path,$root.DIRECTORY_SEPARATOR)!==0||!is_file($path))return new WP_Error('not_found','Shared asset not found.',array('status'=>404));header('Content-Type: '.$asset->mime);header('Content-Length: '.filesize($path));header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');readfile($path);exit;
}
function sanro_dm_register_storage_routes() {
    register_rest_route('sanro-design-maker/v1','/assets',array('methods'=>'POST','callback'=>'sanro_dm_upload_asset','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/assets/(?P<id>[a-f0-9-]+)',array('methods'=>'GET','callback'=>'sanro_dm_get_asset','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/templates',array('methods'=>'GET','callback'=>'sanro_dm_public_templates','permission_callback'=>'__return_true'));
    register_rest_route('sanro-design-maker/v1','/designs',array('methods'=>array('GET','POST'),'callback'=>'sanro_dm_designs','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/designs/(?P<id>[a-f0-9-]+)',array('methods'=>array('GET','PUT','DELETE'),'callback'=>'sanro_dm_single_design','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/designs/(?P<id>[a-f0-9-]+)/shares',array(array('methods'=>'GET','callback'=>'sanro_dm_list_shares','permission_callback'=>'sanro_dm_storage_auth'),array('methods'=>'POST','callback'=>'sanro_dm_create_share','permission_callback'=>'sanro_dm_storage_auth')));
    register_rest_route('sanro-design-maker/v1','/shares/(?P<id>[a-f0-9-]+)',array('methods'=>'DELETE','callback'=>'sanro_dm_revoke_share','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/shares/(?P<token>[a-f0-9]{64})',array('methods'=>'GET','callback'=>'sanro_dm_get_public_share','permission_callback'=>'__return_true'));
    register_rest_route('sanro-design-maker/v1','/shares/(?P<token>[a-f0-9]{64})/assets/(?P<asset>[a-f0-9-]+)',array('methods'=>'GET','callback'=>'sanro_dm_get_shared_asset','permission_callback'=>'__return_true'));
}
function sanro_dm_gc() {
    global $wpdb; $t=sanro_dm_tables(); $cutoff=gmdate('Y-m-d H:i:s',time()-180*DAY_IN_SECONDS); $rows=$wpdb->get_results($wpdb->prepare("SELECT id,path FROM {$t['assets']} WHERE created_at < %s",$cutoff));
    if (!$rows) return; $root=sanro_dm_private_root(); if (is_wp_error($root)) return;
    foreach ($rows as $row) {
        $referenced = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['designs']} WHERE design LIKE %s LIMIT 1", '%' . $wpdb->esc_like($row->id) . '%'));
        if ($referenced) continue;
        $p=realpath($root.'/'.$row->path); if ($p && strpos($p,$root.DIRECTORY_SEPARATOR)===0 && is_file($p)) @unlink($p); $wpdb->delete($t['assets'],array('id'=>$row->id),array('%s'));
    }
}
