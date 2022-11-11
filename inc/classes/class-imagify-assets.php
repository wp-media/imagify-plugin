<?php
use Imagify\Notices\Notices;

/**
 * Class that handles stylesheets and JavaScripts.
 *
 * @since 1.6.10
 */
class Imagify_Assets extends Imagify_Assets_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.4';

	/**
	 * Prefix used for stylesheet handles.
	 *
	 * @var string
	 */
	const CSS_PREFIX = 'imagify-';

	/**
	 * Prefix used for script handles.
	 *
	 * @var string
	 */
	const JS_PREFIX = 'imagify-';

	/**
	 * An array containing our registered styles.
	 *
	 * @var array
	 */
	protected $styles = [];

	/**
	 * An array containing our registered scripts.
	 *
	 * @var array
	 */
	protected $scripts = [];

	/**
	 * Current handle.
	 *
	 * @var string
	 */
	protected $current_handle;

	/**
	 * Current handle type.
	 *
	 * @var string 'css' or 'js'.
	 */
	protected $current_handle_type;

	/**
	 * Array of scripts that should be localized when they are enqueued.
	 *
	 * @var array
	 */
	protected $deferred_localizations = [];

	/**
	 * A "random" script version to use when debug is on.
	 *
	 * @var int
	 */
	protected static $version;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		if ( ! isset( self::$version ) ) {
			self::$version = time();
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC METHODS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since 1.6.10
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Launch the hooks.
	 *
	 * @since 1.6.10
	 */
	public function init() {
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles_and_scripts_frontend' ] );
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_and_scripts' ], IMAGIFY_INT_MAX );
		add_action( 'wp_enqueue_media', [ $this, 'enqueue_media_modal' ] );
	}

	/**
	 * Enqueue stylesheets and scripts for the frontend.
	 *
	 * @since 1.6.10
	 */
	public function enqueue_styles_and_scripts_frontend() {
		if ( ! $this->is_admin_bar_item_showing() ) {
			return;
		}

		$this->register_style( 'admin-bar' );
		$this->register_script( 'admin-bar', 'admin-bar', [ 'jquery' ] );

		$this->enqueue_assets( 'admin-bar' )->localize( 'imagifyAdminBar' );
	}

	/**
	 * Register stylesheets and scripts for the administration area.
	 *
	 * @since 1.6.10
	 */
	public function register_styles_and_scripts() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/**
		 * 3rd Party Styles.
		 */
		$this->register_style( 'sweetalert-core', 'sweetalert2', [], '4.6.6' );

		/**
		 * Imagify Styles.
		 */
		$this->register_style( 'sweetalert', 'sweetalert-custom', [ 'sweetalert-core' ] );

		$this->register_style( 'admin-bar' );

		$this->register_style( 'admin' );

		$this->register_style( 'notices', 'notices', [ 'admin' ] ); // Needs SweetAlert on some cases.

		$this->register_style( 'twentytwenty', 'twentytwenty', [ 'admin' ] );

		$this->register_style( 'pricing-modal', 'pricing-modal', [ 'admin' ] );

		$this->register_style( 'bulk', 'bulk', [ 'sweetalert', 'admin' ] );

		$this->register_style( 'options', 'options', [ 'sweetalert', 'admin' ] );

		$this->register_style( 'files-list', 'files-list', [ 'admin' ] );

		/**
		 * 3rd Party Scripts.
		 */
		$this->register_script( 'promise-polyfill', 'es6-promise.auto', [], '4.1.1' );

		$this->register_script( 'sweetalert', 'sweetalert2', [ 'promise-polyfill' ], '4.6.6' )->localize( 'imagifySwal' );

		$this->register_script( 'chart', 'chart', [], '2.7.1.0' );

		$this->register_script( 'event-move', 'jquery.event.move', [ 'jquery' ], '2.0.1' );

		/**
		 * Imagify Scripts.
		 */
		$this->register_script( 'admin-bar', 'admin-bar', [ 'jquery' ] )->defer_localization( 'imagifyAdminBar' );

		$this->register_script( 'admin', 'admin', [ 'jquery' ] );

		$this->register_script( 'notices', 'notices', [ 'jquery', 'admin' ] )->defer_localization( 'imagifyNotices' ); // Needs SweetAlert on some cases.

		$this->register_script( 'twentytwenty', 'jquery.twentytwenty', [ 'jquery', 'event-move', 'chart', 'admin' ] )->defer_localization( 'imagifyTTT' );

		$this->register_script( 'beat', 'beat', [ 'jquery' ] )->localize( 'imagifybeatSettings' );

		$this->register_script( 'media-modal', 'media-modal', [ 'jquery', 'beat', 'underscore', 'chart', 'admin' ] )->localize( 'imagifyModal' );

		$this->register_script( 'pricing-modal', 'pricing-modal', [ 'jquery', 'admin' ] )->defer_localization( 'imagifyPricingModal' );

		$this->register_script( 'library', 'library', [ 'jquery', 'media-modal' ] )->defer_localization( 'imagifyLibrary' );

		$this->register_script( 'async', 'imagify-gulp' );

		$this->register_script( 'bulk', 'bulk', [ 'jquery', 'beat', 'underscore', 'chart', 'sweetalert', 'async', 'admin' ] )->defer_localization( 'imagifyBulk' );

		$this->register_script( 'options', 'options', [ 'jquery', 'beat', 'sweetalert', 'underscore', 'admin' ] )->defer_localization( 'imagifyOptions' );

		$this->register_script( 'files-list', 'files-list', [ 'jquery', 'beat', 'underscore', 'chart', 'admin' ] )->defer_localization( 'imagifyFiles' );
	}

	/**
	 * Enqueue stylesheets and scripts for the administration area.
	 *
	 * @since 1.6.10
	 */
	public function enqueue_styles_and_scripts() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/*
		 * Register stylesheets and scripts.
		 */
		$this->register_styles_and_scripts();

		/**
		 * Admin bar.
		 */
		if ( $this->is_admin_bar_item_showing() ) {
			$this->enqueue_assets( 'admin-bar' );
		}

		/**
		 * Notices.
		 */
		$notices = Notices::get_instance();

		if ( $notices->has_notices() ) {
			if ( $notices->display_welcome_steps() || $notices->display_wrong_api_key() ) {
				// This is where we display things about the API key.
				$this->enqueue_assets( 'sweetalert' );
			}

			$this->enqueue_assets( 'notices' );
		}

		/**
		 * Loaded in the library and attachment edition.
		 */
		if ( imagify_is_screen( 'library' ) || imagify_is_screen( 'attachment' ) ) {
			$this->enqueue_assets( 'twentytwenty' );
		}

		/**
		 * Loaded in the library.
		 */
		if ( imagify_is_screen( 'library' ) ) {
			$this->enqueue_style( 'admin' )->enqueue_script( 'library' );
		}

		/**
		 * Loaded in the bulk optimization page.
		 */
		if ( imagify_is_screen( 'bulk' ) ) {
			$this->enqueue_assets( [ 'pricing-modal', 'bulk' ] );
		}

		/*
		 * Loaded in the settings page.
		 */
		if ( imagify_is_screen( 'imagify-settings' ) ) {
			$this->enqueue_assets( [ 'sweetalert', 'notices', 'twentytwenty', 'pricing-modal', 'options' ] );
		}

		/*
		 * Loaded in the files list page.
		 */
		if ( imagify_is_screen( 'files-list' ) ) {
			$this->enqueue_assets( [ 'files-list', 'twentytwenty' ] );
		}

		/**
		 * Triggered after Imagify CSS and JS have been enqueued.
		 *
		 * @since 1.6.10
		 */
		do_action( 'imagify_assets_enqueued' );
	}

	/**
	 * Enqueue stylesheets and scripts for the media modal.
	 *
	 * @since 1.6.10
	 */
	public function enqueue_media_modal() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/*
		 * Register stylesheets and scripts.
		 */
		$this->register_styles_and_scripts();

		$this->enqueue_style( 'admin' )->enqueue_script( 'media-modal' );

		// When the optimization buttons are displayed in the media modal, they are fetched through ajax, so they can’t print the "processing" button template in the footer.
		Imagify_Views::get_instance()->print_js_template_in_footer( 'button/processing' );

		/**
		 * Triggered after Imagify CSS and JS have been enqueued for the media modal.
		 *
		 * @since 1.6.10
		 */
		do_action( 'imagify_media_modal_assets_enqueued' );
	}



	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC TOOLS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Register a style.
	 *
	 * @since 1.6.10
	 *
	 * @param  string      $handle       Name of the stylesheet. Should be unique.
	 * @param  string|null $file_name    The file name, without the extension. If null, $handle is used.
	 * @param  array       $dependencies An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string|null $version      String specifying stylesheet version number. If set to null, the plugin version is used. If SCRIPT_DEBUG is true, a random string is used.
	 * @return object                    This class instance.
	 */
	public function register_style( $handle, $file_name = null, $dependencies = [], $version = null ) {
		// If we register it, it's one of our styles.
		$this->styles[ $handle ]   = 1;
		$this->current_handle      = $handle;
		$this->current_handle_type = 'css';

		$file_name    = $file_name        ? $file_name     : $handle;
		$version      = $version          ? $version       : IMAGIFY_VERSION;
		$version      = $this->is_debug() ? self::$version : $version;
		$extension    = $this->is_debug() ? '.css'         : '.min.css';
		$handle       = self::CSS_PREFIX . $handle;
		$dependencies = $this->prefix_dependencies( $dependencies, 'css' );

		wp_register_style(
			$handle,
			IMAGIFY_URL . 'assets/css/' . $file_name . $extension,
			$dependencies,
			$version
		);

		return $this;
	}

	/**
	 * Enqueue a style.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the stylesheet. Should be unique. Can be an array to enqueue several stylesheets.
	 * @return object                This class instance.
	 */
	public function enqueue_style( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			$this->current_handle      = $handle;
			$this->current_handle_type = 'css';

			if ( ! empty( $this->styles[ $handle ] ) ) {
				// If we registered it, it's one of our styles.
				$handle = self::CSS_PREFIX . $handle;
			}

			wp_enqueue_style( $handle );
		}

		return $this;
	}

	/**
	 * Dequeue a style.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the stylesheet. Should be unique. Can be an array to dequeue several stylesheets.
	 * @return object                This class instance.
	 */
	public function dequeue_style( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			$this->current_handle      = $handle;
			$this->current_handle_type = 'css';

			if ( ! empty( $this->styles[ $handle ] ) ) {
				// If we registered it, it's one of our styles.
				$handle = self::CSS_PREFIX . $handle;
			}

			wp_dequeue_style( $handle );
		}

		return $this;
	}

	/**
	 * Register a script.
	 *
	 * @since 1.6.10
	 *
	 * @param  string      $handle       Name of the script. Should be unique.
	 * @param  string|null $file_name    The file name, without the extension. If null, $handle is used.
	 * @param  array       $dependencies An array of registered script handles this script depends on.
	 * @param  string|null $version      String specifying script version number. If set to null, the plugin version is used. If SCRIPT_DEBUG is true, a random string is used.
	 * @return object                    This class instance.
	 */
	public function register_script( $handle, $file_name = null, $dependencies = [], $version = null ) {
		// If we register it, it's one of our scripts.
		$this->scripts[ $handle ]  = 1;
		// Set the current handler and handler type.
		$this->current_handle      = $handle;
		$this->current_handle_type = 'js';

		$file_name    = $file_name        ? $file_name     : $handle;
		$version      = $version          ? $version       : IMAGIFY_VERSION;
		$version      = $this->is_debug() ? self::$version : $version;
		$extension    = $this->is_debug() ? '.js'          : '.min.js';
		$handle       = self::JS_PREFIX . $handle;
		$dependencies = $this->prefix_dependencies( $dependencies );

		wp_register_script(
			$handle,
			IMAGIFY_URL . 'assets/js/' . $file_name . $extension,
			$dependencies,
			$version,
			true
		);

		return $this;
	}

	/**
	 * Enqueue a script.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the script. Should be unique. Can be an array to enqueue several scripts.
	 * @return object                This class instance.
	 */
	public function enqueue_script( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			// Enqueue the corresponding style.
			if ( ! empty( $this->styles[ $handle ] ) ) {
				$this->enqueue_style( $handle );
			}

			$this->current_handle      = $handle;
			$this->current_handle_type = 'js';

			if ( ! empty( $this->scripts[ $handle ] ) ) {
				// If we registered it, it's one of our scripts.
				$handle = self::JS_PREFIX . $handle;
			}

			wp_enqueue_script( $handle );

			// Deferred localization.
			if ( ! empty( $this->deferred_localizations[ $this->current_handle ] ) ) {
				array_map( [ $this, 'localize' ], $this->deferred_localizations[ $this->current_handle ] );
				unset( $this->deferred_localizations[ $this->current_handle ] );
			}
		}

		return $this;
	}

	/**
	 * Dequeue a script.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the script. Should be unique. Can be an array to dequeue several scripts.
	 * @return object                This class instance.
	 */
	public function dequeue_script( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			// Enqueue the corresponding style.
			if ( ! empty( $this->styles[ $handle ] ) ) {
				$this->dequeue_style( $handle );
			}

			$this->current_handle      = $handle;
			$this->current_handle_type = 'js';

			if ( ! empty( $this->scripts[ $handle ] ) ) {
				// If we registered it, it's one of our scripts.
				$handle = self::JS_PREFIX . $handle;
			}

			wp_dequeue_script( $handle );
		}

		return $this;
	}

	/**
	 * Localize a script.
	 *
	 * @since 1.6.10
	 *
	 * @param  string            $handle      Name of the script. Should be unique.
	 * @param  string            $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable. Example: '/[a-zA-Z0-9_]+/'.
	 * @param  string|array|null $l10n        The data itself. The data can be either a single or multi-dimensional array. If null, $handle is used.
	 * @return object                         This class instance.
	 */
	public function localize_script( $handle, $object_name, $l10n = null ) {
		$this->current_handle      = $handle;
		$this->current_handle_type = 'js';

		if ( ! isset( $l10n ) ) {
			$l10n = $handle;
		}

		if ( is_string( $l10n ) ) {
			$l10n = $this->get_localization_data( $l10n );
		}

		if ( ! $l10n ) {
			return $this;
		}

		if ( ! empty( $this->scripts[ $handle ] ) ) {
			// If we registered it, it's one of our scripts.
			$handle = self::JS_PREFIX . $handle;
		}

		wp_localize_script( $handle, $object_name, $l10n );

		return $this;
	}

	/**
	 * Enqueue a style and a script that have the same handle.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the script. Should be unique. Can be an array to enqueue several scripts.
	 * @return object                This class instance.
	 */
	public function enqueue_assets( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			$this->enqueue_script( $handle );
		}

		return $this;
	}

	/**
	 * Dequeue a style and a script that have the same handle.
	 *
	 * @since 1.6.10
	 *
	 * @param  string|array $handles Name of the script. Should be unique. Can be an array to dequeue several scripts.
	 * @return object                This class instance.
	 */
	public function dequeue_assets( $handles ) {
		$handles = (array) $handles;

		foreach ( $handles as $handle ) {
			$this->dequeue_style( $handle );
			$this->dequeue_script( $handle );
		}

		return $this;
	}

	/**
	 * Enqueue the current script or style.
	 *
	 * @since 1.6.10
	 *
	 * @return object This class instance.
	 */
	public function enqueue() {
		if ( 'js' === $this->current_handle_type ) {
			$this->enqueue_script( $this->current_handle );
		} elseif ( 'css' === $this->current_handle_type ) {
			$this->enqueue_style( $this->current_handle );
		}

		return $this;
	}

	/**
	 * Localize the current script.
	 *
	 * @since 1.6.10
	 *
	 * @param  string            $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable. Example: '/[a-zA-Z0-9_]+/'.
	 * @param  string|array|null $l10n        The data itself. The data can be either a single or multi-dimensional array. If null, $handle is used.
	 * @return object                         This class instance.
	 */
	public function localize( $object_name, $l10n = null ) {
		return $this->localize_script( $this->current_handle, $object_name, $l10n );
	}

	/**
	 * Localize the current script when it is enqueued with `$this->enqueue()` or `$this->enqueue_script()`. This should be used right after `$this->register_script()`.
	 * Be careful, it won't work if the script is enqueued because it's a dependency.
	 * This is handy to not forget to localize the script later. It also prevents to localize the script right away, and maybe execute all localizations while the script is not enqueued (so we localize for nothing).
	 *
	 * @since 1.6.10
	 *
	 * @param  string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable. Example: '/[a-zA-Z0-9_]+/'.
	 * @return object              This class instance.
	 */
	public function defer_localization( $object_name ) {
		if ( ! isset( $this->deferred_localizations[ $this->current_handle ] ) ) {
			$this->deferred_localizations[ $this->current_handle ] = [];
		}

		$this->deferred_localizations[ $this->current_handle ][ $object_name ] = $object_name;

		return $this;
	}

	/**
	 * Remove a deferred localization.
	 *
	 * @since 1.6.10
	 *
	 * @param  string $handle      Name of the script. Should be unique.
	 * @param  string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable. Example: '/[a-zA-Z0-9_]+/'.
	 * @return object              This class instance.
	 */
	public function remove_deferred_localization( $handle, $object_name = null ) {
		if ( empty( $this->deferred_localizations[ $handle ] ) ) {
			return $this;
		}

		if ( $object_name ) {
			unset( $this->deferred_localizations[ $handle ][ $object_name ] );
		} else {
			unset( $this->deferred_localizations[ $handle ] );
		}

		return $this;
	}

	/**
	 * Get all translations we can use with wp_localize_script().
	 *
	 * @since 1.6.10
	 *
	 * @param  string $context      The translation context.
	 * @param  array  $more_data    More data to merge.
	 * @return array  $translations The translations.
	 */
	public function get_localization_data( $context, $more_data = [] ) {
		$data = get_imagify_localize_script_translations( $context );

		if ( $more_data ) {
			return array_merge( $data, $more_data );
		}

		return $data;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Prefix the dependencies if they are ours.
	 *
	 * @since 1.6.10
	 *
	 * @param  array  $dependencies An array of registered script handles this script depends on.
	 * @param  string $type         Type of dependency: css or js.
	 * @return array
	 */
	protected function prefix_dependencies( $dependencies, $type = 'js' ) {
		if ( ! $dependencies ) {
			return [];
		}

		if ( 'js' === $type ) {
			$prefix  = self::JS_PREFIX;
			$scripts = $this->scripts;
		} else {
			$prefix  = self::CSS_PREFIX;
			$scripts = $this->styles;
		}

		$depts = [];

		foreach ( $dependencies as $dept ) {
			if ( ! empty( $scripts[ $dept ] ) ) {
				$depts[] = $prefix . $dept;
			} else {
				$depts[] = $dept;
			}
		}

		return $depts;
	}

	/**
	 * Tell if debug is on.
	 *
	 * @since 1.6.10
	 *
	 * @return bool
	 */
	protected function is_debug() {
		return defined( 'IMAGIFY_DEBUG' ) && IMAGIFY_DEBUG || defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Tell if the admin bar item is displaying.
	 *
	 * @since 1.6.10
	 *
	 * @return bool
	 */
	protected function is_admin_bar_item_showing() {
		if ( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT ) {
			return false;
		}

		return get_imagify_option( 'api_key' ) && is_admin_bar_showing() && imagify_get_context( 'wp' )->current_user_can( 'manage' ) && get_imagify_option( 'admin_bar_menu' );
	}
}
