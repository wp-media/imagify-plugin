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
	 * Emits only the leaf `<mimeMap>` targeted at the single shared
	 * `<staticContent>` collection. IIS allows exactly one `<staticContent>`
	 * under `system.webServer`; letting `insert_contents()` create/merge it
	 * avoids the duplicate-collection HTTP 500 (see issue #509).
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		return trim(
			'
<!-- @parent /configuration/system.webServer/staticContent -->
<mimeMap fileExtension=".avif" mimeType="image/avif" />'
		);
	}

	/**
	 * Get the MIME map file extensions owned by this class.
	 *
	 * Used by insert_contents() to dedupe existing `<mimeMap>` entries for the
	 * same file extension before inserting the fresh one, guaranteeing a single
	 * `<staticContent>` collection with no duplicate `fileExtension` keys.
	 *
	 * @since 2.3.4
	 *
	 * @return array
	 */
	protected function get_owned_mime_extensions(): array {
		return [ '.avif' ];
	}
}
