<?php

/*****************************************************/
/* Class:   WSSoapClient                             */
/* Author: Roger Veciana i Rovira                    */
/* Date: 2006-07-12                                  */
/*****************************************************/

/******************************************************************************************************************/
/* The class uses the PHP5 SOAP functions. For PHP4 you should try NuSOAP or the SOAP Pear extension              */
/* This class can be used to connect a SOAP server that asks you to use the WSSecurity authentication             */
/* I've tryed the class with an Axis 1.5 server using WSS4J.                                                      */
/* It's prepared to add the headers for UsernameToken, but other headers can be added with the same method easily */
/******************************************************************************************************************/

/**
 * Use: */
/*
include('WSSoapClient.class.php');
$url = "the WSDL address";
$client = new WSSoapClient($url);
$client->__setUsernameToken('user','passw');
$params=array(); //Put the service parameters here
$result=$client->__soapCall('method_name',$params);
print_r($result);//The easyest way to see the result.
*/

class WSSoapClient extends SoapClient
{
	private $username;
	private $password;

	/**
	 * Generates de WSSecurity header
	 */
	private function wssecurity_header()
	{
		/**
		 * The timestamp. The computer must be on time or the server you are
		 * connecting may reject the password digest for security.
		 */
		$timestamp = gmdate('Y-m-d\TH:i:s\Z');
		/**
		 * A random word. The use of rand() may repeat the word if the server is
		 * very loaded.
		 */
		$nonce = mt_rand();
		/**
		 * This is the right way to create the password digest. Using the
		 * password directly may work also, but it's not secure to transmit it
		 * without encryption. And anyway, at least with axis+wss4j, the nonce
		 * and timestamp are mandatory anyway.
		 */
		$passdigest = base64_encode(pack('H*',sha1(pack('H*', $nonce).pack('a*',$timestamp).pack('a*',$this->password))));

		$auth='
			<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
				<wsse:UsernameToken>
					<wsse:Username>'.$this->username.'</wsse:Username>
					<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$passdigest.'</wsse:Password>
					<wsse:Nonce>'.base64_encode(pack('H*',$nonce)).'</wsse:Nonce>
					<wsu:Created xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$timestamp.'</wsu:Created>
				</wsse:UsernameToken>
			</wsse:Security>
		';
		/**
		 * XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar.
		 * Using other codes such as SOAP_ENC, it's really difficult to set the
		 * correct namespace for the variables, so the axis server rejects the
		 * xml.
		 */
		$authvalues=new SoapVar($auth,XSD_ANYXML);
		$header=new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security",$authvalues,true);

		return $header;
	}

	/**
	 * It's necessary to call it if you want to set a different user and
	 * password
	 */
	public function __setUsernameToken($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Overwrites the original method adding the security header. As you can
	 * see, if you want to add more headers, the method needs to be modifyed
	 */
	public function __soapCall($function_name, $arguments, $options=null,$input_headers=null, $output_headers=null)
	{
		$result = parent::__soapCall($function_name, $arguments, $options,$this->wssecurity_header());
		return $result;
	}
}