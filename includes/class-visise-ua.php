<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_UA {

	public static function parse( $ua ) {
		$platform = 'Unknown';
		$browser  = 'Unknown';
		$ua_lower = strtolower( (string) $ua );

		if ( strpos( $ua_lower, 'windows' ) !== false ) {
			$platform = 'Windows';
		} elseif ( strpos( $ua_lower, 'macintosh' ) !== false || strpos( $ua_lower, 'mac os' ) !== false ) {
			$platform = 'macOS';
		} elseif ( strpos( $ua_lower, 'linux' ) !== false ) {
			$platform = 'Linux';
		} elseif ( strpos( $ua_lower, 'android' ) !== false ) {
			$platform = 'Android';
		} elseif ( strpos( $ua_lower, 'iphone' ) !== false || strpos( $ua_lower, 'ipad' ) !== false ) {
			$platform = 'iOS';
		}

		if ( strpos( $ua_lower, 'chrome' ) !== false ) {
			$browser = 'Chrome';
		} elseif ( strpos( $ua_lower, 'firefox' ) !== false ) {
			$browser = 'Firefox';
		} elseif ( strpos( $ua_lower, 'safari' ) !== false ) {
			$browser = 'Safari';
		} elseif ( strpos( $ua_lower, 'edge' ) !== false ) {
			$browser = 'Edge';
		}

		return compact( 'platform', 'browser' );
	}
}
