<?php
declare(strict_types=1);

namespace Imagify\Avif\RewriteRules;

use Imagify\WriteFile\AbstractApacheDirConfFile;

/**
 * Add and remove rewrite rules to the .htaccess file to display AVIF images on the site.
 */
class Apache extends AbstractApacheDirConfFile {

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
<IfModule mod_setenvif.c>
	# Vary: Accept for all the requests to jpeg, png, and gif.
	SetEnvIf Request_URI "\.(' . $extensions . ')$" REQUEST_image
</IfModule>

<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase ' . $home_root . '

	# Check if browser supports AVIF images.
	# Update the MIME type accordingly.
	RewriteCond %{HTTP_ACCEPT} image/avif

	# Check if AVIF replacement image exists.
	RewriteCond %{REQUEST_FILENAME}.avif -f

	# Serve AVIF image instead.
	RewriteRule (.+)\.(' . $extensions . ')$ $1.$2.avif [T=image/avif,NC]
</IfModule>

<IfModule mod_headers.c>
	# Update the MIME type accordingly.
	Header append Vary Accept env=REQUEST_image
</IfModule>' );
	}
}
