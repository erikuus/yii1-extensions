<?php

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * JQuery plugin that displays a timeout popover after a certain period of time.
 * @see http://www.yiiframework.com/extension/timeout-dialog
 * @see https://github.com/digitick/yii-timeout-dialog
 *
 * @copyright © 2012 Digitick <www.digitick.net> (PHP)
 * @copyright © 2011 Rodrigo Neri <@rigoneri> (Javascript)
 * @license MIT
 * @version 1.1
 * @author Sopheak On
 * @author Ianaré Sévi
 */
class XTimeoutDialog extends CJuiWidget
{
	/**
	 * @var boolean $visible whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var integer The number of your session timeout (in seconds).
	 * The timeout value minus the countdown value determines how long until
	 * the dialog appears.
	 * Default: 1200
	 */
	public $timeout;
	/**
	 * @var integer The countdown total value (in seconds).
	 * Default: 60
	 */
	public $countdown;
	/**
	 * @var string The title message in the dialog box.
	 * Default: 'Your session is about to expire!'
	 */
	public $title;
	/**
	 * @var string The countdown message where {0} will be
	 * used to enter the countdown value.
	 * Default: 'You will be logged out in {0} seconds.'
	 */
	public $message;
	/**
	 * @var string The question message if they want to
	 * continue using the site or not.
	 * Default: 'Do you want to stay signed in?'
	 */
	public $question;
	/**
	 * @var string The text of the YES button to keep the session alive.
	 * Default: 'Yes, Keep me signed in'
	 */
	public $keepAliveButtonText;
	/**
	 * @var string The text of the NO button to kill the session.
	 * Default: 'No, Sign me out'
	 */
	public $signOutButtonText;
	/**
	 * @var string The url that will perform a GET request to keep the
	 * session alive. This GET expects a 'OK' plain HTTP response.
	 * Default: /keep-alive
	 */
	public $keepAliveUrl;
	/**
	 * @var string The url that will perform a POST request to display an error message.
	 * that your session has timed out and has been logged out.
	 * Default: null
	 */
	public $logoutUrl;
	/**
	 * @var string The redirect url after the logout happens, usually back
	 * to the login url. It will also contain a next query param with the url
	 * that they were when timedout and a timeout=t query param indicating
	 * if it was from a timeout, this value will not be set if the user clicked
	 * the 'No, Sign me out' button.
	 * Default: /
	 */
	public $logoutRedirectUrl;
	/**
	 * @var boolean A boolean value that indicates if the countdown will
	 * restart when the user clicks the 'keep session alive' button.
	 * Default: true
	 */
	public $restartOnYes;
	/**
	 * @var integer The width of the dialog box
	 * Default: 350
	 */
	public $dialogWidth;

	public function init()
	{
		parent::init();

		if($this->visible)
		{
			$cs = Yii::app()->getClientScript();
			$assets = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets');
			$cs->registerScriptFile($assets . '/js/timeout-dialog.js');
			$cs->registerCssFile($assets . '/css/timeout-dialog.css');
		}
	}

	public function run()
	{
		// check visibility
		if(!$this->visible)
			return;

		$options = array(
			'timeout' => $this->timeout,
			'countdown' => $this->countdown,
			'title' => $this->title,
			'message' => $this->message,
			'question' => $this->question,
			'keep_alive_button_text' => $this->keepAliveButtonText,
			'sign_out_button_text' => $this->signOutButtonText,
			'keep_alive_url' => $this->keepAliveUrl,
			'logout_url' => $this->logoutUrl,
			'restart_on_yes' => $this->restartOnYes,
			'logout_redirect_url' => $this->logoutRedirectUrl,
			'dialog_width' => $this->dialogWidth,
		);
		foreach($options as $key => $value)
		{
			if($value === null)
				unset($options[$key]);
		}
		$options = CJSON::encode($options);

		Yii::app()->getClientScript()->registerScript('TimeoutDialog', "$.timeoutDialog($options);", CClientScript::POS_READY);
	}

}
