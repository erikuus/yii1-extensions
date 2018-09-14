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

		// prepare code
		$script =
<<<SCRIPT
	<!--Start of GA Script-->
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
	try {
	var pageTracker = _gat._getTracker("{$this->tracker}");
	pageTracker._trackPageview();
	} catch(err) {}
	<!--End of GA Script-->
SCRIPT;

		// register code
		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_END);
	}
}