<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	<title>Submit Payment</title>
</head>

<body>
	<?php echo CHtml::beginForm($serviceUrl, 'post', array('id'=>'ecom-form')); ?>

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

	<?php echo CHtml::endForm(); ?>

	<script type="text/javascript">
	/*<![CDATA[*/
		document.getElementById("ecom-form").submit();
	/*]]>*/
	</script>

</body>
</html>