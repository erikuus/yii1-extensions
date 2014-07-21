<?php

class MenuController extends PageController
{
	/**
	 * @var string the default action for the controller.
	 */
	public $defaultAction='admin';

	/**
	 * @var CActiveRecord the currently loaded data model instance.
	 */
	private $_model;

	/**
	 * @return array actions
	 */
	public function actions()
	{
		return array(
			'reorder'=>array(
				'class'=>'ext.actions.XReorderAction',
				'modelName'=>'PageMenu'
			),
		);
	}

	/**
	 * Redirects to articles by refcode
	 */
	public function actionRedirect($refcode)
	{
		$model=PageMenu::model()->active()->findByAttributes(array('refcode'=>$refcode));
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		$this->redirect(array('/page/article/index','topic'=>$model->generateUniqueSlug()));
	}

	/**
	 * Creates a new model.
	 */
	public function actionCreate()
	{
		$model=new PageMenu;

		if(!$this->module->slugIdPrefix)
			$model->scenario='noSlugIdPrefix';

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['PageMenu']))
		{
			$model->attributes=$_POST['PageMenu'];
			if($model->save())
			{
				Yii::app()->user->setFlash('save.success',Yii::t('PageModule.ui','Menu successfully created!'));

				// using xreturnable extension to go back
				if(!$this->goBack())
					$this->redirect(array('admin'));
				else
					$this->goBack();
			}
		}
		else
			$model->url='http://';

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

		if(!$this->module->slugIdPrefix)
			$model->scenario='noSlugIdPrefix';

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['PageMenu']))
		{
			$model->attributes=$_POST['PageMenu'];
			if($model->save())
			{
				Yii::app()->user->setFlash('save.success',Yii::t('PageModule.ui','Menu successfully updated!'));

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
	 * Deletes a particular model.
	 */
	public function actionDelete()
	{
		if(Yii::app()->request->isPostRequest)
		{
			$model=$this->loadModel();
			$model->scenario='softDelete'; // needed by reorder behavior
			$model->deleted=1;
			$model->update('deleted');

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$this->render('admin',array(
			'dataProvider'=>PageMenu::model()->dataProvider,
		));
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
				$this->_model=PageMenu::model()->active()->findbyPk($_GET['id']);
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
		if(isset($_POST['ajax']) && $_POST['ajax']==='menu-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
