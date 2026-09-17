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

	/**
	 * Mapa explicito de capabilities.
	 *
	 * Sem isto, o WordPress geraria nomes pluralizados como "edit_remc_observacaos",
	 * que nao correspondem as capabilities concedidas aos papeis do projeto.
	 */
	private function caps( $singular, $plural ) {
		return array(
			'edit_post'              => 'edit_' . $singular,
			'read_post'              => 'read_' . $singular,
			'delete_post'            => 'delete_' . $singular,
			'edit_posts'             => 'edit_' . $plural,
			'edit_others_posts'      => 'edit_others_' . $plural,
			'edit_published_posts'   => 'edit_published_' . $plural,
			'edit_private_posts'     => 'edit_private_' . $plural,
			'publish_posts'          => 'publish_' . $singular,
			'read_private_posts'     => 'read_private_' . $plural,
			'create_posts'           => 'create_' . $singular,
			'delete_posts'           => 'delete_' . $plural,
			'delete_others_posts'    => 'delete_others_' . $plural,
			'delete_published_posts' => 'delete_published_' . $plural,
			'delete_private_posts'   => 'delete_private_' . $plural,
		);
	}

	public function register_post_types() {
		// remc_escola - Escolas (sem dados pessoais)
		register_post_type( 'remc_escola', array(
			'label'       => 'Escolas',
			'public'      => false,
			'show_ui'     => true,
			'show_in_rest' => false,
			'supports'    => array( 'title' ),
			'menu_icon'   => 'dashicons-building',
			'capabilities' => $this->caps( 'escola', 'escolas' ),
			'map_meta_cap' => true,
		) );

		// remc_local - Pontos de observacao
		register_post_type( 'remc_local', array(
			'label'       => 'Locais de Observação',
			'public'      => false,
			'show_ui'     => true,
			'show_in_rest' => false,
			'supports'    => array( 'title', 'custom-fields' ),
			'menu_icon'   => 'dashicons-location-alt',
			'capabilities' => $this->caps( 'local', 'locais' ),
			'map_meta_cap' => true,
		) );

		// remc_observacao - Observacoes
		register_post_type( 'remc_observacao', array(
			'label'       => 'Observações',
			'public'      => false,
			'show_ui'     => true,
			'show_in_rest' => false,
			'supports'    => array( 'title', 'custom-fields', 'revisions' ),
			'menu_icon'   => 'dashicons-chart-line',
			'capabilities' => $this->caps( 'observacao', 'observacoes' ),
			'map_meta_cap' => true,
			'rewrite'     => false,
		) );

		// remc_tutorial - Materiais didaticos (publicos quando publicados)
		register_post_type( 'remc_tutorial', array(
			'label'       => 'Tutoriais',
			'public'      => true,
			'show_ui'     => true,
			'show_in_rest' => true,
			'has_archive' => true,
			'supports'    => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			'menu_icon'   => 'dashicons-book-alt',
			'capabilities' => $this->caps( 'tutorial', 'tutoriais' ),
			'map_meta_cap' => true,
			'rewrite'     => array( 'slug' => 'tutoriais' ),
		) );

		// remc_atividade - Relatorios didaticos (privados)
		register_post_type( 'remc_atividade', array(
			'label'       => 'Atividades',
			'public'      => false,
			'show_ui'     => true,
			'show_in_rest' => false,
			'supports'    => array( 'title', 'custom-fields', 'revisions' ),
			'menu_icon'   => 'dashicons-portfolio',
			'capabilities' => $this->caps( 'atividade', 'atividades' ),
			'map_meta_cap' => true,
		) );
	}

	public function register_taxonomies() {
		// Categorias dos tutoriais
		register_taxonomy( 'remc_tutorial_cat', 'remc_tutorial', array(
			'label'        => 'Categorias',
			'rewrite'      => array( 'slug' => 'categoria-tutorial' ),
			'hierarchical' => true,
		) );

		// Metodo de coleta das observacoes
		register_taxonomy( 'remc_metodo', 'remc_observacao', array(
			'label'        => 'Método',
			'rewrite'      => array( 'slug' => 'metodo' ),
			'hierarchical' => true,
		) );
	}
}
