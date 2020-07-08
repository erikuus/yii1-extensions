<?php

/**
 * XDokobitDownloadAction class file.
 *
 * XDokobitDownloadAction downloads signed document file from Dokobit Documents Gateway server and passes downloaded
 * file data to callback function.
 *
 * XDokobitDownloadAction is meant to be used together with {@link XDokobitIframeWidget} and {@link XDokobitDocuments}.
 * These classes provide a unified solution that enables to digitally sign documents through Dokobit Documents Gateway.
 *
 * First configure dokobit documents component:
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
 * Then define dokobit download action in controller.
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitDownload'=>array(
 *             'class'=>'ext.components.dokobit.documents.XDokobitDownloadAction',
 *             'successCallback'=>'handleSuccess',
 *             'failureCallback'=>'handleFailure'
 *         )
 *     );
 * }
 * ```
 *
 * After successful signing redirect user to this download action.
 *
 * ```js
 * Isign.onSignSuccess = function() {
 *     $.post("path/to/dokobitDownload", {
 *         signing_token: "abcdefghij",
 *         callback_token: "klmnoprstuvw"
 *     }, function(data) {
 *         $("#result").html(data);
 *     });
 * };
 * ```
 *
 * Required post params
 * - signing_token: the token returned by Dokobit Documents Gateway API create signing request
 * - callback_token: the token that will be passed through to success or failure callback method
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
	 * @var boolean $flash whether to display flash message on error
	 * Defaults to true
	 */
	public $flash=true;
	/**
	 * @var string $flashKey the key identifying the flash message
	 * Defaults to 'dokobit.download.error'
	 */
	public $flashKey='dokobit.download.error';
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
	 * Downloads signed document file from Dokobit Documents Gateway server to application server
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
				$downloadUrl=$dokobitDocuments->getDownloadUrl($_GET['signing_token']);
			else
				throw new CHttpException(500,'Dokobit Documents Component not found.');

			// download file
			$data=$this->downloadFile($downloadUrl);
			if($data)
				$this->controller->{$this->successCallback}($_GET['signing_token'],$_POST['callback_token'],$data);
			else
				$this->controller->{$this->failureCallback}($_GET['signing_token'],$_POST['callback_token']);
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Download file
	 * @param string $url the download url
	 * @return string file contents
	 */
	protected function downloadFile($url)
	{
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data=curl_exec($ch);
		$error=curl_error($ch);
		curl_close($ch);

		// Log errors
		if($error)
		{
			$this->log('Failed to download file: '.var_export($error, true));
			$this->flash(Yii::t('XDokobitDownloadAction.documents', 'Failed to download file!'));
			return false;
		}

		return $data;
	}

	/**
	 * Log message
	 * @param string $message
	 */
	protected function flash($message)
	{
		if($this->flash===true)
			Yii::app()->user->setFlash($this->flashKey, $message);
	}

	/**
	 * Log message
	 * @param string $message
	 */
	protected function log($message)
	{
		if($this->log===true)
			Yii::log($message, $this->logLevel, $this->logCategory);
	}
}