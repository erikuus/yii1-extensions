<?php
/**
 * Class DigiDocService
 */

final class DigiDocService
{
	const RESPONSE_STATUS_OK = 'OK';

    /**
     * @var array - Different MID status responses and there corresponding error messages as explained in
     * DigiDocServices specification.
     */
    public $getMidStatusResponseErrorMessages = array(
        'EXPIRED_TRANSACTION'  => 'There was a timeout before the user could sign with Mobile ID.',
        'USER_CANCEL'          => 'User has cancelled the signing operation.',
        'NOT_VALID'            => 'Signature is not valid.',
        'MID_NOT_READY'        => 'Mobile ID is not yet available for this phone. Please try again later.',
        'PHONE_ABSENT'         => 'Mobile phone is not reachable.',
        'SENDING_ERROR'        => 'The Mobile ID message could not be sent to the mobile phone.',
        'SIM_ERROR'            => 'There was a problem with the mobile phones SIM card.',
        'OCSP_UNAUTHORIZED '   => 'Mobile ID user is not authorized to make OCSP requests.',
        'INTERNAL_ERROR '      => 'There was an internal error during signing with Mobile ID.',
        'REVOKED_CERTIFICATE ' => 'The signers certificate is revoked.',
    );
    /**
     * @var SoapClient - DigiDocService client.
     */
    private $client;

    /**
     * In here the SOAP Client is initiated for communication with DigiDocService.
     * @param string $location DigiDocService location
     */
    private function __construct($location)
    {
        $this->client = new \SoapClient(
            null,
            array(
                'location' => $location,
                'uri'      => 'http://www.sk.ee/DigiDocService/DigiDocService_2_3.wsdl',
                'use'      => SOAP_LITERAL,
                'trace'    => true,
            )
        );
    }

    /**
     * This method is the only way to get an instance of DigiDocService.
     * @param string $location DigiDocService location
     * @return DigiDocService
     */
    public static function instance($location)
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new DigiDocService($location);
        }

        return $inst;
    }

    /**
     * Start Digidoc Service session
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function StartSession($params)
    {
        return $this->invoke('StartSession', $params);
    }

    /**
     * Helper method through which all invocations of DigiDocService are actually made.
     * @param $serviceName - Name of the DigiDocServices operation to be invoked.
     * @param $params - Request parameters for the operation.
     * @return mixed - SOAP operation Response.
     * @throws \Exception
     */
    private function invoke($serviceName, $params)
    {
        try {
            $response = $this->client->$serviceName($this->getSoapVar($params));

            if ($response === self::RESPONSE_STATUS_OK || $response['Status'] === self::RESPONSE_STATUS_OK) {
                return $response;
            }

            throw new SoapClientException($response['Status']);

        } catch (\Exception $e) {
            $this->propagateSoapException($e, $serviceName);
        }

        return null;
    }

    /**
     * Helper method for converting a usual array to a SoapVar used by SoapClient.
     * @param $data - The usual array which is to be turned to SoapVar
     * @return SoapVar - The resulting SoapVar
     */
    private function getSoapVar(array $data)
    {
        return new \SoapVar($this->getXmlString($data), XSD_ANYXML);
    }

    /**
     * Helper method for construction an XML from an array.
     * @param $data   - The array to be converted to an XML string
     * @param $result - XML to which the rest of the XML is appended.
     * @return string - The resulting XML string.
     */
    private function getXmlString(array $data, $result = '')
    {
        foreach ($data as $key => $value) {
            $result .= '<'.$key.'>';
            if (is_array($value)) {
                $result .= $this->getXmlString($value, $result);
            } else {
                $result .= htmlspecialchars($value);
            }
            $result .= '</'.$key.'>';
        }

        return $result;
    }

    /*
    *  Rest of the methods are invocations of DDS operations. What each DigiDocService operation does is described in
    *  DigiDocService's specification at https://www.sk.ee/upload/files/DigiDocService_spec_eng.pdf
    */

    /**
     * Helper method for handling Exceptions from invocations of DDS properly.
     * @param $exception - The Exception from DDS.
     * @param $service_name - Name of the DigiDocService operation during which the exception occured.
     * @throws Exception - The wrapper Exception of the thrown exception.
     */
    private function propagateSoapException($exception, $service_name)
    {
        $detailMessage = '';
        if (isset($exception->detail, $exception->detail->message)) {
            $detailMessage = $exception->detail->message;
        }

        $code = (!!$exception->getCode() ? $exception->getCode().' - ' : '').$exception->getMessage();
        throw new SoapClientException("There was an error invoking $service_name: ".($code.(empty($detailMessage)
                    ? ''
                    : ' - '.$detailMessage)));
    }

    /**
     * Close Digidoc Service session
     * @param array $params
     * @return mixed
     */
    public function CloseSession($params)
    {
        return $this->invoke('CloseSession', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function GetSignedDoc($params)
    {
        return $this->invoke('GetSignedDoc', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function GetSignedDocInfo($params)
    {
        return $this->invoke('GetSignedDocInfo', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function PrepareSignature($params)
    {
        return $this->invoke('PrepareSignature', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function FinalizeSignature($params)
    {
        return $this->invoke('FinalizeSignature', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function RemoveSignature($params)
    {
        return $this->invoke('RemoveSignature', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function MobileSign($params)
    {
        return $this->invoke('MobileSign', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function GetStatusInfo($params)
    {
        return $this->invoke('GetStatusInfo', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function AddDataFile($params)
    {
        return $this->invoke('AddDataFile', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function RemoveDataFile($params)
    {
        return $this->invoke('RemoveDataFile', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function CreateSignedDoc($params)
    {
        return $this->invoke('CreateSignedDoc', $params);
    }
}