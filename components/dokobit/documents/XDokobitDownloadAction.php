<?php

/**
 * XDokobitDownloadAction class file.
 *
 * XDokobitDownloadAction downloads signed document file from Dokobit Documents Gateway server and passes downloaded
 * file data to callback function.
 *
 * XDokobitDownloadAction is meant to be used together with {@link XDokobitDocuments} and {@link XDokobitIframeWidget}.
 * These classes provide a unified solution that enables to digitally sign documents through Dokobit Documents Gateway.
 *
 * First configure dokobit documents component:
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
 * Then define dokobit download action:
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitDownload'=>array(
 *             'class'=>'ext.components.dokobit.documents.XDokobitDownloadAction',
 *             'successCallback'=>'handleDownloadSuccess',
 *             'failureCallback'=>'handleDownloadFailure'
 *         )
 *     );
 * }
 * ```
 *
 * Please refer to README.md for complete usage information.
 *
 * @link https://gateway-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitDownloadAction extends CAction
{
	/**
	 * @var string $componentName the name of the dokobit documents component
	 * Defaults to 'dokobitDocuments'.
	 */
	public $componentName='dokobitDocuments';
	/**
	 * @var string $successCallback the name of controller method this action calls after download success
	 */
	public $successCallback;
	/**
	 * @var string $failureCallback the name of controller method this action calls after download failure
	 */
	public $failureCallback;
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
	 * Defaults to 'ext.components.dokobit.documents.XDokobitDownloadAction'
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
	 *                 'categories'=>'ext.components.dokobit.documents.XDokobitDownloadAction',
	 *             )
	 *         )
	 *     )
	 * )
	 * ```
	 */
	public $logCategory='ext.components.dokobit.documents.XDokobitDownloadAction';

	/**
	 * Downloads signed document file from Dokobit Documents Gateway server and passes it's
	 * contents to callback function.
	 */
	public function run()
	{
		// check required params
		if(isset($_POST['signing_token']) && isset($_POST['callback_token']))
		{
			// get dokobit documents component
			$dokobitDocuments=Yii::app()->getComponent($this->componentName);

			// get download url by signing token
			if($dokobitDocuments)
				$downloadUrl=$dokobitDocuments->getDownloadUrl($_POST['signing_token']);
			else
				throw new CHttpException(500,'Dokobit Documents Component not found.');

			// download file contents and send to callback function
			$data=$this->downloadFile($downloadUrl);
			if($data)
				$this->controller->{$this->successCallback}($_POST['callback_token'],$data);
			else
				$this->controller->{$this->failureCallback}($_POST['callback_token']);
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Downloades file.
	 * @param string $url the download url
	 * @return string file contents
	 */
	protected function downloadFile($url)
	{
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSLVERSION, 6); // CURL_SSLVERSION_TLSv1_2
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data=curl_exec($ch);
		$error=curl_error($ch);
		curl_close($ch);

		if($error)
		{
			$this->log('Failed to download file: '.var_export($error, true));
			return false;
		}

		return $data;
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