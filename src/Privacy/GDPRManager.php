<?php
declare( strict_types=1 );

namespace VisitorSentinel\Privacy;

class GDPRManager {
	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'registerEraser' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'registerExporter' ) );
	}

	public static function registerEraser( array $erasers ): array {
		$erasers['visitor-sentinel'] = array(
			'eraser_friendly_name' => __( 'Visitor Sentinel logs', 'visitor-sentinel' ),
			'callback'             => array( self::class, 'erase' ),
		);
		return $erasers;
	}

	public static function registerExporter( array $exporters ): array {
		$exporters['visitor-sentinel'] = array(
			'exporter_friendly_name' => __( 'Visitor Sentinel logs', 'visitor-sentinel' ),
			'callback'               => array( self::class, 'export' ),
		);
		return $exporters;
	}

	public static function erase( string $email, int $page = 1 ): array {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array( __( 'Visitor Sentinel stores data by IP address. If you provide the IP address to the administrator, it can be removed manually.', 'visitor-sentinel' ) ),
			'done'           => true,
		);
	}

	public static function export( string $email, int $page = 1 ): array {
		return array(
			'data' => array(),
			'done' => true,
		);
	}
}
