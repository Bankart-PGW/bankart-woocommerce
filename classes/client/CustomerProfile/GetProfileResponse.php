<?php

namespace BankartPaymentGateway\Client\CustomerProfile;

use BankartPaymentGateway\Client\Json\ResponseObject;

/**
 * Class GetProfileResponse
 *
 * @package BankartPaymentGateway\Client\CustomerProfile
 *
 * @property bool                $profileExists
 * @property string              $profileGuid
 * @property string              $customerIdentification
 * @property string              $preferredMethod
 * @property CustomerData        $customer
 * @property PaymentInstrument[] $paymentInstruments
 */
class GetProfileResponse extends ResponseObject {

}