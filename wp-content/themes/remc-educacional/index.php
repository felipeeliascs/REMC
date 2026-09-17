<?php
/**
 * Template principal (fallback)
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="principal" class="main-content" role="main">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		endwhile;

		the_posts_pagination( array(
			'mid_size'  => 2,
			'prev_text' => esc_html__( 'Anterior', 'remc-educacional' ),
			'next_text' => esc_html__( 'Próxima', 'remc-educacional' ),
		) );
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
</main>

<?php
get_sidebar();
get_footer();
