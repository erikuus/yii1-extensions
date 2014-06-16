<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('PageModule.ui', 'Search Result');

$this->breadcrumbs=array(
	Yii::t('PageModule.ui', 'Articles')=>array('index'),
	Yii::t('PageModule.ui', 'Search Result')
);

$cs=Yii::app()->clientScript;

if($this->module->pageCssFile===null)
	$cs->registerCssFile($this->getAsset('/css/page.css'));
else if($this->module->pageCssFile!==false)
	$cs->registerCssFile($this->module->pageCssFile);
?>

<h1><?php echo Yii::t('PageModule.ui', 'Search Result'); ?></h1>

<div class="filter-display">
	<?php echo CHtml::encode($q); ?>
	<?php echo CHtml::link('&times', array('index'),array('class'=>'close'));?>
</div>

<?php $this->renderPartial('_grid', array(
	'dataProvider'=>$dataProvider,
	'template'=>'{items}',
)); ?>