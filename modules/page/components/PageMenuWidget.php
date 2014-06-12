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
 *     'containerTagName'=>null,
 *     'listTagName'=>'dl',
 *     'listCssClass'=>'sub-nav',
 *     'labelTagName'=>'dt',
 *     'itemTagName'=>'dd'
 * ));
 * </pre>
 *
 * 4. Zurb Foundation CSS framework topbar dropdown {@link http://foundation.zurb.com/docs/components/topbar.html}
 * <nav class="top-bar" data-topbar>
 *     <section class="top-bar-section">
 *         <ul class="left">
 *             <li class="has-dropdown"><a href="#">Docs</a>
 *             <?php $this->widget('ext.modules.page.components.PageMenuWidget', array(
 *                 'enableAdminButton'=>false,
 *                 'containerTagName'=>null,
 *                 'listTagName'=>'ul',
 *                 'listCssClass'=>'dropdown',
 *                 'labelTemplate'=>'<label>{label}</label>',
 *             )); ?>
 *             </li>
 *         </ul>
 *     </section>
 * </nav>
 *
 * 5. Bootstrap CSS framework navigation {@link http://getbootstrap.com/components/#nav}
 * <pre>
 * $this->widget('ext.modules.page.components.PageMenuWidget', array(
 *     'containerTagName'=>null,
 *     'listCssClass'=>'nav nav-pills nav-stacked',
 *     'labelTemplate'=>'<h4>{label}</h4>',
 * ));
 * </pre>
 *
 * 6. Bootstrap CSS framework list group {@link http://getbootstrap.com/components/#list-group}
 * <pre>
 * $this->widget('ext.modules.page.components.PageMenuWidget', array(
 *     'containerTagName'=>null,
 *     'listCssClass'=>'list-group',
 *     'labelCssClass'=>'list-group-item',
 *     'itemCssClass'=>'list-group-item',
 *     'activeItemCssClass'=>'list-group-item',
 *     'labelTemplate'=>'<b>{label}</b>',
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class PageMenuWidget extends CWidget
{
	/**
	 * @var string the HTML tag name for the container of the widget.
	 * If set to null no conatiner is used.
	 * Defaults to 'div'.
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
	 * @var string the HTML tag name for the menu label. Defaults to 'li'.
	 */
	public $labelTagName='li';
	/**
	 * Default CSS class for the menu label.
	 */
	public $labelCssClass='page-menu-label';
	/**
	 * @var string the template used to render label. In this template,
	 * the token "{label}" will be replaced with the corresponding text.
	 */
	public $labelTemplate;
	/**
	 * @var string the HTML tag name for the menu list item. Defaults to 'li'.
	 */
	public $itemTagName='li';
	/**
	 * Default CSS class for the menu item.
	 */
	public $itemCssClass;
	/**
	 * Default CSS class for the active menu item. Defaults to 'active'.
	 */
	public $activeItemCssClass='active';
	/**
	 * Boolean whether to display admin button. Defaults to true.
	 */
	public $enableAdminButton=true;

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

		if($this->containerTagName)
			echo CHtml::openTag($this->containerTagName, $this->containerHtmlOptions);

			if($this->enableAdminButton)
				$this->printAdminButton();

			echo CHtml::openTag($this->listTagName, array('class'=>$this->listCssClass));

			foreach ($menuItems as $menu)
				echo $this->getMenuTag($menu);

			echo CHtml::closeTag($this->listTagName);

		if($this->containerTagName)
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
		{
			$formattedItem=$this->labelTemplate ? strtr($this->labelTemplate, array('{label}'=>$menu->formattedItem)) : $menu->formattedItem;
			return CHtml::tag($this->labelTagName, array('class'=>$this->labelCssClass), $formattedItem);
		}
		if($menu->id==Yii::app()->getRequest()->getParam('menuId'))
			return CHtml::tag($this->itemTagName, array('class'=>$this->activeItemCssClass), $menu->formattedItem)."\n";
		else
			return CHtml::tag($this->itemTagName, array('class'=>$this->itemCssClass), $menu->formattedItem)."\n";
	}
}