<?php
$this->pageTitle=Yii::app()->name . ' - ' . $menu->title;

$cs=Yii::app()->clientScript;

if($this->module->pageCssFile===null)
	$cs->registerCssFile($this->getAsset('/css/page.css'));
else if($this->module->pageCssFile!==false)
	$cs->registerCssFile($this->module->pageCssFile);

$cs->registerScript('enhanceArticleContent', "
	$('.page-article-body>table:not(.layout)').addClass('dataGrid');
	$('.page-article-body>table:not(.layout) tr:nth-child(odd)').addClass('odd');
	$('.page-article-body img').each(function(){
	   $(this).fancybox({
	       href : $(this).attr('src')
	   });
	})
");

$this->breadcrumbs=array(
	$menu->title,
);
?>

<div class="page-wrapper">

	<h1 class="page-article-title">
		<?php echo CHtml::encode($menu->title); ?>
		<?php if($this->isAdminAccess()):?>
			<?php echo CHtml::link(
				CHtml::image($this->getAsset('/images/update.png'),Yii::t('PageModule.ui', 'Update Menu')),
				$this->createReturnableUrl('menu/update',array('id'=>$menu->id))
			);?>
			<?php echo CHtml::link(
				CHtml::image($this->getAsset('/images/admin.png'),Yii::t('PageModule.ui', 'Manage Articles')),
				array('article/admin','menuId'=>$menu->id)
			); ?>
			<?php echo CHtml::link(
				CHtml::image($this->getAsset('/images/new.png'),Yii::t('PageModule.ui', 'New Article')),
				$this->createReturnableUrl('article/create',array('menuId'=>$menu->id))
			); ?>
		<?php endif; ?>
	</h1>

<?php if($menu->content): ?>
	<div class="page-article-side-content">
		<?php echo $menu->content; ?>
	</div>
<?php endif; ?>

<?php if($menu->articles): ?>
	<?php if($menu->articleCount>1): ?>
		<div class="page-article-sub-nav">
			<ol>
			<?php foreach ($menu->articles as $article): ?>
				<li><?php echo CHtml::link(CHtml::encode($article->title), '#article'.$article->id); ?></li>
			<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>

	<?php foreach ($menu->articles as $article): ?>
		<h2 class="page-article-subtitle">
		<?php if($menu->articleCount>1): ?>
			<?php echo CHtml::tag('span', array('id'=>'article'.$article->id), CHtml::encode($article->title)) ?>
		<?php endif; ?>
		<?php if($this->isAdminAccess()):?>
			<?php echo CHtml::link(
					CHtml::image($this->getAsset('/images/update.png'),
					Yii::t('PageModule.ui', 'Update Article')
				),
				$this->createReturnableUrl('article/update',array('id'=>$article->id),array('#'=>'article'.$article->id))
			);?>
		<?php endif?>
		</h2>
		<div class="page-article-body">
			<?php echo $article->content; ?>
			<div class="separator"></div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
</div>

<?php $this->widget('ext.widgets.fancybox.XFancyBox'); ?>