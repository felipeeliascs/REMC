<?php
/**
 * REMC Core - Validation Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Validation {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Validation callbacks registered in post type meta boxes
	}

	/**
	 * Validate temperature values
	 */
	public static function validate_temperature( $value, $field = 'temperature_air' ) {
		if ( $value === '' || $value === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		// Accept comma as decimal separator (pt_BR)
		$value = str_replace( ',', '.', $value );

		if ( ! is_numeric( $value ) ) {
			return array( 'valid' => false, 'error' => __( 'Valor inválido para temperatura', 'remc-core' ) );
		}

		$value = (float) $value;

		// Plausibility check: -90°C to +60°C (realistic for school observations)
		if ( $value < -90 || $value > 60 ) {
			return array( 'valid' => false, 'error' => __( 'Temperatura fora da faixa plausível (-90°C a +60°C)', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => $value );
	}

	/**
	 * Validate precipitation (mm)
	 */
	public static function validate_precipitation( $value ) {
		if ( $value === '' || $value === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		// Accept comma as decimal separator (pt_BR)
		$value = str_replace( ',', '.', $value );

		if ( ! is_numeric( $value ) ) {
			return array( 'valid' => false, 'error' => __( 'Valor inválido para precipitação', 'remc-core' ) );
		}

		$value = (float) $value;

		if ( $value < 0 ) {
			return array( 'valid' => false, 'error' => __( 'Precipitação não pode ser negativa', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => $value );
	}

	/**
	 * Validate anemometer rotations (RPM calculation input)
	 */
	public static function validate_anemometer( $rotations, $seconds ) {
		if ( $rotations === '' || $rotations === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		$rotations = (int) $rotations;
		$seconds = (int) $seconds;

		if ( $rotations < 0 ) {
			return array( 'valid' => false, 'error' => __( 'Número de voltas não pode ser negativo', 'remc-core' ) );
		}

		if ( $seconds <= 0 ) {
			return array( 'valid' => false, 'error' => __( 'Duração da contagem deve ser maior que zero', 'remc-core' ) );
		}

		// Calculate RPM
		$rpm = round( 60 * $rotations / $seconds, 2 );

		return array( 'valid' => true, 'value' => array(
			'rotations' => $rotations,
			'seconds' => $seconds,
			'rpm' => $rpm
		) );
	}

	/**
	 * Validate barometer displacement (mm)
	 */
	public static function validate_barometer( $displacement ) {
		if ( $displacement === '' || $displacement === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		// Accept comma as decimal separator (pt_BR)
		$displacement = str_replace( ',', '.', $displacement );

		if ( ! is_numeric( $displacement ) ) {
			return array( 'valid' => false, 'error' => __( 'Valor inválido para deslocamento do barômetro', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => (float) $displacement );
	}

	/**
	 * Validate wind direction
	 */
	public static function validate_wind_direction( $direction ) {
		$valid = array( 'N', 'NE', 'L', 'SE', 'S', 'SO', 'O', 'NO', 'calmaria', 'variavel', 'naoobservado' );

		if ( $direction === '' || $direction === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		if ( ! in_array( $direction, $valid ) ) {
			return array( 'valid' => false, 'error' => __( 'Direção do vento inválida', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => $direction );
	}

	/**
	 * Validate cloud cover (octas: 0-8, plus special values)
	 */
	public static function validate_cloud_cover( $cover ) {
		$valid = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', 'naoobservado', 'obscurecido', 'indeterminavel' );

		if ( $cover === '' || $cover === null ) {
			return array( 'valid' => true, 'value' => null );
		}

		if ( ! in_array( $cover, $valid ) ) {
			return array( 'valid' => false, 'error' => __( 'Cobertura de nuvens inválida', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => $cover );
	}

	/**
	 * Validate multiple cloud genera
	 */
	public static function validate_cloud_genera( $genera ) {
		$valid_genera = array(
			'Ci' => 'Cirrus',
			'Cc' => 'Cirrocumulus',
			'Cs' => 'Cirrostratus',
			'Ac' => 'Altocumulus',
			'As' => 'Altostratus',
			'Ns' => 'Nimbostratus',
			'Sc' => 'Stratocumulus',
			'St' => 'Stratus',
			'Cu' => 'Cumulus',
			'Cb' => 'Cumulonimbus',
			'naoidentificado' => 'Não identificado'
		);

		if ( empty( $genera ) || $genera === null ) {
			return array( 'valid' => true, 'value' => array() );
		}

		if ( ! is_array( $genera ) ) {
			$genera = array( $genera );
		}

		$validated = array();
		foreach ( $genera as $g ) {
			if ( isset( $valid_genera[ $g ] ) ) {
				$validated[] = $g;
			}
		}

		return array( 'valid' => true, 'value' => $validated );
	}

	/**
	 * Validate date/time range
	 */
	public static function validate_datetime_range( $start, $end ) {
		if ( $start === '' || $end === '' ) {
			return array( 'valid' => true, 'value' => null );
		}

		$start_ts = strtotime( $start );
		$end_ts = strtotime( $end );

		if ( ! $start_ts || ! $end_ts ) {
			return array( 'valid' => false, 'error' => __( 'Datas inválidas', 'remc-core' ) );
		}

		if ( $start_ts > $end_ts ) {
			return array( 'valid' => false, 'error' => __( 'Início do período deve ser anterior ou igual ao fim', 'remc-core' ) );
		}

		return array( 'valid' => true, 'value' => array( 'start' => $start, 'end' => $end ) );
	}

	/**
	 * Check for duplicate observation
	 */
	public static function check_duplicate_observation( $local_id, $date, $variable ) {
		global $wpdb;

		// Check for existing observation in same location, date/time with same variable
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			WHERE p.post_type = 'remc_observacao'
			AND p.post_status IN ('pending', 'publish', 'draft')
			AND m.meta_key = '_local_id'
			AND m.meta_value = %d
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} m2
				WHERE m2.post_id = p.ID
				AND m2.meta_key = '_observation_date'
				AND m2.meta_value = %s
			)
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} m3
				WHERE m3.post_id = p.ID
				AND m3.meta_key = '_variable'
				AND m3.meta_value = %s
			)",
			$local_id,
			$date,
			$variable
		) );

		return (bool) $exists;
	}

	/**
	 * Sanitize text input
	 */
	public static function sanitize_text( $value, $max_length = 500 ) {
		$value = sanitize_text_field( $value );
		if ( strlen( $value ) > $max_length ) {
			$value = substr( $value, 0, $max_length );
		}
		return $value;
	}
}
