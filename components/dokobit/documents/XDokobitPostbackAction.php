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
	 * Receives postback data in JSON from Dokobit Documents Gateway server,
	 * decodes it, and passes it to callback function.
	 *
	 * Examples of postback data that will be passed to success callback:
	 *
	 * ```php
	 * array
	 * (
	 *    'action' => 'signer_signed',
	 *    'token' => 'd73286680a815e4deb1cc895c01a1c8074499442',
	 *    'signer' => 132074,
	 *    'signer_info' => array
	 *        (
	 *            'code' => '37307302715',
	 *            'country_code' => 'EE',
	 *            'certificate' => array
	 *                (
	 *                    'name' => '/C=EE/CN=UU,ER,37307302715/SN=UU/GN=ER/serialNumber=PNOEE-37307302715',
	 *                    'subject' => array
	 *                        (
	 *                            'country' => 'EE',
	 *                            'common_name' => 'UU,ER,37307301111',
	 *                            'surname' => 'UU',
	 *                            'name' => 'ER',
	 *                            'serial_number' => 'PNOEE-37307301111',
	 *                        ),
	 *
	 *                    'issuer' => array
	 *                        (
	 *                            'country' => 'EE',
	 *                            'organisation' => 'SK ID Solutions AS',
	 *                            'common_name' => 'ESTEID2018',
	 *                        ),
	 *
	 *                    'valid_from' => '2022-03-18T09:06:06+02:00',
	 *                    'valid_to' => '2027-03-17T23:59:59+02:00',
	 *                    'value' => '...',
	 *                ),
	 *
	 *            'signing_time' => '2024-01-26T15:35:55+02:00',
	 *            'signing_option' => 'stationary',
	 *            'type' => 'qes',
	 *        ),
	 *
	 *    'status' => 'ok',
	 *    'file' => 'https://gateway.dokobit.com/api/signing/d73286680a815e4deb1cc895c01a1c8074499442/download/132074',
	 *    'file_digest' => 'fc5a551b1192f018d8eb23247a191cd7c9896b43688a8c2038752681b9d34cd0',
	 *    'valid_to' => '2035-02-24 00:00:00',
	 *    'signature_id' => 'S-6B361E74966B2A6224A2611F4C2304553C7F766982DEC9D461960650D377CD6A',
	 *)
	 * ```
	 *
	 * ```php
	 * array
	 * (
	 *     'action' => 'signing_completed',
	 *     'token' => 'd73286680a815e4deb1cc895c01a1c8074499442',
	 *     'status' => 'ok',
	 *     'file' => 'https://gateway.dokobit.com/api/signing/d73286680a815e4deb1cc895c01a1c8074499442/download',
	 *     'file_digest' => 'fc5a551b1192f018d8eb23247a191cd7c9896b43688a8c2038752681b9d34cd0',
	 *     'valid_to' => '2035-02-24 00:00:00',
	 * )
	 * ```
	 *
	 * @return array the postback data
	 */
	public function run()
	{
		try
		{
			$body=file_get_contents('php://input');
			$data=json_decode($body, true);
			if($data)
				$this->controller->{$this->successCallback}($data);
			else
				$this->controller->{$this->failureCallback}();
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