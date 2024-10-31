<?php

function ossm_sendle_shipping_zone_method() {
    if ( ! class_exists( 'ossm_sendle_shipping_zone_method' ) ) {
        class ossm_sendle_shipping_zone_method extends WC_Shipping_Method {
            var $api_id,$api_key,$pickup_suburb,$pickup_postcode,$plan_name,$mode,$apiurl;

            public function __construct( $instance_id = 0 ) {
              $this->id                 = 'ossmsendle-zone';
              $this->instance_id 		    = absint( $instance_id );
              $this->method_title       = __( 'Sendle', 'joovii' );
              $this->method_description = __( 'Tested and approved by Sendle, this plugin provides the ultimate connectivity between WooCommerce and Sendle.', 'joovii' );
              $this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal');
              $this->init();
              $title = $this->get_option('title');
              $this->title = !empty( $title ) ? $title : __( 'Sendle Shipping', 'joovii' );

            }

            function init() {
                $this->init_settings();
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

      			public function calculate_shipping( $package = array() ) {

                  //ossm_logActions("<--------- calculate_shipping_zone  ----> ");
                  $weight = 0;
                  $volume	= 0;
                  global $woocommerce;
                  $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
                  if($sendle_setting['enabled'] != "yes"){ return ; }
                  if($sendle_setting['showrates'] != "yes"){ return ; }

                  $pickupCountry    = trim($sendle_setting["pickup_country"]);
                  $pickupSuburb 	  = trim($sendle_setting['pickup_suburb']);
                  $pickupPostcode   = trim($sendle_setting['pickup_postcode']);

                  if(empty($pickupSuburb ) || empty($pickupPostcode)){  return ; }

                  $deliveryCountry  = trim($package["destination"]["country"]);
                  if($pickupCountry == "US"){
                    if($deliveryCountry != "US"){
                        wc_add_notice(  __( 'Sendle does not support International Orders sent from the United States yet.', 'woocommerce' ) );
                        return;
                    }
                  }

                  $maxWeight = ossm_maxWeightLimit ($pickupCountry, trim($package["destination"]["country"]) );
                  $maxVolume = ossm_maxVolumeLimit ($pickupCountry, trim($package["destination"]["country"]) );

                  $items = $woocommerce->cart->get_cart();
                  $packageArr = array();
                  $cartTotalQuatity = 0;
                  $cartTotalweight = 0;
                  foreach ( $package['contents'] as $item_id => $values ) {
                  //foreach ( $items as $item_id => $values ) {
                      //ossm_logActions(" cart-p :".$item_id.": ". print_r($values,true) );
                      $_product = $values['data'];
                      $weightP = ossm_getWeight ($_product->get_weight(), $pickupCountry );
                      $weight = $weight + $weightP * $values['quantity'];
                      $cartTotalQuatity = $cartTotalQuatity + $values['quantity'];
                      if($_product->get_weight()>0){
                        $cartTotalweight = $cartTotalweight + ($_product->get_weight() * $values['quantity']);
                      }

                      ossm_logActions(" get_weight/weight [1] : ". $_product->get_weight() ."--". $weightP );
                      ossm_logActions(" quantity/weight [2] : ". $values['quantity'] );
                      ossm_logActions(" weight/weight [3] : ". $weight );

                      $volumeP = 0;
                      if(trim($sendle_setting['volume_param']) == 'yes'){

                        if($_product->get_length() >0 ){ $volumnP_l = ossm_getDimension($_product->get_length(),$pickupCountry);
                        }else{ $volumnP_l = 0; }

                        if($_product->get_width() >0 ){ $volumnP_w = ossm_getDimension($_product->get_width(),$pickupCountry);
                        }else{ $volumnP_w = 0; }

                        if($_product->get_height() >0 ){ $volumnP_h = ossm_getDimension($_product->get_height(),$pickupCountry);
                        }else{ $volumnP_h = 0; }

                        $volumeP = ( ( $volumnP_l * $volumnP_w * $volumnP_h ));
                        $volume += ($volumeP * $values['quantity']);
                        ossm_logActions(" volume-> :: ". $volume ." = ". $volumnP_l. "--". $volumnP_w."--".$volumnP_h );
                      }
                      ossm_logActions(" weightP/maxWeight [3-1] : ". $weightP ." =" . $maxWeight ."-". $weight );
                      if($weightP > 0){
                        if($weightP > $maxWeight){
                          if($sendle_setting['warningtext_enable'] == 'yes'){
                            wc_add_notice(  __( $sendle_setting['warningtext'], 'woocommerce' ) );
                          }
                          return;
                        }
                        if($volumeP >0){
                          if($volumeP > $maxVolume){
                            if($sendle_setting['warningtext_enable'] == 'yes'){
                              wc_add_notice(  __( $sendle_setting['warningtext'], 'woocommerce' ) );
                            }
                            return;
                          }
                        }
                        for($i=1;$i<=$values['quantity'];$i++){
                          $packageArr[] = array("w" => ossm_getWeight ($_product->get_weight(), $pickupCountry ),
                                                "v" => $volumeP);
                        }

                      }else{
                        if($sendle_setting['warningtext_enable'] == 'yes'){
                          wc_add_notice(  __( $sendle_setting['warningtext'], 'woocommerce' ) );
                        }
                        return;
                      }
                  }

                  ossm_logActions(" volume/weight [4] : ". $volume. "--". $weight );
                  ossm_logActions(" Initial cart item Arr : ". print_r($packageArr,true) );
                  ossm_logActions(" maxWeight : ". $maxWeight );
                  ossm_logActions(" maxVolume : ". $maxVolume );
                  $packageDivisionArr = ossm_weightDistributionArray ($packageArr, $weight,$volume, $maxWeight, $maxVolume );
                  ossm_logActions(" Final package Division Arr : ". print_r($packageDivisionArr,true) );

                  if($weight > $maxWeight || $volume > $maxVolume){

                        if($weight > $maxWeight){
                          ossm_logActions(" cart weight > sendle max weight : ". $weight. ">". $maxWeight );
                        }
                        if($volume > $maxVolume){
                          ossm_logActions(" cart weight > sendle max weight : ". $volume. ">". $maxVolume );
                        }
                        $grossAmount =0;
                        $netAmount =0;
                        $taxAmount =0;
                        $volume = 0;
                        foreach($packageDivisionArr as $krw => $vrw){

                          $weight = $vrw['w'];
                          $volume = $vrw['v'];

                          if($volume > $maxVolume ) { $volume= 0; }
                          $urlParam = ossm_createRequestStr ($package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, 'no' );
                          ossm_logActions(" url [".($krw + 1)."] :: ". $urlParam. "  ");

                          $resultBuffer = ossm_calculateSendleRate ($package, $sendle_setting, $urlParam );
                          ossm_logActions(" rate-result [".($krw + 1)."] :: ". print_r($resultBuffer,true). "  ");
                          if($resultBuffer[0]['quote']['gross']['amount'] > 0){

                            $grossAmount = $grossAmount + $resultBuffer[0]['quote']['gross']['amount'];
                            $netAmount = $netAmount + $resultBuffer[0]['quote']['net']['amount'];
                            $taxAmount = $taxAmount + $resultBuffer[0]['quote']['tax']['amount'];

                          }else{

                            if($sendle_setting['warningtext_enable'] == 'yes'){
                              wc_add_notice(  __( $sendle_setting['warningtext'], 'woocommerce' ) );
                            }
                            return;

                          }


                        }
                        $result = array( array('quote'=> array('gross' => array('amount'=>$grossAmount),
                                                'net' => array('amount'=>$netAmount),
                                                'tax' => array('amount'=>$taxAmount)),
                                                'plan_name'=> 'Easy'
                                              )
                                       );

                  }else{

                        $urlParam = ossm_createRequestStr ($package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, 'yes' );
                        ossm_logActions(" url  :: ". $urlParam. "  ");
                        $result = ossm_calculateSendleRate ($package, $sendle_setting, $urlParam );

                  }

                  ossm_logActions(" resultArray ---> : ". print_r($result,true) );

                  $rate = ossm_createRateArray ($package, $sendle_setting, $result );
                  if(!is_array($result)){ return; } else {
                    if($result[0]['quote']['gross']['amount'] > 0) { $this->add_rate( $rate ); } else{ return; }
                  }

                  // Beverly Hills 90210

      			}

        }
    }
}

add_action( 'woocommerce_shipping_init', 'ossm_sendle_shipping_zone_method' );

function ossm_add_sendle_shipping_zone_method( $methods ) {
    $methods["ossmsendle-zone"] = 'ossm_sendle_shipping_zone_method';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'ossm_add_sendle_shipping_zone_method' );
add_filter( 'woocommerce_shipping_calculator_enable_city','__return_true'  );

add_action( 'in_admin_footer', 'ossm_my_sendle' );
add_action( 'wp_enqueue_scripts', 'theme_enqueue_scripts' );
add_action( 'wp_footer', 'theme_autocomplete_js' );
add_action ( 'wp_ajax_nopriv_' . 'sendlejooviicityziplookup', 'ossm_getcityziplookup' );
add_action ( 'wp_ajax_' . 'sendlejooviicityziplookup', 'ossm_getcityziplookup' );


?>
