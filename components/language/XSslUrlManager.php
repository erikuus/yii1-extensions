<?php
/**
 * XSslUrlManager handles language parameter, secure and nonsecure routes.
 *
 * The following example shows how to set up XSslUrlManager
 * in your application configuration (config/main.php):
 * <pre>
 * 'urlManager'=>array(
 *     'class' => 'ext.components.language.XSslUrlManager',
 *     'urlFormat'=>'path',
 *     'showScriptName'=>true,
 *     'appendParams'=>false,
 *     'supportedLanguages'=>array('et','en'),
 *     'rules'=>array(
 *         '<language:\w{2}>' => 'site/index',
 *         '<language:\w{2}>/<_c:\w+>' => '<_c>',
 *         '<language:\w{2}>/<_c:\w+>/<_a:\w+>'=>'<_c>/<_a>',
 *         '<language:\w{2}>/<_m:\w+>' => '<_m>',
 *         '<language:\w{2}>/<_m:\w+>/<_c:\w+>' => '<_m>/<_c>',
 *         '<language:\w{2}>/<_m:\w+>/<_c:\w+>/<_a:\w+>' => '<_m>/<_c>/<_a>',
 *     ),
 *     'hostInfo' => 'http://example.com',
 *     'secureHostInfo' => 'https://example.com',
 *     'secureRoutes' => array(
 *         '',             // home page (no route in url)
 *         'site/login',   // site/login action
 *         'site/signup',  // site/signup action
 *         'settings',     // all actions of SettingsController
 *     ),
 * ),
 * </pre>
 *
 * @link http://www.yiiframework.com/wiki/407/url-management-for-websites-with-secure-and-nonsecure-pages
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.2.0
 */
class XSslUrlManager extends CUrlManager
{
	/**
	 * @var string $hostInfo the host info used in non-SSL mode
	 */
	public $hostInfo = 'http://localhost';
	/**
	 * @var string $secureHostInfo the host info used in SSL mode
	 */
	public $secureHostInfo = 'https://localhost';
	/**
	 * @var array $secureRoutes the list of routes that should work only in SSL mode.
	 * Each array element can be either a URL route (e.g. 'site/create') or a controller ID (e.g. 'settings').
	 * The latter means all actions of that controller should be secured. If you want all routes to work only
	 * in SSL mode, set $secureRoutes to array('*')
	 * Defaults to array()
	 */
	public $secureRoutes = array();
	/**
	 * @var array allowedLanguages the language codes that are suppported by application,
	 * defaults to array('et','en')
	 */
	public $supportedLanguages = array('et','en');

	private $_secureMap;

	public function createUrl($route, $params = array(), $ampersand = '&')
	{
		// add language param to url
		if(!isset($params['language']))
			$params['language']=Yii::app()->language;

		$url = parent::createUrl($route, $params, $ampersand);

		// If already an absolute URL, return it directly
		if(strpos($url, 'http') === 0)
			return $url;

		// Check if the current protocol matches the expected protocol of the route
		// If not, prefix the generated URL with the correct host info.
		$secureRoute = $this->isSecureRoute($route);

		if($this->isSecureConnection())
			return $secureRoute ? $url : $this->hostInfo . $url;
		else
			return $secureRoute ? $this->secureHostInfo . $url : $url;
	}


	public function parseUrl($request)
	{
		$route = parent::parseUrl($request);

		// Set application language
		$urlLanguage = Yii::app()->getRequest()->getParam('language');

		if($urlLanguage && in_array($urlLanguage, $this->supportedLanguages))
			Yii::app()->setLanguage($urlLanguage);

		// Perform a 301 redirection if the current protocol
		// does not match the expected protocol
		$secureRoute = $this->isSecureRoute($route);
		$sslRequest = $this->isSecureConnection();

		if ($secureRoute !== $sslRequest)
		{
			$hostInfo = $secureRoute ? $this->secureHostInfo : $this->hostInfo;

			if ((strpos($hostInfo, 'https') === 0) xor $sslRequest)
				$request->redirect($hostInfo . $request->url, true, 301);
		}
		return $route;
	}

	/**
	 * Return if the request is sent via secure channel (https).
	 * NOTE! We can not use Yii::app()->request->isSecureConnection
	 * as earlier versions of yii do not account for HTTP_X_FORWARDED_PROTO
	 * @return boolean if the request is sent via secure channel (https)
	 */
	function isSecureConnection()
	{
	    return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'],'on')===0 || $_SERVER['HTTPS']==1)
	        || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'],'https')===0;
	}

	/**
	 * @param string the URL route to be checked
	 * @return boolean if the give route should be serviced in SSL mode
	 */
	protected function isSecureRoute($route)
	{
		if($this->secureRoutes==array('*'))
			return true;

		if($this->_secureMap === null)
		{
			foreach($this->secureRoutes as $r)
				$this->_secureMap[strtolower($r)] = true;
		}

		$route = strtolower($route);

		if(isset($this->_secureMap[$route]))
			return true;
		else
			return ($pos = strpos($route, '/')) !== false && isset($this->_secureMap[substr($route, 0, $pos)]);
	}
}