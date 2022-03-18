<?php

namespace BankartPaymentGateway\Client\CustomerProfile\PaymentData;

/**
 * Class IbanData
 *
 * @package BankartPaymentGateway\Client\CustomerProfile\PaymentData
 *
 * @property string $iban
 * @property string $bic
 * @property string $mandateId
 * @property \DateTime $mandateDate
 */
class IbanData extends PaymentData {

    /**
     * @param \DateTime|string $mandateDate
     * @return IbanData
     */
    public function setMandateDate($mandateDate) {
        if (is_string($mandateDate)) {
            $mandateDate = new \DateTime($mandateDate);
        }
        $this->mandateDate = $mandateDate;
        return $this;
    }

}
