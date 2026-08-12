<?php
/**
 * Plugin Name: Visitor Sentinel
 * Plugin URI: https://wordpress.org/plugins/visitor-sentinel/
 * Description: Monitors visitors, detects bots, honeypots, progressive defense, threat intel, SSE live dashboard.
 * Version: 3.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Visitor Sentinel
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visitor-sentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VISISE_VERSION', '3.0.1' );
define( 'VISISE_PLUGIN_FILE', __FILE__ );
define( 'VISISE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VISISE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'VisitorSentinel\\';
		$base   = VISISE_PLUGIN_DIR . 'src/';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$file = $base . str_replace( '\\', '/', substr( $class, $len ) ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

require_once VISISE_PLUGIN_DIR . 'includes/class-visise-db.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-ip.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-settings.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-logger.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-ban.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-notifications.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-geo.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-ua.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-icons.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-detector.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-honeypot.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-admin.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-frontend.php';
require_once VISISE_PLUGIN_DIR . 'includes/class-visise-cron.php';

function visise_activate_plugin() {
	VISISE_DB::create_tables();
	VISISE_Cron::schedule();

	VISISE_Honeypot::get_honeyfile_slug();
	add_rewrite_rule( '^' . preg_quote( VISISE_Honeypot::get_honeyfile_slug(), '/' ) . '\.(txt|sql|zip|xlsx)$', 'index.php?' . VISISE_Honeypot::HONEYFILE_QUERY_VAR . '=1', 'top' );
	flush_rewrite_rules();
	VisitorSentinel\Privacy\GDPRManager::register();
}
register_activation_hook( __FILE__, 'visise_activate_plugin' );

function visise_deactivate_plugin() {
	VISISE_Cron::unschedule();
}
register_deactivation_hook( __FILE__, 'visise_deactivate_plugin' );

function visise_run_plugin() {
	if ( get_option( 'visise_db_version' ) !== VISISE_VERSION ) {
		VISISE_DB::create_tables();
	}
	new VISISE_Detector();
	new VISISE_Admin();
	new VISISE_Frontend();
	new VISISE_Cron();
	VisitorSentinel\Admin\BulkActions::register();
	VisitorSentinel\Admin\LiveStream::register();
	VisitorSentinel\Privacy\GDPRManager::register();

	$settings = VISISE_Settings::get();
	if ( ! empty( $settings['enable_honeypot_suite'] ) ) {
		new VISISE_Honeypot();
		new VisitorSentinel\Deception\ExtendedHoneypot();
	}
	VisitorSentinel\Core\AsyncLogger::init();
}
add_action( 'plugins_loaded', 'visise_run_plugin' );
