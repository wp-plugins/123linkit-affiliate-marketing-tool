<?php

$api_address = "174.143.204.12";

header("Cache-Control: no-cache");

$path = "http://$api_address/api/";
$agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5";


function geturl($url){
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	$results = curl_exec($ch);
	curl_close($ch);
	return $results;
}
function posturl($url, $data){
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url); //This really isn't the way it's supposed to be done but, can't figure out the problem
	curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$results = curl_exec($ch);
	$info = curl_getinfo( $ch );
    if ($info['http_code'] != 200) {
	     return array(false, "Problem reading data from $url : " . curl_error( $ch ) . "\n");
	}
	curl_close($ch);
	if(!$results){
		$results = false;
	}
	return $results;

}
//When we get a post request...
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	
	/*if (!isset($_POST['_pubkey']) || !isset($_POST['_privkey'])) {
	    header($_SERVER['SERVER_PROTOCOL'] . " 500");
	    header("Content-Type: text/plain");
	    die("No api key.\n");
	}Deal with this later*/ 
	   
	$postvars = '';
	$url = '';
	while ( ($element = current( $_POST ))!==FALSE ) {
			 if(key($_POST) == "url"){
				$url = $element;
				next($_POST);
			 }else{
				$new_element = str_replace( '&', '%26', $element );
				$new_element = str_replace( ';', '%3B', $new_element );
				$postvars .= key( $_POST ).'='.$new_element.'&';
				next( $_POST );
			}
	}
	
	$results = posturl($path.$url, $postvars);
	header("Content-type: text/plain");
	echo $results;

//Well what if we get a GET request?
}else{
	 echo "buzz off";	
}
?>
