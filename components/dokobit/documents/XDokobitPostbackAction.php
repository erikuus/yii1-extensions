<?php

/**
 * XDokobitPostbackAction class file.
 *
 * XDokobitPostbackAction handles postback response from Dokobit Documents Gateway server and passes received
 * data to callback function.
 *
 * XDokobitPostbackAction is meant to be used together with {@link XDokobitDocuments} and {@link XDokobitIframeWidget}.
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
 * Then define dokobit postback action:
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitDownload'=>array(
 *             'class'=>'ext.components.dokobit.documents.XDokobitPostbackAction',
 *             'successCallback'=>'handlePostbackSuccess',
 *             'failureCallback'=>'handlePostbackFailure'
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
class XDokobitPostbackAction extends CAction
{
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
	 * Defaults to 'ext.components.dokobit.documents.XDokobitPostbackAction'
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
	 *                 'categories'=>'ext.components.dokobit.documents.XDokobitPostbackAction',
	 *             )
	 *         )
	 *     )
	 * )
	 * ```
	 */
	public $logCategory='ext.components.dokobit.documents.XDokobitPostbackAction';

	/**
	 * Receives postback data in JSON from Dokobit Documents Gateway server
	 * and passes it to callback function.
	 *
	 * Example of postback data:
	 * {
	 *    "status": "ok",
	 *    "token": "MFs8jeKFZCd9zUyHFXvm",
	 *    "action": "signing_completed|signer_signed|signing_archived|signing_archive_failed",
	 *    "file": "https://developers.dokobit.com/sc/test.pdf",
	 *    "file_digest": "HEX encoded SHA256 file hash",
	 *    "valid_to": "2020-01-01 00:00:00",
	 *    "signer": "60001019906",
	 *    "signer_info": {
	 *        "code": "60001019906",
	 *        "phone": "+37000000766",
	 *        "country_code": "lt",
	 *        "signing_option": "mobile",
	 *        "signing_time": "2020-01-01T00:00:00+02:00",
	 *        "type": "qes|aes|es (qualified electronic signature, advanced electronic signature, electronic signature)"
	 *    }
	 *}
	 */
	public function run()
	{
		try
		{
			$data=file_get_contents('php://input');
			if($data)
				$this->controller->{$this->successCallback}($_POST['callback_token'],$data);
			else
				$this->controller->{$this->failureCallback}($_POST['callback_token']);
		}
		catch(CException $e)
		{
			$this->log('Failed to handle : '.var_export($body, true));
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
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