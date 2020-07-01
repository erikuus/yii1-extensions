<?php

/**
 * XDokobitLoginAction class file.
 *
 * XDokobitLoginAction makes use of {@link XDokobitIdentity} and {@link XDokobitUserIdentity} to login
 * user into application using the data of authenticated user returned by Dokobit Identity Gateway API.
 *
 * First configure dokobit identity component:
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
 * Then set up action in application controller:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction'
 *         )
 *     );
 * }
 * </pre>
 *
 * Please refer to {@link XDokobitLoginWidget} for complete usage information.
 *
 * @link https://id-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitLoginAction extends CAction
{
	/**
	 * @var string $componentName the name of the dokobit component
	 * Defaults to 'dokobitIdentity'.
	 */
	public $componentName='dokobitIdentity';
	/**
	 * @var string $redirectUrl the url user will be redirected after successful login
	 * If empty, Yii::app()->user->returnUrl will be used
	 */
	public $redirectUrl;
	/**
	 * @var array $authOptions the authentication options
	 * @see XDokobitUserIdentity::authenticate
	 */
	public $authOptions=array();
	/**
	 * @var boolean $flash whether to display flash message on error
	 * Defaults to true
	 */
	public $flash=true;
	/**
	 * @var string $flashKey the key identifying the flash message
	 * Defaults to dokobit
	 */
	public $flashKey='dokobit';
	/**
	 * @var boolean $log whether to log errors
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
	 * Defaults to 'ext.components.dokobit.identity.XDokobitLoginAction'
	 * For example to log errors into separate file use configuration as follows:
	 * 'components'=>array(
	 *     'log'=>array(
	 *         'class'=>'CLogRouter',
	 *         'routes'=>array(
	 *             array(
	 *                 'class'=>'CFileLogRoute',
	 *                 'levels'=>'error',
	 *                 'logFile'=>'dokobit_error.log',
	 *                 'categories'=>'ext.components.dokobit.identity.XDokobitLoginAction',
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public $logCategory='ext.components.dokobit.identity.XDokobitLoginAction';

	/**
	 * Logins user into application on the data of authenticated user returned by Dokobit Identity Gateway API
	 */
	public function run()
	{
		// get dokobit indentity api session token
		if(isset($_GET['session_token']))
		{
			// get dokobit component
			$dokobitIdentity=Yii::app()->getComponent($this->componentName);

			if($dokobitIdentity)
				$userData=$dokobitIdentity->getUserData($_GET['session_token']);
			else
				throw new CException('The "XDokobitIdentity" component have to be defined in configuration file.');

			Yii::import('ext.components.dokobit.identity.XDokobitUserIdentity');
			$identity=new XDokobitUserIdentity($userData);
			$identity->authenticate($this->authOptions);
			if($identity->errorCode==XDokobitUserIdentity::ERROR_NONE)
			{
				Yii::app()->user->login($identity);
				$this->controller->redirect($this->redirectUrl ? $this->redirectUrl : Yii::app()->user->returnUrl);
			}
			elseif($identity->errorCode==XDokobitUserIdentity::ERROR_UNAUTHORIZED)
				throw new CHttpException(403,'You do not have the proper credential to access this page.');
			else
			{
				$this->flash(Yii::t('XDokobitLoginAction.identity', 'Login failed!'));
				switch($identity->errorCode)
				{
					case XDokobitUserIdentity::ERROR_INVALID_DATA:
						$this->log('Invalid user data: '.$userData);
						break;
					case XDokobitUserIdentity::ERROR_INVALID_STATUS:
						$this->log('Invalid user status: '.$userData);
						break;
					case XDokobitUserIdentity::ERROR_EXPIRED_CERTIFICATE:
						$this->log('Expired certificate: '.$userData);
						break;
					case XDokobitUserIdentity::ERROR_SYNC_DATA:
						$this->log('Failed data sync: '.$userData);
						break;
					default:
						$this->log('Unknown error code: '.$identity->errorCode);
				}
			}
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
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