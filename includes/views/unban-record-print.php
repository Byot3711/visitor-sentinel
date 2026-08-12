<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php esc_html_e( 'Unban Record', 'visitor-sentinel' ); ?></title>
	<style>
		body{font-family:Georgia,serif;line-height:1.6;max-width:800px;margin:40px auto;padding:20px;color:#111;}
		h1{border-bottom:2px solid #000;padding-bottom:10px;}
		table{width:100%;border-collapse:collapse;margin-top:20px;}
		th,td{border:1px solid #ccc;padding:10px;text-align:left;}
		th{background:#f5f5f5;width:30%;}
		.signature{margin-top:60px;border-top:1px solid #000;width:300px;padding-top:10px;}
	</style>
</head>
<body>
	<h1><?php esc_html_e( 'Unban Declaration Record', 'visitor-sentinel' ); ?></h1>
	<table>
		<tr><th><?php esc_html_e( 'IP Address', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->ip ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Original Ban Type', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->ban_type ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Original Reason', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->original_reason ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Risk Score', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->score ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Admin', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->admin_display_name ); ?> (<?php echo esc_html( $record->admin_login ); ?>)</td></tr>
		<tr><th><?php esc_html_e( 'Declaration', 'visitor-sentinel' ); ?></th><td><?php echo nl2br( esc_html( $record->declaration ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Signature Name', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( $record->signature_name ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Date', 'visitor-sentinel' ); ?></th><td><?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $record->created_at ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Signature Hash', 'visitor-sentinel' ); ?></th><td><code><?php echo esc_html( $record->signature_hash ); ?></code></td></tr>
	</table>
	<div class="signature"><?php esc_html_e( 'Signature', 'visitor-sentinel' ); ?></div>
</body>
</html>
<?php exit;
