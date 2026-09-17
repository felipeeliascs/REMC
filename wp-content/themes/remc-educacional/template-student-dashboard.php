<?php
/**
 * Template Name: Painel do Aluno
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="principal" class="main-content" role="main">
	<h1><?php esc_html_e( 'Painel do Aluno', 'remc-educacional' ); ?></h1>

	<?php if ( ! is_user_logged_in() ) : ?>
		<section class="card">
			<h2><?php esc_html_e( 'Acesse sua conta', 'remc-educacional' ); ?></h2>
			<?php wp_login_form(); ?>
		</section>
		<?php
		get_footer();
		return;
	endif;

	$remc_user     = wp_get_current_user();
	$remc_turmas   = get_user_meta( $remc_user->ID, '_linked_turmas', true );
	$remc_turmas   = is_array( $remc_turmas ) ? $remc_turmas : array();
	$remc_status   = wp_count_posts( 'remc_observacao' );
	?>

	<section class="dashboard-actions">
		<h2><?php esc_html_e( 'Ações rápidas', 'remc-educacional' ); ?></h2>
		<p class="action-buttons">
			<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=remc_observacao' ) ); ?>">
				<?php esc_html_e( 'Nova observação', 'remc-educacional' ); ?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=remc_observacao' ) ); ?>">
				<?php esc_html_e( 'Meus registros', 'remc-educacional' ); ?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=remc_atividade' ) ); ?>">
				<?php esc_html_e( 'Nova atividade', 'remc-educacional' ); ?>
			</a>
		</p>
	</section>

	<section class="student-feed">
		<?php remc_share_panel(); ?>
		<p>
			<a href="<?php echo esc_url( home_url( '/activity/' ) ); ?>">
				<?php esc_html_e( 'Ver a timeline da comunidade', 'remc-educacional' ); ?>
			</a>
		</p>
	</section>

	<section class="student-turmas">
		<h2><?php esc_html_e( 'Minhas turmas', 'remc-educacional' ); ?></h2>
		<?php if ( empty( $remc_turmas ) ) : ?>
			<p><?php esc_html_e( 'Você ainda não está inscrito em nenhuma turma.', 'remc-educacional' ); ?></p>
		<?php else : ?>
			<ul class="turma-list">
				<?php
				foreach ( $remc_turmas as $remc_turma_id ) {
					if ( ! function_exists( 'groups_get_group' ) ) {
						continue;
					}
					$remc_grupo = groups_get_group( array( 'group_id' => (int) $remc_turma_id ) );
					if ( empty( $remc_grupo->name ) ) {
						continue;
					}
					echo '<li>' . esc_html( $remc_grupo->name ) . '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="student-resumo">
		<h2><?php esc_html_e( 'Resumo', 'remc-educacional' ); ?></h2>
		<ul>
			<li>
				<?php
				printf(
					/* translators: %d: quantidade de observações aprovadas */
					esc_html__( 'Observações aprovadas: %d', 'remc-educacional' ),
					(int) $remc_status->publish
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %d: quantidade de observações aguardando revisão */
					esc_html__( 'Aguardando revisão: %d', 'remc-educacional' ),
					(int) $remc_status->pending
				);
				?>
			</li>
		</ul>
	</section>
</main>

<?php
get_sidebar();
get_footer();
