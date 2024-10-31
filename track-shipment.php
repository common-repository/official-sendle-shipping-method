<?php

function ossm_track_shipment(){
	?>
    <div class="wrap">
    <h2>Tracking Information</h2>
    <?php
		$sendle_reference= sanitize_text_field($_GET['sendle_reference']);
		$order_id = sanitize_text_field($_GET['oid']);
    $tracking_url = get_post_meta($order_id,"sendle_tracking_url",true);

    $sendle_reference= sanitize_text_field($_GET['sendle_reference']);
    $api_id = get_option('sendle_shipping_api_id');
    $api_key = get_option('sendle_shipping_api_key');
    $api_mode = get_option('sendle_shipping_api_mode');
    if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
    else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
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
		<table class="widefat" width="100%" border="0" cellspacing="5" cellpadding="5">
        <thead>
          <tr>
            <th><strong>Tracking Number</strong></th>
            <th><strong>Info</strong></th>
          </tr>
      </thead>
  	  <tbody>
      		<tr>
            	<td><?php echo $sendle_reference; ?></td>
              <td>
					<?php
					if(isset($response['error'])){
         			if($response['error']!=""){ echo $response['error']."<br />".$response['error_description']."<br />"; }
					}
					if($response['state']!=""){ echo $response['state']."<br />".$response['status']['description']."<br />"; }
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
    										$scan_time = explode("T",$events['scan_time']);
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
							//echo "No info available";
					}
					echo "<br/><a href=\"$tracking_url\" target=\"_blank\">Goto Sendle for more info :: $tracking_url</a>";
					?></td>
            </tr>
      </tbody>
      </table>
    </div>
<?php } ?>
