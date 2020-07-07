<?php

/**
 * XDokobitDocuments class file.
 *
 * XDokobitDocuments is an application component that enables to request Dokobit Documents Gateway API.
 *
 * XDokobitDocuments is meant to be used together with {@link XDokobitIframeWidget} and {@link XDokobitDownloadAction}.
 * These classes provide a unified solution that enables to digitally sign documents through Dokobit Documents Gateway.
 *
 * Configuration example:
 *
 * ```php
 * 'components'=>array(
 *     'dokobitDocuments'=> array(
 *         'class'=>'ext.components.dokobit.documents.XDokobitDocuments',
 *         'apiAccessToken'=>'testgw_AabBcdEFgGhIJjKKlmOPrstuv',
 *         'apiBaseUrl'=>'https://gateway-sandbox.dokobit.com/api/'
 *     )
 * )
 * ```
 *
 * Please refer to README.md for complete usage information.
 *
 * @link https://gateway-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitDocuments extends CApplicationComponent
{
	/**
	 * @var string $apiAccessToken the Dokobit Documents Gateway API access token
	 */
	public $apiAccessToken;
	/**
	 * @var string $apiUrl the Dokobit Documents Gateway API base url
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
	 * Upload file to Dokobit Documents Gateway server.
	 *
	 * Required params:
	 * - file[name]: file name
	 * - file[digest]: SHA256 file hash
	 *
	 * Optional params:
	 * - file[url]: accesible file url for service to download
	 * - file[content]: base64 encoded file content. Could be used instead of file[url].
	 *
	 * Example of returned data:
	 *
	 * ```php
	 * array(
	 *     "status"=>"ok",
	 *     "token"=>"MFs8jeKFZCd9zUyHFXvm"
	 * )
	 * ```
	 *
	 * @link https://gateway-sandbox.dokobit.com/api/doc#_api_file_upload
	 * @param array $params request params
	 * @return array response
	 */
	public function uploadFile($params)
	{
		$url=$this->getRequestUrlByAction('file/upload');
		return $this->request($url, $params, true);
	}

	/**
	 * Checks uploaded file status in Dokobit Documents Gateway server.
	 *
	 * Example of returned data:
	 *
	 * ```php
	 * array(
	 *     "status"=>"uploaded"
	 * )
	 * ```
	 *
	 * @link https://gateway-sandbox.dokobit.com/api/doc#_api_file_upload_status
	 * @param string $uploadedFileToken the uploaded file token
	 * @return array response
	 */
	public function checkFileStatus($uploadedFileToken)
	{
		$url=$this->getRequestUrlByAction("file/upload/$uploadedFileToken/status");
		return $this->request($url, array('token'=>$uploadedFileToken));
	}

	/**
	 * Delete uploaded file from Dokobit Documents Gateway server.
	 *
	 * Example of returned data:
	 *
	 * ```php
	 * array(
	 *     "status"=>"ok"
	 * )
	 * ```
	 *
	 * @link https://gateway-sandbox.dokobit.com/api/doc#_api_file_delete
	 * @param string $uploadedFileToken the uploaded file token
	 * @return array response
	 */
	public function deleteFile($uploadedFileToken)
	{
		$url=$this->getRequestUrlByAction("file/$uploadedFileToken/delete");
		return $this->request($url, array('token'=>$uploadedFileToken), true);
	}

	/**
	 * Create new signing
	 *
	 * Required params:
	 * - type: signed document format [options: pdf|pdflt|bdoc|edoc|asice|adoc|adoc.cedoc|adoc.bedoc|adoc.gedoc|adoc.ggedoc|mdoc|mdoc.cedoc|mdoc.bedoc|mdoc.gedoc|mdoc.ggedoc]
	 * - name: signed document name
	 * - signers[0][id]: unique user identifier from your system
	 * - signers[0][name]: firstname of signer
	 * - signers[0][surname]: lastname of signer
	 * - files[0][token]: uploaded file token
	 *
	 * Optional params:
	 * - signers[0][phone]: signer's phone number
	 * - signers[0][code]: signer's personal code
	 * - signers[0][country_code]: signer's country code
	 * - signers[0][signing_options]: visible signing options for user [options: mobile|smartid|stationary]
	 * - language: Dokobit Documents Gateway UI language
	 * - postback_url: if set, Dokobit Documents Gateway sends notifications to given url
	 *
	 * Example of postback response:
	 *
	 * ```json
	 * {
	 *     "status": "ok",
	 *     "token": "MFs8jeKFZCd9zUyHFXvm",
	 *     "action": "signing_completed|signer_signed|signing_archived|signing_archive_failed",
	 *     "file": "https://developers.dokobit.com/sc/test.pdf",
	 *     "file_digest": "HEX encoded SHA256 file hash",
	 *     "valid_to": "2020-01-01 00:00:00"
	 *     "signer": "60001019906"
	 *     "signer_info": [
	 *         "code": "60001019906",
	 *         "phone": "+37000000766",
	 *         "country_code": "lt",
	 *         "signing_option": "mobile",
	 *         "type": "qes|aes|es (qualified electronic signature, advanced electronic signature, electronic signature)"
	 *     ]
	 * }
	 * ```
	 *
	 * For more optional params please refer to Dokobit Documents Gateway Documentation.
	 *
	 * Creating a new signing will result a response with status and signing token:
	 *
	 * ```php
	 * array(
	 *     "status"=>"ok",
	 *     "token"=>"MFs8jeKFZCd9zUyHFXvm",
	 *     "signers"=>array(
	 *         "51001091072"=>"7LXvLF8rAFBkGxpKWVti",
	 *         "51001091006"=>"fprWuPdhCPRQjum78UrG"
	 *     )
	 * )
	 * ```
	 *
	 * Note that Dokobit Documents Gateway will store files for 30 days. After this period
	 * signing with all associated files will be deleted automatically.
	 *
	 * @link https://gateway-sandbox.dokobit.com/api/doc#_api_signing_create
	 * @param array $params request params
	 * @return json response
	 */
	public function createSigning($params)
	{
		$url=$this->getRequestUrlByAction('signing/create');
		return $this->request($url, $params, true);
	}

	/**
	 * Get Dokobit Documents Gateway signing page url
	 * @param string $signingToken the token returned by create signing request
	 * @param string $signerAccessToken the signer token returned by create signing request
	 * @return string gateway signing page url
	 */
	public function getSigningUrl($signingToken, $signerAccessToken)
	{
		return $this->apiBaseUrl.'/signing/'.$signingToken.'?access_token='.$signerAccessToken;
	}

	/**
	 * Get Dokobit Documents Gateway download url
	 * @param string $signingToken the token returned by create signing request
	 * @return string url to download signed document file
	 */
	public function getDownloadUrl($signingToken)
	{
		return $this->apiBaseUrl.'/signing/'.$signingToken.'/download?access_token='.$this->apiAccessToken;
	}

	/**
	 * Get API request URL by action path
	 * @param string $action the api action
	 * @return string gateway api request url
	 */
	protected function getRequestUrlByAction($action)
	{
		return $this->apiBaseUrl.$action.'.json?access_token='.$this->apiAccessToken;
	}

	/**
	 * Send Dokobit Documents Gateway API request
	 * @param string $url the api request url
	 * @param array $params request params
	 * @param boolean $post whether to do a post request
	 * @return array response
	 */
	protected function request($url, $params=array(), $post=false)
	{
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, $post);

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
		$response=curl_exec($ch);
		$result=json_decode($response, true);
		return $result;
	}
}