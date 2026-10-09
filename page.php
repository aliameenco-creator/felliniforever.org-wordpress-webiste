<?php get_header();
$fellini_record = fellini_record();
$fellini_title = is_singular() ? get_the_title() : ( $fellini_record['title'] ?? 'Fellini Forever' );
?>
<section class="inner-hero"><div class="inner-hero-copy"><a class="eyebrow" href="<?php echo esc_url( home_url( '/' ) ); ?>">FELLINI FOREVER</a><h1><?php echo esc_html( $fellini_title ); ?></h1></div><span aria-hidden="true" class="hero-rule"></span></section>
<?php fellini_page_content(); get_footer();
