<?php
declare(strict_types=1);

namespace Imagify\Webp\RewriteRules;

use Imagify\WriteFile\AbstractNginxDirConfFile;

/**
 * Add and remove rewrite rules to the imagify.conf file to display WebP images on the site.
 *
 * @since 1.9
 */
class Nginx extends AbstractNginxDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var string
	 * @since 1.9
	 */
	const TAG_NAME = 'Imagify: rewrite rules for webp';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		$extensions = $this->get_extensions_pattern() . '|avif';
		$home_root  = wp_parse_url( home_url( '/' ) );
		$home_root  = $home_root['path'];

		return trim( '
location ~* ^(' . $home_root . '.+)\.(' . $extensions . ')$ {
    add_header Vary Accept;

    set $canavif 1;

    if ($http_accept !~* "avif"){
        set $canavif 0;
    }

    if (!-f $request_filename.avif) {
        set $canavif 0;

    }
    if ($canavif = 1){
        rewrite ^(.*) $1.avif;
        break;
    }

    set $canwebp 1;

    if ($http_accept !~* "webp"){
        set $canwebp 0;
    }

    if (!-f $request_filename.webp) {
        set $canwebp 0;

    }
    if ($canwebp = 1){
        rewrite ^(.*) $1.webp;
        break;
    }
}' );
	}
}
