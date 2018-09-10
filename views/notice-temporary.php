<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$notices = array();

foreach ( $data as $notice_data ) {
	if ( empty( $notices[ $notice_data['type'] ] ) ) {
		$notices[ $notice_data['type'] ] = array();
	}

	$notices[ $notice_data['type'] ][] = $notice_data;
}

foreach ( $notices as $type => $type_notices ) {
	?>
	<div class="<?php echo $type; ?> settings-error notice is-dismissible">
		<?php foreach ( $type_notices as $details ) { ?>
			<p><strong><?php echo $details['message']; ?></strong></p>
		<?php } ?>
	</div>
	<?php
}
