<?php
/**
 * Página inicial do REMC.
 *
 * Estrutura em partes (template-parts/home) para manter cada bloco isolado.
 * Preserva a listagem de tutoriais que já existia.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="principal" class="main-content home" role="main">
	<?php
	get_template_part( 'template-parts/home/hero' );
	get_template_part( 'template-parts/home/how' );
	get_template_part( 'template-parts/home/weather' );
	get_template_part( 'template-parts/home/scientist' );
	get_template_part( 'template-parts/home/program' );
	?>
</main>

<?php
get_footer();
