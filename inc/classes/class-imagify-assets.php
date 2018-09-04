<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles stylesheets and JavaScripts.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 */
class Imagify_Assets {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

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
	protected $styles = array();

	/**
	 * An array containing our registered scripts.
	 *
	 * @var array
	 */
	protected $scripts = array();

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
	protected $deferred_localizations = array();

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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts_frontend' ) );
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ), IMAGIFY_INT_MAX );
		add_action( 'wp_enqueue_media',      array( $this, 'enqueue_media_modal' ) );

		add_action( 'admin_footer-media_page_imagify-bulk-optimization', array( $this, 'print_support_script' ) );
		add_action( 'admin_footer-settings_page_imagify',                array( $this, 'print_support_script' ) );
	}

	/**
	 * Enqueue stylesheets and scripts for the frontend.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function enqueue_styles_and_scripts_frontend() {
		if ( ! $this->is_admin_bar_item_showing() ) {
			return;
		}

		$this->register_style( 'admin-bar' );
		$this->register_script( 'admin-bar', 'admin-bar', array( 'jquery' ) );

		$this->enqueue_assets( 'admin-bar' )->localize( 'imagifyAdminBar' );
	}

	/**
	 * Register stylesheets and scripts for the administration area.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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
		$this->register_style( 'sweetalert-core', 'sweetalert2', array(), '4.6.6' );

		/**
		 * Imagify Styles.
		 */
		$this->register_style( 'sweetalert', 'sweetalert-custom', array( 'sweetalert-core' ) );

		$this->register_style( 'admin-bar' );

		$this->register_style( 'admin' );

		$this->register_style( 'notices', 'notices', array( 'admin' ) ); // Needs SweetAlert on some cases.

		$this->register_style( 'twentytwenty', 'twentytwenty', array( 'admin' ) );

		$this->register_style( 'pricing-modal', 'pricing-modal', array( 'admin' ) );

		$this->register_style( 'bulk', 'bulk', array( 'sweetalert', 'admin' ) );

		$this->register_style( 'options', 'options', array( 'sweetalert', 'admin' ) );

		$this->register_style( 'files-list', 'files-list', array( 'admin' ) );

		/**
		 * 3rd Party Scripts.
		 */
		$this->register_script( 'promise-polyfill', 'es6-promise.auto', array(), '4.1.1' );

		$this->register_script( 'sweetalert', 'sweetalert2', array( 'promise-polyfill' ), '4.6.6' )->localize( 'imagifySwal' );

		$this->register_script( 'chart', 'chart', array(), '2.7.1.0' );

		$this->register_script( 'event-move', 'jquery.event.move', array( 'jquery' ), '2.0.1' );

		/**
		 * Imagify Scripts.
		 */
		$this->register_script( 'admin-bar', 'admin-bar', array( 'jquery' ) )->defer_localization( 'imagifyAdminBar' );

		$this->register_script( 'admin', 'admin', array( 'jquery' ) );

		$this->register_script( 'notices', 'notices', array( 'jquery', 'admin' ) )->defer_localization( 'imagifyNotices' ); // Needs SweetAlert on some cases.

		$this->register_script( 'twentytwenty', 'jquery.twentytwenty', array( 'jquery', 'event-move', 'chart', 'admin' ) )->defer_localization( 'imagifyTTT' );

		$this->register_script( 'media-modal', 'media-modal', array( 'jquery', 'chart', 'admin' ) );

		$this->register_script( 'pricing-modal', 'pricing-modal', array( 'jquery', 'admin' ) )->defer_localization( 'imagifyPricingModal' );

		$this->register_script( 'library', 'library', array( 'jquery', 'media-modal' ) )->defer_localization( 'imagifyLibrary' );

		$this->register_script( 'async', 'imagify-gulp', array(), '2017-07-28' );

		$this->register_script( 'bulk', 'bulk', array( 'jquery', 'heartbeat', 'underscore', 'chart', 'sweetalert', 'async', 'admin' ) )->defer_localization( 'imagifyBulk' );

		$this->register_script( 'options', 'options', array( 'jquery', 'sweetalert', 'underscore', 'admin' ) )->defer_localization( 'imagifyOptions' );

		$this->register_script( 'files-list', 'files-list', array( 'jquery', 'chart', 'admin' ) )->defer_localization( 'imagifyFiles' );
	}

	/**
	 * Enqueue stylesheets and scripts for the administration area.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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
		$notices = Imagify_Notices::get_instance();

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
			$this->enqueue_assets( array( 'pricing-modal', 'bulk' ) );
		}

		/*
		 * Loaded in the settings page.
		 */
		if ( imagify_is_screen( 'imagify-settings' ) ) {
			$this->enqueue_assets( array( 'sweetalert', 'notices', 'twentytwenty', 'pricing-modal', 'options' ) );
		}

		/*
		 * Loaded in the files list page.
		 */
		if ( imagify_is_screen( 'files-list' ) ) {
			$this->enqueue_assets( array( 'files-list', 'twentytwenty' ) );
		}

		/**
		 * Triggered after Imagify CSS and JS have been enqueued.
		 *
		 * @since 1.6.10
		 * @author Grégory Viguier
		 */
		do_action( 'imagify_assets_enqueued' );
	}

	/**
	 * Enqueue stylesheets and scripts for the media modal.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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

		/**
		 * Triggered after Imagify CSS and JS have been enqueued for the media modal.
		 *
		 * @since 1.6.10
		 * @author Grégory Viguier
		 */
		do_action( 'imagify_media_modal_assets_enqueued' );
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization.
	 * Previously was _imagify_admin_print_intercom()
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function print_support_script() {
		if ( ! Imagify_Requirements::is_api_key_valid() ) {
			return;
		}

		$user = get_imagify_user();

		if ( empty( $user->is_intercom ) || empty( $user->display_support ) ) {
			return;
		}
		?>
		<script>
		window.intercomSettings = {
			app_id: 'cd6nxj3z',
			user_id: <?php echo (int) $user->id; ?>
		};
		(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/cd6nxj3z';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()
		</script>
		<?php
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC TOOLS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Register a style.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string      $handle       Name of the stylesheet. Should be unique.
	 * @param  string|null $file_name    The file name, without the extension. If null, $handle is used.
	 * @param  array       $dependencies An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string|null $version      String specifying stylesheet version number. If set to null, the plugin version is used. If SCRIPT_DEBUG is true, a random string is used.
	 * @return object                    This class instance.
	 */
	public function register_style( $handle, $file_name = null, $dependencies = array(), $version = null ) {
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
			IMAGIFY_ASSETS_CSS_URL . $file_name . $extension,
			$dependencies,
			$version
		);

		return $this;
	}

	/**
	 * Enqueue a style.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string      $handle       Name of the script. Should be unique.
	 * @param  string|null $file_name    The file name, without the extension. If null, $handle is used.
	 * @param  array       $dependencies An array of registered script handles this script depends on.
	 * @param  string|null $version      String specifying script version number. If set to null, the plugin version is used. If SCRIPT_DEBUG is true, a random string is used.
	 * @return object                    This class instance.
	 */
	public function register_script( $handle, $file_name = null, $dependencies = array(), $version = null ) {
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
			IMAGIFY_ASSETS_JS_URL . $file_name . $extension,
			$dependencies,
			$version,
			true
		);

		return $this;
	}

	/**
	 * Enqueue a script.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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

			$this->maybe_register_heartbeat( $handle );

			if ( ! empty( $this->scripts[ $handle ] ) ) {
				// If we registered it, it's one of our scripts.
				$handle = self::JS_PREFIX . $handle;
			}

			wp_enqueue_script( $handle );

			// Deferred localization.
			if ( ! empty( $this->deferred_localizations[ $this->current_handle ] ) ) {
				array_map( array( $this, 'localize' ), $this->deferred_localizations[ $this->current_handle ] );
				unset( $this->deferred_localizations[ $this->current_handle ] );
			}
		}

		return $this;
	}

	/**
	 * Dequeue a script.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable. Example: '/[a-zA-Z0-9_]+/'.
	 * @return object              This class instance.
	 */
	public function defer_localization( $object_name ) {
		if ( ! isset( $this->deferred_localizations[ $this->current_handle ] ) ) {
			$this->deferred_localizations[ $this->current_handle ] = array();
		}

		$this->deferred_localizations[ $this->current_handle ][ $object_name ] = $object_name;

		return $this;
	}

	/**
	 * Remove a deferred localization.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
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
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $context      The translation context.
	 * @param  array  $more_data    More data to merge.
	 * @return array  $translations The translations.
	 */
	public function get_localization_data( $context, $more_data = array() ) {
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
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  array  $dependencies An array of registered script handles this script depends on.
	 * @param  string $type         Type of dependency: css or js.
	 * @return array
	 */
	protected function prefix_dependencies( $dependencies, $type = 'js' ) {
		if ( ! $dependencies ) {
			return array();
		}

		if ( 'js' === $type ) {
			$prefix  = self::JS_PREFIX;
			$scripts = $this->scripts;
		} else {
			$prefix  = self::CSS_PREFIX;
			$scripts = $this->styles;
		}

		$depts = array();

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
	 * Make sure Heartbeat is registered if the given script requires it.
	 * Lots of people love deregister Heartbeat.
	 *
	 * @since  1.6.11
	 * @author Grégory Viguier
	 *
	 * @param  string $handle Name of the script. Should be unique.
	 */
	protected function maybe_register_heartbeat( $handle ) {
		if ( wp_script_is( 'heartbeat', 'registered' ) ) {
			return;
		}

		if ( ! empty( $this->scripts[ $handle ] ) ) {
			// If we registered it, it's one of our scripts.
			$handle = self::JS_PREFIX . $handle;
		}

		$dependencies = wp_scripts()->query( $handle );

		if ( ! $dependencies || ! $dependencies->deps ) {
			return;
		}

		$dependencies = array_flip( $dependencies->deps );

		if ( ! isset( $dependencies['heartbeat'] ) ) {
			return;
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'heartbeat', "/wp-includes/js/heartbeat$suffix.js", array( 'jquery' ), false, true );
	}

	/**
	 * Tell if debug is on.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_debug() {
		return defined( 'IMAGIFY_DEBUG' ) && IMAGIFY_DEBUG || defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Tell if the admin bar item is displaying.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_admin_bar_item_showing() {
		if ( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT ) {
			return false;
		}

		return get_imagify_option( 'api_key' ) && is_admin_bar_showing() && imagify_current_user_can() && get_imagify_option( 'admin_bar_menu' );
	}
}
