<?php
namespace Imagify\WriteFile;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class used to add and remove contents to the .htaccess file.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractApacheDirConfFile extends AbstractWriteDirConfFile {

	/**
	 * Insert new contents into the directory conf file.
	 * Replaces existing marked info. Creates file if none exists.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $new_contents Contents to insert.
	 * @return bool|\WP_Error       True on write success, a \WP_Error object on failure.
	 */
	protected function insert_contents( $new_contents ) {
		$contents = $this->get_file_contents();

		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		$start_marker = '# BEGIN ' . static::TAG_NAME;
		$end_marker   = '# END ' . static::TAG_NAME;

		// Remove previous rules.
		$contents = preg_replace( '/\s*?' . preg_quote( $start_marker, '/' ) . '.*' . preg_quote( $end_marker, '/' ) . '\s*?/isU', "\n\n", $contents );
		$contents = trim( $contents );

		if ( $new_contents ) {
			$contents = $new_contents . "\n\n" . $contents;
		}

		return $this->put_file_contents( $contents );
	}

	/**
	 * Get new contents to write into the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_new_contents() {
		$contents = parent::get_new_contents();

		if ( ! $contents ) {
			return '';
		}

		return '# BEGIN ' . static::TAG_NAME . "\n" . $contents . "\n# END " . static::TAG_NAME;
	}

	/**
	 * Get the unfiltered path to the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	protected function get_raw_file_path() {
		return $this->filesystem->get_site_root() . '.htaccess';
	}
}
