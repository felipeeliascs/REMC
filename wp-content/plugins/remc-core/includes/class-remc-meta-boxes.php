<?php
/**
 * REMC Core - Meta Boxes Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Meta_Boxes {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	public function register_meta_boxes() {
		// Observação
		add_meta_box(
			'remc_observation_details',
			'Detalhes da Observação',
			array( $this, 'render_observation_details' ),
			'remc_observacao',
			'normal',
			'default'
		);

		// Atividade
		add_meta_box(
			'remc_activity_details',
			'Detalhes da Atividade',
			array( $this, 'render_activity_details' ),
			'remc_atividade',
			'normal',
			'default'
		);
	}

	public function render_observation_details( $post ) {
		wp_nonce_field( 'remc_observation_nonce', 'remc_observation_nonce' );
		
		$turma_id = get_post_meta( $post->ID, '_turma', true );
		$local_id = get_post_meta( $post->ID, '_local_id', true );
		$observation_date = get_post_meta( $post->ID, '_observation_date', true );
		$status = get_post_meta( $post->ID, '_status', true );
		
		?>
		<div class="form-group">
			<label for="remc_observation_date">Data e Hora da Observação</label>
			<input type="datetime-local" id="remc_observation_date" name="remc_observation_date" 
				value="<?php echo esc_attr( $observation_date ); ?>" class="form-input" required>
		</div>
		
		<div class="form-group">
			<label for="remc_observation_turma">Turma</label>
			<select id="remc_observation_turma" name="remc_observation_turma" class="form-select" required>
				<option value="">Selecionar turma</option>
				<?php
				$turmas = get_posts( array(
					'post_type' => 'group',
					'post_status' => 'publish',
					'posts_per_page' => -1,
				) );
				foreach ( $turmas as $turma ) :
					?>
					<option value="<?php echo esc_attr( $turma->ID ); ?>" 
						<?php selected( $turma_id, $turma->ID ); ?>>
						<?php echo esc_html( $turma->post_title ); ?>
					</option>
					<?php
				endforeach;
				?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="remc_observation_local">Local de Observação</label>
			<select id="remc_observation_local" name="remc_observation_local" class="form-select" required>
				<option value="">Selecionar local</option>
				<?php
				$locals = get_posts( array(
					'post_type' => 'remc_local',
					'post_status' => 'publish',
					'posts_per_page' => -1,
				) );
				foreach ( $locals as $local ) :
					?>
					<option value="<?php echo esc_attr( $local->ID ); ?>" 
						<?php selected( $local_id, $local->ID ); ?>>
						<?php echo esc_html( $local->post_title ); ?>
					</option>
					<?php
				endforeach;
				?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="remc_observation_status">Status da revisão</label>
			<select id="remc_observation_status" name="remc_observation_status" class="form-select">
				<option value="rascunho" <?php selected( $status, 'rascunho' ); ?>>Rascunho</option>
				<option value="pendente" <?php selected( $status, 'pendente' ); ?>>Enviado para revisão</option>
				<option value="aprovado" <?php selected( $status, 'aprovado' ); ?>>Aprovado</option>
				<option value="devolvido" <?php selected( $status, 'devolvido' ); ?>>Devolvido</option>
			</select>
			<p class="description">O status de publicação do WordPress é ajustado separadamente.</p>
		</div>
		<?php
	}

	public function render_activity_details( $post ) {
		wp_nonce_field( 'remc_activity_nonce', 'remc_activity_nonce' );
		
		$turma_id = get_post_meta( $post->ID, '_turma', true );
		$tutorial_id = get_post_meta( $post->ID, '_tutorial_id', true );
		$hypothesis = get_post_meta( $post->ID, '_hypothesis', true );
		$procedure = get_post_meta( $post->ID, '_procedure', true );
		$results = get_post_meta( $post->ID, '_results', true );
		$reflection = get_post_meta( $post->ID, '_reflection', true );
		
		?>
		<div class="form-group">
			<label for="remc_activity_turma">Turma</label>
			<select id="remc_activity_turma" name="remc_activity_turma" class="form-select" required>
				<option value="">Selecionar turma</option>
				<?php
				$turmas = get_posts( array(
					'post_type' => 'group',
					'post_status' => 'publish',
					'posts_per_page' => -1,
				) );
				foreach ( $turmas as $turma ) :
					?>
					<option value="<?php echo esc_attr( $turma->ID ); ?>" 
						<?php selected( $turma_id, $turma->ID ); ?>>
						<?php echo esc_html( $turma->post_title ); ?>
					</option>
					<?php
				endforeach;
				?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="remc_activity_tutorial">Tutorial Relacionado (opcional)</label>
			<select id="remc_activity_tutorial" name="remc_activity_tutorial" class="form-select">
				<option value="">Nenhum</option>
				<?php
				$tutorials = get_posts( array(
					'post_type' => 'remc_tutorial',
					'post_status' => 'publish',
					'posts_per_page' => -1,
				) );
				foreach ( $tutorials as $tutorial ) :
					?>
					<option value="<?php echo esc_attr( $tutorial->ID ); ?>" 
						<?php selected( $tutorial_id, $tutorial->ID ); ?>>
						<?php echo esc_html( $tutorial->post_title ); ?>
					</option>
					<?php
				endforeach;
				?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="remc_activity_hypothesis">Hipótese/Descrição</label>
			<textarea id="remc_activity_hypothesis" name="remc_activity_hypothesis" 
				class="form-textarea" rows="4"><?php echo esc_textarea( $hypothesis ); ?></textarea>
		</div>
		
		<div class="form-group">
			<label for="remc_activity_procedure">Procedimento</label>
			<textarea id="remc_activity_procedure" name="remc_activity_procedure" 
				class="form-textarea" rows="4"><?php echo esc_textarea( $procedure ); ?></textarea>
		</div>
		
		<div class="form-group">
			<label for="remc_activity_results">Resultados</label>
			<textarea id="remc_activity_results" name="remc_activity_results" 
				class="form-textarea" rows="4"><?php echo esc_textarea( $results ); ?></textarea>
		</div>
		
		<div class="form-group">
			<label for="remc_activity_reflection">Reflexão</label>
			<textarea id="remc_activity_reflection" name="remc_activity_reflection" 
				class="form-textarea" rows="4"><?php echo esc_textarea( $reflection ); ?></textarea>
		</div>
		<?php
	}

	public function save_meta_boxes( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['remc_observation_nonce'] ) && ! isset( $_POST['remc_activity_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( $post->post_type === 'remc_observacao' ) {
			if ( isset( $_POST['remc_observation_nonce'] ) && 
				wp_verify_nonce( $_POST['remc_observation_nonce'], 'remc_observation_nonce' ) ) {
				
				if ( isset( $_POST['remc_observation_date'] ) ) {
					update_post_meta( $post_id, '_observation_date', sanitize_text_field( $_POST['remc_observation_date'] ) );
				}
				
				if ( isset( $_POST['remc_observation_turma'] ) ) {
					update_post_meta( $post_id, '_turma', (int) $_POST['remc_observation_turma'] );
				}
				
				if ( isset( $_POST['remc_observation_local'] ) ) {
					update_post_meta( $post_id, '_local_id', (int) $_POST['remc_observation_local'] );
				}
				
				if ( isset( $_POST['remc_observation_status'] ) ) {
					update_post_meta( $post_id, '_status', sanitize_key( $_POST['remc_observation_status'] ) );
				}
			}
		}

		if ( $post->post_type === 'remc_atividade' ) {
			if ( isset( $_POST['remc_activity_nonce'] ) && 
				wp_verify_nonce( $_POST['remc_activity_nonce'], 'remc_activity_nonce' ) ) {
				
				if ( isset( $_POST['remc_activity_turma'] ) ) {
					update_post_meta( $post_id, '_turma', (int) $_POST['remc_activity_turma'] );
				}
				
				if ( isset( $_POST['remc_activity_tutorial'] ) ) {
					update_post_meta( $post_id, '_tutorial_id', (int) $_POST['remc_activity_tutorial'] );
				}
				
				if ( isset( $_POST['remc_activity_hypothesis'] ) ) {
					update_post_meta( $post_id, '_hypothesis', wp_kses_post( $_POST['remc_activity_hypothesis'] ) );
				}
				
				if ( isset( $_POST['remc_activity_procedure'] ) ) {
					update_post_meta( $post_id, '_procedure', wp_kses_post( $_POST['remc_activity_procedure'] ) );
				}
				
				if ( isset( $_POST['remc_activity_results'] ) ) {
					update_post_meta( $post_id, '_results', wp_kses_post( $_POST['remc_activity_results'] ) );
				}
				
				if ( isset( $_POST['remc_activity_reflection'] ) ) {
					update_post_meta( $post_id, '_reflection', wp_kses_post( $_POST['remc_activity_reflection'] ) );
				}
			}
		}
	}
}
