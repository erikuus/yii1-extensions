<div class="mobileSignModalContent">
    <div id="mobileSignErrorContainer" style="display: none;"></div>
	<p><?php echo Yii::t('XDigiDocWidget.digidoc', 'Phone number must start with country prefix.<br>Example: +37212345678'); ?></p>
    <table>
        <tbody>
	        <tr>
	            <td><label for="mid_PhoneNumber"><?php echo Yii::t('XDigiDocWidget.digidoc', 'Mobile phone number'); ?>:</label></td>
	            <td><input id="mid_PhoneNumber" type="text" value="<?php echo $mobilePhoneNumber; ?>"></td>
	        </tr>
	        <tr>
	            <td><label for="mid_idCode"><?php echo Yii::t('XDigiDocWidget.digidoc', 'Social security number'); ?>:</label></td>
	            <td><input id="mid_idCode" type="text" value="<?php echo $idCode;?>"></td>
	        </tr>
    	</tbody>
    </table>
</div>
<div id="mobileSignModalFooter">
    <input type="hidden" name="_token" value="<?php echo $token; ?>">
    <button type="button" class="<?php echo $buttonCssClass; ?>" onclick="ee.sk.hashcode.StartMobileSign()">
    	<?php echo Yii::t('XDigiDocWidget.digidoc', 'Sign the document'); ?>
    </button>
</div>