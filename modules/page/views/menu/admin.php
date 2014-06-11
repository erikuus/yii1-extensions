<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'Manage Menu');

Yii::app()->clientScript->registerCss('gridSpecificStyle', "
	.grid-view table.items tr.type-header {
		text-transform: uppercase;
		background: #e8e8e8;
		font-weight: bold;
		color: #333333;
	}
", 'screen', CClientScript::POS_HEAD);

$this->breadcrumbs=array(
	Yii::t('PageModule.ui','Articles')=>array('article/index'),
	Yii::t('PageModule.ui', 'Manage Menu'),
);
?>

<div style="float:right;">
	<?php echo CHtml::link(Yii::t('PageModule.ui', 'New Menu'), array('create')); ?>
</div>

<h2><?php echo Yii::t('PageModule.ui', 'Manage Menu'); ?></h2>

<div style="clear:both"></div>

<?php $this->widget('ext.widgets.alert.XAlert',array(
	'alerts'=>array(
		'saved'=>'success'
	)
)); ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'menu-grid',
	'dataProvider'=>$dataProvider,
	'hideHeader'=>true,
	'enableSorting'=>false,
	'summaryText'=>false,
	'cssFile'=>$this->module->gridCssFile,
	'itemsCssClass'=>$this->module->gridCssClass,
	'rowCssClass'=>null, // no zebra
	'rowCssClassExpression'=>'$data->typeCssClassName',
	'columns'=>array(
		array(
			'name'=>'title',
			'type'=>'raw',
			'value'=>'$data->formattedItem',
		),
		array(
			'header'=>'',
			'class'=>'ext.widgets.grid.reordercolumn.XReorderColumn',
			'name'=>'position',
		),
		array(
			'class'=>'CButtonColumn',
			'template'=>'{update} {delete}',
			'deleteConfirmation'=>Yii::t('PageModule.ui','Are you sure to delete this item?'),
		),
	),
)); ?>