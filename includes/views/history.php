<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pv-wrap">
	<h1 class="pv-title"><?php esc_html_e( 'Unban History', 'visitor-sentinel' ); ?></h1>
	<?php if ( ! empty( $records ) ) : ?>
		<div class="pv-table-wrap">
			<table class="widefat pv-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'visitor-sentinel' ); ?></th>
						<th><?php esc_html_e( 'IP', 'visitor-sentinel' ); ?></th>
						<th><?php esc_html_e( 'Admin', 'visitor-sentinel' ); ?></th>
						<th><?php esc_html_e( 'Original reason', 'visitor-sentinel' ); ?></th>
						<th><?php esc_html_e( 'Score', 'visitor-sentinel' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'visitor-sentinel' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $records as $r ) : ?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $r->created_at ) ); ?></td>
							<td><?php echo esc_html( $r->ip ); ?></td>
							<td><?php echo esc_html( $r->admin_display_name ); ?> (<?php echo esc_html( $r->admin_login ); ?>)</td>
							<td><?php echo esc_html( $r->original_reason ); ?></td>
							<td><?php echo esc_html( $r->score ); ?></td>
							<td><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'visise_print_record', 'id' => $r->id ), admin_url( 'admin-post.php' ) ), 'visise_print_record' ) ); ?>" class="button button-small" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'visitor-sentinel' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<p class="pv-empty"><?php esc_html_e( 'No unban records yet.', 'visitor-sentinel' ); ?></p>
	<?php endif; ?>
</div>
