<?php

/**
 * XDokobitLoginWidget class file
 *
 * XDokobitLoginWidget embeds Dokobit Identity Gateway UI that allows to authenticate user without leaving website
 *
 * XDokobitLoginWidget is meant to be used together with {@link XDokobitIdentity}, {@link XDokobitUserIdentity} and
 * {@link XDokobitLoginAction}. Together these classes provide a solution that enables to authenticate user by
 * Dokobit Identity Gateway and based on the data of authenticated user to log him/her into application.
 *
 * The following shows how to use XDokobitLoginWidget.
 *
 * First configure dokobit component.
 *
 * <pre>
 * 'components'=>array(
 *     'dokobit'=> array(
 *         'class'=>'ext.components.dokobit.identity.XDokobitIdentity',
 *         'apiAccessToken'=>'testid_AabBcdEFgGhIJjKKlmOPrstuv',
 *         'apiBaseUrl'=>'https://id-sandbox.dokobit.com/api/authentication/'
 *     )
 * )
 * </pre>
 *
 * Then define dokobit login action in controller. After successful identification Dokobit Identity Gateway
 * will redirect user to this action. This action logs user into application using the data of
 * authenticated user returned by Dokobit Identity Gateway API.
 *
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'dokobitLogin'=>array(
 *             'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
 *             'authOptions'=>array(
 *                 'modelName'=>'Kasutaja',
 *                 'scenarioName'=>'dokobit',
 *                 'codeAttributeName'=>'isikukood',
 *                 'countryCodeAttributeName'=>'riigikood',
 *                 'usernameAttributeName'=>'kasutajanimi',
 *                 'birthdayAttributeName'=>'birthday',
 *                 'enableCreate'=>true,
 *                 'enableUpdate'=>true,
 *                 'syncAttributes'=>array(
 *                     'eesnimi'=>'name',
 *                     'perekonnanimi'=>'surname',
 *                     'autentimise_meetod'=>'authentication_method',
 *                     'telefon'=>'phone'
 *                 ),
 *             )
 *         )
 *     );
 * }
 * </pre>
 *
 * Now define application login action that starts Dokobit Identity Gateway session.
 *
 * <pre>
 * public function actionLogin()
 * {
 *     $sessionData=Yii::app()->dokobit->createSession(array(
 *         'return_url'=>$this->createUrl('dokobitLogin')
 *     ));
 *
 *     $sessionData=CJSON::decode($userData);
 *
 *     if($sessionData['status']!='ok')
 *         throw new CHttpException(401);
 *
 *     $this->render('login', array(
 *         'sessionToken'=>$sessionData['session_token']
 *     ));
 * }
 * </pre>
 *
 * And inside login view call widget.
 *
 * <pre>
 * $this->widget('ext.components.dokobit.identity.XDokobitLoginAction', array(
 *     'sessionToken'=>$sessionToken,
 *     'options'=>array(
 *         'locale'=>'et',
 *         'primaryColor'=>'#0088cc'
 *     )
 * ));
 * </pre>
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
	 * - locale	String	en	Interface language [options "en", "lt", "lv", "et", "is", "ru"; defaults to "en"]
	 * - container:	selector of the main html element containing plugin [defaults to "#Dokobit-identity-container"]
	 * - useValidator: Enable/disable default validator on form fields [defaults to true]
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
	 * Initializes the widget
	 */
	public function init()
	{
		// checks if required values are set
		if(!$this->sessionToken)
			throw new CException('"sessionToken" has to be set!');

		// finalize options
		$this->options['sessionToken']=$this->sessionToken;

		// finalize html options
		if(isset($this->htmlOptions['id']))
			$this->options['container']='#'.$this->htmlOptions['id'];

		// register client scripts
		$this->registerClientScript();
		$this->registerClientScriptFiles();

		// render container open tag
		echo CHtml::openTag('div', $this->htmlOptions)."\n";
	}

	/**
	 * Renders the close tag of the container
	 */
	public function run()
	{
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