<?php 
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'Users'); 
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'Users'),
);
?>

<h2><?php echo Yii::t('UserModule.ui', 'Installation'); ?></h2>

<p><?php echo Yii::t('UserModule.ui', 'Create table "{table}" for user module.', 
	array('{table}'=>Yii::app()->controller->module->userTable)); ?></p>

<?php echo CHtml::linkButton(Yii::t('UserModule.ui', 'Create Table'),array(
	'submit'=>array('create'), 'confirm'=>Yii::t('UserModule.ui','Are you sure to create new table?'),
));?>