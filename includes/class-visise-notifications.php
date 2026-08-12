<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VISISE_Notifications {

	public static function notify_ban( $ip, $ban_type, $reason ) {
		$settings = VISISE_Settings::get();
		if ( empty( $settings['email_notifications_enabled'] ) ) {
			return;
		}
		$to = ! empty( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' );
		if ( empty( $to ) || ! is_email( $to ) ) {
			return;
		}
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject   = 'permanent' === $ban_type
			? sprintf( __( '[%s] IP permanently blocked', 'visitor-sentinel' ), $site_name )
			: sprintf( __( '[%s] IP temporarily blocked', 'visitor-sentinel' ), $site_name );

		$body  = sprintf( "%s\n\n", __( 'Visitor Sentinel blocked a visitor.', 'visitor-sentinel' ) );
		$body .= sprintf( "%s %s\n", __( 'IP:', 'visitor-sentinel' ), $ip );
		$body .= sprintf( "%s %s\n", __( 'Type:', 'visitor-sentinel' ), 'permanent' === $ban_type ? __( 'Permanent', 'visitor-sentinel' ) : __( 'Temporary', 'visitor-sentinel' ) );
		$body .= sprintf( "%s %s\n\n", __( 'Reason:', 'visitor-sentinel' ), wp_strip_all_tags( $reason ) );
		$body .= sprintf( "%s %s\n", __( 'Review:', 'visitor-sentinel' ), admin_url( 'admin.php?page=visise-bans&ip=' . rawurlencode( $ip ) ) );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( ! empty( $settings['enable_html_email'] ) ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$body    = nl2br( esc_html( $body ) );
			$body    = '<div style="font-family:sans-serif;line-height:1.6;">' . $body . '</div>';
		}

		wp_mail( $to, $subject, $body, $headers );
	}
}
