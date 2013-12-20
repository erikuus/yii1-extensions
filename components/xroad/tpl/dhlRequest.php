<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<soap:Envelope xmlns:ns="http://schemas.xmlsoap.org/soap/envelope/"
	soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
	xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:xtee="http://x-tee.riik.ee/xsd/xtee.xsd">
	<soap:Header>
		<xtee:asutus xsi:type="xsd:string"><?php echo $asutus; ?></xtee:asutus>
		<xtee:andmekogu xsi:type="xsd:string"><?php echo $andmekogu; ?></xtee:andmekogu>
		<xtee:isikukood xsi:type="xsd:string"><?php echo $isikukood; ?></xtee:isikukood>
		<xtee:ametniknimi xsi:type="xsd:string"><?php echo $ametniknimi; ?></xtee:ametniknimi>
		<xtee:id xsi:type="xsd:string"><?php echo $id; ?></xtee:id>
		<xtee:nimi xsi:type="xsd:string"><?php echo $nimi; ?></xtee:nimi>
	</soap:Header>
	<soap:Body>
		<?php echo $soapBody; ?>
	</soap:Body>
</soap:Envelope>