<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;
$tables = array(
	$wpdb->prefix . 'visise_visits',
	$wpdb->prefix . 'visise_events',
	$wpdb->prefix . 'visise_bans',
	$wpdb->prefix . 'visise_presence',
	$wpdb->prefix . 'visise_unban_log',
	$wpdb->prefix . 'visise_rate_limits',
	$wpdb->prefix . 'visise_subnet_bans',
	$wpdb->prefix . 'visise_daily_stats',
);
foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `" . esc_sql( $table ) . "`" );
}
delete_option( 'visise_settings' );
delete_option( 'visise_db_version' );
delete_option( 'visise_honeyfile_slug' );
delete_option( 'visise_honeyfile_rules_version' );
delete_option( 'visise_decoy_api_key' );
delete_option( 'visise_decoy_username' );
delete_option( 'visise_trap_email' );
delete_option( 'visise_fake_admin_slug' );
delete_option( 'visise_fake_admin_version' );
delete_option( 'visise_tor_exits' );
$timestamp = wp_next_scheduled( 'visise_daily_cleanup' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'visise_daily_cleanup' );
}
