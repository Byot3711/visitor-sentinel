<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pv-wrap">
	<h1 class="pv-title"><?php esc_html_e( 'Blocked IPs', 'visitor-sentinel' ); ?></h1>
	<p class="pv-subtitle"><?php esc_html_e( 'Every block is permanent. Lifting requires a signed declaration.', 'visitor-sentinel' ); ?></p>

	<div class="pv-panel">
		<h2 class="pv-panel-title"><span class="pv-panel-title__icon"><?php VISISE_Icons::render( 'lock', 17 ); ?></span><?php esc_html_e( 'Manually block an IP', 'visitor-sentinel' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pv-manual-form">
			<?php wp_nonce_field( 'visise_ban_nonce' ); ?>
			<input type="hidden" name="action" value="visise_ban_action" />
			<input type="hidden" name="visise_action" value="manual_ban" />
			<label><span><?php esc_html_e( 'IP address', 'visitor-sentinel' ); ?></span><input type="text" name="ip" required placeholder="203.0.113.10" /></label>
			<label class="pv-manual-form__reason"><span><?php esc_html_e( 'Reason', 'visitor-sentinel' ); ?></span><input type="text" name="reason" placeholder="<?php esc_attr_e( 'Manually blocked by administrator.', 'visitor-sentinel' ); ?>" /></label>
			<button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'Block permanently. Continue?', 'visitor-sentinel' ) ); ?>');"><?php esc_html_e( 'Block IP permanently', 'visitor-sentinel' ); ?></button>
		</form>
		<p class="description" style="margin-top:10px;"><?php esc_html_e( 'Blocks are permanent by design. Whitelist your own IP first.', 'visitor-sentinel' ); ?></p>
	</div>

	<?php if ( $inspect_ip && VISISE_IP::is_valid_ip( $inspect_ip ) ) : ?>
		<div class="pv-panel pv-panel--inspect">
			<h2 class="pv-panel-title"><span class="pv-panel-title__icon"><?php VISISE_Icons::render( 'radar', 17 ); ?></span><?php $inspect_geo = VISISE_Geo::lookup( $inspect_ip ); ?>
				<?php if ( $inspect_geo['countryCode'] ) : ?><span class="pv-flag"><?php echo esc_html( strtoupper( $inspect_geo['countryCode'] ) ); ?></span><?php endif; ?>
				<?php printf( esc_html__( 'Details for IP: %s', 'visitor-sentinel' ), esc_html( $inspect_ip ) ); ?>
			</h2>
			<?php if ( $inspect_geo['country'] || $inspect_geo['isp'] ) : ?>
				<div class="pv-ip-profile"><h3><?php esc_html_e( 'Origin & network profile', 'visitor-sentinel' ); ?></h3>
					<table class="pv-ip-profile__table">
						<?php if ( $inspect_geo['country'] ) : ?><tr><th><?php esc_html_e( 'Location', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( trim( implode( ', ', array_filter( array( $inspect_geo['city'], $inspect_geo['regionName'], $inspect_geo['country'] ) ) ) ) ); ?></td></tr><?php endif; ?>
						<?php if ( $inspect_geo['isp'] ) : ?><tr><th><?php esc_html_e( 'ISP', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $inspect_geo['isp'] ); ?></td></tr><?php endif; ?>
						<?php if ( $inspect_geo['proxy'] || $inspect_geo['hosting'] ) : ?><tr><th><?php esc_html_e( 'Connection', 'visitor-sentinel' ); ?></th><td><?php if ( $inspect_geo['proxy'] ) : ?><span class="pv-badge pv-badge--permanent"><?php esc_html_e( 'VPN / Proxy', 'visitor-sentinel' ); ?></span><?php endif; ?><?php if ( $inspect_geo['hosting'] ) : ?><span class="pv-badge pv-badge--temporary"><?php esc_html_e( 'Hosting', 'visitor-sentinel' ); ?></span><?php endif; ?></td></tr><?php endif; ?>
					</table>
				</div>
			<?php endif; ?>

			<?php if ( $inspect_ban ) : ?>
				<p><strong><?php esc_html_e( 'Status:', 'visitor-sentinel' ); ?></strong> <?php esc_html_e( 'Permanently blocked', 'visitor-sentinel' ); ?></p>
				<p><strong><?php esc_html_e( 'Blocked since:', 'visitor-sentinel' ); ?></strong> <?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $inspect_ban->created_at ) ); ?></p>
				<p><strong><?php esc_html_e( 'Reason:', 'visitor-sentinel' ); ?></strong> <?php echo esc_html( $inspect_ban->reason ); ?></p>
				<p><strong><?php esc_html_e( 'Risk score:', 'visitor-sentinel' ); ?></strong> <?php echo esc_html( $inspect_ban->score ); ?></p>
				<div class="pv-actions">
					<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'visise-bans', 'visise_view' => 'confirm_unban', 'ip' => $inspect_ip ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Lift this block…', 'visitor-sentinel' ); ?></a>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pv-inline-form">
						<?php wp_nonce_field( 'visise_ban_nonce' ); ?>
						<input type="hidden" name="action" value="visise_ban_action" />
						<input type="hidden" name="visise_action" value="whitelist" />
						<input type="hidden" name="ip" value="<?php echo esc_attr( $inspect_ip ); ?>" />
						<button type="submit" class="button"><?php esc_html_e( 'Add to whitelist and unblock', 'visitor-sentinel' ); ?></button>
					</form>
				</div>
			<?php else : ?>
				<p class="pv-empty"><?php esc_html_e( 'This IP is not currently blocked.', 'visitor-sentinel' ); ?></p>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Suspicious activity history', 'visitor-sentinel' ); ?></h3>
			<?php if ( empty( $inspect_events ) ) : ?>
				<p class="pv-empty"><?php esc_html_e( 'No events recorded.', 'visitor-sentinel' ); ?></p>
			<?php else : ?>
				<div class="pv-table-wrap"><table class="widefat pv-table"><thead><tr><th><?php esc_html_e( 'Date', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'Type', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'What it tried', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'Score', 'visitor-sentinel' ); ?></th></tr></thead><tbody>
					<?php foreach ( $inspect_events as $event ) : ?>
						<tr><td><?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $event->created_at ) ); ?></td><td><?php echo esc_html( $event->event_type ); ?></td><td><?php echo esc_html( $event->description ); ?></td><td><?php echo esc_html( $event->score ); ?></td></tr>
					<?php endforeach; ?>
				</tbody></table></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="pv-panel">
		<div class="pv-panel__header-row">
			<h2 class="pv-panel-title"><span class="pv-panel-title__icon"><?php VISISE_Icons::render( 'shield', 17 ); ?></span><?php esc_html_e( 'All blocked IPs', 'visitor-sentinel' ); ?></h2>
			<?php if ( ! empty( $bans ) ) : ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=visise_export_bans' ), 'visise_export_nonce' ) ); ?>"><?php esc_html_e( 'Export CSV', 'visitor-sentinel' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $bans ) ) : ?>
			<p class="pv-search"><input type="text" id="pv-ban-search" placeholder="<?php esc_attr_e( 'Search by IP or reason…', 'visitor-sentinel' ); ?>" /></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'visise_bulk_nonce' ); ?>
				<input type="hidden" name="action" value="visise_bulk_ban_action" />
				<div class="pv-actions" style="margin-bottom:10px;">
					<select name="bulk_do"><option value=""><?php esc_html_e( 'Bulk actions', 'visitor-sentinel' ); ?></option><option value="unban"><?php esc_html_e( 'Unban', 'visitor-sentinel' ); ?></option><option value="whitelist"><?php esc_html_e( 'Whitelist & Unban', 'visitor-sentinel' ); ?></option></select>
					<button type="submit" class="button"><?php esc_html_e( 'Apply', 'visitor-sentinel' ); ?></button>
				</div>
				<div class="pv-table-wrap">
					<table class="widefat pv-table" id="pv-ban-table">
						<thead><tr><th style="width:30px;"><input type="checkbox" id="pv-select-all-bans" /></th><th><?php esc_html_e( 'IP', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'Reason', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'Blocked since', 'visitor-sentinel' ); ?></th><th><?php esc_html_e( 'Actions', 'visitor-sentinel' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $bans as $ban ) : ?>
								<?php $ban_country_code = VISISE_Geo::get_country_code( $ban->ip ); ?>
								<tr>
									<td><input type="checkbox" name="ips[]" value="<?php echo esc_attr( $ban->ip ); ?>" /></td>
									<td><?php if ( $ban_country_code ) : ?><span class="pv-flag"><?php echo esc_html( strtoupper( $ban_country_code ) ); ?></span><?php endif; ?><?php echo esc_html( $ban->ip ); ?></td>
									<td><?php echo esc_html( $ban->reason ); ?></td>
									<td><?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $ban->created_at ) ); ?></td>
									<td>
										<div class="pv-row-actions">
											<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'visise-bans', 'ip' => $ban->ip ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'View details', 'visitor-sentinel' ); ?></a>
											<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'visise-bans', 'visise_view' => 'confirm_unban', 'ip' => $ban->ip ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Lift…', 'visitor-sentinel' ); ?></a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</form>
		<?php else : ?>
			<p class="pv-empty"><?php esc_html_e( 'No IP is currently blocked.', 'visitor-sentinel' ); ?></p>
		<?php endif; ?>
	</div>
</div>
