<?php
/**
 * Template Name: Página de Tutorial
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
		the_post();

		$remc_meta = array(
			'objective'         => get_post_meta( get_the_ID(), '_objective', true ),
			'materials'         => get_post_meta( get_the_ID(), '_materials', true ),
			'steps'             => get_post_meta( get_the_ID(), '_steps', true ),
			'reading_mode'      => get_post_meta( get_the_ID(), '_reading_mode', true ),
			'unit'              => get_post_meta( get_the_ID(), '_unit', true ),
			'limitations'       => get_post_meta( get_the_ID(), '_limitations', true ),
			'precautions'       => get_post_meta( get_the_ID(), '_precautions', true ),
			'collection_fields' => get_post_meta( get_the_ID(), '_collection_fields', true ),
			'version'           => get_post_meta( get_the_ID(), '_version', true ),
		);
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'tutorial' ); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( $remc_meta['version'] ) : ?>
					<p class="tutorial-version">
						<?php
						printf(
							/* translators: %s: versão do protocolo */
							esc_html__( 'Versão do protocolo: %s', 'remc-educacional' ),
							esc_html( $remc_meta['version'] )
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<div class="entry-content">
				<?php the_content(); ?>

				<?php
				$remc_secoes = array(
					'objective'         => __( 'Objetivo', 'remc-educacional' ),
					'materials'         => __( 'Materiais', 'remc-educacional' ),
					'steps'             => __( 'Etapas', 'remc-educacional' ),
					'reading_mode'      => __( 'Forma de leitura', 'remc-educacional' ),
					'unit'              => __( 'Unidade', 'remc-educacional' ),
					'collection_fields' => __( 'Campos de coleta', 'remc-educacional' ),
					'precautions'       => __( 'Cuidados', 'remc-educacional' ),
					'limitations'       => __( 'Limitações', 'remc-educacional' ),
				);

				foreach ( $remc_secoes as $remc_chave => $remc_titulo ) {
					if ( empty( $remc_meta[ $remc_chave ] ) ) {
						continue;
					}
					echo '<section class="tutorial-section">';
					echo '<h2>' . esc_html( $remc_titulo ) . '</h2>';
					echo '<p>' . wp_kses_post( nl2br( $remc_meta[ $remc_chave ] ) ) . '</p>';
					echo '</section>';
				}
				?>
			</div>
		</article>

	<?php else : ?>
		<section class="card no-results">
			<h1><?php esc_html_e( 'Tutorial não encontrado', 'remc-educacional' ); ?></h1>
			<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar à página inicial', 'remc-educacional' ); ?></a></p>
		</section>
	<?php endif; ?>
</main>

<?php
get_sidebar();
get_footer();
