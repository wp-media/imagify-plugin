<?php
namespace Imagify\Webp;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Add and remove contents to the web.config file to display WebP images on the site.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class IIS extends \Imagify\WriteFile\AbstractIISDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const TAG_NAME = 'Imagify: webp file type';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	protected function get_raw_new_contents() {
		return trim( '
<!-- @parent /configuration/system.webServer -->
<staticContent name="' . esc_attr( static::TAG_NAME ) . ' 1">
	<mimeMap fileExtension=".webp" mimeType="image/webp" />
</staticContent>' );
	}
}
