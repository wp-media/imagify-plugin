<?php
namespace Imagify\User;

use Imagify_Data;
use WP_Error;

/**
 * Imagify User class.
 *
 * @since 1.0
 */
class User {
	/**
	 * The Imagify user ID.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The user email.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The plan ID.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	public $plan_id;

	/**
	 * The plan label.
	 *
	 * @since 1.2
	 *
	 * @var string
	 */
	public $plan_label;

	/**
	 * The total quota.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	public $quota;

	/**
	 * The total extra quota (Imagify Pack).
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	public $extra_quota;

	/**
	 * The extra quota consumed.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	public $extra_quota_consumed;

	/**
	 * The current month consumed quota.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	public $consumed_current_month_quota;

	/**
	 * The next month date to credit the account.
	 *
	 * @since 1.1.1
	 *
	 * @var string
	 */
	public $next_date_update;

	/**
	 * If the account is activate or not.
	 *
	 * @since 1.0.1
	 *
	 * @var bool
	 */
	public $is_active;

	/**
	 * If the account is monthly or yearly.
	 *
	 * @var bool
	 */
	public $is_monthly;

	/**
	 * Store a \WP_Error object if the request to fetch the user data failed.
	 * False overwise.
	 *
	 * @var bool|WP_Error
	 * @since 1.9.9
	 */
	private $error = false;

	/**
	 * Initialisation.
	 *
	 * @var bool
	 */
	protected $initialized = false;

	/**
	 * Initialise the user data by fetching the api data
	 *
	 * @return void
	 */
	public function init_user() {
		if ( $this->initialized ) {
			return;
		}

		$user = get_imagify_user();

		if ( is_wp_error( $user ) ) {
			$this->error = $user;
			return;
		}

		$this->set_user_properties( $user );
		$this->initialized = true;
	}

	/**
	 * Set user properties
	 *
	 * @param object $user User object data.
	 *
	 * @return void
	 */
	private function set_user_properties( $user ) {
		$this->id                           = $user->id;
		$this->email                        = $user->email;
		$this->plan_id                      = (int) $user->plan_id;
		$this->plan_label                   = ucfirst( $user->plan_label );
		$this->quota                        = $user->quota;
		$this->extra_quota                  = $user->extra_quota;
		$this->extra_quota_consumed         = $user->extra_quota_consumed;
		$this->consumed_current_month_quota = $user->consumed_current_month_quota;
		$this->next_date_update             = $user->next_date_update;
		$this->is_active                    = $user->is_active;
		$this->is_monthly                   = $user->is_monthly;
	}

	/**
	 * Get the possible error returned when fetching user data.
	 *
	 * @return bool|WP_Error A \WP_Error object if the request to fetch the user data failed. False overwise.
	 * @since 1.9.9
	 */
	public function get_error() {
		$this->init_user();

		return $this->error;
	}

	/**
	 * Percentage of consumed quota, including extra quota.
	 *
	 * @since 1.0
	 *
	 * @return float|int
	 */
	public function get_percent_consumed_quota() {
		static $done = false;

		if ( $this->get_error() ) {
			return 0;
		}

		$quota          = $this->quota;
		$consumed_quota = $this->consumed_current_month_quota;

		if ( imagify_round_half_five( $this->extra_quota_consumed ) < $this->extra_quota ) {
			$quota          += $this->extra_quota;
			$consumed_quota += $this->extra_quota_consumed;
		}

		if ( ! $quota || ! $consumed_quota ) {
			$percent = 0;
		} else {
			$percent = 100 * $consumed_quota / $quota;
			$percent = round( $percent, 1 );
			$percent = min( max( 0, $percent ), 100 );
		}

		$percent = (float) $percent;

		if ( $done ) {
			return $percent;
		}

		$previous_percent = Imagify_Data::get_instance()->get( 'previous_quota_percent' );

		// Percent is not 100% anymore.
		if ( 100.0 === (float) $previous_percent && $percent < 100 ) {
			/**
			 * Triggered when the consumed quota percent decreases below 100%.
			 *
			 * @since  1.7
			 * @author Grégory Viguier
			 *
			 * @param float|int $percent The current percentage of consumed quota.
			 */
			do_action( 'imagify_not_over_quota_anymore', $percent );
		}

		// Percent is not >= 80% anymore.
		if ( ( (float) $previous_percent >= 80.0 && $percent < 80 ) ) {
			/**
			 * Triggered when the consumed quota percent decreases below 80%.
			 *
			 * @since  1.7
			 * @author Grégory Viguier
			 *
			 * @param float|int $percent          The current percentage of consumed quota.
			 * @param float|int $previous_percent The previous percentage of consumed quota.
			 */
			do_action( 'imagify_not_almost_over_quota_anymore', $percent, $previous_percent );
		}

		if ( (float) $previous_percent !== (float) $percent ) {
			Imagify_Data::get_instance()->set( 'previous_quota_percent', $percent );
		}

		$done = true;

		return $percent;
	}

	/**
	 * Count percent of unconsumed quota.
	 *
	 * @since 1.0
	 *
	 * @return float|int
	 */
	public function get_percent_unconsumed_quota() {
		$this->init_user();
		return 100 - $this->get_percent_consumed_quota();
	}

	/**
	 * Check if the user has a free account.
	 *
	 * @since 1.1.1
	 *
	 * @return bool
	 */
	public function is_free() {
		$this->init_user();
		return 1 === $this->plan_id;
	}

	/**
	 * Check if the user is a growth account
	 *
	 * @return bool
	 */
	public function is_growth() {
		$this->init_user();
		return ( 16 === $this->plan_id || 18 === $this->plan_id );
	}

	/**
	 * Check if the user is an infinite account
	 *
	 * @return bool
	 */
	public function is_infinite() {
		$this->init_user();
		return ( 15 === $this->plan_id || 17 === $this->plan_id );
	}

	/**
	 * Check if the user has consumed all his/her quota.
	 *
	 * @since 1.1.1
	 * @since 1.9.9 Return false if the request to fetch the user data failed.
	 *
	 * @return bool
	 */
	public function is_over_quota() {
		if ( $this->get_error() ) {
			return false;
		}

		return (
			$this->is_free()
			&&
			floatval( 100 ) === round( $this->get_percent_consumed_quota() )
		);
	}

	/**
	 * Get user Id
	 *
	 * @return string
	 */
	public function get_id() {
		$this->init_user();

		return $this->id;
	}

	/**
	 * Get user email.
	 *
	 * @return string
	 */
	public function get_email() {
		$this->init_user();

		return $this->email;
	}

	/**
	 * Get plan id.
	 *
	 * @return int
	 */
	public function get_plan_id() {
		$this->init_user();

		return $this->plan_id;
	}

	/**
	 * Get user quota.
	 *
	 * @return int
	 */
	public function get_quota() {
		$this->init_user();

		return $this->quota;
	}
}
