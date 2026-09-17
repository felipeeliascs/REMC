<?php
/**
 * REMC Educational Theme
 * Theme Name: REMC Educacional
 * Theme URI: https://github.com/yourusername/remc
 * Author: REMC Team
 * Description: Tema educacional para a Rede Educacional de Monitoramento Climático
 * Version: 0.1.0
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: remc-educacional
 * Tags: education, weather, blue, green
 *
 * This theme works with the REMC Core plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REMC_THEME_VERSION', '0.1.0' );
define( 'REMC_THEME_DIR', get_template_directory() );
define( 'REMC_THEME_URL', get_template_directory_uri() );

/**
 * Enqueue styles and scripts
 */
function remc_theme_enqueue() {
	// Main stylesheet
	wp_enqueue_style( 'remc-style', get_stylesheet_uri(), array(), REMC_THEME_VERSION );
	
	// Custom styles for charts and forms
	wp_enqueue_style( 'remc-charts', REMC_THEME_URL . '/assets/css/charts.css', array(), REMC_THEME_VERSION );
	wp_enqueue_style( 'remc-forms', REMC_THEME_URL . '/assets/css/forms.css', array(), REMC_THEME_VERSION );
	
	// JavaScript for interactive elements
	wp_enqueue_script( 'remc-scripts', REMC_THEME_URL . '/assets/js/scripts.js', array( 'jquery' ), REMC_THEME_VERSION, true );
	
	// Localize script for AJAX
	wp_localize_script( 'remc-scripts', 'remc_ajax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'remc-nonce' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'remc_theme_enqueue' );

/**
 * Theme setup
 */
function remc_theme_setup() {
	// Add theme support
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
	
	// Register navigation menus
	register_nav_menus( array(
		'main' => __( 'Menu Principal', 'remc-educacional' ),
		'footer' => __( 'Rodapé', 'remc-educacional' ),
	) );
	
	// Set content width
	if ( ! isset( $content_width ) ) {
		$content_width = 800;
	}
}
add_action( 'after_setup_theme', 'remc_theme_setup' );

/**
 * Theme widgets
 */
function remc_theme_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar Principal', 'remc-educacional' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Widgets exibidos na sidebar principal.', 'remc-educacional' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'remc_theme_widgets_init' );

/**
 * Custom template tags
 */
if ( ! function_exists( 'remc_posted_on' ) ) :
function remc_posted_on() {
	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
	}
	
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);
	
	$posted_on = sprintf(
		_x( 'Publicado em %s', 'post date', 'remc-educacional' ),
		'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
	);
	
	echo '<span class="posted-on">' . $posted_on . '</span>';
}
endif;

if ( ! function_exists( 'remc_posted_by' ) ) :
function remc_posted_by() {
	$byline = sprintf(
		_x( ' por %s', 'post author', 'remc-educacional' ),
		'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
	);
	
	echo '<span class="byline"> ' . $byline . '</span>';
}
endif;

/**
 * Get user turmas
 */
function remc_get_user_turmas( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	
	$turma_ids = get_user_meta( $user_id, '_linked_turmas', true );
	
	if ( ! $turma_ids || ! is_array( $turma_ids ) ) {
		return array();
	}
	
	return $turma_ids;
}

/**
 * Get user's turma IDs for a specific role
 */
function remc_get_user_turmas_by_role( $role = 'aluno' ) {
	$args = array(
		'role__in' => array( $role ),
		'meta_query' => array(
			array(
				'key' => '_linked_turmas',
				'compare' => 'EXISTS',
			),
		),
	);
	
	$users = get_users( $args );
	$turmas = array();
	
	foreach ( $users as $user ) {
		$turma_ids = get_user_meta( $user->ID, '_linked_turmas', true );
		if ( $turma_ids && is_array( $turma_ids ) ) {
			$turmas = array_merge( $turmas, $turma_ids );
		}
	}
	
	return array_unique( $turmas );
}

/**
 * Template part for displaying posts
 */
function remc_entry_meta() {
	echo '<div class="entry-meta">';
	
	if ( 'post' === get_post_type() ) {
		remc_posted_on();
		remc_posted_by();
	}
	
	echo '</div>';
}
