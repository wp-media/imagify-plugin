<?php
/**
 * Test data for \Imagify\Picture\Display::get_admin_bar_site_icon_tags().
 *
 * The admin bar markup mirrors WordPress core: WP_Admin_Bar::_render_item() prints
 * <li role='group' id='wp-admin-bar-site-name' ...> (single quotes, role before id),
 * and wp_admin_bar_site_menu() prepends <img class="site-icon" ... width="20" height="20" />
 * to the site name inside the item link.
 */

$core_icon = '<img class="site-icon" src="https://example.org/wp-content/uploads/icon-32x32.png" srcset="https://example.org/wp-content/uploads/icon-64x64.png 2x" alt="" width="20" height="20" />';

return [
	'test_data' => [
		'shouldReturnSiteIconFromCoreMarkup'              => [
			'config'   => [
				'html' => '<div id="wpadminbar"><ul><li role=\'group\' id=\'wp-admin-bar-site-name\' class="menupop has-site-icon">'
					. "<a class='ab-item' aria-haspopup=\"true\" href='https://example.org/'>{$core_icon}Site</a>"
					. '</li></ul></div><main><img src="https://example.org/wp-content/uploads/content.jpg" /></main>',
			],
			'expected' => [
				'tags' => [ $core_icon ],
			],
		],
		'shouldReturnSiteIconWithDoubleQuotesAndReorderedAttributes' => [
			'config'   => [
				'html' => '<li id="wp-admin-bar-site-name" class="menupop"><a class="ab-item" href="/">'
					. '<img width="20" height="20" alt="" src="https://example.org/i.png" class="site-icon" /> Site</a></li>',
			],
			'expected' => [
				'tags' => [ '<img width="20" height="20" alt="" src="https://example.org/i.png" class="site-icon" />' ],
			],
		],
		'shouldReturnEmptyWithoutAdminBar'                => [
			'config'   => [
				'html' => '<body><img class="site-icon" src="https://example.org/a.png" /></body>',
			],
			'expected' => [
				'tags' => [],
			],
		],
		'shouldNotReturnLaterImagesWhenSiteNameHasNoIcon' => [
			'config'   => [
				'html' => '<li role=\'group\' id=\'wp-admin-bar-site-name\' class="menupop"><a class=\'ab-item\' href="/">Site</a>'
					. '<div class="ab-sub-wrapper"></div></li><li><a href="#"><img src="menu.png" /></a></li><img src="content.jpg" />',
			],
			'expected' => [
				'tags' => [],
			],
		],
		'shouldNotReturnImagesOfOtherAdminBarItems'       => [
			'config'   => [
				'html' => '<li role=\'group\' id=\'wp-admin-bar-my-account\' class="menupop with-avatar"><a class=\'ab-item\' href="/">'
					. '<img alt="" src="https://example.org/avatar.png" class="avatar avatar-26" height="26" width="26" /></a></li>',
			],
			'expected' => [
				'tags' => [],
			],
		],
	],
];
