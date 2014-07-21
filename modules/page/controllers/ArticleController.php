<?php

class ArticleController extends PageController
{
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
				'modelName'=>'PageArticle'
			),
		);
	}

	/**
	 * Creates dialog page for xheditor template plugin.
	 */
	public function actionTemplate()
	{
		$this->layout='dialog';
		$this->render('template');
	}

	/**
	 * Creates a new model.
	 * @param integer menu id
	 */
	public function actionCreate($menuId=null)
	{
		$model=new PageArticle;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['PageArticle']))
		{
			$model->attributes=$_POST['PageArticle'];
			if($model->save())
			{
				// using xreturnable extension to go back
				if(!$this->goBack())
					$this->redirect(array('admin'));
				else
					$this->goBack();
			}
		}
		elseif($menuId)
			$model->menu_id=$menuId;

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

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['PageArticle']))
		{
			$model->attributes=$_POST['PageArticle'];
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
	 * List models.
	 * @param string topic menu slug
	 */
	public function actionIndex($topic=null)
	{
		$this->layout='page';

		if($topic)
			$menuId=PageMenu::model()->getIdFromSlug($topic, $this->module->slugIdPrefix);
		else
			$menuId=PageMenu::model()->firstItemId;

		$menu=PageMenu::model()->activeItem()->with('articles')->findbyPk($menuId);
		if($menu===null)
			$this->redirect(array('article/index'));

		$this->render('index',array(
			'menu'=>$menu,
		));
	}

	/**
	 * Manages models.
	 * @param integer menu id
	 */
	public function actionAdmin($menuId=null)
	{
		$model=new PageArticle('search');

		if(isset($_GET['PageArticle']))
			$model->attributes=$_GET['PageArticle'];
		elseif ($menuId)
			$model->menu_id=$menuId;

		$this->render('admin',array(
			'model'=>$model
		));
	}

	/**
	 * List models by search query
	 * @param string $q the query search word
	 */
	public function actionSearch($q=null)
	{
		$this->layout='page';

		if($q===null || mb_strlen($q)<2)
		{
			Yii::app()->user->setFlash('search.short',Yii::t('PageModule.ui','Search term too short or missing!'));
			$this->redirect(array('article/index'));
		}

		$model=new PageArticle('search');
		$model->searchTerm=$q;

		$this->render('search',array(
			'dataProvider'=>$model->search('visible'),
			'q'=>$q,
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
				$this->_model=PageArticle::model()->active()->findbyPk($_GET['id']);
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
		if(isset($_POST['ajax']) && $_POST['ajax']==='article-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
