<?php
/*
 * Copyright (c) 2015 Girino Vey.
 *
 * This software is licenced under Girino's Anarchist License.
 *
 * Permission to use this software, modify and distribute it, or parts of
 * it, is granted to everyone who wishes provided that the  conditions
 * in the Girino's Anarchist License are met. Please read it on the link
 * bellow.
 *
 * The full license is available at: http://girino.org/license
 */

function api_query($url, array $req = array()) {

	$post_data = http_build_query($req, '', '&');
	if ($post_data) $url = "$url?$post_data";
	
	//print "Loading: $url\n";
	// our curl handle (initialize if required)
	static $ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Girino Generic API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	// run the query
	$res = curl_exec($ch);
	
	if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
	$dec = json_decode($res, true);
	if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
	
	return $dec;
}

function blockchain_info_query($method, $param) {
	$url = "https://blockchain.info/$method/$param?format=json";
	return api_query($url, array());
}

//$tmp = blockhain_info_query('address', '1AJbsFZ64EpEfS5UAjAfcUG8pH8Jn3rn1F');
//print_r($tmp)

?>
