<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>
<ul class="imagify-list-infos">
	<li>
		<?php
		esc_html_e( 'Please be aware that optimizing a large number of images can take a while depending on your server and network speed.', 'imagify' );

		if ( get_transient( 'imagify_large_library' ) ) {
			printf(
				/* translators: %s is a formatted number. Don't use %d. */
				__( 'If you have more than %s images, you will need to launch the bulk optimization several times.' , 'imagify' ),
				number_format_i18n( imagify_get_unoptimized_attachment_limit() )
			);
		}
		?>
	</li>
	<li>
		<?php esc_html_e( 'You must keep this page open while the bulk optimization is processing. If you leave you can come back to continue where it left off.', 'imagify' ); ?>
	</li>
	<li class="imagify-documentation-link-box">
		<span class="imagify-documentation-icon"><svg viewBox="0 0 15 20" xmlns="http://www.w3.org/2000/svg"><g fill="#40b1d0" fill-rule="nonzero"><g><path d="m14.583 20h-14.167c-.23 0-.417-.187-.417-.417v-14.167c0-.111.044-.217.122-.295l5-5c.078-.078.184-.122.295-.122h9.167c.23 0 .417.187.417.417v19.17c0 .23-.187.417-.417.417m-13.75-.833h13.333v-18.333h-8.578l-4.756 4.756v13.578"/><path d="m5.417 5.833h-5c-.23 0-.417-.187-.417-.417 0-.23.187-.417.417-.417h4.583v-4.583c0-.23.187-.417.417-.417.23 0 .417.187.417.417v5c0 .23-.187.417-.417.417"/></g><path d="m12.583 7h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 5h-4.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h4.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 10h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 13h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 15h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 18h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/></g></svg></span>
		<span>
			<?php _e( 'Need help or have questions?', 'imagify' ); ?>
			<a class="imagify-documentation-link" href="<?php echo esc_url( imagify_get_external_url( 'documentation' ) ); ?>" target="_blank"><?php _e( 'Check our documentation.', 'imagify' ); ?></a>
		<span>
	</li>
</ul>
<?php
