<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'New Article');

$this->breadcrumbs=array(
	Yii::t('PageModule.ui','Articles')=>array('index'),
	Yii::t('PageModule.ui','Manage Articles')=>array('admin'),
	Yii::t('PageModule.ui','New Article'),
);
?>

<h2><?php echo Yii::t('PageModule.ui', 'New Article'); ?></h2>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>