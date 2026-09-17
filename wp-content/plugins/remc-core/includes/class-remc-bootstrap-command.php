<?php
/**
 * WP-CLI command: wp remc bootstrap
 *
 * Cria dados ficticios de demonstracao da REMC.
 * Idempotente: executar mais de uma vez nao duplica dados.
 * Use --force para remover e recriar os dados de demonstracao.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

WP_CLI::add_command( 'remc bootstrap', 'Remc_Bootstrap_Command' );

class Remc_Bootstrap_Command {

	/** @var int */
	private $escola_id = 0;

	/** @var int */
	private $professor_a = 0;

	/** @var int */
	private $professor_b = 0;

	/** @var array<int> */
	private $aluno_ids = array();

	/** @var array<string,int> */
	private $turmas = array();

	/** @var array<int> */
	private $local_ids = array();

	public function __invoke( $args, $assoc_args ) {
		$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		WP_CLI::line( '=== REMC Bootstrap ===' );

		if ( $force ) {
			$this->delete_demo_data();
		}

		// Normaliza acentuacao de dados antigos ANTES de reconciliar.
		$this->ensure_site_identity();
		$this->migrate_seed_titles();
		$this->refresh_feed_content();

		if ( ! $this->buddypress_ready() ) {
			WP_CLI::error( 'BuddyPress nao esta ativo com o componente "groups". Ative o BuddyPress e os componentes.' );
			return;
		}

		$this->escola_id   = $this->ensure_school();
		$this->ensure_admin();
		$this->professor_a = $this->ensure_professor( 'professor_a', 'Professor A (Turma A)', $this->escola_id );
		$this->professor_b = $this->ensure_professor( 'professor_b', 'Professor B (Turma B)', $this->escola_id );
		$this->aluno_ids   = $this->ensure_students();

		$this->turmas['A'] = $this->ensure_turma( 'Turma A - 5º Ano', 'Turma do 5º ano com foco em ciências', $this->professor_a );
		$this->turmas['B'] = $this->ensure_turma( 'Turma B - 6º Ano', 'Turma do 6º ano com foco em meio ambiente', $this->professor_b );

		$this->link_students();
		$this->local_ids = $this->ensure_locals();
		$this->ensure_observations();
		$this->ensure_feed_demo();
		$this->ensure_tutorials();
		$this->ensure_activities();
		$this->ensure_pages();
		$this->ensure_main_menu();

		// Necessario apos registrar o arquivo de tutoriais e criar paginas.
		flush_rewrite_rules();

		WP_CLI::success( 'Bootstrap da REMC concluido (dados ficticios).' );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	private function buddypress_ready() {
		return function_exists( 'groups_create_group' ) && function_exists( 'groups_get_id' );
	}

	/**
	 * Garante que o usuario tenha o papel do projeto (idempotente).
	 */
	private function ensure_role( $user, $role ) {
		$u = new WP_User( $user->ID );
		if ( ! in_array( $role, (array) $u->roles, true ) ) {
			$u->set_role( $role );
			WP_CLI::success( "Papel '{$role}' atribuido a {$u->user_login}." );
		}
	}

	/**
	 * Localiza um post por titulo e tipo.
	 */
	private function find_post_by_title( $title, $type ) {
		$found = get_posts( array(
			'post_type'        => $type,
			'post_status'      => array( 'publish', 'draft', 'pending', 'devolvido' ),
			'title'            => $title,
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		) );

		return $found ? (int) $found[0] : 0;
	}

	private function report( $created, $existing, $label ) {
		if ( $created ) {
			WP_CLI::success( $label . ' criado.' );
		} else {
			WP_CLI::line( $label . ' ja existe, mantendo.' );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Criacao                                                             */
	/* ------------------------------------------------------------------ */

	private function ensure_school() {
		$title = 'Escola Exemplo (DADOS FICTÍCIOS)';
		$id    = $this->find_post_by_title( $title, 'remc_escola' );
		$created = false;

		if ( ! $id ) {
			$id = wp_insert_post( array(
				'post_type'   => 'remc_escola',
				'post_title'  => $title,
				'post_status' => 'publish',
			) );
			$created = true;
		}

		update_post_meta( $id, '_municipio', 'Municipio Ficticio' );
		update_post_meta( $id, '_uf', 'SP' );

		$this->report( $created, true, "Escola (ID {$id})" );
		return (int) $id;
	}

	private function ensure_admin() {
		$user = get_user_by( 'login', 'admin_remc' );
		if ( $user ) {
			WP_CLI::line( 'Administrador admin_remc ja existe, mantendo.' );
			return $user->ID;
		}

		$id = wp_create_user( 'admin_remc', 'admin_password_123', 'admin@remc.local' );
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( 'Nao foi possivel criar admin_remc: ' . $id->get_error_message() );
			return 0;
		}
		( new WP_User( $id ) )->set_role( 'administrator' );
		WP_CLI::success( 'Administrador admin_remc criado.' );
		return $id;
	}

	private function ensure_professor( $login, $display, $escola_id ) {
		$user = get_user_by( 'login', $login );
		if ( $user ) {
			$this->ensure_role( $user, 'professor' );
			update_user_meta( $user->ID, '_linked_escola', $escola_id );
			WP_CLI::line( "Professor {$login} ja existe, mantendo." );
			return $user->ID;
		}

		$id = wp_create_user( $login, 'professor_password_123', $login . '@remc.local' );
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "Nao foi possivel criar {$login}: " . $id->get_error_message() );
			return 0;
		}
		$u = new WP_User( $id );
		$u->set_role( 'professor' );
		wp_update_user( array( 'ID' => $id, 'display_name' => $display ) );
		update_user_meta( $id, '_linked_escola', $escola_id );

		WP_CLI::success( "Professor {$login} criado." );
		return (int) $id;
	}

	private function ensure_students() {
		$students = array(
			'aluno_joao'  => 'Aluno Joao (ficticio)',
			'aluno_maria' => 'Aluna Maria (ficticia)',
			'aluno_pedro' => 'Aluno Pedro (ficticio)',
		);
		$ids = array();

		foreach ( $students as $login => $display ) {
			$user = get_user_by( 'login', $login );
			if ( $user ) {
				$this->ensure_role( $user, 'aluno' );
				WP_CLI::line( "Aluno {$login} ja existe, mantendo." );
				$ids[ $login ] = $user->ID;
				continue;
			}

			$id = wp_create_user( $login, 'aluno_password_123', $login . '@remc.local' );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( "Nao foi possivel criar {$login}: " . $id->get_error_message() );
				continue;
			}
			$u = new WP_User( $id );
			$u->set_role( 'aluno' );
			wp_update_user( array( 'ID' => $id, 'display_name' => $display ) );
			WP_CLI::success( "Aluno {$login} criado." );
			$ids[ $login ] = (int) $id;
		}

		return $ids;
	}

	private function ensure_turma( $name, $description, $professor_id ) {
		$slug = sanitize_title( $name );
		$gid  = groups_get_id( $slug );

		if ( $gid ) {
			WP_CLI::line( "Turma '{$name}' ja existe, mantendo." );
		} else {
			$gid = groups_create_group( array(
				'name'          => $name,
				'slug'          => $slug,
				'description'   => $description . ' (DADOS FICTÍCIOS)',
				'status'        => 'hidden',
				'invite_status' => 'invitations_required',
				'hide_sitewide' => 1,
				'creator_id'    => $professor_id,
			) );

			if ( ! $gid || is_wp_error( $gid ) ) {
				WP_CLI::warning( "Nao foi possivel criar a turma '{$name}'." );
				return 0;
			}

			groups_join_group( $gid, $professor_id );
			groups_promote_member( $professor_id, $gid, 'admin' );
			WP_CLI::success( "Turma '{$name}' criada (grupo oculto)." );
		}

		// Garante que a turma permaneça oculta e com entrada controlada.
		groups_edit_group_settings( $gid, 0, 'hidden', 'invitations_required' );
		groups_update_groupmeta( $gid, 'hide_sitewide', 1 );
		groups_update_groupmeta( $gid, 'professor_responsavel', $professor_id );
		groups_update_groupmeta( $gid, 'escola_id', $this->escola_id );

		return (int) $gid;
	}

	private function link_students() {
		$map = array(
			'aluno_joao'  => array( 'A' ),
			'aluno_maria' => array( 'A' ),
			'aluno_pedro' => array( 'B' ),
		);

		foreach ( $map as $login => $keys ) {
			if ( empty( $this->aluno_ids[ $login ] ) ) {
				continue;
			}
			$tids = array();
			foreach ( $keys as $k ) {
				$tids[] = (int) $this->turmas[ $k ];
			}
			update_user_meta( $this->aluno_ids[ $login ], '_linked_turmas', $tids );

			foreach ( $tids as $gid ) {
				groups_join_group( $gid, $this->aluno_ids[ $login ] );
			}
			WP_CLI::line( "Aluno {$login} vinculado a turma(s): " . implode( ',', $tids ) );
		}
	}

	private function ensure_locals() {
		$locals = array();
		$defs   = array(
			array( 'key' => 'escola', 'tipo' => 'escola', 'instrumento' => 'pluviometro_ref_001' ),
			array( 'key' => 'casa', 'tipo' => 'casa', 'instrumento' => 'pluviometro_ref_001' ),
		);

		foreach ( $this->turmas as $letra => $gid ) {
			if ( ! $gid ) {
				continue;
			}
			$professor = ( 'A' === $letra ) ? $this->professor_a : $this->professor_b;

			foreach ( $defs as $def ) {
				$title = "Ponto {$def['tipo']} - Turma {$letra} (DADOS FICTÍCIOS)";
				$id    = $this->find_post_by_title( $title, 'remc_local' );
				$created = false;

				if ( ! $id ) {
					$id = wp_insert_post( array(
						'post_type'   => 'remc_local',
						'post_title'  => $title,
						'post_status' => 'publish',
					) );
					$created = true;
				}

				update_post_meta( $id, '_turma', $gid );
				update_post_meta( $id, '_tipo', $def['tipo'] );
				update_post_meta( $id, '_escola', $this->escola_id );
				update_post_meta( $id, '_instrumento_codigo', $def['instrumento'] );
				update_post_meta( $id, '_instrumento_versao', '1.0' );
				update_post_meta( $id, '_metodo', 'instrumento_artesanal' );
				update_post_meta( $id, '_responsavel', $professor );

				$this->report( $created, true, "Local '{$title}'" );
				$locals[ $letra ][ $def['key'] ] = (int) $id;
			}
		}

		return $locals;
	}

	private function ensure_observations() {
		$now = current_time( 'mysql' );

		$defs = array(
			array(
				'title'  => 'Registro Pluviométrico com chuva (DADOS FICTÍCIOS)',
				'turma'  => 'A',
				'local'  => 'escola',
				'author' => 'aluno_joao',
				'status' => 'publish',
				'date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
				'meta'   => array(
					'_precipitation'       => '12.5',
					'_precipitation_start' => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days 08:00' ) ),
					'_precipitation_end'   => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days 18:00' ) ),
					'_metodo'              => 'instrumento_artesanal',
					'_instrumento_codigo'  => 'pluviometro_ref_001',
					'_instrumento_versao'  => '1.0',
				),
			),
			array(
				'title'  => 'Registro Pluviométrico sem chuva (DADOS FICTÍCIOS)',
				'turma'  => 'A',
				'local'  => 'casa',
				'author' => 'aluno_maria',
				'status' => 'publish',
				'date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-6 days' ) ),
				'meta'   => array(
					'_precipitation'       => '0',
					'_precipitation_start' => gmdate( 'Y-m-d H:i:s', strtotime( '-6 days 08:00' ) ),
					'_precipitation_end'   => gmdate( 'Y-m-d H:i:s', strtotime( '-6 days 18:00' ) ),
					'_metodo'              => 'instrumento_artesanal',
				),
			),
			array(
				'title'  => 'Contagem do anemômetro - 15 voltas/30s (DADOS FICTÍCIOS)',
				'turma'  => 'A',
				'local'  => 'escola',
				'author' => 'aluno_joao',
				'status' => 'pending',
				'date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
				'meta'   => array(
					'_anemometer_rotations' => '15',
					'_anemometer_seconds'   => '30',
					'_anemometer_rpm'       => '30',
					'_wind_direction'       => 'N',
					'_wind_intensity'       => 'leve',
					'_metodo'               => 'instrumento_artesanal',
				),
			),
			array(
				'title'  => 'Deslocamento do barômetro (DADOS FICTÍCIOS)',
				'turma'  => 'A',
				'local'  => 'casa',
				'author' => 'aluno_maria',
				'status' => 'publish',
				'date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-4 days' ) ),
				'meta'   => array(
					'_barometer_reference'    => 'ref_001',
					'_barometer_displacement' => '-2.5',
					'_barometer_orientation'  => 'direita_aumenta',
					'_metodo'                 => 'instrumento_artesanal',
				),
			),
			array(
				'title'  => 'Cobertura de nuvens e extremos térmicos (DADOS FICTÍCIOS)',
				'turma'  => 'B',
				'local'  => 'escola',
				'author' => 'aluno_pedro',
				'status' => 'publish',
				'date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
				'meta'   => array(
					'_cloud_cover'     => '6',
					'_cloud_genera'    => array( 'Cu', 'Sc' ),
					'_sky_condition'   => 'parcialmente_nublado',
					'_temperature_min' => '18',
					'_temperature_max' => '27',
					'_metodo'          => 'instrumento_calibrado',
				),
			),
		);

		foreach ( $defs as $def ) {
			$existing = $this->find_post_by_title( $def['title'], 'remc_observacao' );
			$turma_id = isset( $this->turmas[ $def['turma'] ] ) ? $this->turmas[ $def['turma'] ] : 0;
			$local_id = isset( $this->local_ids[ $def['turma'] ][ $def['local'] ] ) ? $this->local_ids[ $def['turma'] ][ $def['local'] ] : 0;
			$author   = isset( $this->aluno_ids[ $def['author'] ] ) ? $this->aluno_ids[ $def['author'] ] : 0;

			if ( $existing ) {
				WP_CLI::line( "Observacao ja existe: {$def['title']}" );
				$obs_id = $existing;
				// Nao sobrescreve o estado de revisao ja decidido pelo professor.
			} else {
				$obs_id = wp_insert_post( array(
					'post_type'   => 'remc_observacao',
					'post_title'  => $def['title'],
					'post_author' => $author,
					'post_status' => $def['status'],
					'post_date'   => get_date_from_gmt( $def['date'] ),
				) );
				WP_CLI::success( "Observacao criada: {$def['title']}" );

				// Vocabulario de revisao (pt-BR), distinto do post_status do WordPress.
				$review = ( 'publish' === $def['status'] ) ? 'aprovado' : 'pendente';
				update_post_meta( $obs_id, '_status', $review );
			}

			update_post_meta( $obs_id, '_turma', $turma_id );
			update_post_meta( $obs_id, '_local_id', $local_id );
			update_post_meta( $obs_id, '_observation_date', $def['date'] );
			foreach ( $def['meta'] as $k => $v ) {
				update_post_meta( $obs_id, $k, $v );
			}
		}
	}

	/**
	 * Demonstracao do feed social: compartilha (opt-in simulado) uma observacao
	 * aprovada de chuva. Idempotente.
	 */	private function ensure_feed_demo() {
		if ( ! class_exists( 'Remc_Activity' ) || ! function_exists( 'bp_activity_add' ) ) {
			WP_CLI::line( 'Feed social indisponivel (BuddyPress/atividade inativos), pulando.' );
			return;
		}

		$title = 'Registro Pluviométrico com chuva (DADOS FICTÍCIOS)';
		$obs_id = $this->find_post_by_title( $title, 'remc_observacao' );
		if ( ! $obs_id ) {
			return;
		}

		if ( get_post_meta( $obs_id, '_shared_activity_id', true ) ) {
			WP_CLI::line( 'Observacao de chuva ja compartilhada no feed, mantendo.' );
			return;
		}

		$obs = get_post( $obs_id );
		if ( ! $obs || 'aprovado' !== get_post_meta( $obs_id, '_status', true ) ) {
			return;
		}

		$original = get_current_user_id();
		wp_set_current_user( (int) $obs->post_author );
		$ok = Remc_Activity::instance()->create_activity( $obs_id );
		wp_set_current_user( $original );

		if ( $ok ) {
			WP_CLI::success( 'Observacao de chuva compartilhada no feed (demonstracao).' );
		}
	}

	private function ensure_tutorials() {
		$tutoriais = array(
			array( 'I01', 'Pluviômetro de Garrafa PET', 'instrumentos', 'Construir, instalar e usar um pluviômetro caseiro, distinguindo volume coletado de altura de precipitação.' ),
			array( 'I02', 'Anemômetro de Copos', 'instrumentos', 'Construir um anemômetro e comparar a rotação sob condições diferentes.' ),
			array( 'I03', 'Barômetro de Bexiga', 'instrumentos', 'Construir um barômetro, definir linha de referência e observar o deslocamento do ponteiro.' ),
			array( 'E01', 'Nuvem na Garrafa', 'experimentos', 'Investigar umidade, expansão/resfriamento e condensação, incluindo núcleos de condensação.' ),
			array( 'E02', 'Mini Ciclo da Água / Chuva no Pote', 'experimentos', 'Observar evaporação, condensação e retorno de gotículas em um modelo do ciclo da água.' ),
			array( 'E03', 'Experimento das Duas Vasilhas', 'experimentos', 'Comparar temperaturas em duas montagens controladas e discutir os limites da analogia com o efeito estufa.' ),
			array( 'R01', 'Registro Pluviométrico Diário', 'rotinas', 'Registrar quantidade em mm, instrumento, início/fim da acumulação e ocorrências.' ),
			array( 'R02', 'Monitoramento da Pressão Atmosférica com Escala Milimetrada', 'rotinas', 'Manter série de deslocamento do ponteiro (mm) por instrumento/referência e interpretar tendências.' ),
			array( 'R03', 'Contagem de Velocidade do Vento em RPM', 'rotinas', 'Contar voltas do anemômetro e calcular RPM, lembrando que a grandeza é rotação.' ),
			array( 'R04', 'Mapeamento e Classificação Visual de Nuvens', 'rotinas', 'Identificar gêneros (OMM), cobertura em oitavos, horário e setor do céu.' ),
			array( 'R05', 'Rosa dos Ventos Humana', 'rotinas', 'Identificar referências de orientação e registrar a direção de ORIGEM do vento.' ),
			array( 'R06', 'O Experimento da Amplitude Térmica Diária', 'rotinas', 'Registrar extremos ou observações pontuais e calcular a diferença com a devida limitação de cobertura.' ),
			array( 'R07', 'Previsão do Tempo do Aluno', 'rotinas', 'Registrar hipótese antecipada, confrontar com observações reais e refletir sobre o resultado.' ),
		);

		foreach ( $tutoriais as $t ) {
			list( $codigo, $nome, $categoria, $objetivo ) = $t;
			$title = "{$codigo} - {$nome}";
			$id    = $this->find_post_by_title( $title, 'remc_tutorial' );
			$created = false;

			if ( ! $id ) {
				$id = wp_insert_post( array(
					'post_type'    => 'remc_tutorial',
					'post_title'   => $title,
					'post_content' => "Material didático da REMC (reconstrução). Guia: {$nome}.",
					'post_status'  => 'publish',
				) );
				$created = true;
			} else {
				// Mantem o texto de apoio alinhado, sem sobrescrever conteudo editorial.
				$atual = (string) get_post_field( 'post_content', $id );
				if ( '' === $atual || 0 === strpos( $atual, 'Material didatico' ) || 0 === strpos( $atual, 'Material didático' ) ) {
					wp_update_post( array(
						'ID'           => $id,
						'post_content' => "Material didático da REMC (reconstrução). Guia: {$nome}.",
					) );
				}
			}

			wp_set_object_terms( $id, $categoria, 'remc_tutorial_cat' );

			$meta = array(
				'_codigo'            => $codigo,
				'_objective'         => $objetivo,
				'_materials'         => 'Materiais acessíveis/reutilizados (detalhar no guia).',
				'_steps'             => 'Etapas definidas no guia editorial.',
				'_reading_mode'      => 'Individual ou em grupo',
				'_unit'              => $this->unit_for( $codigo ),
				'_limitations'       => 'Instrumento artesanal: registro educativo não equivale à medição oficial.',
				'_precautions'       => 'Supervisão adulta em cortes e água aquecida. Sem chama, solventes ou pressurizadores improvisados.',
				'_collection_fields' => $this->fields_for( $codigo ),
				'_version'           => '1.0',
				'_is_fictional'      => 'DADOS FICTÍCIOS',
			);
			foreach ( $meta as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}

			$this->report( $created, true, "Tutorial {$codigo}" );
		}
	}

	private function unit_for( $codigo ) {
		$units = array(
			'I01' => 'mm de precipitação',
			'I02' => 'RPM (rotações por minuto)',
			'I03' => 'mm de deslocamento do ponteiro',
			'E01' => 'observação visual',
			'E02' => 'observação visual',
			'E03' => 'graus Celsius (C)',
			'R01' => 'mm',
			'R02' => 'mm',
			'R03' => 'RPM',
			'R04' => 'gêneros (OMM) e oitavos (0-8)',
			'R05' => 'direção cardinal de origem',
			'R06' => 'graus Celsius (C)',
			'R07' => 'hipótese (sem unidade)',
		);
		return isset( $units[ $codigo ] ) ? $units[ $codigo ] : '';
	}

	private function fields_for( $codigo ) {
		$fields = array(
			'I01' => 'Volume coletado (ml); área de captação (cm²); altura (mm)',
			'I02' => 'Voltas; segundos; RPM calculada',
			'I03' => 'Deslocamento (mm); referência; orientação da escala',
			'E01' => 'Condições testadas; gotículas visíveis (sim/não)',
			'E02' => 'Condições testadas; sequência observada',
			'E03' => 'Material; conteúdo; cobertura; exposição; temperaturas por tempo',
			'R01' => 'Precipitação (mm); início/fim; horário; esvaziamento',
			'R02' => 'Deslocamento (mm); referência; orientação; tendência',
			'R03' => 'Voltas; segundos; RPM; direção do vento',
			'R04' => 'Gêneros; cobertura (0-8); horário; setor',
			'R05' => 'Direção de origem; calmaria/variável; horário',
			'R06' => 'Tmín; Tmáx; método; instrumento; período',
			'R07' => 'Hipótese; justificativa; evidências; confronto; reflexão',
		);
		return isset( $fields[ $codigo ] ) ? $fields[ $codigo ] : '';
	}

	private function ensure_activities() {
		$defs = array(
			array(
				'title'  => 'Relatório de experimento - Nuvem na Garrafa (DADOS FICTÍCIOS)',
				'author' => 'aluno_joao',
				'turma'  => 'A',
				'meta'   => array(
					'_tipo'       => 'experimento',
					'_hypothesis' => 'Com fumaca, as goticulas se formam com mais facilidade.',
					'_procedure'  => 'Segui as etapas do guia E01 com agua morna.',
					'_results'    => 'Observou-se nuvem visivel ao pressionar e soltar a garrafa.',
					'_reflection' => 'A fumaca atuou como nucleo de condensacao.',
				),
			),
			array(
				'title'  => 'Previsão do tempo - 24h (DADOS FICTÍCIOS)',
				'author' => 'aluno_maria',
				'turma'  => 'A',
				'meta'   => array(
					'_tipo'        => 'previsao',
					'_hypothesis'  => 'Vai chover amanha porque a pressao caiu e ha nuvens baixas.',
					'_evidence'    => 'Deslocamento do barometro: -2.5 mm; cobertura: 7/8',
					'_confrontation' => 'Nao verificavel: faltam medicoes adequadas do periodo.',
					'_reflection'  => 'Aprendi que sem dados suficientes o resultado e nao verificavel.',
				),
			),
		);

		foreach ( $defs as $def ) {
			$id = $this->find_post_by_title( $def['title'], 'remc_atividade' );
			$created = false;

			if ( ! $id ) {
				$author = isset( $this->aluno_ids[ $def['author'] ] ) ? $this->aluno_ids[ $def['author'] ] : 0;
				$id = wp_insert_post( array(
					'post_type'   => 'remc_atividade',
					'post_title'  => $def['title'],
					'post_author' => $author,
					'post_status' => 'publish',
				) );
				$created = true;
			}

			update_post_meta( $id, '_turma', $this->turmas[ $def['turma'] ] );
			update_post_meta( $id, '_status', 'aprovado' );
			foreach ( $def['meta'] as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}

			$this->report( $created, true, "Atividade '{$def['title']}'" );
		}
	}

	/**
	 * Cria as paginas dos paineis (templates do tema), de forma idempotente.
	 */
	private function ensure_pages() {
		$defs = array(
			'remc_pagina_painel_aluno'     => array(
				'title'    => 'Painel do Aluno',
				'template' => 'template-student-dashboard.php',
			),
			'remc_pagina_painel_professor' => array(
				'title'    => 'Painel do Professor',
				'template' => 'template-teacher-dashboard.php',
			),
		);

		foreach ( $defs as $option => $def ) {
			$id = $this->find_post_by_title( $def['title'], 'page' );
			if ( ! $id ) {
				$id = wp_insert_post( array(
					'post_type'   => 'page',
					'post_title'  => $def['title'],
					'post_status' => 'publish',
					'post_name'   => sanitize_title( $def['title'] ),
				) );
				WP_CLI::success( "Pagina '{$def['title']}' criada." );
			} else {
				WP_CLI::line( "Pagina '{$def['title']}' ja existe, mantendo." );
			}

			update_post_meta( $id, '_wp_page_template', $def['template'] );
			update_option( $option, get_permalink( $id ) );
		}
	}

	/**
	 * URL de um diretorio do BuddyPress, sem chutar o slug.
	 */
	private function directory_url( $component ) {
		$candidatos = array(
			'activity' => array( 'bp_get_activity_directory_url', 'bp_get_activity_directory_permalink' ),
			'groups'   => array( 'bp_get_groups_directory_url', 'bp_get_groups_directory_permalink' ),
			'members'  => array( 'bp_get_members_directory_url', 'bp_get_members_directory_permalink' ),
		);

		foreach ( $candidatos[ $component ] as $f ) {
			if ( function_exists( $f ) ) {
				return (string) call_user_func( $f );
			}
		}
		return '';
	}

	/**
	 * Cria/reconcilia o menu principal e o associa a localizacao "main" do tema.
	 * Idempotente: atualiza apenas os itens que divergem.
	 */
	private function ensure_main_menu() {
		$menu_name = 'REMC - Menu Principal';
		$menu      = wp_get_nav_menu_object( $menu_name );

		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $menu_name );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::warning( 'Nao foi possivel criar o menu principal.' );
				return;
			}
			WP_CLI::success( "Menu '{$menu_name}' criado." );
		} else {
			$menu_id = (int) $menu->term_id;
			WP_CLI::line( "Menu '{$menu_name}' ja existe, reconciliando itens." );
		}

		$tutoriais = get_post_type_archive_link( 'remc_tutorial' );
		$timeline  = $this->directory_url( 'activity' );
		$turmas    = $this->directory_url( 'groups' );

		/*
		 * Estrutura do menu:
		 *   [ Início | Feed | Minha Timeline | Turma | Observações ▾ ]   [ Tutoriais | Programa Educação ]
		 *   Observações ▾: Painel do Aluno, Área de Trabalho
		 *
		 * "Painel do Professor" fica no grupo da direita e só aparece para
		 * professor/administrador (regra aplicada pelo tema).
		 */
		$renomear = array(
			'Timeline'       => 'Feed',
			'Turmas'         => 'Turma',
			'Minha timeline' => 'Minha Timeline',
		);

		$desejados = array(
			'Início'              => array( 'url' => home_url( '/' ), 'parent' => '' ),
			'Feed'                => array( 'url' => $timeline ? $timeline : home_url( '/activity/' ), 'parent' => '' ),
			'Minha Timeline'      => array( 'url' => '#remc-minha-timeline', 'parent' => '' ),
			'Turma'               => array( 'url' => $turmas ? $turmas : home_url( '/grupos/' ), 'parent' => '' ),
			'Observações'         => array( 'url' => '#', 'parent' => '' ),
			'Painel do Aluno'     => array( 'url' => '#remc-painel-aluno', 'parent' => 'Observações' ),
			'Área de Trabalho'    => array( 'url' => '#remc-logado', 'parent' => 'Observações' ),
			'Tutoriais'           => array( 'url' => $tutoriais ? $tutoriais : home_url( '/tutoriais/' ), 'parent' => '', 'classes' => 'remc-menu-right' ),
			'Programa Educação'   => array( 'url' => home_url( '/#programa-educacao' ), 'parent' => '', 'classes' => 'remc-menu-right' ),
			'Painel do Professor' => array( 'url' => '#remc-painel-professor', 'parent' => '', 'classes' => 'remc-menu-right' ),
		);

		// Indice dos itens existentes por titulo.
		$existentes = array();
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			$existentes[ $item->title ] = $item;
		}

		// Aplica renomeacoes preservando o item (e o ID).
		foreach ( $renomear as $antigo => $novo ) {
			if ( isset( $existentes[ $antigo ] ) && ! isset( $existentes[ $novo ] ) ) {
				$item = $existentes[ $antigo ];
				wp_update_nav_menu_item( $menu_id, $item->ID, array(
					'menu-item-title'  => $novo,
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				) );
				$existentes[ $novo ] = $item;
				unset( $existentes[ $antigo ] );
			}
		}

		// Remove itens que nao fazem mais parte do menu.
		foreach ( $existentes as $titulo => $item ) {
			if ( ! isset( $desejados[ $titulo ] ) ) {
				wp_delete_post( $item->ID, true );
				unset( $existentes[ $titulo ] );
			}
		}

		$posicao = 1;
		$ids     = array();
		$criados = 0;
		$ajustes = 0;

		foreach ( $desejados as $titulo => $cfg ) {
			$parent_id = 0;
			if ( ! empty( $cfg['parent'] ) && isset( $ids[ $cfg['parent'] ] ) ) {
				$parent_id = (int) $ids[ $cfg['parent'] ];
			}

			$args = array(
				'menu-item-title'     => $titulo,
				'menu-item-url'       => $cfg['url'],
				'menu-item-parent-id' => $parent_id,
				'menu-item-position'  => $posicao,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-classes'   => isset( $cfg['classes'] ) ? $cfg['classes'] : '',
			);

			if ( isset( $existentes[ $titulo ] ) ) {
				$item = $existentes[ $titulo ];
				wp_update_nav_menu_item( $menu_id, $item->ID, $args );
				$ids[ $titulo ] = $item->ID;
				$ajustes++;
			} else {
				$novo_id = wp_update_nav_menu_item( $menu_id, 0, $args );
				if ( ! is_wp_error( $novo_id ) ) {
					$ids[ $titulo ] = $novo_id;
					$criados++;
				}
			}
			$posicao++;
		}

		if ( $criados ) {
			WP_CLI::success( "Itens do menu criados: {$criados}." );
		}
		if ( $ajustes ) {
			WP_CLI::success( "Itens do menu ajustados: {$ajustes}." );
		}

		$locations         = (array) get_theme_mod( 'nav_menu_locations', array() );
		$locations['main'] = $menu_id;
		// O rodape nao replica o menu principal.
		if ( isset( $locations['footer'] ) && (int) $locations['footer'] === (int) $menu_id ) {
			unset( $locations['footer'] );
		}
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Identidade do site com acentuação correta (idempotente).
	 */
	private function ensure_site_identity() {
		update_option( 'blogname', 'REMC — Rede Educacional de Monitoramento Climático' );
		update_option( 'blogdescription', 'Programa Educação CPTEC/INPE — observe, registre, compartilhe e aprenda.' );
	}

	/**
	 * Mapa de titulos/rotulos antigos (sem acento) para os corretos.
	 *
	 * Usado para renomear dados de demonstracao ja existentes sem duplicar.
	 */
	private static function accent_map() {
		return array(
			'posts'   => array(
				'Escola Exemplo (DADOS FICTICIOS)'                                   => 'Escola Exemplo (DADOS FICTÍCIOS)',
				'Ponto escola - Turma A (DADOS FICTICIOS)'                           => 'Ponto escola - Turma A (DADOS FICTÍCIOS)',
				'Ponto casa - Turma A (DADOS FICTICIOS)'                             => 'Ponto casa - Turma A (DADOS FICTÍCIOS)',
				'Ponto escola - Turma B (DADOS FICTICIOS)'                           => 'Ponto escola - Turma B (DADOS FICTÍCIOS)',
				'Ponto casa - Turma B (DADOS FICTICIOS)'                             => 'Ponto casa - Turma B (DADOS FICTÍCIOS)',
				'Registro Pluviometrico com chuva (DADOS FICTICIOS)'                 => 'Registro Pluviométrico com chuva (DADOS FICTÍCIOS)',
				'Registro Pluviometrico sem chuva (DADOS FICTICIOS)'                 => 'Registro Pluviométrico sem chuva (DADOS FICTÍCIOS)',
				'Contagem do anemometro - 15 voltas/30s (DADOS FICTICIOS)'           => 'Contagem do anemômetro - 15 voltas/30s (DADOS FICTÍCIOS)',
				'Deslocamento do barometro (DADOS FICTICIOS)'                        => 'Deslocamento do barômetro (DADOS FICTÍCIOS)',
				'Cobertura de nuvens e extremos termicos (DADOS FICTICIOS)'          => 'Cobertura de nuvens e extremos térmicos (DADOS FICTÍCIOS)',
				'Relatorio de experimento - Nuvem na Garrafa (DADOS FICTICIOS)'      => 'Relatório de experimento - Nuvem na Garrafa (DADOS FICTÍCIOS)',
				'Previsao do tempo - 24h (DADOS FICTICIOS)'                          => 'Previsão do tempo - 24h (DADOS FICTÍCIOS)',
				'I01 - Pluviometro de Garrafa PET'                                   => 'I01 - Pluviômetro de Garrafa PET',
				'I02 - Anemometro de Copos'                                          => 'I02 - Anemômetro de Copos',
				'I03 - Barometro de Bexiga'                                          => 'I03 - Barômetro de Bexiga',
				'E02 - Mini Ciclo da Agua / Chuva no Pote'                           => 'E02 - Mini Ciclo da Água / Chuva no Pote',
				'R01 - Registro Pluviometrico Diario'                                => 'R01 - Registro Pluviométrico Diário',
				'R02 - Monitoramento da Pressao Atmosferica com Escala Milimetrada'  => 'R02 - Monitoramento da Pressão Atmosférica com Escala Milimetrada',
				'R04 - Mapeamento e Classificacao Visual de Nuvens'                  => 'R04 - Mapeamento e Classificação Visual de Nuvens',
				'R06 - O Experimento da Amplitude Termica Diaria'                    => 'R06 - O Experimento da Amplitude Térmica Diária',
				'R07 - Previsao do Tempo do Aluno'                                   => 'R07 - Previsão do Tempo do Aluno',
			),
			'groups'  => array(
				'Turma A – 5o Ano' => array(
					'name'        => 'Turma A - 5º Ano',
					'description' => 'Turma do 5º ano com foco em ciências (DADOS FICTÍCIOS)',
				),
				'Turma A - 5o Ano' => array(
					'name'        => 'Turma A - 5º Ano',
					'description' => 'Turma do 5º ano com foco em ciências (DADOS FICTÍCIOS)',
				),
				'Turma B - 6o Ano' => array(
					'name'        => 'Turma B - 6º Ano',
					'description' => 'Turma do 6º ano com foco em meio ambiente (DADOS FICTÍCIOS)',
				),
			),
		);
	}

	/**
	 * Renomeia dados de demonstracao antigos para a versao com acentos.
	 * Idempotente: se o titulo novo ja existe, apenas remove/ignora o antigo.
	 */
	private function migrate_seed_titles() {
		$mapa  = self::accent_map();
		$tipos = array( 'remc_escola', 'remc_local', 'remc_observacao', 'remc_tutorial', 'remc_atividade' );
		$total = 0;

		foreach ( $mapa['posts'] as $antigo => $novo ) {
			$antigos = get_posts( array(
				'post_type'      => $tipos,
				'post_status'    => 'any',
				'title'          => $antigo,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			if ( empty( $antigos ) ) {
				continue;
			}

			$id     = (int) $antigos[0];
			$existe = $this->find_post_by_title( $novo, get_post_type( $id ) );
			if ( $existe && $existe !== $id ) {
				wp_delete_post( $id, true );
			} else {
				wp_update_post( array( 'ID' => $id, 'post_title' => $novo ) );
			}
			$total++;
		}

		if ( function_exists( 'groups_get_groups' ) ) {
			$grupos = groups_get_groups( array( 'show_hidden' => true, 'per_page' => false ) );
			if ( ! empty( $grupos['groups'] ) ) {
				foreach ( $grupos['groups'] as $g ) {
					if ( ! isset( $mapa['groups'][ $g->name ] ) ) {
						continue;
					}
					$novo = $mapa['groups'][ $g->name ];
					if ( function_exists( 'groups_edit_base_group_details' ) ) {
						groups_edit_base_group_details( array(
							'group_id'    => $g->id,
							'name'        => $novo['name'],
							'description' => $novo['description'],
							'notify_members' => false,
						) );
						$total++;
					}
				}
			}
		}

		if ( $total ) {
			WP_CLI::success( "Titulos normalizados (acentuacao): {$total}." );
		}
	}

	/**
	 * Regenera o texto publico ja gravado nos itens do feed, para que a
	 * acentuacao acompanhe as observacoes atuais.
	 */
	private function refresh_feed_content() {
		if ( ! class_exists( 'Remc_Activity' ) ) {
			return;
		}

		global $wpdb;
		$tabela = $wpdb->prefix . 'bp_activity';
		$itens  = $wpdb->get_results( "SELECT id, item_id FROM {$tabela} WHERE component = 'remc'" );
		$total  = 0;
		$limpos = 0;

		foreach ( $itens as $item ) {
			$obs      = get_post( (int) $item->item_id );
			$aprovado = $obs && 'publish' === $obs->post_status && 'aprovado' === get_post_meta( $obs->ID, '_status', true );

			if ( ! $aprovado ) {
				if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
					bp_activity_delete_by_item_id( array(
						'item_id'   => (int) $item->item_id,
						'component' => 'remc',
						'type'      => 'remc_shared_observation',
					) );
				}
				delete_post_meta( (int) $item->item_id, '_shared_activity_id' );
				$limpos++;
				continue;
			}

			$conteudo = Remc_Activity::build_public_content( (int) $item->item_id );
			if ( '' === $conteudo ) {
				continue;
			}
			$wpdb->update( $tabela, array( 'content' => $conteudo ), array( 'id' => (int) $item->id ) );
			$total++;
		}

		if ( $total ) {
			WP_CLI::success( "Texto do feed atualizado: {$total}." );
		}
		if ( $limpos ) {
			WP_CLI::success( "Itens fora de aprovacao removidos do feed: {$limpos}." );
		}
	}

	/**
	 * Remove os dados de demonstracao criados por este comando.
	 */
	private function delete_demo_data() {
		WP_CLI::line( 'Removendo dados de demonstracao existentes (--force)...' );

		$tipos = array( 'remc_observacao', 'remc_atividade', 'remc_local', 'remc_tutorial', 'remc_escola' );
		foreach ( $tipos as $tipo ) {
			$ids = get_posts( array(
				'post_type'      => $tipo,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
			foreach ( $ids as $id ) {
				$titulo = (string) get_the_title( $id );
				$fake   = ( false !== strpos( $titulo, '(DADOS FICTICIOS)' ) || false !== strpos( $titulo, '(DADOS FICTÍCIOS)' ) );
				if ( $fake || 'remc_tutorial' === $tipo ) {
					wp_delete_post( $id, true );
				}
			}
		}

		if ( function_exists( 'groups_get_groups' ) ) {
			$groups = groups_get_groups( array( 'per_page' => false, 'show_hidden' => true ) );
			if ( ! empty( $groups['groups'] ) ) {
				foreach ( $groups['groups'] as $g ) {
					if ( false !== strpos( $g->name, 'Turma ' ) ) {
						groups_delete_group( $g->id );
					}
				}
			}
		}
	}
}
