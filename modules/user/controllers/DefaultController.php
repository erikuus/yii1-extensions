<?php
class DefaultController extends Controller
{
	/**
	 * @var CActiveRecord the currently loaded data model instance.
	 */
	private $_model;

	/**
	 * @var default action.
	 */
	public $defaultAction='admin';

	/**
	 * initialize the default portlets for the views
	 */
	public function init()
	{
		parent::init();

		$this->layout=Yii::app()->controller->module->userLayout;

		if(Yii::app()->controller->module->leftPortlets!==array())
			$this->leftPortlets=Yii::app()->controller->module->leftPortlets;

		if(Yii::app()->controller->module->rightPortlets!==array())
			$this->rightPortlets=Yii::app()->controller->module->rightPortlets;
	}

	/**
	 * Filter on all actions
	 * If RBAC is not used, allow admin user to access actions.
	 * If RBAC is used, allow admin user and users with given role to access actions.
	 * @param $c filter chain
	 */
	public function filterOnAllActions($c)
	{
		if(Yii::app()->user->name!='admin')
		{
			$rbac=Yii::app()->controller->module->rbac;

			if($rbac===false)
				throw new CHttpException(403,'You are not allowed to access this page.');

			if($rbac!==false && !Yii::app()->user->checkAccess($rbac))
				throw new CHttpException(403,'You are not allowed to access this page.');
		}
		$c->run();
	}

	/**
	 * Filter on 'update' and 'changePassword' actions
	 * Always allow only admin user to update admin user data
	 * @param $c filter chain
	 */
	public function filterOnUpdateAndPassword($c)
	{
		if(Yii::app()->user->name!='admin')
		{
			$model=$this->loadModel();
			if($model->username=='admin')
				throw new CHttpException(403,'You are not allowed to access this page.');
		}
		$c->run();
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'onAllActions',
			'onUpdateAndPassword+ update changePassword'
		);
	}

	/**
	 * Creates a new model.
	 */
	public function actionCreate()
	{
		$model=new User('create');

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			if($model->save())
				$this->redirect(array('admin'));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 */
	public function actionUpdate()
	{
		$model=$this->loadModel();
		$model->scenario='update';

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			if($model->save())
			{
				// using xreturnable extension to go back
				if(!$this->goBack())
					$this->redirect(array('admin'));
				else
					$this->goBack();
			}
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Changes a password of particular model.
	 */
	public function actionChangePassword()
	{
		$model=$this->loadModel();
		$model->scenario='changePassword';

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			if($model->save())
			{
				// using xreturnable extension to go back
				if(!$this->goBack())
					$this->redirect(array('admin'));
				else
					$this->goBack();
			}
		}
		else
		{
			$model->password=null;
			$model->repeatPassword=null;
		}

		$this->render('password',array(
			'model'=>$model,
		));
	}

	/**
	 * Manages all models.
	 * Handles search and filter requests
	 */
	public function actionAdmin()
	{
		$model=new User('search');

		if(isset($_GET['User']))
			$model->attributes=$_GET['User'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 */
	public function actionDelete()
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel()->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 */
	public function loadModel()
	{
		if($this->_model===null)
		{
			if(isset($_GET['id']))
				$this->_model=User::model()->findbyPk($_GET['id']);
			if($this->_model===null)
				throw new CHttpException(404,'The requested page does not exist.');
		}
		return $this->_model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='user-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
