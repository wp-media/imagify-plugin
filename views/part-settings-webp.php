<?php

use Imagify\Stats\OptimizedMediaWithoutNextGen;
use Imagify\Webp\Display;

defined( 'ABSPATH' ) || exit;

$settings = Imagify_Settings::get_instance();
?>
<div>
	<h3 class="imagify-options-subtitle"><?php esc_html_e( 'Next-Gen image format', 'imagify' ); ?></h3>

	<div class="imagify-setting-line">
		<?php
		$message       = __( 'Select WebP for high compatibility, AVIF for superior compression. Please note that the generation process will start automatically after saving the settings.', 'imagify' );
		$message_class = 'info';
		$disabled      = false;

		if ( has_filter( 'imagify_nextgen_images_formats' ) ) {
			$message = sprintf(
				// translators: %1$s and %2$s are <code> tag opening and closing, %3$s and %4$s are <a> tag opening and closing.
				__( 'Next-Gen Images format is currently defined by the %1$simagify_nextgen_images_format%2$s filter. %3$sRead more%4$s', 'imagify' ),
				'<code>',
				'</code>',
				'<a href="https://imagify.io/documentation/how-to-use-the-next-gen-image-format-filter/" target="_blank">',
				'</a>'
			);

			$message_class = 'error';
			$disabled      = true;
		}

		$attributes = [
			'aria-describedby' => 'describe-optimization_format',
		];

		if ( $disabled ) {
			$attributes['disabled'] = true;
		}

		$settings->field_inline_radio_list(
			[
				'option_name' => 'optimization_format',
				'legend'      => __( 'Next-gen image format', 'imagify' ),
				'info'        => $message,
				'info_class'  => $message_class,
				'values'      => [
					'off'  => __( 'Off', 'imagify' ),
					'avif' => __( 'AVIF', 'imagify' ),
					'webp' => __( 'WebP', 'imagify' ),
				],
				'attributes'  => $attributes,
			]
		);
		?>
	</div>

	<div class="imagify-setting-line">

		<div class="imagify-options-line">
			<?php
			$settings->field_checkbox(
				[
					'option_name' => 'display_nextgen',
					'label'       => __( 'Display images in Next-Gen format on the site', 'imagify' ),
				]
			);
			?>

			<div class="imagify-options-line">
				<?php
				$settings->field_radio_list(
					[
						'option_name' => 'display_nextgen_method',
						'values'      => [
							/* translators: 1 and 2 are <em> tag opening and closing. */
							'rewrite' => sprintf( __( 'Use rewrite rules %1$s(recommended for sites without a CDN)%2$s', 'imagify' ), '<em>', '</em>' ),
							'picture' => __( 'Use &lt;picture&gt; tags', 'imagify' ),
						],
						'attributes'  => [
							'aria-describedby' => 'describe-convert_to_webp',
						],
					]
				);
				?>

				<div class="imagify-options-line">
					<?php
					$cdn_source = apply_filters( 'imagify_cdn_source_url', '' );

					if ( 'option' !== $cdn_source['source'] ) {
						if ( 'constant' === $cdn_source['source'] ) {
							printf(
								/* translators: 1 is an URL, 2 is a php constant name. */
								esc_html__( 'Your CDN URL is set to %1$s by the constant %2$s.', 'imagify' ),
								'<code>' . esc_url( $cdn_source['url'] ) . '</code>',
								'<code>' . esc_html( $cdn_source['name'] ) . '</code>'
							);
						} elseif ( ! empty( $cdn_source['name'] ) ) {
							printf(
								/* translators: 1 is an URL, 2 is a plugin name. */
								esc_html__( 'Your CDN URL is set to %1$s by %2$s.', 'imagify' ),
								'<code>' . esc_url( $cdn_source['url'] ) . '</code>',
								'<code>' . esc_html( $cdn_source['name'] ) . '</code>'
							);
						} else {
							printf(
								/* translators: %s is an URL. */
								esc_html__( 'Your CDN URL is set to %1$s by filter.', 'imagify' ),
								'<code>' . esc_url( $cdn_source['url'] ) . '</code>'
							);
						}

						$settings->field_hidden(
							[
								'option_name'   => 'cdn_url',
								'current_value' => $cdn_source['url'],
							]
						);
					} else {
						$settings->field_text_box(
							[
								'option_name' => 'cdn_url',
								'label'       => __( 'If you use a CDN, specify the URL:', 'imagify' ),
								'attributes'  => [
									'size'        => 30,
									'placeholder' => __( 'https://cdn.example.com', 'imagify' ),
								],
							]
						);
					}
					?>
				</div>
			</div>

			<div id="describe-display_nextgen_method" class="imagify-info">
				<span class="dashicons dashicons-info"></span>
				<?php
				$conf_file_path = Display::get_instance()->get_file_path( true );

				if ( $conf_file_path ) {
					printf(
						/* translators: 1 is a file name, 2 is a <strong> tag opening, 3 is the <strong> tag closing. */
						esc_html__( 'The first option adds rewrite rules to your site’s configuration file (%1$s) and does not alter your pages code. %2$sThis does not work with CDN though.%3$s', 'imagify' ),
						'<code>' . esc_html( $conf_file_path ) . '</code>',
						'<strong>',
						'</strong>'
					);

					echo '<br/>';
				}

				printf(
					/* translators: 1 and 2 are HTML tag names, 3 is a <strong> tag opening, 4 is the <strong> tag closing. */
					esc_html__( 'The second option replaces the %1$s tags with %2$s tags. It is required if you use a CDN, and it is the only option that works when your configuration file cannot be edited. %3$sIt alters your pages code, so it can break your layout or hide images if your theme, a slider or a page builder is not compatible%4$s, and it does not cover images set in CSS, such as backgrounds.', 'imagify' ),
					'<code>&lt;img&gt;</code>',
					'<code>&lt;picture&gt;</code>',
					'<strong>',
					'</strong>'
				);

				echo '<br/>';

				printf(
					/* translators: 1 is a <strong> tag opening, 2 is the <strong> tag closing, 3 is a link opening tag, 4 is the link closing tag. */
					esc_html__( '%1$sAfter changing this setting, clear every cache and check your pages%2$s - your homepage, a product page and any page using a slider. %3$sWhat to do if your images look broken%4$s.', 'imagify' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( imagify_get_external_url( 'documentation-nextgen-delivery' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				);

				echo '<br/>';

				/**
				 * Add more information about WebP.
				 *
				 * @since  1.9
				 * @author Grégory Viguier
				 */
				do_action( 'imagify_settings_webp_info' );
				?>
			</div>
		</div>

		<?php
		$count = OptimizedMediaWithoutNextGen::get_instance()->get_cached_stat();

		if ( $count ) {
			?>
			<div class="imagify-options-line hide-if-no-js generate-missing-webp">
				<?php $this->print_template( 'part-settings-webp-missing-message', [ 'count' => $count ] ); ?>

				<button id="imagify-generate-webp-versions" class="button imagify-button-primary imagify-button-mini" type="button">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php
					/*
					 * Keep this string byte-identical to the ability label in
					 * classes/Abilities/GenerateMissingNextgen.php: same wording means one shared
					 * entry on translate.wordpress.org rather than two. The casing looks off next
					 * to the rest of the UI, but the button is uppercased by CSS, so it renders as
					 * "GENERATE MISSING NEXT-GEN VERSIONS" either way. Re-casing it here would
					 * silently fork the two into separate strings to translate.
					 */
					?>
					<span class="button-text"><?php esc_html_e( 'Generate missing next-gen versions', 'imagify' ); ?></span>
				</button>

				<?php
				$remaining = OptimizedMediaWithoutNextGen::get_instance()->get_stat();
				$total     = get_transient( 'imagify_missing_next_gen_total' );
				$progress  = 0;
				$aria      = ' aria-hidden="true"';
				$class     = 'hidden';
				$style     = '';

				if (
					false !== $total
					&&
					$total > 0
				) {
					$aria  = '';
					$class = '';

					/*
					 * `$total` is a snapshot taken when the run started, while `$remaining` is
					 * recounted on every page load. Anything growing the workload mid-run (new
					 * uploads, or switching the Next-Gen format so every media is missing one)
					 * pushes `$remaining` above `$total`, and `$total - $remaining` goes negative.
					 * Report against the largest workload seen instead. Mirrors getProgress() in
					 * assets/js/options.js.
					 */
					$remaining       = max( (int) $remaining, 0 );
					$effective_total = max( (int) $total, $remaining );
					$processed       = $effective_total - $remaining;
					$progress        = $processed . '/' . $effective_total;
					$percent         = $effective_total > 0 ? floor( $processed / $effective_total * 100 ) : 0;
					$style           = 'style="width:' . $percent . '%;"';
				}
				?>

				<div <?php echo $aria; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="imagify-progress <?php echo esc_attr( $class ); ?>">
					<div class="progress">
						<div class="bar" <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><div class="percent"><?php echo esc_html( $progress ); ?></div></div>
					</div>
				</div>
			</div>
			<?php
			if ( Imagify_Requirements::is_api_key_valid() ) {
				?>
				<script type="text/html" id="tmpl-imagify-overquota-alert">
					<?php $this->print_template( 'part-bulk-optimization-overquota-alert' ); ?>
				</script>
				<?php
			}
		}
		?>
	</div>
</div>
<?php
