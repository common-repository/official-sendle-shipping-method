<?php

function ossm_validate_input_text($input_text){
  
	$input_text = sanitize_text_field($input_text);
	$input_text = esc_html($input_text);
	$input_text = esc_js($input_text);
	return $input_text;
}

function ossm_getAssignRole(){
    $c_id = get_current_user_id();
    $assign_role = '';
    $user = new WP_User($c_id);
    if(isset($user->roles[0])){
      $u_role =  $user->roles[0];
      if($u_role == 'administrator') { $assign_role = $u_role; }else{
        $sendle_setting = maybe_unserialize(get_option('woocommerce_ossmsendle_settings'));
        $role_manager_settings = $sendle_setting['role_manager'];
        if ($u_role == $role_manager_settings){
          $assign_role = $u_role;
        }
      }
    }
    return $assign_role;
}
function ossm_getAssignPermission(){
    $c_id = get_current_user_id();
    $assign_permission = '0';
    $user = new WP_User($c_id);
    if(isset($user->roles[0])){
      $u_role =  $user->roles[0];
      if($u_role == 'administrator') { $assign_permission = '1'; }else{
        $sendle_setting = maybe_unserialize(get_option('woocommerce_ossmsendle_settings'));
        $role_manager_settings = $sendle_setting['role_manager'];
        if ($u_role == $role_manager_settings){
          $assign_permission = '1';
        }
      }
    }
    return $assign_permission;
}

function ossm_getWeightStrForQuote ($weight, $pickupCountry ){
    $wpWeightUnit = get_option('woocommerce_weight_unit');
    $sendleWeightUnit = 'kg';
    if($pickupCountry == 'AU') { $sendleWeightUnit = 'kg';}
    if($pickupCountry == 'US') { $sendleWeightUnit = 'lb';}
    if($pickupCountry == 'CA') { $sendleWeightUnit = 'kg';}

    //$finalWeight= wc_get_weight( $weight, $sendleWeightUnit, $wpWeightUnit );
    //ossm_logActions(" finalWeight :: ". $weight ."-". $sendleWeightUnit ."-". $wpWeightUnit. "  ".$finalWeight);
    return "weight_value=".$weight."&weight_units=".$sendleWeightUnit."&";
}
function ossm_getWeightStrForOrder ($weight, $pickupCountry, $receiver_country, $satchel_booking_click='normal' ){
    $wpWeightUnit = get_option('woocommerce_weight_unit');
    $sendleWeightUnit = 'kg';
    if($pickupCountry == 'AU') { $sendleWeightUnit = 'kg';}
    if($pickupCountry == 'US') { $sendleWeightUnit = 'lb';}
    if($pickupCountry == 'CA') { $sendleWeightUnit = 'kg';}

    $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    //if($sendle_setting['satchel_booking'] == 'yes' && $pickupCountry == 'AU' && $satchel_booking_click == 'satchel'){ $weight = "0.5"; }
    // ------   Satchel calculation start --------------------------------------------
    $wpWeightUnit = get_option('woocommerce_weight_unit');
    //ossm_logActions(" wpWeightUnit ----->  :: ".$wpWeightUnit."   ");
    //ossm_logActions(" cartTotalweight ----->  :: ".$cartTotalweight."   ");
    if($pickupCountry == 'AU' && $receiver_country == 'AU') {
      $satchel_threshold_weight = (float)$sendle_setting['satchel_threshold_weight'];
      $satchel_threshold_qty = (float)$sendle_setting['satchel_threshold_qty'];

          if($sendle_setting['satchel_booking'] == 'yes'){
            if($sendle_setting['satchel_mode'] == 'both' || $sendle_setting['satchel_mode'] == 'booking'){

              if($satchel_booking_click == 'satchel'){
                $weight = "500";
                $sendleWeightUnit = 'g';
              }else{

                if($wpWeightUnit == 'kg') { $cartTotalweight = (float)$weight * 1000; }
                if($cartTotalweight <= $satchel_threshold_weight &&  $satchel_threshold_weight > 0){
                  $weight = "500";
                  $sendleWeightUnit = 'g';
                  ossm_logActions(" satchel_threshold_weight_orderbooking  :: yes   ");
                }
                if($cartTotalQuatity <= $satchel_threshold_qty &&  $satchel_threshold_qty > 0){
                  $weight = "500";
                  $sendleWeightUnit = 'g';
                  ossm_logActions(" satchel_threshold_qty_orderbooking  :: yes  ");
                }

              }

            }
          }

    }
    // ------   Satchel calculation end --------------------------------------------

    //$finalWeight= wc_get_weight( $weight, $sendleWeightUnit, $wpWeightUnit );
    //ossm_logActions(" finalWeight :: ". $weight ."-". $sendleWeightUnit ."-". $wpWeightUnit. "  ".$finalWeight);
    return '"weight": {"value": "'.$weight.'", "units": "'.$sendleWeightUnit.'"},';
}

function ossm_getWeight ($weight, $pickupCountry ){
    $wpWeightUnit = get_option('woocommerce_weight_unit');
    $sendleWeightUnit = 'kg';
    if($pickupCountry == 'AU') { $sendleWeightUnit = 'kg';}
    if($pickupCountry == 'US') { $sendleWeightUnit = 'lbs';}
    if($pickupCountry == 'CA') { $sendleWeightUnit = 'kg';}

    if($weight > 0){
      $finalWeight= wc_get_weight( $weight, $sendleWeightUnit, $wpWeightUnit );
    }else{
      $finalWeight = 0;
    }
    return round($finalWeight,2);
}

function ossm_getVolumeStrForQuote ($volumn, $pickupCountry ){
    $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    if(trim($sendle_setting['volume_param']) != 'yes'){ return ''; }
    $sendleDimensionUnit = 'm3';
    if($pickupCountry == 'AU') { $sendleDimensionUnit = 'm3';}
    if($pickupCountry == 'US') { $sendleDimensionUnit = 'in3';}
    if($pickupCountry == 'CA') { $sendleDimensionUnit = 'm3';}
    return "volume_value=".$volumn."&volume_units=".$sendleDimensionUnit."&";
}
function ossm_getVolumeStrForOrder ($volumn, $weight, $pickupCountry, $receiver_country, $satchel_booking_click='normal'  ){
    $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    if(trim($sendle_setting['volume_param']) != 'yes'){ return ''; }
    $sendleDimensionUnit = 'm';
    if($pickupCountry == 'AU') { $sendleDimensionUnit = 'm3';}
    if($pickupCountry == 'US') { $sendleDimensionUnit = 'in3';}
    if($pickupCountry == 'CA') { $sendleDimensionUnit = 'm3';}
    if($volumn>0){
      //if($sendle_setting['satchel_booking'] == 'yes' && $pickupCountry == 'AU' && $satchel_booking_click = 'satchel'){ $volumn = "0.002"; }
      // ------   Satchel calculation start --------------------------------------------
      $wpWeightUnit = get_option('woocommerce_weight_unit');
      //ossm_logActions(" wpWeightUnit ----->  :: ".$wpWeightUnit."   ");
      //ossm_logActions(" cartTotalweight ----->  :: ".$cartTotalweight."   ");
      if($pickupCountry == 'AU' && $receiver_country == 'AU') {
        $satchel_threshold_weight = (float)$sendle_setting['satchel_threshold_weight'];
        $satchel_threshold_qty = (float)$sendle_setting['satchel_threshold_qty'];

            if($sendle_setting['satchel_booking'] == 'yes'){
              if($sendle_setting['satchel_mode'] == 'both' || $sendle_setting['satchel_mode'] == 'booking'){

                if($satchel_booking_click == 'satchel'){
                  return '';
                }else{
                  if($wpWeightUnit == 'kg') { $cartTotalweight = (float)$weight * 1000; }
                  if($cartTotalweight <= $satchel_threshold_weight &&  $satchel_threshold_weight > 0){
                    ossm_logActions(" satchel_threshold_volumn_orderbooking  :: yes   ");
                    return '';
                  }
                  if($cartTotalQuatity <= $satchel_threshold_qty &&  $satchel_threshold_qty > 0){
                    ossm_logActions(" satchel_threshold_volumn_qty_orderbooking  :: yes  ");
                    return '';
                  }

                }
              }
            }

      }
      // ------   Satchel calculation end --------------------------------------------
      return '"volume": {"value": "'.$volumn.'", "units": "'.$sendleDimensionUnit.'"},';
    }else{ return '';}
}

function ossm_getDimension ($dimension, $pickupCountry ){
    $wpDimensionUnit = get_option('woocommerce_dimension_unit');
    //ossm_logActions(" wpDimensionUnit :: ". $wpDimensionUnit );
    $sendleDimensionUnit = 'm';
    if($pickupCountry == 'AU') { $sendleDimensionUnit = 'm';}
    if($pickupCountry == 'US') { $sendleDimensionUnit = 'in';}
    if($pickupCountry == 'CA') { $sendleDimensionUnit = 'm';}

    if($dimension > 0){
      $finalDimension =  wc_get_dimension( $dimension, $sendleDimensionUnit, $wpDimensionUnit );
    }else{
      $finalDimension = 0;
    }

    //ossm_logActions(" finalDimension :: ". $finalDimension );
    return $finalDimension;
}

function ossm_maxWeightLimit ($pickupCountry, $deliveryCountry ){
    $maxWeight = SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT;
    if($pickupCountry == 'AU'){
      $maxWeight = SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT;
      if($deliveryCountry != 'AU'){ $maxWeight = SENDLE_JOOVII_AU_MAX_INTERNATION_WEIGHT;}
    }
    if($pickupCountry == 'US'){ $maxWeight = SENDLE_JOOVII_US_MAX_DOMESTIC_WEIGHT; }
    if($pickupCountry == 'CA'){ $maxWeight = SENDLE_JOOVII_CA_MAX_DOMESTIC_WEIGHT; }
    return $maxWeight;
}
function ossm_maxVolumeLimit ($pickupCountry, $deliveryCountry ){
    $maxVolume = SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN;
    if($pickupCountry == 'AU'){
      $maxVolume = SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN;
      if($deliveryCountry != 'AU'){ $maxVolume = SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN;}
    }
    if($pickupCountry == 'US'){ $maxVolume = SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN; }
    if($pickupCountry == 'CA'){ $maxVolume = SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN; }
    return $maxVolume;
}

function ossm_weightDistributionArray ($packageArr, $weight, $volume, $maxWeight, $maxVolume ){

    arsort($packageArr,1);
    $packageDivisionArr = array();
    if(count($packageArr)>1){
        $weightP = 0;
        $volumeP = 0;
        $volumeDistribution = 0;
        foreach($packageArr as $kp=>$vp){
            $weightCurrent =  $vp['w'];
            $volumeCurrent =  $vp['v'];
            $weightLast    =  $weightP;
            $volumeLast    =  $volumeP;
            $weightP       =  $weightLast + $weightCurrent;
            $volumeP       =  $volumeLast + $volumeCurrent;
            if($weightP > $maxWeight){

              if($volumeP > $maxVolume){ $volumeDistribution = 1; }
              $packageDivisionArr[] = array("w" => $weightLast, "v" => $volumeLast);
              $weightP = $weightCurrent;
              $volumeP = $volumeCurrent;

            }
        }
        //$packageDivisionArr[] = $weightP;
        if($volumeP > $maxVolume){ $volumeDistribution = 1; }
        $packageDivisionArr[] = array("w"=>$weightP, "v"=>$volumeP);

        if($volumeDistribution == 1 ){
          unset($packageDivisionArr);
          reset($packageArr);
          $weightP = 0;
          $volumeP = 0;
          foreach($packageArr as $kp=>$vp){
              $weightCurrent =  $vp['w'];
              $volumeCurrent =  $vp['v'];
              $weightLast    =  $weightP;
              $volumeLast    =  $volumeP;
              $weightP       =  $weightLast + $weightCurrent;
              $volumeP       =  $volumeLast + $volumeCurrent;
              //ossm_logActions(" volumeP--> : ". $volumeP );
              if($volumeP > $maxVolume){
                //ossm_logActions(" volumeP-->maxVolume : ". $volumeP."-".$maxVolume );

                $packageDivisionArr[] = array("w" => $weightLast, "v" => $volumeLast);
                $weightP = $weightCurrent;
                $volumeP = $volumeCurrent;

              }
          }
          //$packageDivisionArr[] = $weightP;
          $packageDivisionArr[] = array("w"=>$weightP, "v"=>$volumeP);

        }

    }else{
      $packageDivisionArr[] = array("w"=>$weight, "v"=>$volume);
    }
    return $packageDivisionArr;
}

function ossm_checkPackage ($package, $sendle_setting, $weight, $volume, $showWpNotice = 'yes' ){

      $pickupCountry    = trim($sendle_setting["pickup_country"]);
      $deliveryCountry  = trim($package["destination"]["country"]);

      if($pickupCountry == "US"){
          if(trim($sendle_setting['volume_param']) == 'yes'){
            if($volume  > SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN ){
                if($showWpNotice == "yes"){
                  wc_add_notice(  __( 'Too large to be delivered your package volume : '.$volume.'in3', 'woocommerce' ) );
                  return false;
                }
            }
          }
      }
      if($pickupCountry == "CA"){
          if(trim($sendle_setting['volume_param']) == 'yes'){
            if($volume  > SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN){
                if($showWpNotice == "yes"){
                  wc_add_notice(  __( 'Too large to be delivered your package volume : '.$volume.'in3', 'woocommerce' ) );
                  return false;
                }
            }
          }
      }

      if($pickupCountry == "AU"){
        if(trim($sendle_setting['volume_param']) == 'yes'){
          if($deliveryCountry == "AU"){
            if($volume  > SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN ){
                if($showWpNotice == "yes"){
                  wc_add_notice(  __( 'Too large to be delivered your package volume : '.$volume.'m3', 'woocommerce' ) );
                  return false;
                }
            }else{
              if($volume  > SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN ){
                  if($showWpNotice == "yes"){
                    wc_add_notice(  __( 'Too large to be delivered your package volume : '.$volume.'m3', 'woocommerce' ) );
                    return false;
                  }
              }
            }
          }

        }
      }
      return true;

}

function ossm_createRequestStr ($package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, $satchelbooking = 'no' ){

      $weightUnit = 'kg';
      $volumeUnit	= 'm3';
      $cost 	= 0;

      $pickupCountry    = trim($sendle_setting["pickup_country"]);
      $pickupSuburb 	  = trim($sendle_setting['pickup_suburb']);
      $pickupPostcode   = trim($sendle_setting['pickup_postcode']);

      $deliveryCountry  = trim($package["destination"]["country"]);
      $deliverySuburb   = trim($package["destination"]["city"]);
      $deliveryPostcode = trim($package["destination"]["postcode"]);
      $deliveryState    = trim($package["destination"]["state"]);

      $deliveryAddress = "";
      if(isset($package["destination"]["address"]) && trim($package["destination"]["address"]) != ""){
        $deliveryAddress    = "delivery_address_line1=".urlencode(trim($package["destination"]["address"]))."&";
      }

      $volumnStr = '';
      if($sendle_setting['mode'] == "live"){ $sendle_apiurl = "https://api.sendle.com"; }
      else{ $sendle_apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
      $pickupLocationStr   ="pickup_suburb=".urlencode($pickupSuburb)."&pickup_postcode=".$pickupPostcode."&pickup_country=".$pickupCountry."&";
      $deliveryLocationStr = $deliveryAddress."delivery_suburb=".urlencode($deliverySuburb)."&delivery_postcode=".$deliveryPostcode."&delivery_country=".$deliveryCountry."&";
      $weightStr ="weight_value=".$weight."&weight_units=".$weightUnit."&";
      $weightStr = ossm_getWeightStrForQuote ($weight, $pickupCountry );
      if($volume>0){ $volumnStr = ossm_getVolumeStrForQuote ($volume, $pickupCountry ); }
      $extraStr ="first_mile_option=".$sendle_setting['pickupoption'];

      // ------   Satchel calculation start --------------------------------------------
      ossm_logActions(" satchel_quotation  :: ".$sendle_setting["pickup_country"]." - ".$package["destination"]["country"]." - (".$satchelbooking." / ".$sendle_setting['satchel_booking'].") - ".$sendle_setting['satchel_mode']." - (".$sendle_setting['satchel_threshold_weight']." / ".$cartTotalweight.") - (".$sendle_setting['satchel_threshold_qty']." / ".$cartTotalQuatity.") ");
      $wpWeightUnit = get_option('woocommerce_weight_unit');
      //ossm_logActions(" wpWeightUnit ----->  :: ".$wpWeightUnit."   ");
      //ossm_logActions(" cartTotalweight ----->  :: ".$cartTotalweight."   ");
      if($sendle_setting["pickup_country"] == 'AU' && $package["destination"]["country"] == 'AU') {
        $satchel_threshold_weight = (float)$sendle_setting['satchel_threshold_weight'];
        $satchel_threshold_qty = (float)$sendle_setting['satchel_threshold_qty'];
        if($satchelbooking == 'yes'){
            if($sendle_setting['satchel_booking'] == 'yes'){
              if($sendle_setting['satchel_mode'] == 'both' || $sendle_setting['satchel_mode'] == 'quotation'){
                if($wpWeightUnit == 'kg') { $cartTotalweight = (float)$cartTotalweight * 1000; }
                if($cartTotalweight <= $satchel_threshold_weight &&  $satchel_threshold_weight > 0){
                  $weightStr = "weight_value=500&weight_units=g&";
                  $volumnStr = '';
                  ossm_logActions(" satchel_threshold_weight_quotation  :: yes   ");
                }
                if($cartTotalQuatity <= $satchel_threshold_qty &&  $satchel_threshold_qty > 0){
                  $weightStr = "weight_value=500&weight_units=g&";
                  $volumnStr = '';
                  ossm_logActions(" satchel_threshold_qty_quotation  :: yes  ");
                }
              }
            }
        }
      }
      // ------   Satchel calculation end --------------------------------------------

      $urlParam = $sendle_apiurl."/api/quote?".$pickupLocationStr.$deliveryLocationStr.$weightStr.$volumnStr.$extraStr;

      return $urlParam;
}

function ossm_calculateSendleRate ($package, $sendle_setting, $urlParam ){

      if(!is_callable('curl_init')){ return ; }
      $sendle_api_id 		     = $sendle_setting['api_id'];
      $sendle_api_key 		   = $sendle_setting['api_key'];
      //$sendle_plan_name 	 = $sendle_setting['plan_name'];

      $args = array('method'	=> 'GET',
      'timeout'   => 30,
      'user-agent'=> $_SERVER['HTTP_USER_AGENT'],
      'headers'   => array(   'Authorization' => 'Basic ' . base64_encode( $sendle_api_id . ':' . $sendle_api_key ),
                  'Content-Type'=> 'application/json','Accept' =>'application/json','Reseller-Identifier'=> 'JooviiWoocommerce' ));
      $content = wp_remote_get( $urlParam, $args );
      $return = wp_remote_retrieve_body( $content );
      $result = json_decode($return, true);
      //ossm_logActions(" rate-result :: ". print_r($result,true). "  ");
      if(isset($result[0])){
        if($result[0]['quote']['gross']['amount'] > 0) {
            ossm_updateSendleInstallation($sendle_setting);
        }
      }
      if(isset($sendle_setting['optintojoovii'])){
        $optintojoovii = $sendle_setting['optintojoovii'];
      }else{ $optintojoovii =  'yes'; }

      if($optintojoovii == 'no'){  return false; }
      else{ return $result;   }

}

function ossm_createRateArray ($package, $sendle_setting, $result ){

      $pickup_country = $sendle_setting['pickup_country'];
      $shipping_quote_markup = $sendle_setting['quote_markup'];
      $shipping_handling_fee = $sendle_setting['shipping_handling_fee'];
      if(trim($shipping_quote_markup) == ''){ $shipping_quote_markup = 0;}
      if(trim($shipping_handling_fee) == ''){ $shipping_handling_fee = 0;}

	    $sendleTitle = $sendle_setting['title'];
      if(trim($sendleTitle) == ""){ $sendleTitle = "Sendle Shipping" ; }

      $sendlecost=0;
      if(isset($result[0])){
        $sendlecost = $result[0]['quote']['gross']['amount'];
      }
      // Tax calculation start
      $taxesArray = '';
      $shipping_handling_fee_tax = 0;
      $shipping_handling_fee_net = 0;
      if($shipping_handling_fee > 0){
        $shipping_handling_fee_add = $shipping_handling_fee;
        $shipping_handling_fee_tax = round($shipping_handling_fee/11,2);
        $shipping_handling_fee_net = $shipping_handling_fee - $shipping_handling_fee_tax;
        ossm_logActions(" shipping_handling_fee :: ".$shipping_handling_fee." ");
        ossm_logActions(" shipping_handling_fee_tax :: ".$shipping_handling_fee_tax." ");
        ossm_logActions(" shipping_handling_fee_net :: ".$shipping_handling_fee_net." ");
      }
      if($pickup_country == 'AU') {
          $taxEnabled= get_option('woocommerce_calc_taxes');
          $taxClassExists = WC_Tax::get_rates();
          //print_r($taxClassExists);
          if ( !empty($taxClassExists) ) {
            ossm_logActions(" taxClassExists :: ".print_r($taxClassExists,true)." ");

            if($taxEnabled == 'yes'){
              ossm_logActions(" shipping TAX applied ");
              if(isset($result[0])){
                $sendlecost = $result[0]['quote']['net']['amount'];
              }

              $shipping_handling_fee_add = $shipping_handling_fee_net;
              $ratex = WC_Tax::_get_tax_rate('111');
              if ( !empty($ratex) ) {
                $tax_rate_id = $ratex['tax_rate_id'];
              }else{
                $taxkey =array_keys($taxClassExists);
                $taxClasspriority = WC_Tax::_get_tax_rate($taxkey['0']);
                $taxesArray1 = array(
                'tax_rate_id' => '111',
                'tax_rate_country' => '',
                'tax_rate_state' => '',
                'tax_rate' => 0,
                'tax_rate_name' => 'ShippingTax',
                'tax_rate_priority' => ($taxClasspriority['tax_rate_priority'] + 1),
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 1,
                'tax_rate_order' => 1,
                'tax_rate_class' => '',
                );
                $tax_rate_id = WC_Tax::_insert_tax_rate( $taxesArray1 );
              }
              $taxesArray = array($tax_rate_id => $result[0]['quote']['tax']['amount'] + $shipping_handling_fee_tax);
              ossm_logActions("  Final tax(sendleTax + handling_fee_tax)  : " .$result[0]['quote']['tax']['amount']." + ".$shipping_handling_fee_tax);

            }else{
              $taxesArray = false;
              if(isset($result[0])){
                $sendlecost = $result[0]['quote']['gross']['amount'];
              }

              $shipping_handling_fee_add = $shipping_handling_fee;
              ossm_logActions("  shipping TAX --->NOT<---- applied [for taxEnabled = no] ");
            }
          }else{
            $taxesArray = false;
            if(isset($result[0])){
              $sendlecost = $result[0]['quote']['gross']['amount'];
            }

            $shipping_handling_fee_add = $shipping_handling_fee;
            ossm_logActions("  shipping TAX --->NOT<---- applied [for taxClassExists = no] ");
          }
      }else{
        $taxesArray = false;
        if(isset($result[0])){
          $sendlecost = $result[0]['quote']['gross']['amount'];
        }

        $shipping_handling_fee_add = $shipping_handling_fee;
        ossm_logActions("  shipping TAX --->NOT<---- applied [for US]");
      }

      // Tax calculation end
      ossm_logActions("  Final cost without tax(sendlecost + handling_fee + markup)  : " .$sendlecost." + ".$shipping_handling_fee_add." + ".($sendlecost * $shipping_quote_markup/100));
      $rate = array(  'id' => "ossmsendle-".$result[0]['plan_name'],
              'label'=> $sendleTitle,
              'cost'=> ($sendlecost + $shipping_handling_fee_add + ($sendlecost * $shipping_quote_markup/100)),
              'taxes'=> $taxesArray,
              'calc_tax'=> 'per_order' );


      if($rate['cost'] == 0) { return; }

      //ossm_logActions(" result :: ".print_r($result,true)." ");
      ossm_logActions(" Rate :: ".print_r($rate,true)."");
      return $rate;

}

function ossm_my_sendle($hook) {
	$srt = '<script type="text/javascript">
	jQuery(\'#woocommerce_ossmsendle_process_as_sendle_order\').css(\'height\',\'90px\');
	</script> ';
	echo $srt;
}

function theme_enqueue_scripts() {
	$sendle_setting  = 	maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	if($sendle_setting['enable_addressmatch'] == 'yes'){
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
	}
}



function theme_autocomplete_js() {
	$sendle_setting  = 	maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	if($sendle_setting['enable_addressmatch'] == 'yes'){
?>
<style type="text/css">
.cityziploader{ background: url(<?php echo plugins_url( '', __FILE__ ); ?>/loader.gif) !important; background-repeat: no-repeat !important; background-position: right !important; }
.ui-autocomplete {max-height: 300px;overflow-y: auto;overflow-x: hidden; position: absolute; top: 100%; left: 0; z-index: 1000; float: left; display: none; min-width: 160px; padding: 4px; margin: 0 0 10px 25px; list-style: none; background-color: #ffffff; border-color: #ccc; border-color: rgba(0, 0, 0, 0.2); border-style: solid; border-width: 1px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); -webkit-background-clip: padding-box; -moz-background-clip: padding; background-clip: padding-box; *border-right-width: 2px; *border-bottom-width: 2px; }
.ui-autocomplete li:hover { color:red; }
.ui-menu-item > a.ui-corner-all { display: block; padding: 3px 15px; clear: both; font-weight: normal; line-height: 18px; color: #555555; white-space: nowrap; text-decoration: none; }
.ui-state-hover, .ui-state-active { color: #ffffff; text-decoration: none; background-color: #0088cc; border-radius: 0px; -webkit-border-radius: 0px; -moz-border-radius: 0px; background-image: none; }
</style>
<script type="text/javascript">

	function selectElements(pagename, pagetype, ui){
		jQuery( '#'+pagename+''+pagetype+'_postcode').val( ui.item.zip);
		jQuery( '#'+pagename+''+pagetype+'_city' ).val( ui.item.city );
		if(jQuery( '#'+pagename+''+pagetype+'_country' ).val() == 'AU') {
			jQuery("#"+pagename+""+pagetype+"_state option[value='"+ui.item.statecode+"']").remove();
			jQuery("#"+pagename+""+pagetype+"_state").append("<option value='"+ui.item.statecode+"' selected >"+ui.item.statename+"</option>");
		}else{
			if(ui.item.statename != ''){
				jQuery("#"+pagename+""+pagetype+"_state option[value='"+ui.item.statecode+"']").remove();
				jQuery("#"+pagename+""+pagetype+"_state").append("<option value='"+ui.item.statecode+"' selected >"+ui.item.statename+"</option>");
			}
		}
	}


  function dynamicSource( request, response , pagename, pagetype, ) {
		jQuery.ajax({
		  url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
		  dataType: "json",
		  data: {action: 'sendlejooviicityziplookup', q: request.term+'countrycode'+jQuery( "#"+pagename+""+pagetype+"_country" ).val() },
		  success: function( data ) { response( data );}
		});
	}

	jQuery(document).ready(function($){
		// for cart
		jQuery( 'input[name="calc_shipping_city"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, 'calc', '_shipping' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#calc_shipping_city" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('calc', '_shipping', ui); return false; }
		});
		jQuery( 'input[name="calc_shipping_postcode"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, 'calc', '_shipping' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#calc_shipping_postcode" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('calc', '_shipping', ui); return false; }
		});
		// ----- checkout billing
		jQuery( 'input[name="billing_postcode"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, '', 'billing' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#billing_postcode" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('', 'billing', ui); jQuery('body').trigger('update_checkout'); return false; }
		});
		jQuery( 'input[name="billing_city"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, '', 'billing' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#billing_city" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('', 'billing', ui); jQuery('body').trigger('update_checkout'); return false; }
		});
		// ----- checkout shipping
		jQuery( 'input[name="shipping_postcode"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, '', 'shipping' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#shipping_postcode" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('', 'shipping', ui); jQuery('body').trigger('update_checkout'); return false; }
		});
		jQuery( 'input[name="shipping_city"]' ).autocomplete({
			source: function( request, response ) { dynamicSource( request, response, '', 'shipping' ); },
			minLength: 2,
			search: function (e, u) {jQuery(this).addClass('cityziploader');},
			response: function (e, u) { jQuery(this).removeClass('cityziploader'); },
			open    : function(){jQuery(this).removeClass('cityziploader');},
			focus: function( event, ui ) {jQuery( "#shipping_city" ).val(  ui.item.city ); return false; },
			select: function(event, ui) { selectElements('', 'shipping', ui); jQuery('body').trigger('update_checkout'); return false; }
		});

	});
</script>
<?php
	}
}


function ossm_logActions($message){
    global $wpdb;
    $sendle_setting  = 	maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    if($sendle_setting['enable_log'] == 'yes'){
      if( strpos( $message, 'wp_order_id' ) !== false ) {
        $strpos=strpos($message, 'wp_order_id');
        $orderid=(float)trim(substr($message, $strpos + 15, 4));
      }else{ $orderid=0; }

      $table_name = $wpdb->prefix."sendlelogs";
      $date = date('Y-m-d H:i:s');
      $wpdb->insert( $table_name, array('eventname'=>'null', 'orderid'=>$orderid, 'logs'=>$message, 'timestamp'=> $date), array('%s', '%d', '%s'));
    }
}


function ossm_validatePostCode($pcode,$country,$shipping_city,$shipping_state){

		$args = array( 'method'			=> 'GET',
						'timeout'     => 30,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array( 'Content-Type'=> 'application/json', 'Accept' =>'application/json'));
		$postcodeApiUrl = "http://api.geonames.org/postalCodeSearchJSON?postalcode=".$pcode."&country=".$country."&username=nerdster";
		$content = wp_remote_get( $postcodeApiUrl, $args );
		$response = wp_remote_retrieve_body( $content );

		$found_place = 0;
		$found_state =0;
		$placename = array();
		$pname = "";

		if(is_array($response)){
			foreach($response['postalCodes'] as $rs){
				$placename[] = $rs['placeName'];
				$state[] = $rs['adminCode1'];
				$pname .= " | ".$rs['placeName'];
				$stt .= " | ".$rs['adminCode1'];
			}
			if (in_array($shipping_city, $placename)) { $found_place = 1; }
			if (in_array($shipping_state, $state)) { $found_state = 1; }
		}

		if ($found_place == 1 && $found_state == 1) { return("Match found"); }
    else{ return("Match not found");}
}

function ossm_updateSendleInstallation($sendle_setting) {
    $pickup_country = $sendle_setting['pickup_country'];
    if(isset($sendle_setting['optintojoovii'])){
      $optintojoovii = $sendle_setting['optintojoovii'];
    }else{ $optintojoovii =  'yes'; }
    if($optintojoovii == 'yes'){
      if($pickup_country == ''){ $pickup_country = get_option('woocommerce_default_country'); }
      if(trim(get_option('woocommerce_ossm_sendle_updatejoovii')) == 'yessendle_v1') {}else{
         //ossm_logActions("\n  update joovi client db ----9999----".get_option('woocommerce_ossm_sendle_updatejoovii') );
         wp_remote_get( "http://plugins.joovii.com/updateclientdb.php?use=1&domain=".$_SERVER['SERVER_NAME']."&apiid=".$sendle_setting['api_id']."&woocountry=".$pickup_country );
         if(trim(get_option('woocommerce_ossm_sendle_updatejoovii')) != 'yessendle_v1') {
           update_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
         }else{
           add_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
         }
      }
    }
}

function ossm_checkSendleZone($sendle_setting) {

  global $wpdb;
  $pickup_country = $sendle_setting['pickup_country'];
  if($pickup_country == ''){ $pickup_country = get_option('woocommerce_default_country'); }
  if(trim($sendle_setting['api_id']) != ''){
      $sendleCheckZone = $wpdb->get_results( " Select *  FROM {$wpdb->prefix}woocommerce_shipping_zone_methods where method_id='ossmsendle-zone' and is_enabled=1 ");
      if(count($sendleCheckZone) == 0){

        if(trim(get_option('woocommerce_ossm_sendle_checkzone')) == 'yessendlezone') {}else{
          ossm_logActions(" ossm_checkSendleZone notification :: 1"."http://plugins.joovii.com/updateclientdb.php?use=1&domain=".$_SERVER['SERVER_NAME']."&wpcheckzone=1&apiid=".$sendle_setting['api_id']."&woocountry=".$pickup_country );
           wp_remote_get( "http://plugins.joovii.com/updateclientdb.php?use=1&domain=".$_SERVER['SERVER_NAME']."&wpcheckzone=1&apiid=".$sendle_setting['api_id']."&woocountry=".get_option('woocommerce_default_country') );
           $message = 'NOTE: If you have not set a shipping zone and assigned the Sendle shipping method, Sendle shipping quotes will NOT work.  The Sendle shipping method MUST be assigned to the relevant shipping zone for quotes to appear for addresses in that zone.';
           wp_mail( get_option('admin_email'), 'Notice:  Please set WooCommerce Shipping Zones', $message, '', '' );
           if(trim(get_option('woocommerce_ossm_sendle_checkzone')) != 'yessendlezone') {
             update_option( 'woocommerce_ossm_sendle_checkzone', 'yessendlezone' );
           }else{
             add_option( 'woocommerce_ossm_sendle_checkzone', 'yessendlezone' );
           }
        }
      }
  }

}



function ossm_getDownloadLabelLink($sendle_order_id) {

      $sendle_order_id= sanitize_text_field($sendle_order_id);
      $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
      $api_id = $sendle_setting['api_id'];
      $api_key = $sendle_setting['api_key'];
      $api_mode = $sendle_setting['mode'];
      if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
      else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
      $urlParam = $apiurl."/api/orders/".$sendle_order_id."";
      $args = array(
            'method'			=> 'GET',
            'timeout'     => 30,
            'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
            'headers'   => array(   'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
                        'Content-Type'=> 'application/json','Accept' =>'application/json'));

      $content = wp_remote_get( $urlParam, $args );
      $return = wp_remote_retrieve_body( $content );
      $result = json_decode($return, true);
      $labelsArray = $result['labels'];
      return $labelsArray;
}

function ossm_get_sendle_order_details($sendle_order_id){

  $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
  $api_id = $sendle_setting['api_id'];
  $api_key = $sendle_setting['api_key'];
  $api_mode = $sendle_setting['mode'];
  if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
  else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
  $urlParam = $apiurl."/api/orders/".$sendle_order_id."";
  //echo '-u=>'. $api_id.":".$api_key;
  $args = array(
        'method'			=> 'GET',
        'timeout'     => 30,
        'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
        'headers'   => array(   'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
                    'Content-Type'=> 'application/json','Accept' =>'application/json'));

  $content = wp_remote_get( $urlParam, $args );
  $return = wp_remote_retrieve_body( $content );
  $result = json_decode($return, true);
  return $result;
}

function ossm_arrayToString($result){
  $str = '';
  if(is_array($result)){
	  foreach($result as $ks1=>$vs1){
		$str .= "<b>".$ks1."</b>=>";
		if(is_array($vs1)){
		  foreach($vs1 as $ks2=>$vs2){
			if(is_array($vs2)){ $str .= str_replace("Array","",print_r($vs2,true)); }
			else{ $str .= "<br> &nbsp; &nbsp; ".$ks2."=>".$vs2;}
		  }
		}else{ $str .= $vs1; }
		$str .= "<br>";
	  }
  }
  return $str;
}

function ossm_create_shipment(){
	$order_id = sanitize_text_field($_GET['oid']);
  $createMethod = '';
  if(isset($_GET['method'])){
    $createMethod = sanitize_text_field($_GET['method']);
  }
	ossm_logActions("  Order id# ".$order_id."-----------Sendle order created by shipmentSubmit[admin]  ");
	if(get_post_meta( $order_id, 'sendle_reference', true )==""){
			$response = ossm_generate_sendle_reference($order_id,'byadmin',$createMethod);
      if(isset($response['order_id'])){
        $sendle_order_id= sanitize_text_field($response['order_id']);
      }
	}else{
		ossm_logActions("Order id=".$order_id." has been already posted to sendle. [admin]");
    $sendle_order_id = get_post_meta( $order_id, 'sendle_order_id', true );
	}

  $result = ossm_get_sendle_order_details($sendle_order_id);
  //print_r($result);
?>
    <div class="wrap">
  	<h2>Shipment Information</h2>
    <?php if(isset($result['state'])){
			echo "<br> Sendle Order Status: ".$result['state']."";
		} ?>
    <?php
    if(isset($result['state'])){
      if($result['state'] != 'Cancelled' ) {
        $labelsArray = ossm_getDownloadLabelLink($sendle_order_id);
    ?>

	<table class="widefat" width="100%" border="1" cellspacing="5" cellpadding="5">
  	  <tbody>
            <tr>
                <td width="150">Order Id</td>
                <td><?=$order_id?></td>
            </tr>
            <tr>
                <td>Sendle Order Id</td>
                <td><?=$result['order_id']?></td>
            </tr>
            <tr>
                <td>Sendle Reference</td>
                <td><?=$result['sendle_reference']?></td>
            </tr>
            <tr>
                <td>Weight</td>
                <td><?php echo ossm_arrayToString($result['weight']); ?></td>
            </tr>
            <tr>
                <td>Volume</td>
                <td><?php echo ossm_arrayToString($result['volume']); ?></td>
            </tr>
            <tr>
                <td>Sender</td>
                <td><?php echo ossm_arrayToString($result['sender']); ?></td>
            </tr>
            <tr>
                <td>Receiver</td>
                <td><?php echo ossm_arrayToString($result['receiver']); ?></td>
            </tr>
            <tr>
                <td>Tracking url</td>
                <td><a href="<?=$result['tracking_url']?>" target="_blank"><?=$result['tracking_url']?></a></td>
            </tr>
            <tr>
                <td>Shipping Label</td>
                <td>
                  <?php if(isset($labelsArray)) { ?>
                  <?php foreach($labelsArray as $kl=>$vl){ ?>
                    <a href="<?php echo admin_url('admin.php?page=download-shipping-label&oid='.$order_id.'&pdfdlink='.$vl['size']); ?>" target="_blank"><?php _e('Download Shipping Label[size='.$vl['size'].']' )?></a><br>
      					<?php } } ?>
                </td>
            </tr>
            <tr>
                <td>Scheduling</td>
                <td><?php echo ossm_arrayToString($result['scheduling']); ?></td>
            </tr>
            <tr>
                <td>Route</td>
                <td><?php echo ossm_arrayToString($result['route']); ?></td>
            </tr>
            <tr>
                <td>Price</td>
                <td><?php echo ossm_arrayToString($result['price']); ?></td>
            </tr>
      </tbody>
    </table>
      <?php } } ?>
<?php } ?>
