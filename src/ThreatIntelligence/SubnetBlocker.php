<?php
declare( strict_types=1 );

namespace VisitorSentinel\ThreatIntelligence;

class SubnetBlocker {
	public static function checkAndBan( string $ip ): void {
		$settings = \VISISE_Settings::get();
		if ( empty( $settings['enable_subnet_block'] ) ) {
			return;
		}
		$subnet = self::getSubnet( $ip );
		if ( ! $subnet ) {
			return;
		}
		if ( \VISISE_Ban::get_subnet_ban( $subnet ) ) {
			return;
		}
		$count = \VISISE_Ban::count_active_in_subnet( $subnet );
		if ( $count >= 3 ) {
			\VISISE_Ban::apply_subnet_ban( $subnet, __( 'Auto-banned: subnet reached 3 active bans.', 'visitor-sentinel' ) );
		}
	}

	public static function getSubnet( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			return implode( '.', array_slice( $parts, 0, 3 ) ) . '.0/24';
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			return implode( ':', array_slice( $parts, 0, 4 ) ) . '::/64';
		}
		return '';
	}
}
