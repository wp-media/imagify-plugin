
<p>
	<?php
	echo esc_html(
		sprintf(
		/* translators: %s is a formatted number (don’t use %d). */
			_n(
				'It seems that you have %s optimized image without WebP versions. You can generate it here.',
				'It seems that you have %s optimized images without WebP versions. You can generate them here.',
				$data['count'],
				'imagify'
			),
			number_format_i18n( $data['count'] )
		)
	);
	?>
</p>
