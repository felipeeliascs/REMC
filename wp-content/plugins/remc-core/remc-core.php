<?php
/**
 * Plugin Name: REMC Core
 * Description: Core functionality for Rede Educacional de Monitoramento Climático
 * Author: REMC Team
 * Version: 0.1.0
 * Text Domain: remc-core
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REMC_CORE_VERSION', '0.1.0' );
define( 'REMC_CORE_FILE', __FILE__ );
define( 'REMC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'REMC_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstrap
 */
function remc_core_load() {
	require_once REMC_CORE_DIR . 'includes/class-remc-post-types.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-roles-capabilities.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-validation.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-notifications.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-reports.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-tutorials.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-meta-boxes.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-activity.php';
	require_once REMC_CORE_DIR . 'includes/class-remc-weather.php';

	Remc_Post_Types::instance();
	Remc_Roles_Capabilities::instance();
	Remc_Validation::instance();
	Remc_Notifications::instance();
	Remc_Reports::instance();
	Remc_Tutorials::instance();
	Remc_Meta_Boxes::instance();

	if ( function_exists( 'bp_is_active' ) ) {
		Remc_Activity::instance();
	}

	Remc_Weather::instance();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once REMC_CORE_DIR . 'includes/class-remc-bootstrap-command.php';
	}
}
add_action( 'plugins_loaded', 'remc_core_load' );
