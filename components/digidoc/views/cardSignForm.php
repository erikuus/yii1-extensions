<div class="idSignModalContent">
	<div id="idSignModalErrorContainer" style="display: none;"></div>
	<p><?php echo Yii::t('XDigiDocWidget.digidoc', 'Make sure ID-card is inserted correctly and browser extension installed and enabled! Only then press button.'); ?></p>
	<?php if($enableCardSignFields) :?>
	<table>
		 <tbody>
			 <tr>
				 <td><label for="idSignCity"><?php echo Yii::t('XDigiDocWidget.digidoc', 'City'); ?>:</label></td>
				 <td><input id="idSignCity" type="text"></td>
			 </tr>
			 <tr>
				 <td><label for="idSignState"><?php echo Yii::t('XDigiDocWidget.digidoc', 'State'); ?>:</label></td>
				 <td><input id="idSignState" type="text"></td>
			 </tr>
			 <tr>
				 <td><label for="idSignCountry"><?php echo Yii::t('XDigiDocWidget.digidoc', 'Country'); ?>:</label></td>
				 <td><input id="idSignCountry" type="text"></td>
			 </tr>
			 <tr>
				 <td><label for="idSignPostalCode"><?php echo Yii::t('XDigiDocWidget.digidoc', 'Postal Code'); ?>:</label></td>
				 <td><input id="idSignPostalCode" type="text"></td>
			 </tr>
			 <tr>
				 <td><label for="idSignRole"><?php echo Yii::t('XDigiDocWidget.digidoc', 'Role'); ?>:</label></td>
				 <td><textarea id="idSignRole" cols="30" rows="10"></textarea></td>
			 </tr>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<div id="idSignModalFooter">
	<input type="hidden" name="_token" value="<?php echo $token; ?>">
	<button type="button" class="<?php echo $buttonCssClass; ?>" onclick="ee.sk.hashcode.IDCardSign()">
		<?php echo Yii::t('XDigiDocWidget.digidoc', 'Sign the document'); ?>
	</button>
	<?php if($helpUrl) :?>
		<?php echo CHtml::link(Yii::t('XDigiDocWidget.digidoc', 'Help'), $helpUrl, array(
			'class'=>$helpLinkCssClass,
			'target'=>'_blank'
		));?></p>
	<?php endif;?>
</div>
