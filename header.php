<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#64090c"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<noscript><style>@media(max-width:760px){.menu-toggle{display:none}.header-inner{flex-wrap:wrap}.site-header nav{display:block;position:static;width:100%;max-height:none}.sub-menu{display:block!important}.submenu-toggle{display:none}.site-header{position:relative}}</style></noscript>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header"><div class="header-inner">
<a class="wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Fellini Forever home"><strong>FELLINI</strong><span>FOREVER</span></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="site-navigation"><span>Menu</span><span class="menu-icon" aria-hidden="true">☰</span></button>
<nav id="site-navigation" aria-label="Main navigation">
<?php if ( has_nav_menu( 'primary' ) ) { wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-list' ) ); } else { fellini_default_menu(); } ?>
</nav></div></header>
<main id="main">
