<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'Manage Articles');

$this->breadcrumbs=array(
	Yii::t('PageModule.ui','Articles')=>$model->menu_id ? array('index','menuId'=>$model->menu_id) : array('index'),
	Yii::t('PageModule.ui', 'Manage Articles'),
);

Yii::app()->clientScript->registerScript('search', "
    $('.search-article form').live('submit',function(){
        $.fn.yiiGridView.update('article-grid', {
            data: $(this).serialize()
        });
        return false;
    });
");
?>

<h2><?php echo Yii::t('PageModule.ui', 'Manage Articles'); ?></h2>

<?php $this->widget('ext.widgets.alert.XAlert',array(
	'alerts'=>array(
		'saved'=>'success'
	)
)); ?>

<?php $this->beginWidget('CClipWidget', array('id'=>'toolbarClip')); ?>
	<div style="float:right;">
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'New Article'), $this->createReturnableUrl('create',array('menuId'=>$model->menu_id))); ?>
	</div>
	<div class="search-article">
		<?php $this->renderPartial('_search', array('model'=>$model)); ?>
	</div>
	<div style="clear:both;"></div>
<?php $this->endWidget(); ?>

<?php $this->widget('ext.widgets.grid.groupgridview.XGroupGridView', array(
	'id'=>'article-grid',
	'dataProvider'=>$model->search(),
	'hideHeader'=>true,
	'enableSorting'=>false,
	'summaryText'=>false,
	'cssFile'=>$this->module->gridCssFile,
	'itemsCssClass'=>$this->module->gridCssClass,
	'rowCssClass'=>null, // no zebra
	'selectableRows'=>0,
    'extraRowColumns'=>array('menu_id'),
	'extraRowExpression'=>'$data->menu->title',
	'template'=>$this->clips['toolbarClip'].'<br />{summary}{items}',
	'columns'=>array(
		array(
			'name'=>'title',
			'type'=>'raw',
			'value'=>'$data->titleLink',
		),
		array(
			'class'=>'ext.widgets.grid.reordercolumn.XReorderColumn',
			'name'=>'position',
			'header'=>'',
			'visible'=>$model->reorderVisibility
		),
		array(
			'class'=>'CButtonColumn',
			'template'=>'{update} {delete}',
			'updateButtonUrl'=>'$this->grid->controller->createReturnableUrl("update",array("id"=>$data->primaryKey))',
			'deleteButtonUrl'=>'$this->grid->controller->createReturnableUrl("delete",array("id"=>$data->primaryKey))',
			'deleteConfirmation'=>Yii::t('PageModule.ui','Are you sure to delete this article?'),
		),
	),
)); ?>
