<?php
/**
 * Rodapé do tema REMC Educacional
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</div><!-- #conteudo -->

<footer class="footer" role="contentinfo">
	<div class="container">
		<p class="footer-note">
			<?php
			printf(
				/* translators: %s: nome do site */
				esc_html__( '%s - Rede Educacional de Monitoramento Climático', 'remc-educacional' ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
		<p class="footer-note">
			<?php esc_html_e( 'Material da reconstrução. Dados de demonstração são fictícios.', 'remc-educacional' ); ?>
		</p>
		<?php
		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'menu_class'     => 'nav-menu',
				'container'      => false,
				'depth'          => 1,
			) );
		}
		?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
