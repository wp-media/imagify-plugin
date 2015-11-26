<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_User {
	/**
	 * The Imagify user ID
	 *
	 * @since 1.0
	 *
	 * @var    string
	 * @access public
	 */
	public $id;
	
	/**
	 * The user email
	 *
	 * @since 1.0
	 *
	 * @var    string
	 * @access public
	 */
	public $email;

	/**
	 * The plan ID
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $plan_id;
	
	/**
	 * The plan label
	 *
	 * @since 1.2
	 *
	 * @var    string
	 * @access public
	 */
	public $plan_label;

	/**
	 * The total quota
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $quota;
	
	/**
	 * The total extra quota (Imagify Pack)
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $extra_quota;
	
	/**
	 * The extra quota consumed
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $extra_quota_consumed;

	/**
	 * The current month consumed quota
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $consumed_current_month_quota;
	
	/**
	 * The next month date to credit the account
	 *
	 * @since 1.1.1
	 *
	 * @var    date
	 * @access public
	 */
	public $next_date_update;
	
	/**
	 * If the account is activate or not
	 *
	 * @since 1.0.1
	 *
	 * @var    bool
	 * @access public
	 */
	public $is_active;
	
	 /**
     * The constructor
     *
	 * @since 1.0
	 *
     * @return void
     **/
	public function __construct() {
		$user = get_imagify_user();

		if ( ! is_wp_error( $user ) ) {
			$this->id                           = $user->id;
			$this->email                        = $user->email;
			$this->plan_id                      = $user->plan_id;
			$this->plan_label                   = ucfirst( $user->plan_label );
			$this->quota                        = $user->quota;
			$this->extra_quota                  = $user->extra_quota;
			$this->extra_quota_consumed         = $user->extra_quota_consumed;
			$this->consumed_current_month_quota = $user->consumed_current_month_quota;
			$this->next_date_update 			= $user->next_date_update;
			$this->is_active                    = $user->is_active;
		}
	}

	/**
	 * Count percent of consumed quota.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_percent_consumed_quota() {
		$percent        = 0;
		$quota          = $this->quota;
		$consumed_quota = $this->consumed_current_month_quota;
		
		if ( ( $this->quota + $this->extra_quota ) - ( $this->consumed_current_month_quota + $this->extra_quota_consumed ) <= 0 ) {
			return 100;
		}
		
		if( imagify_round_half_five( $this->extra_quota_consumed ) < $this->extra_quota ) {
			$quota          = $this->extra_quota + $quota;
			$consumed_quota = $consumed_quota + $this->extra_quota_consumed;
		}
	
		$percent = 100 - ( ( $quota - $consumed_quota ) / $quota ) * 100;
		$percent = min ( round( $percent, 1 ), 100 );
		
		return $percent;
	}

	/**
	 * Count percent of unconsumed quota.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_percent_unconsumed_quota() {
		$percent = 100 - $this->get_percent_consumed_quota();
		return $percent;
	}
	
	/**
	 * Check if the user has a free account.
	 *
	 * @since 1.1.1
	 *
	 * @access public
	 * @return bool
	 */
	public function is_free() {
		if ( 1 == $this->plan_id ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check if the user has consumed its quota.
	 *
	 * @since 1.1.1
	 *
	 * @access public
	 * @return bool
	 */
	public function is_over_quota() {
		if ( $this->is_free() && 100 == $this->get_percent_consumed_quota() ) {
			return true;
		}
		
		return false;
	}
}