<?php
declare(strict_types=1);

namespace Imagify\Avif;

use Imagify\WriteFile\AbstractIISDirConfFile;

/**
 * Add and remove contents to the web.config file to display AVIF images on the site.
 */
class IIS extends AbstractIISDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var string
	 */
	const TAG_NAME = 'Imagify: avif file type';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @return array
	 */
	protected function get_raw_new_contents() {
		return trim( '
<!-- @parent /configuration/system.webServer -->
<staticContent name="' . esc_attr( static::TAG_NAME ) . ' 1">
	<mimeMap fileExtension=".avif" mimeType="image/avif" />
</staticContent>' );
	}
}
