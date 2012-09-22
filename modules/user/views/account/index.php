<?php
$this->pageTitle=Yii::app()->name.' - '.Yii::t('UserModule.ui', 'Users');
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'My Account'),
);
?>

<h2><?php echo Yii::t('UserModule.ui','My Account'); ?></h2>

<div class="actionMenu">
	<?php echo CHtml::link(Yii::t('UserModule.ui','Update Data'),array('update'));?>
	<span class="sep"> | </span>
	<?php echo CHtml::link(Yii::t('UserModule.ui','Change Password'),array('changePassword'));?>
</div>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'username',
		'firstname',
		'lastname',
		array(
			'name'=>'usergroup',
			'value'=>$model->groupName,
			'template'=>User::model()->groupOptions===array() ? false : null,
		),
		array(
			'name'=>'role',
			'value'=>$model->roleName,
			'template'=>User::model()->roleOptions===array() ? false : null,
		),

	),
)); ?>