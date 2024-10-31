<?php

function ossm_validate_sendle(){
	$sendle_setting 		 = maybe_unserialize( get_option('woocommerce_ossmsendle_settings'));
	$pickup_suburb 		   = $sendle_setting['pickup_suburb'];
	$pickup_postcode		 = $sendle_setting['pickup_postcode'];
	$shipping_quote_markup  = $sendle_setting['quote_markup'];
	$sendle_id 			  	 = $sendle_setting['api_id'];
	$sendle_key 			   = $sendle_setting['api_key'];
	$mode 				       = $sendle_setting['mode'];
	$pickupoption				 = $sendle_setting['pickupoption'];
	$pickup_country 		 = (isset($_REQUEST['pickup_country']))?ossm_validate_input_text($_REQUEST['pickup_country']):"AU";

	if($sendle_setting['pickup_country'] == 'US'){
		$delivery_suburb 		= 'Brooklyn';
		$delivery_postcode	= '11203';
		$product_weight 		= '10';
		$product_volume		  = '70';
		$delivery_country	  = 'US';
		$pickup_country	    = 'US';
	}elseif($sendle_setting['pickup_country'] == 'CA'){
		$delivery_suburb 		= 'Toronto';
		$delivery_postcode	= 'M4Y 0A9';
		$product_weight 		= '1';
		$product_volume		  = '0.001';
		$delivery_country	  = 'CA';
		$pickup_country	    = 'CA';
	}else{
		$delivery_suburb 		= 'Sydney';
		$delivery_postcode	= '2000';
		$product_weight 		= '1';
		$product_volume		  = '0.001';
		$delivery_country	  = 'AU';
		$pickup_country	    = 'AU';
	}

	if(isset($_REQUEST['sendle_id'])){ if(trim($_REQUEST['sendle_id']) != '') { $sendle_id=ossm_validate_input_text($_REQUEST['sendle_id']); } }
	if(isset($_REQUEST['sendle_key'])){ if(trim($_REQUEST['sendle_key']) != '') { $sendle_key=ossm_validate_input_text($_REQUEST['sendle_key']); }}
	if(isset($_REQUEST['mode'])){ if(trim($_REQUEST['mode']) != '') { $mode=ossm_validate_input_text($_REQUEST['mode']); }}
	if(isset($_REQUEST['pickup_suburb'])){ if(trim($_REQUEST['pickup_suburb']) != '') { $pickup_suburb=ossm_validate_input_text($_REQUEST['pickup_suburb']); }}
	if(isset($_REQUEST['pickup_postcode'])){ if(trim($_REQUEST['pickup_postcode']) != '') { $pickup_postcode=ossm_validate_input_text($_REQUEST['pickup_postcode']); }}

	if(isset($_REQUEST['delivery_suburb'])){ if(trim($_REQUEST['delivery_suburb']) != '') { $delivery_suburb=ossm_validate_input_text($_REQUEST['delivery_suburb']); }}
	if(isset($_REQUEST['delivery_postcode'])){ if(trim($_REQUEST['delivery_postcode']) != '') { $delivery_postcode=ossm_validate_input_text($_REQUEST['delivery_postcode']); }}
	if(isset($_REQUEST['delivery_country'])){ if(trim($_REQUEST['delivery_country']) != '') { $delivery_country=ossm_validate_input_text($_REQUEST['delivery_country']); }}

	if(isset($_REQUEST['product_weight'])){ if(trim($_REQUEST['product_weight']) != '') { $product_weight=ossm_validate_input_text($_REQUEST['product_weight']); }}
	if(isset($_REQUEST['product_volume'])){ if(trim($_REQUEST['product_volume']) != '') { $product_volume=ossm_validate_input_text($_REQUEST['product_volume']); }}

	if(isset($_REQUEST['pickupoption'])){ if(trim($_REQUEST['pickupoption']) != '') { $pickupoption=ossm_validate_input_text($_REQUEST['pickupoption']); }}

	$countries_obj   = new WC_Countries();
  $countries   = $countries_obj->__get('countries');
	//print_r($countries);

?>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
<input type="hidden" name="rate" value="calculate">
<div class="wrap"><h3 style="text-decoration:underline">Validate Sendle Api Key</h3></div>
    <table cellpadding="0" cellspacing="0" border="0"  width="100%" >
    <tr>
        <td width="10%" align="right">Sendle ID</th>
        <td width="1%" >&nbsp;</th>
        <td><input type="text" name="sendle_id" value="<?php echo $sendle_id; ?>" style="width:350px" /></td>
    </tr>
    <tr>
        <td align="right">Sendle Key</th>
        <td>&nbsp;</th>
        <td><input type="text" name="sendle_key" value="<?php echo $sendle_key; ?>"  style="width:350px" /></td>
    </tr>
    <tr>
        <td align="right">Mode</th>
        <td>&nbsp;</th>
        <td><select class="select " name="mode" id="mode" style="">
                	<option value="sandbox" <?php if($mode =='sandbox'){?> selected="selected" <?php }?>>Sandbox</option>
                	<option value="live" <?php if($mode =='live'){?> selected="selected" <?php }?>>Live</option>
             </select></td>
    </tr>
		<tr>
        <td align="right">Pickup Country</th>
        <td>&nbsp;</th>
        <td>
        	<select name="pickup_country" id="pickup_country">
						<option value="AU" <?php if($pickup_country =='AU'){?> selected="selected" <?php }?>>Australia</option>
						<option value="US" <?php if($pickup_country =='US'){?> selected="selected" <?php }?>>United States</option>
						<option value="CA" <?php if($pickup_country =='CA'){?> selected="selected" <?php }?>>Canada</option>
        	</select>
        </td>
    </tr>
    <tr>
        <td align="right">Pickup Suburb</th>
        <td>&nbsp;</th>
        <td><input type="text" name="pickup_suburb" value="<?php echo $pickup_suburb; ?>" /></td>
    </tr>
    <tr>
        <td align="right">Pickup Postcode</th>
        <td>&nbsp;</th>
        <td><input type="text" name="pickup_postcode" value="<?php echo $pickup_postcode; ?>" /></td>
    </tr>
    <tr>
        <td align="right">Delivery Suburb</th>
        <td>&nbsp;</th>
        <td><input type="text" name="delivery_suburb" value="<?php echo $delivery_suburb; ?>" /></td>
    </tr>
    <tr>
        <td align="right">Delivery Postcode</th>
        <td>&nbsp;</th>
        <td><input type="text" name="delivery_postcode" value="<?php echo $delivery_postcode; ?>" /></td>
    </tr>
		<tr>
        <td align="right">Delivery Country</th>
        <td>&nbsp;</th>
        <td>
        	<select name="delivery_country" id="delivery_country">
            <?php foreach($countries as $k=>$v){ ?>
            <option value="<?php echo $k;?>" <?php if($delivery_country == $k){ echo "selected"; } ?> ><?php echo $v;?> </option>
            <?php } ?>
        	</select>[Change it for international Order, also Sendle does not support International Orders sent from the United States yet.]
        </td>
    </tr>
    <tr>
        <td align="right">Product Weight</th>
        <td>&nbsp;</th>
        <td><input type="text" name="product_weight" value="<?php echo $product_weight; ?>" />(In KG for AU/CA and lb for US)</td>
    </tr>
    <tr>
        <td align="right">Product Volume</th>
        <td>&nbsp;</th>
        <td><input type="text" name="product_volume" value="<?php echo $product_volume; ?>" />(In m3 for AU/CA and in3 for US)</td>
    </tr>
		<tr>
        <td align="right">Pickup Option </th>
        <td>&nbsp;</th>
        <td><select class="select " name="pickupoption" id="pickupoption" style="">
									<option value="pickup" <?php if($pickupoption == 'pickup'){ echo "selected"; } ?>>Pickup From store</option>
									<option value="drop off" <?php if($pickupoption == 'drop off'){ echo "selected"; } ?>>Drop it off at the nearest drop off location. </option>
							</select></td>
    </tr>
    <tr>
        <td >&nbsp;</th>
        <td>&nbsp;</th>
        <td align="left">&nbsp;<input type='submit' name="validate" value='Validate' class='button'></td>
    </tr>
    </table>
</form>
<?php

if(isset($_REQUEST['rate'])){
if($_REQUEST['rate']=='calculate'){

	$package = array();
	$package["destination"]["country"] = ossm_validate_input_text($_POST['delivery_country']);
	$package["destination"]["city"] = ossm_validate_input_text($_POST['delivery_suburb']);
	$package["destination"]["postcode"] = ossm_validate_input_text($_POST['delivery_postcode']);
	$package["destination"]["state"] = '';
	$sendleSettingArr = array();
	$sendleSettingArr["pickup_country"] = ossm_validate_input_text($_POST['pickup_country']);
	$sendleSettingArr["pickup_suburb"] = ossm_validate_input_text($_POST['pickup_suburb']);
	$sendleSettingArr["pickup_postcode"] = ossm_validate_input_text($_POST['pickup_postcode']);
	$sendleSettingArr["pickupoption"] = ossm_validate_input_text($_POST['pickupoption']);
	$sendleSettingArr["woocommerce_ossm_sendle_updatejoovii"] = 'yes';

  $sendleSettingArr['enabled'] = "yes";
	$sendleSettingArr['showrates'] = "yes";
	$sendleSettingArr['quote_markup'] =0;
	$sendleSettingArr['shipping_handling_fee'] =0;
	$sendleSettingArr['api_id'] = ossm_validate_input_text($_POST['sendle_id']);
	$sendleSettingArr['api_key'] = ossm_validate_input_text($_POST['sendle_key']);
	$sendleSettingArr['mode'] = ossm_validate_input_text($_POST['mode']);
	$sendleSettingArr['volume_param'] = '';
	$sendleSettingArr['satchel_booking'] = '';
	$sendleSettingArr['satchel_mode'] = '';
	$sendleSettingArr['satchel_threshold_weight'] = '';
	$sendleSettingArr['satchel_threshold_qty'] = '';
	$sendleSettingArr['satchel_threshold_weight'] = '';
	$sendleSettingArr['satchel_threshold_qty'] = ''; 

	$urlParam = ossm_createRequestStr ($package, '99999', '99999', $sendleSettingArr, ossm_validate_input_text($_POST['product_weight']), ossm_validate_input_text($_POST['product_volume']),'no' );
	$result = ossm_calculateSendleRate ($package, $sendleSettingArr, $urlParam );

	//echo "</br> Sendle Shipping cost will be : ".$rate;
	?>
    <br />GET URL : <?php echo $urlParam;?><br /><br />
    <?php
	if(isset($result['error_description'])){
		if(trim($result['error_description']) != ''){
			echo '<div style="width:50%; text-align:left; font-weight:bold; font-size:14px; background-color:#D98888;padding: 2px; ">Error :: '.$result['error_description']."</div><br><br>";
			if(isset($result['messages'])){
				print_r($result['messages']);
			}
			//echo $return;
		}
	}
	if(isset($result[0]['quote']['gross']['amount'])){
		$rate = $result[0]['quote']['gross']['amount'];
	?>
	<div style="width:50%; text-align:left; font-weight:bold; font-size:14px; background-color:#CCC; padding: 2px;">Sendle Shipping Cost  : &nbsp;<?php echo $rate; ?></div>
    <?php
	}

}

}
}
?>
