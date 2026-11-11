<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Stubs;

/**
 * Stub optimization process: the user can optimize its media, or not.
 */
class ProcessStub {
	/**
	 * Media ID.
	 *
	 * @var int
	 */
	public $media_id;

	/**
	 * Whether the user can optimize the media.
	 *
	 * @var bool
	 */
	private $allowed;

	/**
	 * Constructor.
	 *
	 * @param int  $media_id Media ID.
	 * @param bool $allowed  Whether the user can optimize the media.
	 */
	public function __construct( int $media_id, bool $allowed ) {
		$this->media_id = $media_id;
		$this->allowed  = $allowed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function current_user_can( $describer ) {
		return 'manual-optimize' === $describer && $this->allowed;
	}
}
