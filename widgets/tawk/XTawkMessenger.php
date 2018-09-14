<?php
/**
 * XTawkMessenger class file
 *
 * Widget to implement tawk.to messaging app
 *
 * tawk.to is a free messaging app that lets you monitor and chat with
 * visitors on your website, mobile app or from a free customizable page
 *
 * Example of usage:
 * <pre>
 * $this->widget('ext.widgets.tawk.XTawkMessenger', array(
 *     'siteId'=>'123456789',
 *     'visible'=>true,
 * ));
 * </pre>
 *
 * @link https://www.tawk.to/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTawkMessenger extends CWidget
{
	/**
	 * @var string the site id as given in dashboard.tawk.to > administration > property settings.
	 */
	public $siteId;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;

	public function run()
	{
		if(!$this->visible || !$this->siteId)
			return;

		// prepare widget code
		$script =
<<<SCRIPT
	<!--Start of Tawk.to Script-->
	var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
	(function(){
	var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
	s1.async=true;
	s1.src='https://embed.tawk.to/{$this->siteId}/default';
	s1.charset='UTF-8';
	s1.setAttribute('crossorigin','*');
	s0.parentNode.insertBefore(s1,s0);
	})();
	<!--End of Tawk.to Script-->
SCRIPT;

		// register widget code
		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_END);
	}
}