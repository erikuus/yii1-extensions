<?php

/**
 * XVauLoginAction class file.
 *
 * XVauLoginAction makes use of the {@link XVauUserIdentity} to authenticate user based on VauID 2.0 protocol
 *
 * For example set up 'vauLogin' action inside actions() method of SiteController:
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
 * By setting 'authOptions' you can restrict access and sync user data {@see XVauUserIdentity::authenticate()}:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'vauLogin'=>array(
 *             'class'=>'ext.components.vauid.XVauLoginAction'
 *             'authOptions'=>array()
 *         )
 *     );
 * }
 * </pre>
 *
 * Note that you need to set login/logout links and login redirect action in your application as well.
 *
 * For that you are strongly encouraged to use following extensions:
 * <ul>
 * <li>{@link XVauHelper} for VAU login and logout links</li>
 * <li>{@link XVauRedirectLoginAction} for setting up login action</li>
 * <ul>
 *
 * But you can also set links as follows:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/login?v=2&s=user&remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'),
 *             'visible'=>Yii::app()->user->isGuest
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.$this->createAbsoluteUrl('/site/logout'),
 *             'visible'=>!Yii::app()->user->isGuest
 *         ),
 * ));
 * </pre>
 *
 * And you can redirect your 'login' action to VAU login page as follows:
 * <pre>
 * public function actionLogin()
 * {
 *     $this->redirect('http://www.ra.ee/vau/index.php/site/login?v=2&s=user&remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'));
 * }
 * </pre>
 *
 * @link http://www.ra.ee/apps/vauid/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauLoginAction extends CAction
{
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
		$controller=$this->getController();

		if(isset($_POST['postedData']))
		{
			Yii::import('ext.components.vauid.XVauUserIdentity');
			$identity=new XVauUserIdentity($_POST['postedData']);
			$identity->authenticate($this->authOptions);
			if($identity->errorCode==XVauUserIdentity::ERROR_NONE)
			{
				Yii::app()->user->login($identity);
				$this->controller->redirect($this->redirectUrl ? $this->redirectUrl : Yii::app()->user->returnUrl);
			}
			elseif($identity->errorCode==XVauUserIdentity::ERROR_ACCESS_DENIED)
				throw new CHttpException(403, 'You are not allowed to access this page.');
			else
			{
				if($this->enableLogging===true)
				{
					switch($identity->errorCode)
					{
						case XVauUserIdentity::ERROR_INVALID_DATA:
							Yii::log('Invalid VAU login request: '.$_POST['postedData'], CLogger::LEVEL_ERROR);
							break;
						case XVauUserIdentity::ERROR_EXPIRED_DATA:
							Yii::log('Expired VAU login request: '.$_POST['postedData'], CLogger::LEVEL_ERROR);
							break;
						case XVauUserIdentity::ERROR_SYNC_DATA:
							Yii::log('Failed VAU user data sync: '.$_POST['postedData'], CLogger::LEVEL_ERROR);
							break;
					}
				}
				throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
			}
		}
		else
			throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
	}
}