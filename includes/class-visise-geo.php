<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Geo {

	public static function lookup( $ip ) {
		$cache_key = 'visise_geo_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$response = wp_remote_get(
			'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,message,country,countryCode,regionName,city,isp,proxy,hosting',
			array( 'timeout' => 3 )
		);
		if ( is_wp_error( $response ) ) {
			set_transient( $cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data ) || ! isset( $data['status'] ) || 'success' !== $data['status'] ) {
			set_transient( $cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}
		set_transient( $cache_key, $data, 30 * DAY_IN_SECONDS );
		return $data;
	}

	public static function get_country_code( $ip ) {
		$data = self::lookup( $ip );
		return ! empty( $data['countryCode'] ) ? $data['countryCode'] : '';
	}
}
