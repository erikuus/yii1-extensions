<?php
/**
 * UserModule class file.
 *
 * UserModule is a module that provides user authentication and management.
 *
 * To use UserModule, you must include it as a module in the application configuration like the following:
 * <pre>
 * return array(
 *     'modules'=>array(
 *         'user'=>array(
 *             'class'=>'ext.modules.user.UserModule',
 *         ),
 *     ),
 * )
 * </pre>
 *
 * With the above configuration, you will be able to access UserModule in your browser using
 * the following URL:
 * http://localhost/path/to/index.php?r=user
 *
 * If your application is using path-format URLs, you can then access UserModule via:
 * http://localhost/path/to/index.php/user
 *
 * NOTE! Default page is 'My Account'. If you need to link to admin page, you can use
 * http://localhost/path/to/index.php/user/default/admin
 *
 * In order to make your application to use this module for user authentication,
 * you need to remove from your application file protected/components/UserIdentity.php
 * and you need to import in the application configuration the following:
 *
 * 'import'=>array(
 *     'ext.modules.user.models.*',
 *     'ext.modules.user.components.*',
 * ),
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class UserModule extends CWebModule
{
	/**
	 * @var string the name of the help table
	 * Defaults to 'user'.
	 */
	public $userTable='user';
	/**
	 * @var string the path to the layout
	 */
	public $userLayout;
	/**
	 * @var array a list of application portlets (className=>properties)
	 * that will be displayed on left panel
	 */
	public $leftPortlets=array();
	/**
	 * @var array a list of application portlets (className=>properties)
	 * that will be displayed on right panel
	 */
	public $rightPortlets=array();
	/**
	 * @var array a list of user group names
	 * Group name that user belongs to is saved to cookie on login.
	 * In application you can get this name as follows: Yii::app()->user->usergroup
	 */
	public $userGroups=array();
	/**
	 * @var mixed a rbac operation name that controls access to user management
	 * Defaults to false, meaning role based access control is not used at all and all
	 * authenticated users are allowed only to access their own account, ie. edit their own data.
	 * NOTE! Admin user is always given full access and delete is allowed only to admin user.
	 * NOTE! Nobody but admin user itself can edit admin user.
	 */
	public $rbac=false;

	private $publicPages=array();

	private $adminPages=array(
		'user/delete',
		'install/index',
		'install/create'
	);

	/**
	 * Initializes the lookup module.
	 */
	public function init()
	{
		// import the module-level models and components
		$this->setImport(array(
			'user.models.*',
			'user.components.*',
		));
	}

	/**
	 * Performs access check to user module.
	 * @param CController the controller to be accessed.
	 * @param CAction the action to be accessed.
	 * @return boolean whether the action should be executed.
	 */
	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			$route=$controller->id.'/'.$action->id;

			// allow only admin user to access admin actions
			if(Yii::app()->user->name!='admin' && in_array($route,$this->adminPages))
				throw new CHttpException(403,'You are not allowed to access this page.');

			// allow authenticated users access restricted pages
			if(Yii::app()->user->isGuest && !in_array($route,$this->publicPages))
				Yii::app()->user->loginRequired();
			else
				return true;

			return true;
		}
		else
			return false;
	}
}
