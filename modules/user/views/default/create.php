<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'New User');
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'Users')=>array('admin'),
	Yii::t('UserModule.ui', 'New User'),
);
?>

<h2><?php echo Yii::t('UserModule.ui', 'New User'); ?></h2>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>