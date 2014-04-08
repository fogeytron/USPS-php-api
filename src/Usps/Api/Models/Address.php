<?php

namespace Usps\Api\Models;

/**
 * USPS Address Class
 * used across other class to create addresses represented as objects
 * @since 1.0
 * @author Vincent Gabriel
 */
class Address extends AbstractBase
{
    protected $allowed = [
        "FirmName",
        "Address1",
        "Address2",
        "City",
        "State",
        "Zip5",
        "Zip4",
    ];
}
