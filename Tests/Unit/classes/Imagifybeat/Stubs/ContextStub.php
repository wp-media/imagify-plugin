<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Stubs;

/**
 * Stub context: allows the "context:describer" pairs it is given.
 */
class ContextStub {
	/**
	 * Context name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Allowed "context:describer" pairs.
	 *
	 * @var string[]
	 */
	private $allowed;

	/**
	 * Constructor.
	 *
	 * @param string   $name    Context name.
	 * @param string[] $allowed Allowed "context:describer" pairs.
	 */
	public function __construct( string $name, array $allowed ) {
		$this->name    = $name;
		$this->allowed = $allowed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_network_wide() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function current_user_can( $describer, $media_id = null ) {
		return in_array( "{$this->name}:{$describer}", $this->allowed, true );
	}
}
