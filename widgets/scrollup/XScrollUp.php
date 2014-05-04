<?php

/**
 * Wrapper for Jquery ScrollUp {@link http://markgoodyear.com/labs/scrollup/} plugin
 *
 * Default Usage
 * <pre>
 * $this->widget('ext.widgets.scrollup.XScrollUp');
 * </pre>
 *
 * Usage with theme parameter
 * <pre>
 * $this->widget('ext.widgets.scrollup.XScrollUp', array(
 *     'theme' => 'pill' // pill, link, image, tab
 * ));
 * </pre>
 *
 * Usage with default optional parameters
 * <pre>
 * $this->widget('ext.widgets.scrollup.XScrollUp', array(
 *     'options' => array(
 *         'scrollName' => 'scrollUp', // Element ID
 *         'topDistance' => '300', // Distance from top before showing element (px)
 *         'topSpeed' => 300, // Speed back to top (ms)
 *         'animation' => 'fade', // Fade, slide, none
 *         'animationInSpeed' => 200, // Animation in speed (ms)
 *         'animationOutSpeed' => 200, // Animation out speed (ms)
 *         'scrollText' => 'Scroll to top', // Text for element
 *         'activeOverlay' => false, // Set CSS color to display scrollUp active point, e.g '#00FFFF'
 *     )
 * ));
 * </pre>
 *
 * @author turi
 * @link http://www.yiiframework.com/extension/escrollup/
 */
class XScrollUp extends CWidget
{
	/**
	 * @var string name of the theme to use
	 */
	public $theme='tab';
	/**
	 * @var array options array to pass jquery plugin
	 */
	public $options=array();

	/**
	 * Init widget
	 */
	public function init()
	{
		parent::init();

		$this->registerClientScript();
	}

	protected function registerClientScript()
	{
		$cs=Yii::app()->getClientScript();
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		switch($this->theme)
		{
			case 'tab':
				$cs->registerCssFile($assets.'/css/themes/tab.css');
				break;
			case 'link':
				$cs->registerCssFile($assets.'/css/themes/link.css');
				break;
			case 'image':
				$cs->registerCssFile($assets.'/css/themes/image.css');
				$this->options=array(
					'scrollImg'=>array(
						'active'=>true
					)
				);
				break;
			case 'pill':
				$cs->registerCssFile($assets.'/css/themes/pill.css');
				break;
			default:
				$cs->registerCssFile($assets.'/css/themes/tab.css');
				break;
		}

		$cs->registerCoreScript('jquery');
		$cs->registerScriptFile($assets.'/js/jquery.scrollUp.min.js',CClientScript::POS_END);
	}

	public function run()
	{
		$options=(isset($this->options)) ? $this->options : array();

		$js="$.scrollUp(".CJavaScript::encode($options).");";

		Yii::app()->clientScript->registerScript('scrollup',$js,CClientScript::POS_READY);
	}
}

?>
