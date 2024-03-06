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
		return '';
	}
}
