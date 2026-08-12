<?php
declare( strict_types=1 );

namespace VisitorSentinel\Security;

class ChallengeManager {
	private const COOKIE_NAME = 'vs_chal_solved';
	private const NONCE_ACTION = 'visise_solve_challenge';

	public function shouldChallenge( int $score ): bool {
		$settings = \VISISE_Settings::get();
		$soft     = (int) ( $settings['challenge_threshold'] ?? 35 );
		$hard     = (int) ( $settings['score_threshold'] ?? 50 );
		return $score >= $soft && $score < $hard;
	}

	public function isChallengeSolved(): bool {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}
		$val = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		return hash_equals( $this->getExpectedHash(), $val );
	}

	public function getExpectedHash(): string {
		$ip = \VISISE_IP::get_client_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return hash_hmac( 'sha256', $ip . '|' . $ua . '|' . wp_salt( 'auth' ), wp_salt( 'secure_auth' ) );
	}

	public function renderChallengeAndExit(): void {
		if ( ! headers_sent() ) {
			status_header( 429 );
			nocache_headers();
		}
		?>
		<!doctype html>
		<html>
		<head><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php esc_html_e( 'Security Check', 'visitor-sentinel' ); ?></title>
		<style>
			body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f6f7fb;color:#16192b;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}
			.card{background:#fff;border:1px solid #e6e8f0;border-radius:16px;padding:40px;max-width:420px;width:100%;box-shadow:0 4px 16px rgba(22,25,43,.05);}
			h2{margin:0 0 10px;font-size:20px;}
			p{color:#5b607a;margin:0 0 20px;line-height:1.55;}
			.spinner{width:28px;height:28px;border:3px solid #eef1fe;border-top-color:#4f6ef7;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px;}
			@keyframes spin{to{transform:rotate(360deg);}}
		</style>
		</head>
		<body>
			<div class="card">
				<div class="spinner" aria-hidden="true"></div>
				<h2><?php esc_html_e( 'Verifying your browser...', 'visitor-sentinel' ); ?></h2>
				<p><?php esc_html_e( 'This takes just a moment.', 'visitor-sentinel' ); ?></p>
			</div>
			<script>
			(function(){
				document.cookie = "<?php echo esc_js( self::COOKIE_NAME ); ?>=<?php echo esc_js( $this->getExpectedHash() ); ?>; path=/; SameSite=Lax<?php echo ( is_ssl() ? '; Secure' : '' ); ?>";
				setTimeout(function(){ location.reload(); }, 1200);
			})();
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	public static function ajaxSolve(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$inst = new self();
		setcookie( self::COOKIE_NAME, $inst->getExpectedHash(), time() + HOUR_IN_SECONDS, '/', '', is_ssl(), true );
		wp_send_json_success();
	}
}
