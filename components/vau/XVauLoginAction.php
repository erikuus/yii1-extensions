<?php

/**
 * XVauLoginAction class file.
 *
 * XVauLoginAction logins user into application based on data posted by VAU after successful login in VAU
 *
 * XVauLoginAction makes use of the XVauUserIdentity class to identify user based on data posted by VAU after
 * successful login in VAU, and then logins user into application. If syncData is set to true, VAU user data
 * will be synced into local user table through User model.
 *
 * For usage set up 'vauLogin' action inside actions() method of SiteController:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'vauLogin'=>array(
 *             'class'=>'ext.components.vau.XVauLoginAction',
 *             'syncData'=>true,
 *         ),
 *     );
 * }
 * </pre>
 *
 * Place VAU login and logout links into your application. For example in MainMenu portlet:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/login?remoteUrl='.$this->controller->createAbsoluteUrl('/site/vauLogin'),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.$this->controller->createAbsoluteUrl('/site/logout'),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
 *
 * Then point your 'login' action to VAU login page:
 * <pre>
 * public function actionLogin()
 * {
 *     $this->redirect('http://www.ra.ee/vau/index.php/site/login?remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'));
 * }
 * </pre>
 *
 * As an alternative you are encouraged to use:
 * - XVauHelper for VAU login and logout links
 * - XVauRedirectLoginAction for setting up login action
 *
 * @link http://www.ra.ee/apps/remotelogin/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauLoginAction extends CAction
{
	/**
	 * @var boolean $syncData whether to sync VAU user data.
	 */
	public $syncData=false;
	/**
	 * @var boolean $enableLogging whether to log failed login requests.
	 */
	public $enableLogging=false;
	/**
	 * @var string $redirectUrl the url user will be redirected after successful login.
	 * If empty, Yii::app()->user->returnUrl will be used.
	 */
	public $redirectUrl=false;

	/**
	 * Logins user into application based on data posted by VAU after successful login in VAU.
	 */
	public function run()
	{
		$controller=$this->getController();

		if(isset($_POST['postedData']))
		{
			Yii::import('ext.components.vau.XVauUserIdentity');
			$identity=new XVauUserIdentity($_POST['postedData']);
			$identity->authenticate($this->syncData);
			if($identity->errorCode==XVauUserIdentity::ERROR_NONE)
			{
				Yii::app()->user->login($identity);
				$this->controller->redirect($this->redirectUrl ? $this->redirectUrl : Yii::app()->user->returnUrl);
			}
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