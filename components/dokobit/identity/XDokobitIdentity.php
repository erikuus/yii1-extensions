<?php

/**
 * XDokobitIdentity class file.
 *
 * XDokobitIdentity is an application component that enables to use Dokobit Identity Gateway API.
 *
 * Configuration example:
 * <pre>
 * 'components'=>array(
 *     'dokobitIdentity'=> array(
 *         'class'=>'ext.components.dokobit.identity.XDokobitIdentity',
 *         'apiAccessToken'=>'testid_AabBcdEFgGhIJjKKlmOPrstuv',
 *         'apiBaseUrl'=>'https://id-sandbox.dokobit.com/api/authentication/'
 *     )
 * )
 * </pre>
 *
 * Please refer to {@link XDokobitLoginWidget} for complete usage information.
 *
 * @link https://id-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitIdentity extends CApplicationComponent
{
	/**
	 * @var string $apiAccessToken the Dokobit Identity Gateway API access token
	 */
	public $apiAccessToken;
	/**
	 * @var string $apiUrl the Dokobit Identity Gateway API base url
	 */
	public $apiBaseUrl;

	/**
	 * Initializes the component.
	 * This method checks if required values are set
	 */
	public function init()
	{
		if(!$this->apiAccessToken)
			throw new CException('"apiAccessToken" has to be set!');

		if(!$this->apiBaseUrl)
			throw new CException('"apiBaseUrl" has to be set!');
	}

	/**
	 * Returns session token which is required for initiating javascript library and
	 * requesting authentication status using /api/authentication/{token}/status.
	 *
	 * Optional params include the following:
	 * - return_url: required if origin_host not set. Url to which user will be redirect after successful authentication
	 * - origin_host: required if return_url not set. Schema and host of your authentication page
	 * - code:	personal code to prefill Mobile ID or Smart-ID form data
	 * - country_code: country code to prefill Smart-ID form data
	 * - phone:	phone number to prefill Mobile ID form data
	 * - authentication_methods: authentications methods displayed for user, for example array(mobile, smartid, smartcard)
	 *
	 * Example of returned data:
	 * {
	 *    "status":"ok",
	 *    "session_token":"02f922c9917231ea8acbbbcf63796924af548c801d75772f2b1701b413462c61",
	 *    "url":"https://id-sandbox.dokobit.com/auth/02f922c9917231ea8acbbbcf63796924af548c801d75772f2b1701b413462c61"
	 * }
	 *
	 * @param array $params optional request params
	 * @return json response
	 */
	public function createSession($params)
	{
		$url=$this->apiBaseUrl.'create?access_token='.$this->apiAccessToken;
		return $this->request($url, $params, true);
	}

	/**
	 * Returns data of authenticated user by session token
	 *
	 * Example of returned data:
	 * {
	 *    "status":"ok",
	 *    "certificate":{
	 *        "name":"/C=LT/SN=SMART-ID/GN=DEMO/serialNumber=PNOLT-10101010005/CN=SMART-ID,DEMO,PNOLT-10101010005/OU=AUTHENTICATION",
	 *        "subject":{
	 *            "country":"LT",
	 *            "surname":"SMART-ID",
	 *            "name":"DEMO",
	 *            "serial_number":"PNOLT-10101010005",
	 *            "common_name":"SMART-ID,DEMO,PNOLT-10101010005",
	 *            "organisation_unit":"AUTHENTICATION"
	 *        },
	 *        "issuer":{
	 *            "country":"EE",
	 *            "organisation":"AS Sertifitseerimiskeskus",
	 *            "common_name":"TEST of EID-SK 2016"
	 *        },
	 *        "valid_from":"2017-08-30T15:08:15+03:00",
	 *        "valid_to":"2020-08-30T15:08:15+03:00",
	 *        "value":"LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUd6RENDQkxTZ0F3SUJBZ0lRTnIrZS9 ..."
	 *    },
	 *    "code":"10101010005",
	 *    "country_code":"lt",
	 *    "name":"DEMO",
	 *    "surname":"SMART-ID",
	 *    "authentication_method":"smartid",
	 *    "date_authenticated":"2019-05-06T12:15:34+03:00"
	 * }
	 *
	 * @param string $sessionToken the session token
	 * @return json response
	 */
	public function getUserData($sessionToken)
	{
		$url=$this->apiBaseUrl.$sessionToken.'/status?access_token='.$this->apiAccessToken;
		return $this->request($url);
	}

	/**
	 * Send Dokobit Identity Gateway API request
	 * @param string $url the api request url
	 * @param array $params request params
	 * @param boolean $post whether to do a post request
	 * @return json response
	 */
	protected function request($url, $params=array(), $post=false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, $post);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$fields='';
		if($post && $params!==array())
		{
			$fields=http_build_query($params);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}

		$requestHeaders=array(
			'Content-type: application/x-www-form-urlencoded',
			'Content-Length: '.strlen($fields),
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		return curl_exec($ch);
	}
}