<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pv-wrap">
	<h1 class="pv-title"><?php esc_html_e( 'Settings', 'visitor-sentinel' ); ?></h1>
	<p class="pv-subtitle"><?php esc_html_e( 'Configure detection, intelligence, alerts and privacy.', 'visitor-sentinel' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="visise-settings-form">
		<?php wp_nonce_field( 'visise_settings_nonce' ); ?>
		<input type="hidden" name="action" value="visise_save_settings" />

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'sliders' ); ?></span>
				<div><h2><?php esc_html_e( 'Automatic detection', 'visitor-sentinel' ); ?></h2><p><?php esc_html_e( 'Thresholds and behaviour.', 'visitor-sentinel' ); ?></p></div>
			</header>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pv_rate_limit_requests"><?php esc_html_e( 'Request threshold', 'visitor-sentinel' ); ?></label></th>
					<td><input type="number" min="1" id="pv_rate_limit_requests" name="visise_settings[rate_limit_requests]" value="<?php echo esc_attr( $settings['rate_limit_requests'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_rate_limit_seconds"><?php esc_html_e( 'Interval (seconds)', 'visitor-sentinel' ); ?></label></th>
					<td><input type="number" min="1" id="pv_rate_limit_seconds" name="visise_settings[rate_limit_seconds]" value="<?php echo esc_attr( $settings['rate_limit_seconds'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_score_threshold"><?php esc_html_e( 'Risk score threshold for blocking', 'visitor-sentinel' ); ?></label></th>
					<td><input type="number" min="1" id="pv_score_threshold" name="visise_settings[score_threshold]" value="<?php echo esc_attr( $settings['score_threshold'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_challenge_threshold"><?php esc_html_e( 'Challenge threshold (soft block)', 'visitor-sentinel' ); ?></label></th>
					<td>
						<input type="number" min="1" id="pv_challenge_threshold" name="visise_settings[challenge_threshold]" value="<?php echo esc_attr( $settings['challenge_threshold'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Between challenge and ban: visitors must pass a JS browser verification.', 'visitor-sentinel' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_track_404"><?php esc_html_e( 'Track 404s', 'visitor-sentinel' ); ?></label></th>
					<td><label class="pv-toggle-row"><input type="checkbox" id="pv_track_404" name="visise_settings[track_404]" value="1" <?php checked( ! empty( $settings['track_404'] ) ); ?> /> <?php esc_html_e( 'Monitor non-existent pages for scanning behaviour.', 'visitor-sentinel' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_trust_forwarded"><?php esc_html_e( 'Site behind proxy/CDN', 'visitor-sentinel' ); ?></label></th>
					<td><label class="pv-toggle-row"><input type="checkbox" id="pv_trust_forwarded" name="visise_settings[trust_forwarded_for]" value="1" <?php checked( ! empty( $settings['trust_forwarded_for'] ) ); ?> /> <?php esc_html_e( 'Use X-Forwarded-For header.', 'visitor-sentinel' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_whitelist"><?php esc_html_e( 'IP whitelist', 'visitor-sentinel' ); ?></label></th>
					<td><textarea id="pv_whitelist" name="visise_settings[whitelist_ips]" rows="5" cols="40" class="large-text code"><?php echo esc_textarea( $settings['whitelist_ips'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_retention"><?php esc_html_e( 'Keep data for (days)', 'visitor-sentinel' ); ?></label></th>
					<td><input type="number" min="1" id="pv_retention" name="visise_settings[retention_days]" value="<?php echo esc_attr( $settings['retention_days'] ); ?>" /></td>
				</tr>
			</table>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'radar' ); ?></span>
				<div><h2><?php esc_html_e( 'Threat Intelligence', 'visitor-sentinel' ); ?></h2><p><?php esc_html_e( 'External sources and network blocking.', 'visitor-sentinel' ); ?></p></div>
			</header>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pv_abuseipdb"><?php esc_html_e( 'AbuseIPDB', 'visitor-sentinel' ); ?></label></th>
					<td>
						<label class="pv-toggle-row"><input type="checkbox" id="pv_abuseipdb" name="visise_settings[enable_abuseipdb]" value="1" <?php checked( ! empty( $settings['enable_abuseipdb'] ) ); ?> /> <?php esc_html_e( 'Check IPs against AbuseIPDB.', 'visitor-sentinel' ); ?></label>
						<p><input type="text" name="visise_settings[abuseipdb_api_key]" class="regular-text" placeholder="API Key" value="<?php echo esc_attr( $settings['abuseipdb_api_key'] ); ?>" /></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_tor"><?php esc_html_e( 'Tor blocking', 'visitor-sentinel' ); ?></label></th>
					<td><label class="pv-toggle-row"><input type="checkbox" id="pv_tor" name="visise_settings[enable_tor_block]" value="1" <?php checked( ! empty( $settings['enable_tor_block'] ) ); ?> /> <?php esc_html_e( 'Detect and penalize Tor exit nodes.', 'visitor-sentinel' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_subnet"><?php esc_html_e( 'Auto subnet block', 'visitor-sentinel' ); ?></label></th>
					<td>
						<label class="pv-toggle-row"><input type="checkbox" id="pv_subnet" name="visise_settings[enable_subnet_block]" value="1" <?php checked( ! empty( $settings['enable_subnet_block'] ) ); ?> /> <?php esc_html_e( 'Ban /24 subnet when 3+ IPs in it are blocked.', 'visitor-sentinel' ); ?></label>
					</td>
				</tr>
			</table>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'bell' ); ?></span>
				<div><h2><?php esc_html_e( 'Email alerts & Webhooks', 'visitor-sentinel' ); ?></h2></div>
			</header>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pv_email_alerts"><?php esc_html_e( 'Email on block', 'visitor-sentinel' ); ?></label></th>
					<td><label class="pv-toggle-row"><input type="checkbox" id="pv_email_alerts" name="visise_settings[email_notifications_enabled]" value="1" <?php checked( ! empty( $settings['email_notifications_enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'visitor-sentinel' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_html_email"><?php esc_html_e( 'HTML email', 'visitor-sentinel' ); ?></label></th>
					<td><label class="pv-toggle-row"><input type="checkbox" id="pv_html_email" name="visise_settings[enable_html_email]" value="1" <?php checked( ! empty( $settings['enable_html_email'] ) ); ?> /> <?php esc_html_e( 'Send rich HTML notifications.', 'visitor-sentinel' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_notification_email"><?php esc_html_e( 'Send alerts to', 'visitor-sentinel' ); ?></label></th>
					<td><input type="email" id="pv_notification_email" name="visise_settings[notification_email]" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_webhook"><?php esc_html_e( 'Webhook URL', 'visitor-sentinel' ); ?></label></th>
					<td>
						<label class="pv-toggle-row"><input type="checkbox" id="pv_webhook" name="visise_settings[enable_webhook]" value="1" <?php checked( ! empty( $settings['enable_webhook'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'visitor-sentinel' ); ?></label>
						<p><input type="url" id="pv_webhook_url" name="visise_settings[webhook_url]" class="regular-text" value="<?php echo esc_attr( $settings['webhook_url'] ); ?>" /></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'eye' ); ?></span>
				<div><h2><?php esc_html_e( 'On-site visitor counter', 'visitor-sentinel' ); ?></h2></div>
			</header>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="pv_counter_enabled"><?php esc_html_e( 'Show counter', 'visitor-sentinel' ); ?></label></th><td><label class="pv-toggle-row"><input type="checkbox" id="pv_counter_enabled" name="visise_settings[frontend_counter_enabled]" value="1" <?php checked( ! empty( $settings['frontend_counter_enabled'] ) ); ?> /> <?php esc_html_e( 'Display live visitor badge.', 'visitor-sentinel' ); ?></label></td></tr>
				<tr><th scope="row"><label for="pv_counter_show_guests"><?php esc_html_e( 'Show to guests', 'visitor-sentinel' ); ?></label></th><td><label class="pv-toggle-row"><input type="checkbox" id="pv_counter_show_guests" name="visise_settings[frontend_counter_show_guests]" value="1" <?php checked( ! empty( $settings['frontend_counter_show_guests'] ) ); ?> /> <?php esc_html_e( 'Show badge to non-logged-in users.', 'visitor-sentinel' ); ?></label></td></tr>
				<tr><th scope="row"><label for="pv_counter_role"><?php esc_html_e( 'Visible to role', 'visitor-sentinel' ); ?></label></th><td><select id="pv_counter_role" name="visise_settings[frontend_counter_role]"><option value="read" <?php selected( $settings['frontend_counter_role'], 'read' ); ?>><?php esc_html_e( 'Any logged-in member', 'visitor-sentinel' ); ?></option><option value="edit_posts" <?php selected( $settings['frontend_counter_role'], 'edit_posts' ); ?>><?php esc_html_e( 'Editors and authors', 'visitor-sentinel' ); ?></option><option value="manage_options" <?php selected( $settings['frontend_counter_role'], 'manage_options' ); ?>><?php esc_html_e( 'Administrators only', 'visitor-sentinel' ); ?></option></select></td></tr>
				<tr><th scope="row"><label for="pv_counter_position"><?php esc_html_e( 'Badge position', 'visitor-sentinel' ); ?></label></th><td><select id="pv_counter_position" name="visise_settings[frontend_counter_position]"><option value="left" <?php selected( $settings['frontend_counter_position'], 'left' ); ?>><?php esc_html_e( 'Bottom left', 'visitor-sentinel' ); ?></option><option value="right" <?php selected( $settings['frontend_counter_position'], 'right' ); ?>><?php esc_html_e( 'Bottom right', 'visitor-sentinel' ); ?></option></select></td></tr>
			</table>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'mask' ); ?></span>
				<div><h2><?php esc_html_e( 'Deception layer', 'visitor-sentinel' ); ?></h2></div>
			</header>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="pv_honeypot_suite"><?php esc_html_e( 'Enable deception', 'visitor-sentinel' ); ?></label></th><td><label class="pv-toggle-row"><input type="checkbox" id="pv_honeypot_suite" name="visise_settings[enable_honeypot_suite]" value="1" <?php checked( ! empty( $settings['enable_honeypot_suite'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'visitor-sentinel' ); ?></label></td></tr>
			</table>
			<?php if ( ! empty( $settings['enable_honeypot_suite'] ) && class_exists( 'VISISE_Honeypot' ) ) : ?>
				<table class="form-table pv-honeypot-tokens" role="presentation">
					<tr><th scope="row"><span class="pv-th-icon"><?php VISISE_Icons::render( 'file-warning', 15 ); ?></span> <?php esc_html_e( 'Honeyfile', 'visitor-sentinel' ); ?></th><td><code><?php echo esc_html( home_url( '/' . VISISE_Honeypot::get_honeyfile_slug() . '.txt' ) ); ?></code></td></tr>
					<tr><th scope="row"><span class="pv-th-icon"><?php VISISE_Icons::render( 'lock', 15 ); ?></span> <?php esc_html_e( 'Decoy username', 'visitor-sentinel' ); ?></th><td><code><?php echo esc_html( VISISE_Honeypot::get_decoy_username() ); ?></code></td></tr>
					<tr><th scope="row"><span class="pv-th-icon"><?php VISISE_Icons::render( 'mail', 15 ); ?></span> <?php esc_html_e( 'Spam-trap email', 'visitor-sentinel' ); ?></th><td><code><?php echo esc_html( VISISE_Honeypot::get_trap_email() ); ?></code></td></tr>
					<tr><th scope="row"><span class="pv-th-icon"><?php VISISE_Icons::render( 'key', 15 ); ?></span> <?php esc_html_e( 'Decoy API key', 'visitor-sentinel' ); ?></th><td><code><?php echo esc_html( VISISE_Honeypot::get_decoy_api_key() ); ?></code></td></tr>
				</table>
			<?php endif; ?>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'lock' ); ?></span>
				<div><h2><?php esc_html_e( 'Device recognition', 'visitor-sentinel' ); ?></h2></div>
			</header>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="pv_fingerprinting"><?php esc_html_e( 'Enable fingerprinting', 'visitor-sentinel' ); ?></label></th><td><label class="pv-toggle-row"><input type="checkbox" id="pv_fingerprinting" name="visise_settings[enable_fingerprinting]" value="1" <?php checked( ! empty( $settings['enable_fingerprinting'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'visitor-sentinel' ); ?></label></td></tr>
			</table>
		</section>

		<section class="visise-settings-card">
			<header class="visise-settings-card__header">
				<span class="visise-settings-card__icon"><?php VISISE_Icons::render( 'shield' ); ?></span>
				<div><h2><?php esc_html_e( 'Privacy / GDPR', 'visitor-sentinel' ); ?></h2></div>
			</header>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="pv_gdpr_days"><?php esc_html_e( 'Auto-anonymize after (days)', 'visitor-sentinel' ); ?></label></th><td><input type="number" min="1" id="pv_gdpr_days" name="visise_settings[gdpr_anonymize_after_days]" value="<?php echo esc_attr( $settings['gdpr_anonymize_after_days'] ); ?>" /><p class="description"><?php esc_html_e( 'After this period, IP addresses in visits are hashed/removed but aggregate stats remain.', 'visitor-sentinel' ); ?></p></td></tr>
			</table>
		</section>

		<?php submit_button( __( 'Save settings', 'visitor-sentinel' ) ); ?>
	</form>
</div>
