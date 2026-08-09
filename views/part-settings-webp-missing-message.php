
<p>
	<?php
	echo esc_html(
		sprintf(
		/* translators: %s is a formatted number (don’t use %d). */
			_n(
				'It seems that you have %s optimized image without Next-Gen versions. You can generate it here.',
				'It seems that you have %s optimized images without Next-Gen versions. You can generate them here.',
				$data['count'],
				'imagify'
			),
			number_format_i18n( $data['count'] )
		)
	);

	/*
	 * The count above spans every context, because the button below generates for all of them.
	 * This link can only ever show the Media Library ones: custom-folder files are not
	 * attachments, and the list table lists attachments. Naming the scope in the link text keeps
	 * the two honest instead of implying the link will show all of them.
	 */
	printf(
		' <a href="%1$s">%2$s</a>',
		esc_url( get_imagify_admin_url( 'missing-nextgen' ) ),
		esc_html__( 'See the affected images in your Media Library.', 'imagify' )
	);
	?>
</p>
