<?php
/**
 * Test data for \Imagify\Picture\Display::process_content().
 *
 * '{{uploads}}' is replaced by the uploads base URL. Every file listed in 'files' is created in the uploads
 * directory with its WebP counterpart, so the image is eligible for a <picture> tag: the site icon is
 * left unconverted because it is excluded, not because it has no WebP.
 *
 * The admin bar markup mirrors WordPress core: WP_Admin_Bar::_render_item() prints
 * <li role='group' id='wp-admin-bar-site-name' ...> (single quotes, role before id),
 * and wp_admin_bar_site_menu() prepends the site icon to the site name inside the item link.
 */

$icon_tag    = '<img class="site-icon" src="{{uploads}}/imagify-test-site-icon.png" alt="" width="20" height="20" />';
$content_tag = '<img class="content-image" src="{{uploads}}/imagify-test-content.jpg" alt="" width="300" height="200" />';

$admin_bar = static function ( string $site_name ): string {
	return '<div id="wpadminbar"><ul><li role=\'group\' id=\'wp-admin-bar-site-name\' class="menupop has-site-icon">'
		. "<a class='ab-item' aria-haspopup='true' href='/'>{$site_name}</a>"
		. '<div class="ab-sub-wrapper"><ul><li><a href="/">Visit Site</a></li></ul></div></li></ul></div>';
};

return [
	'test_data' => [
		'shouldSkipAdminBarSiteIconAndConvertContentImage' => [
			'config'   => [
				'files' => [ 'imagify-test-site-icon.png', 'imagify-test-content.jpg' ],
				'html'  => $admin_bar( $icon_tag . 'Site' ) . "<main>{$content_tag}</main>",
			],
			'expected' => [
				'picture_count' => 1,
				'unchanged'     => [ $icon_tag ],
				'converted'     => [ 'imagify-test-content.jpg' ],
			],
		],
		'shouldConvertContentImageWithoutAdminBar'         => [
			'config'   => [
				'files' => [ 'imagify-test-content.jpg' ],
				'html'  => "<main>{$content_tag}</main>",
			],
			'expected' => [
				'picture_count' => 1,
				'unchanged'     => [],
				'converted'     => [ 'imagify-test-content.jpg' ],
			],
		],
		'shouldConvertContentImageWhenSiteNameHasNoIcon'   => [
			'config'   => [
				'files' => [ 'imagify-test-content.jpg' ],
				'html'  => $admin_bar( 'Site' ) . "<main>{$content_tag}</main>",
			],
			'expected' => [
				'picture_count' => 1,
				'unchanged'     => [],
				'converted'     => [ 'imagify-test-content.jpg' ],
			],
		],
		'shouldConvertSiteIconOutsideAdminBar'             => [
			'config'   => [
				'files' => [ 'imagify-test-site-icon.png' ],
				'html'  => "<header>{$icon_tag}</header>",
			],
			'expected' => [
				'picture_count' => 1,
				'unchanged'     => [],
				'converted'     => [ 'imagify-test-site-icon.png' ],
			],
		],
	],
];
