<div class="emailDebug">
	<h2>.: Dumping email</h2>
	<p>The email extension is in debug mode, which means that the email was not actually sent but is dumped below instead</p>
	<h3>Email</h3>
	<strong>To:</strong> <?php echo CHtml::encode($to) ?><br />
	<strong>Subject:</strong> <?php echo CHtml::encode($subject) ?>
	<div class="emailMessage">
	<?php if ($type=='text/plain'):?>
		<?php echo nl2br($message); ?>
	<?php else:?>
		<?php echo $message; ?>
	<?php endif; ?>
	</div>
	<h3>Additional headers</h3>
	<p>
	<?php foreach ($headers as $value):?>
		<?php echo CHtml::encode($value); ?><br />
	<?php endforeach; ?>
	</p>
</div>