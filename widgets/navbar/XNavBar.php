<?php
/**
 * XNavBar displays an single level navigation menu.
 *
 * XNavBar is simplified version of CMenu.
 *
 * The following example shows how to use XNavBar:
 * <pre>
 * $this->widget('ext.widgets.amenu.XNavBar', array(
 *     'items'=>array(
 *         array('label'=>'Home', 'url'=>array('site/index')),
 *         array('label'=>'Products', 'url'=>array('product/index'), 'linkOptions'=>array('onclick'=>'alert();')),
 *         array('label'=>'Login', 'url'=>array('site/login'), 'visible'=>Yii::app()->user->isGuest),
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.1
 */
class XNavBar extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var array list of menu items. Each menu item is specified as an array of name-value pairs.
	 * Possible option names include the following:
	 * <ul>
	 * <li>icon: string, specifies the menu item icon tag class.</li>
	 * <li>label: string, required, specifies the menu item label. When {@link encodeLabel} is true, the label
	 * will be HTML-encoded.</li>
	 * <li>badge: string, specifies the the menu item badge
	 * <li>url: string or array, optional, specifies the URL of the menu item. It is passed to {@link CHtml::normalizeUrl}
	 * to generate a valid URL. If this is not set, the menu item will be rendered as a span text.</li>
	 * <li>visible: boolean, optional, whether this menu item is visible. Defaults to true.
	 * This can be used to control the visibility of menu items based on user permissions.</li>
	 * <li>template: string, optional, the template used to render this menu item.
	 * In this template, the token "{menu}" will be replaced with the corresponding menu link or text.
	 * Please see {@link itemTemplate} for more details. This option has been available since version 1.1.1.</li>
	 * <li>linkOptions: array, optional, additional HTML attributes to be rendered for the link or span tag of the menu item.</li>
	 * </ul>
	 */
	public $items=array();
	/**
	 * @var array, optional, additional HTML attributes to be merged with
	 * linkOptions set for individual item in $this->item['linkOptions'].
	 */
	public $itemLinkOptions=array();
	/**
	 * @var string the template used to render an individual menu item. In this template,
	 * the token "{menu}" will be replaced with the corresponding menu link or text.
	 * If this property is not set, each menu will be rendered without any decoration.
	 * This property will be overridden by the 'template' option set in individual menu items via {@items}.
	 */
	public $itemTemplate;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var boolean whether the labels for menu items should be HTML-encoded. Defaults to true.
	 */
	public $encodeLabel=true;
	/**
	 * @var boolean whether the labels for menu items should have either label or icon but not both.
	 * Defaults to false.
	 */
	public $compact=false;
	/**
	 * @var string the menu's root container tag name. Defaults 'div'.
	 * If this property is set to null, no container is used.
	 */
	public $containerTag='div';
	/**
	 * @var string the CSS class for the widget container. Defaults to 'navbar'.
	 */
	public $containerCssClass='navbar';
	/**
	 * @var array HTML attributes for the menu's root container tag
	 */
	public $htmlOptions=array();
	/**
	 * @var string the CSS class to be appended to the active menu item.Defaults to 'active'.
	 */
	public $activeCssClass='active';
	/**
	 * @var string the CSS class to be appended to the menu item badge. Defaults to 'sup'.
	 */
	public $badgeCssClass='sup';

	/**
	 * Initializes the menu widget.
	 * This method mainly normalizes the {@link items} property.
	 * If this method is overridden, make sure the parent implementation is invoked.
	 */
	public function init()
	{
		if($this->visible)
		{
			$this->registerClientScript();
			$this->htmlOptions['id']=$this->getId();
			$this->items=$this->normalizeItems($this->items);
		}
	}

	/**
	 * Calls {@link renderMenu} to render the menu.
	 */
	public function run()
	{
		if($this->visible)
			$this->renderMenu($this->items);
	}

	/**
	 * Renders the menu items.
	 * @param array menu items. Each menu item will be an array with at least two elements: 'label' and 'url'.
	 * It may have optional elements: 'visible','linkOptions', 'template'.
	 */
	protected function renderMenu($items)
	{
		if(count($items))
		{
			if(!isset($this->htmlOptions['class']))
				$this->htmlOptions['class']=$this->containerCssClass;
			else
				$this->htmlOptions['class'].=' '.$this->containerCssClass;

			if($this->containerTag)
				echo CHtml::openTag($this->containerTag,$this->htmlOptions)."\n";

			$this->renderMenuItems($items);

			if($this->containerTag)
				echo CHtml::closeTag($this->containerTag);
		}
	}

	/**
	 * Recursively renders the menu items.
	 * @param array the menu items to be rendered recursively
	 */
	protected function renderMenuItems($items)
	{
		foreach($items as $item)
		{
			if($this->compact && isset($item['icon']) && $item['label'])
				unset($item['icon']);

			if($this->itemLinkOptions!==array())
				$item['linkOptions']=array_merge($item['linkOptions'], $this->itemLinkOptions);

			if(isset($item['active']) && $item['active'] && $this->activeCssClass)
			{
				if(empty($item['linkOptions']['class']))
					$item['linkOptions']['class']=$this->activeCssClass;
				else
					$item['linkOptions']['class'].=' '.$this->activeCssClass;
			}

			if(isset($item['icon']))
			{
				$icon='<i class="'.$item['icon'].'"></i>';
				$item['label']=$item['label'] ? $icon.' '.$item['label'].' ' : '&nbsp;'.$icon.'&nbsp;';
			}

			if(isset($item['badge']) && $item['badge'])
				$item['label'].=CHtml::tag('span',array('class'=>$this->badgeCssClass), $item['badge']);

			if(isset($item['url']))
				$menu=CHtml::link($item['label'],$item['url'],isset($item['linkOptions']) ? $item['linkOptions'] : array());
			else
				$menu=CHtml::tag('span',isset($item['linkOptions']) ? $item['linkOptions'] : array(), $item['label']);

			if(isset($this->itemTemplate) || isset($item['template']))
			{
				$template=isset($item['template']) ? $item['template'] : $this->itemTemplate;
				echo strtr($template,array('{menu}'=>$menu));
			}
			else
				echo $menu;
		}
	}

	/**
	 * Normalizes the items property.
	 * @param array the items to be normalized.
	 * @return array the normalized menu items
	 */
	protected function normalizeItems($items)
	{
		foreach($items as $i=>$item)
		{
			if(isset($item['visible']) && !$item['visible'])
			{
				unset($items[$i]);
				continue;
			}
			if($this->encodeLabel)
				$items[$i]['label']=CHtml::encode($item['label']);
		}
		return array_values($items);
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		if($this->cssFile===null)
		{
			$cssFile=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'navbar.css');
			Yii::app()->clientScript->registerCssFile($cssFile);
		}
		elseif($this->cssFile!==false)
			Yii::app()->clientScript->registerCssFile($this->cssFile);
	}
}