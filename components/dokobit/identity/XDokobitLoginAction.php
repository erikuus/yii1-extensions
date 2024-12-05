<?php

/**
 * XDokobitLoginAction class file.
 *
 * XDokobitLoginAction authorizes user and logs him/her into application using the data of authenticated user
 * returned by Dokobit Identity Gateway API.
 *
 * XDokobitLoginAction is meant to be used together with {@link XDokobitIdentity}, {@link XDokobitUserIdentity}
 * and {@link XDokobitLoginWidget}. These classes provide a unified solution that enables to authenticate user by
 * Dokobit Identity Gateway and based on the data of authenticated user to authorize him/her to log into application.
 *
 * First configure dokobit identity component:
 *
 * ```php
 * 'components'=>array(
 *     'dokobitIdentity'=> array(
 *         'class'=>'ext.components.dokobit.identity.XDokobitIdentity',
 *         'apiAccessToken'=>'testid_AabBcdEFgGhIJjKKlmOPrstuv',
 *         'apiBaseUrl'=>'https://id-sandbox.dokobit.com/api/authentication/'
 *     )
 * )
 * ```
 *
 * Then define dokobit login action in controller. After successful authentication Dokobit Identity Gateway will
 * redirect user to this action. This action authorizes and logs user into application using the data of authenticated
 * user returned by Dokobit Identity Gateway API.
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
 *             'successUrl'=>$this->createUrl('index'),
 *             'failureUrl'=>$this->createUrl('login')
 *         )
 *     );
 * }
 * ```
 *
 * Note that in the above example after successful authentication only user session will be started in the application
 * with Yii::app()->user->id being set to <code>@<country_code>. This minimalist configuration is useful only for limited
 * cases, where there are no user data stored in application database. In most cases you need define authOption to authorize
 * athenticated user against database.
 *
 * Example 2:
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
 *             'successUrl'=>$this->createUrl('index'),
 *             'failureUrl'=>$this->createUrl('login')
 *             'authOptions'=>array(
 *                 'modelName'=>'User',
 *                 'codeAttributeName'=>'user_id_number',
 *                 'countryCodeAttributeName'=>'user_country_code',
 *                 'usernameAttributeName'=>'username',
 *             )
 *         )
 *     );
 * }
 * ```
 *
 * In the above example user is authorized against application database. "User" is the name of model that reads and writes
 * data form and to user table. Authenticated user is authorized to log into application only if there is a row in user table
 * where user_id_number=<code> and user_country_code=<country_code>. Yii::app()->user->id will be set to primary key value and
 * Yii::app()->user->name will be assigned the value of username column/attribute.
 *
 * However, there are cases when new user must be created in the application from the data of authenticated user returned
 * by Dokobit Identity Gateway API. In these cases 'enableCreate' should be set to true.
 *
 * Example 3:
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
 *             'successUrl'=>$this->createUrl('index'),
 *             'failureUrl'=>$this->createUrl('login')
 *             'authOptions'=>array(
 *                 'modelName'=>'User',
 *                 'codeAttributeName'=>'user_id_number',
 *                 'countryCodeAttributeName'=>'user_country_code',
 *                 'usernameAttributeName'=>'username',
 *                 'enableCreate'=>true,
 *                 'syncAttributes'=>array(
 *                     'name'=>'firstname',
 *                     'surname'=>'lastname',
 *                     'phone'=>'phone'
 *                 )
 *             )
 *         )
 *     );
 * }
 * ```
 *
 * In the above example, if there is no row in the aplication user table where user_id_number=<code> and user_country_code=<country_code>,
 * new user is inserted based on the data of authenticated user returned by Dokobit Identity Gateway API and according to the data mapping
 * given in 'authOptions'.
 *
 * But you may need to keep application data in sync with authenticated user data that may change in some cases
 * (for example name change in case of marriage).
 *
 * Example 4:
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
 *             'successUrl'=>$this->createUrl('index'),
 *             'failureUrl'=>$this->createUrl('login')
 *             'authOptions'=>array(
 *                 'modelName'=>'User',
 *                 'codeAttributeName'=>'user_id_number',
 *                 'countryCodeAttributeName'=>'user_country_code',
 *                 'usernameAttributeName'=>'username',
 *                 'enableCreate'=>true,
 *                 'enableUpdate'=>true,
 *                 'syncAttributes'=>array(
 *                     'name'=>'firstname',
 *                     'surname'=>'lastname',
 *                     'phone'=>'phone'
 *                 )
 *             )
 *         )
 *     );
 * }
 * ```
 *
 * In the above example, every time user is authorized and logged into application, application database (user table) is updated with
 * the data of authenticated user returned by Dokobit Identity Gateway API and according to the data mapping given in 'authOptions'.
 *
 * Please refer to README.md for complete usage information.
 *
 * @link https://id-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitLoginAction extends CAction
{
	/**
	 * @var string $componentName the name of the dokobit identity component
	 * Defaults to 'dokobitIdentity'.
	 */
	public $componentName='dokobitIdentity';
	/**
	 * @var string $successUrl the location this action redirects after login success
	 */
	public $successUrl;
	/**
	 * @var string $failureUrl the location this action redirects after login failure
	 */
	public $failureUrl;
	/**
	 * @var string $successCallback the name of controller method this action calls after login success
	 * This method is called only if $successUrl is not defined
	 */
	public $successCallback;
	/**
	 * @var string $failureCallback the name of controller method this action calls after login failure
	 * This method is called only if $failureUrl is not defined
	 */
	public $failureCallback;
	/**
	 * @var array $authOptions the authentication options
	 * @see XDokobitUserIdentity::authenticate()
	 */
	public $authOptions=array();
	/**
	 * @var string $userStateKey the key of user session variable that stores authentication method
	 * Defaults to 'dokobit'
	 */
	public $userStateKey='dokobit';
	/**
	 * @var boolean $flash whether to display flash message on error
	 * Defaults to true
	 */
	public $flash=true;
	/**
	 * @var string $flashKey the key identifying the flash message
	 * Defaults to 'dokobit.login.error'
	 */
	public $flashKey='dokobit.login.error';
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
	 * Defaults to 'ext.components.dokobit.identity.XDokobitLoginAction'
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
	 *                 'categories'=>'ext.components.dokobit.identity.XDokobitLoginAction',
	 *             )
	 *         )
	 *     )
	 * )
	 * ```
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
			// get dokobit identity component
			$dokobitIdentity=Yii::app()->getComponent($this->componentName);

			// get dokobit user data
			if($dokobitIdentity)
				$userData=$dokobitIdentity->getUserData($_GET['session_token']);
			else
				throw new CHttpException(500,'Dokobit Identity Component not found.');

			// authenticate user (create and update on demand)
			Yii::import('ext.components.dokobit.identity.XDokobitUserIdentity');
			$identity=new XDokobitUserIdentity($userData);
			$identity->authenticate($this->authOptions);
			if($identity->errorCode==XDokobitUserIdentity::ERROR_NONE)
			{
				// set state
				if($this->userStateKey)
					Yii::app()->user->setState($this->userStateKey, $identity->method);

				// login guest into application
				if(Yii::app()->user->isGuest)
					Yii::app()->user->login($identity);

				// redirect or callback on success
				if($this->successUrl)
					$this->controller->redirect($this->successUrl);
				else
					$this->controller->{$this->successCallback}($userData);
			}
			elseif($identity->errorCode==XDokobitUserIdentity::ERROR_UNAUTHORIZED)
				throw new CHttpException(403,'You do not have the proper credential to access this page.');
			else
			{
				// log errors
				switch($identity->errorCode)
				{
					case XDokobitUserIdentity::ERROR_INVALID_DATA:
						$this->log('Invalid user data: '.$userData);
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Authentication failed!'));
						break;
					case XDokobitUserIdentity::ERROR_INVALID_STATUS:
						$this->log('Invalid user status: '.$userData);
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Authentication failed! User information not received.'));
						break;
					case XDokobitUserIdentity::ERROR_EXPIRED_CERTIFICATE:
						$this->log('Expired certificate: '.$userData);
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Login failed! Certificate has expired.'));
						break;
					case XDokobitUserIdentity::ERROR_SYNC_DATA:
						$this->log('Failed data sync: '.$userData.' ErrorMessage: '.$identity->errorMessage);
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Login failed! Authentication was successfull, but data synchronization failed.'));
						break;
					case XDokobitUserIdentity::ERROR_UNAVAILABLE:
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Connecting failed! Personal identification code is already attached to another user account!'));
						break;
					default:
						$this->log('Unknown error code: '.$identity->errorCode);
						$this->flash(Yii::t('XDokobitLoginAction.identity', 'Login failed!'));
				}

				// redirect or callback on failure
				if($this->failureUrl)
					$this->controller->redirect($this->failureUrl);
				else
					$this->controller->{$this->failureCallback}();
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