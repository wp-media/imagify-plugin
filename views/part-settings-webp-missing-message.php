
<p>
	<?php
	echo wp_kses(
		sprintf(
		/* translators: %1$s is a formatted number (don’t use %2$d), %2$s and %3$s are the opening and closing tags of a link to the filtered Media Library. */
			_n(
				'It seems that you have %1$s optimized image without Next-Gen versions. %2$sYou can generate it here.%3$s',
				'It seems that you have %1$s optimized images without Next-Gen versions. %2$sYou can generate them here.%3$s',
				$data['count'],
				'imagify'
			),
			number_format_i18n( $data['count'] ),
			'<a href="' . esc_url( get_imagify_admin_url( 'missing-nextgen' ) ) . '">',
			'</a>'
		),
		[
			'a' => [
				'href' => true,
			],
		]
	);
	?>
</p>
