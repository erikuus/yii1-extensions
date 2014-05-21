<?php
/**
 * PageModule class file.
 *
 * PageModule is a module that provides pages or article management system.
 *
 * To use PageModule, you must include it as a module in the application configuration as follows:
 * <pre>
 * return array(
 *     'modules'=>array(
 *         'page'=>array(
 *             'class'=>'application.modules.page.PageModule',
 *             'dbConnectionString'=>'mysql:host=127.0.0.1;dbname=test',
 *             'dbUsername'=>'user',
 *             'dbPassword'=>'password',
 *         ),
 *     ),
 * )
 * </pre>
 *
 * With the above configuration, you will be able to access PageModule in your browser using
 * the following URL:
 * http://localhost/path/to/index.php?r=page
 *
 * If your application is using path-format URLs, you can then access PageModule via:
 * http://localhost/path/to/index.php/page
 *
 * You can plug into your application PageModule menu widget as follows:
 * <pre>
 * $this->widget('application.modules.page.components.PageMenuWidget');
 * </pre>
 *
 * Page module depends on following extensions:
 * - ext.behaviors.XReturnableBehavior
 * - ext.behaviors.XReorderBehavior
 * - ext.actions.XReorderAction
 * - ext.actions.XHEditorUpload
 * - ext.validators.XCompareRequiredValidator
 * - ext.widgets.grid.groupgridview.XGroupGridView
 * - ext.widgets.grid.reordercolumn.XReorderColumn
 * - ext.widgets.alert.XAlert
 * - ext.widgets.xheditor.XHeditor
 * - ext.widgets.fancybox.XFancyBox
 * - ext.widgets.form.XDynamicForm
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class PageModule extends CWebModule
{
	/**
	 * @var string the ID of the default controller for this module.
	 */
	public $defaultController='article';
	/**
	 * @var string the template used to render page modul. In this template,
	 * the token "{menu}" will be replaced with the PageMenuWidget,
	 * the token "{breadcrumbs}" will be replaced with the CBreadcrumbs widget,
	 * the token "{content}" will be replaced with the article content
	 */
	public $pageLayout='{menu}<br />{breadcrumbs}{content}';

	public $formSimpleRow='<div class="row">{content}</div>';

	public $formButtonsRow='<div class="row buttons">{content}</div>';
	/**
	 * @var string the module database connection string.
	 */
	public $dbConnectionString;
	/**
	 * @var string the module database username
	 */
	public $dbUsername;
	/**
	 * @var string the module database password
	 */
	public $dbPassword;
	/**
	 * @var string the name of the menu table
	 * Defaults to 'tbl_page_menu'.
	 */
	public $menuTableName='tbl_page_menu';
	/**
	 * @var string the name of the article table
	 * Defaults to 'tbl_page_article'.
	 */
	public $articleTableName='tbl_page_article';
	/**
	 * @var string css class for primary buttons
	 */
	public $primaryButtonCssClass;
	/**
	 * @var string css class for secondary buttons
	 */
	public $secondaryButtonCssClass;
	/**
	 * @var string list of XHeditor tools for menu content
	 * Possible values are also 'mini', 'simple', 'full'
	 */
	public $editorMenuTools='Bold,Link,Unlink,List,SelectAll,Removeformat,Source,Fullscreen';
	/**
	 * @var string list of XHeditor tools for article content
	 * Possible values are also 'mini', 'simple', 'full'
	 */
	public $editorArticleTools='Cut,Copy,Paste,Pastetext,|,Blocktag,Bold,Italic,Underline,FontColor,BackColor,Removeformat,SelectAll,|,Align,List,Outdent,Indent,|,Link,Unlink,Img,Template,Table,|,Source,Preview,Fullscreen';
	/**
	 * @var string the name of the root directory where editor uploads files
	 */
	public $editorUploadRootDir='upload';
	/**
	 * @var string the type of directory structure for uploaded files
	 * Possible values are [day- directory per day, month- directory per month, ext- directory per extension]
	 * Defaults to 'day'
	 */
	public $editorUploadDirStructure='day';
	/**
	 * @var integer the maximum upload size for files
	 * Defaults to 2097152 (=2MB)
	 */
	public $editorUploadMaxSize=2097152;
	/**
	 * @var string the list extensions that are allowed to be uploaded by editor
	 */
	public $editorUploadAllowedExtensions='pdf,txt,rar,zip';
	/**
	 * @var string the list image extensions that are allowed to be uploaded by editor
	 */
	public $editorUploadAllowedImageExtensions='jpg,jpeg,gif,png';
	/**
	 * @var mixed authorization item name (operation, a task or a role) that has access to restricted pages (pages that are not set as public routes).
	 * Defaults to false, meaning authorization component is not used at all and only admin user has access to restricted pages
	 */
	public $authItemName=false;
	/**
	 * @var string The base script URL for all module resources (e.g. javascript, CSS file, images).
	 * If NULL (default) the integrated module resources (which are published as assets) are used.
	 */
	public $baseScriptUrl;
	/**
	 * @var array the list of routes that are publicly accessible
	 */
	private $publicRoutes=array(
		'article/index',
	);

	/**
	 * Initializes the page module.
	 */
	public function init()
	{
		// import the module-level models and components
		$this->setImport(array(
			'page.models.*',
			'page.components.*',
			'ext.helpers.XHtml',
		));

		// set connection to module database
		if($this->dbConnectionString)
		{
			Yii::app()->setComponents(array(
				'pagedb'=>array(
					'class'=>'CDbConnection',
					'connectionString'=>$this->dbConnectionString,
					'username'=>$this->dbUsername,
					'password'=>$this->dbPassword,
				),
			));
		}


		// publish module assets
		if (!is_string($this->baseScriptUrl)) {
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('page.assets'));
		}
	}

	/**
	 * Performs access check to module.
	 * @param CController the controller to be accessed.
	 * @param CAction the action to be accessed.
	 * @return boolean whether the action should be executed.
	 */
	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			$route=$controller->id.'/'.$action->id;

			if($this->authItemName!==false)
				$this->checkAuthAccess($route);
			else
				$this->checkUserAccess($route);

			return true;
		}
		else
			return false;
	}

	/**
	 * Performs access check to allow only admin user to access nonpublic route.
	 * @param string the route.
	 * @return boolean whether the action should be executed.
	 */
	protected function checkUserAccess($route)
	{
		if(Yii::app()->user->isGuest && !in_array($route,$this->publicRoutes))
			Yii::app()->user->loginRequired();
		elseif(!Yii::app()->user->isGuest && !in_array($route,$this->publicRoutes) && Yii::app()->user->name!='admin')
			throw new CHttpException(403,'You are not allowed to access this page.');
		else
			return true;
	}

	/**
	 * Performs access check to allow only specific auth item to access nonpublic route.
	 * @param string the route.
	 * @return boolean whether the action should be executed.
	 */
	protected function checkAuthAccess($route)
	{
		if(Yii::app()->user->isGuest && !in_array($route,$this->publicRoutes))
			Yii::app()->user->loginRequired();
		elseif(!Yii::app()->user->isGuest && !in_array($route,$this->publicRoutes) && Yii::app()->user->name!='admin' && !Yii::app()->user->checkAccess($this->authItemName))
			throw new CHttpException(403,'You are not allowed to access this page.');
		else
			return true;
	}
}
