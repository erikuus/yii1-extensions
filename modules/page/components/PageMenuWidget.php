<?php
/**
 * PageMenuWidget class file.
 *
 * This widget displays navigation list for page module
 *
 * Examples of usage:
 *
 * 1. Default side navigation
 * <pre>
 * $this->widget('application.modules.page.components.PageMenuWidget');
 * </pre>
 *
 * 2. Zurb Foundation CSS framework side navigation {@link http://foundation.zurb.com/docs/components/sidenav.html}
 * <pre>
 * $this->widget('ext.modules.page.components.PageMenuWidget', array(
 *     'listCssClass'=>'side-nav',
 * ));
 * </pre>
 *
 * 3. Zurb Foundation CSS framework sub navigation {@link http://foundation.zurb.com/docs/components/subnav.html}
 * <pre>
 * $this->widget('ext.modules.page.components.PageMenuWidget', array(
 *     'containerCssClass'=>null,
 *     'listTagName'=>'dl',
 *     'listCssClass'=>'sub-nav',
 *     'headerTagName'=>'dt',
 *     'itemTagName'=>'dd'
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class PageMenuWidget extends CWidget
{
	/**
	 * @var string the HTML tag name for the container of the widget. Defaults to 'div'.
	 */
	public $containerTagName='div';
	/**
	 * Default CSS class for the widget container.
	 */
	public $containerCssClass='page-menu';
	/**
	 * @var array HTML attributes for widget container. Defaults to array().
	 */
	public $containerHtmlOptions=array();
	/**
	 * @var string the HTML tag name for the menu list. Defaults to 'ul'.
	 */
	public $listTagName='ul';
	/**
	 * Default CSS class for the menu list.
	 */
	public $listCssClass='page-menu-list';
	/**
	 * @var string the HTML tag name for the menu header. Defaults to 'li'.
	 */
	public $headerTagName='li';
	/**
	 * Default CSS class for the menu header.
	 */
	public $headerCssClass='page-menu-header';
	/**
	 * @var string the HTML tag name for the menu list item. Defaults to 'li'.
	 */
	public $itemTagName='li';
	/**
	 * Default CSS class for the active menu item.
	 */
	public $activeItemCssClass='active';

	private $_module;

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		$this->_module=Yii::app()->getModule('page');

		// register css file
		if($this->_module->menuCssFile===null)
			Yii::app()->clientScript->registerCssFile($this->_module->baseScriptUrl.'/css/menu.css');
		else if($this->_module->menuCssFile!==false)
			Yii::app()->clientScript->registerCssFile($this->_module->menuCssFile);

		// set container html options
		if(isset($this->containerHtmlOptions['class']))
			$this->containerHtmlOptions['class'].=' '.$this->containerCssClass;
		else
			$this->containerHtmlOptions=array_merge($this->containerHtmlOptions, array('class'=>$this->containerCssClass));
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		$menuItems=PageMenu::model()->active()->findAll(array(
			'order'=>'position'
		));

		echo CHtml::openTag($this->containerTagName, $this->containerHtmlOptions);

			$this->printAdminButton();

			echo CHtml::openTag($this->listTagName, array('class'=>$this->listCssClass));

			foreach ($menuItems as $menu)
				echo $this->getMenuTag($menu);

			echo CHtml::closeTag($this->listTagName);

		echo CHtml::closeTag($this->containerTagName);
	}

	/**
	 * Prints admin button
	 */
	protected function printAdminButton()
	{
		if(!Yii::app()->user->isGuest && (Yii::app()->user->name=='admin' || Yii::app()->user->checkAccess($this->_module->authItemName)))
		{
			echo CHtml::link(
				CHtml::image($this->_module->baseScriptUrl.'/images/admin.png',Yii::t('PageModule.ui', 'Manage Menu')),
				array('/page/menu/admin'),
				array('class'=>'page-menu-admin')
			);
		}
	}

	/**
	 * @param active record of menu
	 * @return string html menu tag
	 */
	protected function getMenuTag($menu)
	{
		if($menu->type==PageMenu::TYPE_LABEL)
			return CHtml::tag($this->headerTagName, array('class'=>$this->headerCssClass),$menu->formattedItem);
		if($menu->id==Yii::app()->getRequest()->getParam('menuId'))
			return CHtml::tag($this->itemTagName, array('class'=>$this->activeItemCssClass),$menu->formattedItem);
		else
			return CHtml::tag($this->itemTagName, array(),$menu->formattedItem);
	}
}