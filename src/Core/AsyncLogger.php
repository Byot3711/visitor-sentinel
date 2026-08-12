<?php
declare( strict_types=1 );

namespace VisitorSentinel\Core;

class AsyncLogger {
	public static function init(): void {
		add_action( 'visise_async_log_visit', array( self::class, 'handleLogVisit' ), 10, 5 );
		add_action( 'visise_async_log_event', array( self::class, 'handleLogEvent' ), 10, 4 );
	}

	public static function logVisit( string $ip, string $ua, string $uri, string $referer, bool $loggedIn ): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'visise_async_log_visit', array( $ip, $ua, $uri, $referer, $loggedIn ), 'visitor-sentinel' );
		} else {
			self::handleLogVisit( $ip, $ua, $uri, $referer, $loggedIn );
		}
	}

	public static function handleLogVisit( string $ip, string $ua, string $uri, string $referer, bool $loggedIn ): void {
		\VISISE_Logger::log_visit( $ip, $ua, $uri, $referer, $loggedIn );
	}

	public static function logEvent( string $ip, string $type, string $desc, int $score ): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'visise_async_log_event', array( $ip, $type, $desc, $score ), 'visitor-sentinel' );
		} else {
			self::handleLogEvent( $ip, $type, $desc, $score );
		}
	}

	public static function handleLogEvent( string $ip, string $type, string $desc, int $score ): void {
		\VISISE_Logger::log_event( $ip, $type, $desc, $score );
	}
}
