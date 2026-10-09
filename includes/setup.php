<?php
/** Explicit, repeatable admin setup. Existing pages and edits are always skipped. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function fellini_menu_items() {
    return array(
        array( 'Home', '/', array() ),
        array( 'About', '/about/', array( 'FAQ' => '/faq/' ) ),
        array( 'Gallery', '/gallery/', array( 'Personal Artifacts' => '/personal-artifacts/', 'Film Artefacts' => '/film-artifacts/', 'Media Collection' => '/media-collection/', 'Film Reels' => '/film-reels/', 'Documentary' => '/documentary/' ) ),
        array( '3D Museum', '/3d-museum/', array() ),
        array( 'News', '/blog/', array() ),
        array( 'Contact', '/contact/', array() ),
    );
}
function fellini_default_menu() {
    echo '<ul class="nav-list">';
    foreach ( fellini_menu_items() as $item ) {
        echo '<li' . ( $item[2] ? ' class="menu-item-has-children"' : '' ) . '><a href="' . esc_url( home_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . '</a>';
        if ( $item[2] ) {
            echo '<button class="submenu-toggle" aria-expanded="false" aria-label="Open ' . esc_attr( $item[0] ) . ' submenu">⌄</button><ul class="sub-menu">';
            foreach ( $item[2] as $label => $path ) { echo '<li><a href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $label ) . '</a></li>'; }
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
function fellini_setup_content() {
    $old_lock = (int) get_option( 'fellini_setup_lock', 0 );
    if ( $old_lock && time() - $old_lock > 5 * MINUTE_IN_SECONDS ) { delete_option( 'fellini_setup_lock' ); }
    if ( ! add_option( 'fellini_setup_lock', time(), '', false ) ) { return new WP_Error( 'busy', 'Setup is already running. Reload after it finishes.' ); }
    $report = array( 'created' => array(), 'skipped' => array(), 'failed' => array() );
    try {
        foreach ( fellini_data( 'pages' ) as $record ) {
            $slug = basename( $record['key'] );
            $existing = get_posts( array( 'post_type' => $record['type'], 'post_status' => 'any', 'meta_key' => '_fellini_source_key', 'meta_value' => $record['key'], 'numberposts' => 1 ) );
            $collision = get_page_by_path( $slug, OBJECT, $record['type'] );
            if ( $existing || $collision ) { $report['skipped'][] = $record['key']; continue; }
            $post = array( 'post_type' => $record['type'], 'post_status' => $record['status'], 'post_name' => $slug, 'post_title' => $record['title'], 'post_content' => fellini_resolve_tokens( $record['content'] ), 'meta_input' => array( '_fellini_source_key' => $record['key'], '_fellini_original_path' => $record['path'] ) );
            if ( isset( $record['date'] ) ) { $post['post_date'] = $record['date']; }
            $id = wp_insert_post( wp_slash( $post ), true );
            if ( is_wp_error( $id ) ) { $report['failed'][ $record['key'] ] = $id->get_error_message(); continue; }
            $seo = fellini_data( 'seo' )[ $record['key'] ] ?? array();
            update_post_meta( $id, '_fellini_seo', $seo );
            $report['created'][] = $record['key'];
        }
        $home = get_page_by_path( 'home' );
        if ( ! get_option( 'fellini_setup_done' ) && $home && 'home' === get_post_meta( $home->ID, '_fellini_source_key', true ) ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $home->ID ); }
        if ( ! get_option( 'permalink_structure' ) ) { update_option( 'permalink_structure', '/%postname%/' ); }
        flush_rewrite_rules();
        if ( ! has_nav_menu( 'primary' ) ) {
            $menu = wp_get_nav_menu_object( 'Fellini Forever' );
            $menu_id = $menu ? $menu->term_id : wp_create_nav_menu( 'Fellini Forever' );
            if ( ! is_wp_error( $menu_id ) ) {
                if ( ! wp_get_nav_menu_items( $menu_id ) ) {
                    foreach ( fellini_menu_items() as $item ) {
                        $parent = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $item[0], 'menu-item-url' => home_url( $item[1] ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) );
                        foreach ( $item[2] as $label => $path ) { wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $label, 'menu-item-url' => home_url( $path ), 'menu-item-parent-id' => (int) $parent, 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) ); }
                    }
                }
                $locations = get_theme_mod( 'nav_menu_locations', array() );
                $locations['primary'] = (int) $menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
            }
        }
        update_option( 'fellini_setup_report', $report, false );
        update_option( 'fellini_setup_done', '0.1.0', false );
    } finally { delete_option( 'fellini_setup_lock' ); }
    return $report;
}
add_action( 'admin_menu', function () { add_theme_page( 'Fellini Forever Setup', 'Fellini Forever Setup', 'manage_options', 'fellini-setup', 'fellini_setup_screen' ); } );
function fellini_setup_screen() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $report = get_option( 'fellini_setup_report', array() );
    echo '<div class="wrap"><h1>Fellini Forever Setup</h1><p>Creates 14 original pages with editable WordPress blocks, six placeholder articles as drafts, and the original menu. Existing records are skipped. Sets the imported homepage only when it belongs to this theme. Then imports the bundled images in small batches.</p><p>Original source pages are noindex. Review Settings → Reading and the launch indexing setting below before launch. Forms are saved privately; configure the recipient under Settings → General when ready.</p>';
    echo '<h2>Dry run / inventory</h2><table class="widefat"><thead><tr><th>Original path</th><th>Title</th><th>Action</th></tr></thead><tbody>';
    foreach ( fellini_data( 'pages' ) as $r ) {
        $exists = get_page_by_path( basename( $r['key'] ), OBJECT, $r['type'] );
        echo '<tr><td>' . esc_html( $r['path'] ) . '</td><td>' . esc_html( $r['title'] ) . '</td><td>' . esc_html( $exists ? 'Skip existing (preserve edits)' : 'Create ' . $r['status'] . ' ' . $r['type'] ) . '</td></tr>';
    }
    echo '</tbody></table><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'fellini_setup' );
    echo '<input type="hidden" name="action" value="fellini_setup"><p><button class="button button-primary">Complete Fellini Forever setup</button></p></form>';
    if ( $report ) { echo '<h2>Last setup report</h2><pre>' . esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT ) ) . '</pre>'; }
    echo '</div>';
}
add_action( 'admin_notices', function () {
    if ( current_user_can( 'manage_options' ) && ! get_option( 'fellini_setup_done' ) ) { echo '<div class="notice notice-info"><p><strong>Fellini Forever:</strong> <a href="' . esc_url( admin_url( 'themes.php?page=fellini-setup' ) ) . '">Review and complete the website setup</a> to create editable pages and import images.</p></div>'; }
} );
add_action( 'admin_post_fellini_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'fellini_setup' );
    $result = fellini_setup_content();
    if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
    wp_safe_redirect( fellini_media_pending() ? fellini_media_import_url() : admin_url( 'themes.php?page=fellini-setup' ) );
    exit;
} );
add_filter( 'post_link', function ( $url, $post ) {
    $path = get_post_meta( $post->ID, '_fellini_original_path', true );
    return $path && str_starts_with( $path, '/post/' ) ? home_url( $path ) : $url;
}, 10, 2 );
