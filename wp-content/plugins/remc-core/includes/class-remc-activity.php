<?php
/**
 * REMC Core - Camada social (compartilhamento de dados meteorologicos)
 *
 * Regras:
 * - Apenas a autora ou o autor (aluno) pode compartilhar, manualmente (opt-in).
 * - Apenas observacoes APROVADAS podem ser compartilhadas.
 * - Rascunhos, pendentes e devolvidas NUNCA aparecem no feed.
 * - Reabrir/devolver/apagar a observacao remove o item do feed.
 * - O feed publico exibe apenas dados estruturados (sem notas, sem e-mail,
 *   sem nome completo, sem endereco residencial).
 * - Comentar/curtir apenas para membros da turma; visitante le.
 *
 * Requer o componente "activity" do BuddyPress. Degrada silenciosamente se o
 * BuddyPress estiver ausente ou com o componente desativado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Activity {

	const COMPONENT     = 'remc';
	const TYPE          = 'remc_shared_observation';
	const META_ACTIVITY = '_shared_activity_id';

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Registro da acao do BuddyPress.
		add_action( 'bp_init', array( $this, 'register_action' ) );

		// Opt-in / opt-out (POST autenticado, nonce + autorizacao por objeto).
		add_action( 'admin_post_remc_share_observation', array( $this, 'handle_share' ) );
		add_action( 'admin_post_remc_unshare_observation', array( $this, 'handle_unshare' ) );

		// Mesma operacao via AJAX (melhora a experiencia sem recarregar a pagina).
		add_action( 'wp_ajax_remc_toggle_share', array( $this, 'ajax_toggle_share' ) );

		// Sincronizacao: sai do feed quando deixa de estar aprovada.
		add_action( 'transition_post_status', array( $this, 'sync_on_status_change' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'sync_on_delete' ) );
		// Rede de seguranca: mudancas de estado sem transicao de post_status.
		add_action( 'save_post_remc_observacao', array( $this, 'reconcile_on_save' ), 20, 2 );

		// Interface de compartilhamento (meta box no editor da observacao).
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );

		// Interacoes restritas a turma (visitante apenas le).
		add_filter( 'bp_activity_can_comment', array( $this, 'filter_can_interact' ), 10, 2 );
		add_filter( 'bp_activity_can_favorite', array( $this, 'filter_can_interact' ), 10, 2 );

		// Limita o diretorio publico aos itens da REMC.
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'filter_public_directory' ), 10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Registro da acao no BuddyPress                                      */
	/* ------------------------------------------------------------------ */

	public function register_action() {
		if ( ! function_exists( 'bp_activity_set_action' ) ) {
			return;
		}

		bp_activity_set_action(
			self::COMPONENT,
			self::TYPE,
			__( 'compartilhou dados meteorológicos observados', 'remc-core' ),
			'remc_activity_format_action',
			__( 'Dados meteorológicos', 'remc-core' ),
			array( 'activity' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Conteudo publico (sem dados sensiveis)                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Rotulo publico do local (sem endereco residencial).
	 */
	private static function public_local_label( $local_id ) {
		$local = $local_id ? get_post( $local_id ) : null;
		if ( ! $local ) {
			return __( 'Local não informado', 'remc-core' );
		}

		$tipo = get_post_meta( $local->ID, '_tipo', true );
		if ( 'casa' === $tipo ) {
			return __( 'Ponto doméstico', 'remc-core' );
		}

		return $local->post_title;
	}

	private static function num( $value ) {
		if ( '' === $value || null === $value ) {
			return null;
		}
		$value = (float) $value;
		$text  = rtrim( rtrim( number_format( $value, 2, ',', '.' ), '0' ), ',' );
		return $text;
	}

	/**
	 * Fatos publicos de uma observacao (fonte unica de rotulos e valores).
	 *
	 * @return array<int,array{icon:string,label:string,value:string}>
	 */
	public static function public_facts( $obs_id ) {
		$obs = get_post( $obs_id );
		if ( ! $obs || 'remc_observacao' !== $obs->post_type ) {
			return array();
		}

		$m     = get_post_meta( $obs_id );
		$facts = array();

		$precip = self::meta( $m, '_precipitation' );
		if ( null !== $precip ) {
			$value = self::num( $precip ) . ' mm';
			$ini   = self::meta( $m, '_precipitation_start' );
			$fim   = self::meta( $m, '_precipitation_end' );
			if ( $ini && $fim ) {
				$value .= ' (' . self::fmt_dt( $ini ) . ' → ' . self::fmt_dt( $fim ) . ')';
			}
			$facts[] = array( 'icon' => '🌧️', 'label' => __( 'Precipitação', 'remc-core' ), 'value' => $value );
		}

		$temp = self::meta( $m, '_temperature_air' );
		if ( null !== $temp ) {
			$facts[] = array( 'icon' => '🌡️', 'label' => __( 'Temperatura do ar', 'remc-core' ), 'value' => self::num( $temp ) . ' °C' );
		}

		$tmin = self::meta( $m, '_temperature_min' );
		$tmax = self::meta( $m, '_temperature_max' );
		if ( null !== $tmin ) {
			$facts[] = array( 'icon' => '🔽', 'label' => __( 'Temperatura mínima', 'remc-core' ), 'value' => self::num( $tmin ) . ' °C' );
		}
		if ( null !== $tmax ) {
			$facts[] = array( 'icon' => '🔼', 'label' => __( 'Temperatura máxima', 'remc-core' ), 'value' => self::num( $tmax ) . ' °C' );
		}
		if ( null !== $tmin && null !== $tmax && (float) $tmax >= (float) $tmin ) {
			$facts[] = array( 'icon' => '📊', 'label' => __( 'Amplitude observada', 'remc-core' ), 'value' => self::num( (float) $tmax - (float) $tmin ) . ' °C' );
		}

		$rpm      = self::meta( $m, '_anemometer_rpm' );
		$voltas   = self::meta( $m, '_anemometer_rotations' );
		$segundos = self::meta( $m, '_anemometer_seconds' );
		if ( null !== $rpm ) {
			$value = self::num( $rpm ) . ' RPM';
			if ( null !== $voltas && null !== $segundos ) {
				$value .= ' (' . (int) $voltas . ' ' . __( 'voltas em', 'remc-core' ) . ' ' . (int) $segundos . ' s)';
			}
			$facts[] = array( 'icon' => '💨', 'label' => __( 'Rotação do anemômetro', 'remc-core' ), 'value' => $value );
		}

		$baro = self::meta( $m, '_barometer_displacement' );
		if ( null !== $baro ) {
			$facts[] = array( 'icon' => '📈', 'label' => __( 'Deslocamento do barômetro', 'remc-core' ), 'value' => self::num( $baro ) . ' mm' );
		}

		$dir = self::meta( $m, '_wind_direction' );
		if ( $dir ) {
			$facts[] = array( 'icon' => '🧭', 'label' => __( 'Vento (de onde vem)', 'remc-core' ), 'value' => self::wind_direction_label( $dir ) );
		}

		$intens = self::meta( $m, '_wind_intensity' );
		if ( $intens ) {
			$facts[] = array( 'icon' => '🌬️', 'label' => __( 'Intensidade do vento', 'remc-core' ), 'value' => $intens );
		}

		$cover = self::meta( $m, '_cloud_cover' );
		if ( $cover ) {
			$facts[] = array( 'icon' => '☁️', 'label' => __( 'Cobertura de nuvens', 'remc-core' ), 'value' => self::cloud_cover_label( $cover ) );
		}

		$genera = self::meta( $m, '_cloud_genera' );
		if ( ! empty( $genera ) ) {
			$genera  = is_array( $genera ) ? $genera : explode( ',', $genera );
			$facts[] = array(
				'icon'  => '🌥️',
				'label' => __( 'Gêneros de nuvens', 'remc-core' ),
				'value' => implode( ', ', array_map( 'sanitize_text_field', $genera ) ),
			);
		}

		$sky = self::meta( $m, '_sky_condition' );
		if ( $sky ) {
			$facts[] = array( 'icon' => '🌤️', 'label' => __( 'Condição do céu', 'remc-core' ), 'value' => $sky );
		}

		return $facts;
	}

	/**
	 * Monta o texto publico de uma observacao aprovada.
	 * Retorna '' quando nao ha nenhuma variavel efetivamente observada.
	 */
	public static function build_public_content( $obs_id ) {
		$obs = get_post( $obs_id );
		if ( ! $obs || 'remc_observacao' !== $obs->post_type ) {
			return '';
		}

		$facts = self::public_facts( $obs_id );
		if ( empty( $facts ) ) {
			return '';
		}

		$m      = get_post_meta( $obs_id );
		$content  = '<p><strong>' . esc_html__( 'Dados meteorológicos observados', 'remc-core' ) . '</strong></p><ul>';
		foreach ( $facts as $f ) {
			$content .= '<li>' . esc_html( $f['label'] . ': ' . $f['value'] ) . '</li>';
		}
		$content .= '</ul>';

		$content .= '<p>' . esc_html( sprintf( __( 'Local: %s', 'remc-core' ), self::public_local_label( self::meta( $m, '_local_id' ) ) ) ) . '<br>';

		$quando = self::meta( $m, '_observation_date' );
		if ( $quando ) {
			$content .= esc_html( sprintf( __( 'Observado em: %s', 'remc-core' ), self::fmt_dt( $quando ) ) ) . '<br>';
		}

		$metodo = self::meta( $m, '_metodo' );
		if ( $metodo ) {
			$content .= esc_html( sprintf( __( 'Método: %s', 'remc-core' ), self::method_label( $metodo ) ) );
		}
		$content .= '</p>';

		// Registro de demonstracao deve permanecer identificado.
		if ( false !== strpos( (string) $obs->post_title, 'FICTICIOS' ) || false !== strpos( (string) $obs->post_title, 'FICTÍCIOS' ) ) {
			$content .= '<p><em>' . esc_html__( 'DADOS FICTÍCIOS (demonstração)', 'remc-core' ) . '</em></p>';
		}

		return $content;
	}

	private static function meta( $meta, $key ) {
		if ( ! isset( $meta[ $key ][0] ) ) {
			return null;
		}
		$value = $meta[ $key ][0];
		if ( '' === $value ) {
			return null;
		}
		return $value;
	}

	private static function fmt_dt( $value ) {
		$ts = strtotime( (string) $value );
		if ( ! $ts ) {
			return (string) $value;
		}
		return wp_date( 'd/m/Y H:i', $ts );
	}

	private static function method_label( $metodo ) {
		$labels = array(
			'instrumento_calibrado'  => __( 'instrumento calibrado', 'remc-core' ),
			'instrumento_artesanal'  => __( 'instrumento artesanal (sem calibração)', 'remc-core' ),
			'estimativa'             => __( 'estimativa', 'remc-core' ),
			'observacao_visual'      => __( 'observação visual', 'remc-core' ),
		);
		return isset( $labels[ $metodo ] ) ? $labels[ $metodo ] : $metodo;
	}

	private static function wind_direction_label( $dir ) {
		$labels = array(
			'N' => 'N', 'NE' => 'NE', 'L' => 'L', 'SE' => 'SE',
			'S' => 'S', 'SO' => 'SO', 'O' => 'O', 'NO' => 'NO',
			'calmaria'      => __( 'calmaria', 'remc-core' ),
			'variavel'      => __( 'variável', 'remc-core' ),
			'naoobservado'  => __( 'não observado', 'remc-core' ),
		);
		return isset( $labels[ $dir ] ) ? $labels[ $dir ] : $dir;
	}

	private static function cloud_cover_label( $cover ) {
		if ( is_numeric( $cover ) ) {
			return $cover . '/8';
		}
		$labels = array(
			'naoobservado'   => __( 'não observado', 'remc-core' ),
			'obscurecido'    => __( 'céu obscurecido', 'remc-core' ),
			'indeterminavel' => __( 'cobertura indeterminável', 'remc-core' ),
		);
		return isset( $labels[ $cover ] ) ? $labels[ $cover ] : $cover;
	}

	/* ------------------------------------------------------------------ */
	/* Autorizacao                                                         */
	/* ------------------------------------------------------------------ */

	public static function user_is_turma_member( $user_id, $turma_id ) {
		if ( ! $user_id || ! $turma_id || ! function_exists( 'groups_is_user_member' ) ) {
			return false;
		}
		return (bool) groups_is_user_member( $user_id, (int) $turma_id );
	}

	/**
	 * A observacao esta em estado compartilhavel?
	 */
	public static function is_shareable( $obs_id, $user_id = 0 ) {
		$obs = get_post( $obs_id );
		if ( ! $obs || 'remc_observacao' !== $obs->post_type ) {
			return false;
		}
		if ( 'publish' !== $obs->post_status ) {
			return false;
		}
		if ( 'aprovado' !== get_post_meta( $obs_id, '_status', true ) ) {
			return false;
		}
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( (int) $obs->post_author !== (int) $user_id ) {
			return false;
		}
		$turma = (int) get_post_meta( $obs_id, '_turma', true );
		return self::user_is_turma_member( $user_id, $turma );
	}

	/* ------------------------------------------------------------------ */
	/* Compartilhar / descompartilhar                                      */
	/* ------------------------------------------------------------------ */

	public function handle_share() {
		$this->handle_toggle( true );
	}

	public function handle_unshare() {
		$this->handle_toggle( false );
	}

	private function handle_toggle( $share ) {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Acesso negado.', 'remc-core' ), 403 );
		}

		$obs_id = isset( $_REQUEST['observation'] ) ? (int) $_REQUEST['observation'] : 0;
		$nonce  = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		$action = $share ? 'remc_share_observation_' . $obs_id : 'remc_unshare_observation_' . $obs_id;

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'Nonce inválido.', 'remc-core' ), 403 );
		}

		if ( ! self::is_shareable( $obs_id ) ) {
			wp_die( esc_html__( 'Você só pode compartilhar suas próprias observações aprovadas.', 'remc-core' ), 403 );
		}

		if ( $share ) {
			$resultado = $this->create_activity( $obs_id ) ? 'shared' : 'error';
		} else {
			$this->delete_activity( $obs_id );
			$resultado = 'unshared';
		}

		$back = wp_get_referer();
		if ( ! $back ) {
			$back = function_exists( 'bp_get_activity_directory_url' )
				? bp_get_activity_directory_url()
				: home_url( '/activity/' );
		}
		$back = add_query_arg( 'remc_feed', $resultado, $back );

		nocache_headers();
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Cria o item de feed de uma observacao aprovada.
	 *
	 * Publico para uso interno (bootstrap de demonstracao). Os handlers HTTP
	 * passam por handle_toggle(), que aplica nonce e autorizacao por objeto.
	 *
	 * @return bool
	 */
	public function create_activity( $obs_id ) {
		if ( ! function_exists( 'bp_activity_add' ) ) {
			return false;
		}
		if ( get_post_meta( $obs_id, self::META_ACTIVITY, true ) ) {
			return true; // Ja compartilhado.
		}

		$content = self::build_public_content( $obs_id );
		if ( '' === $content ) {
			return false;
		}

		$user_id = get_current_user_id();
		$turma   = (int) get_post_meta( $obs_id, '_turma', true );

		$activity_id = bp_activity_add( array(
			'user_id'           => $user_id,
			'action'            => __( 'compartilhou dados meteorológicos observados', 'remc-core' ),
			'content'           => $content,
			'component'         => self::COMPONENT,
			'type'              => self::TYPE,
			'item_id'           => $obs_id,
			'secondary_item_id' => $turma,
			'hide_sitewide'     => 0,
			'is_spam'           => 0,
		) );

		if ( $activity_id ) {
			update_post_meta( $obs_id, self::META_ACTIVITY, (int) $activity_id );
			return true;
		}

		return false;
	}

	private function delete_activity( $obs_id ) {
		$activity_id = (int) get_post_meta( $obs_id, self::META_ACTIVITY, true );
		if ( $activity_id && function_exists( 'bp_activity_delete_by_item_id' ) ) {
			bp_activity_delete_by_item_id( array(
				'item_id'   => $obs_id,
				'component' => self::COMPONENT,
				'type'      => self::TYPE,
			) );
		}
		delete_post_meta( $obs_id, self::META_ACTIVITY );
	}

	/**
	 * Permalink de um item do feed, quando o BuddyPress oferece a funcao.
	 */
	public static function activity_permalink( $activity_id ) {
		if ( $activity_id && function_exists( 'bp_activity_get_permalink' ) ) {
			return bp_activity_get_permalink( (int) $activity_id );
		}
		return '';
	}

	/**
	 * Alterna o compartilhamento via AJAX, reutilizando a mesma logica do
	 * fluxo por formulario (is_shareable + create/delete).
	 */
	public function ajax_toggle_share() {
		check_ajax_referer( 'remc_toggle_share', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Acesso negado.', 'remc-core' ) ), 403 );
		}

		$obs_id = isset( $_POST['observation'] ) ? (int) $_POST['observation'] : 0;
		if ( ! $obs_id || ! self::is_shareable( $obs_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Você só pode compartilhar suas próprias observações aprovadas.', 'remc-core' ) ),
				403
			);
		}

		$estado  = isset( $_POST['state'] ) ? sanitize_key( wp_unslash( $_POST['state'] ) ) : '';
		$compartilhado = (int) get_post_meta( $obs_id, self::META_ACTIVITY, true );

		if ( 'unshare' === $estado ) {
			$this->delete_activity( $obs_id );
		} elseif ( 'share' === $estado ) {
			$this->create_activity( $obs_id );
		} elseif ( $compartilhado ) {
			$this->delete_activity( $obs_id );
		} else {
			$this->create_activity( $obs_id );
		}

		$activity_id = (int) get_post_meta( $obs_id, self::META_ACTIVITY, true );

		wp_send_json_success( array(
			'observation' => $obs_id,
			'shared'      => (bool) $activity_id,
			'permalink'   => $activity_id ? self::activity_permalink( $activity_id ) : '',
		) );
	}

	/* ------------------------------------------------------------------ */
	/* Sincronizacao com o ciclo de revisao                                */
	/* ------------------------------------------------------------------ */

	public function sync_on_status_change( $new_status, $old_status, $post ) {
		if ( ! $post || 'remc_observacao' !== $post->post_type ) {
			return;
		}
		if ( $new_status === $old_status ) {
			return;
		}

		$aprovado = ( 'publish' === $new_status && 'aprovado' === get_post_meta( $post->ID, '_status', true ) );
		if ( ! $aprovado ) {
			$this->delete_activity( $post->ID );
		}
	}

	public function sync_on_delete( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'remc_observacao' === $post->post_type ) {
			$this->delete_activity( $post_id );
		}
	}

	/**
	 * Garante que o feed nunca mantenha uma observacao que nao esta aprovada,
	 * mesmo quando o estado muda sem alterar o post_status.
	 */
	public function reconcile_on_save( $post_id, $post = null ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		$post = $post ? $post : get_post( $post_id );
		if ( ! $post || 'remc_observacao' !== $post->post_type ) {
			return;
		}

		$aprovado = ( 'publish' === $post->post_status && 'aprovado' === get_post_meta( $post_id, '_status', true ) );
		if ( ! $aprovado ) {
			$this->delete_activity( $post_id );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Interacoes                                                          */
	/* ------------------------------------------------------------------ */

	public function filter_can_interact( $can, $arg = null ) {
		if ( ! $can ) {
			return $can;
		}
		if ( ! function_exists( 'bp_activity_get_specific' ) ) {
			return $can;
		}

		static $cache = array();

		// Somente dentro do loop de atividades o item corrente existe.
		global $activities_template;
		$activity_id = 0;
		if ( isset( $activities_template->activity->id ) ) {
			$activity_id = (int) $activities_template->activity->id;
		}
		if ( ! $activity_id ) {
			return $can;
		}

		// A cache precisa considerar o usuario (autorizacao por objeto).
		$cache_key = $activity_id . ':' . (int) get_current_user_id();
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$activity = bp_activity_get_specific( array( 'activity_ids' => array( $activity_id ) ) );
		if ( empty( $activity['activities'][0] ) ) {
			$cache[ $cache_key ] = $can;
			return $can;
		}

		$item = $activity['activities'][0];
		if ( self::COMPONENT !== $item->component || self::TYPE !== $item->type ) {
			$cache[ $cache_key ] = $can;
			return $can;
		}

		$turma   = (int) get_post_meta( (int) $item->item_id, '_turma', true );
		$allowed = self::user_is_turma_member( get_current_user_id(), $turma );
		$cache[ $cache_key ] = $allowed;

		return $allowed;
	}

	/**
	 * No diretorio publico, mostra apenas os itens compartilhados da REMC.
	 *
	 * $where_conditions e um mapa chave => trecho SQL, unido por AND pelo BP.
	 */
	public function filter_public_directory( $where_conditions, $args = array() ) {
		if ( is_user_logged_in() ) {
			return $where_conditions; // Membros veem tambem o conteudo da turma.
		}
		$where_conditions['remc_public_component'] = "a.component = '" . esc_sql( self::COMPONENT ) . "'";
		return $where_conditions;
	}

	/* ------------------------------------------------------------------ */
	/* Interface (meta box)                                                */
	/* ------------------------------------------------------------------ */

	public function register_meta_box() {
		add_meta_box(
			'remc_activity_share',
			__( 'Feed REMC (compartilhamento)', 'remc-core' ),
			array( $this, 'render_meta_box' ),
			'remc_observacao',
			'side',
			'default'
		);
	}

	public function render_meta_box( $post ) {
		$status = get_post_meta( $post->ID, '_status', true );
		$shared = (int) get_post_meta( $post->ID, self::META_ACTIVITY, true );
		$link   = get_option( 'remc_pagina_painel_aluno' );

		if ( 'aprovado' !== $status || 'publish' !== $post->post_status ) {
			echo '<p>' . esc_html__( 'Somente observações aprovadas podem ser compartilhadas no feed.', 'remc-core' ) . '</p>';
			return;
		}

		if ( $shared ) {
			echo '<p><em>' . esc_html__( 'Esta observação está compartilhada no feed.', 'remc-core' ) . '</em></p>';
		}

		echo '<p>';
		if ( $link ) {
			printf(
				/* translators: %s: link para o painel do aluno */
				esc_html__( 'O compartilhamento é feito pela autora ou pelo autor no %s.', 'remc-core' ),
				'<a href="' . esc_url( $link ) . '">' . esc_html__( 'Painel do Aluno', 'remc-core' ) . '</a>'
			);
		} else {
			esc_html_e( 'O compartilhamento é feito pela autora ou pelo autor no Painel do Aluno.', 'remc-core' );
		}
		echo '</p>';
	}
}

/**
 * Formata a acao exibida no feed (usado como callback do bp_activity_set_action).
 */
if ( ! function_exists( 'remc_activity_format_action' ) ) {
	function remc_activity_format_action( $action, $activity ) {
		return __( 'compartilhou dados meteorológicos observados', 'remc-core' );
	}
}
