<?php

namespace BankartPaymentGateway\Client\CustomerProfile;

use BankartPaymentGateway\Client\Data\PaymentData\PaymentData;
use BankartPaymentGateway\Client\Json\DataObject;

/**
 * Class PaymentInstrument
 *
 * @package BankartPaymentGateway\Client\CustomerProfile
 *
 * @property string $method
 * @property string $paymentToken
 * @property \DateTime $createdAt
 * @property PaymentData $paymentData
 * @property bool $isPreferred
 */
class PaymentInstrument extends DataObject {

    const METHOD_CARD = 'card';
    const METHOD_IBAN = 'iban';
    const METHOD_WALLET = 'wallet';


    /**
     * @param \DateTime|string $createdAt
     *
     * @return PaymentInstrument
     * @throws \Exception
     */
    public function setCreatedAt($createdAt) {
        if (!empty($createdAt) && is_string($createdAt)) {
            $createdAt = new \DateTime($createdAt);
        }
        $this->createdAt = $createdAt;
        return $this;
    }


}