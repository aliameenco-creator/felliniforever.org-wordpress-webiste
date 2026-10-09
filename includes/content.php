<?php
/** Portable seed records; safe asset resolution; progressively enhanced collection widgets. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function fellini_data( $name ) {
    static $cache = array();
    $allowed = array( 'pages', 'galleries', 'films', 'faq', 'embeds', 'seo', 'media', 'build-report' );
    if ( ! in_array( $name, $allowed, true ) ) { return array(); }
    if ( ! isset( $cache[ $name ] ) ) {
        $file = get_theme_file_path( 'parts/' . $name . '.json' );
        $cache[ $name ] = is_readable( $file ) ? (array) json_decode( file_get_contents( $file ), true ) : array();
    }
    return $cache[ $name ];
}
function fellini_request_path() {
    $path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}
function fellini_record( $key = '' ) {
    $key = $key ? $key : ( fellini_request_path() ?: 'home' );
    foreach ( fellini_data( 'pages' ) as $record ) { if ( $key === $record['key'] ) { return $record; } }
    return array();
}
function fellini_theme_fragment( $html ) {
    $html = fellini_media_swap( $html );
    $html = preg_replace_callback( '~(src|href|poster|data-video)="(/assets/[^"<>]+)"~', function ( $m ) { return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"'; }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) { return 'href="' . esc_url( home_url( $m[1] ) ) . '"'; }, $html );
}
function fellini_page_content() {
    $record = fellini_record();
    if ( have_posts() && is_singular() ) { while ( have_posts() ) { the_post(); the_content(); } }
    elseif ( $record && 'publish' === $record['status'] ) { echo apply_filters( 'the_content', $record['content'] ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Controlled seed, filtered by WordPress.
}
add_action( 'init', function () { add_rewrite_rule( '^post/([^/]+)/?$', 'index.php?post_type=post&name=$matches[1]', 'top' ); } );
add_action( 'template_redirect', function () {
    if ( 'home' === fellini_request_path() ) { wp_safe_redirect( home_url( '/' ), 301 ); exit; }
    $record = fellini_record();
    if ( is_404() && $record && 'publish' === $record['status'] ) {
        $GLOBALS['fellini_virtual'] = true;
        global $wp_query;
        $wp_query->is_404 = false;
        status_header( 200 );
    }
}, 0 );
add_filter( 'template_include', function ( $template ) {
    return ! empty( $GLOBALS['fellini_virtual'] ) ? get_theme_file_path( 'page.php' ) : $template;
} );
add_shortcode( 'fellini_contact', function () { ob_start(); fellini_contact_form(); return ob_get_clean(); } );
add_shortcode( 'fellini_socials', function () {
    $links = array( 'LinkedIn' => 'https://www.linkedin.com/company/fellini-forever-about', 'Instagram' => 'https://www.instagram.com/fellini_forever', 'Facebook' => 'https://www.facebook.com/FelliniForeverTO/' );
    $html = '<div class="socials" aria-label="Follow Fellini Forever">';
    foreach ( $links as $label => $url ) { $html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-' . esc_attr( strtolower( $label ) ) . '" aria-hidden="true"></span> ' . esc_html( $label ) . ' <span aria-hidden="true">↗</span></a>'; }
    return $html . '</div>';
} );
add_shortcode( 'fellini_gallery', function ( $attrs ) {
    $attrs = shortcode_atts( array( 'route' => 'gallery' ), $attrs );
    $items = fellini_data( 'galleries' )[ $attrs['route'] ] ?? array();
    $html = '<section class="section gallery-grid" aria-label="Collection gallery">';
    foreach ( $items as $item ) {
        $html .= '<figure class="gallery-card">';
        if ( 'image' === $item['type'] ) {
            $html .= '<a class="lightbox-link" href="' . esc_attr( $item['src'] ) . '"><img src="' . esc_attr( $item['src'] ) . '" alt="' . esc_attr( $item['title'] ) . '" loading="lazy"></a>';
        } else {
            $html .= '<video controls playsinline preload="none" poster="' . esc_attr( $item['poster'] ) . '" src="' . esc_attr( $item['src'] ) . '" aria-label="' . esc_attr( $item['title'] ) . '"></video>';
        }
        $html .= '<figcaption>' . esc_html( $item['title'] ) . '</figcaption></figure>';
    }
    return fellini_theme_fragment( $html . '</section>' );
} );
add_shortcode( 'fellini_faq', function () {
    $html = '<section class="section faq-list">';
    foreach ( fellini_data( 'faq' ) as $item ) { $html .= '<details><summary>' . esc_html( $item['question'] ) . '</summary><p>' . esc_html( $item['answer'] ) . '</p></details>'; }
    return $html . '</section>';
} );
add_shortcode( 'fellini_tour_preview', function () {
    $photo = fellini_data( 'galleries' )['tour-preview'][0]['src'] ?? '';
    return fellini_theme_fragment( '<a class="tour-preview" href="https://dev.world.treasured.ca/player/felliniexhibit/" target="_blank" rel="noopener"><img src="' . esc_attr( $photo ) . '" alt="Fellini collection — explore the virtual exhibition" loading="lazy"><span class="tour-play">Start Tour ↗</span></a>' );
} );
add_shortcode( 'fellini_3d_objects', function () {
    $html = '<section class="section"><h2>3D Objects</h2><div class="objects-grid">';
    foreach ( fellini_data( 'embeds' ) as $i => $url ) {
        $html .= '<div class="object-viewer"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" data-viewer="' . esc_url( $url ) . '">Explore 3D object ' . (int) ( $i + 1 ) . ' ↗</a></div>';
    }
    return $html . '</div></section>';
} );
add_shortcode( 'fellini_films', function () {
    $html = '<h2>Fellini Forever</h2><div class="films-grid">';
    foreach ( fellini_data( 'films' ) as $film ) {
        if ( $film['src'] ) { $player = '<video controls playsinline preload="none" poster="' . esc_attr( $film['poster'] ) . '" src="' . esc_attr( $film['src'] ) . '"></video>'; }
        elseif ( ! empty( $film['youtube'] ) ) { $player = '<a class="film-play" href="https://www.youtube.com/watch?v=' . esc_attr( $film['youtube'] ) . '" data-viewer="https://www.youtube-nocookie.com/embed/' . esc_attr( $film['youtube'] ) . '?autoplay=1"><img src="' . esc_attr( $film['poster'] ) . '" alt="' . esc_attr( $film['title'] ) . '" loading="lazy"><span>Play film ▶</span></a>'; }
        else { $player = '<a class="film-play" href="' . esc_url( $film['external_url'] ) . '" target="_blank" rel="noopener"><img src="' . esc_attr( $film['poster'] ) . '" alt="' . esc_attr( $film['title'] ) . '" loading="lazy"><span>Watch film ↗</span></a>'; }
        $html .= '<figure>' . $player . '<figcaption>' . esc_html( $film['title'] ) . '</figcaption></figure>';
    }
    return fellini_theme_fragment( $html . '</div>' );
} );
add_shortcode( 'fellini_news', function () {
    $posts = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => max( 1, (int) get_query_var( 'paged' ) ) ) );
    ob_start();
    echo '<section class="section news-grid">';
    while ( $posts->have_posts() ) { $posts->the_post();
        echo '<article><a href="' . esc_url( get_permalink() ) . '">';
        if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); }
        echo '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time><h2>' . esc_html( get_the_title() ) . '</h2></a><p>' . esc_html( wp_trim_words( get_the_excerpt(), 28 ) ) . '</p></article>';
    }
    if ( ! $posts->post_count ) { echo '<div class="news-empty"><p>STAY TUNED!</p></div>'; }
    echo '</section>';
    if ( $posts->max_num_pages > 1 ) { echo '<div class="section">' . wp_kses_post( (string) paginate_links( array( 'total' => $posts->max_num_pages ) ) ) . '</div>'; }
    wp_reset_postdata();
    return ob_get_clean();
} );
