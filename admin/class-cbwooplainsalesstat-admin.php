<?php
	/**
	 * @package   CbwooplainsalesstatAdmin
	 * @author    Codeboxr Team <info@codeboxr.com>
	 * @license   GPL-2.0+
	 * @link      http://codeboxr.com
	 * @copyright 2015-2016 codeboxr
	 */


	if ( ! defined( 'WPINC' ) )
	{
		die;
	}


	require_once( plugin_dir_path( __FILE__ ) . 'includes/cbwooplainstatfunctions.php' );

	/**
	 * Class CbwooplainsalesstatAdmin
	 */
	class CbwooplainsalesstatAdmin {

		protected static $instance = null;
		protected $plugin_screen_hook_suffix = null;

		/**
		 * Initialize the plugin by loading admin scripts & styles and adding a
		 * settings page and menu.
		 */
		private function __construct() {

			$plugin            = Cbwooplainsalesstat::get_instance();
			$this->plugin_slug = $plugin->get_plugin_slug();
			// Load admin style sheet and JavaScript.
			add_action( 'admin_enqueue_scripts', array( $this, 'cbwooplainsalesstat_enqueue_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'cbwooplainsalesstat_enqueue_admin_scripts' ) );
			// Add the options page and menu item.
			add_action( 'admin_init', array( $this, 'init_cbwooplainsalesstat_settings' ) );
			add_action( 'admin_menu', array( $this, 'cbwooplainsalesstat_add_plugin_admin_menu' ) );
			// Add an action link pointing to the options page.
			$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
			add_filter( 'plugin_action_links_' . $plugin_basename, array(
				$this,
				'cbwooplainsalesstat_add_action_links'
			) );

			//export buttons for monthly
			add_filter( 'cbwooplainsalesstat_nav_button_monthly', array(
				$this,
				'cbwooplainsalesstat_nav_button_monthly'
			), 10, 3 );

			add_action( 'admin_init', array( $this, 'cbwooplainsalesstat_export' ) );
		}

		/**
		 * Callback for activation plugin error check action 'plugin_activated'
		 *
		 */
//    public static function cbwooplainsalesstat_activation_error() {
//
//        update_option('cbwooplainsalesstat_activation_error', ob_get_contents());
//    }

		/**
		 * Return an instance of this class.
		 * @return object A single instance of this class.
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
		 * Add settings action link to the plugins page.
		 */
		public function cbwooplainsalesstat_add_action_links( $links ) {

			return array_merge(
				array(
					'settings' => '<a href="' . admin_url( 'options-general.php?page=cbwooreportsettings' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
				), $links
			);
		}

		/**
		 * run when admin init
		 * init settings sections anf fields
		 */
		public function init_cbwooplainsalesstat_settings() {

			$plugin_suffix = '_' . $this->plugin_slug;

			$sections = array(
				array(
					'id'    => $plugin_suffix . 'general_settings',
					'title' => __( 'General Settings', 'cbwooplainsalesstat' ),
					'desc'  => __( 'Select tabs you want to show', 'cbwooplainsalesstat' ),
				),
			);

			$sections = apply_filters( 'cbwooplainsalesstat_add_sections', $sections, $plugin_suffix );

			$cbwoopss_general_settings = array(
				array(
					'name'    => $plugin_suffix . 'monthlystat',
					'label'   => __( 'Monthly Report', 'cbwooplainsalesstat' ),
					'desc'    => '<a href="admin.php?page=cbwooplainsalesstat" target="_blank">' . __( 'Show monthly report (this is default and will always remain active )', 'cbwooplainsalesstat' ) . '</a>',
					'type'    => 'checkbox',
					'default' => 'on',
					'tooltip' => 'disabled',
				),
			);

			$fields = array(
				$plugin_suffix . 'general_settings' => apply_filters( 'cbwooplainsalesstat_add_general_settings', $cbwoopss_general_settings, $plugin_suffix ),
			);
			$fields = apply_filters( 'cbwooplainsalesstat_add_fields', $fields, $plugin_suffix );

			require_once( plugin_dir_path( __FILE__ ) . 'includes/class.cbwooplainsalesstatsettings.php' );

			$this->cbwooplainsalesstat_settings_api = new Cbwooplainsalesstat_Settings_API( $this->plugin_slug );
			$this->cbwooplainsalesstat_settings_api->set_sections( $sections );
			$this->cbwooplainsalesstat_settings_api->set_fields( $fields );
			//initialize them
			$this->cbwooplainsalesstat_settings_api->admin_init();
		}

		/**
		 * Render the settings page for this plugin.
		 *
		 * @since    1.0.0
		 */
		public function display_plugin_admin_page() {
			include_once( 'views/cbstatsettingsadmin.php' );
		}

		/**
		 * Register the administration menu for this plugin into the WordPress Dashboard menu.
		 * Register plain stat menu under woocommerce tab
		 */
		public function cbwooplainsalesstat_add_plugin_admin_menu() {

			$this->plugin_screen_hook_suffix = add_submenu_page(
				'woocommerce', __( 'CBX Sales Report', $this->plugin_slug ), __( 'CBX Sales Report', $this->plugin_slug ), 'manage_options', $this->plugin_slug, array(
				$this,
				'cbwooplainsalesstat_display_plain_stat'
			), plugins_url( 'assets/css/sale.png', __FILE__ )
			);

			add_options_page(
				__( 'CBX Woo Sales Report Setting', $this->plugin_slug ), __( 'CBX Woo Sales Report Setting', $this->plugin_slug ), 'manage_options', 'cbwooreportsettings', array(
					$this,
					'display_plugin_admin_page'
				)
			);
		}

		/**
		 * Register and enqueue admin-specific style sheet.
		 * @return    null    Return early if no settings page is registered.
		 */
		public function cbwooplainsalesstat_enqueue_admin_styles() {

			if ( ! isset( $this->plugin_screen_hook_suffix ) )
			{
				return;
			}
			global $page;
			$screen = get_current_screen();

			if ( $this->plugin_screen_hook_suffix == $screen->id || $page == 'cbwooreportsettings' || 'cbwooplainsalesstat' == $page )
			{
				wp_enqueue_style( $this->plugin_slug . '-ui-styles', plugins_url( 'assets/css/jquery-ui.min.css', __FILE__ ), array(), Cbwooplainsalesstat::VERSION );
				wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/cbwooplainsalesstat_admin.css', __FILE__ ), array(), Cbwooplainsalesstat::VERSION );
			}
		}

		/**
		 * Register and enqueue admin-specific JavaScript.
		 * @return    null    Return early if no settings page is registered.
		 */
		public function cbwooplainsalesstat_enqueue_admin_scripts() {

			global $page;
			if ( ! isset( $this->plugin_screen_hook_suffix ) )
			{
				return;
			}
			$screen = get_current_screen();
			if ( $this->plugin_screen_hook_suffix == $screen->id || $page == 'cbwooreportsettings' || 'cbwooplainsalesstat' == $page )
			{
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-datepicker' );

				//for admin
				wp_register_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/cbwooplainsalesstat_admin.js', __FILE__ ), array( 'jquery' ), Cbwooplainsalesstat::VERSION );
				//for chart
				wp_register_script( 'cbxchartkickgjsapi', '//www.google.com/jsapi', array(), Cbwooplainsalesstat::VERSION, false );
				wp_register_script( $this->plugin_slug . '-chart-script', plugins_url( 'assets/js/chartkick.js', __FILE__ ), array( 'jquery' ), Cbwooplainsalesstat::VERSION );

				// Localize the script with chart header strings
				$translation_array = array(
					'order_amount'      => __( 'Order Amount', 'cbwooplainsalesstat' ),
					'order_number'      => __( 'Order Number', 'cbwooplainsalesstat' ),
					'order_item_number' => __( 'Order Item Number', 'cbwooplainsalesstat' )
				);
				wp_localize_script( $this->plugin_slug . '-admin-script', 'cbwooplainsalesstat', $translation_array );

				wp_enqueue_script( $this->plugin_slug . '-admin-script' );
				wp_enqueue_script( 'cbxchartkickgjsapi' );
				wp_enqueue_script( $this->plugin_slug . '-chart-script' );
			}
		}

		/**
		 * @param $cur_year
		 * @param $cur_month
		 *
		 * @return string
		 */
		public static function cb_calender_prev_link( $cur_year, $cur_month ) {

			$cdpage_link = 'admin.php?page=cbwooplainsalesstat';
			$cdpage_link = $cdpage_link . '&';

			$mod_rewrite_months     = array();
			$mod_rewrite_months[1]  = _x( 'Jan', 'January abbreviation' );
			$mod_rewrite_months[2]  = _x( 'Feb', 'February abbreviation' );
			$mod_rewrite_months[3]  = _x( 'Mar', 'March abbreviation' );
			$mod_rewrite_months[4]  = _x( 'Apr', 'April abbreviation' );
			$mod_rewrite_months[5]  = _x( 'May', 'May abbreviation' );
			$mod_rewrite_months[6]  = _x( 'Jun', 'June abbreviation' );
			$mod_rewrite_months[7]  = _x( 'Jul', 'July abbreviation' );
			$mod_rewrite_months[8]  = _x( 'Aug', 'August abbreviation' );
			$mod_rewrite_months[9]  = _x( 'Sep', 'September abbreviation' );
			$mod_rewrite_months[10] = _x( 'Oct', 'October abbreviation' );
			$mod_rewrite_months[11] = _x( 'Nov', 'November abbreviation' );
			$mod_rewrite_months[12] = _x( 'Dec', 'December abbreviation' );

			$last_year = $cur_year - 1;

			if ( $cur_month == 1 )
			{
				$llink = esc_html__( 'Prev', 'cbwooplainsalesstat' );

				return '<a class = "button" href="' . $cdpage_link . 'cbstatmonth=12&amp;cbstatyear=' . $last_year . '">&laquo; ' . $llink . '</a>';
			}
			else
			{
				$next_month = $cur_month - 1;
				$llink      = esc_html__( 'Prev', 'cbwooplainsalesstat' );

				return '<a class = "button" href="' . $cdpage_link . 'cbstatmonth=' . $next_month . '&amp;cbstatyear=' . $cur_year . '">&laquo; ' . $llink . '</a>';
			}
		}

		/**
		 * @param $cur_year
		 * @param $cur_month
		 *
		 * @return string
		 */
		public static function cb_calender_current_link( $cur_year, $cur_month ) {

			$cdpage_link = 'admin.php?page=cbwooplainsalesstat';
			$cdpage_link = $cdpage_link . '&';

			return '<a class = "button cbwooplainsalesstat" href="' . $cdpage_link . 'cbstatmonth=' . $cur_month . '&amp;cbstatyear=' . $cur_year . '">' . __( 'Current Month', 'cbwooplainsalesstat' ) . '</a>';
		}

		/**
		 * @param $cur_year
		 * @param $cur_month
		 *
		 * @return string
		 */
		public static function cb_calender_next_link( $cur_year, $cur_month ) {

			$cdpage_link = 'admin.php?page=cbwooplainsalesstat';
			$cdpage_link = $cdpage_link . '&';

			$mod_rewrite_months     = array();
			$mod_rewrite_months[1]  = _x( 'Jan', 'January abbreviation' );
			$mod_rewrite_months[2]  = _x( 'Feb', 'February abbreviation' );
			$mod_rewrite_months[3]  = _x( 'Mar', 'March abbreviation' );
			$mod_rewrite_months[4]  = _x( 'Apr', 'April abbreviation' );
			$mod_rewrite_months[5]  = _x( 'May', 'May abbreviation' );
			$mod_rewrite_months[6]  = _x( 'Jun', 'June abbreviation' );
			$mod_rewrite_months[7]  = _x( 'Jul', 'July abbreviation' );
			$mod_rewrite_months[8]  = _x( 'Aug', 'August abbreviation' );
			$mod_rewrite_months[9]  = _x( 'Sep', 'September abbreviation' );
			$mod_rewrite_months[10] = _x( 'Oct', 'October abbreviation' );
			$mod_rewrite_months[11] = _x( 'Nov', 'November abbreviation' );
			$mod_rewrite_months[12] = _x( 'Dec', 'December abbreviation' );

			$next_year           = $cur_year + 1;
			$current_time        = current_time( 'timestamp' );
			$cbxstat_getdate     = getdate( $current_time );
			$cbxstatcurrentmonth = $cbxstat_getdate['mon'];
			$rlink               = __( 'Next', 'cbwooplainsalesstat' );

			if ( $cur_year > $cbxstat_getdate['year'] || ( $cur_year == $cbxstat_getdate['year'] && $cur_month >= $cbxstat_getdate['mon'] ) )
			{
				return '<a class = "button cbwooplainsalesstat" disabled href="#">' . $rlink . ' &raquo;</a>';
			}
			else
			{
				if ( $cur_month == 12 )
				{
					return '<a class = "button cbwooplainsalesstat" href="' . $cdpage_link . 'cbstatmonth=1&amp;cbstatyear=' . $next_year . '">' . $rlink . ' &raquo;</a>';
				}
				else
				{
					$next_month = $cur_month + 1;

					return '<a class = "button cbwooplainsalesstat" href="' . $cdpage_link . 'cbstatmonth=' . $next_month . '&amp;cbstatyear=' . $cur_year . '">' . $rlink . ' &raquo;</a>';
				}
			}
		}

		/**
		 * @param string $link
		 *
		 * @return string
		 */
		public static function get_cb_permalink( $link = '' ) {
			$link .= ( get_option( 'permalink_structure' ) ) ? '?' : '&';

			return $link;
		}

		/**
		 * @param string $cbday
		 * @param string $cbmonth
		 * @param string $cbyear
		 * day ,month and year given and orders for that time is calculated ,main query function called by all views
		 *
		 * @return array
		 */
		public static function cbwooplainsalesstat_get_sale( $cbday = '', $cbmonth = '', $cbyear = '' ) {

			if ( $cbyear == '' )
			{
				$cbxstat_year = strftime( '%Y' );
			}
			else
			{
				$cbxstat_year = $cbyear;
			}
			if ( $cbmonth == '' )
			{
				$cbxstat_month = strftime( '%m' );
			}
			else
			{
				$cbxstat_month = $cbmonth;
			}
			if ( $cbday == '' )
			{
				$cbxstat_date_args = array(
					'year'  => $cbxstat_year,
					'month' => $cbxstat_month,
				);
			}
			else
			{
				$cbxstat_date_args = array(
					'year'  => $cbxstat_year,
					'month' => $cbxstat_month,
					'day'   => $cbday,
				);
			}
			$cbxstat_orders            = array();
			$cbxstat_order_number      = 0;
			$cbxstat_order_item_number = (int) 0;
			$cbxstat_order_total       = $cbxstat_ordershipping_total = $cbxstat_ordertax_total = $cbxstat_orderdiscount_total = 0.0;

			$args = array(
				'post_type'           => 'shop_order',
				'posts_per_page'      => - 1,
				'ignore_sticky_posts' => 0,
				'post_status'         => 'wc-completed',
				'date_query'          => array(
					$cbxstat_date_args,
				),
			);

			$query = new WP_Query( $args );

			if ( $query->have_posts() ): while ( $query->have_posts() ) : $query->the_post();

				array_push( $cbxstat_orders, get_the_ID() );
				$cbxstat_order_number ++;
				$cbxstatorderobj           = new WC_Order( get_the_ID() );
				$cbxstat_items             = (int) $cbxstatorderobj->get_item_count();
				$cbxstat_order_item_number += $cbxstat_items;

				$cbxstat_order_amount          = $cbxstatorderobj->get_total();
				$cbxstat_order_ship_amount     = $cbxstatorderobj->get_total_shipping();
				$cbxstat_order_tax_amount      = $cbxstatorderobj->get_total_tax();
				$cbxstat_order_discount_amount = $cbxstatorderobj->get_total_discount();
				$cbxstat_order_total           += $cbxstat_order_amount;
				$cbxstat_ordershipping_total   += $cbxstat_order_ship_amount;
				$cbxstat_ordertax_total        += $cbxstat_order_tax_amount;
				$cbxstat_orderdiscount_total   += $cbxstat_order_discount_amount;

			endwhile;
			endif;
			wp_reset_query();

			return array(
				'orders'            => $cbxstat_orders,
				'order_amount'      => $cbxstat_order_total,
				'order_number'      => $cbxstat_order_number,
				'order_item_number' => $cbxstat_order_item_number,
				'order_discount'    => $cbxstat_orderdiscount_total,
				'order_tax'         => $cbxstat_ordertax_total,
				'order_shipping'    => $cbxstat_ordershipping_total
			);
		}

		/**
		 * @param int $active
		 * display 6 nevigation link as tab
		 *
		 * @return string
		 */
		public static function cbwooplainsalesstat_display_nav( $active = 3 ) {

			$cbstatlinks = apply_filters( 'cbwooplainsalesstat_display_nav_links', array( 'admin.php?page=cbwooplainsalesstat' ) );

			$cbxpainstat = get_option( '_cbwooplainsalesstatgeneral_settings' );


			$cbxpainstat_values = is_array( $cbxpainstat ) ? array_values( $cbxpainstat ) : array(
				'on',
				'on',
				'on',
				'on',
				'on',
				'on',
				'on'
			);
			$cbstatlinks_labels = apply_filters( 'cbwooplainsalesstat_display_nav_labels', array( __( 'Monthly', 'cbwooplainsalesstat' ) ) );

			$stathtml = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

			foreach ( $cbstatlinks as $index => $cbstatlink )
			{
				if ( $cbxpainstat_values[$index] == 'on' || $index == 3 ):
					if ( $active == $index )
					{
						$cbxclass = 'nav-tab-active ';
					}
					else
					{
						$cbxclass = '';
					}
					$stathtml .= '<a class="nav-tab ' . $cbxclass . '" href="' . $cbstatlink . '">' . $cbstatlinks_labels[$index] . '</a>';
				endif;
			}

			$stathtml .= '</h2>';

			return $stathtml;
		}

		/**
		 * Render the settings report pages for this plugin.
		 */
		public function cbwooplainsalesstat_display_plain_stat() {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_slug = '_cbwooplainsalesstat';
			$grab_pro    = '';
			echo '<div class="wrap">';

			if ( ! in_array( 'cbwooplainsalesstataddon/cbwooplainsalesstataddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
			{
				$grab_pro = sprintf( '  <a class="button" href="%s" target="_blank">%s</a>', 'http://codeboxr.com/product/woocommerce-plain-sales-report-for-wordpress#downloadarea', __( 'Grab Pro', 'cbwooplainsalesstat' ) );
			}
			echo '<h2>' . __( 'CBX Woo Sales Report', 'cbwooplainsalesstat' ) . $grab_pro . '</h2>';

			if ( ! class_exists( 'TCPDF' ) )
			{
				echo '<div class="notice notice-info "><p>' .
				     __( 'Please install the <a href="https://wordpress.org/plugins/tcpdf/" target="_blank">TCPDF Library</a> plugin to export as pdf.', 'cbxwpsimpleaccountinglog' ) .
				     '</p></div>';
			}

			//for general settings
			$cbxpainstat             = apply_filters( 'cbxwoo_display_plain_stat_general_settings', array( $plugin_slug . 'monthlystat' => 'on' ), $plugin_slug );
			$general_settings_optval = get_option( $plugin_slug . 'general_settings', array( $plugin_slug . 'monthlystat' => 'on' ) );
			$general_settings_val    = ( $general_settings_optval != null ) ? $general_settings_optval : array();

			$cbxpainstat = array_merge( $cbxpainstat, $general_settings_val );

			//for chart settings
			$chart_settings        = apply_filters( 'cbxwoo_display_plain_stat_chart_settings', array(
				$plugin_slug . 'chartmonthlystat'     => 1,
				$plugin_slug . 'chartlistmonthlystat' => array( 'line' => 'line' )
			), $plugin_slug );
			$chart_settings_optval = get_option( $plugin_slug . 'chart_settings', array(
				$plugin_slug . 'chartmonthlystat'     => 1,
				$plugin_slug . 'chartlistmonthlystat' => array( 'line' => 'line' )
			) );
			$chart_settings_val    = ( $chart_settings_optval != null ) ? $chart_settings_optval : array();

			$cbx_chart_settings = array_merge( $chart_settings, $chart_settings_val );

			if ( isset( $_REQUEST['cbstattoday'] ) && $_REQUEST['cbstattoday'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstatdailystat'] == 'on' )
			{
				//addon implementation for 'today' report
				do_action( 'cbwooplainsalesstat_daily', $cbx_chart_settings );
			}
			else if ( isset( $_REQUEST['cbstatlastsevendays'] ) && $_REQUEST['cbstatlastsevendays'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstatsevendaysstat'] == 'on' )
			{
				// addon implementation for 'Last Seven Days' report
				do_action( 'cbwooplainsalesstat_cbstatlastsevendays', $cbx_chart_settings );
			}
			else if ( isset( $_REQUEST['cbstatlastweek'] ) && $_REQUEST['cbstatlastweek'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstatweeklystat'] == 'on' )
			{
				// addon implementation for 'this week' report
				do_action( 'cbwooplainsalesstat_cbstatweekly', $cbx_chart_settings );
			}
			else if ( isset( $_REQUEST['cbstatlastyear'] ) && $_REQUEST['cbstatlastyear'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstatyearlystat'] == 'on' )
			{
				// addon implementation for 'Last year' report
				do_action( 'cbwooplainsalesstat_cbstatyearly', $cbx_chart_settings );
			}
			else if ( isset( $_REQUEST['cbstatlasttwelvemonth'] ) && $_REQUEST['cbstatlasttwelvemonth'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstattwelvemonthsstat'] == 'on' )
			{
				// addon implementation for 'last twelvemonth' report
				do_action( 'cbwooplainsalesstat_cbstatlasttwelvemonth', $cbx_chart_settings );
			}
			else if ( isset( $_REQUEST['cbstatbetweentwodates'] ) && $_REQUEST['cbstatbetweentwodates'] != null && is_array( $cbxpainstat ) && $cbxpainstat['_cbwooplainsalesstatbetweendatesstat'] == 'on' )
			{
				// addon implementation for 'date range' report
				do_action( 'cbwooplainsalesstat_cbstatbetweentwodates', $cbx_chart_settings );
			}
			else
			{
				// default plugin implementation for 'per month' report
				self:: cbwooplainsalesstat_monthly( $cbx_chart_settings );
			}
			echo '</wrap>';
		}

		/**
		 * getting chart html
		 */
		public static function cbxreport_charthtml( $settings ) {

			//var_dump($settings);
			$cbxcharthtml = '';
			$cbxsettings  = $settings;
			if ( $cbxsettings['enable'] == 1 )
			{
				$cbxcharthtml .= '<h3>' . __( 'Report in chart', 'cbwooplainsalesstat' ) . '</h3>';
				foreach ( $cbxsettings['types'] as $index => $type )
				{
					$cbxcharthtml .= '<input type = "radio" ' . checked( 'line', $index, false ) . ' class = "cbxsalesreportchart"  name = "cbxsalesreportchart" value="' . $index . '" /><span class = "cbchartlabel" >' . ucfirst( $type ) . '</span>';
				}
				$cbxcharthtml .= '<div class="cbxsalesreportchart_wrapper" ><canvas width="300" height="300" id = "cbxsalesreportchart_draw" class = "cbxsalesreportchart_draw"></canvas></div>';
			}

			return $cbxcharthtml;
		}

		/**
		 * build report of all days of a month
		 */
		public static function cbwooplainsalesstat_monthly( $cbx_chart_settings ) {

			$plugin_slug     = '_cbwooplainsalesstat';
			$cbxstat_getdate = getdate();

			$cbxstat_orderyear = $cbxstat_getdate["year"];
			$cbxstat_ordermon  = $cbxstat_getdate["mon"];

			if ( isset( $_REQUEST['cbstatyear'] ) && $_REQUEST['cbstatyear'] != null && isset( $_REQUEST['cbstatmonth'] ) && $_REQUEST['cbstatmonth'] != null )
			{
				$cbxstat_orderyear = $_REQUEST['cbstatyear'];
				$cbxstat_ordermon  = $_REQUEST['cbstatmonth'];
			}

			$stat_of_month = self::cbwooplainstat_build_stat_permonth( $cbxstat_ordermon, $cbxstat_orderyear );

			$extra_nav_button = '';

			$stathtml = self::cbwooplainsalesstat_display_nav(); //default 3 monthly report
			$stathtml .= '<div class="cbwooplainsalesstat_button_wrapper">';
			$stathtml .= self::cb_calender_prev_link( $cbxstat_orderyear, $cbxstat_ordermon ) . self::cb_calender_current_link( $cbxstat_getdate["year"], $cbxstat_getdate["mon"] ) . self::cb_calender_next_link( $cbxstat_orderyear, $cbxstat_ordermon );

			$stathtml .= apply_filters( 'cbwooplainsalesstat_nav_button_monthly', $extra_nav_button, $cbxstat_orderyear, $cbxstat_ordermon );
			$stathtml .= '</div>';

			$stathtml .= '<div class="cbwooplainsalesstat_wrapper metabox-holder">' . $stat_of_month[0] . '</div>';

			// get chart settings
			$cbx_this_chart_settings = $cbx_chart_settings;


			$cbx_chart['enable'] = $cbx_this_chart_settings [$plugin_slug . 'chartmonthlystat'];
			$cbx_chart['types']  = $cbx_this_chart_settings [$plugin_slug . 'chartlistmonthlystat'];
			echo $stathtml;

			CbwooplainsalesstatFunctions::cbwooplainsalesstat_draw_graph_common( $stat_of_month[1], $cbx_chart_settings, $cbx_chart );
		}

		/**
		 * Extra navs for months
		 *
		 * @param $extra_nav_button
		 * @param $year
		 * @param $month
		 *
		 * @return string
		 */
		public function cbwooplainsalesstat_nav_button_monthly( $extra_nav_button, $year, $month ) {
			// cbwooplainsalesstataddon
			$current_url = $_SERVER['REQUEST_URI'];
			$parts       = parse_url( $current_url );
			parse_str( $parts['query'], $query );

			$type        = __( 'monthly', 'cbwooplainsalesstat' );
			$cbstatmonth = $cbstatyear = null;
			if ( array_key_exists( 'cbstatmonth', $query ) )
			{
				$cbstatmonth = $query['cbstatmonth'];
			}
			if ( array_key_exists( 'cbstatyear', $query ) )
			{
				$cbstatyear = $query['cbstatyear'];
			}

			$extra_nav_button = '
		<a class="button" href="' . admin_url( 'admin.php?page=cbwooplainsalesstat&export=csv&type=' . $type . '&cbstatmonth=' . $cbstatmonth . '&cbstatyear=' . $cbstatyear ) . '">' . __( 'Export as CSV', 'cbwooplainsalesstat' ) . '</a>
		<a class="button" href="' . admin_url( 'admin.php?page=cbwooplainsalesstat&export=xls&type=' . $type . '&cbstatmonth=' . $cbstatmonth . '&cbstatyear=' . $cbstatyear ) . '">' . __( 'Export as xls', 'cbwooplainsalesstat' ) . '</a>
		<a class="button" href="' . admin_url( 'admin.php?page=cbwooplainsalesstat&export=xlsx&type=' . $type . '&cbstatmonth=' . $cbstatmonth . '&cbstatyear=' . $cbstatyear ) . '">' . __( 'Export as xlsx', 'cbwooplainsalesstat' ) . '</a>
		<a class="button" ' . CbwooplainsalesstatFunctions::pdfExportBtnDisable() . ' href="' . (CbwooplainsalesstatFunctions::isTCPDFInstalled()? admin_url( 'admin.php?page=cbwooplainsalesstat&export=pdf&type=' . $type . '&cbstatmonth=' . $cbstatmonth . '&cbstatyear=' . $cbstatyear ) : '#') . '">' . esc_html__( 'Export as Pdf', 'cbwooplainsalesstat' ) . '</a>
	';

			return $extra_nav_button;
		}


		/**
		 * @param $cbxstat_ordermon
		 * @param $cbxstat_orderyear
		 * called from cbwooplainsalesstat_monthly
		 *
		 * @return string
		 */
		public static function cbwooplainstat_build_stat_permonth( $cbxstat_ordermon, $cbxstat_orderyear ) {

			$current_time          = current_time( 'timestamp' );
			$cbxstat_getdate       = getdate( $current_time );
			$currency_pos          = get_option( 'woocommerce_currency_pos' );
			$price_format          = get_woocommerce_price_format();
			$currency_symbol       = get_woocommerce_currency_symbol();
			$cbxstat_month_names   = array();
			$cbxstat_days_of_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

			$cbxstat_month_names[0]  = _x( 'Jan', 'January abbreviation' );
			$cbxstat_month_names[1]  = _x( 'Feb', 'February abbreviation' );
			$cbxstat_month_names[2]  = _x( 'Mar', 'March abbreviation' );
			$cbxstat_month_names[3]  = _x( 'Apr', 'April abbreviation' );
			$cbxstat_month_names[4]  = _x( 'May', 'May abbreviation' );
			$cbxstat_month_names[5]  = _x( 'Jun', 'June abbreviation' );
			$cbxstat_month_names[6]  = _x( 'Jul', 'July abbreviation' );
			$cbxstat_month_names[7]  = _x( 'Aug', 'August abbreviation' );
			$cbxstat_month_names[8]  = _x( 'Sep', 'September abbreviation' );
			$cbxstat_month_names[9]  = _x( 'Oct', 'October abbreviation' );
			$cbxstat_month_names[10] = _x( 'Nov', 'November abbreviation' );
			$cbxstat_month_names[11] = _x( 'Dec', 'December abbreviation' );

			$cbxstat_head = sprintf( __( 'Daily Sales Log for Month %s', 'cbwooplainsalesstat' ), $cbxstat_month_names[$cbxstat_ordermon - 1] . ' , ' . $cbxstat_orderyear );

			$cbxstat = '	<h3 class="hndle ui-sortable-handle"><span>' . $cbxstat_head . '</span></h3>';

			$cbxgraphdata = array( 'legend' => array(), 'label' => array() );

			$cbxstat .= '<table class="widefat fixed pages">';
			$cbxstat .= '<thead>
                        <tr>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Day', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'No. Orders', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Total Items', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Discount (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Shipping (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Tax (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Order Amount (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Progress', 'cbwooplainsalesstat' ) . '</th>
                        </tr>
                       </thead>
                       <tfoot>
                        <tr>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Day', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'No. Orders', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Total Items', 'cbwooplainsalesstat' ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Discount (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Shipping (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Tax (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . sprintf( __( 'Order Amount (%s)', 'cbwooplainsalesstat' ), $currency_symbol ) . '</th>
                            <th class="manage-column" scope="col" style="text-align:center;">' . __( 'Progress', 'cbwooplainsalesstat' ) . '</th>
                        </tr>
                       </tfoot>
                       <tbody id="the-list">';

			$days_of_this_month = $cbxstat_days_of_month[$cbxstat_ordermon - 1];

			$today          = false;
			$countable_days = $total_orders = $total_items = $total_amount = $cbxprevmonthamount = 0;
			$cbxstatorders  = array();

			for ( $i = 1; $i <= $days_of_this_month; $i ++ )
			{
				$cbxstatorder      = self:: cbwooplainsalesstat_get_sale( $i, $cbxstat_ordermon, $cbxstat_orderyear );
				$cbxstatorders[$i] = $cbxstatorder;
			}

			for ( $i = $days_of_this_month; $i >= 1; $i -- )
			{

				if ( $i == $cbxstat_getdate['mday'] && $cbxstat_ordermon == $cbxstat_getdate['mon'] && $cbxstat_orderyear == $cbxstat_getdate['year'] )
				{
					$today = true;
				}
				else
				{
					$today = false;
				}

				if ( $cbxstat_ordermon == $cbxstat_getdate['mon'] && $cbxstat_orderyear == $cbxstat_getdate['year'] && $i > $cbxstat_getdate['mday'] )
				{
					continue;
				}

				$cbxstatorder    = $cbxstatorders[$i];
				$cbxstat_newdate = $cbxstat_orderyear . '-' . $cbxstat_ordermon . '-' . $i;
				$cbxstat_weekday = date( 'D', strtotime( $cbxstat_newdate ) ); // note: first arg to date() is lower-case L

				$cbxgraphdata['label'][]  = $i;
				$cbxgraphdata['legend'][] = $i . ' ( ' . __( $cbxstat_weekday ) . ' ) ';

				$cbxgraphdata['order_amount'][]      = $cbxstatorder ['order_amount'];
				$cbxgraphdata['order_number'][]      = $cbxstatorder ['order_number'];
				$cbxgraphdata['order_item_number'][] = $cbxstatorder ['order_item_number'];

				$cbxstat .= '<tr class="' . ( ( $i % 2 == 0 ) ? 'alternate' : '' ) . '"  ' . ( ( $today ) ? ' style="font-weight:bold;text-align:center;"' : 'style="text-align:center;"' ) . ' >
                            <td scope="col">' . $i . ' ( ' . __( $cbxstat_weekday ) . ' ) ' . '</td>
                            <td scope="col">' . $cbxstatorder['order_number'] . '</td>
                            <td scope="col">' . $cbxstatorder['order_item_number'] . '</td>
                            <td scope="col">' . sprintf( $price_format, $currency_symbol, number_format( $cbxstatorder['order_discount'], 2, '.', ' ' ) ) . ' </td>
                            <td scope="col">' . sprintf( $price_format, $currency_symbol, number_format( $cbxstatorder['order_shipping'], 2, '.', ' ' ) ) . ' </td>
                            <td scope="col">' . sprintf( $price_format, $currency_symbol, number_format( $cbxstatorder['order_tax'], 2, '.', ' ' ) ) . ' </td>';

				$cbxprevdate = $i - 1;

				if ( array_key_exists( $cbxprevdate, $cbxstatorders ) )
				{
					$cbxprevmonthamount = $cbxstatorders[$cbxprevdate]['order_amount'];
				}

				if ( $cbxprevmonthamount > 0 )
				{
					$cbxcompare             = (int) $cbxstatorder['order_amount'] - (int) $cbxprevmonthamount;
					$cbxordercompare        = abs( $cbxcompare );
					$cbxordercomparepercent = ( ( (int) $cbxordercompare / (int) $cbxprevmonthamount ) * 100 );
					$cbxordercomparepercent = number_format( $cbxordercomparepercent, 2, '.', ' ' );
					$cbxordercomparehtml    = ( $cbxcompare > 0 ) ? '<span style = "color:green" ><span class="dashicons dashicons-arrow-up-alt"></span>' . '(' . $cbxordercomparepercent . ' %)</span>' : '<span style = "color:red"><span class="dashicons dashicons-arrow-down-alt"></span>' . '(' . $cbxordercomparepercent . ' %)</span>';
				}
				else
				{
					$cbxordercomparehtml = '';
				}
				$cbxstat .= ' <td scope="col">' . sprintf( $price_format, $currency_symbol, $cbxstatorder['order_amount'] ) . ' </td>
                          <td scope="col">' . $cbxordercomparehtml . ' </td>
                          </tr>';
				$countable_days ++;
				$total_orders = $total_orders + $cbxstatorder['order_number'];
				$total_items  = $total_items + $cbxstatorder['order_item_number'];
				$total_amount = $total_amount + $cbxstatorder['order_amount'];
			}
			$cbxstat .= '</tbody></table>';

			$avg_price = $total_amount / $countable_days;

			$cbxstat_footer = '<div class="postbox " id="dashboard_cbwooplainsalesstat_monthly">
								<h3 class="hndle ui-sortable-handle"><span>' . __( 'Month At a Glance', 'cbwooplainsalesstat' ) . '</span></h3>
								<div class="inside">
									<div class="activity-block">
										<ul>
				                            <li> <span class="dashicons dashicons-dashboard"></span> ' . sprintf( __( 'Month To Date Sales : %s', 'cbwooplainsalesstat' ), sprintf( $price_format, $currency_symbol, number_format( $total_amount, 2, '.', '' ) ) ) . '</li>
				                            <li> <span class="dashicons dashicons-share-alt"></span> ' . sprintf( __( 'Total Orders : %s', 'cbwooplainsalesstat' ), $total_orders ) . '</li>
				                            <li> <span class="dashicons dashicons-share-alt"></span> ' . sprintf( __( 'Total Items : %s', 'cbwooplainsalesstat' ), $total_items ) . '</li>
				                            <li> <span class="dashicons dashicons-chart-pie"></span> ' . sprintf( __( 'Average Sales/Day : %s', 'cbwooplainsalesstat' ), sprintf( $price_format, $currency_symbol, number_format( $avg_price, 2, '.', '' ) ) ) . '</li>
				                            <li> <span class="dashicons dashicons-chart-pie"></span> ' . sprintf( __( 'Avg Orders Per Day : %s', 'cbwooplainsalesstat' ), number_format( $total_orders / $countable_days, 2, '.', '' ) ) . '</li>
				                            <li> <span class="dashicons dashicons-chart-bar"></span> ' . sprintf( __( 'Forecasted Sales : %s', 'cbwooplainsalesstat' ), sprintf( $price_format, $currency_symbol, number_format( $avg_price * $days_of_this_month, 2, '.', '' ) ) ) . '</li>
				                        </ul>
				                    </div>
				                </div>
				           </div>';

			return array( $cbxstat_footer . $cbxstat, $cbxgraphdata );
		}


		/**
		 * export report data as csv, xls, xlsx, pdf
		 */
		public function cbwooplainsalesstat_export() {

			if ( isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == 'cbwooplainsalesstat' && isset( $_GET['export'] ) && $_GET['export'] == ! null )
			{

				$export_format = sanitize_text_field( $_GET['export'] );

				// check if PHPExcel already included or not
				if ( ! class_exists( 'PHPExcel' ) )
				{
					// check if PHPExcel exits in specified directory or not
					if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/PHPExcel.php' ) )
					{
						//Include PHPExcel
						require_once( plugin_dir_path( __FILE__ ) . 'includes/PHPExcel.php' );
					}
				}

				//Include Tcpdf if not exist
				/*if (!class_exists('TCPDF')) {
					if (file_exists(plugin_dir_path(__FILE__) . 'includes/tcpdf/tcpdf.php')) {
						require_once('includes/tcpdf/config/lang/eng.php'); //for pdf
						require_once('includes/tcpdf/tcpdf.php'); // for pdf
					}
				}*/

				if ( ! class_exists( 'TCPDF' ) && ( $export_format == 'pdf' ) )
				{
					//require_once('includes/tcpdf/config/lang/eng.php'); //for pdf
					//require_once('includes/tcpdf/tcpdf.php'); // for pdf

					echo __( 'Please install the <a href="https://wordpress.org/plugins/tcpdf/" target="_blank">TCPDF Library</a> plugin to export as pdf. Back to <a href="' . admin_url( 'admin.php?page=cbwooplainsalesstat' ) . '">CBX Woo Sales Report</a>.', 'cbwooplainsalesstat' );
					exit();
				}

				// monthly report export
				if ( isset( $_REQUEST['type'] ) && sanitize_text_field( $_GET['type'] ) == 'monthly' )
				{

					$cbxstat_getdate   = getdate();
					$cbxstat_orderyear = $cbxstat_getdate["year"];
					$cbxstat_ordermon  = $cbxstat_getdate["mon"];

					if ( isset( $_REQUEST['cbstatyear'] ) && $_REQUEST['cbstatyear'] != null && isset( $_REQUEST['cbstatmonth'] ) && $_REQUEST['cbstatmonth'] != null )
					{
						$cbxstat_orderyear = $_REQUEST['cbstatyear'];
						$cbxstat_ordermon  = $_REQUEST['cbstatmonth'];
					}

					$current_time    = current_time( 'timestamp' );
					$cbxstat_getdate = getdate( $current_time );
					//$currency_pos          = get_option('woocommerce_currency_pos');
					//$price_format          = get_woocommerce_price_format();
					//$currency_symbol       = get_woocommerce_currency_symbol();
					$currency = get_woocommerce_currency();

					$cbxstat_month_names   = array();
					$cbxstat_days_of_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

					$cbxstat_month_names[0]  = _x( 'Jan', 'January abbreviation' );
					$cbxstat_month_names[1]  = _x( 'Feb', 'February abbreviation' );
					$cbxstat_month_names[2]  = _x( 'Mar', 'March abbreviation' );
					$cbxstat_month_names[3]  = _x( 'Apr', 'April abbreviation' );
					$cbxstat_month_names[4]  = _x( 'May', 'May abbreviation' );
					$cbxstat_month_names[5]  = _x( 'Jun', 'June abbreviation' );
					$cbxstat_month_names[6]  = _x( 'Jul', 'July abbreviation' );
					$cbxstat_month_names[7]  = _x( 'Aug', 'August abbreviation' );
					$cbxstat_month_names[8]  = _x( 'Sep', 'September abbreviation' );
					$cbxstat_month_names[9]  = _x( 'Oct', 'October abbreviation' );
					$cbxstat_month_names[10] = _x( 'Nov', 'November abbreviation' );
					$cbxstat_month_names[11] = _x( 'Dec', 'December abbreviation' );

					$days_of_this_month = $cbxstat_days_of_month[$cbxstat_ordermon - 1];

					$today          = false;
					$countable_days = $total_orders = $total_items = $total_amount = $cbxprevmonthamount = 0;
					$cbxstatorders  = array();

					for ( $i = 1; $i <= $days_of_this_month; $i ++ )
					{
						$cbxstatorder      = self:: cbwooplainsalesstat_get_sale( $i, $cbxstat_ordermon, $cbxstat_orderyear );
						$cbxstatorders[$i] = $cbxstatorder;
					}

					// for pdf generation
					$cbx_export_html = '<p>' . __( 'CBX Woo Sales Report of Month ', 'cbwooplainsalesstat' ) . $cbxstat_month_names[$cbxstat_ordermon - 1] . ', ' . $cbxstat_orderyear . '</p>';
					$cbx_export_html .= '<table cellpadding="5" bgcolor="#f6f6f6">
                            <tr>
                              <td align="left">' . __( "Day", 'cbwooplainsalesstat' ) . ' </td>
                              <td align="right">' . __( "No. Orders", 'cbwooplainsalesstat' ) . ' </td>
                              <td align="right">' . __( "Total Items", 'cbwooplainsalesstat' ) . ' </td>
                              <td align="right">' . sprintf( __( "Discount (%s)", 'cbwooplainsalesstat' ), $currency ) . ' </td>
                              <td align="right">' . sprintf( __( "Shipping (%s)", 'cbwooplainsalesstat' ), $currency ) . ' </td>
                              <td align="right">' . sprintf( __( "Tax (%s)", 'cbwooplainsalesstat' ), $currency ) . ' </td>
                              <td align="right">' . sprintf( __( "Order Amount (%s)", 'cbwooplainsalesstat' ), $currency ) . ' </td>
                              <td align="right">' . __( "Progress (%)", 'cbwooplainsalesstat' ) . ' </td>
                            </tr>';

					$objPHPExcel = new PHPExcel();
					$objPHPExcel->setActiveSheetIndex( 0 );
					$objPHPExcel->getActiveSheet()->setCellValue( 'A1', __( 'Day', 'cbwooplainsalesstat' ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'B1', __( 'No. Orders', 'cbwooplainsalesstat' ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'C1', __( 'Total Items', 'cbwooplainsalesstat' ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'D1', sprintf( __( 'Discount (%s)', 'cbwooplainsalesstat' ), $currency ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'E1', sprintf( __( 'Shipping (%s)', 'cbwooplainsalesstat' ), $currency ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'F1', sprintf( __( 'Tax (%s)', 'cbwooplainsalesstat' ), $currency ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'G1', sprintf( __( 'Order Amount (%s)', 'cbwooplainsalesstat' ), $currency ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'H1', __( 'Progress (%)', 'cbwooplainsalesstat' ) );

					$objPHPExcel->getActiveSheet()->getStyle( 'A1:H1' )->getFont()->setBold( true );
					$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn( 'A:H' )->setAutoSize( true );
					for ( $i = $days_of_this_month; $i >= 1; $i -- )
					{

						if ( $i == $cbxstat_getdate['mday'] && $cbxstat_ordermon == $cbxstat_getdate['mon'] && $cbxstat_orderyear == $cbxstat_getdate['year'] )
						{
							$today = true;
						}
						else
						{
							$today = false;
						}

						if ( $cbxstat_ordermon == $cbxstat_getdate['mon'] && $cbxstat_orderyear == $cbxstat_getdate['year'] && $i > $cbxstat_getdate['mday'] )
						{
							continue;
						}

						$cbxstatorder    = $cbxstatorders[$i];
						$cbxstat_newdate = $cbxstat_orderyear . '-' . $cbxstat_ordermon . '-' . $i;
						$cbxstat_weekday = date( 'D', strtotime( $cbxstat_newdate ) ); // note: first arg to date() is lower-case L
						$objPHPExcel->getActiveSheet()->setCellValue( 'A' . ( $i + 1 ), $i . ' ( ' . __( $cbxstat_weekday ) . ' ) ' );
						$objPHPExcel->getActiveSheet()->setCellValue( 'B' . ( $i + 1 ), $cbxstatorder['order_number'] );
						$objPHPExcel->getActiveSheet()->setCellValue( 'C' . ( $i + 1 ), $cbxstatorder['order_item_number'] );
						$objPHPExcel->getActiveSheet()->setCellValue( 'D' . ( $i + 1 ), number_format( $cbxstatorder['order_discount'], 2, '.', ' ' ) );
						$objPHPExcel->getActiveSheet()->setCellValue( 'E' . ( $i + 1 ), number_format( $cbxstatorder['order_shipping'], 2, '.', ' ' ) );
						$objPHPExcel->getActiveSheet()->setCellValue( 'F' . ( $i + 1 ), number_format( $cbxstatorder['order_tax'], 2, '.', ' ' ) );

						$cbxprevdate = $i - 1;

						if ( array_key_exists( $cbxprevdate, $cbxstatorders ) )
						{
							$cbxprevmonthamount = $cbxstatorders[$cbxprevdate]['order_amount'];
						}

						if ( $cbxprevmonthamount > 0 )
						{
							$cbxcompare             = (int) $cbxstatorder['order_amount'] - (int) $cbxprevmonthamount;
							$cbxordercompare        = abs( $cbxcompare );
							$cbxordercomparepercent = ( ( (int) $cbxordercompare / (int) $cbxprevmonthamount ) * 100 );
							$cbxordercomparepercent = number_format( $cbxordercomparepercent, 2, '.', ' ' );
							$cbxordercomparehtml    = ( $cbxcompare > 0 ) ? '+ ' . $cbxordercomparepercent : '- ' . $cbxordercomparepercent;
						}
						else
						{
							$cbxordercomparehtml = '';
						}

						$countable_days ++;
						$total_orders = $total_orders + $cbxstatorder['order_number'];
						$total_items  = $total_items + $cbxstatorder['order_item_number'];
						$total_amount = $total_amount + $cbxstatorder['order_amount'];

						$cbx_export_html .= '<tr nobr="true">
                            <td align="left">' . $i . ' ( ' . __( $cbxstat_weekday ) . ' ) ' . '</td>
                            <td align="right">' . $cbxstatorder['order_number'] . '</td>
                            <td align="right">' . $cbxstatorder['order_item_number'] . '</td>
                            <td align="right">' . number_format( $cbxstatorder['order_discount'], 2, '.', ' ' ) . '</td>
                            <td align="right">' . number_format( $cbxstatorder['order_shipping'], 2, '.', ' ' ) . '</td>
                            <td align="right">' . number_format( $cbxstatorder['order_tax'], 2, '.', ' ' ) . '</td>
                            <td align="right">' . $cbxstatorder['order_amount'] . '</td>
                            <td align="right">' . $cbxordercomparehtml . '</td>
                          </tr>';

						$objPHPExcel->getActiveSheet()->setCellValue( 'G' . ( $i + 1 ), $cbxstatorder['order_amount'] );
						$objPHPExcel->getActiveSheet()->setCellValue( 'H' . ( $i + 1 ), $cbxordercomparehtml );
					}

					$excel_row = $countable_days + 2;
					$objPHPExcel->getActiveSheet()->setCellValue( 'A' . $excel_row, __( 'Total', 'cbwooplainsalesstat' ) );
					$objPHPExcel->getActiveSheet()->setCellValue( 'B' . $excel_row, '=SUM(B2:B' . ( $excel_row - 1 ) . ')' );
					$objPHPExcel->getActiveSheet()->setCellValue( 'C' . $excel_row, '=SUM(C2:C' . ( $excel_row - 1 ) . ')' );
					$objPHPExcel->getActiveSheet()->setCellValue( 'D' . $excel_row, '=SUM(D2:D' . ( $excel_row - 1 ) . ')' );
					$objPHPExcel->getActiveSheet()->setCellValue( 'E' . $excel_row, '=SUM(E2:E' . ( $excel_row - 1 ) . ')' );
					$objPHPExcel->getActiveSheet()->setCellValue( 'F' . $excel_row, '=SUM(F2:F' . ( $excel_row - 1 ) . ')' );
					$objPHPExcel->getActiveSheet()->setCellValue( 'G' . $excel_row, '=SUM(G2:G' . ( $excel_row - 1 ) . ')' );

					$filename = __( 'CBX-Woo-Sales-Report-of-Month-', 'cbwooplainsalesstat' ) . $cbxstat_month_names[$cbxstat_ordermon - 1] . ', ' . $cbxstat_orderyear;

					switch ( $export_format )
					{
						case 'csv':
							// Redirect output to a client’s web browser (csv)
							$filename = $filename . '.csv';
							header( "Content-type: text/csv" );
							header( "Cache-Control: no-store, no-cache" );
							header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
							$objWriter = new PHPExcel_Writer_CSV( $objPHPExcel );
							$objWriter->setDelimiter( ',' );
							$objWriter->setEnclosure( '"' );
							$objWriter->setLineEnding( "\r\n" );
							$objWriter->setSheetIndex( 0 );
							$objWriter->save( 'php://output' );
							break;
						case 'xls':
							// Redirect output to a client’s web browser (Excel5)
							$filename = $filename . '.xls';
							header( 'Content-Type: application/vnd.ms-excel' );
							header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
							header( 'Cache-Control: max-age=0' );
							$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel5' );
							$objWriter->save( 'php://output' );
							break;
						case 'xlsx':
							$filename = $filename . '.xlsx';
							// Redirect output to a client’s web browser (Excel2007)
							header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
							header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
							header( 'Cache-Control: max-age=0' );
							$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
							$objWriter->save( 'php://output' );
							break;
						case 'pdf':

							$pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, false );

							$user_info = get_userdata( get_current_user_id() );
							$user_name = $user_info->display_name;

							$title   = __( 'Sales Statement', 'cbwooplainsalesstat' );
							$subject = __( 'Sales Statement', 'cbwooplainsalesstat' );
							$keyword = __( 'Sales Statement', 'cbwooplainsalesstat' );
							$header  = __( 'Sales Statement ', 'cbwooplainsalesstat' );
							$pdf->SetCreator( $user_name );
							$pdf->SetAuthor( $user_name );
							$pdf->SetTitle( $title );
							$pdf->SetSubject( $subject );
							$pdf->SetKeywords( $keyword );

							$pdf->SetHeaderData( '', '', $header, '', array( 0, 0, 0 ), array( 0, 0, 0 ) );


							$pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', 20 ) );
							$pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

							$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

							$pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
							$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
							$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

							$pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );

							$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

							if ( @file_exists( dirname( __FILE__ ) . '/lang/eng.php' ) )
							{
								require_once( dirname( __FILE__ ) . '/lang/eng.php' );
								$pdf->setLanguageArray( $l );
							}


							$pdf->setFontSubsetting( true );

							$pdf->SetFont( 'dejavusans', '', 10, '', true );

							$pdf->AddPage();


							$html = <<<EOD
                                    $cbx_export_html

EOD;

							$pdf->writeHTMLCell( 0, 0, '', '', $html, 0, 1, 0, true, '', true );

							// Output pdf document
							$pdf->Output( $filename . '.pdf', 'D' );

							break;
					}
					exit();
				}//end monthly

			}
		}

		/* ---- Start Helper Methods ---- */

		/**
		 * @param $start
		 * @param $end
		 *
		 * @return array
		 */
		public static function getDatesFromRange( $startDate, $endDate ) {
			$return = array( $startDate );
			$start  = $startDate;

			$i = 1;
			if ( strtotime( $startDate ) < strtotime( $endDate ) )
			{
				while ( strtotime( $start ) < strtotime( $endDate ) )
				{
					$start    = date( 'd-m-Y', strtotime( $startDate . '+' . $i . ' days' ) );
					$return[] = $start;
					$i ++;
				}
			}

			return $return;
		}

		/* ---- End Helper Methods  ---- */


	}
