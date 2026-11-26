<?php
defined('ABSPATH') || exit;

function sanro_dm_public_https_url($url) {
    if (!is_string($url) || strlen($url) > 2048) return false;
    $parts = wp_parse_url($url);
    if (!$parts || empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) && (int) $parts['port'] !== 443) return false;
    $host = strtolower(rtrim($parts['host'], '.'));
    if (filter_var($host, FILTER_VALIDATE_IP)) return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    if (preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/', $host)) return false;
    $records = function_exists('dns_get_record') ? @dns_get_record($host, DNS_A | DNS_AAAA) : array();
    $ips = array();
    foreach ((array) $records as $record) { if (!empty($record['ip'])) $ips[] = $record['ip']; if (!empty($record['ipv6'])) $ips[] = $record['ipv6']; }
    if (!$ips && function_exists('gethostbynamel')) $ips = (array) @gethostbynamel($host);
    if (!$ips) return false;
    foreach (array_unique($ips) as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    return true;
}
function sanro_dm_https_link($url) {
    if(!is_string($url)||strlen($url)>2048)return false;$p=wp_parse_url($url);if(!$p||empty($p['scheme'])||strtolower($p['scheme'])!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass'])||isset($p['port'])&&(int)$p['port']!==443)return false;
    $host=rtrim($p['host'],'.');if(preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/i',$host))return false;
    return !filter_var($host,FILTER_VALIDATE_IP)||(bool)filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
}
function sanro_dm_allowed_license($license) {
    $license = strtolower(trim((string) $license));
    return in_array($license, array('cc0','pdm','by','by-sa','cc0-1.0','public-domain-mark-1.0','by-4.0','by-sa-4.0'), true);
}
function sanro_dm_openverse_search(WP_REST_Request $request) {
    $query = sanitize_text_field($request->get_param('q'));
    if (strlen($query) < 2 || strlen($query) > 100) return new WP_Error('invalid_query','Search query must contain 2–100 characters.',array('status'=>400));
    $ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown';$key='sanro_dm_openverse_search_'.md5($ip);$count=(int)get_transient($key);if($count>=30)return new WP_Error('rate_limited','Please wait before searching again.',array('status'=>429));set_transient($key,$count+1,HOUR_IN_SECONDS);
    $page = max(1,min(5,(int)$request->get_param('page')));
    $url = add_query_arg(array('q'=>$query,'page'=>$page,'page_size'=>24,'license'=>'cc0,pdm,by,by-sa','license_type'=>'commercial'), 'https://api.openverse.org/v1/images/');
    $response = wp_safe_remote_get($url,array('timeout'=>8,'redirection'=>0,'limit_response_size'=>2*MB_IN_BYTES,'headers'=>array('Accept'=>'application/json','User-Agent'=>'SANRO-Design-Maker/1.0 (+https://kaoscetak.id)')));
    if (is_wp_error($response)) return new WP_Error('provider_unavailable','Openverse search is temporarily unavailable.',array('status'=>502));
    if ((int)wp_remote_retrieve_response_code($response)!==200) return new WP_Error('provider_unavailable','Openverse search is temporarily unavailable.',array('status'=>502));
    $data=json_decode(wp_remote_retrieve_body($response),true); if (!is_array($data) || empty($data['results']) || !is_array($data['results'])) return rest_ensure_response(array());
    $items=array();
    foreach (array_slice($data['results'],0,24) as $item) {
        $license=strtolower((string)($item['license']??'')); if (!sanro_dm_allowed_license($license)) continue;
        $media=isset($item['url'])?(string)$item['url']:''; $landing=isset($item['foreign_landing_url'])?(string)$item['foreign_landing_url']:'';
        if (!sanro_dm_https_link($media) || !sanro_dm_https_link($landing)) continue;
        $licenseUrl=isset($item['license_url'])?(string)$item['license_url']:'';
        if (!sanro_dm_https_link($licenseUrl) || strtolower((string)wp_parse_url($licenseUrl,PHP_URL_HOST))!=='creativecommons.org') continue;
        $normalized=array('id'=>sanitize_text_field($item['id']??''),'title'=>sanitize_text_field($item['title']??'Openverse asset'),'creator'=>sanitize_text_field($item['creator']??''),'attribution'=>sanitize_text_field($item['attribution']??''),'license'=>$license,'license_url'=>$licenseUrl,'source_url'=>$landing,'media_url'=>$media,'width'=>absint($item['width']??0),'height'=>absint($item['height']??0),'provider'=>'openverse');
        if(!$normalized['id'])continue;set_transient('sanro_dm_openverse_'.md5($normalized['id']),$normalized,30*DAY_IN_SECONDS);$items[]=$normalized;
    }
    return rest_ensure_response($items);
}
function sanro_dm_provider_media(WP_REST_Request $request) {
    $providerId=sanitize_text_field($request->get_param('id'));$item=get_transient('sanro_dm_openverse_'.md5($providerId));if(!$item||empty($item['media_url']))return new WP_Error('provider_item_expired','Search for the asset again before importing it.',array('status'=>410));$url=$item['media_url'];if (!sanro_dm_public_https_url($url) || !wp_http_validate_url($url)) return new WP_Error('invalid_media_url','Media URL is not allowed.',array('status'=>400));
    $rateKey='sanro_dm_provider_media_'.md5(isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown');$n=(int)get_transient($rateKey);if($n>=20)return new WP_Error('rate_limited','Please wait before requesting more assets.',array('status'=>429));set_transient($rateKey,$n+1,HOUR_IN_SECONDS);
    $response=wp_safe_remote_get($url,array('timeout'=>8,'redirection'=>0,'limit_response_size'=>8*MB_IN_BYTES,'headers'=>array('Accept'=>'image/png,image/jpeg,image/webp')));
    if(is_wp_error($response)||(int)wp_remote_retrieve_response_code($response)!==200)return new WP_Error('provider_media_unavailable','Asset download is unavailable.',array('status'=>502));
    $body=wp_remote_retrieve_body($response);$mime=wp_remote_retrieve_header($response,'content-type');$mime=strtolower(trim(explode(';',(string)$mime)[0]));
    if(strlen($body)<1||strlen($body)>8*MB_IN_BYTES||!in_array($mime,array('image/png','image/jpeg','image/webp'),true)||!function_exists('getimagesizefromstring'))return new WP_Error('invalid_provider_media','Provider returned an unsupported image.',array('status'=>415));
    $image=@getimagesizefromstring($body);if(!$image||$image['mime']!==$mime||$image[0]>8000||$image[1]>8000||$image[0]*$image[1]>32000000)return new WP_Error('invalid_provider_media','Provider image failed validation.',array('status'=>415));
    $result=new WP_REST_Response($body,200);$result->header('Content-Type',$mime);$result->header('Cache-Control','private, no-store');$result->header('X-Content-Type-Options','nosniff');$result->header('X-SANRO-Provider-ID',$item['id']);return $result;
}
function sanro_dm_register_provider_routes() {
    register_rest_route('sanro-design-maker/v1','/providers/openverse',array('methods'=>'GET','callback'=>'sanro_dm_openverse_search','permission_callback'=>'__return_true'));
    register_rest_route('sanro-design-maker/v1','/providers/media',array('methods'=>'GET','callback'=>'sanro_dm_provider_media','permission_callback'=>'__return_true'));
}
