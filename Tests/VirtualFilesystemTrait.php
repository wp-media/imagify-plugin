<?php

namespace Imagify\Tests;

use Brain\Monkey\Functions;
use org\bovigo\vfs\vfsStream;

trait VirtualFilesystemTrait {
	protected $original_entries      = [];
	protected $shouldNotClean        = [];
	protected $entriesBefore         = [];
	protected $dumpResults           = false;
	protected $default_vfs_structure = '/vfs-structure/default.php';

	protected function initDefaultStructure() {
		if ( empty( $this->config ) ) {
			$this->loadConfig();
		}

		if ( array_key_exists( 'structure', $this->config ) ) {
			return;
		}

		$this->config['structure'] = require IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . $this->default_vfs_structure;
	}

	protected function setUpOriginalEntries() {
		$this->original_entries = array_merge( $this->original_files, $this->original_dirs );
		$this->original_entries = array_filter( $this->original_entries );
		sort( $this->original_entries );
	}

	protected function stripVfsRoot( $path ) {
		$search = vfsStream::SCHEME . "://{$this->rootVirtualDir}";
		$search = rtrim( $search, '/\\' ) . '/';

		return str_replace( $search, '', $path );
	}

	protected function getDirUrl( $dir ) {
		if ( empty( $dir ) ) {
			return $this->filesystem->getUrl( $this->config['vfs_dir'] );
		}

		return $dir;
	}

	public function getPathToFixturesDir() {
		return IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR;
	}

	public function getDefaultVfs() {
		return [
			'wp-admin'      => [],
			'wp-content'    => [
				'mu-plugins'       => [],
				'plugins'          => [
					'wp-rocket' => [],
				],
				'themes'           => [
					'twentytwenty' => [],
				],
				'uploads'          => [],
			],
			'wp-includes'   => [],
			'wp-config.php' => '',
			'index.php'     => '',
		];
	}

	/**
	 * Changes directory permission. If file is given, changes its parent directory's permission.
	 *
	 * @param string $path       Absolute path to the directory (or file).
	 * @param int    $permission Permission level to set.
	 */
	protected function changePermissions( $path, $permission = 0000 ) {
		if ( $this->filesystem->is_file( $path ) ) {
			$path = dirname( $path );
		}

		$dir = $this->filesystem->getDir( $path );
		$dir->chmod( $permission ); // Only the root user.
	}

	protected function redefineImagifyDirectFilesystem() {
		// Redefine imagify_direct_filesystem() to use the virtual filesystem.
		Functions\when( 'imagify_direct_filesystem' )->justReturn( $this->filesystem );
	}


}
