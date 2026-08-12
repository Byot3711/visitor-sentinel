<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pv-wrap">
	<h1 class="pv-title"><?php esc_html_e( 'Confirm unblock', 'visitor-sentinel' ); ?></h1>
	<div class="pv-panel">
		<p><?php printf( esc_html__( 'You are about to lift the permanent block for IP %s.', 'visitor-sentinel' ), '<strong>' . esc_html( $confirm_ip ) . '</strong>' ); ?></p>
		<p class="description"><?php esc_html_e( 'This action requires a signed declaration.', 'visitor-sentinel' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'visise_confirm_unban_nonce' ); ?>
			<input type="hidden" name="action" value="visise_confirm_unban" />
			<input type="hidden" name="ip" value="<?php echo esc_attr( $confirm_ip ); ?>" />
			<table class="form-table">
				<tr>
					<th scope="row"><label for="declaration"><?php esc_html_e( 'Declaration', 'visitor-sentinel' ); ?></label></th>
					<td><textarea id="declaration" name="declaration" rows="4" class="large-text" required placeholder="<?php esc_attr_e( 'I declare that I have verified this request and accept responsibility...', 'visitor-sentinel' ); ?>"></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="signature_name"><?php esc_html_e( 'Your name', 'visitor-sentinel' ); ?></label></th>
					<td><input type="text" id="signature_name" name="signature_name" class="regular-text" required /></td>
				</tr>
			</table>
			<?php submit_button( __( 'Lift block permanently', 'visitor-sentinel' ), 'primary' ); ?>
		</form>
	</div>
</div>
