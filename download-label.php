<?php

function ossm_download_shipping_label(){
?>
    <div class="wrap"><h2>Download Shipping Label</h2>
    <?php

      $sendle_pdfsize= sanitize_text_field($_GET['pdfdlink']);
      $oid= sanitize_text_field($_GET['oid']);
      $sendle_reference = get_post_meta($oid,'sendle_reference',true);
      $sendle_order_id = get_post_meta($oid,'sendle_order_id',true);

      $result = ossm_get_sendle_order_details($sendle_order_id);

      if(isset($result['state'])){
      if($result['state'] != 'Cancelled' ) {

      $sendle_pdfdlink = '';
      $labelsArray = ossm_getDownloadLabelLink($sendle_order_id);
      foreach($labelsArray as $kl=>$vl){
        if($vl['size'] == trim($sendle_pdfsize)){
          $sendle_pdfdlink = $vl['url'];
        }
      }

      //$sendle_pdfdlink= sanitize_text_field($_GET['pdfdlink']);
      $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
      $api_id = $sendle_setting['api_id'];
      $api_key = $sendle_setting['api_key'];
      //$urlParam = $apiurl."/api/orders/".$sendle_order_id."/labels/".$label_size.".pdf";
      $urlParam = $sendle_pdfdlink;
      //echo '-u=>'. $api_id.":".$api_key;
      $args = array(
              'method'			=> 'GET',
              'timeout'     => 30,
              'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
              'headers' 		=> array(  //'-u'=> $api_id.":".$api_key,
                                       'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
                                       '-L' =>'',
                                       '-O'=> 'local_label_filename.pdf'
                                    ));

      $content = wp_remote_get( $urlParam, $args );
      $return = wp_remote_retrieve_body( $content );
      $return1 = (array)$content['http_response'];
       //print_r($return1);
      foreach($return1 as $k=>$v){
        $return3 = (array)$v;
        //print_r($return3);
    		if(isset($return3['url'])){
           $pdflink = $return3['url'];
           if(trim($pdflink) != ''){
              echo '</br><a id="sendleapi" target="_blank" href="'.$pdflink.'">Click here to download the label</a>';
           }
    		}
      }
      ?>
    </div>
    <script> document.getElementById("sendleapi").click(); </script>
<?php }else{ echo "Not Available"; } }?>

<?php } ?>
