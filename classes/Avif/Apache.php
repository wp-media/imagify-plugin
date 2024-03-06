<?php
declare(strict_types=1);

namespace Imagify\Avif;

use Imagify\WriteFile\AbstractApacheDirConfFile;

/**
 * Add and remove contents to the .htaccess file to display AVIF images on the site.
 */
class Apache extends AbstractApacheDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var string
	 */
	const TAG_NAME = 'Imagify: avif file type';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		return trim( '
<IfModule mod_mime.c>
	AddType image/avif .avif
</IfModule>' );
	}
}
