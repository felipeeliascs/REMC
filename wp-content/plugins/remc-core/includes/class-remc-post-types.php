<?php
/**
 * REMC Core - Post Types Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Post_Types {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	public function register_post_types() {
		// remc_escola - Schools (without personal data)
		register_post_type( 'remc_escola', array(
			'label'  => 'Escolas',
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => false,
			'supports' => array( 'title' ),
			'menu_icon' => 'dashicons-building',
			'capability_type' => 'remc_escola',
			'map_meta_cap' => true,
		) );

		// remc_local - Observation points
		register_post_type( 'remc_local', array(
			'label'  => 'Locais de Observação',
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => false,
			'supports' => array( 'title', 'custom-fields' ),
			'menu_icon' => 'dashicons-location-alt',
			'capability_type' => 'remc_local',
			'map_meta_cap' => true,
		) );

		// remc_observacao - Observations
		register_post_type( 'remc_observacao', array(
			'label'  => 'Observações',
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => false,
			'supports' => array( 'title', 'custom-fields', 'revisions' ),
			'menu_icon' => 'dashicons-chart-line',
			'capability_type' => 'remc_observacao',
			'map_meta_cap' => true,
			'rewrite' => false,
		) );

		// remc_tutorial - Educational guides
		register_post_type( 'remc_tutorial', array(
			'label'  => 'Tutoriais',
			'public' => true,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			'menu_icon' => 'dashicons-book-alt',
			'capability_type' => 'remc_tutorial',
			'map_meta_cap' => true,
			'rewrite' => array( 'slug' => 'tutoriais' ),
		) );

		// remc_atividade - Student activities/reports
		register_post_type( 'remc_atividade', array(
			'label'  => 'Atividades',
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => false,
			'supports' => array( 'title', 'custom-fields', 'revisions' ),
			'menu_icon' => 'dashicons-portfolio',
			'capability_type' => 'remc_atividade',
			'map_meta_cap' => true,
		) );
	}

	public function register_taxonomies() {
		// Taxonomy for tutorials
		register_taxonomy( 'remc_tutorial_cat', 'remc_tutorial', array(
			'label'        => 'Categorias',
			'rewrite'      => array( 'slug' => 'categoria-tutorial' ),
			'hierarchical' => true,
		) );

		// Taxonomy for observation methods
		register_taxonomy( 'remc_metodo', 'remc_observacao', array(
			'label'        => 'Método',
			'rewrite'      => array( 'slug' => 'metodo' ),
			'hierarchical' => true,
		) );
	}
}
