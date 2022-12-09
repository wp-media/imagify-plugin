<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="notice notice-success is-dismissible">
	<div>
		<p>
			<?php
			printf(
				// translators: %1$s = opening strong tag, %2$s = closing strong tag, %3$s = opening link tag, %4$s = closing link tag.
				'%1$sImagify%2$s: the bulk optimization is currently running. Check status %3$shere%4$s.',
				'<strong>',
				'</strong>',
				'<a href="' . esc_url( $data['bulk_page_url'] ) . '">',
				'</a>'
			);
			?>
		</p>
<?php $this->print_template( 'notice-footer', [ 'dismissible' => 'bulk-optimization-complete' ] ); ?>
