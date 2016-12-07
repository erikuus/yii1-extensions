<?php

/**
 * XVauHelper class
 *
 * This class adds helper methods for building VAU login and logout urls
 *
 * For usage first set import in main/config:
 * <pre>
 * 'import'=>array(
 *     'ext.components.vau.XVauHelper',
 * ),
 * </pre>
 *
 * Then you can build login and logout links as follows:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>XVauHelper::loginUrl('/site/vauLogin'),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>XVauHelper::logoutUrl('/site/logout'),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
 *
 * @link http://www.ra.ee/apps/remotelogin/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XVauHelper
{
	/**
	 * @param string $route to application VAU login action. Defaults to '/site/vauLogin'.
	 * @param array $params parameters for route. Usually not needed.
	 * @return string VAU logout url
	 */
	public static function loginUrl($route='/site/vauLogin', $params=array())
	{
		return 'http://www.ra.ee/vau/index.php/site/login?remoteUrl='.Yii::app()->createAbsoluteUrl($route, $params);
	}

	/**
	 * @param string $route to application logout action. Defaults to '/site/logout'.
	 * @param array $params parameters for route. Usually not needed.
	 * @return string VAU logout url
	 */
	public static function logoutUrl($route='/site/logout', $params=array())
	{
		return 'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.Yii::app()->createAbsoluteUrl($route, $params);
	}
}