<?php

namespace BankartPaymentGateway\Client\CustomerProfile;

use BankartPaymentGateway\Client\Json\ResponseObject;

/**
 * Class UpdateProfileResponse
 *
 * @package BankartPaymentGateway\Client\CustomerProfile
 *
 * @property string       $profileGuid
 * @property string       $customerIdentification
 * @property CustomerData $customer
 * @property array        $changedFields
 */
class UpdateProfileResponse extends ResponseObject {

}