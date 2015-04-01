<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	<title>Submit Payment</title>
</head>

<body>
	<?php echo CHtml::beginForm($serviceUrl, 'post', array('id'=>'payment-form')); ?>

		<?php foreach ($params as $name=>$value): ?>
			<?php echo CHtml::hiddenField($name,  $value) ?>
		<?php endforeach; ?>

	<?php echo CHtml::endForm(); ?>

	<script type="text/javascript">
	/*<![CDATA[*/
		document.getElementById("payment-form").submit();
	/*]]>*/
	</script>

</body>
</html>