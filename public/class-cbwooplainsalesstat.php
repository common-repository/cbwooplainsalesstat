<?php

	/**
	 *
	 * @package   codeboxrplainsalesstat
	 * @author    Codeboxr Team <info@codeboxr.com>
	 * @license   GPL-2.0+
	 * @link      http://codeboxr.com
	 * @copyright 2015-2016 codeboxr.com
	 */
	class Cbwooplainsalesstat {

		const VERSION = CBWOOPAINSTATVERSION;

		protected $plugin_slug = CBWOOPAINSTATSUFFIX;
		protected static $instance = null;

		private function __construct() {

			// Load plugin text domain
			add_action( 'init', array( $this, 'cbwooplainsalesstat_load_plugin_textdomain' ) );
			// Activate plugin when new blog is added
			add_action( 'wpmu_new_blog', array( $this, 'cbwooplainsalesstat_activate_new_site' ) );
		}

		/**
		 * Return the plugin slug.
		 *
		 * @since    1.0.0
		 *
		 * @return    Plugin slug variable.
		 */
		public function get_plugin_slug() {
			return $this->plugin_slug;
		}

		/**
		 * Return an instance of this class.
		 *
		 * @since     1.0.0
		 *
		 * @return    object    A single instance of this class.
		 */
		public static function get_instance() {

			//check if woocommerce is installed
			if ( ! function_exists( 'WC' ) )
			{
				add_action( 'admin_notices', ( 'add_woocommerce_activation_notice_cbwooplainsalesstat' ) );

				return;
			}

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance )
			{
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Fired when the plugin is activated.
		 *
		 * @since    1.0.0
		 *
		 * @param    boolean $network_wide       True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public static function cbwooplainsalesstat_activate( $network_wide ) {

			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( ! ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) )
			{
				deactivate_plugins( basename( __FILE__ ) );
				wp_die( "This plugin requires WooCommerce to be installed and active." );
			}

			if ( function_exists( 'is_multisite' ) && is_multisite() )
			{

				if ( $network_wide )
				{
					// Get all blog ids
					$blog_ids = self::get_blog_ids();

					foreach ( $blog_ids as $blog_id )
					{

						switch_to_blog( $blog_id );
						self::single_activate();
					}

					restore_current_blog();
				}
				else
				{
					self::single_activate();
				}
			}
			else
			{
				self::single_activate();
			}
		}

		/**
		 * Fired when the plugin is deactivated.
		 *
		 * @since    1.0.0
		 *
		 * @param    boolean $network_wide True if WPMU superadmin uses
		 *                                 "Network Deactivate" action, false if
		 *                                 WPMU is disabled or plugin is
		 *                                 deactivated on an individual blog.
		 */
		public static function cbwooplainsalesstat_deactivate( $network_wide ) {

			if ( function_exists( 'is_multisite' ) && is_multisite() )
			{

				if ( $network_wide )
				{

					// Get all blog ids
					$blog_ids = self::get_blog_ids();

					foreach ( $blog_ids as $blog_id )
					{

						switch_to_blog( $blog_id );
						self::single_deactivate();
					}
					restore_current_blog();
				}
				else
				{
					self::single_deactivate();
				}
			}
			else
			{
				self::single_deactivate();
			}
		}

		/**
		 * Fired when a new site is activated with a WPMU environment.
		 *
		 * @since    1.0.0
		 *
		 * @param    int $blog_id ID of the new blog.
		 */
		public function cbwooplainsalesstat_activate_new_site( $blog_id ) {

			if ( 1 !== did_action( 'wpmu_new_blog' ) )
			{
				return;
			}

			switch_to_blog( $blog_id );
			self::single_activate();
			restore_current_blog();
		}

		/**
		 * Get all blog ids of blogs in the current network that are:
		 * - not archived
		 * - not spam
		 * - not deleted
		 *
		 * @since    1.0.0
		 *
		 * @return   array|false    The blog ids, false if no matches.
		 */
		private static function get_blog_ids() {

			global $wpdb;

			// get an array of blog ids
			$sql = "SELECT blog_id FROM $wpdb->blogs
			    WHERE archived = '0' AND spam = '0'
			    AND deleted = '0'";

			return $wpdb->get_col( $sql );
		}

		/**
		 * Fired for each blog when the plugin is activated.
		 *
		 * @since    1.0.0
		 */
		private static function single_activate() {

			$plugin_slug                                     = '_cbwooplainsalesstat';
			$cbxwoo_group[$plugin_slug . 'dailystat']        = 'on';
			$cbxwoo_group[$plugin_slug . 'weeklystat']       = 'on';
			$cbxwoo_group[$plugin_slug . 'sevendaysstat']    = 'on';
			$cbxwoo_group[$plugin_slug . 'monthlystat']      = 'on';
			$cbxwoo_group[$plugin_slug . 'twelvemonthsstat'] = 'on';
			$cbxwoo_group[$plugin_slug . 'yearlystat']       = 'on';
			$cbxwoo_group[$plugin_slug . 'betweendatesstat'] = 'on';

			$cbxwoo_group = ( get_option( $plugin_slug . 'general_settings' ) );

			if ( is_array( $cbxwoo_group ) )
			{

				if ( empty( $cbxwoo_group ) )
				{
					update_option( $plugin_slug . 'general_settings', $cbxwoo_group );
				}
			}
			else
			{
				update_option( $plugin_slug . 'general_settings', $cbxwoo_group );
			}

			$chart_settings = array(
				$plugin_slug . 'chartweeklystat'           => 1,
				$plugin_slug . 'chartlistweeklystat'       => array
				(
					'line' => 'line'
				),
				$plugin_slug . 'chartsevendaysstat'        => 1,
				$plugin_slug . 'chartlistsevendaysstat'    => array
				(
					'line' => 'line'
				),
				$plugin_slug . 'chartmonthlystat'          => 1,
				$plugin_slug . 'chartlistmonthlystat'      => array
				(
					'line' => 'line'
				),
				$plugin_slug . 'charttwelvemonthsstat'     => 1,
				$plugin_slug . 'chartlisttwelvemonthsstat' => array
				(
					'line' => 'line'
				),
				$plugin_slug . 'chartyearlystat'           => 1,
				$plugin_slug . 'chartlistyearlystat'       => array
				(
					'line' => 'line'
				),
				$plugin_slug . 'chartbetweendatesstat'     => 1,
				$plugin_slug . 'chartlistbetweendatesstat' => array
				(
					'line' => 'line'
				),
			);
			$cbxwoo_group   = ( get_option( $plugin_slug . 'chart_settings' ) );
			if ( is_array( $cbxwoo_group ) )
			{

				if ( empty( $cbxwoo_group ) )
				{
					update_option( $plugin_slug . 'chart_settings', $chart_settings );
				}
			}
			else
			{
				update_option( $plugin_slug . 'chart_settings', $chart_settings );
			}
		}

		/**
		 * Fired for each blog when the plugin is deactivated.
		 *
		 * @since    1.0.0
		 */
		private static function single_deactivate() {

		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since    1.0.0
		 */
		public function cbwooplainsalesstat_load_plugin_textdomain() {

			$domain = $this->plugin_slug;
			load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
		}

	}
