<?php
/**
 * Template Name: Painel do Professor
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="principal" class="main-content" role="main">
	<h1><?php esc_html_e( 'Painel do Professor', 'remc-educacional' ); ?></h1>

	<?php if ( ! is_user_logged_in() ) : ?>
		<section class="card">
			<h2><?php esc_html_e( 'Acesse sua conta', 'remc-educacional' ); ?></h2>
			<?php wp_login_form(); ?>
		</section>
		<?php
		get_footer();
		return;
	endif;

	$remc_user   = wp_get_current_user();
	$remc_turmas = get_user_meta( $remc_user->ID, '_linked_turmas', true );
	$remc_turmas = is_array( $remc_turmas ) ? $remc_turmas : array();
	$remc_pend   = wp_count_posts( 'remc_observacao' );
	?>

	<section class="dashboard-actions">
		<h2><?php esc_html_e( 'Ações rápidas', 'remc-educacional' ); ?></h2>
		<p class="action-buttons">
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=remc_observacao&post_status=pending' ) ); ?>">
				<?php
				printf(
					/* translators: %d: quantidade de observações pendentes */
					esc_html__( 'Fila de revisão (%d)', 'remc-educacional' ),
					(int) $remc_pend->pending
				);
				?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=remc_escola' ) ); ?>">
				<?php esc_html_e( 'Escolas', 'remc-educacional' ); ?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=remc_local' ) ); ?>">
				<?php esc_html_e( 'Locais', 'remc-educacional' ); ?>
			</a>
		</p>
	</section>

	<section class="review-queue">
		<h2><?php esc_html_e( 'Fila de revisão', 'remc-educacional' ); ?></h2>
		<?php
		$remc_pendentes = new WP_Query( array(
			'post_type'      => 'remc_observacao',
			'post_status'    => 'pending',
			'posts_per_page' => 5,
		) );

		if ( $remc_pendentes->have_posts() ) :
			?>
			<div class="table-responsive">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Data', 'remc-educacional' ); ?></th>
							<th><?php esc_html_e( 'Autor', 'remc-educacional' ); ?></th>
							<th><?php esc_html_e( 'Ações', 'remc-educacional' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ( $remc_pendentes->have_posts() ) : $remc_pendentes->the_post(); ?>
							<tr>
								<td><?php echo esc_html( get_the_date() ); ?></td>
								<td><?php the_author(); ?></td>
								<td>
									<a class="button" href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . get_the_ID() ) ); ?>">
										<?php esc_html_e( 'Revisar', 'remc-educacional' ); ?>
									</a>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
			<?php
		else :
			?>
			<p><?php esc_html_e( 'Nenhuma observação aguardando revisão.', 'remc-educacional' ); ?></p>
			<?php
		endif;
		wp_reset_postdata();
		?>
	</section>
</main>

<?php
get_sidebar();
get_footer();
