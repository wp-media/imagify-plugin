<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
	const VERSION = '1.0';

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
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_action( 'wp_enqueue_media',      array( $this, 'enqueue_media_modal' ) );

		add_action( 'admin_footer-media_page_imagify-bulk-optimization', array( $this, 'print_intercom' ) );
		add_action( 'admin_footer-settings_page_imagify',                array( $this, 'print_intercom' ) );
	}

	/**
	 * Register stylesheets and scripts.
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
		$this->register_style( 'sweetalert', 'sweetalert2', array(), '4.6.6' );

		/**
		 * Imagify Styles.
		 */
		$this->register_style( 'twentytwenty' );

		$this->register_style( 'admin' );

		/**
		 * 3rd Party Scripts.
		 */
		$this->register_script( 'promise-polyfill', 'es6-promise.auto', array(), '4.1.1' );

		$this->register_script( 'sweetalert', 'sweetalert2', array( 'jquery', 'promise-polyfill' ), '4.6.6' );

		$this->register_script( 'chart', 'chart', array(), '1.0.2' );

		$this->register_script( 'event-move', 'jquery.event.move', array( 'jquery' ), '2.0.1' );

		/**
		 * Imagify Scripts.
		 */
		$this->register_script( 'async', 'imagify-gulp', array(), '2017-07-28' );

		$this->register_script( 'admin', 'admin', array( 'jquery', 'sweetalert' ) );

		$this->register_script( 'twentytwenty', 'jquery.twentytwenty', array( 'jquery', 'event-move', 'chart' ) );

		$this->register_script( 'options', 'options', array( 'jquery', 'sweetalert', 'twentytwenty' ) );

		$this->register_script( 'media-modal', 'media-modal', array( 'jquery', 'chart' ) );

		$this->register_script( 'library', 'library', array( 'jquery', 'media-modal' ) );

		$this->register_script( 'bulk', 'bulk', array( 'jquery', 'heartbeat', 'chart', 'sweetalert', 'async' ) );
	}

	/**
	 * Enqueue stylesheets and scripts.
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

		/*
		 * Loaded in the whole admnistration.
		 */
		$this->enqueue_style( array( 'admin', 'sweetalert' ) );

		$this->enqueue_script( 'admin' )->localize( 'imagifyAdmin' );

		/**
		 * Loaded in the library and post.php (for attachment post type).
		 */
		if ( $this->is_screen( 'library' ) || $this->is_screen( 'attachment' ) ) {
			$this->enqueue_script( 'twentytwenty' )->localize( 'imagifyTTT' );
		}

		/**
		 * Loaded in the library.
		 */
		if ( $this->is_screen( 'library' ) ) {
			$library_data = $this->get_localization_data( 'library', array(
				'backup_option' => (int) get_imagify_option( 'backup' ),
			) );

			$this->enqueue_script( 'library' )->localize( 'imagifyUpload', $library_data );
		}

		/**
		 * Loaded in the bulk optimization page.
		 */
		if ( $this->is_screen( 'bulk-optimization' ) ) {
			$bulk_data = $this->get_localization_data( 'bulk', array(
				'heartbeat_id' => 'update_bulk_data',
				'ajax_action'  => 'imagify_get_unoptimized_attachment_ids',
				'ajax_context' => 'wp',
				'buffer_size'  => get_imagify_bulk_buffer_size(),
			) );

			/**
			 * Filter the number of parallel queries during the Bulk Optimization.
			 *
			 * @since 1.5.4
			 *
			 * @param int $buffer_size Number of parallel queries.
			 */
			$bulk_data['buffer_size'] = apply_filters( 'imagify_bulk_buffer_size', $bulk_data['buffer_size'] );

			$this->enqueue_script( 'bulk' )->localize( 'imagifyBulk', $bulk_data );
		}

		/*
		 * Loaded in settings page.
		 */
		if ( $this->is_screen( 'imagify-settings' ) ) {
			$this->enqueue_style( 'twentytwenty' );

			$this->enqueue_script( 'options' )->localize( 'imagifyOptions' );
		}
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

		$this->enqueue_script( 'media-modal' );
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization.
	 * Previously was _imagify_admin_print_intercom()
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 */
	public function print_intercom() {
		if ( ! imagify_valid_key() ) {
			return;
		}

		$user = get_imagify_user();

		if ( empty( $user->is_intercom ) || false === $user->display_support ) {
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
	 * Tell if the current screen is what we're looking for.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $identifier The screen "name".
	 * @return bool
	 */
	public function is_screen( $identifier = false ) {
		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		switch ( $identifier ) {
			case 'imagify-settings':
				// /wp-admin/options-general.php?page=imagify
				return 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base;

			case 'upload':
			case 'library':
				// /wp-admin/upload.php
				return 'upload' === $current_screen->base;

			case 'post':
				// /wp-admin/post.php (for any post type)
				return 'post' === $current_screen->base;

			case 'attachment':
				// /wp-admin/post.php (for attachment post type)
				return 'post' === $current_screen->base && 'attachment' === $current_screen->post_type;

			case 'bulk':
			case 'bulk-optimization':
				// /wp-admin/upload.php?page=imagify-bulk-optimization
				return 'media_page_imagify-bulk-optimization' === $current_screen->base;

			default:
				return ! $identifier;
		}
	}

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

			if ( ! empty( $this->scripts[ $handle ] ) ) {
				// If we registered it, it's one of our scripts.
				$handle = self::JS_PREFIX . $handle;
			}

			wp_enqueue_script( $handle );
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
			$this->enqueue_style( $handle );
			$this->enqueue_script( $handle );
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
}
