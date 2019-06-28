<?php
namespace Imagify\ThirdParty\FormidablePro;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Formidable Forms Pro plugin.
 * Each call to `new WP_Query()` made by Imagify must have a `'is_imagify' => true` argument.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 */
class Main {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';

	/**
	 * Set to true when the current query comes from Imagify.
	 *
	 * @var int
	 */
	protected $is_imagify;

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'parse_query',     array( $this, 'maybe_remove_media_library_filter' ) );
		add_action( 'posts_selection', array( $this, 'maybe_put_media_library_filter_back' ) );
	}

	/**
	 * Fires before the 'pre_get_posts' hook.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 *
	 * @param object $wp_query The WP_Query instance (passed by reference).
	 */
	public function maybe_remove_media_library_filter( $wp_query ) {
		if ( ! empty( $wp_query->query_vars['is_imagify'] ) && class_exists( 'FrmProFileField' ) ) {
			$this->is_imagify = true;
			remove_action( 'pre_get_posts', 'FrmProFileField::filter_media_library', 99 );
		} else {
			$this->is_imagify = false;
		}
	}

	/**
	 * Fires after the 'pre_get_posts' hook.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 */
	public function maybe_put_media_library_filter_back() {
		if ( $this->is_imagify ) {
			add_action( 'pre_get_posts', 'FrmProFileField::filter_media_library', 99 );
		}
	}
}
