<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'Manage Articles');

$this->breadcrumbs=array(
	Yii::t('PageModule.ui','Articles')=>$model->menu_id ? array('index','menuId'=>$model->menu_id) : array('index'),
	Yii::t('PageModule.ui', 'Manage Articles'),
);

$cs=Yii::app()->clientScript;

$cs->registerCss('gridSpecificStyle', "
	.grid-view table tr.type-hidden-content a {
		color: #999999;
	}
", 'screen', CClientScript::POS_HEAD);

$cs->registerScript('search', "
    $('.filter form').live('submit',function(){
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
		'save.success'=>'success'
	)
)); ?>

<?php $this->beginWidget('CClipWidget', array('id'=>'toolbarClip')); ?>
	<div style="float:right;">
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'New Article'), $this->createReturnableUrl('create',array('menuId'=>$model->menu_id))); ?>
	</div>
	<div class="filter">
		<?php $this->renderPartial('_filter', array('model'=>$model)); ?>
	</div>
	<div style="clear:both;"></div>
<?php $this->endWidget(); ?>

<?php $this->renderPartial('_grid', array(
	'dataProvider'=>$model->search('active'),
	'template'=>$this->clips['toolbarClip'].'<br />{items}',
)); ?>