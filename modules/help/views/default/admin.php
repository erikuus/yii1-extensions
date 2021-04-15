<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('HelpModule.ui', 'Helps');
$this->breadcrumbs=array(
	Yii::t('HelpModule.ui', 'Helps'),
);
?>

<h2><?php echo Yii::t('HelpModule.ui', 'Helps'); ?></h2>

<?php echo CHtml::link(Yii::t('HelpModule.ui', 'New'), array('create'), array(
	'class'=>$this->module->primaryLinkCssClass,
	'style'=>'float:right; margin-top:-40px'
)); ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'dataProvider'=>$dataProvider,
	'pager'=>array(
		'header'=>'',
		'firstPageLabel'=>'&lt;&lt;',
		'prevPageLabel'=>'&lt;',
		'nextPageLabel'=>'&gt;',
		'lastPageLabel'=>'&gt;&gt;',
	),
	'columns'=>array(
		array(
			'name'=>'code',
			'visible'=>Yii::app()->user->name=='admin',
		),
		'title_et',
		'title_en',
		array(
			'class'=>$this->module->buttonColumnClass,
			'template'=>'{update} {delete}',
			'updateButtonUrl'=>'$this->grid->controller->createReturnableUrl("update",array("id"=>$data->id))',
			'deleteButtonUrl'=>'$this->grid->controller->createReturnableUrl("delete",array("id"=>$data->id))',
			'deleteConfirmation'=>Yii::t('HelpModule.ui','Are you sure to delete this item?'),
			'buttons'=>array(
				'delete'=>array(
					'visible'=>'Yii::app()->user->name=="admin"',
				),
			),
		),
	),
)); ?>