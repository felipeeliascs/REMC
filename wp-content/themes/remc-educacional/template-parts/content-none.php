<?php
/**
 * Conteúdo quando nada é encontrado
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="card no-results">
	<h1 class="entry-title"><?php esc_html_e( 'Nada encontrado', 'remc-educacional' ); ?></h1>
	<p><?php esc_html_e( 'Não há conteúdo disponível aqui. Tente novamente com outro endereço ou volte à página inicial.', 'remc-educacional' ); ?></p>
	<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar à página inicial', 'remc-educacional' ); ?></a></p>
</section>
