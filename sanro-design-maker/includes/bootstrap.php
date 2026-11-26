<?php
defined('ABSPATH') || exit;
function sanro_dm_register_editor() {
    add_shortcode('sanro_design_maker', 'sanro_dm_render_editor');
}

function sanro_dm_register_assets() {
    if (!wp_style_is('sanro-dm-editor', 'registered')) {
        wp_register_style('sanro-dm-editor', SANRO_DM_URL . 'assets/editor.css', array(), SANRO_DM_VERSION);
        wp_register_style('sanro-dm-templates', SANRO_DM_URL . 'assets/templates.css', array('sanro-dm-editor'), SANRO_DM_VERSION);
        wp_register_style('sanro-dm-shapes', SANRO_DM_URL . 'assets/shapes.css', array('sanro-dm-templates'), SANRO_DM_VERSION);
        wp_register_script('sanro-dm-fabric', SANRO_DM_URL . 'assets/vendor/fabric/index.min.js', array(), '7.4.0', true);
        wp_register_script('sanro-dm-editor', SANRO_DM_URL . 'assets/editor.js', array('sanro-dm-fabric'), SANRO_DM_VERSION, true);
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
    wp_enqueue_script('sanro-dm-editor');
}

function sanro_dm_render_editor() {
    sanro_dm_register_assets();
    wp_enqueue_style('sanro-dm-editor');
    wp_enqueue_style('sanro-dm-templates');
    wp_enqueue_style('sanro-dm-shapes');
    wp_enqueue_script('sanro-dm-editor');
    ob_start();
    ?>
    <main class="sanro-dm" data-sanro-dm aria-label="<?php echo esc_attr__('SANRO Design Maker', 'sanro-design-maker'); ?>">
      <header class="sanro-dm__header">
        <a class="sanro-dm__brand" href="<?php echo esc_url(home_url('/design-maker/')); ?>">SANRO <span>DESIGN MAKER</span></a>
        <div class="sanro-dm__header-actions">
          <button type="button" data-action="undo" aria-label="<?php echo esc_attr__('Undo', 'sanro-design-maker'); ?>" title="Undo">↶</button>
          <button type="button" data-action="redo" aria-label="<?php echo esc_attr__('Redo', 'sanro-design-maker'); ?>" title="Redo">↷</button>
          <span class="sanro-dm__saved" data-save-status role="status" aria-live="polite">Draft lokal</span>
          <a class="sanro-dm__print" href="<?php echo esc_url(home_url('/custom/')); ?>">CETAK DESAIN INI</a>
        </div>
      </header>
      <section class="sanro-dm__template-panel" data-template-panel hidden aria-label="<?php echo esc_attr__('Editable starter templates', 'sanro-design-maker'); ?>">
        <div class="sanro-dm__template-heading"><strong>Template editable</strong><button type="button" data-action="templates-close" aria-label="<?php echo esc_attr__('Close templates', 'sanro-design-maker'); ?>">×</button></div>
        <div class="sanro-dm__template-list" data-template-list></div>
      </section>
      <section class="sanro-dm__shape-panel" data-shape-panel hidden aria-label="<?php echo esc_attr__('Choose a shape', 'sanro-design-maker'); ?>">
        <strong>Bentuk</strong>
        <button type="button" data-shape-type="rect">Persegi</button><button type="button" data-shape-type="circle">Lingkaran</button>
        <button type="button" data-shape-type="triangle">Segitiga</button><button type="button" data-shape-type="line">Garis</button>
      </section>
      <div class="sanro-dm__workspace">
        <aside class="sanro-dm__rail" aria-label="<?php echo esc_attr__('Design tools', 'sanro-design-maker'); ?>">
          <button type="button" data-action="add-text"><span aria-hidden="true">T</span>Text</button>
          <button type="button" data-action="upload"><span aria-hidden="true">▧</span>Image</button>
          <button type="button" data-action="add-shape"><span aria-hidden="true">◇</span>Shapes</button>
          <button type="button" data-action="duplicate"><span aria-hidden="true">▢</span>Duplicate</button>
          <button type="button" data-action="delete"><span aria-hidden="true">⌫</span>Delete</button>
          <button type="button" data-action="layers"><span aria-hidden="true">☷</span>Layers</button>
          <button type="button" data-action="templates"><span aria-hidden="true">▤</span>Templates</button>
          <input class="sanro-dm__file" type="file" accept="image/png,image/jpeg,image/webp" data-image-file aria-label="<?php echo esc_attr__('Choose a PNG, JPEG or WebP image', 'sanro-design-maker'); ?>">
        </aside>
        <section class="sanro-dm__stage-wrap" aria-label="<?php echo esc_attr__('Design canvas', 'sanro-design-maker'); ?>">
          <div class="sanro-dm__stage" data-stage><canvas data-design-canvas aria-label="<?php echo esc_attr__('Transparent design canvas', 'sanro-design-maker'); ?>"></canvas></div>
          <p class="sanro-dm__hint">Canvas transparan · pilih objek untuk mengatur properti</p>
        </section>
        <aside class="sanro-dm__properties" data-properties aria-label="<?php echo esc_attr__('Object properties and layers', 'sanro-design-maker'); ?>">
          <h2>Properti objek</h2>
          <label>Warna <input type="color" data-prop="fill" value="#20242a"></label>
          <label>Garis <input type="color" data-prop="stroke" value="#000000"></label>
          <label>Ketebalan garis <input type="range" min="0" max="24" value="0" data-prop="strokeWidth"></label>
          <label>Opasitas <input type="range" min="10" max="100" value="100" data-prop="opacity"></label>
          <div class="sanro-dm__property-actions">
            <button type="button" data-action="align-left">Rata kiri</button><button type="button" data-action="align-center">Tengah</button>
            <button type="button" data-action="align-right">Rata kanan</button><button type="button" data-action="shadow">Bayangan</button>
          </div>
          <h2>Layers</h2><ol class="sanro-dm__layers" data-layer-list></ol>
          <button type="button" class="sanro-dm__save" data-action="save">Simpan desain</button>
          <p class="sanro-dm__privacy">Draft tersimpan di perangkat ini. Akun diperlukan untuk menyimpan permanen.</p>
        </aside>
      </div>
      <footer class="sanro-dm__mobile-tools" aria-label="Design tools">
        <button type="button" data-action="add-text">Text</button><button type="button" data-action="upload">Image</button>
        <button type="button" data-action="add-shape">Shapes</button><button type="button" data-action="templates">Templates</button><button type="button" data-action="layers">Layers</button>
      </footer>
      <a class="sanro-dm__mobile-print" href="<?php echo esc_url(home_url('/custom/')); ?>">CETAK DESAIN INI</a>
      <div class="sanro-dm__notice" data-notice role="status" aria-live="polite"></div>
    </main>
    <?php
    return ob_get_clean();
}
function sanro_dm_admin_menu() {
    add_menu_page('SANRO Design Maker', 'SANRO Design Maker', 'manage_options', 'sanro-design-maker', 'sanro_dm_admin_page', 'dashicons-art');
}
function sanro_dm_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>SANRO Design Maker</h1><p>' . esc_html__('Core editor preview. Asset providers, accounts, storage and Studio bridge are not connected yet.', 'sanro-design-maker') . '</p></div>';
}
