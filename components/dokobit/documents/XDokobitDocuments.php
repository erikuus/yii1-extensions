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
 *     'dokobitDocuments'=>array(
 *         'class'=>'ext.components.dokobit.documents.XDokobitDocuments',
 *         'apiAccessToken'=>'testgw_AabBcdEFgGhIJjKKlmOPrstuv',
 *         'baseUrl'=>'https://gateway-sandbox.dokobit.com'
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
	 * @var string $baseUrl the Dokobit Documents Gateway base url
	 */
	public $baseUrl;
	/**
	 * @var boolean $log whether to log
	 * Defaults to false
	 */
	public $log=false;
	/**
	 * @var string $logLevel the level for log message
	 * Must be one of the following: [trace|info|profile|warning|error]
	 * Defaults to 'error'
	 */
	public $logLevel='error';
	/**
	 * @var string $logCategory the category for log message
	 * Defaults to 'ext.components.dokobit.documents.XDokobitDocuments'
	 * For example to log errors into separate file use configuration as follows:
	 *
	 * ```php
	 * 'components'=>array(
	 *     'log'=>array(
	 *         'class'=>'CLogRouter',
	 *         'routes'=>array(
	 *             array(
	 *                 'class'=>'CFileLogRoute',
	 *                 'levels'=>'error',
	 *                 'logFile'=>'dokobit_error.log',
	 *                 'categories'=>'ext.components.dokobit.documents.XDokobitDocuments',
	 *             )
	 *         )
	 *     )
	 * )
	 * ```
	 */
	public $logCategory='ext.components.dokobit.documents.XDokobitDocuments';

	/**
	 * Initializes the component.
	 * This method checks if required values are set
	 */
	public function init()
	{
		if(!$this->apiAccessToken)
			throw new CException('"apiAccessToken" has to be set!');

		if(!$this->baseUrl)
			throw new CException('"baseUrl" has to be set!');
	}

	/**
	 * Uploads multiple files to Dokobit Documents Gateway server.
	 * Note that this helper method sends base64 encoded file contents instead of urls.
	 * @param array $files the list of paths to files to be uploaded
	 * @return array the list of uploaded file tokens
	 */
	public function uploadFiles($files)
	{
		$uploadedFiles=array();

		foreach($files as $file)
		{
			$uploadFile=array(
				'name'=>basename($file),
				'digest'=>sha1_file($file),
				'content'=>base64_encode(file_get_contents($file))
			);

			$uploadResponse=$this->uploadFile(array(
				'file'=>$uploadFile
			));

			if($uploadResponse['status']!='ok')
			{
				$this->log('Failed to upload file: '.var_export($uploadResponse, true));
				return false;
			}

			// Note that there is no need to check file status here
			// as we are sending content instead of url

			array_push($uploadedFiles, array('token'=>$uploadResponse['token']));
		}
		return $uploadedFiles;
	}

	/**
	 * Uploads file to Dokobit Documents Gateway server.
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
	 * Deletes uploaded file from Dokobit Documents Gateway server.
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
	 * Creates new signing.
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
	 * @return array response
	 */
	public function createSigning($params)
	{
		$url=$this->getRequestUrlByAction('signing/create');
		return $this->request($url, $params, true);
	}

	/**
	 * Gets Dokobit Documents Gateway signing page url.
	 * @param string $signingToken the token returned by create signing request
	 * @param string $signerAccessToken the signer token returned by create signing request
	 * @return string gateway signing page url
	 */
	public function getSigningUrl($signingToken, $signerAccessToken)
	{
		return $this->baseUrl.'/signing/'.$signingToken.'?access_token='.$signerAccessToken;
	}

	/**
	 * Gets Dokobit Documents Gateway download url.
	 * @param string $signingToken the token returned by create signing request
	 * @return string url to download signed document file
	 */
	public function getDownloadUrl($signingToken)
	{
		return $this->baseUrl.'/api/signing/'.$signingToken.'/download?access_token='.$this->apiAccessToken;
	}

	/**
	 * Gets API request URL by action path.
	 * @param string $action the api action
	 * @return string gateway api request url
	 */
	protected function getRequestUrlByAction($action)
	{
		return $this->baseUrl.'/api/'.$action.'.json?access_token='.$this->apiAccessToken;
	}

	/**
	 * Sends Dokobit Documents Gateway API request.
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

	/**
	 * Logs message.
	 * @param string $message
	 */
	protected function log($message)
	{
		if($this->log===true)
			Yii::log($message, $this->logLevel, $this->logCategory);
	}
}