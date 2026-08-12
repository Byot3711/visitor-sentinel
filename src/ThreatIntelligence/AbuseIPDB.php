<?php
declare( strict_types=1 );

namespace VisitorSentinel\ThreatIntelligence;

class AbuseIPDB {
	public static function isKnownBad( string $ip ): bool {
		$settings = \VISISE_Settings::get();
		if ( empty( $settings['enable_abuseipdb'] ) || empty( $settings['abuseipdb_api_key'] ) ) {
			return false;
		}
		$cache_key = 'visise_abuseipdb_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (bool) $cached;
		}
		$response = wp_remote_get(
			'https://api.abuseipdb.com/api/v2/check?ipAddress=' . rawurlencode( $ip ) . '&maxAgeInDays=90',
			array(
				'headers' => array( 'Key' => $settings['abuseipdb_api_key'], 'Accept' => 'application/json' ),
				'timeout' => 3,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, 0, HOUR_IN_SECONDS );
			return false;
		}
		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$score = isset( $body['data']['abuseConfidenceScore'] ) ? (int) $body['data']['abuseConfidenceScore'] : 0;
		$isBad = $score > 25;
		set_transient( $cache_key, $isBad ? 1 : 0, DAY_IN_SECONDS );
		return $isBad;
	}
}
