<?php
/**
 * Home - "Como funciona".
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$remc_passos = array(
	array(
		'icone' => '🔎',
		'titulo' => __( 'Observe', 'remc-educacional' ),
		'texto'  => __( 'Repare nas condições do tempo e do ambiente ao seu redor, na escola ou em casa.', 'remc-educacional' ),
	),
	array(
		'icone' => '📝',
		'titulo' => __( 'Registre', 'remc-educacional' ),
		'texto'  => __( 'Anote seus dados meteorológicos: chuva, temperatura, vento e nuvens.', 'remc-educacional' ),
	),
	array(
		'icone' => '🌎',
		'titulo' => __( 'Compartilhe', 'remc-educacional' ),
		'texto'  => __( 'Depois da aprovação do professor, publique suas descobertas no Feed da rede.', 'remc-educacional' ),
	),
	array(
		'icone' => '📊',
		'titulo' => __( 'Investigue', 'remc-educacional' ),
		'texto'  => __( 'Compare informações, descubra padrões e converse com outros estudantes.', 'remc-educacional' ),
	),
);
?>
<section class="home-how" aria-labelledby="home-how-title">
	<h2 id="home-how-title"><?php esc_html_e( 'Como funciona', 'remc-educacional' ); ?></h2>
	<ol class="home-how__grid">
		<?php foreach ( $remc_passos as $remc_passo ) : ?>
			<li class="card home-how__item">
				<span class="home-how__icon" aria-hidden="true"><?php echo esc_html( $remc_passo['icone'] ); ?></span>
				<h3 class="home-how__title"><?php echo esc_html( $remc_passo['titulo'] ); ?></h3>
				<p class="home-how__text"><?php echo esc_html( $remc_passo['texto'] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
