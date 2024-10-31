<?php

function ossm_pickup_date_delay($select_pickup_date){

	$pickupDate = date_i18n('Y-m-d');
	$select_pickup_date_new = $select_pickup_date;
	if($select_pickup_date>1){

		$pickupDateNew1 = date('D',date(strtotime("+".$select_pickup_date." day", strtotime($pickupDate))));
		$pickupDateNew2 = date('Y-m-d',date(strtotime("+".$select_pickup_date." day", strtotime($pickupDate))));
		if($pickupDateNew1 == 'Sat') {
			$select_pickup_date_new += 2;
			ossm_logActions(" skip[sat-sun] order-pick-date-delay [".$select_pickup_date."-->".$select_pickup_date_new."]  :: [".$pickupDate."==>".$pickupDateNew2."] ");
		}
		if($pickupDateNew1 == 'Sun') {
			$select_pickup_date_new += 1;
			ossm_logActions(" skip[sun] order-pick-date-delay [".$select_pickup_date."-->".$select_pickup_date_new."]  :: [".$pickupDate."==>".$pickupDateNew2."] ");
		}

	}else{
		$select_pickup_date_new = 1;
	}

	return $select_pickup_date_new;

}

function ossm_generate_sendle_reference($order_id=0, $postedby='byfn', $satchel_booking_click='normal'){

	  //ossm_logActions(" ossm_generate_sendle_reference invoked ");
	  $sendle_setting 		 = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	  $pickup_suburb		   = $sendle_setting['pickup_suburb'];
	  $pickup_postcode 		 = $sendle_setting['pickup_postcode'];
		$pickup_country 		 = $sendle_setting['pickup_country'];
		//$satchel_booking 		 = $sendle_setting['satchel_booking'];

		$satchel_booking 		 = '';
		$satchel_mode 		 = '';
		$satchel_threshold_weight 		 = '';
		$satchel_threshold_qty 		 = '';
		if(isset($sendle_setting['satchel_booking'])){
			$satchel_booking 		 = $sendle_setting['satchel_booking'];
		}
		if(isset($sendle_setting['satchel_mode'])){
			$satchel_mode 		 = $sendle_setting['satchel_mode'];
		}
		if(isset($sendle_setting['satchel_threshold_weight'])){
			$satchel_threshold_weight 		 = $sendle_setting['satchel_threshold_weight'];
		}
		if(isset($sendle_setting['satchel_threshold_qty'])){
			$satchel_threshold_qty 		 = $sendle_setting['satchel_threshold_qty'];
		}

		$optintojoovii = 'no';
		if(isset($sendle_setting['optintojoovii'])){
			$optintojoovii = $sendle_setting['optintojoovii'];
		}

		//$optintojoovii = $sendle_setting['optintojoovii'];

		if($optintojoovii == 'no'){	return false;	}

		//ossm_logActions(" -->pickup_country ".$pickup_country);
		if(trim($pickup_country) == ''){$pickup_country = 'AU';}
	  //$plan_name 			   = $sendle_setting['plan_name'];
	  $select_pickup_date 	  = $sendle_setting['pickup_delay'];
		if($select_pickup_date > 0){
		}else{ $select_pickup_date=1; }
	  $site_url 				= home_url();
	  $order = new WC_Order( $order_id );

	  $countries_obj = new WC_Countries();
	  $countries_array = $countries_obj->get_countries();
	  $country_states_array = $countries_obj->get_states();


	  $receiver_name 		= get_post_meta($order_id,"_shipping_first_name",true)." ".get_post_meta($order_id,"_shipping_last_name",true);
	  $receiver_email 	   = get_post_meta($order_id,"_billing_email",true);
	  $receiver_phone 	   = get_post_meta($order_id,"_billing_phone",true);
	  $receiver_address1 	= get_post_meta($order_id,"_shipping_address_1",true);
	  $receiver_company   	 = get_post_meta($order_id,"_shipping_company",true);
	  $receiver_address2 	= get_post_meta($order_id,"_shipping_address_2",true);
	  $receiver_suburb 	  = get_post_meta($order_id,"_shipping_city",true);
	  $receiver_state 	   = get_post_meta($order_id,"_shipping_state",true);
	  $receiver_postcode 	= get_post_meta($order_id,"_shipping_postcode",true);
	  $receiver_country 	 = get_post_meta($order_id,"_shipping_country",true);
	  //$order_comment 	    = get_post_meta($order_id,"customer_message",true);
	  //$order_comment = $order->customer_message;
	  $items 				= $order->get_items();

	  $receiver_suburb_name =  $country_states_array[$receiver_country][$receiver_state];
	  if(trim($receiver_suburb_name) =='') { $receiver_suburb_name=$receiver_state; }

	  //ossm_logActions("<---country_states_array-----".print_r($country_states_array,true)."--------->/n");
	  //ossm_logActions("<---country_states_array-----".$receiver_country."--".$receiver_state."----".$receiver_suburb_name."----->/n");

	  $weight = 0;
	  $volume = 0;
	  $orderdetails = "";
		$orderdetails_withoutsku = "";
	  $orderdetails1 = '';
		$orderdetails1_withoutsku = '';
	  $orderprice = 0;

		$woo_hs_code= '';
		$hs_code_field_name = '';
		if(isset($sendle_setting['hs_code'])){
			$woo_hs_code = $sendle_setting['hs_code'];
		}
		if(isset($sendle_setting['hs_code_field_name'])){
			$hs_code_field_name = $sendle_setting['hs_code_field_name'];
		}

		$hs_code_str = '';
		$extrafieldforint = '';

	  foreach ( $items as $item ) {
			if ( $item['product_id'] > 0 ) {
				ossm_logActions(" items -- [product_id=".$item['product_id']."] variation_id =".$item['variation_id']." ");
				$product_id = $item['variation_id'];
				if(trim($product_id) == '' || trim($product_id) == '0'){

					// for variation item
					$product_id = $item['product_id'];
					$_product = wc_get_product($product_id);

					foreach($_product->attributes as $attr_key=> $attr_val)	{
						if($attr_val['name']==$hs_code_field_name){
							$woo_hs_code= $attr_val['options']['0'];
						}
					}
					if(trim($woo_hs_code) != ''  ) { $hs_code_str = '"hs_code": "'.$woo_hs_code.'",'; }
					if( $pickup_country != $receiver_country){
						$extrafieldforint .= '{ "description": "'.strip_tags($_product->short_description).'",
"value": "'.$_product->regular_price.'",
"quantity": "'.$item['qty'].'",
'.$hs_code_str.'
"country_of_origin": "'.$pickup_country.'" },
';
					}

					if ( ! $_product->is_virtual() ) {
						if(trim($sendle_setting['volume_param']) == 'yes'){
							$pv = (ossm_getDimension($_product->get_length(),$pickup_country) * ossm_getDimension($_product->get_width(),$pickup_country) * ossm_getDimension($_product->get_height(),$pickup_country));
							$orderdetails .= " Name : ".$item['name']." SKU : ".$_product->get_sku()." Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
							$orderdetails_withoutsku .= " Name : ".$item['name']."  Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
							$volume += (float)$pv * $item['qty'];
						}else{
							$orderdetails .= " Name : ".$item['name']." SKU : ".$_product->get_sku()." Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Quantity : ".$item['qty'].",";
							$orderdetails_withoutsku .= " Name : ".$item['name']."  Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Quantity : ".$item['qty'].",";
						}
						$weight += (float)$_product->get_weight() * $item['qty'];
						$orderdetails1 .= $item['name'].'(sku:'.$_product->get_sku().');';
						$orderdetails1_withoutsku .= $item['name'].' ;';
						$orderprice = $orderprice + $_product->get_price();
					}

				}else{

					// for root item [which has no variation products]
					$variation_id = $item['variation_id'];
					$_product = wc_get_product($product_id);
					$_product_variation = wc_get_product($variation_id);

					if ( ! $_product->is_virtual() ) {

						foreach($_product->attributes as $attr_key=> $attr_val)	{
							if(isset($attr_val['name'])){
								if($attr_val['name']==$hs_code_field_name){
									if(isset($attr_val['options'])){
										if(isset($attr_val['options']['0'])){
											$woo_hs_code= $attr_val['options']['0'];
										}
									}
								}
							}

						}
						if(trim($woo_hs_code) != ''  ) { $hs_code_str = '"hs_code": "'.$woo_hs_code.'",'; }
						if( $pickup_country != $receiver_country){
							$extrafieldforint .= '{ "description": "'.strip_tags($_product->short_description).'",
"value": "'.$_product->regular_price.'",
"quantity": "'.$item['qty'].'",
'.$hs_code_str.'
"country_of_origin": "'.$pickup_country.'" },
';
						}

						if($_product->get_weight() > 0){
							$weight += (float)$_product->get_weight() * $item['qty'];
					  }else{
							// if varition item has no weight then get the weight of root item
							$weight += (float)$_product_variation->get_weight() * $item['qty'];
						}
						if(trim($sendle_setting['volume_param']) == 'yes'){
							if($_product->get_length() > 0){
								$pv = (ossm_getDimension($_product->get_length(),$pickup_country) * ossm_getDimension($_product->get_width(),$pickup_country) * ossm_getDimension($_product->get_height(),$pickup_country));
								$orderdetails .= " Name : ".$item['name']." SKU : ".$_product->get_sku()." Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
								$orderdetails_withoutsku .= " Name : ".$item['name']." Weight : ".ossm_getWeight($_product->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
								$volume += (float)$pv * $item['qty'];
							}else{
								// if varition item has no volume then get the volume of root item
								$pv = (ossm_getDimension($_product_variation->get_length(),$pickup_country) * ossm_getDimension($_product_variation->get_width(),$pickup_country) * ossm_getDimension($_product_variation->get_height(),$pickup_country));
								$orderdetails .= " Name : ".$item['name']." SKU : ".$_product_variation->get_sku()." Weight : ".ossm_getWeight($_product_variation->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
								$orderdetails_withoutsku .= " Name : ".$item['name']."  Weight : ".ossm_getWeight($_product_variation->get_weight(), $pickup_country)." Volume : ".$pv." Quantity : ".$item['qty'].",";
								$volume += (float)$pv * $item['qty'];
							}
						}else{
							$orderdetails .= " Name : ".$item['name']." SKU : ".$_product_variation->get_sku()." Weight : ".ossm_getWeight($_product_variation->get_weight(), $pickup_country)." Quantity : ".$item['qty'].",";
							$orderdetails_withoutsku .= " Name : ".$item['name']."  Weight : ".ossm_getWeight($_product_variation->get_weight(), $pickup_country)." Quantity : ".$item['qty'].",";
						}
						$orderdetails1 .= $item['name'].'(sku:'.$_product->get_sku().');';
						$orderdetails1_withoutsku .= $item['name'].' ;';
						$orderprice = $orderprice + $_product->get_price();
					}

				}

			}
	  }
		$weight = ossm_getWeight ($weight, $pickup_country );
		$maxWeight = ossm_maxWeightLimit ($pickup_country, trim($receiver_country) );
		if($weight > $maxWeight){
			ossm_logActions(" OrderBooking Error :: sendle max weight error [".$order_id."] : order item weight(".$weight.") > sendle max weight (".$maxWeight.")  ");
			return;
		} // order items weight is grater than the sendle max weight

		$method = explode(" ",$order->get_shipping_method());
		$shipping_method = @array_shift($order->get_shipping_methods());
		$shipping_method_name1 = $shipping_method['method_id'];
		$shipping_method_name2 = explode("-",$shipping_method['method_id']);
		$shipping_method_name3 = explode(":",$shipping_method['method_id']);


		ossm_logActions(" postorder1-- [orderid:".$order_id."] ".print_r($order->get_shipping_method(),true)." ");
		ossm_logActions(" postorder2-- [orderid:".$order_id."] ".print_r($shipping_method['method_id'],true)." ");
		ossm_logActions(" postorder3-- [orderid:".$order_id."] ".$shipping_method_name1." ");
		ossm_logActions(" postorder4-- [orderid:".$order_id."] ".print_r($shipping_method_name2,true)." ");

		if($pickup_country == "AU"){
			if($receiver_country != "AU"){
					if($weight == 0 || $weight  > SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT ){
						ossm_logActions(" postorder5-- [orderid:".$order_id."] -error : order item weight >25 or <=0 : weight='".$weight."'");
						//return ;
					}
					if(trim($sendle_setting['volume_param']) == 'yes'){
						//$volume = (float)$volume/(1000000);//volume from cm cube to m cube.
						if($volume <= 0 || $volume  > SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN){
							ossm_logActions(" postorder6-- [orderid:".$order_id."] -error : order item Volume >0.1 or <=0 : volume='".$volume."'");
							//return ;
						}
					}
			}else{
					if($weight == 0 || $weight  > SENDLE_JOOVII_AU_MAX_INTERNATION_WEIGHT){
						ossm_logActions(" postorder5-- [orderid:".$order_id."] -error : order item weight >20 or <=0 : weight='".$weight."'");
						//return ;
					}
					if(trim($sendle_setting['volume_param']) == 'yes'){
						//$volume = (float)$volume/(1000000);//volume from cm cube to m cube.
						if($volume <= 0 || $volume  > SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN ){
							ossm_logActions(" postorder6-- [orderid:".$order_id."] -error : order item Volume >0.1 or <=0 : volume='".$volume."'");
							//return ;
						}
					}

			}
		}

		if($pickup_country == "US" ){
			//if($receiver_country == "US"){
					if($weight == 0 || $weight  > SENDLE_JOOVII_US_MAX_DOMESTIC_WEIGHT){
						ossm_logActions(" postorder5-- [orderid:".$order_id."] -error : order item weight >70 or <=0 : weight='".$weight."'");
						//return ;
					}
					if(trim($sendle_setting['volume_param']) == 'yes'){
						if($volume <= 0 || $volume  > SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN){
							ossm_logActions(" postorder6-- [orderid:".$order_id."] -error : order item Volume >864 or <=0 : volume='".$volume."'");
							//return ;
						}
					}
			//}
		}

		if( $pickup_country == "CA"){
			//if($receiver_country == "US"){
					if($weight == 0 || $weight  > SENDLE_JOOVII_CA_MAX_DOMESTIC_WEIGHT){
						ossm_logActions(" postorder5-- [orderid:".$order_id."] -error : order item weight >70 or <=0 : weight='".$weight."'");
						//return ;
					}
					if(trim($sendle_setting['volume_param']) == 'yes'){
						if($volume <= 0 || $volume  > SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN ){
							ossm_logActions(" postorder6-- [orderid:".$order_id."] -error : order item Volume >864 or <=0 : volume='".$volume."'");
							//return ;
						}
					}
			//}
		}

		// Satchel	0.5kg	0.002
		ossm_logActions(" [orderid:".$order_id."] ". $orderdetails ." -- Weight : $weight- Volume : $volume ");
		$psoArr = $sendle_setting['process_as_sendle_order'];
		$sendlePost = 0;
		//if($sendle_setting['showrates'] == 'yes'){ $sendlePost = 0; }else{ }
		if(in_array("flat_rate", $psoArr) && in_array("flat_rate", $shipping_method_name3) ){ $sendlePost = 1; }
		if(in_array("free_rate", $psoArr) && in_array("free_shipping", $shipping_method_name3) ){ $sendlePost = 1; }
		if(in_array("any_method", $psoArr) ){ $sendlePost = 1; }

		if(in_array("ossmsendle", $shipping_method_name2) || $sendlePost == 1){
			ossm_logActions(" process_as_sendle_order -- [orderid:".$order_id."] ".print_r($sendle_setting['process_as_sendle_order'],true)." ");
			ossm_logActions(" method1 -- [orderid:".$order_id."] ".print_r($method,true)." ");
		}else{
			return;
		}


		ossm_logActions(" strlen--orderdetails  ".strlen($orderdetails1)." ");
		if(substr(trim($orderdetails1), -1) == ";"){
			$orderdetails1 = substr(trim($orderdetails1), 0, -1);
			$orderdetails1_withoutsku = substr(trim($orderdetails1_withoutsku), 0, -1);
			if( strlen($orderdetails1)>300 ){
				$orderdetails1 = $orderdetails1_withoutsku;
				if( strlen($orderdetails1)>300 ){
					$orderdetails1 = 'You can find the details in metadata->orderdetails field ';
				}
			}
		}
		$orderdetails = strip_tags($orderdetails);
		$parcel_contents_int ='';
		if( $pickup_country != $receiver_country){
			if($extrafieldforint != ''){
				if(substr(trim($extrafieldforint), -1) == ","){
					$extrafieldforint = substr(trim($extrafieldforint), 0, -1);
				}
				$parcel_contents_int = ',
				"parcel_contents": [ '.$extrafieldforint.' ]  ';
			}
		}

	  $sender_contact = "{
					\"name\": \"".$sendle_setting['sender_name']."\",
					\"phone\": \"".$sendle_setting['sender_contact_number']."\",
					\"email\": \"".get_option('admin_email')."\",
        	\"company\": \"".get_bloginfo( 'name' )."\"
				  }";

	  $sender_address = "{
					\"address_line1\": \"".$sendle_setting['sender_address']."\",
					\"suburb\": \"".$pickup_suburb."\",
					\"state_name\": \"".$sendle_setting['sender_state']."\",
					\"postcode\": \"".$pickup_postcode."\",
					\"country\": \"".$pickup_country."\"
				  }";
	  $receiver_contact = "{
					\"name\": \"".$receiver_name."\",
					\"email\": \"".$receiver_email."\",
					\"phone\": \"".$receiver_phone."\",
					\"company\": \"".$receiver_company."\"
				  }";
	  $receiver_address = "{
					\"address_line1\": \"".$receiver_address1." ".$receiver_address2."\",
					\"suburb\": \"".$receiver_suburb."\",
					\"state_name\": \"".$receiver_suburb_name."\",
					\"postcode\": \"".$receiver_postcode."\",
					\"country\": \"".$receiver_country."\"
				  }";


	  if(isset($sendle_setting['receiver_instruction'])){
	  	$receiver_instruction = $sendle_setting['receiver_instruction'];
		if(trim($receiver_instruction) =='') { $receiver_instruction = "Call me"; }
	  }else{
	  	$receiver_instruction = "Call me";
	  }
	  if(isset($sendle_setting['sender_instruction'])){
	  	$sender_instruction =  $sendle_setting['sender_instruction'];
		if(trim($sender_instruction) =='') { $sender_instruction = "Call me"; }
	  }else{
	  	$sender_instruction = "Call me";
	  }

	  if(($weight<=0.5)){
	  	//$receiver_instruction = "";
	  }else{
	  	//$receiver_instruction = (trim($order_comment)!=""?$order_comment:$receiver_instruction);
	  }


		$pickupdateStr ='';
		if(trim($select_pickup_date) > 1 ) { $pickupdateStr ='"pickup_date": "$$$pickupdate$$$",'; }
		if($sendle_setting['pickupoption'] == 'drop off') { $pickupdateStr =''; }

		if(isset($sendle_setting['enable_customer_reference'])){
			$customer_reference = apply_filters('ossm_filter_customer_reference', $order_id);
			if ($customer_reference && strlen($customer_reference) >= 255) $customer_reference = $order_id;
			ossm_logActions("[orderid:".$order_id."] ossm_filter_customer_reference Customer Reference : ".$customer_reference." ");
		}else{
			$customer_reference = $order_id;
		}

	  $wp_version = get_bloginfo( 'version' );
	  $json = '{
				'.$pickupdateStr.'
				"first_mile_option": "'.$sendle_setting['pickupoption'].'",
				"description": "Shipment Booked with Order Id : '.$order_id.' from '.$site_url.'",
				'.ossm_getWeightStrForOrder ($weight, $pickup_country, $receiver_country, $satchel_booking_click ).'
				'.ossm_getVolumeStrForOrder ($volume, $weight, $pickup_country, $receiver_country, $satchel_booking_click ).'
				"customer_reference": "'.$order_id.'",
				"metadata": {
				  "extensiondetails": "Joovii: wp_joovii_version='.SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION.'; wp_version='.$wp_version.' ",
				  "orderdetails": "'.str_replace("\"","",substr($orderdetails, 0, -1)).'",
				  "wp_order_id": "'.$order_id.'",
				  "wp_store_name": "'.$site_url.'",
					"satchel_booking_enabled": "'.$satchel_booking.'-(mode='.$satchel_mode.')-(action='.$satchel_booking_click.')-(threshold_weight='.$satchel_threshold_weight.'/threshold_qty='.$satchel_threshold_qty.') "
				},
				"sender": {
				  "contact": '.$sender_contact.',
				  "address": '.$sender_address.',
				  "instructions": "'.$sender_instruction.'"
				},
				"receiver": {
				  "contact": '.$receiver_contact.',
				  "address": '.$receiver_address.',
				  "instructions": "'.$receiver_instruction.'"
				}'.$parcel_contents_int.'
			  }';

		$errerArr = array();
		$errerArr[0]=1;
		$new_json = '';
		$pickupDate = date_i18n('Y-m-d');
		$select_pickup_date = ossm_pickup_date_delay($select_pickup_date);
	  if($select_pickup_date > 1){
			$pickupDate = date('Y-m-d',date(strtotime("+".$select_pickup_date." day", strtotime($pickupDate))));
			ossm_logActions(" order pick date ".$select_pickup_date."  :: ".$pickupDate." ");
		}
		ossm_logActions(" order pick date [orderid:".$order_id."] ".$select_pickup_date."  :: ".$pickupDate." ");
		$new_json = str_replace('$$$pickupdate$$$',$pickupDate,$json);

		$alterIdempotencyKey = '';
		for($ic=0;$ic<10;$ic++){
			$return = ossm_postOrderCurl($new_json, $order_id,$postedby,$pickupDate,$alterIdempotencyKey);
			$response = json_decode($return, true);

			if(count($response) > 0 ) {
				if(isset($response['error'])){
					if(trim($response['error']) != ''){

						ossm_logActions("  Order Error ".$postedby." [orderid:".$order_id."]  :: ".$new_json." ");
						ossm_logActions("  Order Error ".$postedby." [orderid:".$order_id."]  :: ".print_r($response, true)." ");

						if(isset($response['messages']['pickup_date'])){
							if (strstr(trim($response['messages']['pickup_date']['0']), 'must be a business day')) {
									$errerArr[0]=1; $errerArr[1]=$response;

									if($sendle_setting['pickupoption'] == 'pickup'){
										if($select_pickup_date > 1){
											$pickupDate = date('Y-m-d',date(strtotime("+1 day", strtotime($pickupDate))));
											ossm_logActions(" order pick date new ".$select_pickup_date."  :: ".$pickupDate." ");
											$new_json = str_replace('$$$pickupdate$$$',$pickupDate,$json);
											$alterIdempotencyKey = 'yes';
											if(trim($pickupdateStr) != ''){ continue; }
										}

									}else{ break; }
							}else{
								$errerArr[0]=1; $errerArr[1]=$response;
								break;
							}
						}else{
							echo "Error in Posting shipment to sendle api :: ". print_r($response, true);
							update_post_meta( $order_id, 'sendle_post_error', print_r($response, true) );
							break;
						}
					}

				}else{

					$errerArr[0]=0; $errerArr[1]=$response;
					ossm_logActions("  Order Success ".$postedby." [orderid:".$order_id."]  :: ".$new_json." ");
					ossm_logActions("  Order Success ".$postedby." [orderid:".$order_id."]  :: ".print_r($errerArr, true)." ");

					update_post_meta( $order_id, 'sendle_order_id', $response['order_id'] );
					update_post_meta( $order_id, 'sendle_reference', $response['sendle_reference'] );
					update_post_meta( $order_id, 'sendle_tracking_url', $response['tracking_url'] );
					update_post_meta( $order_id, 'sendle_response', $return );
					update_post_meta( $order_id, 'sendle_post', $new_json );

					// update joovii with siteurl and sendleid and orderno
					ossm_updateSendleOrderWithJoovii($sendle_setting, $response);

					if($sendle_setting['tracking_email'] == 'yes'){
						$default_emailTemplateVal = '
						Hi {{customer_name}}
						An order you recently placed on our website has had its status changed.

						The status of order #{{order_no}} is now Shipped

						Shipment Tracking Numbers: {{tracking_number}}
						Shipment Tracking Links : {{tracking_link}}

						{{store_name}}';

						$to = $receiver_email;
						$subject = 'Your Order Has Been Updated (#'.$order_id.')';
						$emailTemplateVal = get_option('woocommerce_ossm_sendle_tracking_email_template');
						if(trim($emailTemplateVal) == '') { $emailTemplateVal = $default_emailTemplateVal; }
						$body = str_replace('{{customer_name}}', ucwords($receiver_name), $emailTemplateVal );
						$body = str_replace('{{order_no}}', $order_id, $body );
						$body = str_replace('{{tracking_number}}', $response['sendle_reference'], $body );
						$body = str_replace('{{tracking_link}}', $response['tracking_url'], $body );
						$body = nl2br($body);
						$body = str_replace('{{store_name}}', $site_url, $body );

						$site_title = get_bloginfo( 'name' );
						$site_admin_email = get_bloginfo( 'admin_email' );


						$headers = array('Content-Type: text/html; charset=UTF-8');
						$headers[] = 'From: '.$site_title.'<'.$site_admin_email.'>';

						wp_mail( $to, $subject, $body, $headers );
						ossm_logActions(" tracking email sent [".$to."]  ::   ".$order_id);
						ossm_logActions(" tracking email sent [subject] ::   ".$subject);
						ossm_logActions(" tracking email sent [body] ::   ".$body);
						ossm_logActions(" tracking email sent [header] ::  --->".$site_title."--".$site_admin_email."<---");
						ossm_logActions(" tracking email sent [emailTemplateVal] ::   ".$emailTemplateVal);

					}


					if($sendle_setting['change_order_status'] == 'processing'){
						$order->update_status( 'processing' );
						ossm_logActions(" update order status to processing  ::   ".$order_id);
					}
					if( $sendle_setting['change_order_status'] == 'completed'){
						$order->update_status( 'completed' );
						ossm_logActions(" update order status to completed ::   ".$order_id);
					}
					ossm_logActions("  Order submitted from '".$sendle_setting['book_shipment_on']."' completed. ".$postedby." ");

					break;
				}


			} else {
				ossm_logActions("  Order Error(response blank) (".$postedby.") [orderid:".$order_id."]  :: ".$new_json." ");
				ossm_logActions("  Order Error(response blank) (".$postedby.") [orderid:".$order_id."]  :: maybe order has been already posted. ");
				$errerArr[0]=1;
				break;
			}

		}
		return $response;

}

$sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
$book_shipment_on = '';
if(isset($sendle_setting['book_shipment_on'])){
	$book_shipment_on = $sendle_setting['book_shipment_on'];
}

//ossm_logActions("----------- ".$book_shipment_on."------------------------/n");

if($book_shipment_on!="shipment_submit"){
		//if($sendle_setting['orderconfig_lineEnable'] == 'yes'){
			add_action('woocommerce_thankyou', 'ossm_generate_sendle_reference', 111, 1);
		//}
}


function ossm_custom_add_validation_rules_shipping() {
		if ( ! isset( WC()->cart ) ) { return; }
		$bcountry    	    = WC()->customer->get_billing_country();
		$bshipping_city   = WC()->customer->get_billing_city();
		$bshipping_state  = WC()->customer->get_billing_state();
		$bpcode   		    = WC()->customer->get_billing_postcode();
		$scountry    	    = WC()->customer->get_shipping_country();
		$sshipping_city   = WC()->customer->get_shipping_city();
		$sshipping_state  = WC()->customer->get_shipping_state();
		$spcode   		    = WC()->customer->get_shipping_postcode();
		$matched = 0;
		if(ossm_validatePostCode($bpcode,$bcountry,$bshipping_city,$bshipping_state)=="Match found"){
			$matched = 1;
		}
		if(ossm_validatePostCode($spcode,$scountry,$sshipping_city,$sshipping_state)=="Match found"){
			$matched = 1;
		}
		//ossm_logActions(" Custom shipping rule validation invoked  --Shipping Country : $bcountry|$scountry-----State : $bshipping_city|$sshipping_state------City : $bshipping_city|$sshipping_city--- Postcode : $bpcode|$spcode---Found : $matched--- ");
		if ($matched != 1){
				WC_add_notice( "Invalid Postcode, City and State for Sendle Shipping not available.", 'woocommerce' );
		}

}
add_action( 'woocommerce_checkout_process', 'ossm_custom_add_validation_rules_shipping' );
//add_action('woocommerce_after_checkout_validation', 'ossm_deny_pobox_postcode');

function ossm_deny_pobox_postcode( $posted ) {
		global $woocommerce;
		$check_address  = array();
		$check_address[] = isset( $posted['shipping_address_1'] ) ? $posted['shipping_address_1'] : $posted['billing_address_1'];
		$check_address[] = isset( $posted['shipping_address_2'] ) ? $posted['shipping_address_2'] : $posted['billing_address_2'];
		$check_address = strtolower( str_replace( array( ' ', '.' ), '', implode( '-', $check_address ) ) );
		if(trim($posted['shipping_country']) == 'AU'){
			if ( strstr( $check_address, 'pobox' ) ) {
				wc_add_notice( sprintf( __( "Sorry, we cannot ship to PO BOX addresses.") ) ,'error' );
			}
		}
}

function ossm_postOrderCurl($json , $order_id, $postedby, $pickupDate, $alterIdempotencyKey){

	$sendle_setting  = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	$api_id 	= $sendle_setting['api_id'];
	$api_key 	= $sendle_setting['api_key'];
	$api_mode = $sendle_setting['mode'];
	if($api_mode == "live"){ $apiurl = "https://api.sendle.com";
	}else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
	$url = $apiurl."/api/orders";
	ossm_logActions(" apiurl [orderid:".$order_id."] :: ".$url." ");
	$appendStr = '';
	if(trim(get_post_meta( $order_id, 'sendle_reference', true ))==""){
		if($alterIdempotencyKey == 'yes'){ $appendStr = "-".$pickupDate; }
	}
	if(trim(get_post_meta( $order_id, 'sendle_post_error', true ))!=""){
		$appendStr = "-".$pickupDate;
	}
	$idempotencyKey = md5($order_id."-".$api_key.$appendStr."-".rand(11, 99));
	ossm_logActions(" raw idempotencyKey   :: ".$order_id."-".$api_key.$appendStr."-".rand(11, 99));
	$args = array(
					'timeout'     => 30,
					'user-agent'  => 'Joovii WooCommerce/3.2.4',
					'body'		=> $json,
					'headers' 	 => array('Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
																	'Idempotency-Key' => $idempotencyKey,
																	'Content-Type'=> 'application/json',
																	'Reseller-Identifier'=> 'JooviiWoocommerce',
																	'Accept' =>'application/json') );

  ossm_logActions(" Idempotency array  :: ".print_r($args,true));

	$sendle_reference = get_post_meta($order_id,'sendle_reference',true);
	$sendle_order_id = get_post_meta($order_id,'sendle_order_id',true);
	$sendle_tracking_url = get_post_meta($order_id,'sendle_tracking_url',true);

	ossm_logActions(" sendle_reference   :: ".$sendle_reference);
	ossm_logActions(" sendle_tracking_url   :: ".$sendle_tracking_url);

	if(trim($sendle_reference) == ''){
		$content = wp_remote_post( $url, $args );
		$return  = wp_remote_retrieve_body( $content );
	}else{

		ossm_logActions("Order id=".$order_id." has been already posted to sendle. [".$postedby."]");
		if(trim($sendle_tracking_url)!= ""){
			$sendle_tracking_url_array = explode("=",$sendle_tracking_url);
			update_post_meta( $order_id, 'sendle_reference', $sendle_tracking_url_array['1'] );
		}
		$return ='';
	}
	return $return;

}


function ossm_updateSendleOrderWithJoovii($sendle_setting, $response) {

    $pickup_country = $sendle_setting['pickup_country'];
    if(isset($sendle_setting['optintojoovii'])){
      $optintojoovii = $sendle_setting['optintojoovii'];
    }else{ $optintojoovii =  'yes'; }
		if($pickup_country == ''){ $pickup_country = get_option('woocommerce_default_country'); }

    if($optintojoovii == 'yes'){
      if(trim(get_option('woocommerce_ossm_sendle_updatejoovii')) == 'yessendle_v1') {}else{
         //ossm_logActions("\n  update joovi client db ----9999----".get_option('woocommerce_ossm_sendle_updatejoovii') );
				 $new_url = sanitize_url( "https://plugins.joovii.com/updateclientdb.php?use=1&domain=".$_SERVER['SERVER_NAME']."&apiid=".$sendle_setting['api_id']."&woocountry=".$pickup_country, array( 'http', 'https' ) );
         wp_remote_get( $new_url );
         if(trim(get_option('woocommerce_ossm_sendle_updatejoovii')) != 'yessendle_v1') {
           update_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
         }else{
           add_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
         }
      }
    }

		$sendle_order_id = $response['order_id'];
		$sendle_reference = $response['sendle_reference'];

		$new_url = sanitize_url( "https://plugins.joovii.com/updatesendledata.php?use=1&domain=".$_SERVER['SERVER_NAME']."&apiid=".$sendle_setting['api_id']."&woocountry=".$pickup_country."&tid=".$sendle_reference, array( 'http', 'https' ) );
		wp_remote_get( $new_url );


}
