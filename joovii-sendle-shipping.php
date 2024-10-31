<?php
/**
 * Plugin Name: Sendle Shipping Plugin
 * Plugin URI: http://joovii.com/shipping-method/sendle-wp.html
 * Description: Tested and approved by Sendle, this plugin provides the basic connectivity between WooCommerce and Sendle. For the ultimate connectivity and features, please install the Premium Plugin.
 * Version: 6.01
 * Author: Joovii
 * Author URI: http://joovii.com/installation-instruction/wp
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: Joovii
 * WC requires at least: 3.0
 * WC tested up to: 7.5.1
 */

//if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { return false; }
//error_reporting(E_ALL);
define( 'SENDLE_JOOVII_API_SANDBOX_URL', 'https://sendle-sandbox.herokuapp.com' );
define( 'SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT', '25' );
define( 'SENDLE_JOOVII_AU_MAX_INTERNATION_WEIGHT', '20' );
define( 'SENDLE_JOOVII_US_MAX_DOMESTIC_WEIGHT', '70' );
define( 'SENDLE_JOOVII_CA_MAX_DOMESTIC_WEIGHT', '25' );

define( 'SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN', '0.100001' );
define( 'SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN', '0.125' );
define( 'SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN', '864' );
define( 'SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN', '0.125' );
define( 'SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION', '6.01' );

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'joovii_action_links' );
function joovii_action_links( $actions ) {
	$actions[] = '<a style="color:green;font-weight:bold;" target="_blank" href="https://joovii.com/woocommerce-plugin/wordpress-sendle-premium-plugin">Get Sendle Pro</a>';
	return $actions;
}

function ossm_sendle_create_logs_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . "sendlelogs";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `eventname` varchar(50) CHARACTER SET utf8 NOT NULL,
            `orderid` int(11) NOT NULL,
            `logs` text CHARACTER SET utf8 NOT NULL,
            `timestamp` varchar(50) CHARACTER SET utf8 NOT NULL,
            PRIMARY KEY (`id`)
          )".$charset_collate."; ";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'ossm_sendle_create_logs_table');

add_action('admin_menu', 'ossm_sendle_settings');
function ossm_sendle_settings(){
	add_submenu_page('woocommerce','Sendle Settings', 'Sendle Settings',  ossm_getAssignRole(),'admin.php?page=wc-settings&tab=shipping&section=ossmsendle',null,2);
}
add_action('admin_menu', 'ossm_validate_sendle_menu');
function ossm_validate_sendle_menu(){
	add_submenu_page('woocommerce', 'Validate Sendle', 'Validate Sendle',  ossm_getAssignRole(), 'ossm_validate_sendle','ossm_validate_sendle',3);
}
add_action('admin_menu', 'ossm_sendle_trackingemailtemplate_menu');
function ossm_sendle_trackingemailtemplate_menu(){
	add_submenu_page('woocommerce', 'Sendle Email Template', 'Sendle Email Template',  ossm_getAssignRole(), 'ossm_sendle_trackingemailtemplate','ossm_sendle_trackingemailtemplate',3);
}

add_action('admin_menu','ossm_sendle_dashboard');
function ossm_sendle_dashboard() {

	 add_submenu_page('','Track Shipment','Track Shipment', ossm_getAssignRole(),'track-shipment','ossm_track_shipment',1);
	 add_submenu_page('','Download Shipping Label','Download Shipping Label', ossm_getAssignRole(),'download-shipping-label','ossm_download_shipping_label',1);
	 add_submenu_page('','Cancel Sendle Order','Cancel Sendle Order', ossm_getAssignRole(),'cancel-sendle','ossm_cancel_sendle',1);
   add_submenu_page('','View Sendle Order Details','View Sendle Order Details', ossm_getAssignRole(),'viewdetails-sendle','ossm_create_shipment',1);
	 add_submenu_page('','Create Sendle Shipment','Create Sendle Shipment', ossm_getAssignRole(),'create-shipment','ossm_create_shipment',1 );
}

require_once("sendle-shipping-function.php");
require_once("sendle-shipping-zone.php");
require_once("sendle-shipping-global.php");
require_once("sendle-shipment-booking.php");
require_once("sendle-admin-feature.php");
require_once("track-shipment.php");
require_once("download-label.php");
require_once("frontend-tracking.php");
require_once("cancel-shipment.php");
require_once('sendle-logs.php');
require_once('validate-sendle.php');
require_once('sendle-tracking-email.php');
require_once("sendle-widget.php");
require_once("cityziplookup.php");

function ossm_sendle_shipping_method() {
  $assign_permission=ossm_getAssignPermission();
	if ( ! class_exists( 'ossm_sendle_shipping_method' )  && $assign_permission ) {
        class ossm_sendle_shipping_method extends WC_Shipping_Method {
            var $api_id,$api_key,$pickup_suburb,$pickup_postcode,$mode,$apiurl;
            public function __construct() {

              $this->id                  = 'ossmsendle';
              $this->method_title        = __( 'Sendle', 'joovii' );
              $this->method_description  = __( 'Tested and approved by Sendle, this plugin provides the <b><u>basic</u></b> connectivity between WooCommerce and Sendle.<br>For the <b><u>ultimate</u></b> connectivity and features, please install the  <a style="color:green;font-weight:bold;" target="_blank" href="https://joovii.com/woocommerce-plugin/wordpress-sendle-premium-plugin">Premium Plugin</a>.', 'joovii' );
              $this->availability 		   = 'sendle_wp';
              $this->init();
              $this->enabled             = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
              $this->title               = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Sendle Shipping', 'joovii' );

            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }
            function generate_settings_html( $form_fields = array(), $echo = true ) {
               		if ( empty( $form_fields ) ) {
               			$form_fields = $this->get_form_fields();
               		}

               		$html = '';
               		foreach ( $form_fields as $k => $v ) {
               			$type = $this->get_field_type( $v );

                     if($type == 'ossm_customplaceholder') {
                       $html .= $this->ossm_generate_text_html_custom( $k, $v );
                     }else{
                       if ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
                 				$html .= $this->{'generate_' . $type . '_html'}( $k, $v );
                 			} else {
                 				$html .= $this->generate_text_html( $k, $v );
                 			}
                     }
               		}

               		if ( $echo ) {
               			echo $html ; // WPCS: XSS ok.
               		} else {
               			return $html ;
               		}
           	}

             function ossm_generate_text_html_custom ( $key, $data ) {
               $defaults = array();
               $data = wp_parse_args( $data, $defaults );
               ob_start();
               ?>
                 <tr valign="top">
             			<td class="forminp" colspan="2">
             				--------------------------- <?php echo wp_kses_post( $data['title'] ); ?> <?php //echo wp_kses_post( $data['description'] ); ?> ----------------------------------------------
             			</td>
             		</tr>
             	<?php
               return ob_get_clean();
             }

             function init_form_fields() {

                       $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
                       $optionArray = array(
                           'enabled' => array(
                               'title'       => 	__( 'Enable', 'joovii' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'Enable this shipping method.', 'joovii' ),
                               'default'     => 	'yes'
                           ),

                           'title' => array(
                               'title'       => 	__( 'Title', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Title to be display on site', 'joovii' ),
                               'default'     => 	__( 'Sendle', 'joovii' )
                           ),
                           'showrates' => array(
                               'title'       => 	__( 'Show the rates in frontend', 'joovii' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'If not then the rates will not show in frontend but backend process will work for other shipping method', 'joovii' ),
                               'default' 	  => 	'yes'
                           ),

                           'api_id' => array(
                               'title'       => 	__( 'Sendle ID', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( '<a href="admin.php?page=ossm_validate_sendle">Validate Your Sendle ID</a>', 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'api_key' => array(
                               'title'       => 	__( 'API Key', 'joovii' ),
                               'type'        => 	'password',
                               'description' => 	__( 'Do not know what is your API key ? <a target="_blank" href="https://support.sendle.com/hc/en-us/articles/210798518-Sendle-API">Click Here</a>', 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'mode' => array(
                               'title'       => 	__( 'Mode', 'joovii' ),
                               'type'        => 	'select',
                               'description' => 	__( '', 'joovii' ),
                               'default'     => 	'live',
                               'options'     => 	array("sandbox"=>"Sandbox","live"=>"Live"),
                           ),
                           'pickup_suburb' => array(
                               'title'       => 	__( 'Pickup Suburb', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Suburb must be real and match pickup postcode.', 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'pickup_postcode'  => array(
                               'title'       => 	__( 'Pickup Postcode', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Four-digit post code for the pickup address.', 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'pickup_country' => array(
                               'title'       => 	__( 'Pickup Country', 'joovii' ),
                               'type'        => 	'select',
                               'description' => 	__( 'Pickup Country', 'joovii' ),
                               'default'     => 	'AU',
                               'options'     => 	array("AU"=>"Australia","US"=>"United States","CA"=>"Canada",""=>"Please Select"),
                           ),
                           'quote_markup' => array(
                               'title'       => 	__( 'Shipping quote markup %', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( "Shipping quote markup %", 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'shipping_handling_fee' => array(
                               'title'       => 	__( 'Additional Handling Fee Applied', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( "Additional Handling Fee Applied", 'joovii' ),
                               'default'     => 	__( '', 'joovii' )
                           ),
                           'enable_addressmatch' => array(
                               'title'       => 	__( 'Enable Address Match', 'joovii' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'Enable Address Match', 'joovii' ),
                               'default'     => 	__( 'yes', 'joovii' )
                           ),
                           'volume_param' => array(
                           	'title'         =>	__('Enable Volume Calcultion', 'joovii'),
                           	'type'          =>	'checkbox',
                           	'description'   =>	__('Enable Volume Calcultion for quote or book shipment. Please set proper volume unit in woocommerce for your country first. ', 'joovii'),
                           	'default'       =>	__('no')
                           ),

                           'warningtext_enable' => array(
                             'title'         =>	__('Enable warning text', 'joovii'),
                             'type'          =>	'checkbox',
                             'description'   =>	__('Enable warning text if product  weight/volume >  sendle max weight/volume', 'joovii'),
                             'default'       =>	__('no')
                           ),
                           'warningtext' => array(
                               'title'       => 	__( 'Warning Text for  sendle max weight/volume', 'joovii' ),
                               'type'        => 	'text',
                               'description' => 	__( "Warning Text if product weight/volume is greater than  sendle max weight/volume", 'joovii' ),
                               'default'     => 	__( 'One of the product of the cart weight/volume is greater than the sendle max weight/volume.', 'joovii' )
                           ),

                       );

                        // ----------------Satchel config_line---------------------------------------------------
                       $satchel_manager_seperator_array =   array(
                         'satchelconfig_line' => array(
                             'title'       => 	__( 'Satchel Configuration [For Australian Domestic parcels only]', 'joovii' ),
                             'type'        => 	'ossm_customplaceholder',
                             'description' => 	__( '[Do it here] ', 'joovii' )
                         ),
                         'satchel_booking' => array(
                            'title'         =>	__('Enable Satchel', 'joovii'),
                            'type'          =>	'checkbox',
                            'description'   =>	__('Enable Satchel for Booking or Quotation [For Australian Domestic parcels only]', 'joovii'),
                            'default'       =>	__('no')
                         ),
                       ) ;
                       $optionArray = array_merge($optionArray, $satchel_manager_seperator_array);
                       $satchel_manager_array =   array(
                         'satchel_mode' => array(
                             'title'       => 	__( 'Satchel Mode', 'joovii' ),
                             'type'        => 	'select',
                             'description' => 	__( 'Enable Satchel for Booking/Quotation/Both ', 'joovii' ),
                             'default'     => 	'none',
                             'options'     => 	array("none"=>"Please Select","both"=>"Enable Satchel for Both Booking and Quotation","booking"=>"Enable Satchel for Booking only", "quotation"=>"Enable Satchel for Quotation only"),
                         ),
                         'satchel_threshold_weight' => array(
                             'title'       => 	__( 'Satchel Threshold Weight', 'joovii' ),
                             'type'        => 	'text',
                             'description' => 	__( "Satchel Threshold Weight(In Grams)", 'joovii' ),
                             'default'     => 	__( '500', 'joovii' )
                         ),
                         'satchel_threshold_qty' => array(
                             'title'       => 	__( 'Satchel Threshold Quantity', 'joovii' ),
                             'type'        => 	'text',
                             'description' => 	__( "Satchel Threshold Quantity", 'joovii' ),
                             'default'     => 	__( '0', 'joovii' )
                         ),
                         'satchel_booking_adminlink' => array(
                            'title'         =>	__('Enable Satchel  Booking Link in Admin ', 'joovii'),
                            'type'          =>	'checkbox',
                            'description'   =>	__('Enable a Satchel Booking link for every order in admin', 'joovii'),
                            'default'       =>	__('no')
                         ),
                     ) ;

                     //if($sendle_setting['satchel_booking'] == 'yes'){
                       $optionArray = array_merge($optionArray, $satchel_manager_array);
                     //}

                     // ----------------orderconfig_line---------------------------------------------------
                     $order_manager_seperator_array =   array(
                       'orderconfig_line' => array(
                           'title'       => 	__( 'Order Synchronization Configuration', 'joovii' ),
                           'type'        => 	'ossm_customplaceholder',
                           'description' => 	__( '[Do it here] ', 'joovii' )
                       ),
                       /*'orderconfig_lineEnable' => array(
                          'title'         =>	__('Enable Order Synchronization', 'joovii'),
                          'type'          =>	'checkbox',
                          'description'   =>	__('Enable Order Synchronization', 'joovii'),
                          'default'       =>	__('no')
                       ),*/
                     ) ;
                     $optionArray = array_merge($optionArray, $order_manager_seperator_array);
                     $order_manager_array =   array(
                       'pickupoption' => array(
                        'title'         =>	__('Pickup Option', 'joovii'),
                        'type'          =>	'select',
                        'description'   =>	__('Use pickup to get your parcel picked up or drop off to drop it off at the nearest drop off location.', 'joovii'),
                        'default'       =>	__('pickup'),
                        'options'       =>	array("pickup"=>"Pick up from merchant address (below)","drop off"=>"Drop it off at the nearest drop off location. ")
                       ),

                       'process_as_sendle_order' => array(
                           'title'       => 	__( 'Post to Sendle API for the selected shipping method', 'joovii' ),
                           'type'        => 	'multiselect',
                           'description' => 	__( 'Process As Sendle Order if any of the shipping method are selected.', 'joovii' ),
                           'default'     => 	'None',
                           'options'     => 	array("None"=>"Sendle","flat_rate"=>"Flat Rate","free_rate"=>"Free Shipping","any_method"=>"Any method selected by the customer"),
                       ),
                       'book_shipment_on' => array(
                         'title'         =>	__('Book Shipment on', 'joovii'),
                         'type'          =>	'select',
                         'description'   =>	__('Select when the shipment will be created.', 'joovii'),
                         'default'       =>	__('order_submit', 'joovii'),
                         'options'       =>	array("order_submit"=>"Order Submit","shipment_submit"=>"Shipment Submit from admin")
                       ),
                       'sender_name' => array(
                           'title'       => 	__( 'Sender Name', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Sender Name", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),
                       'sender_contact_number' => array(
                           'title'       => 	__( 'Sender contact number', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Used to coordinate pickup if the courier is outside attempting delivery.Must be a valid phone number. 13, 1300, and 1800 numbers are not allowed.", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),
                       'sender_address' => array(
                           'title'       => 	__( 'Sender address', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "The street address where the parcel will be picked up.", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),
                       'sender_state' => array(
                           'title'       => 	__( 'Sender State', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Must be the pickup location’s state or territory.", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),
                       'sender_instruction' => array(
                           'title'       => 	__( 'Sender Pickup instructions', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Short message used as pickup instructions for courier. It must be under 255 chars, but is recommended to be under 40 chars due to label-size limitations.", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),
                       'receiver_instruction' => array(
                           'title'       => 	__( 'Receiver instructions', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Short message used as delivery instructions for courier. It must be under 255 chars, but is recommended to be under 40 chars due to label-size limitations.", 'joovii' ),
                           'default'     => 	__( '', 'joovii' )
                       ),

                       'pickup_delay' => array(
                           'title'       => 	__( 'Select pickup days delay', 'joovii' ),
                           'type'        => 	'text',
                           'description' => 	__( "Normal pickup date is the next business day. Should be a number greater than 0.", 'joovii' ),
                           'default'     => 	__( '1', 'joovii' )
                       ),
                       /*'label_size' => array(
                           'title'       => 	__( 'Download Label Size', 'joovii' ),
                           'type'        => 	'select',
                           'description' => 	__( "Download Label Size", 'joovii' ),
                           'default'     => 	__( '', 'joovii' ),
                           'options'     => 	array("a4"=>"Default","a4"=>"A4","cropped"=>"Cropped"),
                       ),*/

                       'change_order_status' => array(
                           'title'       => 	__( 'Change Order Status', 'joovii' ),
                           'type'        => 	'select',
                           'description' => 	__( 'Change Order Status to Processing/Completed after shipment booking', 'joovii' ),
                           'default'     => 	'no',
                           'options'     => 	array("processing"=>"Processing","completed"=>"Completed","no"=>"No"),
                       ),

                       'tracking_email' => array(
                        'title'         =>	__('Send Tracking Email', 'joovii'),
                        'type'          =>	'checkbox',
                        'description'   =>	__('Send Tracking Email. <a href="admin.php?page=ossm_sendle_trackingemailtemplate">Edit Tracking Email Template.</a> ', 'joovii'),
                        'default'       =>	__('no')
                       ),

                       'hs_code' => array(
                        'title'         =>	__('HS Code', 'joovii'),
                        'type'          =>	'text',
                        'description'   =>	__('A Harmonized System code for this item, appropriate for the destination country for international shipping. Including a HS code speeds up customs processing. Single HS tarrif code only. Must contain 6–10 digits with separating dots.', 'joovii'),
                        'default'     => 	__( '', 'joovii' )
                       ),
                       'hs_code_field_name' => array(
                         'title'         =>	__('HS Code Field Name', 'joovii'),
                         'type'          =>	'text',
                         'description'   =>	__('Enter Harmonized System Code Field Name.', 'joovii'),
                         'default'     => 	__( '', 'joovii' )
                        ),
                   ) ;
                   //if($sendle_setting['orderconfig_lineEnable'] == 'yes'){
                     $optionArray = array_merge($optionArray, $order_manager_array);
                   //}

                   // ----------------devconfig_line---------------------------------------------------
                   $devconfig_manager_array =   array(
                     'devconfig_line' => array(
                         'title'       => 	__( 'Developer Configuration', 'joovii' ),
                         'type'        => 	'ossm_customplaceholder',
                         'description' => 	__( '[Do it here] ', 'joovii' )
                     ),
                     'optintojoovii' => array(
                      'title'         =>	__('Allow access to Sendle and Joovii API\'s.', 'joovii'),
                      'type'          =>	'checkbox',
                      'description'   =>	__('This is required to allow live shipping quoting and booking and for support from Joovii.   The plugin will be disabled without this access approved. ', 'joovii'),
                      'default'       =>	__('yes')
                     ),
                     'enable_log' => array(
                         'title'       => 	__( 'Enable Log', 'joovii' ),
                         'type'        => 	'checkbox',
                         'description' => 	__( '<a href="admin.php?page=sendle_logs">Go to Sendle Log to view logs</a>', 'joovii' ),
                         'default'     => 	__( 'no', 'joovii' )
                     ),
                     'enable_customer_reference' => array(
                         'title'       => 	__( 'Enable Customer Reference', 'joovii' ),
                         'type'        => 	'checkbox',
                         'description' => 	__( 'This will apply a filter ossm_filter_customer_reference to add customer_reference to the sendle order. By default orderId will be added to the customer_reference.', 'joovii' ),
                         'default'     => 	__( 'no', 'joovii' )
                     )
                 ) ;
                 $optionArray = array_merge($optionArray, $devconfig_manager_array);

                 $role_manager_array =   array('role_manager' => array(
                     'title'       => __( 'User Role Manager', 'joovii' ),
                     'type'        => 'select',
                     'description' => __( 'Which user role can access this plugin', 'joovii' ),
                     'default'     => 'None',
                     'options'     => array('None'=>'None',
                                            "shop_manager"=>"Shop manager",
                                            //"author"=>"Author",
                                            //"editor"=>"Editor"
                                          ),
                 )) ;
                 if(ossm_getAssignRole() == 'administrator'){
                   $optionArray = array_merge($optionArray, $role_manager_array);
                 }

                 $this->form_fields = $optionArray;
            }

            public function calculate_shipping( $package = array() ) {

                //$result = ossm_calculateSendleRate($package );
                //if(!is_array($result)){ reurn; }else{ $this->add_rate( $result ); }
                $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
                //ossm_checkSendleZone($sendle_setting);


          }
        }
    }
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'ossm_add_plugin_page_settings_link');
function ossm_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=ossmsendle' ) . '">' . __('Settings') . '</a>';
	return $links;
}
add_action( 'woocommerce_shipping_init', 'ossm_sendle_shipping_method' );

function ossm_add_sendle_shipping_method( $methods ) {
  $methods[] = 'ossm_sendle_shipping_method';
  return $methods;
}



add_filter( 'woocommerce_shipping_methods', 'ossm_add_sendle_shipping_method' );
add_filter( 'woocommerce_shipping_calculator_enable_city','__return_true'  );

/* function ossm_other_shipping_method($rates, $package){
    $shipping_type = 'flat_';
    foreach( $rates AS $id => $data )  {
      // if the rate id starts with "flat_", remove it
      if ( 0 === stripos( $id, $shipping_type ) ) {
        unset( $rates[ $id ] );
      }
    }
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'ossm_other_shipping_method', 10, 2);*/

function ossm_my_sendle_pickup_country($hook) {
	$srt = '<script type="text/javascript">
  if ( jQuery(\'#woocommerce_ossmsendle_api_id\').val() != \'\' ) {
  	if ( jQuery(\'#woocommerce_ossmsendle_pickup_country\').val() == \'\' ) {
  		alert(\'Please Select Pickup Country.\');
      jQuery(\'#woocommerce_ossmsendle_pickup_country\').css(\'border-color\', \'red\');
      jQuery(\'#woocommerce_ossmsendle_pickup_country\').css(\'border-width\', \'thick\');
  	}
  }
	</script> ';
  if(isset($_GET['section'])){
    if(trim($_GET['section']) == 'ossmsendle'){
	   echo $srt;
    }
  }
}
add_action( 'in_admin_footer', 'ossm_my_sendle_pickup_country' );

?>
