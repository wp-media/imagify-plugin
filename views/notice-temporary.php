<?php
defined( 'ABSPATH' ) || exit;

$notices = array();

foreach ( $data as $notice_data ) {
	if ( empty( $notices[ $notice_data['type'] ] ) ) {
		$notices[ $notice_data['type'] ] = array();
	}

	$notices[ $notice_data['type'] ][] = $notice_data;
}

foreach ( $notices as $type_id => $type_notices ) {
	?>
	<div class="<?php echo $type_id; ?> settings-error notice is-dismissible">
		<?php foreach ( $type_notices as $details ) { ?>
			<p><strong><?php echo wp_kses( $details['message'], [ 'code' => [] ] ); ?></strong></p>
		<?php } ?>
	</div>
	<?php
}
