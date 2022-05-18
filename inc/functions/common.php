<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get the list of the names of the Imagify context currently in use.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @return array An array of strings.
 */
function imagify_get_context_names() {
	static $contexts;

	if ( isset( $contexts ) ) {
		return $contexts;
	}

	/**
	 * Register new contexts.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param array $contexts An array of context names.
	 */
	$contexts = (array) apply_filters( 'imagify_register_context', [] );

	$contexts = array_filter( $contexts, function( $context ) {
		return $context && is_string( $context );
	} );
	$contexts = array_merge( [ 'wp', 'custom-folders' ], $contexts );

	sort( $contexts );

	return $contexts;
}

/**
 * Sanitize an optimization context.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $context The context.
 * @return string
 */
function imagify_sanitize_context( $context ) {
	return sanitize_key( $context );
}

/**
 * Get the Imagify context instance.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  string $context  The context name. Default values are 'wp' and 'custom-folders'.
 * @return ContextInterface The context instance.
 */
function imagify_get_context( $context ) {
	$class_name = imagify_get_context_class_name( $context );
	return $class_name::get_instance();
}

/**
 * Get the Imagify context class name.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
 * @return string          The context class name.
 */
function imagify_get_context_class_name( $context ) {
	$context = imagify_sanitize_context( $context );

	switch ( $context ) {
		case 'wp':
			$class_name = '\\Imagify\\Context\\WP';
			break;

		case 'custom-folders':
			$class_name = '\\Imagify\\Context\\CustomFolders';
			break;

		default:
			$class_name = '\\Imagify\\Context\\Noop';
	}

	/**
	 * Filter the name of the class to use to define a context.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param int    $class_name The class name.
	 * @param string $context    The context name.
	 */
	$class_name = apply_filters( 'imagify_context_class_name', $class_name, $context );

	return '\\' . ltrim( $class_name, '\\' );
}

/**
 * Get the Imagify process instance depending on a context.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  int    $media_id The media ID.
 * @param  string $context  The context name. Default values are 'wp' and 'custom-folders'.
 * @return ProcessInterface The optimization process instance.
 */
function imagify_get_optimization_process( $media_id, $context ) {
	$class_name = imagify_get_optimization_process_class_name( $context );
	return new $class_name( $media_id );
}

/**
 * Get the Imagify process class name depending on a context.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  string $context The context name. Default values are 'wp' and 'custom-folders'.
 * @return string          The optimization process class name.
 */
function imagify_get_optimization_process_class_name( $context ) {
	$context = imagify_sanitize_context( $context );

	switch ( $context ) {
		case 'wp':
			$class_name = '\\Imagify\\Optimization\\Process\\WP';
			break;

		case 'custom-folders':
			$class_name = '\\Imagify\\Optimization\\Process\\CustomFolders';
			break;

		default:
			$class_name = '\\Imagify\\Optimization\\Process\\Noop';
	}

	/**
	 * Filter the name of the class to use for the optimization.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param int    $class_name The class name.
	 * @param string $context    The context name.
	 */
	$class_name = apply_filters( 'imagify_process_class_name', $class_name, $context );

	return '\\' . ltrim( $class_name, '\\' );
}

/**
 * Get WP Direct filesystem object. Also define chmod constants if not done yet.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @return object A Imagify_Filesystem object.
 */
function imagify_get_filesystem() {
	return Imagify_Filesystem::get_instance();
}

/**
 * Convert a path (or URL) to its WebP version.
 * To keep the function simple:
 * - Not tested if it's an image.
 * - File existance is not tested.
 * - If an URL is given, make sure it doesn't contain query args.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  string $path A file path or URL.
 * @return string
 */
function imagify_path_to_webp( $path ) {
	return $path . '.webp';
}

/**
 * Tell if the current user can optimize custom folders.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return bool
 */
function imagify_can_optimize_custom_folders() {
	static $can;

	if ( isset( $can ) ) {
		return $can;
	}

	// Check if the DB tables are ready.
	if ( ! Imagify_Folders_DB::get_instance()->can_operate() || ! Imagify_Files_DB::get_instance()->can_operate() ) {
		$can = false;
		return $can;
	}

	// Check for user capacity.
	$can = imagify_get_context( 'custom-folders' )->current_user_can( 'optimize' );

	return $can;
}

/**
 * Simple helper to get some external URLs, like to the documentation.
 *
 * @since  1.6.12
 * @author Grégory Viguier
 *
 * @param  string $target     What we want.
 * @param  array  $query_args An array of query arguments.
 * @return string The URL.
 */
function imagify_get_external_url( $target, $query_args = array() ) {
	$site_url = IMAGIFY_SITE_DOMAIN . '/';
	$app_url  = IMAGIFY_APP_DOMAIN . '/#/';

	switch ( $target ) {
		case 'plugin':
			/* translators: Plugin URI of the plugin/theme */
			$url = __( 'https://wordpress.org/plugins/imagify/', 'imagify' );
			break;

		case 'rate':
			$url = 'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform';
			break;

		case 'share-twitter':
			$url = rawurlencode( imagify_get_external_url( 'plugin' ) );
			$url = 'https://twitter.com/intent/tweet?source=webclient&original_referer=' . $url . '&url=' . $url . '&related=imagify&hastags=performance,web,wordpress';
			break;

		case 'share-facebook':
			$url = rawurlencode( imagify_get_external_url( 'plugin' ) );
			$url = 'https://www.facebook.com/sharer/sharer.php?u=' . $url;
			break;

		case 'contact':
			$lang  = imagify_get_current_lang_in( 'fr' );
			$paths = array(
				'en' => 'contact',
				'fr' => 'fr/contact',
			);

			$url = $site_url . $paths[ $lang ] . '/';
			break;

		case 'documentation':
			$url = $site_url . 'documentation/';
			break;

		case 'documentation-imagick-gd':
			$url = $site_url . 'documentation/solve-imagemagick-gd-required/';
			break;

		case 'register':
			$partner = imagify_get_partner();

			if ( $partner ) {
				$query_args['partner'] = $partner;
			}

			$url = $app_url . 'register';
			break;

		case 'subscription':
			$url = $app_url . 'subscription';
			break;

		case 'get-api-key':
			$url = $app_url . 'api';
			break;

		case 'payment':
			// Don't remove the trailing slash.
			$url = $app_url . 'plugin/';
			break;

		default:
			return '';
	}

	if ( $query_args ) {
		$url = add_query_arg( $query_args, $url );
	}

	return $url;
}

/**
 * Get the current lang ('fr', 'en', 'de'...), limited to a given list.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @param  array $langs An array of langs, like array( 'de', 'es', 'fr', 'it' ).
 * @return string The current lang. Default is 'en'.
 */
function imagify_get_current_lang_in( $langs ) {
	static $locale;

	if ( ! isset( $locale ) ) {
		$locale = imagify_get_locale();
		$locale = explode( '_', strtolower( $locale . '_' ) ); // Trailing underscore is to make sure $locale[1] is set.
	}

	foreach ( (array) $langs as $lang ) {
		if ( $lang === $locale[0] || $lang === $locale[1] ) {
			return $lang;
		}
	}

	return 'en';
}

/**
 * Get the current locale.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @return string The current locale.
 */
function imagify_get_locale() {
	$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
	/**
	 * Filter the locale used by Imagify.
	 *
	 * @since  1.6.14
	 * @author Grégory Viguier
	 *
	 * @param string $locale The current locale.
	 */
	return apply_filters( 'imagify_locale', $locale );
}

/**
 * Get the label corresponding to the given optimization label.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  int|bool $level  Optimization level (between 0 and 2). False if no level.
 * @param  string   $format Format to display the label. Use %ICON% for the icon and %s for the label.
 * @return string           The label.
 */
function imagify_get_optimization_level_label( $level, $format = '%s' ) {
	if ( ! is_numeric( $level ) ) {
		return '';
	}

	if ( strpos( $format, '%ICON%' ) !== false ) {
		$icon = '<svg width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><g fill="#40B1D0" fill-rule="evenodd">';

		switch ( $level ) {
			case 2:
			case 1:
				$icon .= '<polygon points="11.6054688 11.6054688 8.7890625 11.6054688 8.7890625 0.39453125 11.6054688 0.39453125"/><polygon points="7.39453125 11.6054688 4.60546875 11.6054688 4.60546875 3.89453125 7.39453125 3.89453125"/><polygon points="3.2109375 11.6054688 0.39453125 11.6054688 0.39453125 6 3.2109375 6"/>';
				break;
			case 0:
				$icon .= '<polygon fill="#CCD1D6" points="11.6054688 11.6054688 8.7890625 11.6054688 8.7890625 0.39453125 11.6054688 0.39453125"/><polygon fill="#CCD1D6" points="7.39453125 11.6054688 4.60546875 11.6054688 4.60546875 3.89453125 7.39453125 3.89453125"/><polygon points="3.2109375 11.6054688 0.39453125 11.6054688 0.39453125 6 3.2109375 6"/>';
		}

		$icon .= '</g></svg>';

		$format = str_replace( '%ICON%', $icon, $format );
	}

	switch ( $level ) {
		case 2:
		case 1:
			return sprintf( $format, __( 'Smart', 'imagify' ) );
		case 0:
			return sprintf( $format, __( 'Lossless', 'imagify' ) );
	}

	return '';
}

/**
 * `array_merge()` + `array_intersect_key()`.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  array $values  The array we're interested in.
 * @param  array $default The array we use as boundaries.
 * @return array
 */
function imagify_merge_intersect( $values, $default ) {
	$values = array_merge( $default, (array) $values );
	return array_intersect_key( $values, $default );
}

/**
 * Returns true.
 * Useful for returning true to filters easily.
 * Similar to WP's __return_true() function, it allows to remove it from a filter without removing another one added by another plugin.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @return bool True.
 */
function imagify_return_true() {
	return true;
}

/**
 * Returns false.
 * Useful for returning false to filters easily.
 * Similar to WP's __return_false() function, it allows to remove it from a filter without removing another one added by another plugin.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @return bool False.
 */
function imagify_return_false() {
	return false;
}

/**
 * Marks a class as deprecated and informs when it has been used.
 * Similar to _deprecated_constructor(), but with different strings.
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param string $class        The class containing the deprecated constructor.
 * @param string $version      The version of WordPress that deprecated the function.
 * @param string $replacement  Optional. The function that should have been called. Default null.
 * @param string $parent_class Optional. The parent class calling the deprecated constructor. Default empty string.
 */
function imagify_deprecated_class( $class, $version, $replacement = null, $parent_class = '' ) {

	/**
	 * Fires when a deprecated class is called.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param string $class        The class containing the deprecated constructor.
	 * @param string $version      The version of WordPress that deprecated the function.
	 * @param string $replacement  Optional. The function that should have been called.
	 * @param string $parent_class The parent class calling the deprecated constructor.
	 */
	do_action( 'imagify_deprecated_class_run', $class, $version, $replacement, $parent_class );

	if ( ! WP_DEBUG ) {
		return;
	}

	/**
	 * Filters whether to trigger an error for deprecated classes.
	 *
	 * `WP_DEBUG` must be true in addition to the filter evaluating to true.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 *
	 * @param bool $trigger Whether to trigger the error for deprecated classes. Default true.
	 */
	if ( ! apply_filters( 'imagify_deprecated_class_trigger_error', true ) ) {
		return;
	}

	if ( function_exists( '__' ) ) {
		if ( ! empty( $parent_class ) ) {
			/**
			 * With parent class.
			 */
			if ( ! empty( $replacement ) ) {
				/**
				 * With replacement.
				 */
				call_user_func(
					'trigger_error',
					sprintf(
						/* translators: 1: PHP class name, 2: PHP parent class name, 3: version number, 4: replacement class name. */
						__( 'The called class %1$s extending %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.', 'imagify' ),
						'<code>' . $class . '</code>',
						'<code>' . $parent_class . '</code>',
						'<strong>' . $version . '</strong>',
						'<code>' . $replacement . '</code>'
					)
				);
				return;
			}

			/**
			 * Without replacement.
			 */
			call_user_func(
				'trigger_error',
				sprintf(
					/* translators: 1: PHP class name, 2: PHP parent class name, 3: version number. */
					__( 'The called class %1$s extending %2$s is <strong>deprecated</strong> since version %3$s!', 'imagify' ),
					'<code>' . $class . '</code>',
					'<code>' . $parent_class . '</code>',
					'<strong>' . $version . '</strong>'
				)
			);
			return;
		}

		/**
		 * Without parent class.
		 */
		if ( ! empty( $replacement ) ) {
			/**
			 * With replacement.
			 */
			call_user_func(
				'trigger_error',
				sprintf(
					/* translators: 1: PHP class name, 2: version number, 3: replacement class name. */
					__( 'The called class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'imagify' ),
					'<code>' . $class . '</code>',
					'<strong>' . $version . '</strong>',
					'<code>' . $replacement . '</code>'
				)
			);
			return;
		}

		/**
		 * Without replacement.
		 */
		call_user_func(
			'trigger_error',
			sprintf(
				/* translators: 1: PHP class name, 2: version number. */
				__( 'The called class %1$s is <strong>deprecated</strong> since version %2$s!', 'imagify' ),
				'<code>' . $class . '</code>',
				'<strong>' . $version . '</strong>'
			)
		);
		return;
	}

	if ( ! empty( $parent_class ) ) {
		/**
		 * With parent class.
		 */
		if ( ! empty( $replacement ) ) {
			/**
			 * With replacement.
			 */
			call_user_func(
				'trigger_error',
				sprintf(
					'The called class %1$s extending %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.',
					'<code>' . $class . '</code>',
					'<code>' . $parent_class . '</code>',
					'<strong>' . $version . '</strong>',
					'<code>' . $replacement . '</code>'
				)
			);
			return;
		}

		/**
		 * Without replacement.
		 */
		call_user_func(
			'trigger_error',
			sprintf(
				'The called class %1$s extending %2$s is <strong>deprecated</strong> since version %3$s!',
				'<code>' . $class . '</code>',
				'<code>' . $parent_class . '</code>',
				'<strong>' . $version . '</strong>'
			)
		);
		return;
	}

	/**
	 * Without parent class.
	 */
	if ( ! empty( $replacement ) ) {
		/**
		 * With replacement.
		 */
		call_user_func(
			'trigger_error',
			sprintf(
				'The called class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
				'<code>' . $class . '</code>',
				'<strong>' . $version . '</strong>',
				'<code>' . $replacement . '</code>'
			)
		);
		return;
	}

	/**
	 * Without replacement.
	 */
	call_user_func(
		'trigger_error',
		sprintf(
			'The called class %1$s is <strong>deprecated</strong> since version %2$s!',
			'<code>' . $class . '</code>',
			'<strong>' . $version . '</strong>'
		)
	);
}
