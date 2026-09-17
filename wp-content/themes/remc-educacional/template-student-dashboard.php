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

	<?php
	// Retorno do compartilhamento (admin-post.php).
	$remc_feed = isset( $_GET['remc_feed'] ) ? sanitize_key( wp_unslash( $_GET['remc_feed'] ) ) : '';
	if ( $remc_feed ) :
		$remc_msgs = array(
			'shared'   => __( 'Dados compartilhados no feed da comunidade.', 'remc-educacional' ),
			'unshared' => __( 'Dados removidos do feed.', 'remc-educacional' ),
			'error'    => __( 'Não foi possível compartilhar agora. Tente novamente.', 'remc-educacional' ),
		);
		$remc_tipo = ( 'error' === $remc_feed ) ? 'error' : 'success';
		?>
		<div class="form-status <?php echo esc_attr( $remc_tipo ); ?>" role="status">
			<?php echo esc_html( isset( $remc_msgs[ $remc_feed ] ) ? $remc_msgs[ $remc_feed ] : '' ); ?>
		</div>
	<?php endif; ?>

	<section class="student-feed">
		<h2><?php esc_html_e( 'Compartilhar no feed da comunidade', 'remc-educacional' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Somente observações aprovadas podem ser compartilhadas, e apenas por você. A prévia mostra exatamente o que ficará público: sem notas, sem e-mail, sem nome completo e sem endereço residencial.', 'remc-educacional' ); ?>
		</p>
		<?php
		$remc_aprovadas = get_posts( array(
			'post_type'      => 'remc_observacao',
			'post_status'    => 'publish',
			'author'         => $remc_user->ID,
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_status',
					'value' => 'aprovado',
				),
			),
		) );

		if ( empty( $remc_aprovadas ) ) :
			?>
			<p><?php esc_html_e( 'Você ainda não tem observações aprovadas para compartilhar.', 'remc-educacional' ); ?></p>
			<?php
		else :
			foreach ( $remc_aprovadas as $remc_obs ) :
				$remc_shared = (int) get_post_meta( $remc_obs->ID, '_shared_activity_id', true );
				$remc_data   = get_post_meta( $remc_obs->ID, '_observation_date', true );
				$remc_previa = class_exists( 'Remc_Activity' ) ? Remc_Activity::build_public_content( $remc_obs->ID ) : '';
				?>
				<article class="card">
					<h3><?php echo esc_html( get_the_title( $remc_obs ) ); ?></h3>
					<p>
						<?php
						echo esc_html( sprintf(
							/* translators: %s: data da observação */
							__( 'Observado em %s', 'remc-educacional' ),
							$remc_data ? mysql2date( 'd/m/Y H:i', $remc_data ) : get_the_date( '', $remc_obs )
						) );
						?>
					</p>

					<details>
						<summary><?php esc_html_e( 'Ver prévia pública', 'remc-educacional' ); ?></summary>
						<div class="feed-preview">
							<?php
							if ( $remc_previa ) {
								echo wp_kses_post( $remc_previa );
							} else {
								echo '<p>' . esc_html__( 'Este registro não tem variáveis reconhecidas (por exemplo, os dados foram salvos com um nome de campo diferente). Edite a observação e use os campos do formulário para poder compartilhar.', 'remc-educacional' ) . '</p>';
							}
							?>
						</div>
					</details>

					<?php if ( ! $remc_previa ) : ?>
						<p><span class="badge badge-rejected"><?php esc_html_e( 'Sem dados reconhecidos', 'remc-educacional' ); ?></span></p>
						<p>
							<?php if ( current_user_can( 'edit_post', $remc_obs->ID ) ) : ?>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $remc_obs->ID ) ); ?>">
									<?php esc_html_e( 'Editar observação', 'remc-educacional' ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( 'Peça ao professor para devolver a observação e corrija os campos antes de compartilhar.', 'remc-educacional' ); ?>
							<?php endif; ?>
						</p>
					<?php elseif ( $remc_shared ) : ?>
						<p><span class="badge badge-approved"><?php esc_html_e( 'Compartilhado no feed', 'remc-educacional' ); ?></span></p>
						<p>
							<a class="button button-secondary"
								href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=remc_unshare_observation&observation=' . $remc_obs->ID ), 'remc_unshare_observation_' . $remc_obs->ID ) ); ?>">
								<?php esc_html_e( 'Remover do feed', 'remc-educacional' ); ?>
							</a>
						</p>
					<?php else : ?>
						<p>
							<a class="button button-primary"
								href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=remc_share_observation&observation=' . $remc_obs->ID ), 'remc_share_observation_' . $remc_obs->ID ) ); ?>">
								<?php esc_html_e( 'Compartilhar no feed', 'remc-educacional' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>

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
