<system schemaVersion="1" xmlns="http://www.nortal.com/FlairPoint/RecordsManagement/WebCapture">
	<hierarchy>
		<parent>
			<referenceCode><?php echo $parentReferenceCode ?></referenceCode>
		</parent>
		<children>
			<case merge="referenceCode" referenceCode="<?php echo $mergeReferenceCode ?>">
				<children>
					<document name="<?php echo $documentName ?>" contentType="<?php echo $documentType ?>">
						<?php echo $metadata ?>
					</document>
				</children>
			</case>
		</children>
	</hierarchy>
</system>