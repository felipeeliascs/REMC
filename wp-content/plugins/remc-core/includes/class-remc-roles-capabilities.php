<?php
/**
 * REMC Core - Papeis, capabilities e autorizacao por objeto.
 *
 * A autorizacao por objeto e feita no filtro "map_meta_cap": quando o vinculo
 * com a escola/turma nao confere, devolvemos "do_not_allow", que e a forma
 * canonica de negar acesso a um objeto especifico no WordPress.
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
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		add_action( 'save_post_remc_observacao', array( $this, 'validate_observation_scope' ), 10, 2 );
		add_action( 'save_post_remc_atividade', array( $this, 'validate_activity_scope' ), 10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Papeis e capabilities                                               */
	/* ------------------------------------------------------------------ */

	public function register_caps() {
		$caps = array(
			'read_escola', 'edit_escola', 'delete_escola', 'create_escola',
			'read_local', 'edit_local', 'delete_local', 'create_local',
			'read_observacao', 'edit_observacao', 'delete_observacao', 'create_observacao',
			'publish_observacao', 'approve_observacao',
			'read_tutorial', 'edit_tutorial', 'delete_tutorial', 'create_tutorial',
			'publish_tutorial', 'edit_others_tutorials', 'delete_others_tutorials',
			'read_atividade', 'edit_atividade', 'delete_atividade', 'create_atividade',
			'export_data', 'manage_escolas', 'manage_turmas', 'manage_alunos',
			'reset_student_password',
		);

		$plurais = array(
			'edit_locais', 'delete_locais', 'edit_published_locais', 'delete_published_locais',
			'edit_escolas', 'delete_escolas', 'edit_published_escolas', 'delete_published_escolas',
			'edit_observacoes', 'delete_observacoes', 'edit_others_observacoes',
			'delete_others_observacoes', 'read_private_observacoes',
			'edit_published_observacoes', 'delete_published_observacoes',
			'edit_tutoriais', 'delete_tutoriais', 'edit_others_tutoriais',
			'publish_tutoriais', 'read_private_tutoriais',
			'edit_published_tutoriais', 'delete_published_tutoriais',
			'edit_atividades', 'delete_atividades', 'edit_others_atividades',
			'read_private_atividades',
			'edit_published_atividades', 'delete_published_atividades',
		);

		$cap_concedidas = array(
			'administrator' => array_merge( $caps, $plurais, array( 'read' ) ),
			'professor'     => array(
				'read',
				'read_escola', 'edit_escola', 'edit_escolas',
				'read_local', 'create_local', 'edit_local', 'edit_locais',
				'read_observacao', 'create_observacao', 'edit_observacao', 'delete_observacao',
				'edit_observacoes', 'edit_others_observacoes', 'edit_published_observacoes',
				'read_private_observacoes', 'publish_observacao', 'approve_observacao',
				'read_atividade', 'create_atividade', 'edit_atividade',
				'read_tutorial', 'create_tutorial', 'edit_tutorial', 'edit_tutoriais', 'publish_tutorial',
				'export_data',
			),
			'aluno'         => array(
				'read',
				'read_escola', 'read_local',
				'read_observacao', 'create_observacao', 'edit_observacao', 'delete_observacao',
				'edit_observacoes', 'edit_published_observacoes',
				'read_atividade', 'create_atividade', 'edit_atividade', 'delete_atividade',
				'edit_atividades',
				'read_tutorial',
			),
			'visitante'     => array( 'read', 'read_tutorial' ),
		);

		$rotulos = array(
			'professor' => __( 'Professor REMC', 'remc-core' ),
			'aluno'     => __( 'Aluno REMC', 'remc-core' ),
			'visitante' => __( 'Visitante REMC', 'remc-core' ),
		);

		foreach ( $cap_concedidas as $role_name => $role_caps ) {
			$role = get_role( $role_name );

			if ( ! $role && isset( $rotulos[ $role_name ] ) ) {
				add_role( $role_name, $rotulos[ $role_name ], array( 'read' => true ) );
				$role = get_role( $role_name );
			}

			if ( ! $role ) {
				continue;
			}

			foreach ( array_unique( $role_caps ) as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Vinculos com escola e turma                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Turmas pelas quais o usuario e responsavel (apenas professor/admin).
	 *
	 * Alunos tambem possuem `_linked_turmas`, mas o vinculo de aluno nao da
	 * poderes de gestao. Por isso o papel e verificado antes.
	 *
	 * @return int[]
	 */
	public function managed_turmas( $user_id ) {
		if ( ! $user_id ) {
			return array();
		}

		$user  = get_userdata( $user_id );
		$roles = $user ? (array) $user->roles : array();
		if ( ! in_array( 'professor', $roles, true ) && ! in_array( 'administrator', $roles, true ) ) {
			return array();
		}

		$turmas = (array) get_user_meta( $user_id, '_linked_turmas', true );
		$turmas = array_filter( array_map( 'intval', $turmas ) );

		if ( function_exists( 'groups_get_groups' ) ) {
			$grupos = groups_get_groups( array(
				'user_id'    => $user_id,
				'show_hidden' => true,
				'per_page'   => false,
				'meta_query' => array(
					array(
						'key'   => 'professor_responsavel',
						'value' => (int) $user_id,
					),
				),
			) );
			if ( ! empty( $grupos['groups'] ) ) {
				foreach ( $grupos['groups'] as $g ) {
					$turmas[] = (int) $g->id;
				}
			}
		}

		return array_values( array_unique( $turmas ) );
	}

	public function user_manages_turma( $user_id, $turma_id ) {
		if ( ! $user_id || ! $turma_id ) {
			return false;
		}
		return in_array( (int) $turma_id, $this->managed_turmas( $user_id ), true );
	}

	public function user_in_turma( $user_id, $turma_id ) {
		if ( ! $user_id || ! $turma_id || ! function_exists( 'groups_is_user_member' ) ) {
			return false;
		}
		if ( groups_is_user_member( $user_id, (int) $turma_id ) ) {
			return true;
		}
		$turmas = (array) get_user_meta( $user_id, '_linked_turmas', true );
		return in_array( (int) $turma_id, array_map( 'intval', $turmas ), true );
	}

	public function is_admin( $user_id ) {
		return user_can( $user_id, 'manage_options' );
	}

	/* ------------------------------------------------------------------ */
	/* Autorizacao por objeto                                              */
	/* ------------------------------------------------------------------ */

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'edit_post', 'read_post', 'delete_post' ), true ) ) {
			return $caps;
		}
		if ( empty( $args[0] ) ) {
			return $caps;
		}

		$post = get_post( $args[0] );
		if ( ! $post ) {
			return $caps;
		}

		switch ( $post->post_type ) {
			case 'remc_observacao':
				return $this->can_access_observation( $caps, $cap, $user_id, $post );
			case 'remc_atividade':
				return $this->can_access_atividade( $caps, $cap, $user_id, $post );
			case 'remc_local':
				return $this->can_access_local( $caps, $cap, $user_id, $post );
			case 'remc_escola':
				return $this->can_access_escola( $caps, $cap, $user_id, $post );
		}

		return $caps;
	}

	private function can_access_observation( $caps, $cap, $user_id, $post ) {
		if ( $this->is_admin( $user_id ) ) {
			return $caps;
		}

		$author = (int) $post->post_author;
		$turma  = (int) get_post_meta( $post->ID, '_turma', true );
		$status = (string) get_post_meta( $post->ID, '_status', true );
		$aberto = in_array( $status, array( '', 'rascunho', 'devolvido' ), true );

		// Autoria: le sempre; edita/apaga apenas enquanto nao aprovada.
		if ( $user_id === $author ) {
			if ( 'read_post' === $cap ) {
				return $caps;
			}
			return $aberto ? $caps : array( 'do_not_allow' );
		}

		// Professor responsavel pela turma: le e revisa.
		if ( $this->user_manages_turma( $user_id, $turma ) ) {
			if ( in_array( $cap, array( 'read_post', 'edit_post' ), true ) ) {
				return $caps;
			}
			return array( 'do_not_allow' ); // apagar observacao: somente administrador
		}

		// Colega de turma: le apenas o que esta aprovado.
		if ( 'read_post' === $cap && $this->user_in_turma( $user_id, $turma ) ) {
			return ( 'publish' === $post->post_status && 'aprovado' === $status ) ? $caps : array( 'do_not_allow' );
		}

		return array( 'do_not_allow' );
	}

	private function can_access_atividade( $caps, $cap, $user_id, $post ) {
		if ( $this->is_admin( $user_id ) ) {
			return $caps;
		}

		$author = (int) $post->post_author;
		$turma  = (int) get_post_meta( $post->ID, '_turma', true );

		if ( $user_id === $author ) {
			return $caps;
		}

		if ( $this->user_manages_turma( $user_id, $turma ) ) {
			if ( in_array( $cap, array( 'read_post', 'edit_post' ), true ) ) {
				return $caps;
			}
			return array( 'do_not_allow' );
		}

		return array( 'do_not_allow' );
	}

	private function can_access_local( $caps, $cap, $user_id, $post ) {
		if ( $this->is_admin( $user_id ) ) {
			return $caps;
		}

		$turma = (int) get_post_meta( $post->ID, '_turma', true );

		if ( $this->user_manages_turma( $user_id, $turma ) ) {
			return $caps;
		}

		if ( 'read_post' === $cap && $this->user_in_turma( $user_id, $turma ) ) {
			return $caps;
		}

		return array( 'do_not_allow' );
	}

	private function can_access_escola( $caps, $cap, $user_id, $post ) {
		if ( $this->is_admin( $user_id ) ) {
			return $caps;
		}

		$escola = (int) get_user_meta( $user_id, '_linked_escola', true );
		if ( $escola && $escola === (int) $post->ID ) {
			return $caps;
		}

		return array( 'do_not_allow' );
	}

	/* ------------------------------------------------------------------ */
	/* Validacao do escopo no salvamento                                   */
	/* ------------------------------------------------------------------ */

	public function validate_observation_scope( $post_id, $post ) {
		$this->validate_scope( $post_id, $post, '_turma' );
	}

	public function validate_activity_scope( $post_id, $post ) {
		$this->validate_scope( $post_id, $post, '_turma' );
	}

	/**
	 * Impede que alguem vincule um registro a uma turma da qual nao participa.
	 */
	private function validate_scope( $post_id, $post, $meta_key ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$turma = (int) get_post_meta( $post_id, $meta_key, true );
		if ( ! $turma ) {
			return;
		}

		$user_id   = get_current_user_id();
		$is_author = ( (int) $post->post_author === $user_id );

		if ( $this->is_admin( $user_id ) || $this->user_manages_turma( $user_id, $turma ) ) {
			return;
		}

		if ( $is_author && $this->user_in_turma( $user_id, $turma ) ) {
			return;
		}

		delete_post_meta( $post_id, $meta_key );
	}

	public function remove_meta_boxes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			remove_meta_box( 'authordiv', 'remc_observacao', 'normal' );
		}
	}
}
