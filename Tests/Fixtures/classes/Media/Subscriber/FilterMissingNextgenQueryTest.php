<?php

return [
	'test_data' => [
		// Not the admin, or not the main query: the request filter (dead-code, §5.2) is out of
		// scope entirely, so the new hook must also bail rather than touch a front-end/secondary query.
		'shouldBailWhenNotAdmin'                  => [
			'config'   => [
				'is_admin'           => false,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => 'missing-nextgen',
				'formats'            => [ 'webp' => 'webp' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => false,
				'mime_type_set'  => false,
			],
		],

		'shouldBailWhenNotMainQuery'              => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => false,
				'is_wp_library_page' => true,
				'get_status'         => 'missing-nextgen',
				'formats'            => [ 'webp' => 'webp' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => false,
				'mime_type_set'  => false,
			],
		],

		// Not on the Media Library list screen: must not touch the query.
		'shouldBailWhenNotWpLibraryPage'          => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => false,
				'get_status'         => 'missing-nextgen',
				'formats'            => [ 'webp' => 'webp' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => false,
				'mime_type_set'  => false,
			],
		],

		// Any other `imagify-status` value (including the ones the legacy request-filter handles)
		// must be left completely alone by this hook.
		'shouldIgnoreOtherStatusValues'           => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => 'errors',
				'formats'            => [ 'webp' => 'webp' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => false,
				'mime_type_set'  => false,
			],
		],

		'shouldIgnoreMissingStatus'               => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => null,
				'formats'            => [ 'webp' => 'webp' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => false,
				'mime_type_set'  => false,
			],
		],

		// WebP is the active next-gen format: the meta_query needles must use the WebP suffix.
		'shouldBuildMetaQueryForWebp'             => [
			'config'   => [
				'is_admin'                => true,
				'is_main_query'           => true,
				'is_wp_library_page'      => true,
				'get_status'              => 'missing-nextgen',
				'formats'                 => [ 'webp' => 'webp' ],
				'existing_post_mime_type' => null,
			],
			'expected' => [
				'meta_query_set' => true,
				'post_in_set'    => false,
				'mime_type_set'  => true,
				'suffix'         => '@imagify-webp',
				'excluded_mime'  => 'image/webp',
			],
		],

		// AVIF is the active next-gen format: don't hardcode webp — the needles must use the AVIF suffix.
		'shouldBuildMetaQueryForAvif'             => [
			'config'   => [
				'is_admin'                => true,
				'is_main_query'           => true,
				'is_wp_library_page'      => true,
				'get_status'              => 'missing-nextgen',
				'formats'                 => [ 'avif' => 'avif' ],
				'existing_post_mime_type' => null,
			],
			'expected' => [
				'meta_query_set' => true,
				'post_in_set'    => false,
				'mime_type_set'  => true,
				'suffix'         => '@imagify-avif',
				'excluded_mime'  => 'image/avif',
			],
		],

		// `post_mime_type` already set by something else (e.g. another filter): must not be overwritten.
		'shouldNotOverwriteExistingPostMimeType'  => [
			'config'   => [
				'is_admin'                => true,
				'is_main_query'           => true,
				'is_wp_library_page'      => true,
				'get_status'              => 'missing-nextgen',
				'formats'                 => [ 'webp' => 'webp' ],
				'existing_post_mime_type' => 'image/jpeg',
			],
			'expected' => [
				'meta_query_set' => true,
				'post_in_set'    => false,
				'mime_type_set'  => false,
				'suffix'         => '@imagify-webp',
				'excluded_mime'  => 'image/webp',
			],
		],

		// The `imagify_nextgen_images_formats` filter is public, so third parties may return a
		// plain list rather than the keyed shape core builds. Reading the key then yields the
		// integer 0, which made strtoupper() throw a TypeError and took the whole Media Library
		// down with a critical error. The value must be read instead.
		'shouldHandleListShapedFormatsFromFilter' => [
			'config'   => [
				'is_admin'                => true,
				'is_main_query'           => true,
				'is_wp_library_page'      => true,
				'get_status'              => 'missing-nextgen',
				'formats'                 => [ 'webp' ],
				'existing_post_mime_type' => null,
			],
			'expected' => [
				'meta_query_set' => true,
				'post_in_set'    => false,
				'mime_type_set'  => true,
				'suffix'         => '@imagify-webp',
				'excluded_mime'  => 'image/webp',
			],
		],

		// Same filter, uppercase value: must still resolve rather than fall through.
		'shouldHandleUppercaseFormatFromFilter'   => [
			'config'   => [
				'is_admin'                => true,
				'is_main_query'           => true,
				'is_wp_library_page'      => true,
				'get_status'              => 'missing-nextgen',
				'formats'                 => [ 'WEBP' ],
				'existing_post_mime_type' => null,
			],
			'expected' => [
				'meta_query_set' => true,
				'post_in_set'    => false,
				'mime_type_set'  => true,
				'suffix'         => '@imagify-webp',
				'excluded_mime'  => 'image/webp',
			],
		],

		// An unknown format from the filter must show nothing rather than raise an error or, worse,
		// fall through and list the entire library as "missing next-gen".
		'shouldShowNoResultsForUnknownFormat'     => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => 'missing-nextgen',
				'formats'            => [ 'jxl' => 'jxl' ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => true,
				'mime_type_set'  => false,
			],
		],

		// A non-string value from the filter must not reach strtolower()/strtoupper().
		'shouldShowNoResultsForNonStringFormat'   => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => 'missing-nextgen',
				'formats'            => [ 123 ],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => true,
				'mime_type_set'  => false,
			],
		],

		// No next-gen format enabled: show no results rather than the whole library.
		'shouldShowNoResultsWhenNoFormatEnabled'  => [
			'config'   => [
				'is_admin'           => true,
				'is_main_query'      => true,
				'is_wp_library_page' => true,
				'get_status'         => 'missing-nextgen',
				'formats'            => [],
			],
			'expected' => [
				'meta_query_set' => false,
				'post_in_set'    => true,
				'mime_type_set'  => false,
			],
		],
	],
];
