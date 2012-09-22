<?php 
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'Install'); 
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'Users')=>array('/user/default/admin'),
	Yii::t('UserModule.ui', 'Installation')=>array('index'),
	Yii::t('UserModule.ui', 'Installation is complete'),
);
?>

<h2><?php echo Yii::t('UserModule.ui', 'Installation is complete'); ?></h2>

<p><?php echo CHtml::link(Yii::t('UserModule.ui', 'Manage users'), array('/user/default/admin'));?></p>