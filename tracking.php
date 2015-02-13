<?php

//include our handy API wrapper that makes it easy to call the API, it also depends on MOScURL to make the cURL call
require_once("MOSAPICall.class.php");

// setup our credentials
$apikey = '0c4b719ef4cc61c10b0e48475f9e2ea455c0f8e3a91315a0607df5709b34fa20';
$password = 'apikey'; // API Keys don't have a password and just use this filler string
$account_id = '56345';
$mosapi = new MOSAPICall($apikey, $account_id);

$item_response_xml = $mosapi->makeAPICall("Account.Order","Read");
//$item_response_xml = $mosapi->makeAPICall("Account.Order","Read", '','null','xml',"load_relations=all");

foreach ($item_response_xml->Order as $order){

	if ($order->complete == 'false'){
		$orderID = $order->orderID;
		$order_xml = $mosapi->makeAPICall("Account.Order","Read",$orderID,'null','xml',"load_relations=[\"CustomFieldValues\"]");

		if ($order_xml->CustomFieldValues) {
			if ($order_xml->CustomFieldValues->CustomFieldValue->name == 'Tracking Number'){
				//Call function with tracking number
				$writenote = USPS($order_xml->CustomFieldValues->CustomFieldValue->value);
				echo empty($writenote);
				if (!empty($writenote)) {
					writeNote($mosapi, $orderID, $writenote);
				}
			}
		}
	}
}

function USPS($tracking){
	//$trackingnum = $item_response_xml->CustomFieldValues->CustomFieldValue->value;
	
	echo '<pre>';
	echo 'Tracking Number: '.$tracking;
	echo '</pre>';
	
	$xml = '<TrackRequest USERID="749LIGHT6958"><TrackID ID="'.$tracking.'"></TrackID></TrackRequest>';
			
	$url = "http://production.shippingapis.com/ShippingAPI.dll?API=TrackV2&XML=";
	$url .= urlencode($xml);
	
	// create curl resource 
	$ch = curl_init(); 
	
	// set url 
	curl_setopt($ch, CURLOPT_URL, $url); 
	
	//return the transfer as a string 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	// $output contains the output string 
	$output = curl_exec($ch); 
	
	// close curl resource to free up system resources 
	curl_close($ch);
	
	$results = simplexml_load_string($output);
	$final = (array)$results;    

	return $final['TrackInfo']->TrackSummary;
}

function writeNote($mosapi, $ID, $note){
	
	$write_data = "<?xml version='1.0'?><Order><Note><note>$note</note></Note></Order>";
	echo $ID.' '.htmlentities($write_data);
	$order_xml = $mosapi->makeAPICall("Account.Order","Update", $ID, $write_data);
	echo '<pre>';
	print_r($order_xml);
	echo '</pre>';
	echo $write_data . 'written to Order: '. $ID . '<br />';
	
}
?>
