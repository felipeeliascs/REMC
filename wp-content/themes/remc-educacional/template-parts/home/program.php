<?php
/**
 * Home - Programa Educação CPTEC/INPE e conteúdo em destaque (tutoriais).
 *
 * Mantém a listagem de tutoriais que já existia na home.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="home-program" id="programa-educacao" aria-labelledby="home-program-title">
	<h2 id="home-program-title"><?php esc_html_e( 'Programa Educação', 'remc-educacional' ); ?></h2>
	<p class="home-program__text">
		<?php esc_html_e( 'O REMC faz parte do Programa Educação do CPTEC/INPE, em Cachoeira Paulista (SP). O programa aproxima a ciência do clima da sala de aula: estudantes observam o tempo com instrumentos simples, registram dados e investigam o que acontece na atmosfera.', 'remc-educacional' ); ?>
	</p>
	<ul class="home-program__list">
		<li><?php esc_html_e( 'Instrumentos caseiros construídos com materiais acessíveis.', 'remc-educacional' ); ?></li>
		<li><?php esc_html_e( 'Rotinas de observação de chuva, vento, temperatura e nuvens.', 'remc-educacional' ); ?></li>
		<li><?php esc_html_e( 'Dados comparados com informações meteorológicas reais da região.', 'remc-educacional' ); ?></li>
	</ul>
	<p class="home-program__link">
		<a class="button button-secondary"
			href="https://programaeducacao.cptec.inpe.br/"
			target="_blank"
			rel="noopener noreferrer">
			<?php esc_html_e( 'Visitar o site do Programa Educação', 'remc-educacional' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( '(abre em nova aba)', 'remc-educacional' ); ?></span>
		</a>
	</p>
	<p class="home-program__note">
		<?php
		printf(
			/* translators: %s: endereço do site do Programa Educação */
			esc_html__( 'Saiba mais em %s', 'remc-educacional' ),
			'<a href="https://programaeducacao.cptec.inpe.br/" target="_blank" rel="noopener noreferrer">programaeducacao.cptec.inpe.br</a>'
		);
		?>
	</p>
</section>

<?php
$remc_tutoriais = new WP_Query( array(
	'post_type'      => 'remc_tutorial',
	'post_status'    => 'publish',
	'posts_per_page' => 3,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

if ( $remc_tutoriais->have_posts() ) :
	?>
	<section class="featured-content" aria-labelledby="home-tutorials-title">
		<h2 id="home-tutorials-title"><?php esc_html_e( 'Biblioteca de tutoriais', 'remc-educacional' ); ?></h2>
		<div class="tutorials-grid">
			<?php
			while ( $remc_tutoriais->have_posts() ) :
				$remc_tutoriais->the_post();
				?>
				<article class="tutorial-card card">
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php the_excerpt(); ?></p>
					<a href="<?php the_permalink(); ?>" class="button button-secondary"><?php esc_html_e( 'Ver detalhes', 'remc-educacional' ); ?></a>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'remc_tutorial' ) ); ?>">
				<?php esc_html_e( 'Ver todos os tutoriais', 'remc-educacional' ); ?>
			</a>
		</p>
	</section>
	<?php
	wp_reset_postdata();
endif;
