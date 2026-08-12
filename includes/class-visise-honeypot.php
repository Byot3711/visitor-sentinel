<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Honeypot {

	const HONEYFILE_QUERY_VAR = 'visise_honeyfile';

	public static function get_honeyfile_slug() {
		$slug = get_option( 'visise_honeyfile_slug' );
		if ( ! $slug ) {
			$slug = 'readme-' . wp_generate_password( 8, false, false );
			update_option( 'visise_honeyfile_slug', $slug );
		}
		return $slug;
	}

	public static function get_decoy_username() {
		$username = get_option( 'visise_decoy_username' );
		if ( ! $username ) {
			$username = 'admin-' . wp_generate_password( 6, false, false );
			update_option( 'visise_decoy_username', $username );
		}
		return $username;
	}

	public static function get_trap_email() {
		$email = get_option( 'visise_trap_email' );
		if ( ! $email ) {
			$domain = wp_parse_url( home_url(), PHP_URL_HOST );
			$email  = 'trap-' . wp_generate_password( 6, false, false ) . '@' . $domain;
			update_option( 'visise_trap_email', $email );
		}
		return $email;
	}

	public static function get_decoy_api_key() {
		$key = get_option( 'visise_decoy_api_key' );
		if ( ! $key ) {
			$key = 'vs_' . wp_generate_password( 32, false, false );
			update_option( 'visise_decoy_api_key', $key );
		}
		return $key;
	}

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_honeyfile' ), 0 );
		add_filter( 'rest_endpoints', array( $this, 'add_decoy_rest_endpoint' ) );
	}

	public function handle_honeyfile() {
		if ( '1' !== get_query_var( self::HONEYFILE_QUERY_VAR ) ) {
			return;
		}
		$ip = VISISE_IP::get_client_ip();
		if ( ! empty( $ip ) && ! VISISE_IP::is_whitelisted( $ip ) && ! ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
			VISISE_Logger::log_event( $ip, 'honeyfile_accessed', __( 'Accessed honeyfile.', 'visitor-sentinel' ), 60 );
			VISISE_Ban::apply_ban( $ip, __( 'Accessed honeyfile.', 'visitor-sentinel' ), 60 );
		}
		status_header( 403 );
		nocache_headers();
		echo 'Access denied.';
		exit;
	}

	public function add_decoy_rest_endpoint( $endpoints ) {
		register_rest_route(
			'visise/v1',
			'/config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'decoy_api_callback' ),
				'permission_callback' => '__return_true',
			)
		);
		return $endpoints;
	}

	public function decoy_api_callback() {
		$ip = VISISE_IP::get_client_ip();
		if ( ! empty( $ip ) && ! VISISE_IP::is_whitelisted( $ip ) ) {
			VISISE_Logger::log_event( $ip, 'honeytoken_api_key_used', __( 'Used decoy API endpoint.', 'visitor-sentinel' ), 50 );
			VISISE_Ban::apply_ban( $ip, __( 'Used decoy API endpoint.', 'visitor-sentinel' ), 50 );
		}
		return new WP_Error( 'not_found', __( 'Not found.', 'visitor-sentinel' ), array( 'status' => 404 ) );
	}
}
