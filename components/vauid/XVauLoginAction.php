<?php

/**
 * XVauLoginAction class file.
 *
 * XVauLoginAction makes use of {@link XVauSecurityManager} and {@link XVauUserIdentity} to authenticate user based on VauID 2.0 protocol
 *
 * First configure security manager component.
 * <pre>
 * 'components'=>array(
 *     'vauid'=> array(
 *         'class'=>'ext.components.vauid.XVauSecurityManager',
 *         'validationKey'=>'###'
 *     )
 * )
 * </pre>
 *
 * Now set up 'vauLogin' action in application SiteController.
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'vauLogin'=>array(
 *             'class'=>'ext.components.vauid.XVauLoginAction'
 *         )
 *     );
 * }
 * </pre>
 *
 * Next redirect your login action as follows.
 * <pre>
 * public function actionLogin()
 * {
 *     $this->redirect('http://www.ra.ee/vau/index.php/site/login?v=2&s=user&remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'));
 * }
 * </pre>
 *
 * Finally point logout link as follows.
 * <pre>
 * echo CHtml::link('Logout', 'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.Yii::app()->createAbsoluteUrl('site/logout'));?>
 * </pre>
 *
 * @link http://www.ra.ee/apps/vauid/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauLoginAction extends CAction
{
	/**
	 * @var string $securityManagerName the name of the  vauid security manager {@link XVauUserIdentity}.
	 * Defaults to 'vauSecurityManager'.
	 */
	public $securityManagerName='vauSecurityManager';
	/**
	 * @var string $redirectUrl the url user will be redirected after successful login.
	 * If empty, Yii::app()->user->returnUrl will be used.
	 */
	public $redirectUrl;
	/**
	 * @var array $authOptions the authentication options
	 * @see XVauUserIdentity::authenticate()
	 */
	public $authOptions=array();
	/**
	 * @var boolean $enableLogging whether to log failed login requests.
	 */
	public $enableLogging=false;

	/**
	 * Logins user into application based on data posted by VAU after successful login in VAU.
	 */
	public function run()
	{
		if(isset($_POST['postedData']))
		{
			$securityManager=Yii::app()->getComponent($this->securityManagerName);

			if($securityManager)
				$jsonData=$securityManager->decrypt($_POST['postedData']);
			else
				throw new CException('The "securityManager" component have to be defined in configuration file.');

			Yii::import('ext.components.vauid.XVauUserIdentity');
			$identity=new XVauUserIdentity($jsonData);
			$identity->authenticate($this->authOptions);
			if($identity->errorCode==XVauUserIdentity::ERROR_NONE)
			{
				Yii::app()->user->login($identity);
				$this->controller->redirect($this->redirectUrl ? $this->redirectUrl : Yii::app()->user->returnUrl);
			}
			elseif($identity->errorCode==XVauUserIdentity::ERROR_UNAUTHORIZED)
				throw new CHttpException(403,'You do not have the proper credential to access this page.');
			else
			{
				if($this->enableLogging===true)
				{
					switch($identity->errorCode)
					{
						case XVauUserIdentity::ERROR_INVALID_DATA:
							Yii::log('Invalid VAU login request: '.$jsonData,CLogger::LEVEL_ERROR);
							break;
						case XVauUserIdentity::ERROR_EXPIRED_DATA:
							Yii::log('Expired VAU login request: '.$jsonData,CLogger::LEVEL_ERROR);
							break;
						case XVauUserIdentity::ERROR_SYNC_DATA:
							Yii::log('Failed VAU user data sync: '.$jsonData,CLogger::LEVEL_ERROR);
							break;
						default:
							Yii::log('Unknown error code: '.$identity->errorCode, CLogger::LEVEL_ERROR);
					}
				}
				throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
			}
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}
}