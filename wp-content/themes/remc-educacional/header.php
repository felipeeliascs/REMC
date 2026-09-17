<?php
/**
 * Cabeçalho do tema REMC Educacional
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="screen-reader-text skip-link" href="#conteudo"><?php esc_html_e( 'Pular para o conteúdo', 'remc-educacional' ); ?></a>

<header class="header" role="banner">
	<div class="container">
		<?php if ( is_front_page() && is_home() ) : ?>
			<p class="site-title"><?php bloginfo( 'name' ); ?></p>
		<?php else : ?>
			<p class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</p>
		<?php endif; ?>
		<?php
		$remc_descricao = get_bloginfo( 'description', 'display' );
		if ( $remc_descricao || is_customize_preview() ) :
			?>
			<p class="site-description"><?php echo esc_html( $remc_descricao ); ?></p>
		<?php endif; ?>
	</div>
</header>

<nav class="nav-main" role="navigation" aria-label="<?php esc_attr_e( 'Menu principal', 'remc-educacional' ); ?>">
	<div class="container">
		<?php
		if ( has_nav_menu( 'main' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'main',
				'menu_class'     => 'nav-menu',
				'container'      => false,
				'depth'          => 2,
			) );
		} else {
			wp_page_menu( array( 'show_home' => true ) );
		}
		?>
	</div>
</nav>

<div id="conteudo" class="site-content container">
