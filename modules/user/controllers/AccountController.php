<?php
class AccountController extends Controller
{
	/**
	 * @var CActiveRecord the currently loaded data model instance.
	 */
	private $_model;

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
	 * Default action.
	 */
	public function actionIndex()
	{
		$model=$this->loadModel();
		$this->render('index',array(
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
				$this->redirect(array('index'));
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
				$this->redirect(array('index'));
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
	 * Returns the data model based on the authenticated user id.
	 * If the data model is not found, an HTTP exception will be raised.
	 */
	public function loadModel()
	{
		if($this->_model===null)
		{
			if(isset(Yii::app()->user->id))
				$this->_model=User::model()->findbyPk(Yii::app()->user->id);
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
