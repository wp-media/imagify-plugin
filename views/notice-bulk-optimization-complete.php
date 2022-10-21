<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="notice notice-success is-dismissible">
	<div class="imagify-notice-bulk-complete">
		<div class="imagify-notice-bulk-complete-logo">
			<img src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'imagify.svg' ); ?>" width="96" height="96" alt="Imagify" />
		</div>
		<div>
			<p><strong><?php esc_html_e( 'Well done!', 'imagify' ); ?></strong></p>
			<p><?php esc_html_e( 'The bulk optimization is now complete.', 'imagify' ); ?></p>
			<p><?php
				printf(
					// translators: %1$s = number of images optimized, %2$s = size saved, %3$s = total size.
					__( 'We have optimized %1$s images and you have just saved %2$s out of %3$s. %4$sCheck your stats%5$s' ),
					$data['images_optimized'],
					$data['size_saved'],
					$data['size_total'],
					'<a href="' . esc_url( $data['bulk_page_url'] ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
<?php $this->print_template( 'notice-footer', [ 'dismissible' => 'bulk-optimization-complete' ] ); ?>
