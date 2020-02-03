<?php

/**
 * XVauRedirectLoginAction class file.
 *
 * XVauRedirectLoginAction provides simple redirect action that can be used to implement authentication
 * based on VauID 2.0 protocol
 *
 * When user requests action that requires authentication, Yii framework by default redirects user
 * to 'site/login' action. In case of VAU login we need that this login action redirects user into
 * VAU login page.
 *
 * For example set up 'login' action inside actions() method of SiteController as follows:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'login'=>array(
 *             'class'=>'ext.components.vauid.XVauRedirectLoginAction'
 *         ),
 *     );
 * }
 * </pre>
 *
 * @link http://www.ra.ee/apps/vauid/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauRedirectLoginAction extends CAction
{
	/**
	 * @var string $route the name of the application route that will login user
	 * into application based on data that VAU will post back after successful
	 * login in VAU. Defaults to 'site/vauLogin'
	 */
	public $route='site/vauLogin';
	/**
	 * @var array $params parameters for route. Usually not needed.
	 */
	public $params=array();

	/**
	 * Redirects to VAU login page.
	 */
	public function run()
	{
		$controller=$this->getController();
		$remoteUrl=$controller->createAbsoluteUrl($this->route,$this->params);
		$controller->redirect('http://www.ra.ee/vau/index.php/site/login?remoteUrl='.$remoteUrl);
	}
}