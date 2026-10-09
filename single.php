<?php get_header(); while ( have_posts() ) { the_post(); ?>
<article class="section reading article"><a class="eyebrow" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">NEWS</a><h1><?php the_title(); ?></h1><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } the_content(); ?><nav class="post-navigation" aria-label="Articles"><?php previous_post_link(); next_post_link(); ?></nav></article>
<?php } get_footer();
