<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Ban {

	private static function purge_page_cache() {
		if ( has_action( 'litespeed_purge_all' ) || defined( 'LSCWP_V' ) ) {
			do_action( 'litespeed_purge_all' );
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		if ( class_exists( 'WpFastestCache' ) ) {
			global $wp_fastest_cache;
			if ( is_object( $wp_fastest_cache ) && method_exists( $wp_fastest_cache, 'deleteCache' ) ) {
				$wp_fastest_cache->deleteCache( true );
			}
		}
	}

	public static function get( $ip ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VISISE_DB::bans_table() . " WHERE ip = %s", $ip ) );
	}

	public static function get_all( $limit = 100, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . VISISE_DB::bans_table() . " ORDER BY updated_at DESC LIMIT %d OFFSET %d", $limit, $offset )
		);
	}

	public static function get_all_active( $limit = 100, $offset = 0 ) {
		global $wpdb;
		$now = current_time( 'mysql' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . VISISE_DB::bans_table() . " WHERE ban_type = 'permanent' OR expires_at > %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
				$now,
				$limit,
				$offset
			)
		);
	}

	public static function get_active_ips() {
		global $wpdb;
		$now = current_time( 'mysql' );
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ip FROM " . VISISE_DB::bans_table() . " WHERE ban_type = 'permanent' OR expires_at > %s",
				$now
			)
		);
	}

	public static function count_all() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . VISISE_DB::bans_table() );
	}

	public static function count_active() {
		global $wpdb;
		$now = current_time( 'mysql' );
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM " . VISISE_DB::bans_table() . " WHERE ban_type = 'permanent' OR expires_at > %s", $now )
		);
	}

	public static function is_banned( $ip ) {
		$ban = self::get( $ip );
		if ( ! $ban ) {
			$subnet = VisitorSentinel\ThreatIntelligence\SubnetBlocker::getSubnet( $ip );
			if ( $subnet ) {
				$ban = self::get_subnet_ban( $subnet );
			}
		}
		if ( ! $ban ) {
			return false;
		}
		if ( 'permanent' === $ban->ban_type ) {
			return $ban;
		}
		if ( ! empty( $ban->expires_at ) && strtotime( $ban->expires_at ) > current_time( 'timestamp' ) ) {
			return $ban;
		}
		return false;
	}

	public static function get_subnet_ban( string $subnet ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . VISISE_DB::subnet_bans_table() . " WHERE subnet = %s", $subnet )
		);
	}

	public static function count_active_in_subnet( string $subnet ) {
		global $wpdb;
		$like = str_replace( '/24', '.%', $subnet );
		$like = str_replace( '/64', '%', $like );
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM " . VISISE_DB::bans_table() . " WHERE ip LIKE %s", $like )
		);
	}

	public static function apply_subnet_ban( string $subnet, string $reason ) {
		global $wpdb;
		$now = current_time( 'mysql' );
		$wpdb->replace(
			VISISE_DB::subnet_bans_table(),
			array(
				'subnet'     => $subnet,
				'reason'     => mb_substr( $reason, 0, 500 ),
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%s', '%s', '%s' )
		);
		self::purge_page_cache();
	}

	public static function find_active_for_request( $ip ) {
		$ban = self::is_banned( $ip );
		if ( $ban ) {
			return $ban;
		}
		if ( ! empty( $_COOKIE['visise_id'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_COOKIE['visise_id'] ) );
			if ( preg_match( '/^[a-f0-9]{40}$/', $token ) ) {
				$ban = self::get_by_device_token( $token );
				if ( $ban ) {
					return $ban;
				}
			}
		}
		$settings = VISISE_Settings::get();
		if ( empty( $settings['enable_fingerprinting'] ) ) {
			return false;
		}
		$fingerprint = self::get_fingerprint_from_request();
		if ( empty( $fingerprint ) ) {
			return false;
		}
		return self::get_by_fingerprint( $fingerprint );
	}

	public static function get_fingerprint_from_request() {
		if ( empty( $_COOKIE['visise_fp'] ) ) {
			return '';
		}
		$fingerprint = sanitize_text_field( wp_unslash( $_COOKIE['visise_fp'] ) );
		if ( ! preg_match( '/^[a-f0-9]{1,32}$/', $fingerprint ) ) {
			return '';
		}
		return $fingerprint;
	}

	public static function get_by_device_token( $token ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VISISE_DB::bans_table() . " WHERE device_token = %s", $token ) );
	}

	public static function get_by_fingerprint( $fingerprint ) {
		global $wpdb;
		if ( empty( $fingerprint ) ) {
			return false;
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VISISE_DB::bans_table() . " WHERE fingerprint = %s", $fingerprint ) );
	}

	private static function generate_device_token() {
		return bin2hex( random_bytes( 20 ) );
	}

	public static function set_device_cookie( $ban ) {
		if ( empty( $ban->device_token ) || headers_sent() ) {
			return;
		}
		setcookie(
			'visise_id',
			$ban->device_token,
			array(
				'expires'  => time() + 10 * YEAR_IN_SECONDS,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	public static function apply_ban( $ip, $reason, $score ) {
		global $wpdb;
		$existing    = self::get( $ip );
		$now         = current_time( 'mysql' );
		$fingerprint = self::get_fingerprint_from_request();

		if ( $existing ) {
			$data   = array(
				'ban_type'   => 'permanent',
				'reason'     => mb_substr( $reason, 0, 500 ),
				'score'      => absint( $score ),
				'expires_at' => null,
				'updated_at' => $now,
			);
			$format = array( '%s', '%s', '%d', '%s', '%s' );
			if ( empty( $existing->device_token ) ) {
				$data['device_token'] = self::generate_device_token();
				$format[]             = '%s';
			}
			if ( ! empty( $fingerprint ) && empty( $existing->fingerprint ) ) {
				$data['fingerprint'] = $fingerprint;
				$format[]            = '%s';
			}
			$wpdb->update( VISISE_DB::bans_table(), $data, array( 'ip' => $ip ), $format, array( '%s' ) );
			VISISE_Notifications::notify_ban( $ip, 'permanent', $reason );
			VisitorSentinel\Notifications\WebhookNotifier::notify( $ip, 'permanent', $reason, (int) $score );
			self::purge_page_cache();
			return 'permanent';
		}

		$wpdb->insert(
			VISISE_DB::bans_table(),
			array(
				'ip'                => $ip,
				'ban_type'          => 'permanent',
				'reason'            => mb_substr( $reason, 0, 500 ),
				'score'             => absint( $score ),
				'temp_ban_count'    => 0,
				'hits_while_banned' => 0,
				'device_token'      => self::generate_device_token(),
				'fingerprint'       => $fingerprint,
				'created_at'        => $now,
				'expires_at'        => null,
				'updated_at'        => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		VISISE_Notifications::notify_ban( $ip, 'permanent', $reason );
		VisitorSentinel\Notifications\WebhookNotifier::notify( $ip, 'permanent', $reason, (int) $score );
		self::purge_page_cache();
		return 'permanent';
	}

	public static function register_hit_while_banned( $ip ) {
		global $wpdb;
		$ban = self::get( $ip );
		if ( ! $ban ) {
			return;
		}
		$wpdb->update(
			VISISE_DB::bans_table(),
			array(
				'hits_while_banned' => (int) $ban->hits_while_banned + 1,
				'updated_at'        => current_time( 'mysql' ),
			),
			array( 'ip' => $ip ),
			array( '%d', '%s' ),
			array( '%s' )
		);
	}

	public static function unban( $ip ) {
		global $wpdb;
		$result = $wpdb->delete( VISISE_DB::bans_table(), array( 'ip' => $ip ), array( '%s' ) );
		self::purge_page_cache();
		return $result;
	}

	public static function unban_with_declaration( $ip, $declaration, $signature_name ) {
		global $wpdb;
		$ban = self::get( $ip );
		if ( ! $ban ) {
			return false;
		}
		$admin       = wp_get_current_user();
		$now         = current_time( 'mysql' );
		$declaration = sanitize_textarea_field( $declaration );
		$signature   = sanitize_text_field( $signature_name );

		$signature_hash = hash(
			'sha256',
			implode( '|', array( $ip, $ban->ban_type, $ban->reason, $ban->score, $admin->user_login, $declaration, $signature, $now ) )
		);

		$wpdb->insert(
			VISISE_DB::unban_log_table(),
			array(
				'ip'                 => $ip,
				'ban_type'           => $ban->ban_type,
				'original_reason'    => $ban->reason,
				'score'              => (int) $ban->score,
				'admin_display_name' => $admin->display_name,
				'admin_login'        => $admin->user_login,
				'declaration'        => $declaration,
				'signature_name'     => $signature,
				'signature_hash'     => $signature_hash,
				'created_at'         => $now,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$record_id = (int) $wpdb->insert_id;
		VISISE_Logger::delete_events_for_ip( $ip );
		$wpdb->delete( VISISE_DB::bans_table(), array( 'ip' => $ip ), array( '%s' ) );
		self::purge_page_cache();
		return $record_id;
	}

	public static function manual_ban( $ip, $reason ) {
		global $wpdb;
		$now      = current_time( 'mysql' );
		$existing = self::get( $ip );
		if ( $existing ) {
			$data   = array(
				'ban_type'   => 'permanent',
				'reason'     => mb_substr( $reason, 0, 500 ),
				'expires_at' => null,
				'is_manual'  => 1,
				'updated_at' => $now,
			);
			$format = array( '%s', '%s', '%s', '%d', '%s' );
			if ( empty( $existing->device_token ) ) {
				$data['device_token'] = self::generate_device_token();
				$format[]             = '%s';
			}
			$result = $wpdb->update( VISISE_DB::bans_table(), $data, array( 'ip' => $ip ), $format, array( '%s' ) );
			self::purge_page_cache();
			return $result;
		}
		$result = $wpdb->insert(
			VISISE_DB::bans_table(),
			array(
				'ip'           => $ip,
				'ban_type'     => 'permanent',
				'reason'       => mb_substr( $reason, 0, 500 ),
				'score'        => 0,
				'is_manual'    => 1,
				'device_token' => self::generate_device_token(),
				'created_at'   => $now,
				'expires_at'   => null,
				'updated_at'   => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		self::purge_page_cache();
		return $result;
	}
}
