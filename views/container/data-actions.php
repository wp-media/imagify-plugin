<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>
<div class="imagify-data-actions-container" data-id="<?php echo (int) $data['media_id']; ?>" data-context="<?php echo esc_attr( $data['context'] ); ?>">
	<?php echo $data['content']; ?>
</div>

<?php
