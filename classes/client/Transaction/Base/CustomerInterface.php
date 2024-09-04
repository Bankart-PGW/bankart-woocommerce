<?php

namespace BankartPaymentGateway\Client\Transaction\Base;
use BankartPaymentGateway\Client\Data\Customer;

/**
 * Interface CustomerInterface
 *
 * @package BankartPaymentGateway\Client\Transaction\Base
 */
interface CustomerInterface {

    /**
     * @return Customer
     */
    public function getCustomer();

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer);

}