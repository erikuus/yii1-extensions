<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'Update Menu');

$this->breadcrumbs=array(
	Yii::t('PageModule.ui','Articles')=>array('article/index'),
	Yii::t('PageModule.ui','Manage Menu')=>array('admin'),
	Yii::t('PageModule.ui','Update Menu'),
);
?>

<h2><?php echo Yii::t('PageModule.ui', 'Update Menu'); ?></h2>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>