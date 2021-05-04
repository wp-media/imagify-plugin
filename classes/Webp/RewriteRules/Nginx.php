<?php
namespace Imagify\Webp\RewriteRules;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Add and remove rewrite rules to the imagify.conf file to display WebP images on the site.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Nginx extends \Imagify\WriteFile\AbstractNginxDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const TAG_NAME = 'Imagify: rewrite rules for webp';

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
		$extensions = $this->get_extensions_pattern();
		$home_root  = wp_parse_url( home_url( '/' ) );
		$home_root  = $home_root['path'];

		return trim( '
location ~* ^(' . $home_root . '.+)\.(' . $extensions . ')$ {
	add_header Vary Accept;

	if ($http_accept ~* "webp"){
		set $imwebp A;
	}
	if (-f $request_filename.webp) {
		set $imwebp  "${imwebp}B";
	}
	if ($imwebp = AB) {
		rewrite ^(.*) $1.webp;
	}
}' );
	}
}
