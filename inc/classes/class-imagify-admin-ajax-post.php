<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles admin ajax/post callbacks.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 */
class Imagify_Admin_Ajax_Post {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Actions to be triggered on admin ajax and admin post.
	 *
	 * @var array
	 */
	protected $ajax_post_actions = array(
		'imagify_manual_upload',
		'imagify_manual_override_upload',
		'imagify_optimize_missing_sizes',
		'imagify_restore_upload',
	);

	/**
	 * Actions to be triggered only on admin ajax.
	 *
	 * @var array
	 */
	protected $ajax_only_actions = array(
		'imagify_bulk_upload',
		'imagify_async_optimize_upload_new_media',
		'imagify_async_optimize_save_image_editor_file',
		'imagify_get_unoptimized_attachment_ids',
		'imagify_check_backup_dir_is_writable',
		'nopriv_imagify_rpc',
		'imagify_signup',
		'imagify_check_api_key_validity',
		'imagify_get_admin_bar_profile',
		'imagify_get_prices',
		'imagify_check_coupon',
		'imagify_get_discount',
		'imagify_get_images_counts',
		'imagify_update_estimate_sizes',
	);

	/**
	 * Actions to be triggered only on admin post.
	 *
	 * @var array
	 */
	protected $post_only_actions = array();

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
	protected function __construct() {}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC METHODS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.11
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
	 * @since  1.6.11
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Actions triggered only on admin ajax.
			$actions = array_merge( $this->ajax_post_actions, $this->ajax_only_actions );

			foreach ( $actions as $action ) {
				add_action( 'wp_ajax_' . $action, array( $this, $action . '_callback' ) );
			}
		}

		// Actions triggered on both admin ajax and admin post.
		$actions = array_merge( $this->ajax_post_actions, $this->post_only_actions );

		foreach ( $actions as $action ) {
			add_action( 'admin_post_' . $action, array( $this, $action . '_callback' ) );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** MANUAL OPTIMIZATION ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize all thumbnails of a specific image with the manual method.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_manual_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-manual-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_manual_upload' );

		// Optimize it!!!!!
		$attachment->optimize();

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Optimize all thumbnails of a specific image with a different optimization level.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_manual_override_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-manual-override-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_manual_override_upload' );

		// Restore the backup file.
		$attachment->restore();

		// Optimize it!!!!!
		$attachment->optimize( (int) $_GET['optimization_level'] );

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Optimize one or some thumbnails that are not optimized yet.
	 *
	 * @since  1.6.11
	 * @author Grégory Viguier
	 */
	public function imagify_optimize_missing_sizes_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-optimize-missing-sizes-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_optimize_missing_sizes' );

		// Optimize the missing thumbnails.
		$attachment->optimize_missing_thumbnails();

		imagify_maybe_redirect();

		// Return the optimization statistics.
		$output = get_imagify_attachment_optimization_text( $attachment, $context );
		wp_send_json_success( $output );
	}

	/**
	 * Process a restoration to the original attachment.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_restore_upload_callback() {
		if ( empty( $_GET['attachment_id'] ) || empty( $_GET['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_GET['context'] );
		$attachment_id = absint( $_GET['attachment_id'] );

		imagify_check_nonce( 'imagify-restore-upload-' . $attachment_id . '-' . $context );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_restore_upload' );

		// Restore the backup file.
		$attachment->restore();

		imagify_maybe_redirect();

		// Return the optimization button.
		$output = get_imagify_admin_url( 'manual-upload', array( 'attachment_id' => $attachment->id, 'context' => $context ) );
		$output = '<a id="imagify-upload-' . $attachment->id . '" href="' . esc_url( $output ) . '" class="button-primary button-imagify-manual-upload" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Optimize', 'imagify' ) . '</a>';
		wp_send_json_success( $output );
	}

	/**
	 * Optimize all thumbnails of a specific image with the bulk method.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_bulk_upload_callback() {
		if ( empty( $_POST['image'] ) || empty( $_POST['context'] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['image'] );

		imagify_check_nonce( 'imagify-bulk-upload', 'imagifybulkuploadnonce' );
		imagify_check_user_capacity( 'bulk-optimize', $attachment_id );

		$attachment         = get_imagify_attachment( $context, $attachment_id, 'imagify_bulk_upload' );
		$optimization_level = get_transient( 'imagify_bulk_optimization_level' );

		// Restore it if the optimization level is updated.
		if ( $optimization_level !== $attachment->get_optimization_level() ) {
			$attachment->restore();
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level );

		// Return the optimization statistics.
		$fullsize_data = $attachment->get_size_data();
		$stats_data    = $attachment->get_stats_data();
		$user          = new Imagify_User();
		$data          = array();

		if ( ! $attachment->is_optimized() ) {
			$data['success'] = false;
			$data['error']   = $fullsize_data['error'];

			imagify_die( $data );
		}

		$data['success']               = true;
		$data['original_size']         = $fullsize_data['original_size'];
		$data['new_size']              = $fullsize_data['optimized_size'];
		$data['percent']               = $fullsize_data['percent'];
		$data['overall_saving']        = $stats_data['original_size'] - $stats_data['optimized_size'];
		$data['original_overall_size'] = $stats_data['original_size'];
		$data['new_overall_size']      = $stats_data['optimized_size'];
		$data['thumbnails']            = $attachment->get_optimized_sizes_count();

		wp_send_json_success( $data );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** AUTOMATIC OPTIMIZATION ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.6.11
	 * @author Julio Potier
	 * @see    _imagify_optimize_attachment()
	 */
	public function imagify_async_optimize_upload_new_media_callback() {
		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['metadata'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			return;
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['attachment_id'] );

		imagify_check_nonce( 'new_media-' . $attachment_id );
		imagify_check_user_capacity( 'auto-optimize' );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_async_optimize_upload_new_media' );

		// Optimize it!!!!!
		$attachment->optimize( null, $_POST['metadata'] );
		die( 1 );
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.6.11
	 * @author Julio Potier
	 */
	public function imagify_async_optimize_save_image_editor_file_callback() {
		$attachment_id = ! empty( $_POST['postid'] ) ? absint( $_POST['postid'] ) : 0;

		if ( ! $attachment_id || empty( $_POST['do'] ) ) {
			return;
		}

		imagify_check_nonce( 'image_editor-' . $attachment_id );
		imagify_check_user_capacity( 'edit_post', $attachment_id );

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'wp_ajax_imagify_async_optimize_save_image_editor_file' );

		if ( ! $attachment->get_data() ) {
			return;
		}

		$optimization_level = $attachment->get_optimization_level();
		$metadata           = wp_get_attachment_metadata( $attachment_id );

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		if ( 'restore' === $_POST['do'] ) {
			// Restore the backup file.
			$attachment->restore();

			// Get old metadata to regenerate all thumbnails.
			$metadata     = array( 'sizes' => array() );
			$backup_sizes = (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			foreach ( $backup_sizes as $size_key => $size_data ) {
				$size_key = str_replace( '-origin', '' , $size_key );
				$metadata['sizes'][ $size_key ] = $size_data;
			}
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level, $metadata );
		die( 1 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS FOR OPTIMIZATION ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_get_unoptimized_attachment_ids_callback() {
		global $wpdb;

		imagify_check_nonce( 'imagify-bulk-upload', 'imagifybulkuploadnonce' );
		imagify_check_user_capacity( 'bulk-optimize' );

		if ( ! imagify_valid_key() ) {
			wp_send_json_error( array( 'message' => 'invalid-api-key' ) );
		}

		$user = new Imagify_User();

		if ( $user->is_over_quota() ) {
			wp_send_json_error( array( 'message' => 'over-quota' ) );
		}

		@set_time_limit( 0 );

		// Get (ordered) IDs.
		$optimization_level = (int) $_GET['optimization_level'];
		$optimization_level = -1 !== $optimization_level ? $optimization_level : (int) get_imagify_option( 'optimization_level', 1 );

		Imagify_DB::unlimit_joins();

		$mime_types = Imagify_DB::get_mime_types();
		$ids        = $wpdb->get_col( $wpdb->prepare( // WPCS: unprepared SQL ok.
			"SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta
				ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_imagify_optimization_level' )
			LEFT JOIN $wpdb->postmeta AS mt1
				ON ( $wpdb->posts.ID = mt1.post_id AND mt1.meta_key = '_imagify_status' )
			INNER JOIN $wpdb->postmeta AS mt2
				ON ( $wpdb->posts.ID = mt2.post_id AND mt2.meta_key = '_wp_attached_file' )
			INNER JOIN $wpdb->postmeta AS mt3
				ON ( $wpdb->posts.ID = mt3.post_id AND mt3.meta_key = '_wp_attachment_metadata' )
			WHERE
				$wpdb->posts.post_mime_type IN ( $mime_types )
				AND (
					$wpdb->postmeta.meta_value != '%d'
					OR
					$wpdb->postmeta.post_id IS NULL
					OR
					mt1.meta_value = 'error'
				)
				AND $wpdb->posts.post_type = 'attachment'
				AND $wpdb->posts.post_status = 'inherit'
			GROUP BY $wpdb->posts.ID
			ORDER BY
				CASE mt1.meta_value
					WHEN 'already_optimized' THEN 2
					ELSE 1
				END ASC,
				$wpdb->posts.ID DESC
			LIMIT 0, %d",
			$optimization_level,
			imagify_get_unoptimized_attachment_limit()
		) );

		$wpdb->flush();
		unset( $mime_types );
		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( ! $ids ) {
			wp_send_json_error( array( 'message' => 'no-images' ) );
		}

		$results = Imagify_DB::get_metas( array(
			// Get attachments filename.
			'filenames'           => '_wp_attached_file',
			// Get attachments data.
			'data'                => '_imagify_data',
			// Get attachments optimization level.
			'optimization_levels' => '_imagify_optimization_level',
			// Get attachments status.
			'statuses'            => '_imagify_status',
		), $ids );

		// Save the optimization level in a transient to retrieve it later during the process.
		set_transient( 'imagify_bulk_optimization_level', $optimization_level );

		// First run.
		foreach ( $ids as $i => $id ) {
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;
			$attachment_error              = '';

			if ( isset( $results['data'][ $id ]['sizes']['full']['error'] ) ) {
				$attachment_error = $results['data'][ $id ]['sizes']['full']['error'];
			}

			// Don't try to re-optimize if the optimization level is still the same.
			if ( $optimization_level === $attachment_optimization_level && is_string( $attachment_error ) ) {
				unset( $ids[ $i ] );
				continue;
			}

			// Don't try to re-optimize images already compressed.
			if ( 'already_optimized' === $attachment_status && $attachment_optimization_level >= $optimization_level ) {
				unset( $ids[ $i ] );
				continue;
			}

			$attachment_error = trim( $attachment_error );

			// Don't try to re-optimize images with an empty error message.
			if ( 'error' === $attachment_status && empty( $attachment_error ) ) {
				unset( $ids[ $i ] );
			}
		}

		if ( ! $ids ) {
			wp_send_json_error( array( 'message' => 'no-images' ) );
		}

		$ids = array_values( $ids );

		/**
		 * Triggered before testing for file existence.
		 *
		 * @since  1.6.7
		 * @author Grégory Viguier
		 *
		 * @param array $ids                An array of attachment IDs.
		 * @param array $results            An array of the data fetched from the database.
		 * @param int   $optimization_level The optimization level that will be used for the optimization.
		 */
		do_action( 'imagify_bulk_optimize_before_file_existence_tests', $ids, $results, $optimization_level );

		$data = array();

		foreach ( $ids as $i => $id ) {
			if ( empty( $results['filenames'][ $id ] ) ) {
				// Problem.
				continue;
			}

			$file_path = get_imagify_attached_file( $results['filenames'][ $id ] );

			/** This filter is documented in inc/functions/process.php. */
			$file_path = apply_filters( 'imagify_file_path', $file_path );

			if ( ! $file_path || ! file_exists( $file_path ) ) {
				continue;
			}

			$attachment_backup_path        = get_imagify_attachment_backup_path( $file_path );
			$attachment_status             = isset( $results['statuses'][ $id ] )            ? $results['statuses'][ $id ]            : false;
			$attachment_optimization_level = isset( $results['optimization_levels'][ $id ] ) ? $results['optimization_levels'][ $id ] : false;

			// Don't try to re-optimize if there is no backup file.
			if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! file_exists( $attachment_backup_path ) ) {
				continue;
			}

			$data[ '_' . $id ] = get_imagify_attachment_url( $results['filenames'][ $id ] );
		} // End foreach().

		if ( ! $data ) {
			wp_send_json_error( array( 'message' => 'no-images' ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Check if the backup directory is writable.
	 * This is used to display an error message in the plugin's settings page.
	 *
	 * @since  1.6.11
	 * @author Grégory Viguier
	 */
	public function imagify_check_backup_dir_is_writable_callback() {
		imagify_check_nonce( 'imagify_check_backup_dir_is_writable' );
		imagify_check_user_capacity();

		wp_send_json_success( array(
			'is_writable' => (int) imagify_backup_dir_is_writable(),
		) );
	}

	/**
	 * Bridge between XML-RPC and actions triggered by imagify_do_async_job().
	 * When XML-RPC is used, a current user is set, but no cookies are set, so they cannot be sent with the request. Instead we stored the user ID in a transient.
	 *
	 * @since  1.6.11
	 * @author Grégory Viguier
	 * @see    imagify_do_async_job()
	 */
	public function nopriv_imagify_rpc_callback() {
		if ( empty( $_POST['imagify_rpc_action'] ) || empty( $_POST['imagify_rpc_id'] ) || 32 !== strlen( $_POST['imagify_rpc_id'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// Not necessary but just in case, whitelist the original action.
		$action  = $_POST['imagify_rpc_action']; // WPCS: CSRF ok.
		$actions = array_flip( $this->ajax_only_actions );
		unset( $actions['nopriv_imagify_rpc'] );

		if ( ! isset( $actions[ $action ] ) ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// Get the user ID.
		$rpc_id  = sanitize_key( $_POST['imagify_rpc_id'] );
		$user_id = absint( get_transient( 'imagify_rpc_' . $rpc_id ) );
		$user    = $user_id ? get_userdata( $user_id ) : false;

		delete_transient( 'imagify_rpc_' . $rpc_id );

		if ( ! $user || ! $user->exists() ) {
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		// The current user must be set before verifying the nonce.
		wp_set_current_user( $user_id );

		imagify_check_nonce( 'imagify_rpc_' . $rpc_id, 'imagify_rpc_nonce' );

		// Trigger the action we originally wanted.
		$_POST['action'] = $action;
		unset( $_POST['imagify_rpc_action'], $_POST['imagify_rpc_id'], $_POST['imagify_rpc_nonce'] );

		/** This hook is documented in wp-admin/admin-ajax.php. */
		do_action( 'wp_ajax_' . $_POST['action'] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFY ACCOUNT ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create a new Imagify account.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_signup_callback() {
		imagify_check_nonce( 'imagify-signup', 'imagifysignupnonce' );
		imagify_check_user_capacity();

		if ( empty( $_GET['email'] ) ) {
			imagify_die( __( 'Empty email address.', 'imagify' ) );
		}

		if ( ! is_email( $_GET['email'] ) ) {
			imagify_die( __( 'Not a valid email address.', 'imagify' ) );
		}

		$data = array(
			'email'    => $_GET['email'],
			'password' => wp_generate_password( 12, false ),
			'lang'     => imagify_get_locale(),
		);

		$response = add_imagify_user( $data );

		if ( is_wp_error( $response ) ) {
			imagify_die( $response );
		}

		wp_send_json_success();
	}

	/**
	 * Check the API key validity.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_check_api_key_validity_callback() {
		imagify_check_nonce( 'imagify-check-api-key', 'imagifycheckapikeynonce' );
		imagify_check_user_capacity();

		if ( empty( $_GET['api_key'] ) ) {
			imagify_die( __( 'Empty API key.', 'imagify' ) );
		}

		$response = get_imagify_status( $_GET['api_key'] );

		if ( is_wp_error( $response ) ) {
			imagify_die( $response );
		}

		$options            = get_site_option( IMAGIFY_SETTINGS_SLUG );
		$options['api_key'] = sanitize_key( $_GET['api_key'] );

		update_site_option( IMAGIFY_SETTINGS_SLUG, $options );

		wp_send_json_success();
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @since  1.6.11
	 * @author Jonathan Buttigieg
	 */
	public function imagify_get_admin_bar_profile_callback() {
		imagify_check_nonce( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce' );
		imagify_check_user_capacity();

		$user             = new Imagify_User();
		$unconsumed_quota = $user->get_percent_unconsumed_quota();
		$meteo_icon       = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="37" height="38" alt="" />';
		$bar_class        = 'positive';
		$message          = '';

		if ( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
			$bar_class  = 'neutral';
			$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="37" height="38" alt="" />';
		}
		elseif ( $unconsumed_quota <= 20 ) {
			$bar_class  = 'negative';
			$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="38" height="36" alt="" />';
		}

		if ( $unconsumed_quota <= 20 && $unconsumed_quota > 0 ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s almost over!', 'imagify' ) . '</strong></p>';
				/* translators: %s is a line break. */
				$message .= '<p>' . sprintf( __( 'You have almost used all your credit.%sDon\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ), '<br/><br/>' ) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'View My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		if ( 0 === $unconsumed_quota ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s Over!', 'imagify' ) . '</strong></p>';
				$message .= '<p>' . sprintf(
					/* translators: 1 is a data quota, 2 is a date. */
					__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
					size_format( $user->quota * 1048576 ),
					date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) )
				) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'Upgrade My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		// Custom HTML.
		$quota_section  = '<div class="imagify-admin-bar-quota">';
			$quota_section .= '<div class="imagify-abq-row">';

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-meteo-icon">' . $meteo_icon . '</div>';
		}

		$quota_section .= '<div class="imagify-account">';
			$quota_section .= '<p class="imagify-meteo-title">' . __( 'Account status', 'imagify' ) . '</p>';
			$quota_section .= '<p class="imagify-meteo-subs">' . __( 'Your subscription:', 'imagify' ) . '&nbsp;<strong class="imagify-user-plan">' . $user->plan_label . '</strong></p>';
		$quota_section .= '</div>'; // .imagify-account
		$quota_section .= '</div>'; // .imagify-abq-row

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-abq-row">';
				$quota_section .= '<div class="imagify-space-left">';
					/* translators: %s is a data quota. */
					$quota_section .= '<p>' . sprintf( __( 'You have %s space credit left', 'imagify' ), '<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>' ) . '</p>';
					$quota_section .= '<div class="imagify-bar-' . $bar_class . '">';
						$quota_section .= '<div style="width: ' . $unconsumed_quota . '%;" class="imagify-unconsumed-bar imagify-progress"></div>';
					$quota_section .= '</div>'; // .imagify-bar-{$bar_class}
				$quota_section .= '</div>'; // .imagify-space-left
			$quota_section .= '</div>'; // .imagify-abq-row
		}

		$quota_section .= '<p class="imagify-abq-row">';
			$quota_section .= '<a class="imagify-account-link" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">';
				$quota_section .= '<span class="dashicons dashicons-admin-users"></span>';
				$quota_section .= '<span class="button-text">' . __( 'View my subscription', 'imagify' ) . '</span>';
			$quota_section .= '</a>'; // .imagify-account-link
		$quota_section .= '</p>'; // .imagify-abq-row
		$quota_section .= '</div>'; // .imagify-admin-bar-quota
		$quota_section .= $message;

		wp_send_json_success( $quota_section );
	}

	/**
	 * Get pricings from API for Onetime and Plans at the same time.
	 *
	 * @since  1.6.11
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_prices_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		$prices_all = get_imagify_all_prices();

		if ( is_wp_error( $prices_all ) ) {
			imagify_die( $prices_all );
		}

		if ( ! is_object( $prices_all ) ) {
			imagify_die( __( 'Wrongly formatted response from our server.', 'imagify' ) );
		}

		wp_send_json_success( array(
			'onetimes'  => $prices_all->Packs,
			'monthlies' => $prices_all->Plans,
		) );
	}

	/**
	 * Check Coupon code on modal popin.
	 *
	 * @since  1.6.11
	 * @author Geoffrey Crofte
	 */
	public function imagify_check_coupon_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		if ( empty( $_POST['coupon'] ) ) {
			wp_send_json_success( array(
				'success' => false,
				'detail'  => __( 'Coupon is empty.', 'imagify' ),
			) );
		}

		$coupon = check_imagify_coupon_code( $_POST['coupon'] );

		if ( is_wp_error( $coupon ) ) {
			imagify_die( $coupon );
		}

		wp_send_json_success( imagify_translate_api_message( $coupon ) );
	}

	/**
	 * Get current discount promotion to display information on payment modal.
	 *
	 * @since  1.6.11
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_discount_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		wp_send_json_success( imagify_translate_api_message( check_imagify_discount() ) );
	}

	/**
	 * Get estimated sizes from the WordPress library.
	 *
	 * @since  1.6.11
	 * @author Geoffrey Crofte
	 */
	public function imagify_get_images_counts_callback() {
		imagify_check_nonce( 'imagify_get_pricing_' . get_current_user_id(), 'imagifynonce' );
		imagify_check_user_capacity();

		$raw_total_size_in_library = imagify_calculate_total_size_images_library();
		$raw_average_per_month     = imagify_calculate_average_size_images_per_month();
		update_imagify_option( 'total_size_images_library', array( 'raw' => $raw_total_size_in_library, 'human' => size_format( $raw_total_size_in_library ) ) );
		update_imagify_option( 'average_size_images_per_month', array( 'raw' => $raw_average_per_month, 'human' => size_format( $raw_average_per_month ) ) );

		wp_send_json_success( array(
			'total_library_size' => get_imagify_option( 'total_size_images_library', null ),
			'average_month_size' => get_imagify_option( 'average_size_images_per_month', null ),
		) );
	}

	/**
	 * Estimate sizes and update the options values for them.
	 *
	 * @since  1.6.11
	 * @author Remy Perona
	 */
	public function imagify_update_estimate_sizes_callback() {
		imagify_check_nonce( 'update_estimate_sizes' );
		imagify_check_user_capacity();

		$raw_total_size_in_library = imagify_calculate_total_size_images_library();
		$raw_average_per_month     = imagify_calculate_average_size_images_per_month();

		update_imagify_option( 'total_size_images_library', array( 'raw' => $raw_total_size_in_library, 'human' => size_format( $raw_total_size_in_library ) ) );
		update_imagify_option( 'average_size_images_per_month', array( 'raw' => $raw_average_per_month, 'human' => size_format( $raw_average_per_month ) ) );

		die( 1 );
	}
}
