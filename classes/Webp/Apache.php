<?php
namespace Imagify\Webp;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Add and remove contents to the .htaccess file to display webp images on the site.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Apache extends \Imagify\WriteFile\AbstractApacheDirConfFile {

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
	 * @return string
	 */
	protected function get_raw_new_contents() {
		return trim( '
<IfModule mod_mime.c>
	AddType image/webp .webp
</IfModule>' );
	}
}
