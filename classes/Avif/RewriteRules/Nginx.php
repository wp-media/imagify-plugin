<?php
declare(strict_types=1);

namespace Imagify\Avif\RewriteRules;

use Imagify\WriteFile\AbstractNginxDirConfFile;

/**
 * Add and remove rewrite rules to the imagify.conf file to display AVIF images on the site.
 */
class Nginx extends AbstractNginxDirConfFile {

	/**
	 * Name of the tag used as block delimiter.
	 *
	 * @var    string
	 */
	const TAG_NAME = 'Imagify: rewrite rules for avif';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		$extensions = $this->get_extensions_pattern();
		$extensions = str_replace( '|avif', '', $extensions );
		$home_root  = wp_parse_url( home_url( '/' ) );
		$home_root  = $home_root['path'];

		return trim( '
location ~* ^(' . $home_root . '.+)\.(' . $extensions . ')$ {
	add_header Vary Accept;

	if ($http_accept ~* "avif"){
		set $imavif A;
	}
	if (-f $request_filename.avif) {
		set $imavif  "${imavif}B";
	}
	if ($imavif = AB) {
		rewrite ^(.*) $1.avif;
	}
}' );
	}
}
