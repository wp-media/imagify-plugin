<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$settings    = Imagify_Settings::get_instance();
$options     = Imagify_Options::get_instance();
$option_name = $options->get_option_name();
?>
<div class="<?php echo imagify_can_optimize_custom_folders() ? 'imagify-col' : ''; ?>">
	<h3 class="imagify-options-subtitle"><?php _e( 'Media Library', 'imagify' ); ?></h3>

	<p class="imagify-setting-line">
		<?php
		$settings->field_checkbox( array(
			'option_name' => 'resize_larger',
			'label'       => __( 'Resize larger images', 'imagify' ),
			'attributes'  => array(
				'aria-describedby' => 'describe-resize_larger',
			),
		) );
		?>

		<span class="imagify-options-line">
			<label for="imagify_resize_larger_w">
			<?php
			$max_sizes       = get_imagify_max_intermediate_image_size();
			$resize_larger_w = $options->get( 'resize_larger_w' );
			printf(
				/* translators: 1 is a text input for a number of pixels (don't use %d). */
				__( 'to maximum %s pixels width', 'imagify' ),
				'<input type="number" id="imagify_resize_larger_w" min="' . $max_sizes['width'] . '" name="' . $option_name . '[resize_larger_w]" value="' . ( $resize_larger_w ? $resize_larger_w : '' ) . '" size="5">'
			);
			?>
			</label>
		</span>

		<span id="describe-resize_larger" class="imagify-info">
			<span class="dashicons dashicons-info"></span>
			<?php
			printf(
				/* translators: 1 is a number of pixels. */
				__( 'This option is recommended to reduce larger images. You can save up to 80%% after resizing. The new width should not be less than your largest thumbnail width, which is actually %dpx.', 'imagify' ),
				$max_sizes['width']
			);
			?>
		</span>
	</p>

	<?php if ( ! imagify_is_active_for_network() ) : ?>

		<div class="imagify-divider"></div>

		<h4 class="imagify-h4-like"><?php _e( 'Files optimization', 'imagify' ); ?></h4>

		<p>
			<?php _e( 'You can choose to optimize different image sizes created by WordPress here.', 'imagify' ); ?>
		</p>

		<p>
			<?php
			printf(
				/* translators: 1 is a "bold" tag start, 2 is the "bold" tag end. */
				__( 'The %1$soriginal size%2$s is %1$sautomatically optimized%2$s by Imagify.', 'imagify' ),
				'<strong>', '</strong>'
			);
			?>
			<br>
			<span class="imagify-success">
				<?php _e( 'Remember each additional image size will affect your Imagify monthly usage!', 'imagify' ); ?>
			</span>
		</p>

		<?php
		/**
		 * Disallowed thumbnail sizes.
		 */
		$settings->field_checkbox_list( array(
			'option_name'   => 'disallowed-sizes',
			'legend'        => __( 'Choose the sizes to optimize', 'imagify' ),
			'values'        => Imagify_Settings::get_thumbnail_sizes(),
			'reverse_check' => true,
		) );
		?>

	<?php endif; ?>
</div>
<?php
