<?php
declare( strict_types=1 );

namespace VisitorSentinel\Admin;

class BulkActions {
	public static function register(): void {
		add_action( 'admin_post_visise_bulk_ban_action', array( self::class, 'handle' ) );
	}

	public static function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'visitor-sentinel' ) );
		}
		check_admin_referer( 'visise_bulk_nonce' );

		$do   = isset( $_POST['bulk_do'] ) ? sanitize_key( wp_unslash( $_POST['bulk_do'] ) ) : '';
		$ips  = isset( $_POST['ips'] ) && is_array( $_POST['ips'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ips'] ) ) : array();
		$notice = '';

		foreach ( $ips as $ip ) {
			if ( ! \VISISE_IP::is_valid_ip( $ip ) ) {
				continue;
			}
			switch ( $do ) {
				case 'unban':
					\VISISE_Ban::unban( $ip );
					$notice = 'unbanned';
					break;
				case 'whitelist':
					self::addWhitelist( $ip );
					\VISISE_Ban::unban( $ip );
					$notice = 'whitelisted';
					break;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'visise-bans', 'visise_notice' => $notice ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function addWhitelist( string $ip ): void {
		$settings = \VISISE_Settings::get();
		$list     = array_filter( array_map( 'trim', explode( "\n", $settings['whitelist_ips'] ) ) );
		if ( in_array( $ip, $list, true ) ) {
			return;
		}
		$list[]                    = $ip;
		$settings['whitelist_ips'] = implode( "\n", $list );
		\VISISE_Settings::update( $settings );
	}
}
