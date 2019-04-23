<?php
namespace Imagify\WriteFile;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class used to add and remove contents to a directory conf file (.htaccess, etc).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractWriteDirConfFile implements WriteFileInterface {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const TAG_NAME = 'Imagify ###';

	/**
	 * Filesystem object.
	 *
	 * @var    \Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * Constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function __construct() {
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Add new contents to the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True on success. A \WP_Error object on error.
	 */
	public function add() {
		$result = $this->insert_contents( $this->get_new_contents() );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}
		$file_path = $this->get_file_path();
		$file_name = $this->filesystem->make_path_relative( $file_path );

		if ( 'edition_disabled' === $result->get_error_code() ) {
			return new \WP_Error(
				'edition_disabled',
				sprintf(
					/* translators: %s is a file name. */
					__( 'Imagify did not add contents to the %s file, as its edition is disabled.', 'imagify' ),
					$file_name
				)
			);
		}

		return new \WP_Error(
			'add_contents_failure',
			sprintf(
				/* translators: 1 is a file name, 2 is an error message. */
				__( 'Imagify could not insert contents into the %1$s file: %2$s', 'imagify' ),
				$file_name,
				$result->get_error_message()
			),
			[ 'code' => $result->get_error_code() ]
		);
	}

	/**
	 * Remove the related contents from the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True on success. A \WP_Error object on error.
	 */
	public function remove() {
		$result = $this->insert_contents( '' );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		$file_name = $this->filesystem->make_path_relative( $file_path );

		if ( 'edition_disabled' === $result->get_error_code() ) {
			return new \WP_Error(
				'edition_disabled',
				sprintf(
					/* translators: %s is a file name. */
					__( 'Imagify did not remove the contents from the %s file, as its edition is disabled.', 'imagify' ),
					$file_name
				)
			);
		}

		return new \WP_Error(
			'add_contents_failure',
			sprintf(
				/* translators: 1 is a file name, 2 is an error message. */
				__( 'Imagify could not remove contents from the %1$s file: %2$s', 'imagify' ),
				$file_name,
				$result->get_error_message()
			),
			[ 'code' => $result->get_error_code() ]
		);
	}

	/**
	 * Get the path to the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_file_path() {
		$file_path = $this->get_raw_file_path();

		/**
		 * Filter the path to the directory conf file.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $file_path Path to the file.
		 */
		$new_file_path = apply_filters( 'imagify_dir_conf_path', $file_path );

		if ( $new_file_path && is_string( $new_file_path ) ) {
			return $new_file_path;
		}

		return $file_path;
	}

	/**
	 * Tell if the file is writable.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True if writable. A \WP_Error object if not.
	 */
	public function is_file_writable() {
		$file_path = $this->get_file_path();
		$file_name = $this->filesystem->make_path_relative( $file_path );

		if ( $this->is_conf_edition_disabled() ) {
			return new \WP_Error(
				'edition_disabled',
				sprintf(
					/* translators: %s is a file name. */
					__( 'Edition of the %s file is disabled.', 'imagify' ),
					'<code>' . esc_html( $file_name ) . '</code>'
				)
			);
		}

		if ( ! $this->filesystem->exists( $file_path ) ) {
			$dir_path = $this->filesystem->dir_path( $file_path );

			$this->filesystem->make_dir( $dir_path );

			if ( ! $this->filesystem->is_writable( $dir_path ) ) {
				return new \WP_Error(
					'parent_not_writable',
					sprintf(
						/* translators: %s is a file name. */
						__( '%s’s parent folder is not writable.', 'imagify' ),
						'<code>' . esc_html( $file_name ) . '</code>'
					)
				);
			}
			if ( ! $this->filesystem->touch( $file_path ) ) {
				return new \WP_Error(
					'not_created',
					sprintf(
						/* translators: %s is a file name. */
						__( 'The %s file could not be created.', 'imagify' ),
						'<code>' . esc_html( $file_name ) . '</code>'
					)
				);
			}
		} elseif ( ! $this->filesystem->is_writable( $file_path ) ) {
			return new \WP_Error(
				'not_writable',
				sprintf(
					/* translators: %s is a file name. */
					__( 'The %s file is not writable.', 'imagify' ),
					'<code>' . esc_html( $file_name ) . '</code>'
				)
			);
		}

		return true;
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
		$contents = $this->get_raw_new_contents();

		/**
		 * Filter the contents to add to the directory conf file.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $contents The contents.
		 */
		$new_contents = apply_filters( 'imagify_dir_conf_contents', $contents );

		if ( $new_contents && is_string( $new_contents ) ) {
			return $new_contents;
		}

		return $contents;
	}

	/** ----------------------------------------------------------------------------------------- */
	/** ABSTRACT METHODS ======================================================================== */
	/** ----------------------------------------------------------------------------------------- */

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
	abstract protected function insert_contents( $new_contents );

	/**
	 * Get the unfiltered path to the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	abstract protected function get_raw_file_path();

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	abstract protected function get_raw_new_contents();

	/** ----------------------------------------------------------------------------------------- */
	/** OTHER TOOLS ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the file contents.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return mixed|\WP_Error The file contents on success, a \WP_Error object on failure.
	 */
	protected function get_file_contents() {
		$writable = $this->is_file_writable();

		if ( is_wp_error( $writable ) ) {
			return $writable;
		}

		$file_path = $this->get_file_path();

		if ( ! $this->filesystem->exists( $file_path ) ) {
			// This should not happen.
			return '';
		}

		$contents = $this->filesystem->get_contents( $file_path );

		if ( false === $contents ) {
			return new \WP_Error(
				'not_read',
				sprintf(
					/* translators: %s is a file name. */
					__( 'The %s file could not be read.', 'imagify' ),
					'<code>' . esc_html( $file_name ) . '</code>'
				)
			);
		}

		return $contents;
	}

	/**
	 * Put new contents into the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $contents New contents to add to the file.
	 * @return bool|\WP_Error   True on success, a \WP_Error object on failure.
	 */
	protected function put_file_contents( $contents ) {
		$file_path = $this->get_file_path();
		$result    = $this->filesystem->put_contents( $file_path, $contents );

		if ( $result ) {
			return true;
		}

		$file_name = $this->filesystem->make_path_relative( $file_path );

		return new \WP_Error(
			'edition_failed',
			sprintf(
				/* translators: %s is a file name. */
				__( 'Could not write into the %s file.', 'imagify' ),
				'<code>' . esc_html( $file_name ) . '</code>'
			)
		);
	}

	/**
	 * Tell if edition of the directory conf file is disabled.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True to disable, false otherwise.
	 */
	protected function is_conf_edition_disabled() {
		/**
		 * Disable directory conf edition.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param bool $disable True to disable, false otherwise.
		 */
		return (bool) apply_filters( 'imagify_disable_dir_conf_edition', false );
	}

	/**
	 * Get a regex pattern to be used to match the supported file extensions.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	protected function get_extensions_pattern() {
		$extensions = imagify_get_mime_types( 'image' );
		$extensions = array_keys( $extensions );

		return implode( '|', $extensions );
	}
}
