<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Geo {

	public static function lookup( $ip ) {
		$response = wp_remote_get(
			'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,message,country,countryCode,regionName,city,isp,proxy,hosting',
			array( 'timeout' => 3 )
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data ) || ! isset( $data['status'] ) || 'success' !== $data['status'] ) {
			return array();
		}
		return $data;
	}

	public static function get_country_code( $ip ) {
		$data = self::lookup( $ip );
		return ! empty( $data['countryCode'] ) ? $data['countryCode'] : '';
	}
}
