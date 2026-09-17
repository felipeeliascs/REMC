<?php
/**
 * Home - bloco educativo "Cientista do dia".
 *
 * A pergunta pode ser trocada por filtro: remc_scientist_question.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$remc_pergunta = apply_filters(
	'remc_scientist_question',
	__( 'A temperatura de Cachoeira Paulista mudou desde ontem?', 'remc-educacional' )
);

$remc_feed_url = '';
if ( function_exists( 'bp_get_activity_directory_url' ) ) {
	$remc_feed_url = bp_get_activity_directory_url();
}
if ( ! $remc_feed_url ) {
	$remc_feed_url = home_url( '/activity/' );
}
?>
<section class="home-scientist" aria-labelledby="home-scientist-title">
	<div class="card home-scientist__card">
		<p class="home-scientist__icon" aria-hidden="true">🔎</p>
		<h2 id="home-scientist-title"><?php esc_html_e( 'Cientista do dia', 'remc-educacional' ); ?></h2>
		<p class="home-scientist__question"><?php echo esc_html( $remc_pergunta ); ?></p>
		<p class="home-scientist__text">
			<?php esc_html_e( 'Observe os dados, compare com as suas próprias observações e compartilhe suas descobertas no Feed.', 'remc-educacional' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $remc_feed_url ); ?>">
				<?php esc_html_e( 'Ver o Feed', 'remc-educacional' ); ?>
			</a>
		</p>
	</div>
</section>
