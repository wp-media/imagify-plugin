<?php
declare(strict_types=1);

namespace Imagify\Webp;

use Imagify\WriteFile\AbstractApacheDirConfFile;

/**
 * Add and remove contents to the .htaccess file to display WebP images on the site.
 *
 * @since 1.9
 */
class Apache extends AbstractApacheDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var string
	 * @since 1.9
	 */
	const TAG_NAME = 'Imagify: webp file type';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		return trim(
			'
<IfModule mod_mime.c>
	AddType image/webp .webp
</IfModule>'
		);
	}
}
