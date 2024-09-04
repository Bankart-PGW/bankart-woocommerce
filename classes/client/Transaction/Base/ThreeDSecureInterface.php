<?php

namespace BankartPaymentGateway\Client\Transaction\Base;
use BankartPaymentGateway\Client\Data\ThreeDSecureData;

/**
 * Interface ThreeDSecureInterface
 *
 * @package BankartPaymentGateway\Client\Transaction\Base
 */
interface ThreeDSecureInterface {

    /**
     * @return ThreeDSecureData
     */
    public function getThreeDSecureData();

    /**
     * @param ThreeDSecureData $threeDSecureData
     *
     * @return mixed
     */
    public function setThreeDSecureData($threeDSecureData);

}