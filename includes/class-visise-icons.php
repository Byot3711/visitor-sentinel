<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Icons {

	private static $icons = array(
		'gauge' => '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><path d="M12 6a6 6 0 0 0-6 6h2a4 4 0 0 1 8 0h2a6 6 0 0 0-6-6z"/>',
		'eye' => '<path d="M12 5C6.5 5 2.7 8.4 1 12c1.7 3.6 5.5 7 11 7s9.3-3.4 11-7c-1.7-3.6-5.5-7-11-7Z"/><circle cx="12" cy="12" r="3.2"/>',
		'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
		'radar' => '<path d="M12 2 4.5 6v6c0 5 3.3 8.4 7.5 9 4.2-.6 7.5-4 7.5-9V6Z"/><path d="M9.5 12.2 11 13.7l3.5-4"/>',
		'sliders' => '<path d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h6m2-6h6m2 8h6"/>',
		'shield' => '<path d="M12 3 4.5 6v6c0 5.2 3.6 9.7 9 11 5.4-1.3 9-5.8 9-11V6Z"/><path d="m9.5 12.2 1.5 1.5 3.5-4"/>',
		'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
		'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
		'mask' => '<path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2z"/><circle cx="12" cy="10" r="3"/><path d="M7 16s1.5-2 5-2 5 2 5 2"/>',
		'file-warning' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
		'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6"/><path d="M15.5 7.5l3 3L21 8l-3-3.5z"/>',
		'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
	);

	public static function render( $name, $size = 20 ) {
		$path = isset( self::$icons[ $name ] ) ? self::$icons[ $name ] : '';
		if ( ! $path ) {
			return;
		}
		printf(
			'<svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
			(int) $size,
			(int) $size,
			$path // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
