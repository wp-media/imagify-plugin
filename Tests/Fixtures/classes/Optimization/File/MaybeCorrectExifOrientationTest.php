<?php

return [
	'test_data' => [
		// When EXIF data can't be read at all, nothing should happen.
		'shouldReturnFalseWhenExifIsNotAvailable'   => [
			'config'   => [
				'can_get_exif' => false,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => null,
				'editor'       => 'none',
				'save'         => null,
			],
			'expected' => [
				'get_image_exif_called' => false,
				'rotate'                => null,
				'flip'                  => null,
				'save_called'           => false,
				'result'                => 'false',
			],
		],

		// When the file isn't a JPEG, EXIF orientation is irrelevant.
		'shouldReturnFalseWhenNotJpeg'              => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'png',
					'type' => 'image/png',
				],
				'orientation'  => null,
				'editor'       => 'none',
				'save'         => null,
			],
			'expected' => [
				'get_image_exif_called' => false,
				'rotate'                => null,
				'flip'                  => null,
				'save_called'           => false,
				'result'                => 'false',
			],
		],

		// When the orientation is already 1 (or absent), the file must not be touched: this also
		// guards against rotating the same working copy twice.
		'shouldReturnFalseWhenOrientationIsAlreadyOne' => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => 1,
				'editor'       => 'valid',
				'save'         => null,
			],
			'expected' => [
				'get_image_exif_called' => true,
				'rotate'                => null,
				'flip'                  => null,
				'save_called'           => false,
				'result'                => 'false',
			],
		],

		// Orientation 6 (the common "rotated 90 CW on capture" case) must rotate the editor by 270
		// and save the result back over the same (temporary) path.
		'shouldRotateAndSaveForOrientationSix'      => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => 6,
				'editor'       => 'valid',
				'save'         => 'success',
			],
			'expected' => [
				'get_image_exif_called' => true,
				'rotate'                => 270,
				'flip'                  => null,
				'save_called'           => true,
				'result'                => 'true',
			],
		],

		// Orientation 5 involves a rotation followed by a flip.
		'shouldRotateAndFlipForOrientationFive'     => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => 5,
				'editor'       => 'valid',
				'save'         => 'success',
			],
			'expected' => [
				'get_image_exif_called' => true,
				'rotate'                => 90,
				'flip'                  => [ false, true ],
				'save_called'           => true,
				'result'                => 'true',
			],
		],

		// If the editor can't be retrieved, the WP_Error must be surfaced (and nothing saved).
		'shouldReturnWpErrorWhenEditorFails'        => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => 6,
				'editor'       => 'error',
				'save'         => null,
			],
			'expected' => [
				'get_image_exif_called' => true,
				'rotate'                => null,
				'flip'                  => null,
				'save_called'           => false,
				'result'                => 'wp_error',
			],
		],

		// If saving the rotated file fails, the WP_Error must be surfaced.
		'shouldReturnWpErrorWhenSaveFails'          => [
			'config'   => [
				'can_get_exif' => true,
				'file_type'    => [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				],
				'orientation'  => 6,
				'editor'       => 'valid',
				'save'         => 'error',
			],
			'expected' => [
				'get_image_exif_called' => true,
				'rotate'                => 270,
				'flip'                  => null,
				'save_called'           => true,
				'result'                => 'wp_error',
			],
		],
	],
];
