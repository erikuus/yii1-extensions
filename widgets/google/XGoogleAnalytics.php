<?php
/**
 * XGoogleAnalytics class file
 *
 * Widget to implement a Google Analytics
 *
 * Example of usage:
 * <pre>
 * $this->widget('ext.widgets.google.XGoogleAnalytics', array(
 *     'visible'=>true,
 *     'tracker'=>'UA-4477704-X',
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XGoogleAnalytics extends CWidget
{
	/**
	 * @var string the tracker code as given in Google Analytics dashboard.
	 */
	public $tracker;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;

	public function run()
	{
		if(!$this->visible || !$this->tracker)
			return;

		$cs=Yii::app()->clientScript;

		// register js file
		// <script async src="https://www.googletagmanager.com/gtag/js?id={$this->tracker}"></script>
		$cs->registerScriptFile(
			"https://www.googletagmanager.com/gtag/js?id={$this->tracker}",
			CClientScript::POS_HEAD,
			array('async'=>'async')
		);

		// register js code
		$script =
<<<SCRIPT
<!--Start of GA Script-->
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$this->tracker}');
<!--End of GA Script-->
SCRIPT;

		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_HEAD);
	}
}