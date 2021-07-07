<?php
namespace Imagify\ThirdParty\WooCommerce;

class WooCommerce {
	public function init() {
		add_action( 'woocommerce_single_product_summary', [ $this, 'variable_products_webp_compat' ] );
	}

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

	function remove_wp_post_image_class( $attributes, $image ) {
		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = str_replace( 'wp-post-image', '', $attributes['class'] );
		}

		if ( empty( $attributes['class'] ) ) {
			unset( $attributes['class'] );
		}

		return $attributes;
	}

	function maybe_add_wp_post_image_class_on_picture_internal_tags( $attributes, $image ) {
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

$imagify_woocommerce_compat = new WooCommerce();
$imagify_woocommerce_compat->init();
