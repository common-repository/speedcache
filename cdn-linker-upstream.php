<?php

if (!function_exists('json_decode')) {
	// Using JSON from WP's TinyMCE we don't have to rely on the existence of PHP's "json_decode".
	include_once(ABSPATH."/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
}

/**
 * Gets data from a remote location.
 *
 * @return String or Boolean false if fetching failed
 */
function get_from_remote($url, $force_fopen = false) {
	$raw = '';
	if (function_exists('curl_init') && !$force_fopen) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $url);
		$raw = curl_exec($c);
		$http_response_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		if (!(200 <= $http_response_code && $http_response_code <= 206)) {
			$raw = false;
		}
		curl_close($c);
	} else {
		assert(ini_get('allow_url_fopen'));
		$ctx = stream_context_create(array('http' => array('timeout' => 2),
						   'https' => array('timeout' => 3)
						   ));
		$raw = @file_get_contents($url, 0, $ctx);
	}

	if (function_exists('mb_convert_encoding')) {
		$from_encoding = mb_detect_encoding($raw, 'UTF-8, ISO-8859-1', true);
		$raw = mb_convert_encoding($raw, 'UTF-8', $from_encoding);
	}

	return $raw;
}

/** Decodes a JSON string. */
function hash_from_json($str) {
	if (function_exists('json_decode')) {
		return json_decode($str, true);
	} else {
		$json_obj = new Moxiecode_JSON();
		return $json_obj->decode($str);
	}
}

/**
 * Generates a random token for Arcostream services.
 *
 * That token identifies a customer/site combination.
 * More specifically, it is linked to a single CDN bucker and its CNAME.
 *
 * @return String random token. between 19 and 24 characters long
 */
function generate_random_token() {
	return random_string(4).'-'.random_string(4).'-'.random_string(4).'-'.random_string(4);
}

/**
 * Represents a customer/token combination.
 *
 * Use this to get account validity and settings from the CDN Signup Automator.
 */
class TokenData
{
	/** String: the customer's token for this installation */
	var $token		= null;
	/** String: URL of the Automator, including protocoll prefix (such as 'http://') w/o trailing slash*/
	var $upstream		= null;
	/** Boolean: true if the token has data on the Automator's side. */
	var $exists		= null;

	// settings, if set by the Automator (else NULL)

	/** Boolean: true if a CDN bucket has been create for this customer/token */
	var $status_cdn		= null;
	/** Boolean: true if DNS CNAME has been created */
	var $status_dns		= null;
	/** String: status of the account - 'ok', 'cancelled' */
	var $status_account	= null;
	/** String: the DNS CNAME - also known as $cdn_url */
	var $cdn_url		= null;
	/** ISO 8601 formatted datetime (see also DIN 1355-1:2006) */
	var $paid_including	= null;
	/** timezone of 'paid_including' */
	var $paid_timezone	= null;
	/** Boolean: true if the account expires after the date, e.g. the user doesn't want it to renew */
	var $last_period	= null;
	/** Integer: usagr of the plan (e.g. total MB of the last 30 days) */
	var $traffic_used	= null;
	/** String: how the plan's traffic or bandwidth is measured */
	var $traffic_limits	= null;

	function __construct($token, $automator_url) {
		$this->token = $token;
		$this->upstream = $automator_url;
		$this->exists = false;
		$this->populate();
	}

	protected function get_data_from_upstream() {
		$raw = get_from_remote($this->upstream.'/status/'.$this->token);
		if (!!$raw && strstr($raw, 'status')) {
			return hash_from_json($raw);
		}
		return $raw;
	}

	protected function populate() {
		$j = $this->get_data_from_upstream();

		if (!$j || !isset($j['status'])) {
			$this->exists = false;
			$this->status_cdn = false;
			$this->status_dns = false;
			$this->status_account = 'unknown';
			$this->paid_including = false;
		} else {
			$this->exists = true;
			$this->cdn_url = $j['cdn_url'];
			$this->status_cdn = $j['status']['cdn'];
			$this->status_dns = $j['status']['dns'];
			$this->status_account = $j['status']['account'];

			if ( isset($j['paid']) ) {
				$this->paid_including = $j['paid']['including'];
				$this->paid_timezone = $j['paid']['timezone'];
				$this->last_period = $j['paid']['last_period'];
			} else {
				$this->paid_including = false;
			}

			if ( isset($j['traffic']) ) {
				$this->traffic_used = $j['traffic']['used'];
				$this->traffic_limits = $j['traffic']['interval'];
			}
		}
	}

}
