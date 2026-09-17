/**
 * REMC Core - Bootstrap Script
 * Creates default data for development and testing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent direct execution
if ( ! defined( 'REMC_CORE_DIR' ) ) {
	return;
}

// Only run in WP_CLI context
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Bootstrap class for REMC
 */
class Remc_Bootstrap {
	
	public function register() {
		WP_CLI::add_command( 'remc bootstrap', array( $this, 'bootstrap' ) );
	}
	
	/**
	 * Create default data for REMC
	 */
	public function bootstrap( $args, $assoc_args ) {
		WP_CLI::line( 'Iniciando bootstrap da REMC...' );
		
		// Create default school
		$escola_id = $this->create_school();
		
		// Create default users
		$admin_id = $this->create_admin();
		$professor_id = $this->create_professor( $escola_id );
		$aluno_ids = $this->create_students( $professor_id, $escola_id );
		
		// Create default groups (turmas)
		$turma_ids = $this->create_groups( $professor_id );
		
		// Link students to turmas
		$this->link_students_to_turmas( $aluno_ids, $turma_ids );
		
		// Create observation points
		$local_ids = $this->create_observation_points( $turma_ids );
		
		// Create sample observations
		$this->create_sample_observations( $aluno_ids, $local_ids );
		
		// Create sample tutorials
		$this->create_sample_tutorials();
		
		// Create sample activities
		$this->create_sample_activities( $aluno_ids );
		
		WP_CLI::success( 'Bootstrap concluído!' );
	}
	
	private function create_school() {
		$escola_id = wp_insert_post( array(
			'post_type' => 'remc_escola',
			'post_title' => 'Escola Exemplo',
			'post_status' => 'publish',
		) );
		
		WP_CLI::line( "Escola criada: ID {$escola_id}" );
		
		return $escola_id;
	}
	
	private function create_admin() {
		$user_id = username_exists( 'admin_remc' );
		
		if ( ! $user_id ) {
			$user_id = wp_create_user( 'admin_remc', 'admin_password_123', 'admin@remc.local' );
			$user = new WP_User( $user_id );
			$user->set_role( 'administrator' );
			
			WP_CLI::line( "Administrador criado: admin_remc" );
		} else {
			WP_CLI::line( "Administrador já existe: admin_remc" );
		}
		
		return $user_id;
	}
	
	private function create_professor( $escola_id ) {
		$user_id = username_exists( 'professor_exemplo' );
		
		if ( ! $user_id ) {
			$user_id = wp_create_user( 'professor_exemplo', 'professor_password_123', 'professor@remc.local' );
			$user = new WP_User( $user_id );
			$user->set_role( 'professor' );
			
			// Link professor to school
			update_user_meta( $user_id, '_linked_escola', $escola_id );
			
			WP_CLI::line( "Professor criado: professor_exemplo" );
		} else {
			WP_CLI::line( "Professor já existe: professor_exemplo" );
		}
		
		return $user_id;
	}
	
	private function create_students( $professor_id, $escola_id ) {
		$students = array(
			array( 'username' => 'aluno_joao', 'email' => 'joao@remc.local', 'display_name' => 'João da Silva' ),
			array( 'username' => 'aluno_maria', 'email' => 'maria@remc.local', 'display_name' => 'Maria Oliveira' ),
			array( 'username' => 'aluno_pedro', 'email' => 'pedro@remc.local', 'display_name' => 'Pedro Santos' ),
		);
		
		$student_ids = array();
		
		foreach ( $students as $student ) {
			$user_id = username_exists( $student['username'] );
			
			if ( ! $user_id ) {
				$user_id = wp_create_user( $student['username'], 'aluno_password_123', $student['email'] );
				$user = new WP_User( $user_id );
				$user->set_role( 'aluno' );
				update_user_meta( $user_id, 'display_name', $student['display_name'] );
				update_user_meta( $user_id, '_linked_turmas', array() );
				
				WP_CLI::line( "Aluno criado: {$student['username']}" );
			} else {
				WP_CLI::line( "Aluno já existe: {$student['username']}" );
			}
			
			$student_ids[] = $user_id;
		}
		
		return $student_ids;
	}
	
	private function create_groups( $professor_id ) {
		$turmas = array(
			array( 'title' => 'Turma A - 5º Ano', 'description' => 'Turma do 5º ano com foco em ciências' ),
			array( 'title' => 'Turma B - 6º Ano', 'description' => 'Turma do 6º ano com foco em meio ambiente' ),
		);
		
		$turma_ids = array();
		
		foreach ( $turmas as $turma ) {
			// Check if BuddyPress groups exist
			if ( function_exists( 'groups_create_group' ) ) {
				$group_id = groups_get_group_by( 'slug', sanitize_title( $turma['title'] ) );
				
				if ( ! $group_id ) {
					$group_id = groups_create_group( array(
						'name' => $turma['title'],
						'slug' => sanitize_title( $turma['title'] ),
						'description' => $turma['description'],
						'invite_status' => 'invitations_required',
						'hide_sitewide' => 1,
						'creator_id' => $professor_id,
					) );
					
					// Set professor as group admin
					groups_join_group( $group_id, $professor_id );
					groups_promote_user( $group_id, $professor_id, 'admin' );
					
					// Store professor reference
					groups_update_groupmeta( $group_id, 'professor_responsavel', $professor_id );
					
					WP_CLI::line( "Turma criada: {$turma['title']}" );
				} else {
					WP_CLI::line( "Turma já existe: {$turma['title']}" );
				}
				
				$turma_ids[] = $group_id;
			} else {
				// Fallback: create a custom post as group
				$group_id = wp_insert_post( array(
					'post_type' => 'group',
					'post_title' => $turma['title'],
					'post_content' => $turma['description'],
					'post_status' => 'publish',
					'post_author' => $professor_id,
				) );
				
				update_post_meta( $group_id, 'professor_responsavel', $professor_id );
				update_post_meta( $group_id, 'escola_id', get_user_meta( $professor_id, '_linked_escola', true ) );
				
				WP_CLI::line( "Grupo criado (fallback): {$turma['title']}" );
				$turma_ids[] = $group_id;
			}
		}
		
		return $turma_ids;
	}
	
	private function link_students_to_turmas( $aluno_ids, $turma_ids ) {
		foreach ( $aluno_ids as $aluno_id ) {
			update_user_meta( $aluno_id, '_linked_turmas', $turma_ids );
			WP_CLI::line( "Aluno {$aluno_id} vinculado às turmas" );
		}
	}
	
	private function create_observation_points( $turma_ids ) {
		$locals = array(
			array( 'title' => 'Ponto de Observação Escola', 'type' => 'escola' ),
			array( 'title' => 'Ponto de Observação Casa', 'type' => 'casa' ),
		);
		
		$local_ids = array();
		
		foreach ( $turma_ids as $turma_id ) {
			foreach ( $locals as $local ) {
				$local_id = wp_insert_post( array(
					'post_type' => 'remc_local',
					'post_title' => $local['title'] . ' - ' . get_post( $turma_id )->post_title,
					'post_status' => 'publish',
				) );
				
				update_post_meta( $local_id, '_turma', $turma_id );
				update_post_meta( $local_id, '_tipo', $local['type'] );
				update_post_meta( $local_id, '_escola', get_user_meta( get_user_meta( $turma_id, 'professor_responsavel', true ), '_linked_escola', true ) );
				update_post_meta( $local_id, '_pluviometer', 'pluviometro_ref_001' );
				update_post_meta( $local_id, '_pluviometer_version', '1.0' );
				
				$local_ids[] = $local_id;
				WP_CLI::line( "Local criado: {$local['title']}" );
			}
		}
		
		return $local_ids;
	}
	
	private function create_sample_observations( $aluno_ids, $local_ids ) {
		$observation_types = array(
			array(
				'title' => 'Observação Pluviométrica - Chuva',
				'data' => array(
					'_observation_date' => date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
					'_status' => 'aprovado',
					'_turma' => $local_ids[0],
					'_local_id' => $local_ids[0],
					'_precipitation' => '12.5',
					'_precipitation_start' => date( 'Y-m-d H:i:s', strtotime( '-7 days 08:00' ) ),
					'_precipitation_end' => date( 'Y-m-d H:i:s', strtotime( '-7 days 18:00' ) ),
					'_instrument' => 'pluviometro_ref_001',
					'_instrument_version' => '1.0',
				),
			),
			array(
				'title' => 'Observação Pluviométrica - Sem Chuva',
				'data' => array(
					'_observation_date' => date( 'Y-m-d H:i:s', strtotime( '-6 days' ) ),
					'_status' => 'aprovado',
					'_turma' => $local_ids[1],
					'_local_id' => $local_ids[1],
					'_precipitation' => '0',
					'_precipitation_start' => date( 'Y-m-d H:i:s', strtotime( '-6 days 08:00' ) ),
					'_precipitation_end' => date( 'Y-m-d H:i:s', strtotime( '-6 days 18:00' ) ),
					'_instrument' => 'pluviometro_ref_001',
					'_instrument_version' => '1.0',
				),
			),
			array(
				'title' => 'Observação Anemômetro',
				'data' => array(
					'_observation_date' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
					'_status' => 'pendente',
					'_turma' => $local_ids[0],
					'_local_id' => $local_ids[0],
					'_anemometer_rotations' => '15',
					'_anemometer_seconds' => '30',
					'_anemometer_rpm' => '30.00',
					'_wind_direction' => 'N',
					'_wind_intensity' => 'leve',
				),
			),
			array(
				'title' => 'Observação Barômetro',
				'data' => array(
					'_observation_date' => date( 'Y-m-d H:i:s', strtotime( '-4 days' ) ),
					'_status' => 'aprovado',
					'_turma' => $local_ids[1],
					'_local_id' => $local_ids[1],
					'_barometer_reference' => 'referencia_001',
					'_barometer_displacement' => '-2.5',
				),
			),
		);
		
		$aluno_idx = 0;
		
		foreach ( $observation_types as $obs ) {
			$obs_id = wp_insert_post( array(
				'post_type' => 'remc_observacao',
				'post_title' => $obs['title'],
				'post_author' => $aluno_ids[ $aluno_idx % count( $aluno_ids ) ],
				'post_status' => $obs['data']['_status'],
				'post_date' => $obs['data']['_observation_date'],
			) );
			
			foreach ( $obs['data'] as $key => $value ) {
				update_post_meta( $obs_id, $key, $value );
			}
			
			WP_CLI::line( "Observação criada: {$obs['title']}" );
			$aluno_idx++;
		}
	}
	
	private function create_sample_tutorials() {
		$tutorials = array(
			array(
				'title' => 'Pluviômetro de Garrafa PET',
				'category' => 'instrumentos',
				'objective' => 'Construir e utilizar um pluviômetro caseiro para medir a precipitação.',
				'materials' => 'Garrafa PET de 2 litros, faca, fita adesiva, régua, marcador.',
				'steps' => '1. Corte a garrafa na metade.\n2. Inverta a parte superior e insira na inferior.\n3. Marque a escala com a régua.\n4. Instale em local aberto e protegido.',
				'reading_mode' => 'Individual ou em grupo',
				'unit' => 'mm de precipitação',
				'limitations' => 'Instrumento artesanal, sem calibração precisa. Mede volume, não altura diretamente.',
				'precautions' => 'Cuidado ao cortar a garrafa. Instalar em local seguro.',
				'collection_fields' => 'Volume coletado (ml), área de captação (cm²), altura da chuva (mm)',
				'version' => '1.0',
			),
			array(
				'title' => 'Anemômetro de Copos',
				'category' => 'instrumentos',
				'objective' => 'Construir um anemômetro simples para medir rotação e estimar velocidade do vento.',
				'materials' => '4 copos de papel, canudos, alfinete, base de madeira ou plástico, régua.',
				'steps' => '1. Fixe os copos em cruz.\n2. Fixe os canudos no centro.\n3. Fixe um alfinete para rotação.\n4. conte as voltas em 30 segundos.',
				'reading_mode' => 'Grupal',
				'unit' => 'RPM (rotações por minuto)',
				'limitations' => 'Não converte para velocidade do vento. Fator de calibração necessário.',
				'precautions' => 'Evitar ventos muito fortes. Supervisão adulta.',
				'collection_fields' => 'Voltas, segundos, RPM calculado',
				'version' => '1.0',
			),
			array(
				'title' => 'Barômetro de Bexiga',
				'category' => 'instrumentos',
				'objective' => 'Construir um barômetro simples para observar variações de pressão.',
				'materials' => 'Bexiga, frasco de vidro, canudo, papel, fita, base.',
				'steps' => '1. Cubra o frasco com a bexiga.\n2. Fixe o canudo como ponteiro.\n3. Crie uma escala.\n4. Observe as variações.',
				'reading_mode' => 'Individual',
				'unit' => 'mm de deslocamento do ponteiro',
				'limitations' => 'Não mede pressão absoluta. Sensível a temperatura.',
				'precautions' => 'Evitar variações bruscas de temperatura.',
				'collection_fields' => 'Deslocamento do ponteiro (mm), referência, orientação da escala',
				'version' => '1.0',
			),
			array(
				'title' => 'Nuvem na Garrafa',
				'category' => 'experimentos',
				'objective' => 'Investigar condensação e formação de nuvens em modelo controlado.',
				'materials' => 'Garrafa PET, água morna, fósforo ou isqueiro, tampão.',
				'steps' => '1. Coloque água morna na garrafa.\n2. Agite para evaporar.\n3. Solte fumaça dentro.\n4. Aperte e solte a garrafa.',
				'reading_mode' => 'Demonstração',
				'unit' => 'Observação visual',
				'limitations' => 'Modelo simplificado. Não replica todas as condições atmosféricas.',
				'precautions' => 'Cuidado com fogo. Supervisão adulta obrigatória.',
				'collection_fields' => 'Condições testadas, observação de gotículas, temperatura',
				'version' => '1.0',
			),
			array(
				'title' => 'Registro Pluviométrico Diário',
				'category' => 'rotinas',
				'objective' => 'Realizar leitura diária do pluviômetro e registrar dados.',
				'materials' => 'Pluviômetro, caderno ou formulário digital.',
				'steps' => '1. Verifique o volume coletado.\n2. Anote data e horário.\n3. Registre observações.\n4. Esvazie o coletor.',
				'reading_mode' => 'Diário',
				'unit' => 'mm de precipitação',
				'limitations' => 'Depende da calibração do dispositivo.',
				'precautions' => 'Registrar mesmo sem chuva. Anotar eventuais falhas.',
				'collection_fields' => 'Volume (ml), início/fim da acumulação, horário, observações',
				'version' => '1.0',
			),
			array(
				'title' => 'Monitoramento da Pressão Atmosférica',
				'category' => 'rotinas',
				'objective' => 'Acompanhar variações de pressão com barômetro de bexiga.',
				'materials' => 'Barômetro de bexiga, caderno ou formulário.',
				'steps' => '1. Leia o deslocamento do ponteiro.\n2. Registre data e horário.\n3. Compare com leituras anteriores.',
				'reading_mode' => 'Diário',
				'unit' => 'mm de deslocamento',
				'limitations' => 'Apenas tendências relativas. Sensível a temperatura.',
				'precautions' => 'Manter referência fixa. Evitar toque no ponteiro.',
				'collection_fields' => 'Deslocamento (mm), referência, orientação, observações',
				'version' => '1.0',
			),
			array(
				'title' => 'Contagem de Velocidade do Vento em RPM',
				'category' => 'rotinas',
				'objective' => 'Contar rotações do anemômetro e calcular RPM.',
				'materials' => 'Anemômetro de copos, cronômetro, formulário.',
				'steps' => '1. Posicione-se em local aberto.\n2. Conte as voltas em 30 segundos.\n3. Calcule RPM = 60 × voltas / segundos.',
				'reading_mode' => 'Individual ou grupal',
				'unit' => 'RPM',
				'limitations' => 'RPM não é velocidade do vento. Fator de calibração necessário.',
				'precautions' => 'Evitar ventos muito fortes. Supervisão.',
				'collection_fields' => 'Voltas, segundos, RPM calculado, direção do vento',
				'version' => '1.0',
			),
			array(
				'title' => 'Mapeamento e Classificação Visual de Nuvens',
				'category' => 'rotinas',
				'objective' => 'Identificar gêneros de nuvens e medir cobertura em oitavos.',
				'materials' => 'Cartolina com imagens dos gêneros (OMM), caderno.',
				'steps' => '1. Observe o céu.\n2. Identifique gêneros presentes.\n3. Estime cobertura em oitavos.\n4. Registre horário e setor.',
				'reading_mode' => 'Individual',
				'unit' => 'Gêneros e oitavos (0-8)',
				'limitations' => 'Requer treinamento. Nuvens podem ser múltiplas.',
				'precautions' => 'Não forçar identificação. Anotar incertezas.',
				'collection_fields' => 'Gêneros (múltiplos), cobertura (0-8), horário, setor',
				'version' => '1.0',
			),
			array(
				'title' => 'O Experimento da Amplitude Térmica Diária',
				'category' => 'rotinas',
				'objective' => 'Medir e calcular a diferença entre temperatura máxima e mínima.',
				'materials' => 'Termômetro, formulário, cronômetro.',
				'steps' => '1. Registre temperatura mínima (manhã).\n2. Registre temperatura máxima (tarde).\n3. Calcule amplitude = Tmáx - Tmín.',
				'reading_mode' => 'Diário',
				'unit' => '°C',
				'limitations' => 'Depende do instrumento. Um único par não dá amplitude diária.',
				'precautions' => 'Usar mesmo instrumento e local. Registrar método.',
				'collection_fields' => 'Tmín, Tmáx, método, instrumento, período',
				'version' => '1.0',
			),
			array(
				'title' => 'Previsão do Tempo do Aluno',
				'category' => 'rotinas',
				'objective' => 'Elaborar previsão baseada em observações e confrontar depois.',
				'materials' => 'Formulário, observações anteriores.',
				'steps' => '1. Analise dados atuais (chuva, pressão, nuvens).\n2. Formule hipótese para o próximo dia.\n3. Registre justificativa.\n4. Confronte amanhã.',
				'reading_mode' => 'Individual',
				'unit' => 'N/A (hipótese)',
				'limitations' => 'Não é serviço de previsão meteorológica. Requer análise crítica.',
				'precautions' => 'Hipótese deve ser antecipada. Aprovação não altera hipótese original.',
				'collection_fields' => 'Hipótese, justificativa, evidências, confronto, reflexão',
				'version' => '1.0',
			),
		);
		
		$categories = array( 'instrumentos' => 'Instrumentos meteorológicos caseiros', 
		                     'experimentos' => 'Experimentos de fenômenos atmosféricos',
		                     'rotinas' => 'Rotinas "Aluno Estação Meteorológica"' );
		
		foreach ( $tutorials as $tutorial ) {
			$tutorial_id = wp_insert_post( array(
				'post_type' => 'remc_tutorial',
				'post_title' => $tutorial['title'],
				'post_content' => "Guia educativo para {$tutorial['title']}",
				'post_status' => 'publish',
				'post_author' => 1,
			) );
			
			// Add category
			wp_set_object_terms( $tutorial_id, $tutorial['category'], 'remc_tutorial_cat' );
			
			// Save metadata
			foreach ( $tutorial as $key => $value ) {
				if ( ! in_array( $key, array( 'title', 'category' ) ) ) {
					update_post_meta( $tutorial_id, '_' . $key, $value );
				}
			}
			
			WP_CLI::line( "Tutorial criado: {$tutorial['title']}" );
		}
	}
	
	private function create_sample_activities( $aluno_ids ) {
		$activities = array(
			array(
				'title' => 'Relatório de Experimento - Nuvem na Garrafa',
				'author' => $aluno_ids[0],
				'status' => 'publish',
				'data' => array(
					'_turma' => $aluno_ids[0],
					'_tutorial_id' => 1,
					'_hypothesis' => 'Com fumaça, as gotículas se formam melhor.',
					'_procedure' => 'Seguiu os passos do tutorial. Usou água morna e fósforo.',
					'_results' => 'Formou-se uma nuvem visível dentro da garrafa quando pressionada e solta.',
					'_reflection' => 'A fumaça ajudou a formar núcleos de condensaç��o.',
				),
			),
			array(
				'title' => 'Previsão do Tempo - 24 horas',
				'author' => $aluno_ids[1],
				'status' => 'publish',
				'data' => array(
					'_turma' => $aluno_ids[1],
					'_prediction_date' => date( 'Y-m-d', strtotime( '+1 day' ) ),
					'_hypothesis' => 'Vai chover porque a pressão está baixa e há nuvens escuras.',
					'_evidence' => 'Pressão: -2.5mm (baixa), Nuvens: Ns (nimbostatus)',
					'_confrontation' => 'Choveu 5mm durante o dia. Hipótese correta!',
					'_reflection' => 'A pressão baixa realmente indica instabilidade.',
				),
			),
		);
		
		foreach ( $activities as $activity ) {
			$activity_id = wp_insert_post( array(
				'post_type' => 'remc_atividade',
				'post_title' => $activity['title'],
				'post_author' => $activity['author'],
				'post_status' => $activity['status'],
			) );
			
			foreach ( $activity['data'] as $key => $value ) {
				update_post_meta( $activity_id, $key, $value );
			}
			
			WP_CLI::line( "Atividade criada: {$activity['title']}" );
		}
	}
}

// Register bootstrap
$remc_bootstrap = new Remc_Bootstrap();
$remc_bootstrap->register();
