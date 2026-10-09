<?php
/** Original SEO copy; destination canonical URLs; explicit indexing decision for launch. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function fellini_current_seo() {
    $key = fellini_request_path() ?: 'home';
    $saved = is_singular() ? get_post_meta( get_queried_object_id(), '_fellini_seo', true ) : array();
    return is_array( $saved ) && $saved ? $saved : ( fellini_data( 'seo' )[ $key ] ?? array() );
}
function fellini_seo_plugin_active() { return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ); }
add_filter( 'pre_get_document_title', function ( $title ) { $data = fellini_current_seo(); return ! fellini_seo_plugin_active() && ! empty( $data['title'] ) ? $data['title'] : $title; }, 99 );
add_action( 'wp_head', function () {
    if ( fellini_seo_plugin_active() ) { return; }
    $data = fellini_current_seo();
    if ( ! $data ) { return; }
    $url = home_url( $data['path'] );
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">'; }
    echo '<meta name="description" content="' . esc_attr( $data['description'] ) . '"><meta property="og:title" content="' . esc_attr( $data['title'] ) . '"><meta property="og:description" content="' . esc_attr( $data['description'] ) . '"><meta property="og:url" content="' . esc_url( $url ) . '"><meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">';
}, 3 );
add_filter( 'wp_robots', function ( $robots ) {
    $data = fellini_current_seo();
    if ( $data && ! get_option( 'fellini_launch_indexing' ) && str_contains( $data['robots']['robots'] ?? '', 'noindex' ) ) { $robots['noindex'] = true; unset( $robots['index'] ); }
    return $robots;
} );
add_action( 'admin_init', function () {
    register_setting( 'reading', 'fellini_launch_indexing', array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ) );
    add_settings_field( 'fellini_launch_indexing', 'Fellini source indexing', function () {
        echo '<label><input type="checkbox" name="fellini_launch_indexing" value="1" ' . checked( get_option( 'fellini_launch_indexing' ), true, false ) . '> Release the source noindex setting after the website is ready.</label><p class="description">The archived site was noindex on every page. The WordPress “Discourage search engines” setting still applies separately.</p>';
    }, 'reading' );
} );
// Documented Yoast presentation filters: preserve copy without overwriting its settings/meta.
add_filter( 'wpseo_title', function ( $title ) { $seo = fellini_current_seo(); return $seo['title'] ?? $title; } );
add_filter( 'wpseo_metadesc', function ( $description ) { $seo = fellini_current_seo(); return $seo['description'] ?? $description; } );
