<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify\Tests\Integration\TestCase as BaseTestCase;
use WPMedia\PHPUnit\Integration\HttpRequestTrait;

abstract class TestCase extends BaseTestCase {
	use HttpRequestTrait;

	/**
	 * A syntactically valid API key. The Imagify API is mocked, so the key only
	 * needs to be non-empty for the request to be attempted.
	 *
	 * @var string
	 */
	protected $validApiKey = 'imagify1234567890abcdefghijklmnopqrst';

	protected $originalUserInstance;

	public function set_up() {
		parent::set_up();

		// Block and mock any outbound HTTP request. Responses are declared per test via `$this->config['http']`.
		$this->setup_http();

		$this->originalUserInstance = $this->resetPropertyValue( 'user', Imagify::class );

		//Clean up the transients for API cache
		delete_transient('imagify_user_cache');
	}

	public function tear_down() {
		// Restore the user on the static property.
		$this->setPropertyValue( 'user', Imagify::class, $this->originalUserInstance );

		//Clean up the transients for API cache
		delete_transient('imagify_user_cache');

		// Fails the test if an HTTP request was not mocked, then removes the filter.
		$this->tear_down_http();

		parent::tear_down();
	}

	/**
	 * Mocks the `users/me/` endpoint with a successful user account response.
	 *
	 * @param array $data Values overriding the default user account payload.
	 *
	 * @return void
	 */
	protected function mockUserAccount( array $data = [] ) {
		$user = array_merge(
			[
				'id'                           => 14,
				'email'                        => 'imagify@example.com',
				'plan_id'                      => 1,
				'plan_label'                   => 'free',
				'account_type'                 => 'free',
				'quota'                        => 1000,
				'extra_quota'                  => 0,
				'extra_quota_consumed'         => 0,
				'consumed_current_month_quota' => 200,
				'next_date_update'             => '2026-09-01',
				'is_active'                    => true,
				'is_monthly'                   => true,
			],
			$data
		);

		$this->mockUsersMeEndpoint( wp_json_encode( $user ), 200, 'OK' );
	}

	/**
	 * Mocks the `users/me/` endpoint with the "Invalid token" error the API
	 * returns when the API key is not valid.
	 *
	 * @return void
	 */
	protected function mockInvalidToken() {
		$body = wp_json_encode(
			[
				'code'   => 'invalid_credentials',
				'detail' => 'Invalid token.',
			]
		);

		$this->mockUsersMeEndpoint( $body, 401, 'Unauthorized' );
	}

	/**
	 * Registers the mocked HTTP response for the `users/me/` endpoint.
	 *
	 * @param string $body    Response body.
	 * @param int    $code    HTTP status code.
	 * @param string $message HTTP status message.
	 *
	 * @return void
	 */
	private function mockUsersMeEndpoint( $body, $code, $message ) {
		$this->config['http'] = [
			Imagify::API_ENDPOINT . 'users/me/' => [
				'headers'  => [],
				'body'     => $body,
				'response' => [
					'code'    => $code,
					'message' => $message,
				],
				'cookies'  => [],
			],
		];
	}
}
