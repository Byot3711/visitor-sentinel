<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Settings {

	const OPTION_KEY = 'visise_settings';

	public static function defaults() {
		return array(
			'rate_limit_requests'          => 60,
			'rate_limit_seconds'           => 10,
			'score_threshold'              => 50,
			'challenge_threshold'          => 35,
			'whitelist_ips'                => '',
			'trust_forwarded_for'          => 0,
			'track_404'                    => 1,
			'retention_days'               => 30,
			'frontend_counter_enabled'     => 1,
			'frontend_counter_role'        => 'read',
			'frontend_counter_position'    => 'left',
			'frontend_counter_show_guests' => 1,
			'enable_geo_lookup'            => 0,
			'email_notifications_enabled'  => 0,
			'notification_email'           => '',
			'enable_honeypot_suite'        => 1,
			'enable_fingerprinting'        => 0,
			'enable_abuseipdb'             => 0,
			'abuseipdb_api_key'            => '',
			'enable_tor_block'             => 0,
			'enable_subnet_block'          => 0,
			'enable_webhook'               => 0,
			'webhook_url'                  => '',
			'enable_html_email'            => 0,
			'gdpr_anonymize_after_days'    => 90,
		);
	}

	public static function get() {
		$settings = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $settings, self::defaults() );
	}

	public static function update( array $settings ) {
		update_option( self::OPTION_KEY, $settings );
	}

	public static function sanitize( array $input ) {
		$defaults = self::defaults();
		$clean    = array();

		$int_fields = array(
			'rate_limit_requests',
			'rate_limit_seconds',
			'score_threshold',
			'challenge_threshold',
			'retention_days',
			'gdpr_anonymize_after_days',
		);
		foreach ( $int_fields as $f ) {
			$clean[ $f ] = max( 1, absint( $input[ $f ] ?? $defaults[ $f ] ) );
		}

		$bool_fields = array(
			'trust_forwarded_for',
			'track_404',
			'frontend_counter_enabled',
			'frontend_counter_show_guests',
			'enable_geo_lookup',
			'email_notifications_enabled',
			'enable_honeypot_suite',
			'enable_fingerprinting',
			'enable_abuseipdb',
			'enable_tor_block',
			'enable_subnet_block',
			'enable_webhook',
			'enable_html_email',
		);
		foreach ( $bool_fields as $f ) {
			$clean[ $f ] = ! empty( $input[ $f ] ) ? 1 : 0;
		}

		$clean['notification_email'] = '';
		if ( ! empty( $input['notification_email'] ) ) {
			$e = sanitize_email( wp_unslash( $input['notification_email'] ) );
			if ( is_email( $e ) ) {
				$clean['notification_email'] = $e;
			}
		}

		$clean['abuseipdb_api_key'] = isset( $input['abuseipdb_api_key'] ) ? sanitize_text_field( wp_unslash( $input['abuseipdb_api_key'] ) ) : '';
		$clean['webhook_url']       = isset( $input['webhook_url'] ) ? esc_url_raw( wp_unslash( $input['webhook_url'] ) ) : '';

		$allowed_roles = array( 'read', 'edit_posts', 'manage_options' );
		$role          = isset( $input['frontend_counter_role'] ) ? sanitize_key( wp_unslash( $input['frontend_counter_role'] ) ) : $defaults['frontend_counter_role'];
		$clean['frontend_counter_role'] = in_array( $role, $allowed_roles, true ) ? $role : $defaults['frontend_counter_role'];

		$allowed_positions = array( 'left', 'right' );
		$position           = isset( $input['frontend_counter_position'] ) ? sanitize_key( wp_unslash( $input['frontend_counter_position'] ) ) : $defaults['frontend_counter_position'];
		$clean['frontend_counter_position'] = in_array( $position, $allowed_positions, true ) ? $position : $defaults['frontend_counter_position'];

		$raw_ips   = isset( $input['whitelist_ips'] ) ? (string) wp_unslash( $input['whitelist_ips'] ) : '';
		$lines     = array_filter( array_map( 'trim', explode( "\n", $raw_ips ) ) );
		$valid_ips = array_filter( $lines, array( 'VISISE_IP', 'is_valid_ip' ) );
		$clean['whitelist_ips'] = implode( "\n", $valid_ips );

		return $clean;
	}
}
