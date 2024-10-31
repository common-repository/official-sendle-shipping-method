<?php

function ossm_sendle_add_my_account_endpoint() {
    add_rewrite_endpoint( 'tracking', EP_PAGES );
}
add_action( 'init', 'ossm_sendle_add_my_account_endpoint' );

function ossm_sendle_tracking_endpoint_content() {
  global $wp;
  $order_id = sanitize_text_field($_GET['order']);
  // Get all customer orders
  $customer_orders = get_posts( array(
        'numberposts' => -1,
        'meta_key'    => '_customer_user',
        'meta_value'  => get_current_user_id(),
        'post_type'   => wc_get_order_types(),
        'post_status' => array_keys( wc_get_order_statuses() ),
        ) );
  $order_ids = array();
  foreach($customer_orders as $customer_order){
    $order_ids[] = $customer_order->ID;
  }
	if (in_array($order_id, $order_ids)) {

		$order = new WC_Order($order_id);
		?>
		<section class="woocommerce-order-details">
			<h2 class="woocommerce-order-details__title">Tracking Information</h2>
			<?php
				$tracking_url = get_post_meta($order_id,"sendle_tracking_url",true);

        $order_id = trim(str_replace('#', '', $order->get_order_number()));
        $sendle_reference = get_post_meta($order_id,'sendle_reference',true);
        $api_id = get_option('sendle_shipping_api_id');
        $api_key = get_option('sendle_shipping_api_key');
        $api_mode = get_option('sendle_shipping_api_mode');
        if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
        else{$apiurl = SENDLE_JOOVII_API_SANDBOX_URL;}
        $urlParam = $apiurl."/api/tracking/".$sendle_reference;

        $args = array(
                'method'			=> 'GET',
                'timeout'     => 30,
                'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
                'headers' 		=> array( 'Content-Type'=> 'application/json',
                                        'Accept' =>'application/json')
                );

        $content = wp_remote_get( $urlParam, $args );
        $return = wp_remote_retrieve_body( $content );
        $response = json_decode($return, true);

			?>
			<table class="woocommerce-table woocommerce-table--order-details shop_table" width="100%" border="0" cellspacing="5" cellpadding="5">
			<thead>
			  <tr>
				<th><strong>Tracking Number</strong></th>
				<th><strong>Info</strong></th>
			  </tr>
		  </thead>
		  <tbody>
				<tr>
					<td><?=$sendle_reference?></td>
					<td>
						<?php
						if($response['error']!=""){
							echo $response['error_description'];
						}
						if(!empty($response['tracking_events'])){
							?>
								<table class="widefat striped">
									<thead>
									<tr>
										<th><strong>Event</strong></th>
										<th><strong>Time</strong></th>
										<th><strong>Description</strong></th>
									</tr>
									</thead>
									<tbody>
										<?php
										foreach(array_reverse($response['tracking_events']) as $events){
											echo "<tr>";
											echo "<td>".$events['event_type']."</td>";
											$scan_time =explode("T",$events['scan_time']);
											$event_date = $scan_time[0];
											$event_time = substr($scan_time[1], 0, -1);
											echo "<td>".$event_date." @ ".$event_time."</td>";
											echo "<td>".$events['description']."</td>";
											echo "</tr>";
										}
										?>
									</tbody>
								</table>
							<?php
						}else{
								echo "No info available";
						}
						echo "<br/><a href=\"$tracking_url\" target=\"_blank\">Goto Sendle for more info :: $tracking_url</a>";
						?></td>
				</tr>
		  </tbody>
		  </table>
		</section>
		<?php
	}else{
    ?>
<div class="woocommerce-error">Invalid order. <a href="<?=home_url('my-account');?>" class="wc-forward">My account</a></div>
<?php
	}
}

add_action( 'woocommerce_account_tracking_endpoint', 'ossm_sendle_tracking_endpoint_content' );

function ossm_sendle_is_endpoint( $endpoint = false ) {
    global $wp_query;
    if( !$wp_query ){ return false; }
    return isset( $wp_query->query[ $endpoint ] );
}


function ossm_sendle_add_my_account_order_actions( $actions, $order ) {
	if($order->get_shipping_method()=="Sendle Shipping - Easy"){
		$actions['help'] = array(
			'url'  => '/my-account/tracking/?order=' . $order->get_order_number(),
			'name' => __( 'Track', 'nerdster' ),
		);
	}
  return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', 'ossm_sendle_add_my_account_order_actions', 10, 2 );
?>
