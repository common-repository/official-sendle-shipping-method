<?php

function ossm_getcityziplookup(){

  $input =sanitize_text_field($_REQUEST['q']);
  $inputArr = explode("countrycode", $input);
  $placename_startsWith = $inputArr[0];
  $country=$inputArr[1];
  $url = 'http://api.geonames.org/postalCodeSearchJSON?placename_startsWith='.$placename_startsWith.'&maxRows=400&country='.$country.'&username=nerdster';
  $url = str_replace(' ', '%20', $url);

	$suggestions1 = wp_remote_get($url);
	$suggestions2 = json_decode(wp_remote_retrieve_body($suggestions1),true);
	//echo "<pre>";print_r($suggestions2);
  $json = '';
  foreach($suggestions2['postalCodes'] as $k=>$v){
  	$json .= '{"ID":'.($k+1).',"label":"'.$v['placeName'].', '.$v['postalCode'].' '.$v['adminName1'].' '.$country.'","city":"'.$v['placeName'].'","zip":"'.$v['postalCode'].'","statecode":"'.trim($v['ISO3166-2']).'","statename":"'.trim($v['adminName1']).'"},';
  }
  $json = rtrim($json, ',');
  $json = '['.$json.']';
  echo $json;
  die();

}

?>
