<?php
declare( strict_types=1 );

namespace VisitorSentinel\ThreatIntelligence;

class TorChecker {
	public static function isTorExit( string $ip ): bool {
		$settings = \VISISE_Settings::get();
		if ( empty( $settings['enable_tor_block'] ) ) {
			return false;
		}
		$list = get_transient( 'visise_tor_exits' );
		if ( false === $list ) {
			$list = self::fetchList();
			set_transient( 'visise_tor_exits', $list, WEEK_IN_SECONDS );
		}
		return in_array( $ip, $list, true );
	}

	private static function fetchList(): array {
		$response = wp_remote_get( 'https://check.torproject.org/torbulkexitlist', array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$body = wp_remote_retrieve_body( $response );
		return array_filter( explode( "\n", $body ) );
	}
}
