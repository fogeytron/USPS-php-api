<?php

namespace Usps\Api;

use Usps\Api\Models\Address;

/**
 * USPS Zip code lookup by city/state
 * used to find a zip code by city/state lookup
 * @since 1.0
 * @author Vincent Gabriel
 */
class ZipCodeLookup extends AbstractClientBase
{
    public function __construct($data = [])
    {
        parent::__construct($data);
        
        if (empty($this->apiVersion)) $this->apiVersion = "ZipCodeLookup";
    }
    
    /**
     * Perform the API call
     * @return string
     */
    public function lookup() { return $this->doRequest(); }

    /**
     * Add Address to the stack
     * @param Usps\Api\Models\Address object $address
     * @param string $id the address unique id
     * @return void
     */
    public function addAddress(Address $address, $id = null)
    {
        $packageId = $id !== null ? $id : ((count($this->postFields)+1));
        $postFields = $this->postFields;
        if (empty($postFields['Address'])) $postFields['Address'] = [];
        $postFields['Address'][] = array_merge(array('@attributes' => array('ID' => $packageId)), $address->data());
        $this->postFields = $postFields;
    }
}
