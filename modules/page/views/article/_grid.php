<?php $this->widget('ext.widgets.grid.groupgridview.XGroupGridView', array(
	'id'=>'article-grid',
	'dataProvider'=>$dataProvider,
	'hideHeader'=>true,
	'enableSorting'=>false,
	'summaryText'=>false,
	'cssFile'=>$this->module->gridCssFile,
	'itemsCssClass'=>$this->module->gridCssClass,
	'rowCssClass'=>null, // no zebra
	'selectableRows'=>0,
    'extraRowColumns'=>array('menu_id'),
	'extraRowExpression'=>'$data->menu->title',
	'rowCssClassExpression'=>'$data->menu->typeCssClassName',
	'template'=>$template,
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
			'visible'=>$this->isAdminAccess() && $this->action->id=='admin'
		),
		array(
			'class'=>'CButtonColumn',
			'template'=>'{update} {delete}',
			'updateButtonUrl'=>'$this->grid->controller->createUrl("update",array("id"=>$data->primaryKey))',
			'deleteButtonUrl'=>'$this->grid->controller->createUrl("delete",array("id"=>$data->primaryKey))',
			'deleteConfirmation'=>Yii::t('PageModule.ui','Are you sure to delete this article?'),
			'visible'=>$this->isAdminAccess() && $this->action->id=='admin'
		),
	),
)); ?>