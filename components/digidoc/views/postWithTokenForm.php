<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	<title>Redirect</title>
</head>
<body>
	<?php echo CHtml::beginForm($url, 'post', array('id'=>'digidoc-post-token')); ?>
	<?php echo CHtml::hiddenField('_token',  $token) ?>
	<?php echo CHtml::endForm(); ?>
	<script type="text/javascript">
	/*<![CDATA[*/
		document.getElementById("digidoc-post-token").submit();
	/*]]>*/
	</script>
</body>
</html>