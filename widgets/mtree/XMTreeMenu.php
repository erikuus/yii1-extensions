<?php
/**
 * XMTreeMenu class file.
 *
 * XMTreeMenu is an extension to CMenu that supports treeview using the mtree-plugin.
 *
 * The following example shows how to use XMTreeMenu:
 * <pre>
 * $this->widget('ext.widgets.mtree.XMTreeMenu', array(
 *     'items'=>array(
 *         array('label'=>'Africa','url'=>'#','items'=>array(
 *             array('label'=>'Algeria', 'url'=>'#'),
 *             array('label'=>'Marocco', 'url'=>'#'),
 *             array('label'=>'Libya', 'url'=>'#'),
 *             array('label'=>'Somalia', 'url'=>'#'),
 *             array('label'=>'Kenya', 'url'=>'#'),
 *             array('label'=>'Mauritania', 'url'=>'#'),
 *         )),
 *         array('label'=>'Arctica', 'url'=>'#'),
 *         array('label'=>'Antarctica', 'url'=>'#'),
 *     ),
 * ));
 * </pre>
 *
 * NOTE! Erik Uus <erik.uus@gmail.com> modified mtree.js so that
 * if click comes from checkbox/input there will be no folding
 *
 * @author Karl Ward
 * @link http://foundation.zurb.com/forum/posts/17104-plugin-mtree-menu
 */
Yii::import('zii.widgets.CMenu');
class XMTreeMenu extends CMenu
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		parent::init();
		$this->registerClientScript();
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions = array_merge($this->htmlOptions, array('class'=>'mtree'));
		else
			$this->htmlOptions['class'] .= ' mtree';

		$this->renderMenu($this->items);
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// register core script
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// publish assets
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register js
		$cs->registerScriptFile($assets.'/js/velocity.min.js', CClientScript::POS_END);
		$cs->registerScriptFile($assets.'/js/mtree.js', CClientScript::POS_END);

		// register css
		if($this->cssFile===null)
			$cs->registerCssFile($assets.'/css/mtree.css');
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);
	}
}