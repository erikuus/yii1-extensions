<ns1:AddFile>
	<ns1:request>
		<ns1:document id="<?php echo $documentId ?>">
			<ns1:fileVersion fileName="<?php echo $fileName ?>" mimeType="<?php echo $mimeType ?>"><?php echo $fileContent ?></ns1:fileVersion>
		</ns1:document>
	</ns1:request>
</ns1:AddFile>