<?php
/**
 * PageMenuWidget class file.
 *
 * This widget displays bootstrap style navigation list
 *
 * Call this widget as follows:
 *
 * $this->widget('application.modules.page.components');
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
	 * Default CSS class for the menu header.
	 */
	public $headerCssClass='page-menu-header';
	/**
	 * Default CSS class for the active menu item.
	 */
	public $activeCssClass='active';
	/**
	 * @var array HTML attributes for widget container. Defaults to array('class'=>'page-menu').
	 */
	public $containerHtmlOptions=array();

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

			echo '<ul>';

			foreach ($menuItems as $menu)
				echo CHtml::tag('li',$this->getHtmlOptions($menu),$menu->formattedItem);

			echo '</ul>';

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
	 * @return array html options for menu list tag
	 */
	protected function getHtmlOptions($menu)
	{
		if($menu->type==PageMenu::TYPE_HEADER)
			return array('class'=>$this->headerCssClass);
		if($menu->id==Yii::app()->getRequest()->getParam('menuId'))
			return array('class'=>$this->activeCssClass);
		else
			return array();
	}
}