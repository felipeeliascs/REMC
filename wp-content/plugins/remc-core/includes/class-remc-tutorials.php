<?php
/**
 * REMC Core - Tutorials Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remc_Tutorials {
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_tutorial_fields' ) );
		add_action( 'save_post_remc_tutorial', array( $this, 'save_tutorial_fields' ), 10, 2 );
	}

	/**
	 * Register tutorial meta fields
	 */
	public function register_tutorial_fields() {
		$fields = array(
			'objective' => array(
				'label' => 'Objetivo',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'materials' => array(
				'label' => 'Materiais',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'steps' => array(
				'label' => 'Etapas',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'reading_mode' => array(
				'label' => 'Forma de leitura',
				'type' => 'text',
				'context' => 'normal'
			),
			'unit' => array(
				'label' => 'Unidade',
				'type' => 'text',
				'context' => 'normal'
			),
			'limitations' => array(
				'label' => 'Limitações',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'precautions' => array(
				'label' => 'Cuidados',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'collection_fields' => array(
				'label' => 'Campos de coleta',
				'type' => 'textarea',
				'context' => 'normal'
			),
			'version' => array(
				'label' => 'Versão do protocolo',
				'type' => 'text',
				'context' => 'side'
			),
		);

		foreach ( $fields as $meta_key => $config ) {
			add_meta_box(
				"remc_tutorial_{$meta_key}",
				$config['label'],
				array( $this, 'render_tutorial_field' ),
				'remc_tutorial',
				$config['context'],
				'default',
				array( 'meta_key' => $meta_key, 'config' => $config )
			);
		}
	}

	/**
	 * Render tutorial field
	 */
	public function render_tutorial_field( $post, $box ) {
		$config = $box['args'];
		$value = get_post_meta( $post->ID, $config['meta_key'], true );
		?>
		<textarea name="<?php echo esc_attr( $config['meta_key'] ); ?>" 
			id="<?php echo esc_attr( $config['meta_key'] ); ?>" 
			cols="30" rows="<?php echo $config['type'] === 'textarea' ? 5 : 1; ?>" 
			class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/**
	 * Save tutorial fields
	 */
	public function save_tutorial_fields( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_remc_tutorial', $post_id ) ) {
			return;
		}

		$fields = array( 'objective', 'materials', 'steps', 'reading_mode', 'unit', 
			'limitations', 'precautions', 'collection_fields', 'version' );

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( $_POST[ $field ] ) );
			}
		}
	}

	/**
	 * Get tutorial by ID with full data
	 */
	public function get_tutorial( $id ) {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== 'remc_tutorial' ) {
			return null;
		}

		$meta = array(
			'objective' => get_post_meta( $id, '_objective', true ),
			'materials' => get_post_meta( $id, '_materials', true ),
			'steps' => get_post_meta( $id, '_steps', true ),
			'reading_mode' => get_post_meta( $id, '_reading_mode', true ),
			'unit' => get_post_meta( $id, '_unit', true ),
			'limitations' => get_post_meta( $id, '_limitations', true ),
			'precautions' => get_post_meta( $id, '_precautions', true ),
			'collection_fields' => get_post_meta( $id, '_collection_fields', true ),
			'version' => get_post_meta( $id, '_version', true ),
		);

		return array( 'post' => $post, 'meta' => $meta );
	}

	/**
	 * Get tutorials by category
	 */
	public function get_tutorials_by_category( $category_slug ) {
		$args = array(
			'post_type' => 'remc_tutorial',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'remc_tutorial_cat',
					'field' => 'slug',
					'terms' => $category_slug,
				),
			),
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Get all tutorial categories
	 */
	public function get_categories() {
		return get_terms( array(
			'taxonomy' => 'remc_tutorial_cat',
			'hide_empty' => false,
		) );
	}
}
