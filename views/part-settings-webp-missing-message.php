<p>
	<?php
	echo esc_html(
		sprintf(
		/* translators: %s is a formatted number (don’t use %d). */
			_n(
				'It seems that you have %s optimized image without WebP versions. You can generate them here if a backup copy is available.',
				'It seems that you have %s optimized images without WebP versions. You can generate them here if backup copies are available.',
				$data['count'],
				'imagify'
			),
			number_format_i18n( $data['count'] )
		)
	);
	?>
</p>
