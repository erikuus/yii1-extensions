<?php

/**
 * XDokobitLoginWidget class file.
 *
 * XDokobitLoginWidget embeds Dokobit Identity Gateway UI that allows to authenticate user without leaving website.
 *
 * XDokobitLoginWidget is meant to be used together with {@link XDokobitIdentity}, {@link XDokobitUserIdentity} and
 * {@link XDokobitLoginAction}. These classes provide a unified solution that enables to authenticate user by Dokobit
 * Identity Gateway and based on the data of authenticated user to authorize him/her to log into application.
 *
 * First define controller action that starts Dokobit Identity Gateway session and passes session token to the view
 * that embeds XDokobitLoginWidget.
 *
 * ```php
 * public function actionLogin()
 * {
 *     // create dokobit session
 *     $dokobitSessionData=Yii::app()->dokobitIdentity->createSession(array(
 *         'return_url'=>$this->createAbsoluteUrl('dokobitLogin')
 *     ));
 *
 *     // decode data
 *     $dokobitSessionData=CJSON::decode($dokobitSessionData);
 *
 *     // check data, get token
 *     $dokobitSessionToken=null;
 *     if($dokobitSessionData['status']=='ok')
 *         $dokobitSessionToken=$dokobitSessionData['session_token'];
 *     else
 *         Yii::app()->user->setFlash('failure', Yii::t('ui', 'Mobile ID, Smart Card and Smart-ID authentication methods are unavailable!'));
 *
 *     $this->render('login', array(
 *         'dokobitSessionToken'=>$dokobitSessionToken
 *     ));
 * }
 * ```
 *
 * Inside this view call widget that displays Dokobit Identity Gateway UI.
 *
 * ```php
 * $this->widget('ext.components.dokobit.identity.XDokobitLoginWidget', array(
 *     'sessionToken'=>$sessionToken,
 *     'options'=>array(
 *         'locale'=>'et',
 *         'primaryColor'=>'#0088cc'
 *     )
 * ));
 * ```
 *
 * Please refer to README.md for complete usage information.
 *
 * @link https://id-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitLoginWidget extends CWidget
{
	/**
	 * @var string $sessionToken the Dokobit Identity Gateway API session token
	 * @see XDokobitIdentity::createSession
	 */
	public $sessionToken;
	/**
	 * @var array the initial JavaScript options that should be passed to the BookReader
	 * Possible options include the following:
	 * - callback: function which will be called after successful authentication
	 * - host: host for API requests
	 * - locale: interface language [options "en", "lt", "lv", "et", "is", "ru"; defaults to "en"]
	 * - container:	selector of the main html element containing plugin [defaults to "#Dokobit-identity-container"]
	 * - useValidator: enable/disable default validator on form fields [defaults to true]
	 * - debug: enable/disable debugging mode [defaults to false]
	 * - logo: path for custom logo.
	 * - customBackground: custom background color for main container and input fields
	 * - primaryColor: primary color for buttons, links and inputs in HEX or RGBA format
	 */
	public $options;
	/**
	 * @var array $htmlOptions the HTML attributes for the container tag
	 * Defaults to array()
	 */
	public $htmlOptions=array();
	/**
	 * @var string $jsUrl the url to dokobit integration javascript that allows to authenticate user without leaving website
	 * This script will be added at the bottom of the page before body closing tag
	 * Defaults to 'https://id-sandbox.dokobit.com/js/dokobit-integration.min.js'
	 */
	public $jsUrl='https://id-sandbox.dokobit.com/js/dokobit-integration.min.js';
	/**
	 * @var boolean whether the widget is visible
	 * Defaults to true
	 */
	public $visible=true;

	/**
	 * Initializes the widget
	 */
	public function init()
	{
		if($this->visible)
		{
			// checks if required values are set
			if(!$this->sessionToken)
				throw new CException('"sessionToken" has to be set!');

			// finalize options
			$this->options['sessionToken']=$this->sessionToken;

			// finalize html options
			if(isset($this->htmlOptions['id']))
				$this->options['container']='#'.$this->htmlOptions['id'];
			else
				$this->htmlOptions['id']='Dokobit-identity-container';

			// register client scripts
			$this->registerClientScript();
			$this->registerClientScriptFiles();

			// render container open tag
			echo CHtml::openTag('div', $this->htmlOptions)."\n";
		}
	}

	/**
	 * Renders the close tag of the container
	 */
	public function run()
	{
		if($this->visible)
			echo CHtml::closeTag('div');
	}

	/**
	 * Register necessary inline client script.
	 */
	protected function registerClientScript()
	{
		$options=CJavaScript::encode($this->options);
		Yii::app()->clientScript->registerScript(__CLASS__, "
			var dokobitIdentity = new DokobitIdentity($options).init();
		", CClientScript::POS_END);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;

		// register core script
		$cs->registerCoreScript('jquery');

		// register dokobit integration javascript
		$cs->registerScriptFile($this->jsUrl, CClientScript::POS_END);
	}
}