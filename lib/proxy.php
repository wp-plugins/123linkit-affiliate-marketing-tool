<?php

define('BASE_URL', "http://www.123linkit.com/");

function LinkITCurlOpen($url, $vars) {
	$c = curl_init($url);
	$variables_url = "";
	foreach($vars as $key=>$value) $variables_url .= urlencode($key) . '=' . urlencode($value) . '&';

	$c_options = array(
		CURLOPT_USERAGENT => "123LinkIT Plugin",
		CURLOPT_RETURNTRANSFER =>	true,
		CURLOPT_POST =>	true,
		CURLOPT_HTTPHEADER => array('Expect:'),
		CURLOPT_TIMEOUT => 1
	);
	
	if($variables_url)
		$c_options[CURLOPT_POSTFIELDS] = $variables_url;

	curl_setopt_array($c, $c_options);

	$response['data'] = curl_exec($c);
	$response['code'] = curl_getinfo($c, CURLINFO_HTTP_CODE);
	return $response;
}

function LinkITFOpen($url, $vars) {
	echo "Error: FOpen not implemented!";
}

function LinkITFSockOpen($url, $vars) {
	echo "Error: FSockOpen not implemented!";
}

function LinkITMakeRequest($url, $vars) {
	if(function_exists('curl_init')) {
		return LinkITCurlOpen($url, $vars);
	} else if(ini_get('allow_url_fopen') && function_exists('stream_get_contents')) {
		return LinkITFOpen($url, $vars);
	} else {
		return LinkITFSockOpen($url, $vars);
	}
}

function LinkITAPILogin($email, $password) {
	$request = array('email' => $email, 'password' => $password);
	return LinkITMakeRequest(BASE_URL . "api/login", $request);
}

function LinkITApiUpload($params) {
	return LinkITMakeRequest(BASE_URL . "api/createPost", $params);
}

function LinkITApiDownload($params) {
	return LinkITMakeRequest(BASE_URL . "api/downloadPost", $params);
}

function LinkITApiGetOptions($params) {
	return LinkITMakeRequest(BASE_URL . "api/getOptions", $params);
}

function LinkITApiUpdateOptions($params) {
	return LinkITMakeRequest(BASE_URL . "api/updateOptions", $params);
}

function LinkITApiGetStats($params) {
  return LinkITMakeRequest(BASE_URL . "api/getStats", $params);
}

function LinkITApiRestoreDefaultSettings($params) {
  return LinkITMakeRequest(BASE_URL . "api/restoreDefaultSettings", $params);
}

function LinkITApiGetRandomKeywords() {
  return LinkITMakeRequest(BASE_URL . "api/getRandomKeywords", array());
}


?>