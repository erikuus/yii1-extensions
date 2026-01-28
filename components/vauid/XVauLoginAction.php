<?php

/**
 * XVauLoginAction makes use of {@link XVauSecurityManager} and {@link XVauUserIdentity} to authenticate user based on VauID 2.0 protocol
 *
 * @link https://www.ra.ee/vau/index.php/et/vauid/doc/
 * @link https://github.com/erikuus/yii1-extensions/tree/master/components/vauid#readme
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

Yii::import('ext.components.vauid.XVauAccessDeniedException');

class XVauLoginAction extends CAction
{
	/**
	 * @var string $securityManagerName the name of the security manager component.
	 * Defaults to 'vauSecurityManager'.
	 */
	public $securityManagerName='vauSecurityManager';
	/**
	 * @var string $userIdentityClassName the name of the user identity class.
	 * Defaults to 'XVauUserIdentity'.
	 */
	public $userIdentityClassName='XVauUserIdentity';
	/**
	 * @var string $redirectUrl the url user will be redirected after successful login.
	 * If empty, Yii::app()->user->returnUrl will be used.
	 */
	public $redirectUrl;
	/**
	 * @var array $authOptions the authentication options
	 */
	public $authOptions=array();
	/**
	 * @var integer the number of seconds VAU postback is valid.
	 */
	public $requestLifetime=60;
	/**
	 * @var boolean $enableLogging whether to log failed login requests.
	 */
	public $enableLogging=false;

	/**
	 * Logins user into application based on data posted by VAU after successful login in VAU.
	 */
	public function run()
	{
		if(!isset($_POST['postedData']))
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');

		$securityManager=Yii::app()->getComponent($this->securityManagerName);
		if($securityManager)
			$jsonData=$securityManager->decrypt($_POST['postedData']);
		else
			throw new CException('The security manager component have to be defined in configuration file.');

		try
		{
			Yii::import('ext.components.vauid.'.$this->userIdentityClassName);
			$identity=new $this->userIdentityClassName($jsonData);
			$identity->authenticate($this->authOptions, $this->requestLifetime);
			Yii::app()->user->login($identity);
			$this->controller->redirect($this->redirectUrl ? $this->redirectUrl : Yii::app()->user->returnUrl);
		}
		catch(XVauAccessDeniedException $e)
		{
			throw new CHttpException(403,'You do not have the proper credential to access this page.');
		}
		catch(CException $e)
		{
			if($this->enableLogging)
				Yii::log($e->getMessage().PHP_EOL.$jsonData, CLogger::LEVEL_ERROR);
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}
}