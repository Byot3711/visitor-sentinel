<?php
declare( strict_types=1 );

namespace VisitorSentinel\Deception;

class ExtendedHoneypot {
	public function __construct() {
		add_action( 'init', array( $this, 'registerFakeAdmin' ) );
	}

	public function registerFakeAdmin(): void {
		$slug = get_option( 'visise_fake_admin_slug' );
		if ( empty( $slug ) ) {
			$slug = 'wp-admin-' . wp_generate_password( 6, false, false );
			update_option( 'visise_fake_admin_slug', $slug );
		}
		add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?visise_fake_admin=1', 'top' );
		if ( get_option( 'visise_fake_admin_version' ) !== VISISE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'visise_fake_admin_version', VISISE_VERSION );
		}
		add_action( 'template_redirect', array( $this, 'maybeTrapFakeAdmin' ), 0 );
	}

	public function maybeTrapFakeAdmin(): void {
		if ( '1' !== get_query_var( 'visise_fake_admin' ) ) {
			return;
		}
		$ip = \VISISE_IP::get_client_ip();
		if ( ! empty( $ip ) && ! \VISISE_IP::is_whitelisted( $ip ) && ! ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
			\VISISE_Logger::log_event( $ip, 'honeyfile_accessed', __( 'Accessed decoy admin panel URL.', 'visitor-sentinel' ), 100 );
			\VISISE_Ban::apply_ban( $ip, __( 'Accessed decoy admin panel.', 'visitor-sentinel' ), 100 );
		}
		nocache_headers();
		status_header( 403 );
		?>
		<!doctype html><html><head><title>403</title></head><body style="font-family:sans-serif;text-align:center;padding:10vh;">
		<h1>403 Forbidden</h1><p>If you see this, you should not be here.</p>
		</body></html>
		<?php
		exit;
	}
}
