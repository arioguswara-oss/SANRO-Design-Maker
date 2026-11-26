<?php
defined('ABSPATH') || exit;
function sanro_dm_register_editor() {
    add_shortcode('sanro_design_maker', 'sanro_dm_render_editor');
}

function sanro_dm_editor_body_class($classes) {
    $page = get_post();
    if ($page && has_shortcode($page->post_content, 'sanro_design_maker')) { $classes[] = 'sanro-dm-fullscreen'; }
    return $classes;
}
add_filter('body_class', 'sanro_dm_editor_body_class');

function sanro_dm_register_assets() {
    if (!wp_style_is('sanro-dm-editor', 'registered')) {
        wp_register_style('sanro-dm-editor', SANRO_DM_URL . 'assets/editor.css', array(), SANRO_DM_VERSION);
        wp_register_style('sanro-dm-templates', SANRO_DM_URL . 'assets/templates.css', array('sanro-dm-editor'), SANRO_DM_VERSION);
        wp_register_style('sanro-dm-shapes', SANRO_DM_URL . 'assets/shapes.css', array('sanro-dm-templates'), SANRO_DM_VERSION);
        wp_register_style('sanro-dm-mobile', SANRO_DM_URL . 'assets/mobile-controls.css', array('sanro-dm-shapes'), SANRO_DM_VERSION);
        wp_register_script('sanro-dm-fabric', SANRO_DM_URL . 'assets/vendor/fabric/index.min.js', array(), '7.4.0', true);
        wp_register_script('sanro-dm-remove-bg', SANRO_DM_URL . 'assets/remove-background.js', array(), SANRO_DM_VERSION, true);
        wp_register_script('sanro-dm-controls', SANRO_DM_URL . 'assets/object-controls.js', array('sanro-dm-fabric'), SANRO_DM_VERSION, true);
        wp_register_script('sanro-dm-editor', SANRO_DM_URL . 'assets/editor.js', array('sanro-dm-fabric','sanro-dm-remove-bg','sanro-dm-controls'), SANRO_DM_VERSION, true);
        wp_localize_script('sanro-dm-editor', 'SANRO_DM_API', array(
            'root' => esc_url_raw(rest_url('sanro-design-maker/v1/')),
            'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'loggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url(home_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/design-maker/')),
            'fonts' => array_values(array_map(function ($font) { return array('family'=>$font['family'],'url'=>$font['url']); }, (array)get_option('sanro_dm_fonts',array()))),
            'watermark' => sanitize_text_field(get_option('sanro_dm_watermark','kaoscetak.id')),
        ));
        $fontCss='@font-face{font-family:"Anton";src:url("'.esc_url(SANRO_DM_URL.'assets/fonts/Anton-Regular.ttf').'") format("truetype");font-style:normal;font-weight:400;font-display:swap;}';foreach((array)get_option('sanro_dm_fonts',array()) as $font){if(empty($font['family'])||empty($font['file'])||!in_array($font['family'],sanro_dm_font_families(),true)||basename($font['file'])!==$font['file']||pathinfo($font['file'],PATHINFO_EXTENSION)!=='woff2')continue;$uploads=wp_upload_dir();$url=trailingslashit($uploads['baseurl']).'sanro-design-maker-fonts/'.rawurlencode($font['file']);$fontCss.='@font-face{font-family:"'.esc_attr($font['family']).'";src:url("'.esc_url($url).'") format("woff2");font-style:normal;font-weight:100 900;font-display:swap;}';}
        if($fontCss)wp_add_inline_style('sanro-dm-editor',$fontCss);
    }
}

function sanro_dm_enqueue_editor_assets() {
    if (!is_singular()) { return; }
    $page = get_post();
    if (!$page || !has_shortcode($page->post_content, 'sanro_design_maker')) { return; }
    sanro_dm_register_assets();
    wp_enqueue_style('sanro-dm-editor');
    wp_enqueue_style('sanro-dm-templates');
    wp_enqueue_style('sanro-dm-shapes');
    wp_enqueue_style('sanro-dm-mobile');
    wp_enqueue_script('sanro-dm-editor');
}
function sanro_dm_referrer_policy() { $page=get_post();if($page&&has_shortcode($page->post_content,'sanro_design_maker'))echo '<meta name="referrer" content="no-referrer">'; }

function sanro_dm_render_editor() {
    sanro_dm_register_assets();
    wp_enqueue_style('sanro-dm-editor');
    wp_enqueue_style('sanro-dm-templates');
    wp_enqueue_style('sanro-dm-shapes');
    wp_enqueue_style('sanro-dm-mobile');
    wp_enqueue_script('sanro-dm-editor');
    ob_start();
    ?>
    <main class="sanro-dm" data-sanro-dm aria-label="<?php echo esc_attr__('SANRO Design Maker', 'sanro-design-maker'); ?>">
      <header class="sanro-dm__header">
        <div class="sanro-dm__brand-group">
          <a class="sanro-dm__brand" href="<?php echo esc_url(home_url('/design-maker/')); ?>">SANRO <span>DESIGN MAKER</span></a>
          <a class="sanro-dm__home" data-home-link href="<?php echo esc_url(home_url('/')); ?>" aria-label="Kembali ke halaman utama" title="Beranda">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 10.8 12 3l9 7.8v9.1a1.1 1.1 0 0 1-1.1 1.1h-5.2v-6.4H9.3V21H4.1A1.1 1.1 0 0 1 3 19.9z" fill="currentColor"/></svg>
          </a>
        </div>
        <div class="sanro-dm__header-actions">
          <button type="button" data-action="undo" aria-label="<?php echo esc_attr__('Undo', 'sanro-design-maker'); ?>" title="Undo">↶</button>
          <button type="button" data-action="redo" aria-label="<?php echo esc_attr__('Redo', 'sanro-design-maker'); ?>" title="Redo">↷</button>
          <button type="button" data-action="download-preview">Preview</button>
          <span class="sanro-dm__saved" data-save-status role="status" aria-live="polite">Draft lokal</span>
        </div>
      </header>
      <div class="sanro-dm__workspace">
        <section class="sanro-dm__template-panel" data-template-panel hidden aria-label="<?php echo esc_attr__('Editable starter templates', 'sanro-design-maker'); ?>">
          <div class="sanro-dm__template-heading"><strong>Template editable</strong><button type="button" data-action="panel-close" aria-label="<?php echo esc_attr__('Close templates', 'sanro-design-maker'); ?>">×</button></div>
          <div class="sanro-dm__template-list" data-template-list></div>
        </section>
        <section class="sanro-dm__shape-panel" data-shape-panel hidden aria-label="<?php echo esc_attr__('Choose a shape', 'sanro-design-maker'); ?>">
          <div class="sanro-dm__panel-heading"><strong>Bentuk</strong><button type="button" data-action="panel-close" aria-label="Tutup bentuk">×</button></div>
          <button type="button" data-shape-type="rect">Persegi</button><button type="button" data-shape-type="circle">Lingkaran</button>
          <button type="button" data-shape-type="triangle">Segitiga</button><button type="button" data-shape-type="line">Garis</button>
        </section>
        <section class="sanro-dm__asset-panel" data-asset-panel hidden aria-label="Cari aset berlisensi komersial">
          <div class="sanro-dm__panel-heading"><strong>Aset</strong><button type="button" data-action="panel-close" aria-label="Tutup aset">×</button></div>
          <label>Cari aset Openverse <input type="search" data-asset-query maxlength="100" placeholder="Contoh: mountain illustration"></label>
          <button type="button" data-action="asset-search">Cari aset</button><div data-asset-results aria-live="polite"></div>
        </section>
        <aside class="sanro-dm__rail" aria-label="<?php echo esc_attr__('Design tools', 'sanro-design-maker'); ?>">
          <button type="button" data-action="add-text"><span aria-hidden="true">T</span>Text</button>
          <button type="button" data-action="upload"><span aria-hidden="true">▧</span>Image</button>
          <button type="button" data-action="assets"><span aria-hidden="true">⌕</span>Assets</button>
          <button type="button" data-action="add-shape"><span aria-hidden="true">◇</span>Shapes</button>
          <button type="button" data-action="duplicate"><span aria-hidden="true">▢</span>Duplicate</button>
          <button type="button" data-action="delete"><span aria-hidden="true">⌫</span>Delete</button>
          <button type="button" data-action="layers" aria-controls="sanro-dm-properties"><span aria-hidden="true">☷</span>Layers</button>
          <button type="button" data-action="templates"><span aria-hidden="true">▤</span>Templates</button>
          <input class="sanro-dm__file" type="file" accept="image/png,image/jpeg,image/webp" data-image-file aria-label="<?php echo esc_attr__('Choose a PNG, JPEG or WebP image', 'sanro-design-maker'); ?>">
        </aside>
        <section class="sanro-dm__stage-wrap" aria-label="<?php echo esc_attr__('Design canvas', 'sanro-design-maker'); ?>">
          <div class="sanro-dm__stage" data-stage><canvas data-design-canvas aria-label="<?php echo esc_attr__('Transparent design canvas', 'sanro-design-maker'); ?>"></canvas></div>
          <p class="sanro-dm__hint">Canvas transparan · pilih objek untuk mengatur properti</p>
        </section>
        <aside class="sanro-dm__properties" id="sanro-dm-properties" data-properties aria-label="<?php echo esc_attr__('Object properties and layers', 'sanro-design-maker'); ?>">
          <div class="sanro-dm__panel-heading sanro-dm__properties-heading"><h2>Properti objek</h2><button type="button" data-action="panel-close" aria-label="Tutup properti">×</button></div>
          <label>Warna <input type="color" data-prop="fill" value="#20242a"></label>
          <label>Garis <input type="color" data-prop="stroke" value="#000000"></label>
          <label>Ketebalan garis <input type="range" min="0" max="24" value="0" data-prop="strokeWidth"></label>
          <label>Opasitas <input type="range" min="10" max="100" value="100" data-prop="opacity"></label>
          <label>Font <select data-font-family><option value="Arial">Arial</option><option value="Anton">Anton</option><?php foreach ((array) get_option('sanro_dm_fonts', array()) as $font) { if (!empty($font['family']) && $font['family'] !== 'Anton') { ?><option value="<?php echo esc_attr($font['family']); ?>"><?php echo esc_html($font['family']); ?></option><?php } } ?></select></label>
          <button type="button" data-action="remove-background">Hapus latar warna rata</button>
          <div class="sanro-dm__property-actions">
            <button type="button" data-action="align-left">Rata kiri</button><button type="button" data-action="align-center">Tengah</button>
            <button type="button" data-action="align-right">Rata kanan</button><button type="button" data-action="shadow">Bayangan</button>
            <button type="button" class="sanro-dm__mobile-object-action" data-action="duplicate">Duplikat</button><button type="button" class="sanro-dm__mobile-object-action" data-action="delete">Hapus</button>
          </div>
          <h2>Layers</h2><ol class="sanro-dm__layers" data-layer-list></ol>
          <button type="button" class="sanro-dm__save" data-action="save">Simpan desain</button>
          <button type="button" data-action="my-designs">Desain saya</button>
          <div class="sanro-dm__my-designs" data-my-designs hidden></div>
          <p class="sanro-dm__privacy">Draft tersimpan di perangkat ini. Akun diperlukan untuk menyimpan permanen.</p>
        </aside>
      </div>
      <a class="sanro-dm__print" href="<?php echo esc_url(home_url('/custom/')); ?>">CETAK DESAIN INI</a>
      <nav class="sanro-dm__mobile-tools" aria-label="Design tools">
        <button type="button" data-action="add-text" aria-label="Add text">Text</button><button type="button" data-action="upload" aria-label="Upload image">Image</button>
        <button type="button" data-action="add-shape" aria-label="Shapes">Shapes</button><button type="button" data-action="templates">Templates</button><button type="button" data-action="assets">Assets</button><button type="button" data-action="layers" aria-controls="sanro-dm-properties" aria-expanded="false">Layers</button>
      </nav>
      <div class="sanro-dm__notice" data-notice role="status" aria-live="polite"></div>
    </main>
    <?php
    return ob_get_clean();
}
