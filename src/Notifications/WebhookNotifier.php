<?php
declare( strict_types=1 );

namespace VisitorSentinel\Notifications;

class WebhookNotifier {
	public static function notify( string $ip, string $type, string $reason, int $score ): void {
		$settings = \VISISE_Settings::get();
		if ( empty( $settings['enable_webhook'] ) || empty( $settings['webhook_url'] ) ) {
			return;
		}
		wp_remote_post(
			$settings['webhook_url'],
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'event'   => 'ip_blocked',
						'site'    => home_url(),
						'ip'      => $ip,
						'type'    => $type,
						'reason'  => $reason,
						'score'   => $score,
						'time'    => current_time( 'mysql' ),
					)
				),
				'timeout' => 5,
			)
		);
	}
}
