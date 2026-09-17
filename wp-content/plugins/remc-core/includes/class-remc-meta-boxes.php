<?php
/**
 * REMC Core - Meta boxes (formulario das observacoes e das atividades).
 *
 * O formulario de observacao contem os campos estruturados das variaveis
 * meteorologicas, validados por Remc_Validation. O painel de "Campos
 * Personalizados" foi removido dos CPTs para evitar chaves arbitrarias.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Meta_Boxes {
	private static $instance;

	/** Evita recursao ao sincronizar post_status. */
	private static $saving = false;

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

	/* ------------------------------------------------------------------ */
	/* Auxiliares                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Turmas em que o usuario atual pode registrar dados.
	 *
	 * @return object[] Objetos de grupo do BuddyPress.
	 */
	private function allowed_turmas() {
		if ( ! function_exists( 'groups_get_groups' ) ) {
			return array();
		}

		$user_id = get_current_user_id();

		if ( current_user_can( 'manage_options' ) ) {
			$grupos = groups_get_groups( array(
				'show_hidden' => true,
				'per_page'    => false,
			) );
			return ! empty( $grupos['groups'] ) ? $grupos['groups'] : array();
		}

		$roles = (array) wp_get_current_user()->roles;

		if ( in_array( 'professor', $roles, true ) ) {
			$ids = Remc_Roles_Capabilities::instance()->managed_turmas( $user_id );
		} else {
			$ids = array_map( 'intval', (array) get_user_meta( $user_id, '_linked_turmas', true ) );
		}

		$out = array();
		foreach ( array_unique( $ids ) as $id ) {
			if ( ! $id ) {
				continue;
			}
			$g = groups_get_group( array( 'group_id' => $id ) );
			if ( ! empty( $g->id ) ) {
				$out[] = $g;
			}
		}

		return $out;
	}

	private function locals_for_turmas( $turma_ids ) {
		$turma_ids = array_filter( array_map( 'intval', (array) $turma_ids ) );
		if ( empty( $turma_ids ) ) {
			return array();
		}
		return get_posts( array(
			'post_type'      => 'remc_local',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => '_turma', 'value' => $turma_ids, 'compare' => 'IN' ),
			),
		) );
	}

	/** O usuario atual pode revisar (aprovar/devolver) nesta turma? */
	private function can_review( $turma_id ) {
		$user_id = get_current_user_id();
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return Remc_Roles_Capabilities::instance()->user_manages_turma( $user_id, $turma_id );
	}

	private static function wind_directions() {
		return array(
			'N'            => __( 'N (norte)', 'remc-core' ),
			'NE'           => __( 'NE (nordeste)', 'remc-core' ),
			'L'            => __( 'L (leste)', 'remc-core' ),
			'SE'           => __( 'SE (sudeste)', 'remc-core' ),
			'S'            => __( 'S (sul)', 'remc-core' ),
			'SO'           => __( 'SO (sudoeste)', 'remc-core' ),
			'O'            => __( 'O (oeste)', 'remc-core' ),
			'NO'           => __( 'NO (noroeste)', 'remc-core' ),
			'calmaria'     => __( 'Calmaria', 'remc-core' ),
			'variavel'     => __( 'Variável', 'remc-core' ),
			'naoobservado' => __( 'Não observado', 'remc-core' ),
		);
	}

	private static function sky_conditions() {
		return array(
			'limpo'            => __( 'Céu limpo', 'remc-core' ),
			'poucas_nuvens'    => __( 'Poucas nuvens', 'remc-core' ),
			'parcialmente_nublado' => __( 'Parcialmente nublado', 'remc-core' ),
			'nublado'          => __( 'Nublado', 'remc-core' ),
			'encoberto'        => __( 'Encoberto', 'remc-core' ),
			'nao_observado'    => __( 'Não observado', 'remc-core' ),
		);
	}

	private static function wind_intensities() {
		return array(
			'calmo'       => __( 'Calmo (folhas paradas)', 'remc-core' ),
			'leve'        => __( 'Leve (folhas se movem)', 'remc-core' ),
			'moderado'    => __( 'Moderado (galhos se movem)', 'remc-core' ),
			'forte'       => __( 'Forte (galhos grandes se movem)', 'remc-core' ),
			'muito_forte' => __( 'Muito forte (dificulta caminhar)', 'remc-core' ),
		);
	}

	private static function methods() {
		return array(
			'instrumento_calibrado' => __( 'Instrumento calibrado', 'remc-core' ),
			'instrumento_artesanal' => __( 'Instrumento artesanal (sem calibração)', 'remc-core' ),
			'estimativa'            => __( 'Estimativa', 'remc-core' ),
			'observacao_visual'     => __( 'Observação visual', 'remc-core' ),
		);
	}

	private static function cloud_genera() {
		return array(
			'Ci' => 'Cirrus', 'Cc' => 'Cirrocumulus', 'Cs' => 'Cirrostratus',
			'Ac' => 'Altocumulus', 'As' => 'Altostratus', 'Ns' => 'Nimbostratus',
			'Sc' => 'Stratocumulus', 'St' => 'Stratus', 'Cu' => 'Cumulus',
			'Cb' => 'Cumulonimbus', 'naoidentificado' => __( 'Não identificado', 'remc-core' ),
		);
	}

	private function render_select( $name, $label, $options, $value, $include_blank = true ) {
		echo '<div class="form-group"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
		echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="form-select">';
		if ( $include_blank ) {
			echo '<option value="">' . esc_html__( '— não informado —', 'remc-core' ) . '</option>';
		}
		foreach ( $options as $key => $text ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $text )
			);
		}
		echo '</select></div>';
	}

	private function render_number( $name, $label, $value, $atts = array() ) {
		$atts = array_merge( array( 'step' => 'any', 'inputmode' => 'decimal' ), $atts );
		$extra = '';
		foreach ( $atts as $k => $v ) {
			$extra .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		echo '<div class="form-group"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
		echo '<input type="number" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="form-input"' . $extra . '>';
		echo '</div>';
	}

	private function meta_value( $post_id, $key ) {
		$v = get_post_meta( $post_id, $key, true );
		return is_array( $v ) ? $v : (string) $v;
	}

	/**
	 * A observacao ja possui alguma variavel observada?
	 */
	private function observation_has_variable( $post_id ) {
		$chaves = array(
			'_temperature_air', '_temperature_min', '_temperature_max',
			'_precipitation', '_anemometer_rotations', '_barometer_displacement',
			'_wind_direction', '_cloud_cover', '_cloud_genera',
		);
		foreach ( $chaves as $k ) {
			$v = get_post_meta( $post_id, $k, true );
			if ( is_array( $v ) ? ! empty( $v ) : ( '' !== $v && null !== $v ) ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Registro                                                            */
	/* ------------------------------------------------------------------ */

	public function register_meta_boxes() {
		add_meta_box(
			'remc_observation_details',
			__( 'Dados da observação', 'remc-core' ),
			array( $this, 'render_observation_details' ),
			'remc_observacao',
			'normal',
			'default'
		);

		add_meta_box(
			'remc_activity_details',
			__( 'Detalhes da atividade', 'remc-core' ),
			array( $this, 'render_activity_details' ),
			'remc_atividade',
			'normal',
			'default'
		);
	}

	/* ------------------------------------------------------------------ */
	/* Observacao                                                          */
	/* ------------------------------------------------------------------ */

	public function render_observation_details( $post ) {
		wp_nonce_field( 'remc_observation_nonce', 'remc_observation_nonce' );

		$turma_id   = (int) $this->meta_value( $post->ID, '_turma' );
		$local_id   = (int) $this->meta_value( $post->ID, '_local_id' );
		$status     = $this->meta_value( $post->ID, '_status' );
		$date       = $this->meta_value( $post->ID, '_observation_date' );
		$can_review = $this->can_review( $turma_id );

		$aviso = get_transient( 'remc_obs_aviso_' . $post->ID );
		if ( $aviso ) {
			delete_transient( 'remc_obs_aviso_' . $post->ID );
			echo '<div class="notice notice-error inline"><p>' . esc_html( $aviso ) . '</p></div>';
		}

		echo '<p class="description">' . esc_html__( 'Preencha apenas o que foi observado. É obrigatório registrar pelo menos uma variável. Campos vazios não viram zero.', 'remc-core' ) . '</p>';

		// --- Identificacao --------------------------------------------------
		echo '<h4>' . esc_html__( 'Identificação', 'remc-core' ) . '</h4>';

		printf(
			'<div class="form-group"><label for="remc_observation_date">%s</label><input type="datetime-local" id="remc_observation_date" name="remc_observation_date" value="%s" class="form-input" required></div>',
			esc_html__( 'Data e hora observadas', 'remc-core' ),
			esc_attr( $date )
		);

		echo '<div class="form-group"><label for="remc_observation_turma">' . esc_html__( 'Turma', 'remc-core' ) . '</label>';
		echo '<select id="remc_observation_turma" name="remc_observation_turma" class="form-select" required>';
		echo '<option value="">' . esc_html__( 'Selecionar turma', 'remc-core' ) . '</option>';
		foreach ( $this->allowed_turmas() as $turma ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $turma->id ), selected( $turma_id, (int) $turma->id, false ), esc_html( $turma->name ) );
		}
		echo '</select></div>';

		echo '<div class="form-group"><label for="remc_observation_local">' . esc_html__( 'Local de observação', 'remc-core' ) . '</label>';
		echo '<select id="remc_observation_local" name="remc_observation_local" class="form-select" required>';
		echo '<option value="">' . esc_html__( 'Selecionar local', 'remc-core' ) . '</option>';
		foreach ( $this->locals_for_turmas( wp_list_pluck( $this->allowed_turmas(), 'id' ) ) as $local ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $local->ID ), selected( $local_id, (int) $local->ID, false ), esc_html( $local->post_title ) );
		}
		echo '</select></div>';

		// Status de revisao: aluno submete; professor/admin revisa.
		$status_opts = array(
			'rascunho' => __( 'Rascunho', 'remc-core' ),
			'pendente' => __( 'Enviar para revisão', 'remc-core' ),
		);
		if ( $can_review ) {
			$status_opts['aprovado']  = __( 'Aprovado', 'remc-core' );
			$status_opts['devolvido'] = __( 'Devolvido (com orientação)', 'remc-core' );
		}
		$this->render_select( 'remc_observation_status', __( 'Situação da revisão', 'remc-core' ), $status_opts, $status, false );

		// --- Temperatura ----------------------------------------------------
		echo '<h4>' . esc_html__( 'Temperatura do ar', 'remc-core' ) . '</h4>';
		$this->render_number( 'remc_temperature_air', __( 'Temperatura (°C)', 'remc-core' ), $this->meta_value( $post->ID, '_temperature_air' ) );
		$this->render_number( 'remc_temperature_min', __( 'Temperatura mínima (°C)', 'remc-core' ), $this->meta_value( $post->ID, '_temperature_min' ) );
		$this->render_number( 'remc_temperature_max', __( 'Temperatura máxima (°C)', 'remc-core' ), $this->meta_value( $post->ID, '_temperature_max' ) );

		// --- Precipitacao ---------------------------------------------------
		echo '<h4>' . esc_html__( 'Precipitação', 'remc-core' ) . '</h4>';
		$this->render_number( 'remc_precipitation', __( 'Precipitação acumulada (mm) — use 0 quando mediu e não choveu', 'remc-core' ), $this->meta_value( $post->ID, '_precipitation' ), array( 'min' => '0' ) );
		printf(
			'<div class="form-group"><label for="remc_precipitation_start">%s</label><input type="datetime-local" id="remc_precipitation_start" name="remc_precipitation_start" value="%s" class="form-input"></div>',
			esc_html__( 'Início da acumulação', 'remc-core' ),
			esc_attr( $this->meta_value( $post->ID, '_precipitation_start' ) )
		);
		printf(
			'<div class="form-group"><label for="remc_precipitation_end">%s</label><input type="datetime-local" id="remc_precipitation_end" name="remc_precipitation_end" value="%s" class="form-input"></div>',
			esc_html__( 'Fim da acumulação', 'remc-core' ),
			esc_attr( $this->meta_value( $post->ID, '_precipitation_end' ) )
		);

		// --- Anemometro -----------------------------------------------------
		echo '<h4>' . esc_html__( 'Anemômetro de copos', 'remc-core' ) . '</h4>';
		echo '<p class="description">' . esc_html__( 'Registre rotações, não velocidade do vento. RPM = 60 × voltas ÷ segundos.', 'remc-core' ) . '</p>';
		$this->render_number( 'remc_anemometer_rotations', __( 'Voltas completas (≥ 0)', 'remc-core' ), $this->meta_value( $post->ID, '_anemometer_rotations' ), array( 'min' => '0', 'step' => '1' ) );
		$this->render_number( 'remc_anemometer_seconds', __( 'Duração da contagem (s > 0)', 'remc-core' ), $this->meta_value( $post->ID, '_anemometer_seconds' ), array( 'min' => '1', 'step' => '1' ) );
		printf(
			'<p><strong>%s</strong> %s</p>',
			esc_html__( 'RPM calculada:', 'remc-core' ),
			esc_html( $this->meta_value( $post->ID, '_anemometer_rpm' ) )
		);

		// --- Barometro ------------------------------------------------------
		echo '<h4>' . esc_html__( 'Barômetro de bexiga', 'remc-core' ) . '</h4>';
		echo '<p class="description">' . esc_html__( 'Milímetros de deslocamento do ponteiro — não é hPa nem mmHg. Valores negativos são válidos.', 'remc-core' ) . '</p>';
		printf(
			'<div class="form-group"><label for="remc_barometer_reference">%s</label><input type="text" id="remc_barometer_reference" name="remc_barometer_reference" value="%s" class="form-input"></div>',
			esc_html__( 'Referência / linha de base', 'remc-core' ),
			esc_attr( $this->meta_value( $post->ID, '_barometer_reference' ) )
		);
		$this->render_number( 'remc_barometer_displacement', __( 'Deslocamento do ponteiro (mm)', 'remc-core' ), $this->meta_value( $post->ID, '_barometer_displacement' ) );
		$this->render_select(
			'remc_barometer_orientation',
			__( 'Orientação da escala', 'remc-core' ),
			array(
				'direita_aumenta' => __( 'Ponteiro para a direita = aumento', 'remc-core' ),
				'esquerda_aumenta' => __( 'Ponteiro para a esquerda = aumento', 'remc-core' ),
			),
			$this->meta_value( $post->ID, '_barometer_orientation' )
		);

		// --- Vento ----------------------------------------------------------
		echo '<h4>' . esc_html__( 'Vento', 'remc-core' ) . '</h4>';
		$this->render_select( 'remc_wind_direction', __( 'Direção de ORIGEM do vento (de onde vem)', 'remc-core' ), self::wind_directions(), $this->meta_value( $post->ID, '_wind_direction' ) );
		$this->render_select( 'remc_wind_intensity', __( 'Intensidade (qualitativa)', 'remc-core' ), self::wind_intensities(), $this->meta_value( $post->ID, '_wind_intensity' ) );

		// --- Nuvens ---------------------------------------------------------
		echo '<h4>' . esc_html__( 'Céu e nuvens', 'remc-core' ) . '</h4>';
		$this->render_select( 'remc_sky_condition', __( 'Condição do céu', 'remc-core' ), self::sky_conditions(), $this->meta_value( $post->ID, '_sky_condition' ) );

		$cobertura = array(
			'0' => '0/8 (céu limpo)', '1' => '1/8', '2' => '2/8', '3' => '3/8', '4' => '4/8',
			'5' => '5/8', '6' => '6/8', '7' => '7/8', '8' => '8/8 (encoberto)',
			'naoobservado'   => __( 'Não observado', 'remc-core' ),
			'obscurecido'    => __( 'Céu obscurecido', 'remc-core' ),
			'indeterminavel' => __( 'Cobertura indeterminável', 'remc-core' ),
		);
		$this->render_select( 'remc_cloud_cover', __( 'Cobertura de nuvens (oitavos)', 'remc-core' ), $cobertura, $this->meta_value( $post->ID, '_cloud_cover' ) );

		$selecionados = (array) get_post_meta( $post->ID, '_cloud_genera', true );
		echo '<fieldset class="fieldset-group"><legend>' . esc_html__( 'Gêneros de nuvens (pode marcar vários)', 'remc-core' ) . '</legend>';
		foreach ( self::cloud_genera() as $sigla => $nome ) {
			printf(
				'<label style="display:inline-block;margin-right:1rem;"><input type="checkbox" name="remc_cloud_genera[]" value="%s"%s> %s</label>',
				esc_attr( $sigla ),
				checked( in_array( $sigla, $selecionados, true ), true, false ),
				esc_html( $sigla . ' — ' . $nome )
			);
		}
		echo '</fieldset>';

		// --- Metodo, instrumento e notas ------------------------------------
		echo '<h4>' . esc_html__( 'Método e notas', 'remc-core' ) . '</h4>';
		$this->render_select( 'remc_metodo', __( 'Método de obtenção', 'remc-core' ), self::methods(), $this->meta_value( $post->ID, '_metodo' ) );

		printf(
			'<div class="form-group"><label for="remc_instrumento_codigo">%s</label><input type="text" id="remc_instrumento_codigo" name="remc_instrumento_codigo" value="%s" class="form-input"></div>',
			esc_html__( 'Código do instrumento', 'remc-core' ),
			esc_attr( $this->meta_value( $post->ID, '_instrumento_codigo' ) )
		);
		printf(
			'<div class="form-group"><label for="remc_instrumento_versao">%s</label><input type="text" id="remc_instrumento_versao" name="remc_instrumento_versao" value="%s" class="form-input"></div>',
			esc_html__( 'Versão do protocolo', 'remc-core' ),
			esc_attr( $this->meta_value( $post->ID, '_instrumento_versao' ) )
		);
		printf(
			'<div class="form-group"><label for="remc_notes">%s</label><textarea id="remc_notes" name="remc_notes" rows="3" class="form-textarea">%s</textarea><p class="description">%s</p></div>',
			esc_html__( 'Notas (não vão para o feed público)', 'remc-core' ),
			esc_textarea( $this->meta_value( $post->ID, '_notes' ) ),
			esc_html__( 'Texto breve, sem HTML.', 'remc-core' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Atividade                                                           */
	/* ------------------------------------------------------------------ */

	public function render_activity_details( $post ) {
		wp_nonce_field( 'remc_activity_nonce', 'remc_activity_nonce' );

		$turma_id    = (int) $this->meta_value( $post->ID, '_turma' );
		$tutorial_id = (int) $this->meta_value( $post->ID, '_tutorial_id' );

		echo '<div class="form-group"><label for="remc_activity_turma">' . esc_html__( 'Turma', 'remc-core' ) . '</label>';
		echo '<select id="remc_activity_turma" name="remc_activity_turma" class="form-select" required>';
		echo '<option value="">' . esc_html__( 'Selecionar turma', 'remc-core' ) . '</option>';
		foreach ( $this->allowed_turmas() as $turma ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $turma->id ), selected( $turma_id, (int) $turma->id, false ), esc_html( $turma->name ) );
		}
		echo '</select></div>';

		echo '<div class="form-group"><label for="remc_activity_tutorial">' . esc_html__( 'Tutorial relacionado (opcional)', 'remc-core' ) . '</label>';
		echo '<select id="remc_activity_tutorial" name="remc_activity_tutorial" class="form-select">';
		echo '<option value="">' . esc_html__( 'Nenhum', 'remc-core' ) . '</option>';
		foreach ( get_posts( array( 'post_type' => 'remc_tutorial', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $t ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $t->ID ), selected( $tutorial_id, (int) $t->ID, false ), esc_html( $t->post_title ) );
		}
		echo '</select></div>';

		$campos = array(
			'remc_activity_hypothesis' => array( __( 'Hipótese (previsão fica congelada após envio)', 'remc-core' ), '_hypothesis' ),
			'remc_activity_procedure'  => array( __( 'Procedimento', 'remc-core' ), '_procedure' ),
			'remc_activity_results'    => array( __( 'Resultados', 'remc-core' ), '_results' ),
			'remc_activity_reflection' => array( __( 'Reflexão', 'remc-core' ), '_reflection' ),
		);

		foreach ( $campos as $name => $def ) {
			printf(
				'<div class="form-group"><label for="%s">%s</label><textarea id="%s" name="%s" rows="4" class="form-textarea">%s</textarea></div>',
				esc_attr( $name ),
				esc_html( $def[0] ),
				esc_attr( $name ),
				esc_attr( $name ),
				esc_textarea( $this->meta_value( $post->ID, $def[1] ) )
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Salvamento                                                          */
	/* ------------------------------------------------------------------ */

	public function save_meta_boxes( $post_id, $post ) {
		if ( self::$saving ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'remc_observacao' === $post->post_type ) {
			$this->save_observation( $post_id, $post );
		} elseif ( 'remc_atividade' === $post->post_type ) {
			$this->save_activity( $post_id, $post );
		}
	}

	private function save_observation( $post_id, $post ) {
		if ( ! isset( $_POST['remc_observation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['remc_observation_nonce'] ) ), 'remc_observation_nonce' ) ) {
			return;
		}

		$erros   = array();
		$salvou_variavel = false;

		// Identificacao.
		if ( isset( $_POST['remc_observation_date'] ) ) {
			update_post_meta( $post_id, '_observation_date', sanitize_text_field( wp_unslash( $_POST['remc_observation_date'] ) ) );
		}
		if ( isset( $_POST['remc_observation_turma'] ) ) {
			update_post_meta( $post_id, '_turma', (int) $_POST['remc_observation_turma'] );
		}
		if ( isset( $_POST['remc_observation_local'] ) ) {
			update_post_meta( $post_id, '_local_id', (int) $_POST['remc_observation_local'] );
		}

		// Temperatura.
		foreach ( array( 'remc_temperature_air' => '_temperature_air', 'remc_temperature_min' => '_temperature_min', 'remc_temperature_max' => '_temperature_max' ) as $field => $meta ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $field ] );
			if ( '' === $raw ) {
				delete_post_meta( $post_id, $meta );
				continue;
			}
			$res = Remc_Validation::validate_temperature( $raw );
			if ( $res['valid'] ) {
				update_post_meta( $post_id, $meta, $res['value'] );
				$salvou_variavel = true;
			} else {
				$erros[] = $res['error'];
			}
		}

		// Precipitacao.
		if ( isset( $_POST['remc_precipitation'] ) ) {
			$raw = wp_unslash( $_POST['remc_precipitation'] );
			if ( '' === $raw ) {
				delete_post_meta( $post_id, '_precipitation' );
			} else {
				$res = Remc_Validation::validate_precipitation( $raw );
				if ( $res['valid'] ) {
					update_post_meta( $post_id, '_precipitation', $res['value'] );
					$salvou_variavel = true;
				} else {
					$erros[] = $res['error'];
				}
			}
		}
		foreach ( array( 'remc_precipitation_start' => '_precipitation_start', 'remc_precipitation_end' => '_precipitation_end' ) as $field => $meta ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
		$ini = get_post_meta( $post_id, '_precipitation_start', true );
		$fim = get_post_meta( $post_id, '_precipitation_end', true );
		if ( $ini && $fim ) {
			$res = Remc_Validation::validate_datetime_range( $ini, $fim );
			if ( ! $res['valid'] ) {
				$erros[] = $res['error'];
			}
		}

		// Anemometro.
		$voltas = isset( $_POST['remc_anemometer_rotations'] ) ? wp_unslash( $_POST['remc_anemometer_rotations'] ) : '';
		$seg    = isset( $_POST['remc_anemometer_seconds'] ) ? wp_unslash( $_POST['remc_anemometer_seconds'] ) : '';
		if ( '' !== $voltas || '' !== $seg ) {
			$res = Remc_Validation::validate_anemometer( $voltas, $seg );
			if ( $res['valid'] && null !== $res['value'] ) {
				update_post_meta( $post_id, '_anemometer_rotations', $res['value']['rotations'] );
				update_post_meta( $post_id, '_anemometer_seconds', $res['value']['seconds'] );
				update_post_meta( $post_id, '_anemometer_rpm', $res['value']['rpm'] );
				$salvou_variavel = true;
			} else {
				$erros[] = $res['error'];
			}
		}

		// Barometro.
		if ( isset( $_POST['remc_barometer_displacement'] ) ) {
			$raw = wp_unslash( $_POST['remc_barometer_displacement'] );
			if ( '' === $raw ) {
				delete_post_meta( $post_id, '_barometer_displacement' );
			} else {
				$res = Remc_Validation::validate_barometer( $raw );
				if ( $res['valid'] ) {
					update_post_meta( $post_id, '_barometer_displacement', $res['value'] );
					$salvou_variavel = true;
				} else {
					$erros[] = $res['error'];
				}
			}
		}
		foreach ( array( 'remc_barometer_reference' => '_barometer_reference', 'remc_barometer_orientation' => '_barometer_orientation' ) as $field => $meta ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		// Vento.
		if ( isset( $_POST['remc_wind_direction'] ) ) {
			$raw = wp_unslash( $_POST['remc_wind_direction'] );
			$res = Remc_Validation::validate_wind_direction( $raw );
			if ( $res['valid'] ) {
				if ( null === $res['value'] ) {
					delete_post_meta( $post_id, '_wind_direction' );
				} else {
					update_post_meta( $post_id, '_wind_direction', $res['value'] );
					$salvou_variavel = true;
				}
			} else {
				$erros[] = $res['error'];
			}
		}
		if ( isset( $_POST['remc_wind_intensity'] ) ) {
			update_post_meta( $post_id, '_wind_intensity', sanitize_text_field( wp_unslash( $_POST['remc_wind_intensity'] ) ) );
		}

		// Ceu e nuvens.
		if ( isset( $_POST['remc_sky_condition'] ) ) {
			update_post_meta( $post_id, '_sky_condition', sanitize_text_field( wp_unslash( $_POST['remc_sky_condition'] ) ) );
		}
		if ( isset( $_POST['remc_cloud_cover'] ) ) {
			$res = Remc_Validation::validate_cloud_cover( wp_unslash( $_POST['remc_cloud_cover'] ) );
			if ( $res['valid'] ) {
				if ( null === $res['value'] ) {
					delete_post_meta( $post_id, '_cloud_cover' );
				} else {
					update_post_meta( $post_id, '_cloud_cover', $res['value'] );
					$salvou_variavel = true;
				}
			} else {
				$erros[] = $res['error'];
			}
		}
		$genera = isset( $_POST['remc_cloud_genera'] ) ? (array) wp_unslash( $_POST['remc_cloud_genera'] ) : array();
		$res    = Remc_Validation::validate_cloud_genera( $genera );
		if ( $res['valid'] && ! empty( $res['value'] ) ) {
			update_post_meta( $post_id, '_cloud_genera', $res['value'] );
			$salvou_variavel = true;
		} else {
			delete_post_meta( $post_id, '_cloud_genera' );
		}

		// Metodo, instrumento e notas.
		foreach ( array(
			'remc_metodo'             => '_metodo',
			'remc_instrumento_codigo' => '_instrumento_codigo',
			'remc_instrumento_versao' => '_instrumento_versao',
		) as $field => $meta ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
		if ( isset( $_POST['remc_notes'] ) ) {
			update_post_meta( $post_id, '_notes', Remc_Validation::sanitize_text( wp_unslash( $_POST['remc_notes'] ) ) );
		}

		// Pelo menos uma variavel efetivamente observada (considera o que ja
		// esta salvo, para que a revisao docente nao exija redigitar tudo).
		if ( ! $salvou_variavel && ! $this->observation_has_variable( $post_id ) ) {
			$erros[] = __( 'Registre pelo menos uma variável observada.', 'remc-core' );
		}

		// Situacao da revisao (aluno nao aprova a si mesmo).
		$novo_status = isset( $_POST['remc_observation_status'] ) ? sanitize_key( wp_unslash( $_POST['remc_observation_status'] ) ) : '';
		$turma       = (int) get_post_meta( $post_id, '_turma', true );
		$permitidos  = array( 'rascunho', 'pendente' );
		if ( $this->can_review( $turma ) ) {
			$permitidos[] = 'aprovado';
			$permitidos[] = 'devolvido';
		}
		if ( $novo_status && ! in_array( $novo_status, $permitidos, true ) ) {
			$novo_status = 'pendente';
			$erros[]     = __( 'Somente a revisão docente pode aprovar ou devolver.', 'remc-core' );
		}

		if ( ! empty( $erros ) ) {
			$novo_status = 'rascunho';
			set_transient( 'remc_obs_aviso_' . $post_id, implode( ' ', array_unique( $erros ) ), 60 );
		}

		if ( $novo_status ) {
			update_post_meta( $post_id, '_status', $novo_status );

			$mapa = array(
				'rascunho'  => 'draft',
				'pendente'  => 'pending',
				'aprovado'  => 'publish',
				'devolvido' => 'devolvido',
			);
			$novo_post_status = $mapa[ $novo_status ];
			if ( $novo_post_status !== $post->post_status ) {
				self::$saving = true;
				wp_update_post( array( 'ID' => $post_id, 'post_status' => $novo_post_status ) );
				self::$saving = false;
			}
		}
	}

	private function save_activity( $post_id, $post ) {
		if ( ! isset( $_POST['remc_activity_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['remc_activity_nonce'] ) ), 'remc_activity_nonce' ) ) {
			return;
		}

		if ( isset( $_POST['remc_activity_turma'] ) ) {
			update_post_meta( $post_id, '_turma', (int) $_POST['remc_activity_turma'] );
		}
		if ( isset( $_POST['remc_activity_tutorial'] ) ) {
			update_post_meta( $post_id, '_tutorial_id', (int) $_POST['remc_activity_tutorial'] );
		}

		$campos = array(
			'remc_activity_hypothesis' => '_hypothesis',
			'remc_activity_procedure'  => '_procedure',
			'remc_activity_results'    => '_results',
			'remc_activity_reflection' => '_reflection',
		);
		foreach ( $campos as $field => $meta ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta, wp_kses_post( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
	}
}
