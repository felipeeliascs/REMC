<?php
/**
 * REMC Core - Roles and Capabilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Roles_Capabilities {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_caps' ) );
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) );
		add_filter( 'user_has_cap', array( $this, 'filter_user_caps' ), 10, 4 );
	}

	public function register_caps() {
		// Define custom capabilities
		$caps = array(
			// Schools
			'read_escola', 'edit_escola', 'delete_escola', 'create_escola',
			// Local observation points
			'read_local', 'edit_local', 'delete_local', 'create_local',
			// Observations
			'read_observacao', 'edit_observacao', 'delete_observacao', 'create_observacao',
			// Publish observations
			'publish_observacao',
			// Approve observations
			'approve_observacao',
			// Tutorials
			'read_tutorial', 'edit_tutorial', 'delete_tutorial', 'create_tutorial',
			'publish_tutorial', 'edit_others_tutorials', 'delete_others_tutorials',
			// Activities
			'read_atividade', 'edit_atividade', 'delete_atividade', 'create_atividade',
			// Export data
			'export_data',
			// Manage schools
			'manage_escolas',
			// Manage turmas (groups)
			'manage_turmas',
			// Manage students
			'manage_alunos',
			// Reset passwords
			'reset_student_password',
		);

		$roles = array(
			'administrator' => array(
				'read' => true,
				'create_escolas' => true,
				'edit_escolas' => true,
				'delete_escolas' => true,
				'manage_escolas' => true,
				'manage_turmas' => true,
				'manage_alunos' => true,
				'reset_student_password' => true,
			),
			'professor' => array(
				'read' => true,
				'read_escola' => true,
				'edit_escola' => true,
				'read_local' => true,
				'edit_local' => true,
				'create_local' => true,
				'read_observacao' => true,
				'edit_observacao' => true,
				'create_observacao' => true,
				'publish_observacao' => true,
				'approve_observacao' => true,
				'read_atividade' => true,
				'edit_atividade' => true,
				'create_atividade' => true,
				'export_data' => true,
			),
			'aluno' => array(
				'read' => true,
				'read_observacao' => true,
				'edit_observacao' => true,
				'create_observacao' => true,
				'read_atividade' => true,
				'edit_atividade' => true,
				'create_atividade' => true,
			),
			'visitante' => array(
				'read' => true,
				'read_tutorial' => true,
			),
		);

		// Add capabilities to roles
		foreach ( $roles as $role_name => $role_caps ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $role_caps as $cap => $grant ) {
					if ( $grant ) {
						$role->add_cap( $cap );
					}
				}
			}
		}

		// Register caps for mapping
		global $wp_roles;
		if ( ! isset( $wp_roles->role_objects['administrator'] ) ) {
			return;
		}

		$caps_obj = array();
		foreach ( $caps as $cap ) {
			$caps_obj[ $cap ] = true;
		}
	}

	public function remove_meta_boxes() {
		// Remove author meta box for non-admins on observations
		if ( ! current_user_can( 'administrator' ) && current_user_can( 'edit_observacao' ) ) {
			remove_meta_box( 'authordiv', 'remc_observacao', 'normal' );
		}
	}

	/**
	 * Filter capabilities based on object ownership and school assignment
	 */
	public function filter_user_caps( $allcaps, $caps, $args, $user ) {
		if ( empty( $args ) ) {
			return $allcaps;
		}

		$object_type = '';
		$object_id = 0;

		// Determine object type and ID from args
		if ( isset( $args[0] ) ) {
			$cap = $args[0];
			if ( in_array( $cap, array( 'edit_post', 'read_post', 'delete_post' ) ) && isset( $args[2] ) ) {
				$object_id = $args[2];
				$post = get_post( $object_id );
				if ( $post ) {
					$object_type = $post->post_type;
				}
			}
		}

		// Apply custom logic for REMC post types
		switch ( $object_type ) {
			case 'remc_escola':
				$allcaps = $this->filter_escola_caps( $allcaps, $caps, $args, $user );
				break;
			case 'remc_local':
				$allcaps = $this->filter_local_caps( $allcaps, $caps, $args, $user );
				break;
			case 'remc_observacao':
				$allcaps = $this->filter_observacao_caps( $allcaps, $caps, $args, $user );
				break;
			case 'remc_atividade':
				$allcaps = $this->filter_atividade_caps( $allcaps, $caps, $args, $user );
				break;
		}

		return $allcaps;
	}

	private function filter_escola_caps( $allcaps, $caps, $args, $user ) {
		// Schools are managed by administrators and linked to professors
		if ( ! empty( $args[2] ) ) {
			$escola_id = $args[2];
			$professor_id = get_user_meta( $user->ID, '_linked_escola', true );

			if ( $professor_id && $professor_id === $escola_id ) {
				$allcaps['edit_escola'] = true;
				$allcaps['delete_escola'] = true;
			}
		}
		return $allcaps;
	}

	private function filter_local_caps( $allcaps, $caps, $args, $user ) {
		if ( empty( $args[2] ) ) {
			return $allcaps;
		}

		$local_id = $args[2];
		$turma_id = get_post_meta( $local_id, '_turma', true );
		$escola_id = get_post_meta( $local_id, '_escola', true );

		// Professores can only manage locals in their assigned turmas
		if ( in_array( 'professor', (array) $user->roles ) ) {
			$linked_turmas = get_user_meta( $user->ID, '_linked_turmas', true );
			if ( ! $linked_turmas || ! in_array( $turma_id, (array) $linked_turmas ) ) {
				if ( in_array( 'edit_local', $caps ) || in_array( 'delete_local', $caps ) || in_array( 'create_local', $caps ) ) {
					$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
				}
			}
		}

		// Students can read locals in their turmas
		if ( in_array( 'aluno', (array) $user->roles ) ) {
			$linked_turmas = get_user_meta( $user->ID, '_linked_turmas', true );
			if ( $turma_id && ( ! $linked_turmas || ! in_array( $turma_id, (array) $linked_turmas ) ) ) {
				if ( in_array( 'read_local', $caps ) ) {
					$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
				}
			}
		}

		return $allcaps;
	}

	private function filter_observacao_caps( $allcaps, $caps, $args, $user ) {
		if ( empty( $args[2] ) ) {
			return $allcaps;
		}

		$obs_id = $args[2];
		$obs = get_post( $obs_id );
		if ( ! $obs || $obs->post_type !== 'remc_observacao' ) {
			return $allcaps;
		}

		$author_id = $obs->post_author;
		$turma_id = get_post_meta( $obs_id, '_turma', true );

		// Students can only edit their own observations (drafts)
		if ( in_array( 'aluno', (array) $user->roles ) && $user->ID !== $author_id ) {
			if ( in_array( 'edit_observacao', $caps ) || in_array( 'delete_observacao', $caps ) ) {
				$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
			}
		}

		// Students can only publish their own observations
		if ( in_array( 'aluno', (array) $user->roles ) && $user->ID !== $author_id && in_array( 'publish_observacao', $caps ) ) {
			$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
		}

		// Professores can only approve/edit observations in their turmas
		if ( in_array( 'professor', (array) $user->roles ) ) {
			$linked_turmas = get_user_meta( $user->ID, '_linked_turmas', true );
			if ( $turma_id && ( ! $linked_turmas || ! in_array( $turma_id, (array) $linked_turmas ) ) ) {
				if ( in_array( 'publish_observacao', $caps ) || in_array( 'approve_observacao', $caps ) ) {
					$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
				}
			}
		}

		// Approve capability check
		if ( in_array( 'approve_observacao', $caps ) ) {
			$status = get_post_meta( $obs_id, '_status', true );
			if ( $status !== 'pendente' ) {
				$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
			}
		}

		return $allcaps;
	}

	private function filter_atividade_caps( $allcaps, $caps, $args, $user ) {
		if ( empty( $args[2] ) ) {
			return $allcaps;
		}

		$ativ_id = $args[2];
		$ativ = get_post( $ativ_id );
		if ( ! $ativ || $ativ->post_type !== 'remc_atividade' ) {
			return $allcaps;
		}

		$author_id = $ativ->post_author;
		$turma_id = get_post_meta( $ativ_id, '_turma', true );

		// Students can only edit their own activities
		if ( in_array( 'aluno', (array) $user->roles ) && $user->ID !== $author_id ) {
			if ( in_array( 'edit_atividade', $caps ) || in_array( 'delete_atividade', $caps ) ) {
				$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
			}
		}

		// Professores can only edit activities in their turmas
		if ( in_array( 'professor', (array) $user->roles ) ) {
			$linked_turmas = get_user_meta( $user->ID, '_linked_turmas', true );
			if ( $turma_id && ( ! $linked_turmas || ! in_array( $turma_id, (array) $linked_turmas ) ) ) {
				if ( in_array( 'edit_atividade', $caps ) || in_array( 'delete_atividade', $caps ) ) {
					$allcaps = array_diff_key( $allcaps, array_flip( $caps ) );
				}
			}
		}

		return $allcaps;
	}
}
