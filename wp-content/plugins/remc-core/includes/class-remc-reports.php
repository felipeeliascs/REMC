<?php
/**
 * REMC Core - Reports and Charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Reports {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_remc_get_chart_data', array( $this, 'ajax_get_chart_data' ) );
		add_action( 'wp_ajax_remc_export_csv', array( $this, 'ajax_export_csv' ) );
	}

	/**
	 * Get observations data for charts
	 */
	public function get_chart_data( $args ) {
		global $wpdb;

		$defaults = array(
			'turma_id' => 0,
			'local_id' => 0,
			'variable' => 'temperature',
			'start_date' => '',
			'end_date' => '',
			'status' => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = "p.post_type = 'remc_observacao' AND p.post_status IN ('publish', 'draft', 'pending', 'devolvido')";
		$join = '';

		if ( $args['turma_id'] ) {
			$join .= " INNER JOIN {$wpdb->postmeta} mt ON p.ID = mt.post_id";
			$where .= " AND mt.meta_key = '_turma' AND mt.meta_value = " . (int) $args['turma_id'];
		}

		if ( $args['local_id'] ) {
			$join .= " INNER JOIN {$wpdb->postmeta} ml ON p.ID = ml.post_id";
			$where .= " AND ml.meta_key = '_local_id' AND ml.meta_value = " . (int) $args['local_id'];
		}

		if ( $args['status'] && $args['status'] !== 'all' ) {
			$where .= " AND p.post_status = '" . sanitize_key( $args['status'] ) . "'";
		}

		if ( $args['start_date'] ) {
			$where .= " AND pm.meta_value >= '" . esc_sql( $args['start_date'] ) . "'";
		}

		if ( $args['end_date'] ) {
			$where .= " AND pm.meta_value <= '" . esc_sql( $args['end_date'] ) . "'";
		}

		switch ( $args['variable'] ) {
			case 'temperature':
				$field = 'pm_temp.meta_value';
				$join .= " INNER JOIN {$wpdb->postmeta} pm_temp ON p.ID = pm_temp.post_id";
				$where_temp = " pm_temp.meta_key = '_temperature_air'";
				break;
			case 'precipitation':
				$field = 'pm_precip.meta_value';
				$join .= " INNER JOIN {$wpdb->postmeta} pm_precip ON p.ID = pm_precip.post_id";
				$where_temp = " pm_precip.meta_key = '_precipitation'";
				break;
			case 'rpm':
				$field = 'pm_rpm.meta_value';
				$join .= " INNER JOIN {$wpdb->postmeta} pm_rpm ON p.ID = pm_rpm.post_id";
				$where_temp = " pm_rpm.meta_key = '_anemometer_rpm'";
				break;
			case 'barometer':
				$field = 'pm_baro.meta_value';
				$join .= " INNER JOIN {$wpdb->postmeta} pm_baro ON p.ID = pm_baro.post_id";
				$where_temp = " pm_baro.meta_key = '_barometer_displacement'";
				break;
			default:
				return array();
		}

		$where .= " AND " . $where_temp;

		$query = "SELECT p.ID, p.post_title, p.post_date, $field as value, pm_local.meta_value as local_id
			FROM {$wpdb->posts} p
			$join
			INNER JOIN {$wpdb->postmeta} pm_local ON p.ID = pm_local.post_id AND pm_local.meta_key = '_local_id'
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_observation_date'
			WHERE $where
			ORDER BY pm.meta_value, p.post_date";

		$results = $wpdb->get_results( $query );

		// Group by date
		$data = array();
		foreach ( $results as $row ) {
			$date = substr( $row->post_date, 0, 10 );
			if ( ! isset( $data[ $date ] ) ) {
				$data[ $date ] = array();
			}
			$data[ $date ][] = array(
				'id' => $row->ID,
				'value' => $row->value,
				'local_id' => $row->local_id
			);
		}

		return $data;
	}

	/**
	 * Calculate aggregates
	 */
	public function calculate_aggregates( $data, $variable ) {
		$aggregates = array(
			'count' => 0,
			'min' => null,
			'max' => null,
			'sum' => 0,
			'avg' => null,
			'periods' => array()
		);

		if ( empty( $data ) ) {
			return $aggregates;
		}

		$values = array();
		foreach ( $data as $date => $records ) {
			foreach ( $records as $record ) {
				if ( $record['value'] !== null && $record['value'] !== '' ) {
					$values[] = (float) $record['value'];
					$aggregates['count']++;
					$aggregates['sum'] += (float) $record['value'];

					if ( $aggregates['min'] === null || (float) $record['value'] < $aggregates['min'] ) {
						$aggregates['min'] = (float) $record['value'];
					}
					if ( $aggregates['max'] === null || (float) $record['value'] > $aggregates['max'] ) {
						$aggregates['max'] = (float) $record['value'];
					}
				}
			}
		}

		if ( $aggregates['count'] > 0 ) {
			$aggregates['avg'] = round( $aggregates['sum'] / $aggregates['count'], 2 );
		}

		// Add date periods
		foreach ( array_keys( $data ) as $date ) {
			$aggregates['periods'][] = $date;
		}

		return $aggregates;
	}

	/**
	 * Export to CSV
	 */
	public function export_csv( $args ) {
		global $wpdb;

		$defaults = array(
			'turma_id' => 0,
			'status' => 'publish',
			'start_date' => '',
			'end_date' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = "p.post_type = 'remc_observacao' AND p.post_status = 'publish'";
		$join = '';

		if ( $args['turma_id'] ) {
			$join .= " INNER JOIN {$wpdb->postmeta} mt ON p.ID = mt.post_id";
			$where .= " AND mt.meta_key = '_turma' AND mt.meta_value = " . (int) $args['turma_id'];
		}

		if ( $args['start_date'] ) {
			$where .= " AND pm_date.meta_value >= '" . esc_sql( $args['start_date'] ) . "'";
		}

		if ( $args['end_date'] ) {
			$where .= " AND pm_date.meta_value <= '" . esc_sql( $args['end_date'] ) . "'";
		}

		$query = "SELECT p.ID, p.post_title, p.post_date, u.display_name as aluno,
			pm_local.meta_value as local_id, pm_turma.meta_value as turma_id,
			pm_date.meta_value as observation_date, pm_status.meta_value as status,
			pm_temp.meta_value as temperature_air, pm_precip.meta_value as precipitation,
			pm_rpm.meta_value as anemometer_rpm, pm_baro.meta_value as barometer_displacement,
			pm_wind_dir.meta_value as wind_direction, pm_wind_int.meta_value as wind_intensity,
			pm_sky.meta_value as sky_condition, pm_cloud.meta_value as cloud_genera,
			pm_cover.meta_value as cloud_cover, pm_notes.meta_value as notes
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
			LEFT JOIN {$wpdb->postmeta} pm_local ON p.ID = pm_local.post_id AND pm_local.meta_key = '_local_id'
			LEFT JOIN {$wpdb->postmeta} pm_turma ON p.ID = pm_turma.post_id AND pm_turma.meta_key = '_turma'
			LEFT JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_observation_date'
			LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = '_status'
			LEFT JOIN {$wpdb->postmeta} pm_temp ON p.ID = pm_temp.post_id AND pm_temp.meta_key = '_temperature_air'
			LEFT JOIN {$wpdb->postmeta} pm_precip ON p.ID = pm_precip.post_id AND pm_precip.meta_key = '_precipitation'
			LEFT JOIN {$wpdb->postmeta} pm_rpm ON p.ID = pm_rpm.post_id AND pm_rpm.meta_key = '_anemometer_rpm'
			LEFT JOIN {$wpdb->postmeta} pm_baro ON p.ID = pm_baro.post_id AND pm_baro.meta_key = '_barometer_displacement'
			LEFT JOIN {$wpdb->postmeta} pm_wind_dir ON p.ID = pm_wind_dir.post_id AND pm_wind_dir.meta_key = '_wind_direction'
			LEFT JOIN {$wpdb->postmeta} pm_wind_int ON p.ID = pm_wind_int.post_id AND pm_wind_int.meta_key = '_wind_intensity'
			LEFT JOIN {$wpdb->postmeta} pm_sky ON p.ID = pm_sky.post_id AND pm_sky.meta_key = '_sky_condition'
			LEFT JOIN {$wpdb->postmeta} pm_cloud ON p.ID = pm_cloud.post_id AND pm_cloud.meta_key = '_cloud_genera'
			LEFT JOIN {$wpdb->postmeta} pm_cover ON p.ID = pm_cover.post_id AND pm_cover.meta_key = '_cloud_cover'
			LEFT JOIN {$wpdb->postmeta} pm_notes ON p.ID = pm_notes.post_id AND pm_notes.meta_key = '_notes'
			LEFT JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_observation_date'
			WHERE $where
			ORDER BY pm_date.meta_value, p.post_date";

		$results = $wpdb->get_results( $query );

		// Build CSV
		$output = fopen( 'php://temp', 'r+' );
		fputcsv( $output, array(
			'ID', 'Título', 'Data de Cadastro', 'Aluno', 'Local ID', 'Turma ID',
			'Data da Observação', 'Status', 'Temperatura (°C)', 'Precipitação (mm)',
			'RPM', 'Deslocamento Barômetro (mm)', 'Direção do Vento', 'Intensidade do Vento',
			'Condição do Céu', 'Gêneros de Nuvens', 'Cobertura de Nuvens', 'Notas'
		) );

		foreach ( $results as $row ) {
			fputcsv( $output, array(
				$row->ID,
				$this->sanitize_csv_cell( $row->post_title ),
				$row->post_date,
				$this->sanitize_csv_cell( $row->aluno ),
				$row->local_id,
				$row->turma_id,
				$row->observation_date,
				$row->status,
				$row->temperature_air,
				$row->precipitation,
				$row->anemometer_rpm,
				$row->barometer_displacement,
				$row->wind_direction,
				$this->sanitize_csv_cell( $row->wind_intensity ),
				$this->sanitize_csv_cell( $row->sky_condition ),
				$this->sanitize_csv_cell( $row->cloud_genera ),
				$row->cloud_cover,
				$this->sanitize_csv_cell( $row->notes )
			) );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	private function sanitize_csv_cell( $value ) {
		$value = (string) $value;
		// Escape formulas by prefixing with single quote
		if ( in_array( substr( $value, 0, 1 ), array( '=', '+', '-', '@' ) ) ) {
			$value = "'" . $value;
		}
		return $value;
	}

	/**
	 * AJAX handler for chart data
	 */
	public function ajax_get_chart_data() {
		if ( ! current_user_can( 'read_observacao' ) ) {
			wp_send_json_error( __( 'Acesso negado', 'remc-core' ), 403 );
		}

		$args = array(
			'turma_id' => isset( $_GET['turma_id'] ) ? (int) $_GET['turma_id'] : 0,
			'local_id' => isset( $_GET['local_id'] ) ? (int) $_GET['local_id'] : 0,
			'variable' => sanitize_key( $_GET['variable'] ),
			'start_date' => sanitize_text_field( $_GET['start_date'] ?? '' ),
			'end_date' => sanitize_text_field( $_GET['end_date'] ?? '' ),
		);

		$data = $this->get_chart_data( $args );
		$aggregates = $this->calculate_aggregates( $data, $args['variable'] );

		wp_send_json_success( array(
			'data' => $data,
			'aggregates' => $aggregates,
			'variable' => $args['variable']
		) );
	}

	/**
	 * AJAX handler for CSV export
	 */
	public function ajax_export_csv() {
		if ( ! current_user_can( 'export_data' ) ) {
			wp_send_json_error( __( 'Acesso negado', 'remc-core' ), 403 );
		}

		$args = array(
			'turma_id' => isset( $_GET['turma_id'] ) ? (int) $_GET['turma_id'] : 0,
			'status' => sanitize_key( $_GET['status'] ?? 'publish' ),
			'start_date' => sanitize_text_field( $_GET['start_date'] ?? '' ),
			'end_date' => sanitize_text_field( $_GET['end_date'] ?? '' ),
		);

		$csv = $this->export_csv( $args );

		$filename = 'remc-export-' . current_time( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $csv;
		wp_die();
	}
}
