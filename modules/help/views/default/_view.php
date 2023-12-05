<h2 class="font-heading text-lg font-bold text-gray-700 mx-6">
	<?php echo $model->{'title_'.Yii::app()->language}; ?>
	<?php if($this->module->editOnPage): ?>
		<?php echo $this->getEditLink($model->id); ?>
	<?php endif; ?>
</h2>
<?php echo $model->{'content_'.Yii::app()->language}; ?>