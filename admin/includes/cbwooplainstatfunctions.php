<?php
	if ( ! defined( 'WPINC' ) )
	{
		die;
	}

	/**
	 * class CbwooplainsalesstatFunctions
	 * draw graph for result page and after page refresh
	 */
	class CbwooplainsalesstatFunctions {

		public static function cbwooplainsalesstat_draw_graph_common( $content_graph, $setting, $cbx_chart ) {
			$cbxcharthtml = '';
			$cbxsettings  = $cbx_chart;

			if ( $cbxsettings['enable'] == 1 ):
				$cbxcharthtml .= '<h3 id="cbxsalesreportcharttitle">' . __( 'Report in Chart', 'cbwooplainsalesstat' ) . '</h3>';

				foreach ( $cbxsettings['types'] as $index => $type )
				{
					$cbxcharthtml .= '<input type = "radio" ' . checked( 'line', $index, false ) . ' class = "cbxsalesreportchart"  name = "cbxsalesreportchart" value="' . $index . '" /><span class = "cbchartlabel" >' . ucfirst( $type ) . '</span>';
				}
				$cbxcharthtml .= '<p></p><div style="width:100%;height:600px;" id="cbxsalesreportchart"></div>';
				$cbxcharthtml .= '<div id="cbxsalesreportchartpie" style="width:100%;height:600px;display:none;"><div id="cbxsalesreportpie_order_amount" style="width:33%; float:left;"></div><div id="cbxsalesreportpie_order_number" style="width:33%;float:left;"></div><div id="cbxsalesreportpie_order_item_number" style="width:33%;float:left;"></div></div>';

				echo $cbxcharthtml;
				?>

                <script type="text/javascript">

                    jQuery(document).ready(function ($) {

                        //gather the data
                        var cbxdata = '<?php echo json_encode( $content_graph ); ?>';
                        cbxdata     = jQuery.parseJSON(cbxdata);

                        var order_amount_data      = {};
                        var order_number_data      = {};
                        var order_item_number_data = {};

                        $(cbxdata.label).each(function (index) {
                            order_amount_data[cbxdata.legend[index]] = cbxdata.order_amount[index];
                        });

                        $(cbxdata.label).each(function (index) {
                            order_number_data[cbxdata.legend[index]] = cbxdata.order_number[index];
                        });

                        $(cbxdata.label).each(function (index) {
                            order_item_number_data[cbxdata.legend[index]] = cbxdata.order_item_number[index];
                        });

                        // By default showing line chart
                        new Chartkick.LineChart(
                            "cbxsalesreportchart",
                            [
                                {"name": cbwooplainsalesstat.order_amount, "data": order_amount_data},
                                {"name": cbwooplainsalesstat.order_number, "data": order_number_data},
                                {"name": cbwooplainsalesstat.order_item_number, "data": order_item_number_data}
                            ],
                            {"discrete": true, "colors": ["#4285F4", "#db4437", "#b400f4"]}
                        );
                        //pro addon implementation on-click show relevant chart
						<?php do_action( 'cbwooplainslaes_onchange_charttype' ); ?>

                    });

                </script>
				<?php
			endif;
		}

		/**
		 * if tcpdf not active, then disabled class will added
		 * @return string
		 */
		public static function pdfExportBtnDisable() {
			return ( ! class_exists( 'TCPDF' ) ) ? 'disabled' : '';
		}

		public static function isTCPDFInstalled(){
			return ( class_exists( 'TCPDF' ) ) ? true : false;
        }
	}
