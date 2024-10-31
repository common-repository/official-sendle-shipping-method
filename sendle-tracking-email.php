<?php

function ossm_sendle_trackingemailtemplate(){

	$defaultValue = '
	Hi {{customer_name}}
	An order you recently placed on our website has had its status changed.

	The status of order #{{order_no}} is now Shipped

	Shipment Tracking Numbers: {{tracking_number}}
	Shipment Tracking Links : {{tracking_link}}

	{{store_name}}';
	if(isset($_POST['sendle_tracking_email_template'])){
		$postemailTemplateVal = sanitize_textarea_field($_POST['sendle_tracking_email_template']);
	}else{
		$postemailTemplateVal='';
	}
	$emailTemplateVal = get_option('woocommerce_ossm_sendle_tracking_email_template');

	if(trim($postemailTemplateVal) != ''){

		$emailTemplateVal = $postemailTemplateVal;
		if(trim(get_option('woocommerce_ossm_sendle_tracking_email_template')) != '') {
			update_option( 'woocommerce_ossm_sendle_tracking_email_template', $emailTemplateVal );
		}else{
			add_option( 'woocommerce_ossm_sendle_tracking_email_template', $emailTemplateVal );
		}

	}else{
		if(trim(get_option('woocommerce_ossm_sendle_tracking_email_template')) != '') {
			$emailTemplateVal = $emailTemplateVal;
		}else{
			$emailTemplateVal = $defaultValue;
		}
	}





?>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
<input type="hidden" name="rate" value="calculate">
<div class="wrap"><h3 style="text-decoration:underline">Sendle Tracking Email Template</h3></div>
    <table cellpadding="0" cellspacing="0" border="0"  width="100%" >
    <tr>
        <td>
					<textarea rows="15" cols="120" name="sendle_tracking_email_template"> <?php echo $emailTemplateVal; ?> </textarea>
				</td>
    </tr>
    <tr>
        <td align="left">&nbsp;<br><input type='submit' name="Save" value='Save' class='button'></td>
    </tr>
		<tr>
        <td align="left">&nbsp;<br><b>Following is the default email template.</b></td>
    </tr>
		<tr>
        <td align="left">&nbsp;<br> <?php echo nl2br($defaultValue); ?> </td>
    </tr>
    </table>
</form>
<?php
}
?>
