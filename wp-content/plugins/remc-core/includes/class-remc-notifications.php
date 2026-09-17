<?php
/**
 * REMC Core - Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Notifications {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'transition_post_status', array( $this, 'status_transition' ), 10, 3 );
	}

	/**
	 * Handle notifications on post status changes
	 */
	public function status_transition( $new_status, $old_status, $post ) {
		if ( $post->post_type !== 'remc_observacao' ) {
			return;
		}

		// Notify student when observation is returned
		if ( $old_status !== 'devolvido' && $new_status === 'devolvido' ) {
			$this->notify_observation_returned( $post );
		}

		// Notify student when observation is approved
		if ( $new_status === 'aprovado' ) {
			$this->notify_observation_approved( $post );
		}
	}

	private function notify_observation_returned( $post ) {
		$author_id = $post->post_author;
		$revision = wp_get_post_revisions( $post->ID, array( 'limit' => 1, 'order' => 'DESC' ) );
		$last_revision = reset( $revision );

		if ( ! $last_revision ) {
			return;
		}

		$return_comment = get_post_meta( $last_revision->ID, '_return_comment', true );

		if ( ! $return_comment ) {
			return;
		}

		$student_email = get_the_author_meta( 'user_email', $author_id );
		$subject = sprintf( __( 'Sua observação da REMC foi devolvida para ajustes' ), get_bloginfo( 'name' ) );

		$message = sprintf(
			__( 'Olá,\n\nSua observação "%s" foi devolvida para ajustes.\n\nComentário do professor:\n%s\n\nAcesse o painel para corrigir e reenviar.\n\n%s' ),
			$post->post_title,
			$return_comment,
			esc_url( admin_url( 'edit.php?post_type=remc_observacao' ) )
		);

		wp_mail( $student_email, $subject, $message );
	}

	private function notify_observation_approved( $post ) {
		$author_id = $post->post_author;
		$student_email = get_the_author_meta( 'user_email', $author_id );

		if ( ! $student_email ) {
			return;
		}

		$subject = sprintf( __( 'Sua observação da REMC foi aprovada' ), get_bloginfo( 'name' ) );

		$message = sprintf(
			__( 'Olá,\n\nSua observação "%s" foi aprovada e agora faz parte dos dados da turma.\n\nAcesse o painel para ver seus registros.\n\n%s' ),
			$post->post_title,
			esc_url( admin_url( 'edit.php?post_type=remc_observacao' ) )
		);

		wp_mail( $student_email, $subject, $message );
	}

	/**
	 * Send notification to professor when new observation needs review
	 */
	public static function notify_new_observation_for_review( $post_id ) {
		$post = get_post( $post_id );
		if ( $post->post_type !== 'remc_observacao' ) {
			return;
		}

		$turma_id = get_post_meta( $post_id, '_turma', true );
		if ( ! $turma_id ) {
			return;
		}

		// Get professor from group metadata
		$professor_id = groups_get_groupmeta( $turma_id, 'professor_responsavel' );

		if ( ! $professor_id ) {
			return;
		}

		$professor_email = get_the_author_meta( 'user_email', $professor_id );

		if ( ! $professor_email ) {
			return;
		}

		$subject = sprintf( __( 'Nova observação aguardando revisão - REMC' ) );

		$message = sprintf(
			__( 'Olá,\n\nUma nova observação foi enviada para revisão.\n\nAluno: %s\nLocal: %s\nData: %s\n\nAcesse o painel para revisar.\n\n%s' ),
			get_the_author_meta( 'display_name', $post->post_author ),
			self::get_local_name( $post_id ),
			get_post_meta( $post_id, '_observation_date', true ),
			esc_url( admin_url( 'edit.php?post_type=remc_observacao&post_status=pending' ) )
		);

		wp_mail( $professor_email, $subject, $message );
	}

	private static function get_local_name( $obs_id ) {
		$local_id = get_post_meta( $obs_id, '_local_id', true );
		if ( ! $local_id ) {
			return __( 'Local não identificado' );
		}
		$local = get_post( $local_id );
		return $local ? $local->post_title : __( 'Local não encontrado' );
	}
}
