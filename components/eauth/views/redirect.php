<!DOCTYPE html>
<html>
  <head>
	<script type="text/javascript">
		<?php
			$code = 'if (window.opener) {';
			$code .= 'window.close();';
			if ($redirect)
				$code .= 'window.opener.location = \''.addslashes($url).'\';';
			$code .= '}';
			$code .= 'else {';
			if ($redirect)
				$code .= 'window.location = \''.addslashes($url).'\';';
			$code .= '}';
			echo $code;
		?>
	</script>
  </head>
  <body>
	<h2 id="title" style="display:none;"><?php echo Yii::t('EAuth.eauth','Redirecting back to the application');?>...</h2>
	<h3 id="link"><a href="<?php echo $url; ?>"><?php echo Yii::t('EAuth.eauth','Click here to return to the application');?></a></h3>
	<script type="text/javascript">
		document.getElementById('title').style.display = '';
		document.getElementById('link').style.display = 'none';
	</script>
  </body>
</html>