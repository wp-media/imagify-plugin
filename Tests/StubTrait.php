<?php

namespace Imagify\Tests;

use Brain\Monkey\Functions;

trait StubTrait {
	protected $abspath                  = 'vfs://public/';
	protected $mock_imagify_get_constant = true;
	protected $is_running_vfs           = true;
	protected $just_return_path         = false;
	protected $wp_cache_constant        = false;
	protected $wp_content_dir           = 'vfs://public/wp-content';
	protected $script_debug             = false;
	protected $imagify_version;
	protected $disable_wp_cron          = false;
	protected $plugin_name              = 'Imagify';
	protected $constants                = [];

	protected function resetStubProperties() {
		$defaults = [
			'abspath'                   => 'vfs://public/',
			'mock_imagify_get_constant' => true,
			'disable_wp_cron'           => false,
			'wp_cache_constant'         => false,
			'wp_content_dir'            => 'vfs://public/wp-content',
			'script_debug'              => false,
			'imagify_version'            => null,
			'constants'                 => [],
		];

		foreach ( $defaults as $property => $value ) {
			$this->$property = $value;
		}
	}

	protected function stubImagifyGetConstant() {
		if ( ! $this->mock_imagify_get_constant ) {
			return;
		}

		Functions\when( 'imagify_get_constant' )->alias(
			function( $constant_name, $default = null ) {
				return $this->getConstant( $constant_name, $default );
			}
		);
	}

	protected function getConstant( $constant_name, $default = null ) {
		switch ( $constant_name ) {
			case 'ABSPATH':
				return $this->abspath;

			case 'DISABLE_WP_CRON':
				return $this->disable_wp_cron;

			case 'FS_CHMOD_DIR':
				return 0777;

			case 'FS_CHMOD_FILE':
				return 0666;

			case 'SCRIPT_DEBUG':
				return $this->script_debug;

			case 'WP_CACHE':
				return $this->wp_cache_constant;

			case 'WP_CONTENT_DIR':
				return $this->wp_content_dir;

			case 'IMAGIFY_RUNNING_VFS':
				return $this->is_running_vfs;

			case 'IMAGIFY_VERSION':
				if ( ! empty( $this->imagify_version ) ) {
					return $this->imagify_version;
				}
				break;

			case 'IMAGIFY_PLUGIN_NAME':
				return $this->plugin_name;

			default:
				if ( isset( $this->constants[$constant_name] ) ){
					return $this->constants[$constant_name];
				}

				if ( ! imagify_has_constant( $constant_name ) ) {
					return $default;
				}

				return constant( $constant_name );
		}
	}

	protected function stubWpNormalizePath() {
		Functions\when( 'wp_normalize_path' )->alias(
			function( $path ) {
				if ( true === $this->just_return_path ) {
					return $path;
				}

				$path = str_replace( '\\', '/', $path );

				if ( ':' === substr( $path, 1, 1 ) ) {
					$path = ucfirst( $path );
				}

				return $path;
			}
		);
	}

	protected function stubWpParseUrl() {
		Functions\when( 'wp_parse_url' )->alias(
			function( $url, $component = - 1 ) {
				return parse_url( $url, $component );
			}
		);
	}

	protected function stubfillWpBasename() {
		Functions\when( 'wp_basename' )->alias(
			function( $path, $suffix = '' ) {
				return urldecode( basename( str_replace( [ '%2F', '%5C' ], '/', urlencode( $path ) ), $suffix ) );
			}
		);
	}

	protected function stubSetUrlSchema(){
		// set_url_scheme().
		Functions\when( 'set_url_scheme' )->alias( function( $url, $scheme = null ) {
			$orig_scheme = $scheme;

			if ( ! $scheme ) {
				$scheme = 'https';
			} elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
				$scheme = 'https';
			} elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
				$scheme = 'https';
			}

			$url = trim( $url );
			if ( substr( $url, 0, 2 ) === '//' ) {
				$url = 'http:' . $url;
			}

			if ( 'relative' == $scheme ) {
				$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
				if ( $url !== '' && $url[0] === '/' ) {
					$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
				}
			} else {
				$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
			}

			return $url;
		} );
	}
}
