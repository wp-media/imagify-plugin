<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_User {
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
     * The constructor
     *
	 * @since 1.0
	 *
     * @return void
     **/
	public function __construct() {
		$user = get_imagify_user();

		if ( ! is_wp_error( $user ) ) {
			$this->email                        = $user->email;
			$this->plan_id                      = $user->plan_id;
			$this->quota                        = $user->quota;
			$this->extra_quota                  = $user->extra_quota;
			$this->extra_quota_consumed         = $user->extra_quota_consumed;
			$this->consumed_current_month_quota = $user->consumed_current_month_quota;
		}
	}

	/**
	 * Get the plan label
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_plan_label() {
		$label = ( 1 === $this->plan_id ) ? __( 'Free', 'imagify' ) : __( 'Pro', 'imagify' );
		return $label;
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
}