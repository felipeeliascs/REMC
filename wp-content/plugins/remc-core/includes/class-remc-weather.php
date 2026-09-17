<?php
/**
 * REMC Core - Servico de clima (Open-Meteo).
 *
 * Centraliza o acesso a API publica Open-Meteo (sem chave), com cache em
 * transients para evitar chamadas excessivas. Nenhum outro ponto do projeto
 * deve chamar a API diretamente.
 *
 * Documentacao: https://open-meteo.com/en/docs
 *
 * @package remc-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Weather {

	const REST_NAMESPACE = 'remc/v1';
	const REST_ROUTE     = '/weather';

	const GEO_TRANSIENT  = 'remc_weather_geo';
	const DATA_TRANSIENT = 'remc_weather_data';

	const GEO_TTL  = 2592000; // 30 dias.
	const DATA_TTL = 900;     // 15 minutos.

	const CIDADE = 'Cachoeira Paulista';
	const UF     = 'SP';
	const PAIS   = 'BR';

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/* ------------------------------------------------------------------ */
	/* API publica                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Dados de clima atuais e previsao curta.
	 *
	 * @param bool $forcar Ignora o cache.
	 * @return array|WP_Error
	 */
	public function get_weather( $forcar = false ) {
		if ( ! $forcar ) {
			$cache = get_transient( self::DATA_TRANSIENT );
			if ( is_array( $cache ) && ! empty( $cache['current'] ) ) {
				return $cache;
			}
		}

		$local = $this->get_place();
		if ( is_wp_error( $local ) ) {
			return $local;
		}

		$url = add_query_arg(
			array(
				'latitude'      => $local['latitude'],
				'longitude'     => $local['longitude'],
				'current'       => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m',
				'daily'         => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset',
				'timezone'      => 'America/Sao_Paulo',
				'forecast_days' => 4,
			),
			'https://api.open-meteo.com/v1/forecast'
		);

		$resposta = wp_remote_get( $url, array( 'timeout' => 8 ) );
		if ( is_wp_error( $resposta ) ) {
			return $resposta;
		}

		$codigo = wp_remote_retrieve_response_code( $resposta );
		$corpo  = json_decode( wp_remote_retrieve_body( $resposta ), true );

		if ( 200 !== (int) $codigo || empty( $corpo['current'] ) ) {
			return new WP_Error( 'remc_weather', __( 'Não foi possível carregar os dados meteorológicos agora.', 'remc-core' ) );
		}

		$dados = $this->normalizar( $local, $corpo );
		set_transient( self::DATA_TRANSIENT, $dados, self::DATA_TTL );

		return $dados;
	}

	/**
	 * Descricao e icone de um weather_code da OMM.
	 *
	 * @return array{label:string,icon:string}
	 */
	public static function code_info( $code ) {
		$mapa = array(
			0  => array( __( 'Céu limpo', 'remc-core' ), '☀️' ),
			1  => array( __( 'Predomínio de sol', 'remc-core' ), '🌤️' ),
			2  => array( __( 'Parcialmente nublado', 'remc-core' ), '⛅' ),
			3  => array( __( 'Nublado', 'remc-core' ), '☁️' ),
			45 => array( __( 'Nevoeiro', 'remc-core' ), '🌫️' ),
			48 => array( __( 'Nevoeiro com geada', 'remc-core' ), '🌫️' ),
			51 => array( __( 'Chuvisco leve', 'remc-core' ), '🌦️' ),
			53 => array( __( 'Chuvisco', 'remc-core' ), '🌦️' ),
			55 => array( __( 'Chuvisco forte', 'remc-core' ), '🌦️' ),
			56 => array( __( 'Chuvisco congelante', 'remc-core' ), '🌧️' ),
			57 => array( __( 'Chuvisco congelante forte', 'remc-core' ), '🌧️' ),
			61 => array( __( 'Chuva leve', 'remc-core' ), '🌧️' ),
			63 => array( __( 'Chuva', 'remc-core' ), '🌧️' ),
			65 => array( __( 'Chuva forte', 'remc-core' ), '🌧️' ),
			66 => array( __( 'Chuva congelante', 'remc-core' ), '🌧️' ),
			67 => array( __( 'Chuva congelante forte', 'remc-core' ), '🌧️' ),
			71 => array( __( 'Neve leve', 'remc-core' ), '🌨️' ),
			73 => array( __( 'Neve', 'remc-core' ), '🌨️' ),
			75 => array( __( 'Neve forte', 'remc-core' ), '🌨️' ),
			77 => array( __( 'Grãos de neve', 'remc-core' ), '🌨️' ),
			80 => array( __( 'Pancadas de chuva leves', 'remc-core' ), '🌦️' ),
			81 => array( __( 'Pancadas de chuva', 'remc-core' ), '🌦️' ),
			82 => array( __( 'Pancadas de chuva fortes', 'remc-core' ), '🌧️' ),
			85 => array( __( 'Pancadas de neve', 'remc-core' ), '🌨️' ),
			86 => array( __( 'Pancadas de neve fortes', 'remc-core' ), '🌨️' ),
			95 => array( __( 'Tempestade', 'remc-core' ), '⛈️' ),
			96 => array( __( 'Tempestade com granizo', 'remc-core' ), '⛈️' ),
			99 => array( __( 'Tempestade com granizo forte', 'remc-core' ), '⛈️' ),
		);

		$code = (int) $code;
		if ( isset( $mapa[ $code ] ) ) {
			return array( 'label' => $mapa[ $code ][0], 'icon' => $mapa[ $code ][1] );
		}

		return array( 'label' => __( 'Condição não informada', 'remc-core' ), 'icon' => '🌡️' );
	}

	/* ------------------------------------------------------------------ */
	/* Internos                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Coordenadas da cidade (geocoding Open-Meteo), com cache longo.
	 *
	 * @return array|WP_Error
	 */
	private function get_place() {
		$cache = get_transient( self::GEO_TRANSIENT );
		if ( is_array( $cache ) && ! empty( $cache['latitude'] ) ) {
			return $cache;
		}

		$url = add_query_arg(
			array(
				'name'        => self::CIDADE,
				'countryCode' => self::PAIS,
				'language'    => 'pt',
				'count'       => 1,
			),
			'https://geocoding-api.open-meteo.com/v1/search'
		);

		$resposta = wp_remote_get( $url, array( 'timeout' => 8 ) );
		if ( is_wp_error( $resposta ) ) {
			return $resposta;
		}

		$corpo = json_decode( wp_remote_retrieve_body( $resposta ), true );
		if ( empty( $corpo['results'][0]['latitude'] ) ) {
			return new WP_Error( 'remc_weather_geo', __( 'Não foi possível localizar a cidade.', 'remc-core' ) );
		}

		$r = $corpo['results'][0];
		$local = array(
			'name'        => $r['name'],
			'admin1'      => isset( $r['admin1'] ) ? $r['admin1'] : self::UF,
			'country'     => isset( $r['country'] ) ? $r['country'] : 'Brasil',
			'latitude'    => (float) $r['latitude'],
			'longitude'   => (float) $r['longitude'],
			'timezone'    => isset( $r['timezone'] ) ? $r['timezone'] : 'America/Sao_Paulo',
		);

		set_transient( self::GEO_TRANSIENT, $local, self::GEO_TTL );

		return $local;
	}

	/**
	 * Normaliza a resposta da API para o formato usado na interface.
	 */
	private function normalizar( $local, $corpo ) {
		$current = $corpo['current'];
		$info    = self::code_info( isset( $current['weather_code'] ) ? $current['weather_code'] : null );

		$dias = array();
		if ( ! empty( $corpo['daily']['time'] ) ) {
			$total = count( $corpo['daily']['time'] );
			for ( $i = 0; $i < $total; $i++ ) {
				$code = null; // A API nao devolve weather_code diario neste pedido.
				$dias[] = array(
					'data'   => $corpo['daily']['time'][ $i ],
					'max'    => isset( $corpo['daily']['temperature_2m_max'][ $i ] ) ? (float) $corpo['daily']['temperature_2m_max'][ $i ] : null,
					'min'    => isset( $corpo['daily']['temperature_2m_min'][ $i ] ) ? (float) $corpo['daily']['temperature_2m_min'][ $i ] : null,
					'chuva'  => isset( $corpo['daily']['precipitation_probability_max'][ $i ] ) ? (int) $corpo['daily']['precipitation_probability_max'][ $i ] : null,
					'nascer' => isset( $corpo['daily']['sunrise'][ $i ] ) ? $corpo['daily']['sunrise'][ $i ] : '',
					'por'    => isset( $corpo['daily']['sunset'][ $i ] ) ? $corpo['daily']['sunset'][ $i ] : '',
				);
			}
		}

		return array(
			'place'      => $local,
			'updated_at' => current_time( 'mysql' ),
			'current'    => array(
				'time'        => isset( $current['time'] ) ? $current['time'] : '',
				'temperature' => isset( $current['temperature_2m'] ) ? (float) $current['temperature_2m'] : null,
				'humidity'    => isset( $current['relative_humidity_2m'] ) ? (int) $current['relative_humidity_2m'] : null,
				'apparent'    => isset( $current['apparent_temperature'] ) ? (float) $current['apparent_temperature'] : null,
				'precipitation' => isset( $current['precipitation'] ) ? (float) $current['precipitation'] : null,
				'wind'        => isset( $current['wind_speed_10m'] ) ? (float) $current['wind_speed_10m'] : null,
				'code'        => isset( $current['weather_code'] ) ? (int) $current['weather_code'] : null,
				'label'       => $info['label'],
				'icon'        => $info['icon'],
			),
			'daily'      => $dias,
		);
	}

	/* ------------------------------------------------------------------ */
	/* REST                                                                */
	/* ------------------------------------------------------------------ */

	public function register_rest_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_weather' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'force' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	public function rest_get_weather( WP_REST_Request $request ) {
		$dados = $this->get_weather( (bool) $request->get_param( 'force' ) );

		if ( is_wp_error( $dados ) ) {
			return new WP_REST_Response(
				array(
					'error'   => true,
					'message' => $dados->get_error_message(),
				),
				200
			);
		}

		return rest_ensure_response( $dados );
	}
}
