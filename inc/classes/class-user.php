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
	 * The current month consumed quota
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $consumed_current_month;

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
			$this->email                  = $user->email;
			$this->plan_id                = $user->plan_id;
			$this->quota                  = $user->quota;
			$this->consumed_current_month = $user->consumed_current_month;
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
		$percent = 0;
		$percent = 100 - ( ( $this->quota - $this->consumed_current_month ) / $this->quota ) * 100;
		$percent = ceil( $percent );
		
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