<?php

namespace BankartPaymentGateway\Client\Transaction\Base;

/**
 * Interface AddToCustomerProfileInterface
 * @package BankartPaymentGateway\Client\Transaction\Base
 */
interface AddToCustomerProfileInterface {

    /**
     * @param bool $addToCustomerProfile
     */
    public function setAddToCustomerProfile($addToCustomerProfile);

    /**
     * @return bool
     */
    public function getAddToCustomerProfile();

    /**
     * @param string $profileGuid
     */
    public function setCustomerProfileGuid($profileGuid);

    /**
     * @return string
     */
    public function getCustomerProfileGuid();

    /**
     * @param string $identification
     */
    public function setCustomerProfileIdentification($identification);

    /**
     * @return string
     */
    public function getCustomerProfileIdentification();
    
    /**
     * @return bool
     */
    public function getMarkAsPreferred();

    /**
     * @param bool $markAsPreferred
     */
    public function setMarkAsPreferred($markAsPreferred);
}
