<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Cron {

	const HOOK = 'visise_daily_cleanup';

	public function __construct() {
		add_action( self::HOOK, array( $this, 'run_cleanup' ) );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	public function run_cleanup() {
		$settings = VISISE_Settings::get();
		VISISE_Logger::purge_old_data( $settings['retention_days'] );
		VISISE_Logger::purge_stale_presence();

		global $wpdb;
		$yesterday = gmdate( 'Y-m-d', current_time( 'timestamp' ) - DAY_IN_SECONDS );
		$visits    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . VISISE_DB::visits_table() . " WHERE DATE(created_at) = %s", $yesterday ) );
		$events    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . VISISE_DB::events_table() . " WHERE DATE(created_at) = %s", $yesterday ) );
		$bans      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . VISISE_DB::bans_table() . " WHERE DATE(created_at) = %s", $yesterday ) );

		$wpdb->replace(
			VISISE_DB::daily_stats_table(),
			array(
				'stats_date' => $yesterday,
				'visits'     => $visits,
				'events'     => $events,
				'bans'       => $bans,
			),
			array( '%s', '%d', '%d', '%d' )
		);
	}
}
