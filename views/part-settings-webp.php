<?php

use Imagify\Stats\OptimizedMediaWithoutWebp;
use Imagify\Webp\Display;
use Imagify\Webp\Picture\Display as PictureDisplay;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$settings = Imagify_Settings::get_instance();
?>
<div>
	<h3 class="imagify-options-subtitle"><?php _e( 'WebP format', 'imagify' ); ?></h3>

	<div class="imagify-setting-line">
		<?php
		$settings->field_checkbox( [
			'option_name' => 'convert_to_webp',
			'label'       => __( 'Create WebP versions of images', 'imagify' ),
			'attributes'  => [
				'aria-describedby' => 'describe-convert_to_webp',
			],
		] );
		?>

		<div class="imagify-options-line">
			<?php
			$settings->field_checkbox( [
				'option_name' => 'display_webp',
				'label'       => __( 'Display images in WebP format on the site', 'imagify' ),
			] );
			?>

			<div class="imagify-options-line">
				<?php
				$settings->field_radio_list( [
					'option_name' => 'display_webp_method',
					'values'      => [
						'rewrite' => __( 'Use rewrite rules', 'imagify' ),
						/* translators: 1 and 2 are <em> tag opening and closing. */
						'picture' => sprintf( __( 'Use &lt;picture&gt; tags %1$s(preferred)%2$s', 'imagify' ), '<em>', '</em>' ),
					],
					'attributes'  => [
						'aria-describedby' => 'describe-convert_to_webp',
					],
				] );
				?>

				<div class="imagify-options-line">
					<?php
					$cdn_source = PictureDisplay::get_instance()->get_cdn_source();

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

						$settings->field_hidden( [
							'option_name'   => 'cdn_url',
							'current_value' => $cdn_source['url'],
						] );
					} else {
						$settings->field_text_box( [
							'option_name' => 'cdn_url',
							'label'       => __( 'If you use a CDN, specify the URL:', 'imagify' ),
							'attributes'  => [
								'size'        => 30,
								'placeholder' => __( 'https://cdn.example.com', 'imagify' ),
							],
						] );
					}
					?>
				</div>
			</div>

			<div id="describe-display_webp_method" class="imagify-info">
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
					esc_html__( 'The second option replaces the %1$s tags with %2$s tags. %3$sThis is the preferred solution but some themes may break%4$s, so make sure to verify that everything seems fine.', 'imagify' ),
					'<code>&lt;img&gt;</code>',
					'<code>&lt;picture&gt;</code>',
					'<strong>',
					'</strong>'
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
		$count = OptimizedMediaWithoutWebp::get_instance()->get_cached_stat();

		if ( $count ) {
			?>
			<div class="imagify-options-line hide-if-no-js generate-missing-webp">
				<?php $this->print_template( 'part-settings-webp-missing-message', [ 'count' => $count ] ); ?>

				<button id="imagify-generate-webp-versions" class="button imagify-button-primary imagify-button-mini" type="button">
					<span class="dashicons dashicons-admin-generic"></span>
					<span class="button-text"><?php esc_html_e( 'Generate missing WebP versions', 'imagify' ); ?></span>
				</button>

				<?php
				$remaining = OptimizedMediaWithoutWebp::get_instance()->get_stat();
				$total     = get_transient( 'imagify_missing_webp_total' );
				$progress  = 0;
				$aria      = ' aria-hidden="true"';
				$class     = 'hidden';
				$style     = '';

				if ( false !== $total ) {
					$aria      = '';
					$class     = '';
					$processed = $total - $remaining;
					$progress  = $processed . '/' . $total;
					$percent   = $processed / $total * 100;
					$style     = 'style="width:' . $percent . '%;"';
				}
				?>

				<div <?php echo $aria; ?> class="imagify-progress <?php echo $class; ?>">
					<div class="progress">
						<div class="bar" <?php echo $style; ?>><div class="percent"><?php echo $progress; ?></div></div>
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
