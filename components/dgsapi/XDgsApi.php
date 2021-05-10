<?php

/**
 * XDgsApi component enables to call DGS API
 *
 * @link https://www.ra.ee/dgs-api/doc/index
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XDgsApi extends CApplicationComponent
{
	/**
	 * @var string the url where API calls are sent to
	 */
	public $url;

	/**
	 * Send API request
	 * @param string $request the name of the request
	 * @param array $params parameters (name=>value) of the request
	 * @return array response
	 */
	public function request($request, $parameters=array())
	{
		// create request
		$handle=curl_init($this->url.$request);

		// set the payload
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);

		// return body only
		curl_setopt($handle, CURLOPT_HEADER, 0);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);

		// create errors on timeout and on response code >= 300
		curl_setopt($handle, CURLOPT_TIMEOUT, 45);
		curl_setopt($handle, CURLOPT_FAILONERROR, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);

		// set up verification
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

		// run
		$response=curl_exec($handle);
		$error=curl_error($handle);
		$errorNumber=curl_errno($handle);
		curl_close($handle);

		if($error)
			throw new Exception('CURL error: '.$response.':'.$error.': '.$errorNumber);

		return json_decode($response, true);
	}
}