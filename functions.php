<?php
/** Fellini Forever: shared infrastructure and archive-backed layouts. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
foreach ( array( 'github-updater', 'media', 'contact-form', 'content', 'setup', 'seo', 'newsletter' ) as $fellini_module ) {
    require_once __DIR__ . '/includes/' . $fellini_module . '.php';
}
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'gallery', 'caption', 'style', 'script', 'search-form' ) );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/fellini.css' );
    register_nav_menus( array( 'primary' => 'Primary navigation' ) );
} );
add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'fellini-theme', get_theme_file_uri( 'assets/fellini.css' ), array(), $version );
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_script( 'fellini-theme', get_theme_file_uri( 'assets/fellini.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );
add_action( 'wp_head', function () {
    if ( ! has_site_icon() ) { echo '<link rel="icon" href="' . esc_url( get_theme_file_uri( 'assets/favicon.ico' ) ) . '">'; }
}, 1 );
add_filter( 'the_content', 'fellini_theme_fragment', 30 );
