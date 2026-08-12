<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_IP {

	public static function get_client_ip() {
		$settings = VISISE_Settings::get();
		$headers  = array( 'REMOTE_ADDR' );
		if ( ! empty( $settings['trust_forwarded_for'] ) ) {
			$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		}
		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$raw = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ips = explode( ',', $raw );
				foreach ( $ips as $ip ) {
					$ip = trim( $ip );
					if ( self::is_valid_ip( $ip ) ) {
						return $ip;
					}
				}
			}
		}
		return '';
	}

	public static function is_valid_ip( $ip ) {
		return ! empty( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	public static function is_whitelisted( $ip ) {
		$settings  = VISISE_Settings::get();
		$whitelist = array_filter( array_map( 'trim', explode( "\n", $settings['whitelist_ips'] ) ) );
		return in_array( $ip, $whitelist, true );
	}
}
