<?php

namespace Usps\Api;

use Usps\lib\Array2Xml;
use Usps\lib\Xml2Array;
use Usps\Api\Models\AbstractBase;


/**
 * USPS Base class
 * used to perform the actual api calls
 * @since 1.0
 * @author Vincent Gabriel
 */
abstract class AbstractClientBase extends AbstractBase
{
    const LIVE_API_URL = 'https://secure.shippingapis.com/ShippingAPI.dll';
    const TEST_API_URL = 'https://stg-secure.shippingapis.com/ShippingAPI.dll';

    protected $allowed = [
        "username",
        "errorCode",
        "errorMessage",
        "response",
        "headers",
        "arrayResponse",
        "postFields",
        "apiVersion",
        "testMode",
    ];

    /**
     * @var array - different kind of supported api calls by this wrapper
     */
    protected $apiCodes = [
        'RateV2' => 'RateV2Request',
        'RateV4' => 'RateV4Request',
        'IntlRateV2' => 'IntlRateV2Request',
        'Verify' => 'AddressValidateRequest',
        'ZipCodeLookup' => 'ZipCodeLookupRequest',
        'CityStateLookup' => 'CityStateLookupRequest',
        'TrackV2' => 'TrackFieldRequest',
        'FirstClassMail' => 'FirstClassMailRequest',
        'SDCGetLocations' => 'SDCGetLocationsRequest',
        'ExpressMailLabel' => 'ExpressMailLabelRequest',
        'PriorityMail' => 'PriorityMailRequest',
        'OpenDistributePriorityV2' => 'OpenDistributePriorityV2.0Request',
        'OpenDistributePriorityV2Certify' => 'OpenDistributePriorityV2.0CertifyRequest',
        'ExpressMailIntl' => 'ExpressMailIntlRequest',
        'PriorityMailIntl' => 'PriorityMailIntlRequest',
        'FirstClassMailIntl' => 'FirstClassMailIntlRequest',
    ];

    /**
     * Default options for curl.
     */
    public static $CURL_OPTS = [
        CURLOPT_CONNECTTIMEOUT  => 30,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 60,
        CURLOPT_FRESH_CONNECT   => 1,
        CURLOPT_PORT            => 443,
        CURLOPT_USERAGENT       => 'usps-php',
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_RETURNTRANSFER  => true,
    ];

    /**
     * Return the post data fields as an array
     * @return array
     */
    public function getPostData()
    {
        $fields = array('API' => $this->apiVersion, 'XML' => $this->getXMLString());
    
        return $fields;
    }

    /**
     * Response api name
     * @return string
     */
    public function getResponseApiName()
    {
        return str_replace('Request', 'Response', $this->apiCodes[$this->apiVersion]);
    }

    /**
     * Makes an HTTP request. This method can be overriden by subclasses if
     * developers want to do fancier things or use something other than curl to
     * make the request.
     *
     * @param CurlHandler optional initialized curl handle
     * @return String the response text
     */
    protected function doRequest($curl = null)
    {
        if (!$curl) {
            $curl = curl_init();
        }

        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($this->getPostData(), null, '&');
        $opts[CURLOPT_URL] = $this->getEndpoint();

        // Replace 443 with 80 if it's not secured
        if (strpos($opts[CURLOPT_URL], 'https://')===false) {
            $opts[CURLOPT_PORT] = 80;
        }

        // set options
        curl_setopt_array($curl, $opts);

        // execute
        $this->response = curl_exec($curl);
        $this->headers = curl_getinfo($curl);

        // fetch errors
        $this->errorCode = curl_errno($curl);
        $this->errorMessage = curl_error($curl);

        // Convert response to array
        $this->convertResponseToArray();

        // If it failed then set error code and message
        if ($this->isError()) {
            // Find the error number
            $errorInfo = $this->getValueByKey($this->arrayResponse, 'Error');

            if ($errorInfo) {
                $this->errorCode = $errorInfo['Number'];
                $this->errorMessage = $errorInfo['Description'];
            }
        }

        // close
        curl_close($curl);

        return $this->response;
    }

    public function getEndpoint()
    {
        return $this->testMode ? self::TEST_API_URL : self::LIVE_API_URL;
    }

    /**
     * Return the xml string built that we are about to send over to the api
     * @return string
     */
    protected function getXMLString()
    {
        // Add in the defaults
        $postFields = array(
            '@attributes' => array('USERID' => $this->username),
        );

        // Add in the sub class data
        $postFields = array_merge($postFields, $this->postFields);

        $xml = Array2Xml::createXML($this->apiCodes[$this->apiVersion], $postFields);

        return $xml->saveXML();
    }

    /**
     * Did we encounter an error?
     * @return boolean
     */
    public function isError()
    {
        $headers = $this->headers;
        $response = $this->arrayResponse;

        // First make sure we got a valid response
        if ($headers['http_code'] != 200) {
            return true;
        }

        // Make sure the response does not have error in it
        if (isset($response['Error'])) {
            return true;
        }

        // Check to see if we have the Error word in the response
        if(strpos($this->response(), '<Error>') !== false) {
            return true;
        }

        // No error
        return false;
    }

    /**
     * Was the last call successful
     * @return boolean
     */
    public function isSuccess()
    {
        return !$this->isError() ? true : false;
    }

    /**
    * Return the response represented as string
    * @return array
    */
    public function convertResponseToArray()
    {
        if ($this->response) {
            $this->arrayResponse = XML2Array::createArray($this->response);
        }

        return $this->arrayResponse;
    }

    /**
     * Find a key inside a multi dim. array
     * @param array $array
     * @param string $key
     * @return mixed
     */
    protected function getValueByKey($array, $key)
    {
        foreach ($array as $k => $v) {
            if ($k == $key) {
                return $v;
            }

            if (is_array($v)) {
                if ($return = $this->getValueByKey($v, $key)) {
                    return $return;
                }
            }
        }

        // Nothing matched
        return null;
    }
}
