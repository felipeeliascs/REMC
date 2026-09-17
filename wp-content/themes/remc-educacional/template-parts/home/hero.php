<?php
/**
 * Home - destaque (hero).
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$remc_feed_url = '';
if ( function_exists( 'bp_get_activity_directory_url' ) ) {
	$remc_feed_url = bp_get_activity_directory_url();
} elseif ( function_exists( 'bp_get_activity_directory_permalink' ) ) {
	$remc_feed_url = bp_get_activity_directory_permalink();
}
if ( ! $remc_feed_url ) {
	$remc_feed_url = home_url( '/activity/' );
}

$remc_nova_url = admin_url( 'post-new.php?post_type=remc_observacao' );
$remc_login    = is_user_logged_in() ? $remc_nova_url : wp_login_url( $remc_nova_url );
?>
<section class="home-hero">
	<p class="home-hero__eyebrow"><?php esc_html_e( 'Programa Educação CPTEC/INPE', 'remc-educacional' ); ?></p>
	<h1 class="home-hero__title"><?php esc_html_e( 'Rede Educacional de Monitoramento Climático', 'remc-educacional' ); ?></h1>
	<p class="home-hero__tagline"><?php esc_html_e( 'Observe. Registre. Compartilhe. Aprenda.', 'remc-educacional' ); ?></p>
	<p class="home-hero__text">
		<?php esc_html_e( 'O REMC conecta estudantes, escolas e tecnologia para transformar observações meteorológicas em experiências de aprendizagem.', 'remc-educacional' ); ?>
	</p>
	<p class="home-hero__text">
		<?php esc_html_e( 'Aqui você pode registrar observações do tempo, acompanhar informações meteorológicas e compartilhar suas descobertas com outros participantes da rede.', 'remc-educacional' ); ?>
	</p>
	<p class="home-hero__actions">
		<a class="button button-primary" href="<?php echo esc_url( $remc_feed_url ); ?>">
			<?php esc_html_e( 'Ver o Feed', 'remc-educacional' ); ?>
		</a>
		<a class="button button-secondary" href="<?php echo esc_url( $remc_login ); ?>">
			<?php esc_html_e( 'Registrar observação', 'remc-educacional' ); ?>
		</a>
	</p>
</section>
