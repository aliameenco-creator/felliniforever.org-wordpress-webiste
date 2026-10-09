<?php get_header();
$fellini_home = get_page_by_path( 'home' );
if ( $fellini_home && 'publish' === $fellini_home->post_status && ! is_singular() ) { echo apply_filters( 'the_content', $fellini_home->post_content ); }
else { fellini_page_content(); }
get_footer();
