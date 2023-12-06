<?php
/**
 * The class extends SoapClient with NTLM authentication
 *
 * Usage example:
 *
 * <pre>
 * // options for ssl in php 5.6
 * $opts = array(
 *     'ssl' => array(
 *         'ciphers'=>'RC4-SHA',
 *         'verify_peer'=>false,
 *         'verify_peer_name'=>false
 *     )
 * );
 *
 * $client=new PinalSoapClient("some.wsdl",array(
 *     'login'=>'some_name',
 *     'password'=>'some_password',
 *     'cache_wsdl'=>WSDL_CACHE_NONE,
 *     'cache_ttl'=>86400,
 *     'trace'=>true,
 *     'exceptions'=>true,
 *     'stream_context' => stream_context_create($opts)
 * ));
 * </pre>
 */
class PinalSoapClient extends SoapClient
{
	private $username;
	private $password;

	public function __construct($wsdl, array $options=array())
	{
		parent::__construct($wsdl, $options);

		if(array_key_exists('login',$options))
			$this->username=$options['login'];

		if(array_key_exists('password',$options))
			$this->password=$options['password'];
	}

	public function __doRequest($request, $location, $action, $version, $one_way=0)
	{
		$headers=array(
			'Method: POST',
			'Connection: Keep-Alive',
			'User-Agent: PHP-SOAP-CURL',
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "'.$action.'"',
		);

		$this->__last_request_headers=$headers;

		$ch=curl_init($location);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$request);
		curl_setopt($ch,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
		curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
		curl_setopt($ch,CURLOPT_USERPWD,$this->username.':'.$this->password);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch,CURLINFO_HEADER_OUT,true);
		$response=curl_exec($ch);

		// Absolutely stupid hack necessary to get around Microsoft invalid XML response that
		// occurs every once in a while
		// http://social.technet.microsoft.com/Forums/pl-PL/exchangesvrdevelopment/thread/a58d4400-12d5-4856-91c7-c6135035e147
		$response=str_replace('"&#x1;"','""',$response);

		return $response;
	}

	public function __getLastRequestHeaders()
	{
		return implode("n",$this->__last_request_headers)."n";
	}
}