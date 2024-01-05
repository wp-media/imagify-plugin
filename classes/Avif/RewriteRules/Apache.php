<?php
namespace Imagify\Avif\RewriteRules;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Add and remove rewrite rules to the .htaccess file to display AVIF images on the site.
 *
 * @author Gael Robin
 */
class Apache extends \Imagify\WriteFile\AbstractApacheDirConfFile {

	/**
	 * Name of the tag used as block delimiter.
	 *
	 * @var    string
	 * @author Gael Robin
	 */
	const TAG_NAME = 'Imagify: rewrite rules for avif';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @access protected
	 * @author Gael Robin
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		$extensions = $this->get_extensions_pattern();
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
