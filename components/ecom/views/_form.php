<?php echo CHtml::beginForm($serviceUrl, 'post'); ?>

	<?php echo CHtml::hiddenField('id',  $serviceId) ?>
	<?php echo CHtml::hiddenField('action', $action) ?>
	<?php echo CHtml::hiddenField('ver', $ver) ?>
	<?php echo CHtml::hiddenField('delivery', $delivery) ?>
	<?php echo CHtml::hiddenField('charEncoding', $charEncoding) ?>
	<?php echo CHtml::hiddenField('cur', $cur) ?>
	<?php echo CHtml::hiddenField('lang', $lang) ?>
	<?php echo CHtml::hiddenField('eamount', $eamount) ?>
	<?php echo CHtml::hiddenField('datetime', $datetime) ?>
	<?php echo CHtml::hiddenField('feedBackUrl', $feedBackUrl) ?>
	<?php echo CHtml::hiddenField('ecuno', $ecuno) ?>
	<?php echo CHtml::hiddenField('mac', $mac) ?>
	<?php echo CHtml::submitButton($payButtonLabel, $payButtonHtmlOptions); ?>

<?php echo CHtml::endForm(); ?>