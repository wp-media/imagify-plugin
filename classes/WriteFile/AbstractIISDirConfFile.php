<?php
namespace Imagify\WriteFile;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class used to add and remove contents to the web.config file.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
abstract class AbstractIISDirConfFile extends AbstractWriteDirConfFile {

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
		$doc = $this->get_file_contents();

		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		$marker = static::TAG_NAME;
		$xpath  = new \DOMXPath( $doc );

		// Remove previous rules.
		$old_nodes = $xpath->query( ".//*[starts-with(@name,'$marker')]" );

		if ( $old_nodes->length > 0 ) {
			foreach ( $old_nodes as $old_node ) {
				$old_node->parentNode->removeChild( $old_node );
			}
		}

		// No new contents? Stop here.
		if ( ! $new_contents ) {
			return $this->put_file_contents( $doc );
		}

		$new_contents = preg_split( '/<!--\s+@parent\s+(.+?)\s+-->/', $new_contents, -1, PREG_SPLIT_DELIM_CAPTURE );
		unset( $new_contents[0] );
		$new_contents = array_chunk( $new_contents, 2 );

		foreach ( $new_contents as $i => $new_content ) {
			$path        = rtrim( $new_content[0], '/' );
			$new_content = trim( $new_content[1] );

			if ( '' === $new_content ) {
				continue;
			}

			$fragment = $doc->createDocumentFragment();
			$fragment->appendXML( $new_content );

			$this->get_node( $doc, $xpath, $path, $fragment );
		}

		return $this->put_file_contents( $doc );
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
		return $this->filesystem->get_site_root() . 'web.config';
	}

	/** ----------------------------------------------------------------------------------------- */
	/** OTHER TOOLS ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

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

		if ( ! class_exists( '\DOMDocument' ) ) {
			return new \WP_Error(
				'not_domdocument',
				sprintf(
					/* translators: 1 is a php class name, 2 is a file name. */
					__( 'The class %1$s is not present on your server, a %2$s file cannot be created nor edited.', 'imagify' ),
					'<code>DOMDocument</code>',
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
			if ( ! $this->filesystem->exists( $file_path ) ) {
				$result = $this->filesystem->put_contents( $file_path, '<configuration/>' );

				if ( ! $result ) {
					return new \WP_Error(
						'not_created',
						sprintf(
							/* translators: %s is a file name. */
							__( 'The %s file could not be created.', 'imagify' ),
							'<code>' . esc_html( $file_name ) . '</code>'
						)
					);
				}
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
	 * Get the file contents.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return \DOMDocument|\WP_Error A \DOMDocument object on success, a \WP_Error object on failure.
	 */
	protected function get_file_contents() {
		$writable = $this->is_file_writable();

		if ( is_wp_error( $writable ) ) {
			return $writable;
		}

		$file_path = $this->get_file_path();
		$doc       = new \DOMDocument();

		$doc->preserveWhiteSpace = false;

		if ( false === $doc->load( $file_path ) ) {
			$file_path = $this->get_file_path();
			$file_name = $this->filesystem->make_path_relative( $file_path );

			return new \WP_Error(
				'not_read',
				sprintf(
					/* translators: %s is a file name. */
					__( 'The %s file could not be read.', 'imagify' ),
					'<code>' . esc_html( $file_name ) . '</code>'
				)
			);
		}

		return $doc;
	}

	/**
	 * Put new contents into the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  \DOMDocument $contents A \DOMDocument object.
	 * @return bool|\WP_Error         True on success, a \WP_Error object on failure.
	 */
	protected function put_file_contents( $contents ) {
		$contents->encoding     = 'UTF-8';
		$contents->formatOutput = true;

		saveDomDocument( $contents, $this->get_file_path() );

		return true;
	}


	/**
	 * Get a DOMNode node.
	 * If it does not exist it is created recursively.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  \DOMDocument $doc   A \DOMDocument element.
	 * @param  \DOMXPath    $xpath A \DOMXPath element.
	 * @param  string       $path  Path to the desired node.
	 * @param  \DOMNode     $child A \DOMNode to be prepended.
	 * @return \DOMNode            The \DOMNode node.
	 */
	protected function get_node( $doc, $xpath, $path, $child ) {
		$nodelist = $xpath->query( $path );

		if ( $nodelist->length > 0 ) {
			return $this->prepend_node( $nodelist->item( 0 ), $child );
		}

		$path = explode( '/', $path );
		$node = array_pop( $path );
		$path = implode( '/', $path );

		$final_node = $doc->createElement( $node );

		if ( $child ) {
			$final_node->appendChild( $child );
		}

		return $this->get_node( $doc, $xpath, $path, $final_node );
	}


	/**
	 * Prepend a DOMNode node.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  \DOMNode $container_node The \DOMNode that will contain the new node.
	 * @param  \DOMNode $new_node       The \DOMNode to be prepended.
	 * @return \DOMNode                 The \DOMNode containing the new node.
	 */
	protected function prepend_node( $container_node, $new_node ) {
		if ( ! $new_node ) {
			return $container_node;
		}

		if ( $container_node->hasChildNodes() ) {
			$container_node->insertBefore( $new_node, $container_node->firstChild );
		} else {
			$container_node->appendChild( $new_node );
		}

		return $container_node;
	}
}
