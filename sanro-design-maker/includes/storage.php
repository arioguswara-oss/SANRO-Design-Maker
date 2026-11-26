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
    return array('designs' => $wpdb->prefix . 'sanro_dm_designs', 'assets' => $wpdb->prefix . 'sanro_dm_assets');
}
function sanro_dm_install_storage() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t = sanro_dm_tables(); $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$t['designs']} (id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, title varchar(190) NOT NULL, design longtext NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_updated (user_id,updated_at)) $collate;");
    dbDelta("CREATE TABLE {$t['assets']} (id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, path varchar(255) NOT NULL, mime varchar(32) NOT NULL, bytes bigint(20) unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_id (user_id)) $collate;");
}
function sanro_dm_storage_init() {
    if (get_option('sanro_dm_storage_schema') !== '1') { sanro_dm_install_storage(); update_option('sanro_dm_storage_schema', '1', false); }
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
    @chmod($path, 0600); $wpdb->insert($tables['assets'], array('id' => $id, 'user_id' => $uid, 'path' => $relative, 'mime' => $mime, 'bytes' => (int) $file['size'], 'created_at' => current_time('mysql', true)), array('%s','%d','%s','%s','%d','%s'));
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
    if ($method==='DELETE') { $wpdb->delete($t['designs'],array('id'=>$id,'user_id'=>$uid),array('%s','%d')); return new WP_REST_Response(null,204); }
    if ($method==='PUT') { $request->set_param('id',$id); return sanro_dm_designs($request); }
    $design=json_decode($row->design,true); return rest_ensure_response(array('id'=>$id,'title'=>$row->title,'design'=>$design));
}
function sanro_dm_register_storage_routes() {
    register_rest_route('sanro-design-maker/v1','/assets',array('methods'=>'POST','callback'=>'sanro_dm_upload_asset','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/assets/(?P<id>[a-f0-9-]+)',array('methods'=>'GET','callback'=>'sanro_dm_get_asset','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/designs',array('methods'=>array('GET','POST'),'callback'=>'sanro_dm_designs','permission_callback'=>'sanro_dm_storage_auth'));
    register_rest_route('sanro-design-maker/v1','/designs/(?P<id>[a-f0-9-]+)',array('methods'=>array('GET','PUT','DELETE'),'callback'=>'sanro_dm_single_design','permission_callback'=>'sanro_dm_storage_auth'));
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
