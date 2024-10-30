<?php
	if ( ! defined( 'ABSPATH' ) )
	{
		exit;
	}

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	$grab_pro = '';

	if ( ! in_array( 'cbwooplainsalesstataddon/cbwooplainsalesstataddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
	{
		$grab_pro = sprintf( '  <a class="button" href="%s" target="_blank">%s</a>', 'http://codeboxr.com/product/woocommerce-plain-sales-report-for-wordpress#downloadarea', __( 'Grab Pro', 'cbwooplainsalesstat' ) );
	}
?>
<div class="wrap columns-2">
    <h2><?php echo esc_html( get_admin_page_title() ) . $grab_pro; ?></h2>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content" style="position: relative;">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox">
                        <div class="inside">
							<?php
								$this->cbwooplainsalesstat_settings_api->show_navigation();
								$this->cbwooplainsalesstat_settings_api->show_forms();
							?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="postbox-container-1" class="postbox-container-1">
				<?php require_once( plugin_dir_path( __FILE__ ) . '/cb-sidebar.php' ); ?>
            </div>
        </div>
    </div>
</div>
