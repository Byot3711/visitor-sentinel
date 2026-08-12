<?php
declare( strict_types=1 );

namespace VisitorSentinel\Admin;

class LiveStream {
	public static function register(): void {
		add_action( 'wp_ajax_visise_sse_stream', array( self::class, 'stream' ) );
	}

	public static function stream(): void {
		check_ajax_referer( 'visise_sse', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', '1' );
		}
		@ini_set( 'zlib.output_compression', '0' );
		@ini_set( 'implicit_flush', '1' );
		for ( $i = 0; $i < ob_get_level(); $i++ ) {
			ob_end_flush();
		}
		ob_implicit_flush( true );

		$lastHash = '';
		$start    = time();
		while ( time() - $start < 30 ) {
			$online = \VISISE_Logger::count_online();
			$visits = \VISISE_Logger::get_recent_visits( 30, 0 );

			ob_start();
			include \VISISE_PLUGIN_DIR . 'includes/views/partials/visitors-rows.php';
			$html = ob_get_clean();

			$payload = wp_json_encode(
				array(
					'online'        => $online,
					'onlineText'    => sprintf( _n( '%s online', '%s online', $online, 'visitor-sentinel' ), number_format_i18n( $online ) ),
					'visitorsHtml'  => $html,
					'visitorsEmpty' => empty( $visits ),
					'time'          => current_time( 'mysql' ),
				)
			);

			if ( md5( $payload ) !== $lastHash ) {
				echo "data: {$payload}\n\n";
				if ( ob_get_level() ) {
					ob_flush();
				}
				flush();
				$lastHash = md5( $payload );
			}
			sleep( 5 );
		}
	}
}
