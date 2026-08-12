<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_DB {

	public static function visits_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_visits';
	}
	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_events';
	}
	public static function bans_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_bans';
	}
	public static function presence_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_presence';
	}
	public static function unban_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_unban_log';
	}
	public static function rate_limits_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_rate_limits';
	}
	public static function subnet_bans_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_subnet_bans';
	}
	public static function daily_stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'visise_daily_stats';
	}

	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$visits       = self::visits_table();
		$events       = self::events_table();
		$bans         = self::bans_table();
		$presence     = self::presence_table();
		$unban_log    = self::unban_log_table();
		$rate_limits  = self::rate_limits_table();
		$subnet_bans  = self::subnet_bans_table();
		$daily_stats  = self::daily_stats_table();

		$sql_visits = "CREATE TABLE $visits (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			request_uri VARCHAR(500) NOT NULL DEFAULT '',
			referer VARCHAR(500) NOT NULL DEFAULT '',
			country_code VARCHAR(2) NOT NULL DEFAULT '',
			is_logged_in TINYINT(1) NOT NULL DEFAULT 0,
			visitor_role VARCHAR(20) NOT NULL DEFAULT 'guest',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY ip (ip),
			KEY created_at (created_at),
			KEY country_code (country_code)
		) $charset_collate;";

		$sql_events = "CREATE TABLE $events (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			event_type VARCHAR(50) NOT NULL,
			description VARCHAR(500) NOT NULL DEFAULT '',
			score INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY ip (ip),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) $charset_collate;";

		$sql_bans = "CREATE TABLE $bans (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			ban_type VARCHAR(20) NOT NULL DEFAULT 'permanent',
			reason VARCHAR(500) NOT NULL DEFAULT '',
			score INT NOT NULL DEFAULT 0,
			temp_ban_count INT NOT NULL DEFAULT 0,
			hits_while_banned INT NOT NULL DEFAULT 0,
			is_manual TINYINT(1) NOT NULL DEFAULT 0,
			device_token VARCHAR(64) NOT NULL DEFAULT '',
			fingerprint VARCHAR(32) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			expires_at DATETIME NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ip (ip),
			KEY device_token (device_token),
			KEY fingerprint (fingerprint)
		) $charset_collate;";

		$sql_presence = "CREATE TABLE $presence (
			ip VARCHAR(45) NOT NULL,
			last_seen DATETIME NOT NULL,
			PRIMARY KEY  (ip),
			KEY last_seen (last_seen)
		) $charset_collate;";

		$sql_unban_log = "CREATE TABLE $unban_log (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip VARCHAR(45) NOT NULL,
			ban_type VARCHAR(20) NOT NULL DEFAULT '',
			original_reason VARCHAR(500) NOT NULL DEFAULT '',
			score INT NOT NULL DEFAULT 0,
			admin_display_name VARCHAR(200) NOT NULL DEFAULT '',
			admin_login VARCHAR(200) NOT NULL DEFAULT '',
			declaration TEXT NOT NULL,
			signature_name VARCHAR(200) NOT NULL DEFAULT '',
			signature_hash VARCHAR(64) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY ip (ip)
		) $charset_collate;";

		$sql_rate_limits = "CREATE TABLE $rate_limits (
			ip_hash VARCHAR(32) NOT NULL,
			request_count INT UNSIGNED NOT NULL DEFAULT 1,
			window_start DATETIME NOT NULL,
			PRIMARY KEY  (ip_hash),
			KEY window_start (window_start)
		) $charset_collate;";

		$sql_subnet_bans = "CREATE TABLE $subnet_bans (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			subnet VARCHAR(64) NOT NULL,
			reason VARCHAR(500) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY subnet (subnet)
		) $charset_collate;";

		$sql_daily_stats = "CREATE TABLE $daily_stats (
			stats_date DATE NOT NULL,
			visits INT UNSIGNED NOT NULL DEFAULT 0,
			events INT UNSIGNED NOT NULL DEFAULT 0,
			bans INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (stats_date)
		) $charset_collate;";

		dbDelta( $sql_visits );
		dbDelta( $sql_events );
		dbDelta( $sql_bans );
		dbDelta( $sql_presence );
		dbDelta( $sql_unban_log );
		dbDelta( $sql_rate_limits );
		dbDelta( $sql_subnet_bans );
		dbDelta( $sql_daily_stats );

		$wpdb->query( "DELETE v1 FROM $visits v1 INNER JOIN $visits v2 ON v1.ip = v2.ip AND v1.id < v2.id" );

		if ( false === get_option( 'visise_settings' ) ) {
			add_option( 'visise_settings', VISISE_Settings::defaults() );
		}
		add_option( 'visise_db_version', VISISE_VERSION );
	}
}
