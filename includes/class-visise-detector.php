<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VisitorSentinel\Core\Enum\EventType;
use VisitorSentinel\Detection\RulesEngine;
use VisitorSentinel\Security\ChallengeManager;
use VisitorSentinel\ThreatIntelligence\AbuseIPDB;
use VisitorSentinel\ThreatIntelligence\TorChecker;
use VisitorSentinel\ThreatIntelligence\SubnetBlocker;

class VISISE_Detector {

	private RulesEngine $rulesEngine;
	private ChallengeManager $challengeManager;

	public function __construct() {
		$this->rulesEngine      = new RulesEngine();
		$this->challengeManager = new ChallengeManager();

		add_action( 'init', array( $this, 'handle_request' ), 1 );
		add_action( 'wp_login_failed', array( $this, 'handle_login_failed' ) );
		add_action( 'template_redirect', array( $this, 'handle_404' ) );
		add_filter( 'preprocess_comment', array( $this, 'handle_comment_submission' ) );
		add_action( 'login_form', array( $this, 'render_login_honeypot' ) );
		add_filter( 'authenticate', array( $this, 'check_login_honeypot' ), 30, 1 );
		add_action( 'comment_form', array( $this, 'render_comment_honeypot' ) );
		add_action( 'xmlrpc_call', array( $this, 'handle_xmlrpc_call' ) );
	}

	public function render_login_honeypot() {
		?>
		<p style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
			<label for="visise_website"><?php esc_html_e( 'Website', 'visitor-sentinel' ); ?></label>
			<input type="text" name="visise_website" id="visise_website" tabindex="-1" autocomplete="off" value="" />
		</p>
		<input type="hidden" name="visise_ts" value="<?php echo esc_attr( current_time( 'timestamp' ) ); ?>" />
		<?php
	}

	public function check_login_honeypot( $user ) {
		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return $user;
		}
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) || VISISE_IP::is_whitelisted( $ip ) ) {
			return $user;
		}
		$login_hp = isset( $_POST['visise_website'] ) ? sanitize_text_field( wp_unslash( $_POST['visise_website'] ) ) : '';
		$login_ts = isset( $_POST['visise_ts'] ) ? absint( wp_unslash( $_POST['visise_ts'] ) ) : 0;
		if ( ! empty( $login_hp ) ) {
			VISISE_Logger::log_event( $ip, EventType::HONEYPOT_TRIGGERED, __( 'Login honeypot filled.', 'visitor-sentinel' ), 50 );
			$this->maybe_ban( $ip );
		} elseif ( ! empty( $login_ts ) ) {
			$elapsed = current_time( 'timestamp' ) - $login_ts;
			if ( $elapsed >= 0 && $elapsed < 2 ) {
				VISISE_Logger::log_event( $ip, EventType::FAST_SUBMIT_BOT, __( 'Login submitted under 2s.', 'visitor-sentinel' ), 35 );
				$this->maybe_ban( $ip );
			}
		}
		return $user;
	}

	public function render_comment_honeypot() {
		?>
		<p style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
			<label for="visise_comment_hp"><?php esc_html_e( 'Leave this field empty', 'visitor-sentinel' ); ?></label>
			<input type="text" name="visise_comment_hp" id="visise_comment_hp" tabindex="-1" autocomplete="off" value="" />
		</p>
		<input type="hidden" name="visise_comment_ts" value="<?php echo esc_attr( current_time( 'timestamp' ) ); ?>" />
		<?php
	}

	public function handle_xmlrpc_call( $method ) {
		$abused = array( 'pingback.ping', 'pingback.extensions.getPingbacks', 'system.multicall' );
		if ( ! in_array( $method, $abused, true ) ) {
			return;
		}
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) || VISISE_IP::is_whitelisted( $ip ) ) {
			return;
		}
		VISISE_Logger::log_event( $ip, EventType::XMLRPC_ABUSE, sprintf( __( 'XML-RPC abuse: %s', 'visitor-sentinel' ), $method ), 35 );
		$this->maybe_ban( $ip );
	}

	public function handle_request() {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) ) {
			return;
		}
		$ban = VISISE_Ban::find_active_for_request( $ip );
		if ( $ban ) {
			VISISE_Ban::register_hit_while_banned( $ban->ip );
			$this->block_visitor( $ban );
			return;
		}
		$is_trusted_admin = is_user_logged_in() && current_user_can( 'manage_options' );
		if ( is_admin() && ! is_user_logged_in() ) {
			return;
		}
		if ( VISISE_IP::is_whitelisted( $ip ) ) {
			return;
		}
		$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$referer     = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		if ( $is_trusted_admin ) {
			VisitorSentinel\Core\AsyncLogger::logVisit( $ip, $user_agent, $request_uri, $referer, true );
			VISISE_Logger::heartbeat( $ip );
			return;
		}
		VisitorSentinel\Core\AsyncLogger::logVisit( $ip, $user_agent, $request_uri, $referer, is_user_logged_in() );
		VISISE_Logger::heartbeat( $ip );

		$this->analyze( $ip, $user_agent, $request_uri );
	}

	private function analyze( $ip, $user_agent, $request_uri ) {
		$settings = VISISE_Settings::get();

		$hits = $this->rulesEngine->evaluate( $request_uri, $user_agent, $_SERVER );
		foreach ( $hits as $hit ) {
			$desc = sprintf( __( 'Rule %1$s matched: %2$s', 'visitor-sentinel' ), $hit['rule_id'], $hit['matched'] );
			VISISE_Logger::log_event( $ip, $hit['type'], $desc, $hit['score'] );
		}

		if ( AbuseIPDB::isKnownBad( $ip ) && ! VISISE_Logger::has_recent_event( $ip, EventType::ABUSEIPDB_HIT, HOUR_IN_SECONDS ) ) {
			VISISE_Logger::log_event( $ip, EventType::ABUSEIPDB_HIT, __( 'IP reported by AbuseIPDB.', 'visitor-sentinel' ), 20 );
		}
		if ( TorChecker::isTorExit( $ip ) && ! VISISE_Logger::has_recent_event( $ip, EventType::TOR_EXIT_NODE, HOUR_IN_SECONDS ) ) {
			VISISE_Logger::log_event( $ip, EventType::TOR_EXIT_NODE, __( 'Tor exit node detected.', 'visitor-sentinel' ), 15 );
		}

		$ua_lower = strtolower( $user_agent );
		if ( '' === trim( $user_agent ) ) {
			VISISE_Logger::log_event( $ip, EventType::EMPTY_USER_AGENT, __( 'Empty User-Agent.', 'visitor-sentinel' ), 15 );
		}

		$accept_header = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		if ( ! empty( $user_agent ) && empty( $accept_header ) && ! VISISE_Logger::has_recent_event( $ip, EventType::NON_BROWSER_CLIENT, 300 ) ) {
			VISISE_Logger::log_event( $ip, EventType::NON_BROWSER_CLIENT, __( 'No Accept header.', 'visitor-sentinel' ), 10 );
		}

		$requests = VISISE_Logger::track_request_atomic( $ip, (int) $settings['rate_limit_seconds'] );
		if ( $requests > (int) $settings['rate_limit_requests'] && ! VISISE_Logger::has_recent_event( $ip, EventType::RATE_LIMIT, 60 ) ) {
			VISISE_Logger::log_event( $ip, EventType::RATE_LIMIT, sprintf( __( 'High rate: %d requests.', 'visitor-sentinel' ), $requests ), 15 );
		}

		$burst = VISISE_Logger::track_request_atomic( $ip . '_burst', 8 );
		if ( $burst > 40 && ! VISISE_Logger::has_recent_event( $ip, EventType::TRAFFIC_FLOOD, 30 ) ) {
			VISISE_Logger::log_event( $ip, EventType::TRAFFIC_FLOOD, sprintf( __( 'Traffic flood: %d req in 8s.', 'visitor-sentinel' ), $burst ), 60 );
		}

		$this->maybe_ban( $ip );
	}

	public function handle_login_failed( $username ) {
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) || VISISE_IP::is_whitelisted( $ip ) ) {
			return;
		}
		VISISE_Logger::log_event( $ip, EventType::LOGIN_FAILED, __( 'Failed login.', 'visitor-sentinel' ), 15 );
		$attempts = VISISE_Logger::track_login_attempt( $ip, 60 );
		if ( $attempts > 5 && ! VISISE_Logger::has_recent_event( $ip, EventType::BRUTE_FORCE_LOGIN, 60 ) ) {
			VISISE_Logger::log_event( $ip, EventType::BRUTE_FORCE_LOGIN, sprintf( __( 'Brute-force: %d failed logins in 60s.', 'visitor-sentinel' ), $attempts ), 40 );
		}
		if ( ! empty( $username ) ) {
			$distinct = VISISE_Logger::track_login_username_and_get_distinct_count( $ip, $username, 600 );
			if ( $distinct > 3 && ! VISISE_Logger::has_recent_event( $ip, EventType::CREDENTIAL_STUFFING, 300 ) ) {
				VISISE_Logger::log_event( $ip, EventType::CREDENTIAL_STUFFING, sprintf( __( 'Credential stuffing: %d distinct usernames.', 'visitor-sentinel' ), $distinct ), 40 );
			}
		}
		$this->maybe_ban( $ip );
	}

	public function handle_404() {
		$settings = VISISE_Settings::get();
		if ( empty( $settings['track_404'] ) || ! is_404() ) {
			return;
		}
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) || VISISE_IP::is_whitelisted( $ip ) ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return;
		}
		VISISE_Logger::log_event( $ip, EventType::NOT_FOUND, __( '404 access.', 'visitor-sentinel' ), 5 );
		$count = VISISE_Logger::count_404_in_window( $ip, 300 );
		if ( $count > 30 && ! VISISE_Logger::has_recent_event( $ip, EventType::NOT_FOUND_FLOOD, 300 ) ) {
			VISISE_Logger::log_event( $ip, EventType::NOT_FOUND_FLOOD, sprintf( __( '404 flood: %d in 5 min.', 'visitor-sentinel' ), $count ), 10 );
		}
		$this->maybe_ban( $ip );
	}

	public function handle_comment_submission( $commentdata ) {
		$ip = VISISE_IP::get_client_ip();
		if ( empty( $ip ) || VISISE_IP::is_whitelisted( $ip ) ) {
			return $commentdata;
		}
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return $commentdata;
		}

		$comment_hp = isset( $_POST['visise_comment_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['visise_comment_hp'] ) ) : '';
		$comment_ts = isset( $_POST['visise_comment_ts'] ) ? absint( wp_unslash( $_POST['visise_comment_ts'] ) ) : 0;
		if ( ! empty( $comment_hp ) ) {
			VISISE_Logger::log_event( $ip, EventType::HONEYPOT_TRIGGERED, __( 'Comment honeypot filled.', 'visitor-sentinel' ), 50 );
		} elseif ( ! empty( $comment_ts ) ) {
			$elapsed = current_time( 'timestamp' ) - $comment_ts;
			if ( $elapsed >= 0 && $elapsed < 2 ) {
				VISISE_Logger::log_event( $ip, EventType::FAST_SUBMIT_BOT, __( 'Comment under 2s.', 'visitor-sentinel' ), 35 );
			}
		}

		$content    = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$link_count = preg_match_all( '#https?://#i', $content, $matches );
		if ( $link_count >= 3 ) {
			VISISE_Logger::log_event( $ip, EventType::COMMENT_SPAM, sprintf( __( 'Comment with %d links.', 'visitor-sentinel' ), $link_count ), 25 );
		}

		$spam_keywords = array( 'viagra', 'casino', 'porn', 'xxx', 'crypto airdrop', 'loan approved', 'bitcoin doubler', 'verify your account', 'urgent action required' );
		$content_lower = strtolower( $content );
		foreach ( $spam_keywords as $keyword ) {
			if ( false !== strpos( $content_lower, $keyword ) ) {
				VISISE_Logger::log_event( $ip, EventType::COMMENT_SPAM_KEYWORD, sprintf( __( 'Spam keyword: %s', 'visitor-sentinel' ), $keyword ), 25 );
				break;
			}
		}
		$this->maybe_ban( $ip );
		if ( VISISE_Ban::is_banned( $ip ) ) {
			wp_die( esc_html__( 'Comment rejected for security reasons.', 'visitor-sentinel' ), esc_html__( 'Comment rejected', 'visitor-sentinel' ), array( 'response' => 403 ) );
		}
		return $commentdata;
	}

	private $high_confidence_event_types = array(
		'suspicious_user_agent',
		'suspicious_request',
		'suspicious_query',
		'comment_spam',
		'comment_spam_keyword',
		'login_failed',
		'honeypot_triggered',
		'fast_submit_bot',
		'xmlrpc_abuse',
		'brute_force_login',
		'credential_stuffing',
		'traffic_flood',
		'honeytoken_api_key_used',
		'honeytoken_username_used',
		'honeytoken_email_harvested',
		'honeyfile_accessed',
		'abuseipdb_hit',
		'tor_exit_node',
	);

	private $soft_event_types = array(
		'not_found',
		'not_found_flood',
		'rate_limit',
		'empty_user_agent',
		'non_browser_client',
	);

	private function maybe_ban( $ip ) {
		$settings = VISISE_Settings::get();
		$score    = VISISE_Logger::get_score_for_ip( $ip, 60 );

		if ( $score < (int) $settings['score_threshold'] ) {
			if ( $this->challengeManager->shouldChallenge( $score ) ) {
				if ( ! $this->challengeManager->isChallengeSolved() ) {
					$this->challengeManager->renderChallengeAndExit();
				}
			}
			return;
		}

		$recent_events = VISISE_Logger::get_events_for_ip( $ip, 20 );
		$event_types   = array_unique( wp_list_pluck( $recent_events, 'event_type' ) );
		$has_high      = (bool) array_intersect( $event_types, $this->high_confidence_event_types );
		$meaningful    = array_diff( $event_types, $this->soft_event_types );
		if ( ! $has_high && count( $meaningful ) < 2 ) {
			return;
		}

		$reasons = wp_list_pluck( array_slice( $recent_events, 0, 5 ), 'description' );
		$reason  = implode( ' | ', array_unique( $reasons ) );
		if ( empty( $reason ) ) {
			$reason = __( 'High risk score.', 'visitor-sentinel' );
		}

		VISISE_Ban::apply_ban( $ip, $reason, $score );
		SubnetBlocker::checkAndBan( $ip );
	}

	private function block_visitor( $ban ) {
		if ( ! headers_sent() ) {
			status_header( 403 );
			nocache_headers();
		}
		VISISE_Ban::set_device_cookie( $ban );
		wp_die(
			self::build_block_page_html( $ban ),
			esc_html( self::block_page_title( $ban ) ),
			array( 'response' => 403 )
		);
	}

	public static function block_page_title( $ban ) {
		return 'permanent' === $ban->ban_type
			? __( 'Access permanently blocked', 'visitor-sentinel' )
			: __( 'Access temporarily blocked', 'visitor-sentinel' );
	}

	public static function build_block_page_html( $ban ) {
		$is_permanent = 'permanent' === $ban->ban_type;
		$events       = VISISE_Logger::get_events_for_ip( $ban->ip, 10 );
		ob_start();
		?>
		<style>
			html,body{height:100%;margin:0;}
			body{background:linear-gradient(160deg,#0d1526 0%,#101d33 55%,#132a45 100%);}
			.visise-block-overlay{position:fixed;inset:0;display:flex;align-items:flex-start;justify-content:center;background:linear-gradient(160deg,#0d1526 0%,#101d33 55%,#132a45 100%);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;padding:48px 16px;box-sizing:border-box;overflow-y:auto;z-index:2147483647;}
			.visise-block-card{width:100%;max-width:640px;background:#131f36;border:1px solid #26364f;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.35);padding:40px 36px;box-sizing:border-box;}
			.visise-block-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?php echo $is_permanent ? 'rgba(224,48,63,.15)' : 'rgba(217,119,6,.15)'; ?>;margin-bottom:20px;}
			.visise-block-title{color:#fff;font-size:26px;font-weight:800;letter-spacing:-.01em;margin:0 0 10px;}
			.visise-block-lead{color:#a9b8ce;font-size:15px;line-height:1.65;margin:0 0 22px;}
			.visise-block-row{display:flex;gap:8px;font-size:14.5px;color:#c7d6e8;margin:0 0 10px;}
			.visise-block-row strong{color:#fff;font-weight:600;min-width:150px;flex-shrink:0;}
			.visise-block-divider{height:1px;background:#26364f;margin:22px 0;}
			.visise-block-code-label{color:#8b93a7;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;}
			.visise-block-code{background:#0d1526;border:1px solid #26364f;border-radius:10px;padding:16px 18px;font-family:Consolas,'SFMono-Regular',Menlo,monospace;font-size:12.5px;line-height:1.9;color:#c7d6e8;overflow-x:auto;}
			.visise-block-code .type{color:#7fc4ff;}
			.visise-block-code .time{color:#67799a;}
			.visise-block-footer{color:#67799a;font-size:12.5px;margin-top:22px;}
		</style>
		<div class="visise-block-overlay">
		<div class="visise-block-card">
			<div class="visise-block-icon">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="<?php echo $is_permanent ? '#ff6b76' : '#f0a83c'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v6c0 5 3.3 8.4 7.5 9 4.2-.6 7.5-4 7.5-9V6Z"/><path d="M9.5 12.2 11 13.7l3.5-4"/></svg>
			</div>
			<h1 class="visise-block-title"><?php echo $is_permanent ? esc_html__( 'Access Permanently Banned', 'visitor-sentinel' ) : esc_html__( 'Access Temporarily Banned', 'visitor-sentinel' ); ?></h1>
			<p class="visise-block-lead"><?php echo $is_permanent ? esc_html__( 'This IP has been permanently blocked due to attack patterns.', 'visitor-sentinel' ) : esc_html__( 'This IP has been temporarily blocked.', 'visitor-sentinel' ); ?></p>
			<?php if ( ! $is_permanent && ! empty( $ban->expires_at ) ) : ?>
				<div class="visise-block-row"><strong><?php esc_html_e( 'Block lifts at', 'visitor-sentinel' ); ?></strong> <?php echo esc_html( mysql2date( 'd.m.Y H:i', $ban->expires_at ) ); ?></div>
			<?php endif; ?>
			<div class="visise-block-row"><strong><?php esc_html_e( 'Main reason', 'visitor-sentinel' ); ?></strong> <?php echo esc_html( $ban->reason ); ?></div>
			<?php if ( ! empty( $events ) ) : ?>
				<div class="visise-block-divider"></div>
				<p class="visise-block-code-label"><?php esc_html_e( 'Precise reason (technical log)', 'visitor-sentinel' ); ?></p>
				<div class="visise-block-code">
					<?php foreach ( $events as $event ) : ?>
						<div>[<span class="time"><?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $event->created_at ) ); ?></span>] <span class="type"><?php echo esc_html( $event->event_type ); ?></span>: <?php echo esc_html( $event->description ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p class="visise-block-footer"><?php esc_html_e( 'If you believe this is a mistake, please contact the site owner.', 'visitor-sentinel' ); ?></p>
		</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
