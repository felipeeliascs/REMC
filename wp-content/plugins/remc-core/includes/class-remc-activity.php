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

		// Sincronizacao: sai do feed quando deixa de estar aprovada.
		add_action( 'transition_post_status', array( $this, 'sync_on_status_change' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'sync_on_delete' ) );

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
	 * Monta o texto publico de uma observacao aprovada.
	 * Retorna '' quando nao ha nenhuma variavel efetivamente observada.
	 */
	public static function build_public_content( $obs_id ) {
		$obs = get_post( $obs_id );
		if ( ! $obs || 'remc_observacao' !== $obs->post_type ) {
			return '';
		}

		$m     = get_post_meta( $obs_id );
		$lines = array();

		$precip = self::meta( $m, '_precipitation' );
		if ( null !== $precip ) {
			$line = sprintf( __( 'Precipitação: %s mm', 'remc-core' ), self::num( $precip ) );
			$ini  = self::meta( $m, '_precipitation_start' );
			$fim  = self::meta( $m, '_precipitation_end' );
			if ( $ini && $fim ) {
				$line .= ' (' . self::fmt_dt( $ini ) . ' → ' . self::fmt_dt( $fim ) . ')';
			}
			$lines[] = $line;
		}

		$temp = self::meta( $m, '_temperature_air' );
		if ( null !== $temp ) {
			$lines[] = sprintf( __( 'Temperatura do ar: %s °C', 'remc-core' ), self::num( $temp ) );
		}

		$tmin = self::meta( $m, '_temperature_min' );
		$tmax = self::meta( $m, '_temperature_max' );
		if ( null !== $tmin ) {
			$lines[] = sprintf( __( 'Temperatura mínima: %s °C', 'remc-core' ), self::num( $tmin ) );
		}
		if ( null !== $tmax ) {
			$lines[] = sprintf( __( 'Temperatura máxima: %s °C', 'remc-core' ), self::num( $tmax ) );
		}
		if ( null !== $tmin && null !== $tmax && (float) $tmax >= (float) $tmin ) {
			$lines[] = sprintf( __( 'Amplitude observada: %s °C', 'remc-core' ), self::num( (float) $tmax - (float) $tmin ) );
		}

		$rpm       = self::meta( $m, '_anemometer_rpm' );
		$voltas    = self::meta( $m, '_anemometer_rotations' );
		$segundos  = self::meta( $m, '_anemometer_seconds' );
		if ( null !== $rpm ) {
			$line = sprintf( __( 'Rotação do anemômetro: %s RPM', 'remc-core' ), self::num( $rpm ) );
			if ( null !== $voltas && null !== $segundos ) {
				$line .= ' (' . (int) $voltas . ' ' . __( 'voltas em', 'remc-core' ) . ' ' . (int) $segundos . ' s)';
			}
			$lines[] = $line;
		}

		$baro = self::meta( $m, '_barometer_displacement' );
		if ( null !== $baro ) {
			$lines[] = sprintf( __( 'Deslocamento do ponteiro do barômetro: %s mm', 'remc-core' ), self::num( $baro ) );
		}

		$dir = self::meta( $m, '_wind_direction' );
		if ( $dir ) {
			$lines[] = sprintf( __( 'Vento (de onde vem): %s', 'remc-core' ), self::wind_direction_label( $dir ) );
		}
		$intens = self::meta( $m, '_wind_intensity' );
		if ( $intens ) {
			$lines[] = sprintf( __( 'Intensidade do vento: %s', 'remc-core' ), $intens );
		}

		$cover = self::meta( $m, '_cloud_cover' );
		if ( $cover ) {
			$lines[] = sprintf( __( 'Cobertura de nuvens: %s', 'remc-core' ), self::cloud_cover_label( $cover ) );
		}
		$genera = self::meta( $m, '_cloud_genera' );
		if ( ! empty( $genera ) ) {
			$genera  = is_array( $genera ) ? $genera : explode( ',', $genera );
			$lines[] = sprintf( __( 'Gêneros de nuvens: %s', 'remc-core' ), implode( ', ', array_map( 'sanitize_text_field', $genera ) ) );
		}
		$sky = self::meta( $m, '_sky_condition' );
		if ( $sky ) {
			$lines[] = sprintf( __( 'Condição do céu: %s', 'remc-core' ), $sky );
		}

		if ( empty( $lines ) ) {
			return '';
		}

		$local  = self::public_local_label( self::meta( $m, '_local_id' ) );
		$metodo = self::meta( $m, '_metodo' );
		$quando = self::meta( $m, '_observation_date' );

		$content  = '<p><strong>' . esc_html__( 'Dados meteorológicos observados', 'remc-core' ) . '</strong></p>';
		$content .= '<ul>';
		foreach ( $lines as $line ) {
			$content .= '<li>' . esc_html( $line ) . '</li>';
		}
		$content .= '</ul>';
		$content .= '<p>' . esc_html( sprintf( __( 'Local: %s', 'remc-core' ), $local ) ) . '<br>';
		if ( $quando ) {
			$content .= esc_html( sprintf( __( 'Observado em: %s', 'remc-core' ), self::fmt_dt( $quando ) ) ) . '<br>';
		}
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

	private function delete_activity( $obs_id ) {		$activity_id = (int) get_post_meta( $obs_id, self::META_ACTIVITY, true );
		if ( $activity_id && function_exists( 'bp_activity_delete_by_item_id' ) ) {
			bp_activity_delete_by_item_id( array(
				'item_id'   => $obs_id,
				'component' => self::COMPONENT,
				'type'      => self::TYPE,
			) );
		}
		delete_post_meta( $obs_id, self::META_ACTIVITY );
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
