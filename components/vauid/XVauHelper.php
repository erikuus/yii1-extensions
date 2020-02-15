<?php

/**
 * XVauHelper class
 *
 * This class provides helper methods for building login and logout urls that can be used
 * to implement authentication based on VauID 2.0 protocol
 *
 * First set import in main/config:
 * <pre>
 * 'import'=>array(
 *     'ext.components.vauid.XVauHelper'
 * ),
 * </pre>
 *
 * Then you can build login and logout links as follows, assuming that you have vauLogin
 * and logout actions in your site controller and you want to use 'user' scope:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>XVauHelper::loginUrl(),
 *             'visible'=>Yii::app()->user->isGuest
 *         ),
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>XVauHelper::logoutUrl(),
 *             'visible'=>!Yii::app()->user->isGuest
 *         )
 *     )
 * ));
 * </pre>
 *
 * If you need you can specify different VauID scope and login route:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>XVauHelper::loginUrl('user_role', '/site/loginVau'),
 *             'visible'=>Yii::app()->user->isGuest
 *         )
 *     )
 * ));
 * </pre>
 *
 * Note that login route should point to action based on {@link XVauLoginAction}!
 *
 * @link http://www.ra.ee/apps/vauid/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XVauHelper
{
	/**
	 * @param string $route to application VAU login action. Defaults to '/site/vauLogin'.
	 * @param array $params login url parameters. Defaults to array().
	 * @return string VAU login url
	 */
	public static function loginUrl($scope='user',$route='/site/vauLogin',$params=array())
	{
		return 'http://www.ra.ee/vau/index.php/site/login?v=2&s='.$scope.'&remoteUrl='.Yii::app()->createAbsoluteUrl($route,$params);
	}

	/**
	 * @param string $route to application logout action. Defaults to '/site/logout'.
	 * @param array $params logout url parameters. Mostly not needed.
	 * @return string VAU logout url
	 */
	public static function logoutUrl($route='/site/logout',$params=array())
	{
		return 'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.Yii::app()->createAbsoluteUrl($route,$params);
	}
}