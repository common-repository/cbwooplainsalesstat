<?php
	/**
	 * Plugin Name:       CBX Woo Sales Stat
	 * Plugin URI:        http://codeboxr.com/product/woocommerce-plain-sales-report-for-wordpress
	 * Description:       Sales Stat For Woocommerce
	 * Version:           1.1.1
	 * Author:            Codeboxr Team
	 * Author URI:        http://codeboxr.com
	 * Text Domain:       cbwooplainsalesstat
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * Domain Path:       /languages
	 */
// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) )
	{
		die;
	}

	defined( 'CBWOOPAINSTAT_ROOT_PATH' ) or define( 'CBWOOPAINSTAT_ROOT_PATH', plugin_dir_path( __FILE__ ) );
	defined( 'CBWOOPAINSTAT_ROOT_URL' ) or define( 'CBWOOPAINSTAT_ROOT_URL', plugin_dir_url( __FILE__ ) );
	define( 'CBWOOPAINSTATNAME', esc_html__( 'CBX Woocommerce Sales Stat', 'cbwooplainsalesstat' ) );
	define( 'CBWOOPAINSTATSUFFIX', 'cbwooplainsalesstat' );
	define( 'CBWOOPAINSTATVERSION', '1.1.1' );

	require_once( plugin_dir_path( __FILE__ ) . 'public/class-cbwooplainsalesstat.php' );

	register_activation_hook( __FILE__, 'cbwooplainsalesstat_activate' );
	register_deactivation_hook( __FILE__, 'cbwooplainsalesstat_deactivate' );

	/**
	 * Fired when the plugin is activated.
	 * @since    1.0.0
	 *
	 */
	function cbwooplainsalesstat_activate() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( ! ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) )
		{
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( "This plugin requires WooCommerce to be installed and active." );
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 * @since    1.0.0
	 *
	 */
	function cbwooplainsalesstat_deactivate() {

	}

	/**
	 * Show wordpress error notice if Woocommerce not found (inspired from Dokan plugin)
	 *
	 * @since 2.3
	 *
	 */
	function add_woocommerce_activation_notice_cbwooplainsalesstat() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>CBX Woocommerce Sales Stat</strong> requires %sWoocommerce%s to be installed & activated!', 'cbwooplainsalesstat' ), '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">', '</a>' ) . '</p></div>';
	}

	if ( is_admin() )
	{
		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-cbwooplainsalesstat-admin.php' );

		add_action( 'plugins_loaded', array( 'CbwooplainsalesstatAdmin', 'get_instance' ) );
	}

	add_action( 'plugins_loaded', array( 'Cbwooplainsalesstat', 'get_instance' ) );
