<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'Users');
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'Users'),
);
?>

<h2><?php echo Yii::t('UserModule.ui', 'Users'); ?></h2>

<?php //if(Yii::app()->user->checkAccess('adminSystem')): ?>
	<?php echo CHtml::link(Yii::t('UserModule.ui', 'New User'), array('create'));?>
<?php //endif; ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'user-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'pager'=>array(
		'header'=>'',
		'firstPageLabel'=>'&lt;&lt;',
		'prevPageLabel'=>'&lt;',
		'nextPageLabel'=>'&gt;',
		'lastPageLabel'=>'&gt;&gt;',
	),
	'columns'=>array(
		'username',
		'firstname',
		'lastname',
		array(
			'name'=>'usergroup',
			'value'=>'$data->groupName',
			'filter'=>User::model()->groupOptions,
			'visible'=>User::model()->groupOptions===array() ? false : true,
		),
		array(
			'name'=>'role',
			'value'=>'$data->roleName',
			'filter'=>User::model()->roleOptions,
			'visible'=>User::model()->roleOptions===array() ? false : true,
		),
		array(
			'class'=>'CLinkColumn',
			'labelExpression'=>'Yii::t("UserModule.ui", "Change Password")',
			'urlExpression'=>'$this->grid->controller->createReturnableUrl("changePassword",array("id"=>$data->id))',
			'htmlOptions'=>array('style'=>'width:100px'),
		),
		array(
			'class'=>'CButtonColumn',
			'template'=>Yii::app()->user->name=='admin' ? '{update} {delete}':'{update}',
			'updateButtonUrl'=>'$this->grid->controller->createReturnableUrl("update",array("id"=>$data->id))',
			'deleteButtonUrl'=>'$this->grid->controller->createReturnableUrl("delete",array("id"=>$data->id))',
			'deleteConfirmation'=>Yii::t('UserModule.ui','Are you sure to delete this item?'),
		),
	),
)); ?>
