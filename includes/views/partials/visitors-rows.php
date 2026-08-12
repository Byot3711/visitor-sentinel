<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( empty( $visits ) ) {
	return;
}
foreach ( $visits as $visit ) :
	$ua_info = VISISE_UA::parse( $visit->user_agent );
	$country = VISISE_Geo::get_country_code( $visit->ip );
	?>
	<tr>
		<td><?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $visit->created_at ) ); ?></td>
		<td>
			<?php if ( $country ) : ?><span class="pv-flag"><?php echo esc_html( strtoupper( $country ) ); ?></span><?php endif; ?>
			<?php echo esc_html( $visit->ip ); ?>
		</td>
		<td><?php echo esc_html( $visit->request_uri ); ?></td>
		<td><?php echo esc_html( $ua_info['platform'] . ' / ' . $ua_info['browser'] ); ?></td>
		<td><?php echo esc_html( mb_substr( $visit->user_agent, 0, 80 ) ); ?></td>
		<td><?php echo esc_html( $visit->visitor_role ); ?></td>
		<td>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'visise-bans', 'ip' => $visit->ip ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Inspect', 'visitor-sentinel' ); ?></a>
		</td>
	</tr>
<?php endforeach;
