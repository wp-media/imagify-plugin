<?php
namespace Imagify\ThirdParty\WooCommerce;

/**
 * Compatibility for WooCommerce.
 *
 * @since 1.10.0
 */
class WooCommerce {
	/**
	 * Initialize compatibility functionality.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'woocommerce_single_product_summary', [ $this, 'variable_products_webp_compat' ] );
	}

	/**
	 * Add Variable Products Webp Compatibility.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function variable_products_webp_compat() {
		global $product;

		if ( ! isset( $product ) || ! $product->is_type( 'variable' ) ) {
			return;
		}

		add_filter( 'imagify_picture_attributes', [ $this, 'remove_wp_post_image_class' ], 10, 2 );
		add_filter(
			'imagify_picture_source_attributes',
			[ $this, 'maybe_add_wp_post_image_class_on_picture_internal_tags' ],
			10,
			2
		);
		add_filter(
			'imagify_picture_img_attributes',
			[ $this, 'maybe_add_wp_post_image_class_on_picture_internal_tags' ],
			10,
			2
		);
	}

	/**
	 * Remove wp-post-image class from picture tags.
	 *
	 * @since 1.10.0
	 *
	 * @param array $attributes The picture tag attributes.
	 *
	 * @return array The picture tage attributes with modified or removed 'class'.
	 */
	public function remove_wp_post_image_class( $attributes ) {
		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = str_replace( 'wp-post-image', '', $attributes['class'] );
		}

		if ( empty( $attributes['class'] ) ) {
			unset( $attributes['class'] );
		}

		return $attributes;
	}

	/**
	 * Add wp-post-image class to source and image tags internal to a picture tag.
	 *
	 * @since 1.10.0
	 *
	 * @param array $attributes The source or img tag attributes.
	 * @param array $image      The original image tag data.
	 *
	 * @return array Source or image tag attributes with modified 'class'.
	 */
	public function maybe_add_wp_post_image_class_on_picture_internal_tags( $attributes, $image ) {
		if (
			! empty( $image['attributes']['class'] )
			&& strpos( $image['attributes']['class'], 'wp-post-image' ) !== false
		) {
			$attributes['class'] = isset( $attributes['class'] )
				? $attributes['class'] . ' wp-post-image'
				: 'wp-post-image';
		}

		return $attributes;
	}
}

( new WooCommerce() )->init();
