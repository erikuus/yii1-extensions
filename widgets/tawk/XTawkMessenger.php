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
 *     'source'=>'https://embed.tawk.to/123456789/default',
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
	 * @var string $source the source for tawk javascript
	 */
	public $source;
	/**
	 * @var boolean $visible whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var array $pattern the pattern that current route must match to make widget visible.
	 * @see XTawkMessenger::checkRoute()
	 * Note that this is effective only if array is not empty and visible is set to true.
	 */
	public $pattern=array();

	/**
	 * Checks visibility
	 */
	public function run()
	{
		if(!$this->visible || !$this->source)
			return;

		if($this->pattern!==array() && !$this->checkRoute($this->pattern))
			return;

		// prepare widget code
		$script =
<<<SCRIPT
	<!--Start of Tawk.to Script-->
	var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
	(function(){
	var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
	s1.async=true;
	s1.src='{$this->source}';
	s1.charset='UTF-8';
	s1.setAttribute('crossorigin','*');
	s0.parentNode.insertBefore(s1,s0);
	})();
	<!--End of Tawk.to Script-->
SCRIPT;

		// register widget code
		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_END);
	}

	/**
	 * Check if the current route matches a given pattern
	 * @param array the pattern to be checked ('controller'=>array('action1','action2') or 'controller'=>array('*'))
	 * @return boolean whether the URL matches given pattern
	 */
	protected function checkRoute($pattern)
	{
		$route=$this->controller->getRoute();
		foreach($pattern as $controller=>$actions)
		{
			foreach($actions as $action)
			{
				if($action=='*' && $this->controller->uniqueID==$controller)
				   return true;
				elseif($route==$controller.'/'.$action)
				   return true;
			}
		}
		return false;
	}
}